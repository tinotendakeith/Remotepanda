<?php

namespace App\Controllers;

use App\Libraries\TwilioMessenger;
use App\Models\Content;
use App\Models\Customer as CustomerModel;
use App\Models\Users;
use Exception;

class Home extends BaseController
{

    public function dashboard(): string
    {

        helper(["customer", "media", 'html']);

        $data = [];

        $model = new CustomerModel();
        $model->select("*, YEAR(CURRENT_TIMESTAMP()) - YEAR(dob) AS currentAge, DATE_ADD(dob, INTERVAL TIMESTAMPDIFF(YEAR, DATE_ADD(dob, INTERVAL 1 DAY), CURRENT_DATE()) + 1 YEAR) AS nextBirthday");
        $model->orderBy("nextBirthday");
        $model->having("nextBirthday BETWEEN DATE_ADD(CURRENT_DATE(), INTERVAL 1 DAY) AND DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY)");

        $search = $this->request->getGet("search");
        if ($search){
            $model->like("tblcustomers.Name", $search);
        }
        $data["upcomingBirthdays"] = $model->findAll();

        $model->select("*, YEAR(CURRENT_TIMESTAMP()) - YEAR(dob) AS currentAge, DATE_ADD(dob, INTERVAL TIMESTAMPDIFF(YEAR, DATE_ADD(dob, INTERVAL 1 DAY), CURRENT_DATE()) + 0 YEAR) AS nextBirthday");
        $model->orderBy("nextBirthday", "DESC");
        $model->having("nextBirthday BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY) AND DATE_SUB(CURRENT_DATE(), INTERVAL 1 DAY)");

        $search = $this->request->getGet("search");
        if ($search){
            $model->like("tblcustomers.Name", $search);
        }
        $data["recentBirthdays"] = $model->findAll();

        $model->select("*, YEAR(CURRENT_TIMESTAMP()) - YEAR(dob) as currentAge");
        $model->where("DAYOFMONTH(dob) = DAYOFMONTH(CURRENT_TIMESTAMP()) AND MONTH(dob) = MONTH(CURRENT_TIMESTAMP())");

        $search = $this->request->getGet("search");
        if ($search){
            $model->like("tblcustomers.Name", $search);
        }
        $data["currentBirthdays"] = $model->findAll();

        $data["customerCount"] = $model->countAllResults();

        $tablePrefix = (new Content())->db->getPrefix();

        $model->join($tablePrefix . "content AS content", sprintf("(tblcustomers.ID + %s) = content.user AND content.type = '%s'", USER_ID_OFFSET, META_KEY_ENABLED), "LEFT");
        $model->where("content.content IN ('0','false')");
        $data["unsubscribedCount"] = $model->countAllResults();

        $model = new Content();
        $model->joinMeta(["error"]);
        $model->where("type", TYPE_HISTORY);
        $model->where("error.value IS NULL OR error.value = ''");
        $data["sentMessages"] = $model->countAllResults();

        $model->joinMeta(["error"]);
        $model->where("type", TYPE_HISTORY);
        $model->where("error.value IS NULL OR error.value = ''");
        $data["sentMessagesWeek"] = $model->countAllResults();

        $model->joinMeta(["error"]);
        $model->where("type", TYPE_HISTORY);
        $model->where("error.value IS NOT NULL OR error.value != ''");
        $data["failedMessages"] = $model->countAllResults();

        return view('dashboard', $data);
    }

    public function customers(): string
    {

        helper(["customer", "media", 'html', "option", "form"]);

        $tablePrefix = (new Content())->db->getPrefix();

        $model = new CustomerModel();
        $model->orderBy("TRIM(name)");
        $model->join($tablePrefix . "content AS enabled_content", sprintf("(tblcustomers.ID + %s) = enabled_content.user AND enabled_content.type = '%s'", USER_ID_OFFSET, META_KEY_ENABLED), "LEFT");
        $model->join($tablePrefix . "content AS method_content", sprintf("(tblcustomers.ID + %s) = method_content.user AND method_content.type = '%s'", USER_ID_OFFSET, META_KEY_METHOD), "LEFT");
        $model->select("tblcustomers.*, IFNULL(enabled_content.content, 'true') as subscribed, method_content.content AS method");

        $search = $this->request->getGet("search");
        if ($search){
            $model->like("tblcustomers.Name", $search);
        }

        $limit = get_option("page-limit")->value;

        $customers = $model->paginate($limit);
        $pager = $model->pager;

        $message = get_option("message")->value;

        return view('customers', compact("customers", "pager", "message"));
    }

    public function users()
    {

        helper(["media", 'html', "option", "form", "user"]);

        if (isset($_POST["submit"])) {

            $userId = $this->request->getPost("user-id");

            $rules = [
                'user-id' => 'required|numeric',
                'user-name' => 'required|min_length[3]',
                'user-login' => 'required|is_unique[users.login,id,' . $userId . ']|max_length[50]|min_length[3]',
                'user-email' => 'required|is_unique[users.email,id,' . $userId . ']|max_length[50]|valid_email',
                'user-password' => 'required|min_length[6]',
                'user-password-confirm' => 'required|matches[user-password]',
            ];

            if ($this->validate($rules)) {

                $userName = $this->request->getPost("user-name");
                $userLogin = $this->request->getPost("user-login");
                $userEmail = $this->request->getPost("user-email");
                $userPassword = $this->request->getPost("user-password");

                if ($userId !== DEFAULT_ID) {
                    update_user($userId, [
                        "login" => $userLogin,
                        "name" => $userName,
                        "email" => $userEmail,
                        "password" => $userPassword
                    ]);
                } else {
                    insert_user($userEmail, $userPassword, [
                        "login" => $userLogin,
                        "name" => $userName
                    ]);
                }

                return redirect()->to("users");
            }
        }

        $model = new Users();

        $search = $this->request->getGet("search");
        if ($search){
            $model->like("name", $search);
            $model->orLike("email", $search);
            $model->orLike("login", $search);
        }

        $limit = get_option("page-limit")->value;

        $users = $model->paginate($limit);
        $pager = $model->pager;

        $validation = $this->validator;

        return view('users', compact("users", "pager", "validation"));
    }

    public function history(): string
    {

        helper(["customer", "media", 'html', "option", "text", "form"]);

        $tablePrefix = (new Content())->db->getPrefix();

        $model = new Content();
        $model->orderBy("content.created_at", "DESC");
        $model->where("content.type", TYPE_HISTORY);
        $model->join( "content_meta AS meta_error", sprintf("content.id = meta_error.content AND meta_error.key = '%s'", "error"), "LEFT");
        $model->join( "content_meta AS meta_number", sprintf("content.id = meta_number.content AND meta_number.key = '%s'", META_KEY_NUMBER), "LEFT");
        $model->select("content.content AS message_sent, content.created_at AS sent_at");
        $model->join("tblcustomers", sprintf("(tblcustomers.ID + %s) = %scontent.user", USER_ID_OFFSET, $tablePrefix), "LEFT", false);
        $model->select("meta_error.value AS send_status, meta_number.value AS send_number, tblcustomers.ID AS id, tblcustomers.Name AS name, tblcustomers.MobileNumber as mobileNumber", false);

        $search = $this->request->getGet("search");
        if ($search){
            $model->like("content.content", $search);
        }

        $limit = get_option("page-limit")->value;

        $history = $model->paginate($limit);
        $pager = $model->pager;

        return view('history', compact("history", "pager"));
    }

    public function settings()
    {
        helper(['html', "option", "form", "customer"]);

        if (isset($_POST["system"])) {

            $rules = [
                'enabled' => 'if_exist|in_list[true,false]',
                'page-limit' => 'required|numeric',
                'message' => 'required',
                'test-number' => 'required',
            ];

            if ($this->validate($rules)) {

                update_option("enabled", $this->request->getPost('enabled'));
                update_option("page-limit", $this->request->getPost('page-limit'));
                update_option("message", $this->request->getPost('message'));
                update_option("test-number", normaliseNumber($this->request->getPost('test-number')));

                return redirect()->to('settings');
            }
        }

        if (isset($_POST["twilio"])) {

            $rules = [
                'twilio-sid' => 'required|alpha_numeric|regex_match[/^AC.*$/]',
                'twilio-token' => 'required|alpha_numeric',
                'twilio-from' => 'required',
            ];

            if ($this->validate($rules)) {

                $actions = [
                    [
                        "pattern" => "/(TWILIO_FROM_NUMBER\s*=\s*)[\"']?.*[\"']?$/m",
                        "data" => "TWILIO_FROM_NUMBER = '" . preg_replace("/[^+0-9]/m", "", $this->request->getPost('twilio-from')) . "'"
                    ],
                    [
                        "pattern" => "/(TWILIO_ACCOUNT_SID\s*=\s*)[\"']?.*[\"']?$/m",
                        "data" => "TWILIO_ACCOUNT_SID = '" . preg_replace("/[^a-zA-Z0-9]/m", "", $this->request->getPost('twilio-sid')) . "'"
                    ],
                    [
                        "pattern" => "/(TWILIO_AUTH_TOKEN\s*=\s*)[\"']?.*[\"']?$/m",
                        "data" => "TWILIO_AUTH_TOKEN = '" . preg_replace("/[^a-zA-Z0-9]/m", "", $this->request->getPost('twilio-token')) . "'"
                    ]
                ];

                $environment = file_get_contents(ROOTPATH . ".env");

                foreach ($actions as $action) {
                    if (preg_match($action["pattern"], $environment) !== 0) {
                        $environment = preg_replace($action["pattern"], $action["data"], $environment);
                    } else {
                        $environment .= PHP_EOL . $action["data"];
                    }
                }

                file_put_contents(ROOTPATH . ".env", $environment);

                return redirect()->to('settings');
            }
        }

        if (isset($_POST["test"])) {

            $rules = [
                'test-number' => 'required',
                'test-method' => 'required'
            ];

            if ($this->validate($rules)) {

                try {
                    $messenger = new TwilioMessenger();
                    $messenger->setTo($this->request->getPost('test-number'));
                    $messenger->setMethod($this->request->getPost('test-method'));
                    $messenger->setMessage('This is a test message from ' . get_option("site-name")->value);
                    $messenger->send();

                    return redirect()->to('settings');
                } catch (Exception $e) {
                    $this->validator->setError('twilio', $e->getMessage());
                }
            }
        }

        $data = [
            "enabled" => get_option("enabled"),
            "message" => get_option("message"),
            "pageLimit" => get_option("page-limit"),
            "siteName" => get_option("site-name"),
            "testNumber" => get_option("test-number"),
        ];

        $data = array_map(function ($option) {
            if (is_object_option($option)) {
                return $option->value;
            }
            return "";
        }, $data);

        $data["twilio_from"] = getenv("TWILIO_FROM_NUMBER");
        $data["validation"] = $this->validator;

        return view("settings", $data);
    }

}

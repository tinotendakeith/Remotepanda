<?php

namespace App\Controllers;

use App\Libraries\TwilioMessenger;
use App\Models\Content;
use App\Models\Customer as CustomerModel;
use App\Models\Users;
use CodeIgniter\I18n\Time;
use Exception;

class Home extends BaseController
{

    /**
     * @throws Exception
     */
    public function dashboard(): string
    {

        helper(["customer", "media", 'html']);

        $data = [];

        $model = new CustomerModel();
        $model->select("*, YEAR(CURRENT_TIMESTAMP()) - YEAR(dob) AS currentAge, DATE_ADD(dob, INTERVAL TIMESTAMPDIFF(YEAR, DATE_ADD(dob, INTERVAL 1 DAY), CURRENT_DATE()) + 1 YEAR) AS nextBirthday");
        $model->orderBy("nextBirthday");
        $model->having("nextBirthday BETWEEN DATE_ADD(CURRENT_DATE(), INTERVAL 1 DAY) AND DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY)");

        $search = $this->request->getGet("search");
        if ($search) {
            $model->like("tblcustomers.Name", $search);
        }
        $data["upcomingBirthdays"] = $model->findAll();

        $model->select("*, YEAR(CURRENT_TIMESTAMP()) - YEAR(dob) AS currentAge, DATE_ADD(dob, INTERVAL TIMESTAMPDIFF(YEAR, DATE_ADD(dob, INTERVAL 1 DAY), CURRENT_DATE()) + 0 YEAR) AS nextBirthday");
        $model->orderBy("nextBirthday", "DESC");
        $model->having("nextBirthday BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY) AND DATE_SUB(CURRENT_DATE(), INTERVAL 1 DAY)");

        $search = $this->request->getGet("search");
        if ($search) {
            $model->like("tblcustomers.Name", $search);
        }
        $data["recentBirthdays"] = $model->findAll();

        $model->select("*, YEAR(CURRENT_TIMESTAMP()) - YEAR(dob) as currentAge");

        $model->groupStart();

        $model->groupStart();
        $model->where("DAYOFMONTH(dob) = DAYOFMONTH(CURRENT_TIMESTAMP()) AND MONTH(dob) = MONTH(CURRENT_TIMESTAMP())");
        $model->groupEnd();

        $date = Time::now();
        if (intval($date->getYear()) % 4 !== 0 && intval($date->format("n")) === 2 && intval($date->format("j")) === 28) {
            // Leap year birthdays

            $model->orGroupStart();
            $model->where("DAYOFMONTH(tblcustomers.dob) = 29 AND MONTH(tblcustomers.dob) = 2");
            $model->groupEnd();
        }
        $model->groupEnd();

        $search = $this->request->getGet("search");
        if ($search) {
            $model->like("tblcustomers.Name", $search);
        }
        $data["currentBirthdays"] = $model->findAll();

        $data["customerCount"] = $model->countAllResults();

        $model->joinContent([META_KEY_ENABLED => "subscribed"]);
        $model->where("subscribed.content", "false");
        $data["unsubscribedCount"] = $model->countAllResults();

        $model = new Content();
        $model->joinMeta(["error"]);
        $model->where("type", TYPE_HISTORY);
        $model->where("IFNULL(error.value, '') = ''");
        $data["sentMessages"] = $model->countAllResults();

        $model->joinMeta(["error"]);
        $model->where("type", TYPE_HISTORY);
        $model->where("IFNULL(error.value, '') = ''");
        $data["sentMessagesWeek"] = $model->countAllResults();

        $model->joinMeta(["error"]);
        $model->where("type", TYPE_HISTORY);
        $model->where("IFNULL(error.value, '') != ''");
        $data["failedMessages"] = $model->countAllResults();

        return view('dashboard', $data);
    }

    public function customers(): string
    {

        helper(["customer", "media", 'html', "option", "form"]);

        $subscribedQuery = "IFNULL(enabled_content.content, 'true')";
        $methodQuery = "IFNULL(method_content.content, 'message')";

        $model = new CustomerModel();
        $model->orderBy("TRIM(name)");

        $model->select(sprintf("tblcustomers.*, %s AS subscribed, %s AS method", $subscribedQuery, $methodQuery));
        $model->joinContent([META_KEY_ENABLED => 'enabled_content', META_KEY_METHOD => 'method_content']);

        $search = $this->request->getGet("search");
        if ($search) {
            $model->like("tblcustomers.Name", $search);
        }

        $filterSubscription = $this->request->getGet("subscription");
        switch ($filterSubscription) {
            case "yes":
                $model->where($subscribedQuery, "true");
                break;
            case "no":
                $model->where($subscribedQuery, "false");
                break;
            default :
                // Do nothing
                break;
        }

        $filterMethod = $this->request->getGet("method");
        switch ($filterMethod) {
            case "whatsapp":
            case "message":
                $model->where($methodQuery, $filterMethod);
                break;
            default :
                // Do nothing
                break;
        }

        $limit = get_option("page-limit")->value;

        $customers = $model->paginate($limit);
        $pager = $model->pager;

        $message = get_option("message")->value;

        return view('customers', compact("customers", "pager", "message"));
    }

    /**
     * @throws Exception
     */
    public function users()
    {

        helper(["media", 'html', "option", "form", "user"]);

        if (isset($_GET["submit"])) {

            $userId = $this->request->getGet("user-id");

            $rules = [
                'user-id' => 'required|numeric',
                'user-name' => 'required|min_length[3]',
                'user-login' => 'required|is_unique[users.login,id,' . $userId . ']|max_length[50]|min_length[3]',
                'user-email' => 'required|is_unique[users.email,id,' . $userId . ']|max_length[50]|valid_email',
                'user-password' => 'required|min_length[6]',
                'user-password-confirm' => 'required|matches[user-password]',
            ];

            if ($this->validate($rules)) {

                $userName = $this->request->getGet("user-name");
                $userLogin = $this->request->getGet("user-login");
                $userEmail = $this->request->getGet("user-email");
                $userPassword = $this->request->getGet("user-password");

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

                return redirect()->to("users")->with("message", "User data saved successfully!");
            }
        }

        $model = new Users();

        $search = $this->request->getGet("search");
        if ($search) {
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

        $model->joinMeta(["error" => "meta_error", "content_meta" => META_KEY_NUMBER]);
        $model->join("tblcustomers", sprintf("(tblcustomers.ID + %s) = %scontent.user", USER_ID_OFFSET, $tablePrefix), "LEFT", false);

        $model->select("content.content AS message_sent, content.created_at AS sent_at");
        $model->select("meta_error.value AS send_status, number.value AS send_number, tblcustomers.ID AS id, tblcustomers.Name AS name, tblcustomers.MobileNumber as mobileNumber", false);

        $search = $this->request->getGet("search");
        if ($search) {
            $model->like("content.content", $search);
        }

        $limit = get_option("page-limit")->value;

        $history = $model->paginate($limit);
        $pager = $model->pager;

        return view('history', compact("history", "pager"));
    }

    /**
     * @throws Exception
     */
    public function settings()
    {
        helper(['html', "option", "form", "customer"]);

        if (isset($_GET["system"])) {

            $rules = [
                'enabled' => 'if_exist|in_list[true,false]',
                'page-limit' => 'required|numeric',
                'message' => 'required',
                'test-number' => 'required',
            ];

            if ($this->validate($rules)) {

                update_option("enabled", $this->request->getGet('enabled'));
                update_option("page-limit", $this->request->getGet('page-limit'));
                update_option("message", $this->request->getGet('message'));
                update_option("test-number", normaliseNumber($this->request->getGet('test-number')));

                return redirect()->to('settings')->with("message", "Settings saved successfully");
            }
        }

        if (isset($_GET["twilio"])) {

            $rules = [
                'twilio-sid' => 'required|alpha_numeric|regex_match[/^AC.*$/]',
                'twilio-token' => 'required|alpha_numeric',
                'twilio-from' => 'required',
            ];

            if ($this->validate($rules)) {

                $actions = [
                    [
                        "pattern" => "/(TWILIO_FROM_NUMBER\s*=\s*)[\"']?.*[\"']?$/m",
                        "data" => "TWILIO_FROM_NUMBER = '" . preg_replace("/[^+0-9]/m", "", $this->request->getGet('twilio-from')) . "'"
                    ],
                    [
                        "pattern" => "/(TWILIO_ACCOUNT_SID\s*=\s*)[\"']?.*[\"']?$/m",
                        "data" => "TWILIO_ACCOUNT_SID = '" . preg_replace("/[^a-zA-Z0-9]/m", "", $this->request->getGet('twilio-sid')) . "'"
                    ],
                    [
                        "pattern" => "/(TWILIO_AUTH_TOKEN\s*=\s*)[\"']?.*[\"']?$/m",
                        "data" => "TWILIO_AUTH_TOKEN = '" . preg_replace("/[^a-zA-Z0-9]/m", "", $this->request->getGet('twilio-token')) . "'"
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

                return redirect()->to('settings')->with("message", "Twilio settings saved successfully");
            }
        }

        if (isset($_GET["test"])) {

            $rules = [
                'test-number' => 'required',
                'test-method' => 'required'
            ];

            if ($this->validate($rules)) {

                try {
                    $messenger = new TwilioMessenger();
                    $messenger->setTo($this->request->getGet('test-number'));
                    $messenger->setMethod($this->request->getGet('test-method'));
                    $messenger->setMessage('This is a test message from ' . get_option("site-name")->value);
                    $messenger->send();

                    return redirect()->to('settings')->with("message", "Test message sent successfully");
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

    /**
     * @throws Exception
     */
    public function broadcast()
    {
        helper(["html", "form"]);

        $model = new CustomerModel();
        $customerCount = $model->countAllResults();

        $validation = $this->validator;

        return view("broadcast", compact("customerCount", "validation"));
    }

}

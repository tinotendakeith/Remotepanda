<?php

namespace App\Controllers;

use App\Libraries\TwilioMessenger;
use App\Models\Content;
use App\Models\Customer as CustomerModel;
use CodeIgniter\API\ResponseTrait;
use Config\Database;
use Config\Services;
use Exception;

class Cron extends BaseController
{

    use ResponseTrait;

    public function install()
    {
        $request = Services::request();

        if ($request->getPostGet("secure") == "sure") {

            $migrate = Services::migrations();

            try {
                //$migrate->regress();
                $migrate->latest();

                $seeder = Database::seeder();

                $seeds = array(
                    "Users",
                    "Options"
                );

                foreach ($seeds as $seed) {
                    $seeder->call($seed);
                }

            } catch (Exception $e) {
                // Do something with the error here...

                echo $e->getMessage();
            }
        }
    }

    public function notify(int $customer = 0)
    {

        helper(["customer", "option", "inflector", "user", "content"]);

        $option = get_option("test-number");

        if (get_option("enabled")->value || ($option !== false && $option->value !== "")) {

            $model = new CustomerModel();
            if ($customer === 0) {

                $tablePrefix = (new Content())->db->getPrefix();

                $model->join($tablePrefix . "content AS content", sprintf("(tblcustomers.ID + %s) = content.user AND content.type = '%s'", USER_ID_OFFSET, META_KEY_ENABLED), "LEFT");

                $model->groupStart();
                $model->where("DAYOFMONTH(tblcustomers.dob) = DAYOFMONTH(CURRENT_TIMESTAMP())");
                $model->where("MONTH(tblcustomers.dob) = MONTH(CURRENT_TIMESTAMP())");
                $model->groupEnd();

                $model->groupStart();
                $model->where("content.content IN ('1', 'true')");
                $model->orWhere("content.content IS NULL");
                $model->groupEnd();

            } else {
                $model->where("ID", $customer);
            }

            $model->select("tblcustomers.*, YEAR(CURRENT_TIMESTAMP()) - YEAR(tblcustomers.dob) as currentAge");
            $customers = $model->findAll();

            foreach ($customers as $customer) {

                $userId = USER_ID_OFFSET + $customer->id;

                $meta = get_user_meta(META_KEY_METHOD, $userId);
                $method = $meta !== false ? $meta->value : METHOD_MESSAGE;

                $message = strtr(get_option("message")->value, [
                    "{{name}}" => ucwords($customer->name),
                    "{{ordinal}}" => ordinalize($customer->currentAge),
                    "{{age}}" => $customer->currentAge,
                    "{{company}}" => get_option("site-name")->value
                ]);

                if (!get_option("enabled")->value && ($option !== false && $option->value !== "")) {
                    $number = $option->value;
                } else {
                    $number = normaliseNumber($customer->mobileNumber);
                }

                $error = "";
                if (!empty($number)) {
                    try {
                        $messenger = new TwilioMessenger();
                        $messenger->setTo($number);
                        $messenger->setMethod($method);
                        $messenger->setMessage($message);
                        $messenger->send();
                    } catch (Exception $e) {
                        $error = $e->getMessage();
                    }
                } else {
                    $error = "Invalid target number.";
                }

                $history = insert_content(TYPE_HISTORY, $message, $userId);
                insert_content_meta($history, META_KEY_NUMBER, $number);
                insert_content_meta($history, META_KEY_METHOD, $method);
                insert_content_meta($history, "error", $error);
            }
        }
        if ($customer !== 0) {
            return $this->respondCreated();
        }
    }
}

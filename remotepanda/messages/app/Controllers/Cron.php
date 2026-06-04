<?php

namespace App\Controllers;

use App\Libraries\TwilioMessenger;
use App\Models\Content;
use App\Models\Customer as CustomerModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\I18n\Time;
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

    /**
     * @throws Exception
     */
    public function notify()
    {

        helper(["customer", "option"]);

        $model = new CustomerModel();
        $model->joinContent([META_KEY_ENABLED => "content"]);

        $model->groupStart();

        $model->groupStart();
        $model->where("DAYOFMONTH(tblcustomers.dob) = DAYOFMONTH(CURRENT_TIMESTAMP()) AND MONTH(tblcustomers.dob) = MONTH(CURRENT_TIMESTAMP())");
        $model->groupEnd();

        $date = Time::now();
        if (intval($date->getYear()) % 4 !== 0 && intval($date->format("n")) === 2 && intval($date->format("j")) === 28) {
            // Leap year birthdays

            $model->orGroupStart();
            $model->where("DAYOFMONTH(tblcustomers.dob) = 29 AND MONTH(tblcustomers.dob) = 2");
            $model->groupEnd();
        }

        $model->groupEnd();

        $model->groupStart();
        $model->where("IFNULL(content.content, 'true') = 'true'");
        $model->groupEnd();

        $model->joinContent([META_KEY_ENABLED => "enabled_content"]);
        $model->joinContent([META_KEY_METHOD => "method_content"]);

        $model->select("tblcustomers.*, YEAR(CURRENT_TIMESTAMP()) - YEAR(tblcustomers.dob) as currentAge, IFNULL(enabled_content.content, 'true') AS subscribed, IFNULL(method_content.content, 'message') AS method");
        $customers = $model->findAll();

        foreach ($customers as $customer) {
            if ($customer->{"subscribed"} === "true") {
                send_customer_message($customer, get_option("message")->value);
            }
        }
    }
}

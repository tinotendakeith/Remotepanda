<?php

namespace App\Controllers;

use App\Models\Content;
use App\Models\Users;
use CodeIgniter\API\ResponseTrait;
use Exception;
use App\Models\Customer as CustomerModel;

class Rest extends BaseController
{

    use ResponseTrait;

    public function history()
    {

        $tablePrefix = (new Content())->db->getPrefix();

        $model = new Content();
        $model->where("type", TYPE_HISTORY);
        $model->join($tablePrefix . "content_meta AS content_meta", sprintf("content.id = content_meta.content AND content_meta.key = '%s'", "error"), "LEFT");
        $model->select("content.created_at, content_meta.value AS status");
        $model->where("content.created_at > DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 WEEK)");

        $history = array_map(function ($item) {
            return [
                "date" => $item->created_at->getTimeStamp(),
                "status" => empty($item->status)
            ];
        }, $model->findAll());

        return $this->respond(["data" => $history]);
    }

    public function customers()
    {

        $model = new CustomerModel();
        $model->select("tblcustomers.ID, tblcustomers.Name");

        $customers = array_map(function ($customer) {
            return [
                "id" => $customer->id,
                "name" => $customer->name
            ];
        }, $model->findAll());

        return $this->respond(["data" => $customers]);
    }

    /**
     * @throws Exception
     */
    public function method(int $customerId)
    {

        helper(["content", "customer"]);

        $model = new CustomerModel();

        $customer = $model->find($customerId);
        $customer->with([META_KEY_METHOD => "method"], "edit");

        switch ($customer->method->content) {
            case METHOD_MESSAGE:
                update_content(META_KEY_METHOD, METHOD_WHATSAPP, customer_id_offset($customerId), $customer->method->id ?: DEFAULT_ID);
                break;
            case METHOD_WHATSAPP:
                update_content(META_KEY_METHOD, METHOD_MESSAGE, customer_id_offset($customerId), $customer->method->id ?: DEFAULT_ID);
                break;
        }

        $customer = $model->find($customerId);
        $customer->with([META_KEY_METHOD => "method"]);

        return $this->respond([
            "method" => $customer->method,
            "description" => ucfirst($customer->method)
        ]);
    }

    /**
     * @throws Exception
     */
    public function subscribe(int $customerId)
    {
        helper(["content", "customer"]);

        $model = new CustomerModel();

        $customer = $model->find($customerId);
        $customer->with([META_KEY_ENABLED => "subscribed"], "edit");

        if ($customer->subscribed->content === "true") {
            update_content(META_KEY_ENABLED, "false", customer_id_offset($customerId), $customer->subscribed->id ?: DEFAULT_ID);
        } else {
            update_content(META_KEY_ENABLED, "true", customer_id_offset($customerId), $customer->subscribed->id ?: DEFAULT_ID);
        }

        $customer = $model->find($customerId);
        $customer->with([META_KEY_ENABLED => "subscribed"]);

        return $this->respond([
            "subscribed" => $customer->subscribed === "true",
            "description" => $customer->subscribed === "true" ? "Subscribed" : "UnSubscribed"
        ]);
    }

    public function deleteUser(int $user)
    {
        $model = new Users();
        $model->delete($user);

        return $this->respondDeleted();
    }

    /**
     * @throws Exception
     */
    public function message(int $customerId)
    {
        helper(["customer"]);

        $model = new CustomerModel();
        $model->joinContent([META_KEY_ENABLED => "enabled_content"]);
        $model->joinContent([META_KEY_METHOD => "method_content"]);
        $model->select("tblcustomers.*, YEAR(CURRENT_TIMESTAMP()) - YEAR(tblcustomers.dob) as currentAge, IFNULL(enabled_content.content, 'true') AS subscribed, IFNULL(method_content.content, 'message') AS method");

        $customer = $model->find($customerId);

        if ($customer !== null && $customer->{"subscribed"} === "true") {
            send_customer_message($customer, $this->request->getGetPost("message"));
        }

        return $this->respondCreated();
    }
}

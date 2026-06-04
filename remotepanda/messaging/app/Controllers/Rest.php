<?php

namespace App\Controllers;

use App\Models\Content;
use App\Models\Users;
use CodeIgniter\API\ResponseTrait;

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
        $model->where("content.created_at > SUBDATE(CURRENT_TIMESTAMP(), INTERVAL 2 YEAR)");

        $history = array_map(function ($item) {
            return [
                "date" => $item->created_at->getTimeStamp(),
                "status" => empty($item->status)
            ];
        }, $model->findAll());

        return $this->respond(["data" => $history]);
    }

    public function method(int $customer)
    {

        helper(["content"]);

        $userId = $customer + USER_ID_OFFSET;

        $model = new Content();
        $model->where("type", META_KEY_METHOD);
        $model->where("user", $userId);

        $content = $model->first();
        $method = $content !== null ? $content->content : METHOD_MESSAGE;

        switch ($method) {
            case METHOD_MESSAGE:
                update_content(META_KEY_METHOD, METHOD_WHATSAPP, $userId, $content === null ? DEFAULT_ID : $content->id);
                break;
            case METHOD_WHATSAPP:
                update_content(META_KEY_METHOD, METHOD_MESSAGE, $userId, $content === null ? DEFAULT_ID : $content->id);
                break;
        }

        $model->where("type", META_KEY_METHOD);
        $model->where("user", $userId);
        $content = $model->first();

        $method = ucfirst($content->content);

        return $this->respond(compact("method"));
    }

    public function subscribe(int $customer)
    {
        helper(["content"]);

        $userId = $customer + USER_ID_OFFSET;

        $model = new Content();
        $model->where("type", META_KEY_ENABLED);
        $model->where("user", $userId);

        $content = $model->first();
        $subscribed = $content === null || $content->content;

        if ($subscribed) {
            update_content(META_KEY_ENABLED, 0, $userId, $content === null ? DEFAULT_ID : $content->id);
        } else {
            update_content(META_KEY_ENABLED, true, $userId, $content === null ? DEFAULT_ID : $content->id);
        }

        $model->where("type", META_KEY_ENABLED);
        $model->where("user", $userId);
        $content = $model->first();

        $subscribed = $content->content ? "Subscribed" : "UnSubscribed";

        return $this->respond(compact("subscribed"));
    }

    public function deleteUser(int $user){
        $model = new Users();
        $model->delete($user);

        return $this->respondDeleted();
    }
}

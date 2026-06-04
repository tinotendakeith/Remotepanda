<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use ReflectionException;
use App\Models\Users as UsersModel;

class Users extends Seeder
{
    /**
     * Insert initial user
     *
     * @throws ReflectionException
     */
    public function run()
    {
        $model = new UsersModel();
        if ($model->countAll() === 0) {

            helper('user');

            insert_user("admin@hillpaulhealthclinic.co.zw", "password", [
                "login" => "admin",
                "name" => "Administrator"
            ]);
        }
    }
}

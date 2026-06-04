<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Customer extends Entity
{
    protected $datamap = [
        "name" => "Name",
        "id" => "ID",
        "mobileNumber" => "MobileNumber",
        "created_at" => "CreationDate",
        "updated_at" => "UpdationDate",
        "dateOfBirth" => "dob"
    ];
    protected $dates = ['CreationDate', 'UpdationDate', "dob"];
    protected $casts = [];
}

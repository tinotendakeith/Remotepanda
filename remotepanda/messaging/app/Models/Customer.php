<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * @method having(string $string)
 */
class Customer extends Model
{
    protected $DBGroup = 'default';
    protected $table = 'tblcustomers';
    protected $primaryKey = 'ID';
    protected $returnType = 'App\Entities\Customer';
    protected $dateFormat = 'datetime';

    // Dates
    protected $createdField = 'CreationDate';
    protected $updatedField = 'UpdationDate';

}

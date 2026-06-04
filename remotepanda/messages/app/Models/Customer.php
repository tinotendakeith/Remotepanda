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

    /**
     * Join with content table
     *
     * @param array|string $fields Fields to join.
     * @param string $type Type of join.
     *
     * @return self
     * @since   1.0.0
     * @version 1.0.0
     *
     * @author  Richard Muvirimi <rich4rdmuvirimi@gmail.com>
     */
    public function joinContent($fields, string $type = 'LEFT'): self
    {
        helper('collection');

        $tablePrefix = (new Content())->db->getPrefix();

        foreach (array_maybe($fields) as $field => $name) {

            if (is_numeric($field)) {
                $field = $name;
            }

            $table = $tablePrefix . 'content AS ' . $name;
            $condition = sprintf("(tblcustomers.ID + %s) = %s.user AND %s.type = '%s'", USER_ID_OFFSET, $name, $name, $field);

            $this->join($table, $condition, $type);
        }

        return $this;
    }

}

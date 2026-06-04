<?php

/**
 * Base Model
 */

namespace App\Models;

use CodeIgniter\Model;

/**
 * Base Model Class
 *
 * @author  Richard Muvirimi <rich4rdmuvirimi@gmail.com>
 * @since   1.0.0
 * @version 1.0.0
 */
abstract class BaseModel extends Model
{
    protected $DBGroup = 'notifier';
    protected $useTimestamps = true;

    /**
     * Join with meta table
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
    public function joinMeta($fields, string $type = 'LEFT'): self
    {
        helper('collection');

        foreach (array_maybe($fields) as $field => $name) {

            if (is_numeric($field)) {
                $field = $name;
            }

            $this->join($this->table . '_meta AS ' . $name, $this->table . '.id = ' . $name . '.' . $this->table . ' AND ' . $name . ".key = '" . $field . "'", $type);
        }

        return $this;
    }

}

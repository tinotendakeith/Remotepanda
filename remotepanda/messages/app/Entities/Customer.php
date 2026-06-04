<?php

namespace App\Entities;

use App\Models\Content;
use CodeIgniter\Entity\Entity;

/**
 * Customer Entity
 *
 * @property int $id
 * @property string $name
 * @property int $currentAge
 * @property string $mobileNumber
 * @property string $title
 */
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

    /**
     * Load requested content data onto customer
     *
     * @param array $fields Fields to load.
     * @param string $context Contextual use of value, view returns resolved value, edit the meta object.
     *
     * @return self
     * @since   1.0.0
     * @version 1.0.0
     *
     * @author  Richard Muvirimi <rich4rdmuvirimi@gmail.com>
     */
    public function with(array $fields, string $context = 'view'): self
    {
        helper(["content", "customer"]);

        foreach ($fields as $field => $name) {
            if (is_numeric($field)) {
                $field = $name;
            }

            $model = new Content();
            $model->where("type", $field);
            $model->where("user", customer_id_offset($this->id));
            $this->{$name} = $model->first() ?? new Content();

            switch ($context) {
                case 'view':
                    if (is_object_content($this->{$name})) {
                        $this->{$name} = $this->{$name}->content;
                    } else {
                        $this->{$name} = '';
                    }
                    break;
                default:
                    // Leave as is
                    break;
            }
        }

        return $this;
    }
}

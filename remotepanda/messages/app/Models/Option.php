<?php

/**
 * Option Model
 */

namespace App\Models;

/**
 * Option model class
 *
 * @author  Richard Muvirimi <rich4rdmuvirimi@gmail.com>
 * @since   1.0.0
 * @version 1.0.0
 */
class Option extends BaseModel
{
    protected $table = 'option';
    protected $allowedFields = [
        'key',
        'value',
    ];
    protected $returnType = 'App\Entities\Option';

}

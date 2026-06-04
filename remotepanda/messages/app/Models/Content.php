<?php

/**
 * Content Model
 */

namespace App\Models;

/**
 * Content model class
 *
 * @author  Richard Muvirimi <rich4rdmuvirimi@gmail.com>
 * @since   1.0.0
 * @version 1.0.0
 * @method  groupStart()
 * @method  groupEnd()
 * @method  orGroupStart()
 */
class Content extends BaseModel
{
    protected $table = 'content';
    protected $allowedFields = [
        'user',
        'type',
        'content',
        'parent',
    ];
    protected $returnType = 'App\Entities\Content';

}

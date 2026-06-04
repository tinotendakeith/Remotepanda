<?php

namespace App\Filters;

use App\Models\Content;
use App\Models\Customer;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Maintenance filter
 *
 * @author  Richard Muvirimi <rich4rdmuvirimi@gmail.com>
 * @since   1.0.0
 * @version 1.0.0
 */
class Maintenance implements FilterInterface
{
    /**
     * Do whatever processing this filter needs to do.
     * By default it should not return anything during
     * normal execution. However, when an abnormal state
     * is found, it should return an instance of
     * CodeIgniter\HTTP\Response. If it does, script
     * execution will end and that Response will be
     * sent back to the client, allowing for error pages,
     * redirects, etc.
     *
     * @param RequestInterface $request Request.
     * @param array|null $arguments Arguments.
     *
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {

        // Clear orphaned history
        $model = new Customer();
        $model->select(sprintf("%s + ID", USER_ID_OFFSET), false);
        $sql = $model->builder()->getCompiledSelect();

        $model = new Content();
        $model->where(sprintf("user NOT IN (%s)", $sql));
        $model->orWhere("created_at < DATE_SUB(NOW(), INTERVAL 2 YEAR)");
        $model->delete();

    }

    /**
     * Allows After filters to inspect and modify the response
     * object as needed. This method does not allow any way
     * to stop execution of other after filters, short of
     * throwing an Exception or Error.
     *
     * @param RequestInterface $request Request.
     * @param ResponseInterface $response Response.
     * @param array|null $arguments Arguments.
     *
     * @return mixed
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        //
    }
}

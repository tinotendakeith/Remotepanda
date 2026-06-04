<?php

/**
 * User helper
 */

use App\Entities\UserMeta;
use App\Entities\User;
use App\Models\UserMeta as ModelsUserMeta;
use App\Models\Users;
use CodeIgniter\Database\BaseResult;
use CodeIgniter\Database\Exceptions\DataException;
use Config\Services;

/**
 * Get the id of the currently logged in user
 *
 * @return integer|null
 * @version 1.0.0
 * @since   1.0.0
 *
 * @author  Richard Muvirimi <rich4rdmuvirimi@gmail.com>
 */
function current_user_id(): ?int
{
    $session = session();
    $user = $session->getTempdata('user_id');

    $model = new Users();
    $user = $model->find($user);

    return $user->id ?? null;
}

/**
 * Insert user meta
 *
 * @param integer $user User Id.
 * @param string $key Key.
 * @param string|integer $value Value.
 *
 * @return  BaseResult|false|integer|object|string
 * @throws  ReflectionException
 * @version 1.0.0
 *
 * @author Richard Muvirimi <rich4rdmuvirimi@gmail.com>
 * @since  1.0.0
 */
function insert_user_meta(int $user, string $key, $value)
{
    if (empty($user) || empty($key)) {
        throw new DataException('Key and user field is required');
    }

    $meta = new UserMeta(compact('user', 'key', 'value'));

    $model = new ModelsUserMeta();
    return $model->insert($meta);
}

/**
 * Update user meta
 *
 * @param integer $user User Id.
 * @param string $key Key.
 * @param string|integer $value Value to set.
 * @param string|integer $oldValue Value to update.
 * @param integer $id Meta Id.
 *
 * @return  integer
 * @throws  ReflectionException
 * @author  Richard Muvirimi <rich4rdmuvirimi@gmail.com>
 * @since   1.0.0
 * @version 1.0.0
 */
function update_user_meta(int $user, string $key, $value = '', $oldValue = '', int $id = DEFAULT_ID): int
{
    if (empty($user) || empty($key)) {
        throw new DataException('Key and user field is required');
    }

    $model = new ModelsUserMeta();
    $model->where(compact('user', 'key'));

    if (empty($oldValue) === false) {
        $model->where('value', $oldValue);
    }

    if ($id !== DEFAULT_ID) {
        $model->where('id', $id);
    }

    if (!empty($value)) {
        $meta = $model->first() ?? new UserMeta(compact('key', 'user', 'id'));
        $meta->fill(compact('value'));

        try {
            $model->save($meta);
        } catch (DataException $e) {
        } finally {
            if (isset($meta->id) && $id !== DEFAULT_ID) {
                return $meta->id;
            }

            return $model->getInsertID();
        }
    } else {
        $meta = $model->first();
        delete_user_meta($user, $key, $oldValue);

        return $meta->id ?? 0;
    }
}

/**
 * Delete user meta
 *
 * @param integer $user User ID.
 * @param string $key Meta Key.
 * @param string|integer $value Meta Value.
 * @param integer $metaId Meta Id.
 *
 * @return boolean
 * @since   1.0.0
 * @version 1.0.0
 *
 * @author  Richard Muvirimi <rich4rdmuvirimi@gmail.com>
 */
function delete_user_meta(int $user, string $key, $value = '', int $metaId = DEFAULT_ID): bool
{
    if (empty($user) || empty($key)) {
        throw new DataException('Key and user field is required');
    }

    $model = new ModelsUserMeta();
    $model->where(compact('user', 'key'));

    if ($metaId !== DEFAULT_ID) {
        $model->where('id', $metaId);
    }

    if (empty($value) === false) {
        $model->where(compact('value'));
    }

    return $model->delete();
}

/**
 * Get user meta
 *
 * @param string $key Meta Key.
 * @param integer $user User Id.
 * @param boolean $first Return first or all.
 * @param string|integer $value Value to match.
 *
 * @return  array|false|object
 * @since   1.0.0
 * @version 1.0.0
 *
 * @author Richard Muvirimi <rich4rdmuvirimi@gmail.com>
 */
function get_user_meta(string $key, int $user = DEFAULT_ID, bool $first = true, $value = '')
{
    if (empty($user) || empty($key)) {
        throw new DataException('Key and user field is required');
    }

    $model = new ModelsUserMeta();
    $model->where(compact('key'));

    if (empty($value) === false) {
        $model->where(compact('value'));
    }

    if ($user !== DEFAULT_ID) {
        $user = get_user($user);

        if ($user === false) {
            return false;
        }
        $model->where('user', $user->id);
    }

    if ($first === true) {
        return $model->first() ?? false;
    } else {
        return $model->findAll() ?? [];
    }
}

/**
 * Insert user
 *
 * @param string $email Email.
 * @param string $password Un-hashed Password.
 * @param array $args Other parameters.
 *
 * @return  BaseResult|false|integer|object|string
 * @throws  ReflectionException
 * @version 1.0.0
 *
 * @author Richard Muvirimi <rich4rdmuvirimi@gmail.com>
 * @since  1.0.0
 */
function insert_user(string $email, string $password, array $args = [])
{
    if (empty($email) || empty($password)) {
        throw new DataException('Email and password field is required');
    }

    $content = new User();
    $content->fill(compact('email', 'password'));
    $content->fill($args);

    $model = new Users();
    return $model->insert($content);
}

/**
 * Update user
 *
 * @param string|integer $userId User Id.
 * @param array $args Other parameters.
 *
 * @return  integer
 * @throws  ReflectionException
 * @version 1.0.0
 *
 * @author Richard Muvirimi <rich4rdmuvirimi@gmail.com>
 * @since  1.0.0
 */
function update_user($userId, array $args = []): int
{
    $user = get_user($userId) ?: new User();

    $user->fill($args);

    $model = new Users();
    try {
        $model->save($user);
    } catch (DataException $e) {
    } finally {
        if (isset($user->id)) {
            return $user->id;
        }

        return $model->getInsertID();
    }
}

/**
 * Get user object
 *
 * @param integer|string $user User Id or email.
 *
 * @return array|false|integer|object|string User Object.
 * @since   1.0.0
 * @version 1.0.0
 *
 * @author  Richard Muvirimi <rich4rdmuvirimi@gmail.com>
 */
function get_user($user)
{
    if (is_object_user($user)) {
        return $user;
    }

    $model = new Users();

    if (is_numeric($user)) {
        $model->where('id', $user);
    } else {
        $model->where('email', $user);
        $model->orWhere('login', $user);
    }

    return $model->first() ?: false;
}

/**
 * Get all users from the database
 *
 * @param integer $limit Number of users.
 *
 * @return array Of users.
 * @since   1.0.0
 * @version 1.0.0
 *
 * @author  Richard Muvirimi <rich4rdmuvirimi@gmail.com>
 */
function get_all_users(int $limit = 0): array
{
    $model = new Users();

    $model->select('*');

    return $model->findAll($limit);
}

/**
 * Delete user by email
 *
 * @param string $email Email.
 *
 * @return boolean
 * @throws Exception If email is empty
 * @version 1.0.0
 *
 * @author  Richard Muvirimi <rich4rdmuvirimi@gmail.com>
 * @since   1.0.0
 */
function delete_user(string $email): bool
{
    if (empty($email)) {
        throw new DataException('Email field is required');
    }

    $model = new Users();
    $model->where('email', $email);

    return $model->delete();
}

/**
 * Get multiple user meta
 *
 * @param array $keys Keys to retrieve.
 * @param integer $user User.
 *
 * @return array
 * @since   1.0.0
 * @version 1.0.0
 *
 * @author  Richard Muvirimi <rich4rdmuvirimi@gmail.com>
 */
function get_user_meta_multiple(array $keys, int $user = DEFAULT_ID): array
{
    helper('collection');

    if (empty($keys)) {
        throw new DataException('Key field is required');
    }

    //1 => one, 2 => two, 3 => three
    $data = [];
    foreach ($keys as $key) {
        $metas = array_maybe(get_user_meta($key, $user, false));
        foreach ($metas as $meta) {
            $data[$key][] = $meta->value;
        }
    }

    return $data;
}

/**
 * Check whether an object is a user
 *
 * @param object|string $item Item to check.
 *
 * @return boolean
 * @since   1.0.0
 * @version 1.0.0
 *
 * @author  Richard Muvirimi <rich4rdmuvirimi@gmail.com>
 */
function is_object_user($item): bool
{
    return is_a($item, '\App\Entities\User');
}

/**
 * Check whether an object is a user meta object
 *
 * @param object|string $item Item to check.
 *
 * @return boolean
 * @since   1.0.0
 * @version 1.0.0
 *
 * @author  Richard Muvirimi <rich4rdmuvirimi@gmail.com>
 */
function is_object_user_meta($item): bool
{
    return is_a($item, '\App\Entities\UserMeta');
}

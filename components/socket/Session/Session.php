<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/3/4
 * Time: 17:26
 */

namespace app\components\socket\Session;


class Session extends \Symfony\Component\HttpFoundation\Session\Session
{
    /**
     * @return bool whether the session has started
     */
    public function getIsActive()
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }
}
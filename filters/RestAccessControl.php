<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2017/7/21
 * Time: 11:50
 */

namespace app\filters;


use mdm\admin\components\AccessControl;

class RestAccessControl extends AccessControl
{
    public function beforeAction($action)
    {
//        $user = $this->getUser();
//        if ($user->getIsGuest()) {
//            throw new UnauthorizedRestException();
//        }
        $this->owner->controller->module->get('user')->userSkip($action);
        return parent::beforeAction($action);
    }

    protected function denyAccess($user)
    {
        $this->owner->controller->module->get('user')->userDeny();
        return;
    }

}
<?php

namespace app\components\filters;

use app\common\exception\ForbiddenRestException;
use app\common\exception\UnauthorizedRestException;
use mdm\admin\components\AccessControl;

class RestAccessControl extends AccessControl
{
    protected function denyAccess($user)
    {
        if ($user->getIsGuest()) {
            throw new UnauthorizedRestException();
        } else {
            throw new ForbiddenRestException();
        }
    }

}
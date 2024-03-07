<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2017/7/20
 * Time: 17:31
 */

namespace app\components\rest;

use app\common\exception\UnauthorizedRestException;
use yii\filters\auth\HttpBearerAuth;

class RestHttpBearerAuth extends HttpBearerAuth
{
    public function authenticate($user, $request, $response)
    {
        $authHeader = $request->getHeaders()->get('Authorization');
        if ($authHeader !== null &&
            preg_match('/^Bearer\s+(.*?)$/', $authHeader, $matches)) {
            $identity = $user->loginByAccessToken($matches[1], get_class($this));
            if ($identity === null) {
                throw new UnauthorizedRestException();
            }
            return $identity;
        }
        throw new UnauthorizedRestException();
    }

    public function handleFailure($response)
    {
        throw new UnauthorizedRestException();
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: tableliu
 * Date: 2016/3/19
 * Time: 22:07
 */

namespace app\common;


class Consts
{
    /**
     * rest result
     */
    const REST_NO_LOGIN = -1;
    const REST_SERVER_ERROR = 0;
    const REST_OK = 1;
    const REST_MODEL_ERROR = 2;
    const REST_FORBIDDEN = 3;
    const REST_FILE_NOT_EXIST = 4;
    const REST_NO_PID = 5;
    const REST_DATA_ERROR = 6;
    const REST_DATA_ILLEGAL_OPERATION = 7;

    /**
     * websocket
     */
    const LIVE_USER_TYPE_WEB = 'web';
    const LIVE_USER_TYPE_MOBILE = 'mobile';
}

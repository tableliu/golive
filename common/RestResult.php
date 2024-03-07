<?php
/**
 * Created by PhpStorm.
 * User: tableliu
 * Date: 2016/3/19
 * Time: 17:58
 */

namespace app\common;


use yii\base\BaseObject;

/**
 * Class RestResult
 * @package app\modules\rest\Common
 *
 * @OAS\Schema(
 *     title="RestResult",
 *     description="Rest Result model",
 *     type="object",
 *     @OAS\Xml(
 *         name="RestResult"
 *     )
 *
 * )
 *
 */
class RestResult extends BaseObject
{
    /**
     * @OAS\Property(
     *     title="code",
     *     description="返回状态代码（0=REST_SERVER_ERROR, 1=REST_OK, 2=REST_LOGIN_DENY, 3=REST_FORBIDDEN）",
     *     enum={"0", "1", "2", "3"},
     *     format="int32",
     * )
     *
     * @var integer
     */
    public $code;

    /**
     * @OAS\Property(
     *     title="content",
     *     description="返回内容",
     * )
     *
     * @var string
     */
    public $data;

    /**
     * @OAS\Property(
     *     title="content",
     *     description="返回提示信息",
     * )
     *
     * @var string
     */
    public $msg = "";

    public $errdata = null;

    public $errcode = 0;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }

    public function init()
    {
        parent::init();
    }
}
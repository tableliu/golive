<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/4/7
 * Time: 21:29
 */

namespace app\components\socket\rpc;


use Yii;
use yii\base\InlineAction;
use yii\filters\RateLimiter;
use yii\filters\VerbFilter;
use yii\web\ServerErrorHttpException;

class Controller extends \yii\base\Controller
{
    public function behaviors()
    {
        return [
//            'contentNegotiator' => [
//                'class' => ContentNegotiator::class,
//                'formats' => [
//                    'application/json' => 'json',
//                    'application/xml' => 'xml',
//                ],
//            ],
//            'authenticator' => [
//                'class' => CompositeAuth::class,
//            ],
            'verbFilter' => [
                'class' => VerbFilter::class,
            ],

            'rateLimiter' => [
                'class' => RateLimiter::class,
            ],
        ];
    }

    public function beforeAction($action)
    {
        if (parent::beforeAction($action)) {
            return true;
        }

        return false;
    }

    /**
     * @param \yii\base\Action $action
     * @param array $params
     * @return array
     * @throws Yii\base\UserException
     * @throws \ReflectionException
     */
    public function bindActionParams($action, $params)
    {
        if ($action instanceof InlineAction) {
            $method = new \ReflectionMethod($this, $action->actionMethod);
        } else {
            throw new ServerErrorHttpException(Yii::t('yii', 'action error.'));
        }

        $args = array_values($params);

        $missing = [];
        foreach ($method->getParameters() as $i => $param) {
            if ($param->isArray() && isset($args[$i])) {
                $args[$i] = $args[$i] === '' ? [] : preg_split('/\s*,\s*/', $args[$i]);
            }
            if (!isset($args[$i])) {
                if ($param->isDefaultValueAvailable()) {
                    $args[$i] = $param->getDefaultValue();
                } else {
                    $missing[] = $param->getName();
                }
            }
        }

        if (!empty($missing)) {
            throw new ServerErrorHttpException(Yii::t('yii', 'Missing required arguments: {params}', ['params' => implode(', ', $missing)]));
        }

        return $args;
    }
}
<?php

namespace app\models;

use app\common\IIPActiveRecord;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\web\UploadedFile;

/**
 * This is the model class for table "{{%live_msg}}".
 *
 * @property int $id
 * @property string $updated_at
 * @property string $created_at
 * @property int $updated_by
 * @property int $created_by
 * @property int $room_id
 * @property string $value
 * @property string $type
 * @property string $file_name
 * @property LiveRoom $room
 */
class LiveMsg extends IIPActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public $liveMsgDir = "upload/live-msg";

    public static function tableName()
    {
        return '{{%live_msg}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['updated_at', 'created_at'], 'safe'],
            [['room_id', 'type'], 'required'],
            [['updated_by', 'created_by', 'room_id'], 'integer'],
            [['type'], 'string', 'max' => 100],
            [['value', 'file_name'], 'string', 'max' => 255],
            [['room_id'], 'exist', 'skipOnError' => true, 'targetClass' => LiveRoom::className(), 'targetAttribute' => ['room_id' => 'id']],
        ];
    }


    public function behaviors()
    {
        return [
            'blameable' => [
                'class' => BlameableBehavior::class,
            ],
            'timestamp' => [
                'class' => 'yii\behaviors\TimestampBehavior',
                'value' => function ($event) {
                    return date("Y-m-d H:i:s");
                },
            ],
        ];
    }

    public function getRoom()
    {
        return $this->hasOne(LiveRoom::className(), ['id' => 'room_id']);
    }

    public function getProfile()
    {
        return $this->hasOne(Profile::class, ['user_id' => 'created_by']);
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    public static function Search($params)
    {
        $room_id = $params['room_id'];
        $pager = isset($params['pager']) ? $params['pager'] : Yii::$app->params['GridView.pagination.pageSize'];
        $query = LiveMsg::find()
            ->where(['room_id' => $room_id])
            ->limit($pager)
            ->orderBy('id DESC');
        if (isset($params['earliest_chat_id']) && !empty($params['earliest_chat_id'])) {
            $earliest_chat_id = $params['earliest_chat_id'];
            $query->andWhere(["<", 'id', $earliest_chat_id]);
        } else {
            $earliest_chat_id = 0;
        }
        $model = $query->all();
        return array("model" => $model, 'earliest_chat_id' => $earliest_chat_id, 'pager' => $pager);


    }


    public function getValueFile()
    {
        $time = date('Y-m-d', strtotime($this->created_at));
        $value = ($this->type != 'text' && isset($this->value)) ? $this->liveMsgDir . '/' . $time . '/' . $this->value : null;
        return $value;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function send($call_user, $exclude_self_push = true)
    {
        if ($this->save()) {
            \app\controllers\LivePushController::pushWebChatSent($this, LiveRoom::getWebUserIds($this->room_id, 1), $call_user, $exclude_self_push);
            \app\modules\onsite\controllers\LivePushController::pushMobileChatSent($this, LiveRoom::getMobileUserIds($this->room_id, 1), $call_user, $exclude_self_push);
            return true;
        }
        return false;
    }

}

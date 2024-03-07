<?php

namespace app\components;

use app\common\NotSelectProjectException;
use app\models\Project;
use app\models\ProjectUser;
use Yii;
use yii\base\Component;
use yii\helpers\Json;
use yii\web\ServerErrorHttpException;

class Settings extends Component implements SettingsInterface
{
    public $cache = 'cache';
    const SETTINGS_CACHE_KEY_PROJECT_INFO = 'project';
    const SETTINGS_CACHE_KEY_PROJECT_ID = 'pid';
    const SETTINGS_CACHE_KEY_DBS = 'databases';
    const SETTINGS_CACHE_KEY_DB = 'database';//MiA68I88DlFvQ206
    const SETTINGS_DB_PREFIX = 'iip';

    private $_project_db_array = [];

    public function init()
    {
        parent::init();
        if (is_string($this->cache)) {
            $this->cache = Yii::$app->get($this->cache, false);
        }
    }

    public static function getProjectDB($pid)
    {
        if (array_key_exists($pid, Yii::$app->settings->_project_db_array))
            return Yii::$app->settings->_project_db_array[$pid];

        /** @var \app\models\Project $project */
        $project = Project::find()->where(["pid" => $pid])->one();
        if (!isset($project) || !isset($project->db_config) || empty($project->db_config))
            throw new ServerErrorHttpException(Yii::t('app', 'No project or no db_config found.'));
        $db = Yii::createObject(Json::decode($project->db_config, true));
        Settings::setProjectDB($pid, $db);
        return $db;
    }

    public static function setProjectDB($pid, $db)
    {
        Yii::$app->settings->_project_db_array[$pid] = $db;
    }

    public function getCurrentDB($cache_key = "")
    {
        $pid = $this->getCurrentPid();
        if (!isset($pid) || empty($pid)) {
            throw new NotSelectProjectException();
        }

        return $this->getProjectDB($pid);
    }

    public static function getCurrentPid()
    {
        $curr_user = Yii::$app->user->identity;
        $pid = $curr_user->last_pid;
        if (isset($pid) && !empty($pid))
            return $pid;

        // 默认将 last_pid设置成分配给该用户的第一个项目id
        $pu = ProjectUser::find()
            ->andWhere(['user_id' => $curr_user->id])
            ->orderBy(['id' => SORT_ASC])
            ->one();
        if ($pu == null)
            throw new ServerErrorHttpException(Yii::t('app', 'No project for the user.'));
        $project = Project::findOne(['id' => $pu->pid]);
        if ($project == null)
            throw new ServerErrorHttpException(Yii::t('app', 'No project for the user.'));

        self::setUserLastPid($project->pid);
        return $curr_user->last_pid;
    }

    public static function setUserLastPid($pid, $user_id = null)
    {
        if ($user_id == null) {
            /** @var \app\models\User $user */
            $user = Yii::$app->user->identity;
        } else {
            $user = \app\models\User::findOne(['id' => $user_id]);
        }

        $user->last_pid = $pid;
        return $user->save(true, ['last_pid']);
    }


    //users
    public function getUserDB()
    {
        return Yii::$app->get("user_db");
    }


    public static function DsnName()
    {
        $dsn = Yii::$app->get("user_db")->dsn;
        $dbname = strrchr($dsn, 'dbname=');
        return substr($dbname, 7);
    }

    public static function makePidCacheKeyFromUser($user_id)
    {
        return 'pid_user_' . $user_id;
    }

    public static function makeDbCacheKeyFromPid($pid)
    {
        return 'db_pid_' . $pid;
    }


    //language

    public static function getCurrentLanguage()
    {
        $curr_user = Yii::$app->user->identity;
        $langguage = $curr_user->last_language;
        if (isset($langguage) && !empty($langguage))
            return $langguage;
        self::setUserLastLanguage(Yii::$app->params['Languages']['zh-CN']['lang']);
        return $curr_user->last_language;
    }

    public static function setUserLastLanguage($language, $user_id = null)
    {
        if ($user_id == null) {
            $user = Yii::$app->user->identity;
        } else {
            $user = \app\models\User::findOne(['id' => $user_id]);
        }

        $user->last_language = $language;
        return $user->save(true, ['last_language']);
    }



}
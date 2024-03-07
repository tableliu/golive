<?php

namespace app\components;

use app\models\Project;
use Yii;
use yii\base\Component;
use yii\helpers\Json;

class ConsoleSettings extends Component implements SettingsInterface
{
    private $_project_db_array = [];
    private $_currDb;

    /**
     * @param $pids string|array
     * @return object|array
     * @throws \yii\base\InvalidConfigException
     */
    public function getProjectsDB($pids)
    {
        $dbs = [];
        foreach ($pids as $item) {
            if (array_key_exists($item, Yii::$app->settings->_project_db_array)) {
                $dbs[] = Yii::$app->settings->_project_db_array[$item];
                $pids = array_diff($pids, [$item]);
            }
        }
        if (empty($pids))
            return $dbs;
        $projects = Project::find()
            ->select(['pid', 'db_config'])
            ->where(['in', 'pid', $pids])
            ->asArray()
            ->all();
        foreach ($projects as $project) {
            $db = Yii::createObject(Json::decode($project['db_config'], true));
            $this->setProjectDB($project['pid'], $db);
            $dbs[] = $db;
        }

        return $dbs;
    }

    public function setProjectDB($pid, $db)
    {
        $this->_project_db_array[$pid] = $db;
    }

    public function getCurrentDB($cache_key = null)
    {
        return $this->_currDb;
    }

    public function setCurrentDB($db)
    {
        $this->_currDb = $db;
    }

    public function getUserDB()
    {
        return Yii::$app->get("user_db");
    }

}
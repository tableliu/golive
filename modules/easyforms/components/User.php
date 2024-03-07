<?php
/**
 * Copyright (C) Baluart.COM - All Rights Reserved
 *
 * @since 1.0
 * @author Balu
 * @copyright Copyright (c) 2015 - 2019 Baluart.COM
 * @license http://codecanyon.net/licenses/faq Envato marketplace licenses
 * @link http://easyforms.baluart.com/ Easy Forms
 */

namespace app\modules\easyforms\components;

use Yii;
use app\modules\easyforms\models\Form;
use app\modules\easyforms\models\Theme;
use app\modules\easyforms\models\Template;
use app\modules\easyforms\helpers\ArrayHelper;
use app\modules\easyforms\components\behaviors\UserPreferences;

/**
 * Class User
 * @package app\components
 *
 * User Component
 */
class User extends \yii\web\User
{
    /**
     * @var null|Preferences
     */
    public $preferences = null;

    /**
     * Initializes the application component.
     */
    public function init()
    {
        parent::init();
        $this->preferences = new UserPreferences();
    }

    /**
     * @inheritdoc
     */
    public $identityClass = 'app\models\User';

    /**
     * Form ids created by this user
     *
     * @return array
     */
    public function getMyFormIds()
    {
        /** @var \app\models\User $user */
        $user = Yii::$app->user->identity;
        $userForms = Form::find()->where([
            'created_by' => $user->id
        ])->asArray()->all();
        $userForms = ArrayHelper::getColumn($userForms, 'id');
        return $userForms;
    }

    /**
     * Theme ids created by this user
     *
     * @return array
     */
    public function getMyThemeIds()
    {
        /** @var \app\models\User $user */
        $user = Yii::$app->user->identity;
        $userThemes = Theme::find()->where([
            'created_by' => $user->id
        ])->asArray()->all();
        $userThemes = ArrayHelper::getColumn($userThemes, 'id');
        return $userThemes;
    }

    /**
     * Theme ids created by this user
     *
     * @return array
     */
    public function getMyTemplateIds()
    {
        /** @var \app\models\User $user */
        $user = Yii::$app->user->identity;
        $userTemplates = Template::find()->where([
            'created_by' => $user->id
        ])->asArray()->all();
        $userTemplates = ArrayHelper::getColumn($userTemplates, 'id');
        return $userTemplates;
    }

    /**
     * Form ids assigned to this user
     *
     * @return array
     */
    public function getAssignedFormIds()
    {
        /** @var \app\models\User $user */
        $user = Yii::$app->user->identity;
        $userForms = $user->getUserForms()->asArray()->all(); // TODO Add forms created by the same user
        $userForms = ArrayHelper::getColumn($userForms, 'form_id');
        return $userForms;
    }

    /**
     * Check if user can access to Form.
     *
     * @param integer $id Form ID
     * @return bool
     */
    public function canAccessToForm($id)
    {
        if (isset(Yii::$app->user)) {
            $ids = null;
            if (Yii::$app->user->can('admin')) {
                return true;
            } elseif (Yii::$app->user->can('edit_own_content')) {
                $ids = $this->getMyFormIds();
            } else {
                $ids = $this->getAssignedFormIds();
            }
            if (count($ids) > 0 && in_array($id, $ids)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user can access to Theme.
     *
     * @param integer $id Theme ID
     * @return bool
     */
    public function canAccessToTheme($id)
    {
        if (isset(Yii::$app->user)) {
            if (Yii::$app->user->can('admin')) {
                return true;
            } else { // Only Advanced Users can create a theme
                $ids = $this->getMyThemeIds();
                if (count($ids) > 0 && in_array($id, $ids)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if user can access to Template.
     *
     * @param integer $id Template ID
     * @return bool
     */
    public function canAccessToTemplate($id)
    {
        if (isset(Yii::$app->user)) {
            if (Yii::$app->user->can('admin')) {
                return true;
            } else { // Only Advanced Users can create a template
                $ids = $this->getMyTemplateIds();
                if (count($ids) > 0 && in_array($id, $ids)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function userSkip($action)
    {
        if ($this->getIsGuest()) {
            header("Location: http://192.168.3.18:8080/");
            exit;
        }
        return;
    }

    public function userDeny()
    {

        if ($this->getIsGuest()) {
            $this->loginRequired();
        } else {
            header("Location: http://192.168.3.18:8080/");
            exit;
        }
    }

    public function getDisplayName($default = "")
    {
        /** @var \app\modules\user\models\User $user */
        $user = $this->getIdentity();
        return $user ? $user->getDisplayName($default) : "";
    }

    /**
     * Check if user can do $permissionName.
     * If "authManager" component is set, this will simply use the default functionality.
     * Otherwise, it will use our custom permission system
     * @param string $permissionName
     * @param array $params
     * @param bool $allowCaching
     * @return bool
     */
    public function can($permissionName, $params = [], $allowCaching = true)
    {
        // check for auth manager to call parent
        $auth = Yii::$app->getAuthManager();
        if ($auth) {
            return parent::can($permissionName, $params, $allowCaching);
        }

        // otherwise use our own custom permission (via the role table)
        /** @var \app\modules\user\models\User $user */
        $user = $this->getIdentity();
        return $user ? $user->can($permissionName) : false;
    }

}

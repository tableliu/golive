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

namespace app\modules\easyforms\models;

use app\components\JobBehavior;
use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\web\NotFoundHttpException;
use yii\helpers\FileHelper;
use app\modules\easyforms\helpers\Language;
use app\modules\easyforms\helpers\UrlHelper;
use app\modules\easyforms\helpers\TimeHelper;
use app\modules\easyforms\events\SubmissionEvent;
use app\modules\easyforms\components\behaviors\SluggableBehavior;
use app\modules\easyforms\components\validators\RecaptchaValidator;

/**
 * This is the model class for table "form".
 *
 * @property integer $id
 * @property string $name
 * @property string $slug
 * @property integer $status
 * @property integer $use_password
 * @property string $password
 * @property integer $authorized_urls
 * @property string $urls
 * @property integer $schedule
 * @property integer $schedule_start_date
 * @property integer $schedule_end_date
 * @property integer $total_limit
 * @property integer $total_limit_number
 * @property string $total_limit_period
 * @property integer $ip_limit
 * @property integer $ip_limit_number
 * @property string $ip_limit_period
 * @property integer $submission_number
 * @property string $submission_number_prefix
 * @property string $submission_number_suffix
 * @property integer $submission_number_width
 * @property integer $save
 * @property integer $resume
 * @property integer $autocomplete
 * @property integer $novalidate
 * @property integer $analytics
 * @property integer $honeypot
 * @property integer $recaptcha
 * @property string $language
 * @property string $message
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $created_at
 * @property integer $updated_at
 *
 * @property string $languageLabel
 *
 * @property User $author
 * @property User $lastEditor
 * @property Theme $theme
 * @property FormData $formData
 * @property FormUI $ui
 * @property FormRule $formRules
 * @property FormConfirmation $formConfirmation
 * @property FormEmail $formEmail
 * @property FormSubmission[] $formSubmissions
 * @property FormSubmissionFile[] $formSubmissionFiles
 * @property FormChart[] $formCharts
 * @property FormUser[] $formUsers
 * @property User[] $users
 */
class Form extends ActiveRecord
{

    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;

    const OFF = 0;
    const ON = 1;

    const SAVE_DISABLE = 0;
    const SAVE_ENABLE = 1;

    const RESUME_DISABLE = 0;
    const RESUME_ENABLE = 1;

    const AUTOCOMPLETE_DISABLE = 0;
    const AUTOCOMPLETE_ENABLE = 1;

    const ANALYTICS_DISABLE = 0;
    const ANALYTICS_ENABLE = 1;

    const HONEYPOT_INACTIVE = 0;
    const HONEYPOT_ACTIVE = 1;

    const RECAPTCHA_INACTIVE = 0;
    const RECAPTCHA_ACTIVE = 1;

    const FILES_DIRECTORY = "static_files/uploads"; // Give 0777 permission

    const EVENT_CHECKING_HONEYPOT = "app.form.submission.checkingHoneypot";
    const EVENT_CHECKING_SAVE = "app.form.submission.checkingSave";
    const EVENT_SPAM_DETECTED = "app.form.submission.spamDetected";

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%form}}';
    }

    /**
     * @inheritdoc
     */
    public static function primaryKey()
    {
        return ['id'];
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            [
                'class' => BlameableBehavior::className(),
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'updated_by',
                'defaultValue' => 1
            ],
            TimestampBehavior::className(),
            [
                'class' => SluggableBehavior::className(),
                'attribute' => 'name',
                'ensureUnique' => true,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['schedule_start_date', 'schedule_end_date'], 'required', 'when' => function ($model) {
                return $model->schedule == $this::ON;
            }, 'whenClient' => "function (attribute, value) {
                return $(\"input[name='Form[schedule]']:checked\").val() == '" . $this::ON . "';
            }"],
            [['password'], 'required', 'when' => function ($model) {
                return $model->use_password == $this::ON;
            }, 'whenClient' => "function (attribute, value) {
                return $(\"input[name='Form[use_password]']:checked\").val() == '" . $this::ON . "';
            }"],
            [['urls'], 'required', 'when' => function ($model) {
                return $model->authorized_urls == $this::ON;
            }, 'whenClient' => "function (attribute, value) {
                return $(\"input[name='Form[authorized_urls]']:checked\").val() == '" . $this::ON . "';
            }"],
            [['total_limit_number', 'total_limit_period'], 'required', 'when' => function ($model) {
                return $model->total_limit == $this::ON;
            }, 'whenClient' => "function (attribute, value) {
                return $(\"input[name='Form[total_limit]']:checked\").val() == '" . $this::ON . "';
            }"],
            [['ip_limit_number', 'ip_limit_period'], 'required', 'when' => function ($model) {
                return $model->ip_limit == $this::ON;
            }, 'whenClient' => "function (attribute, value) {
                return $(\"input[name='Form[ip_limit]']:checked\").val() == '" . $this::ON . "';
            }"],
            [['message'], 'string'],
            [['status', 'use_password', 'authorized_urls', 'schedule', 'schedule_start_date', 'schedule_end_date',
                'total_limit', 'total_limit_number', 'ip_limit', 'ip_limit_number',
                'save', 'resume', 'autocomplete', 'novalidate', 'analytics', 'honeypot', 'recaptcha',
                'created_by', 'updated_by', 'created_at', 'updated_at'], 'integer'],
            [['total_limit_period', 'ip_limit_period'], 'string', 'max' => 1],
            [['submission_number'], 'integer', 'min' => 0],
            [['submission_number_width'], 'integer', 'min' => 0, 'max' => 45],
            [['submission_number_prefix', 'submission_number_suffix'], 'string', 'max' => 100],
            [['name', 'password'], 'string', 'max' => 255],
            [['urls'], 'string', 'max' => 2555],
            [['password'], 'string', 'min' => 3],
            [['password'], 'filter', 'filter' => 'trim'],
            // ensure empty values are stored as NULL in the database
            ['password', 'default', 'value' => null],
            ['schedule_start_date', 'default', 'value' => null],
            ['schedule_end_date', 'default', 'value' => null],
            [['language'], 'string', 'max' => 5],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('form', 'ID'),
            'name' => Yii::t('form', 'Form Name'),
            'status' => Yii::t('form', 'Status'),
            'use_password' => Yii::t('form', 'Use password'),
            'password' => Yii::t('form', 'Password'),
            'authorized_urls' => Yii::t('form', 'Authorized URLs'),
            'urls' => Yii::t('form', 'URLs'),
            'schedule' => Yii::t('form', 'Schedule Form Activity'),
            'schedule_start_date' => Yii::t('form', 'Start Date'),
            'schedule_end_date' => Yii::t('form', 'End Date'),
            'total_limit' => Yii::t('form', 'Limit total number of submission'),
            'total_limit_number' => Yii::t('form', 'Total Number'),
            'total_limit_period' => Yii::t('form', 'Per Time Period'),
            'ip_limit' => Yii::t('form', 'Limit submissions from the same IP'),
            'ip_limit_number' => Yii::t('form', 'Max Number'),
            'ip_limit_period' => Yii::t('form', 'Per Time Period'),
            'submission_number' => Yii::t('form', 'Generate Submission Number'),
            'submission_number_prefix' => Yii::t('form', 'Number Prefix'),
            'submission_number_suffix' => Yii::t('form', 'Number Suffix'),
            'submission_number_width' => Yii::t('form', 'Number Width'),
            'save' => Yii::t('form', 'Save to DB'),
            'resume' => Yii::t('form', 'Save & Resume Later'),
            'autocomplete' => Yii::t('form', 'Auto complete'),
            'novalidate' => Yii::t('form', 'No validate'),
            'analytics' => Yii::t('form', 'Analytics'),
            'honeypot' => Yii::t('form', 'Spam filter'),
            'recaptcha' => Yii::t('form', 'reCaptcha'),
            'language' => Yii::t('form', 'Language'),
            'message' => Yii::t('form', 'Message'),
            'created_by' => Yii::t('form', 'Created by'),
            'updated_by' => Yii::t('form', 'Updated by'),
            'created_at' => Yii::t('form', 'Created at'),
            'updated_at' => Yii::t('form', 'Updated at'),
        ];
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAuthor()
    {
        return $this->hasOne(User::className(), ['id' => 'created_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLastEditor()
    {
        return $this->hasOne(User::className(), ['id' => 'updated_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFormData()
    {
        return $this->hasOne(FormData::className(), ['form_id' => 'id'])->inverseOf('form');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUi()
    {
        return $this->hasOne(FormUI::className(), ['form_id' => 'id'])->inverseOf('form');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTheme()
    {
        return $this->hasOne(Theme::className(), ['id' => 'theme_id'])
            ->via('ui');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFormRules()
    {
        return $this->hasMany(FormRule::className(), ['form_id' => 'id'])->inverseOf('form');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getActiveRules()
    {
        return $this->hasMany(FormRule::className(), ['form_id' => 'id'])
            ->where('status = :status', [':status' => FormRule::STATUS_ACTIVE])
            ->orderBy(['ordinal' => 'ASC', 'id' => 'ASC']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFormConfirmation()
    {
        return $this->hasOne(FormConfirmation::className(), ['form_id' => 'id'])->inverseOf('form');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFormEmail()
    {
        return $this->hasOne(FormEmail::className(), ['form_id' => 'id'])->inverseOf('form');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFormSubmissions()
    {
        return $this->hasMany(FormSubmission::className(), ['form_id' => 'id'])->inverseOf('form');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFormSubmissionFiles()
    {
        return $this->hasMany(FormSubmissionFile::className(), ['form_id' => 'id'])->inverseOf('form');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFormCharts()
    {
        return $this->hasMany(FormChart::className(), ['form_id' => 'id'])->inverseOf('form');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFormUsers()
    {
        return $this->hasMany(FormUser::className(), ['form_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUsers()
    {
        return $this->hasMany(User::className(), ['id' => 'user_id'])
            ->via('formUsers');
    }

    /**
     * Show label instead of value for boolean Status property
     * @return string
     */
    public function getStatusLabel()
    {
        return $this->status ? Yii::t('form', 'Active') : Yii::t('form', 'Inactive');
    }

    /**
     * Return list of Time Periods
     * @return array
     */
    public function getTimePeriods()
    {
        return TimeHelper::timePeriods();
    }

    /**
     * Returns the language name by its code
     * @return mixed
     */
    public function getLanguageLabel()
    {
        return Language::getLangByCode($this->language);
    }

    /**
     * @inheritdoc
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if ($insert) {
            // Create files directory, if it doesn't exists
            $filesDirectory = $this::FILES_DIRECTORY . '/' . $this->id;
            if (!is_dir($filesDirectory)) {
                FileHelper::createDirectory($filesDirectory, 0777, true);
            }
        }

    }

    /**
     * @inheritdoc
     */
    public function beforeDelete()
    {
        if (parent::beforeDelete()) {

            // Delete related Models
            $this->formData->delete();
            $this->ui->delete();
            $this->formConfirmation->delete();
            $this->formEmail->delete();

            // Delete all Charts, Submissions and Files related to this form
            // We use deleteAll for performance reason
            FormUser::deleteAll(["form_id" => $this->id]);
            FormRule::deleteAll(["form_id" => $this->id]);
            FormChart::deleteAll(["form_id" => $this->id]);
            FormSubmissionFile::deleteAll(["form_id" => $this->id]);
            FormSubmission::deleteAll(["form_id" => $this->id]);

            // Delete all Stats related to this form
            Event::deleteAll(["app_id" => $this->id]);
            StatsPerformance::deleteAll(["app_id" => $this->id]);
            StatsSubmissions::deleteAll(["app_id" => $this->id]);

            // Removes files directory (and all its content)
            // of this form (if exists)
            FileHelper::removeDirectory($this::FILES_DIRECTORY . '/' . $this->id);

            return true;
        } else {
            return false;
        }
    }

    /**
     * Delete related stats
     *
     * @return integer The number of rows deleted
     */
    public function deleteStats()
    {
        // Delete all Stats related to this form
        $events = Event::deleteAll(["app_id" => $this->id]);
        $stats = StatsPerformance::deleteAll(["app_id" => $this->id]);
        $submissions = StatsSubmissions::deleteAll(["app_id" => $this->id]);

        return $events + $stats + $submissions;
    }

    /**
     * Check if submission pass Honey Pot trap
     * If no pass throw NotFoundHttpException
     *
     * @param $post
     * @throws NotFoundHttpException
     */
    public function checkHoneypot($post)
    {

        Yii::$app->trigger($this::EVENT_CHECKING_HONEYPOT, new SubmissionEvent([
            'sender' => $this,
        ]));

        if ($this->honeypot === $this::HONEYPOT_ACTIVE) {
            if (isset($post['_email']) && !empty($post['_email'])) {

                Yii::$app->trigger($this::EVENT_SPAM_DETECTED, new SubmissionEvent([
                    'sender' => $this,
                ]));

                throw new NotFoundHttpException();
            }
        }

    }

    /**
     * Check if referrer page is in a authorized host
     *
     * @throws NotFoundHttpException
     */
    public function checkAuthorizedUrls()
    {
        if ($this->authorized_urls === $this::ON && !empty($this->urls)) {
            // Parse authorized hosts
            $urls = array_map('trim', explode(',', $this->urls));
            $hosts = [];
            foreach ($urls as $url) {
                $parsedUrl = parse_url(UrlHelper::addScheme($url, 'http'));
                $hosts[] = $parsedUrl['host'];
            }
            $hosts = array_unique($hosts);
            if (isset($_SERVER['HTTP_REFERER'])) {
                $referrer = parse_url($_SERVER['HTTP_REFERER']);
                // Add current host to authorized hosts
                if (isset($_SERVER['HTTP_HOST']) && !empty($_SERVER['HTTP_HOST'])) {
                    array_push($hosts, $_SERVER['HTTP_HOST']);
                }
                // If referrer is not in an authorized host
                if (!in_array($referrer['host'], $hosts)) {
                    throw new NotFoundHttpException();
                }
            }
        }
    }

    /**
     * reCaptcha Validation
     *
     * @param $post
     * @return array
     */
    public function validateRecaptcha($post)
    {

        $v = ['success' => true];

        // Only if Form has reCaptcha component and was not passed in this session
        if ($this->recaptcha === $this::RECAPTCHA_ACTIVE) {
            $recaptchaValidator = new RecaptchaValidator();
            $session = Yii::$app->session;
            $validate = true; // Flag
            // Detect if reCAPTCHA was loaded within the form
            $loaded = isset($post[$recaptchaValidator::CAPTCHA_RESPONSE_FIELD]);
            // Get the reCAPTCHA token
            $token = !empty($post[$recaptchaValidator::CAPTCHA_RESPONSE_FIELD]) ? $post[$recaptchaValidator::CAPTCHA_RESPONSE_FIELD] : '';
            // Check if user sent a valid reCAPTCHA token previously
            // reCaptcha can be true or false
            $captchaWasSolved = $session->has('reCaptcha') && $session->get('reCaptcha');
            // Logic
            if (!$loaded && $captchaWasSolved) {
                $validate = false;
            }

            // Smart captcha. Validate only if reCaptcha wasn't sent previously
            if ($validate && !$recaptchaValidator->validate($token, $message)) {
                $v = [
                    'success' => false,
                    'errorMessage' => Yii::t('form', 'There is {startTag}an error in your submission{endTag}.', [
                        'startTag' => '<strong>',
                        'endTag' => '</strong>',
                    ]),
                    'error' => [
                        'field' => $this->formData->getRecaptchaFieldID(),
                        'alias' => '',
                        'messages' => [$message],
                    ]
                ];
            }
        }

        return $v;
    }

    /**
     * Check if form does no accept more submissions
     */
    public function checkTotalLimit()
    {
        $v = ['success' => true];

        if ($this->total_limit === $this::ON) {

            $startTime = TimeHelper::startTime($this->total_limit_period);

            $submissions = FormSubmission::find()->select(['id'])->asArray()
                ->where(['form_id' => $this->id])
                ->andWhere(['between', 'created_at', $startTime, time()])->count();

            if ($this->total_limit_number <= $submissions) {
                $v = [
                    'success' => false,
                    'errorMessage' => Yii::t("app", "This form is no longer accepting new submissions per {period}.", [
                        'period' => TimeHelper::getPeriodByCode($this->total_limit_period)]),
                ];
            }
        }

        return $v;
    }

    /**
     * Check if user has reached his submission limit
     */
    public function checkIPLimit()
    {
        $v = ['success' => true];

        if ($this->ip_limit === $this::ON) {

            $startTime = TimeHelper::startTime($this->ip_limit_period);

            $ip = Yii::$app->getRequest()->getUserIP();

            if ($ip === "::1") {
                // Useful when the App run in localhost
                $ip = "81.2.69.160";
            }

            $submissions = FormSubmission::find()->select(['id'])->asArray()
                ->where(['form_id' => $this->id])
                ->andWhere(['between', 'created_at', $startTime, time()])
                ->andWhere(['ip' => $ip])
                ->count();

            if ($this->ip_limit_number <= $submissions) {
                $v = [
                    'success' => false,
                    'errorMessage' => Yii::t("app", "You have reached your Submission Limit per {period}.", [
                        'period' => TimeHelper::getPeriodByCode($this->ip_limit_period)]),
                ];
            }
        }

        return $v;
    }

    /**
     * Enable / Disable Form Activity
     */
    public function checkFormActivity()
    {
        if ($this->schedule === $this::ON && $this->status === $this::STATUS_ACTIVE) {
            if ($this->schedule_start_date > time() || $this->schedule_end_date < time()) {
                $this->status = $this::STATUS_INACTIVE;
                $this->save();
            }
        } elseif ($this->schedule === $this::ON && $this->status === $this::STATUS_INACTIVE) {
            if ($this->schedule_start_date < time() && $this->schedule_end_date > time()) {
                $this->status = $this::STATUS_ACTIVE;
                $this->save();
            }
        }
    }

    public function saveToDB()
    {

        Yii::$app->trigger($this::EVENT_CHECKING_SAVE, new SubmissionEvent([
            'sender' => $this,
        ]));

        return ($this->save === $this::SAVE_ENABLE);
    }

}

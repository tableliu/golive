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

namespace app\modules\easyforms\controllers;

use app\modules\easyforms\components\console\Console;
use app\modules\easyforms\helpers\ArrayHelper;
use app\modules\easyforms\helpers\FileHelper;
use app\modules\easyforms\helpers\MailHelper;
use app\modules\easyforms\helpers\SlugHelper;
use app\modules\easyforms\models\Form;
use app\modules\easyforms\models\FormConfirmation;
use app\modules\easyforms\models\FormData;
use app\modules\easyforms\models\FormEmail;
use app\modules\easyforms\models\FormRule;
use app\modules\easyforms\models\FormUI;
use app\modules\easyforms\models\Template;
use Carbon\Carbon;
use Swift_Mailer;
use Swift_SmtpTransport;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\Json;
use yii\web\Controller;
use yii\web\Response;
use yii\web\UploadedFile;

/**
 * Class SettingsController
 * @package app\controllers
 */
class SettingsController extends Controller
{

    public $defaultAction = 'site';

    public function behaviors()
    {
        return [
            'access' => [
                'class' => VerbFilter::className(),
            ],
        ];
    }

    /**
     * Update App Settings
     *
     * @return string
     */
    public function actionSite()
    {

        $this->layout = 'admin'; // In @app/views/layouts
        if (Yii::$app->request->post()) {
            Yii::$app->formsettings->set('app.name', Yii::$app->request->post('app_name', Yii::$app->formsettings->get('app.name')));
            Yii::$app->formsettings->set('app.description', Yii::$app->request->post('app_description', Yii::$app->formsettings->get('app.description')));
            Yii::$app->formsettings->set('app.adminEmail', Yii::$app->request->post('app_adminEmail', Yii::$app->formsettings->get('app.adminEmail')));
            Yii::$app->formsettings->set('app.supportEmail', Yii::$app->request->post('app_supportEmail', Yii::$app->formsettings->get('app.supportEmail')));
            Yii::$app->formsettings->set('app.noreplyEmail', Yii::$app->request->post('app_noreplyEmail', Yii::$app->formsettings->get('app.noreplyEmail')));
            //
            Yii::$app->formsettings->set('app.reCaptchaSiteKey', Yii::$app->request->post('app_reCaptchaSiteKey', Yii::$app->formsettings->get('app.reCaptchaSiteKey')));
            Yii::$app->formsettings->set('app.reCaptchaSecret', Yii::$app->request->post('app_reCaptchaSecret', Yii::$app->formsettings->get('app.reCaptchaSecret')));

            // Membership
            $anyoneCanRegister = Yii::$app->request->post('app_anyoneCanRegister', null);
            $useCaptcha = Yii::$app->request->post('app_useCaptcha', null);
            $loginWithoutPassword = Yii::$app->request->post('app_loginWithoutPassword', null);
            Yii::$app->formsettings->set('app.anyoneCanRegister', is_null($anyoneCanRegister) ? 0 : 1);
            Yii::$app->formsettings->set('app.useCaptcha', is_null($useCaptcha) ? 0 : 1);
            Yii::$app->formsettings->set('app.loginWithoutPassword', is_null($loginWithoutPassword) ? 0 : 1);
            Yii::$app->formsettings->set('app.defaultUserRole', Yii::$app->request->post('app_defaultUserRole', Yii::$app->formsettings->get('app.defaultUserRole')));

            // Logo
            $image = UploadedFile::getInstanceByName('logo');
            if ($image) {
                $logoDir = 'static_files/uploads/logos';
                $oldImage = Yii::$app->settings->get('app.logo');
                $newImage = $logoDir . '/' . $image->baseName . '.' . $image->extension;
                if (FileHelper::createDirectory($logoDir)) {
                    if ($image->saveAs($newImage)) {
                        @unlink($oldImage);
                        Yii::$app->settings->set('app.logo', $newImage);
                    }
                }
            }

            // Show success alert
            Yii::$app->getSession()->setFlash(
                'success',
                Yii::t('form', 'The site settings have been successfully updated.')
            );
        }

        return $this->render('site');
    }

    public function actionMail()
    {

        $this->layout = 'admin'; // In @app/views/layouts

        if (Yii::$app->request->post()) {

            try {

                if ($toEmail = Yii::$app->request->post('email')) {

                    // Remove all illegal characters from email
                    $toEmail = filter_var($toEmail, FILTER_SANITIZE_EMAIL);
                    // Validate e-mail
                    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL) === false) {
                        // Sender by default: No-Reply Email
                        $fromEmail = MailHelper::from(Yii::$app->formsettings->get("app.noreplyEmail"));
                        // Send email
                        $success = Yii::$app->mailer->compose()
                            ->setFrom($fromEmail)
                            ->setTo($toEmail)
                            ->setSubject(Yii::t('form', 'Test email sent to {email}', ['email' => $toEmail]))
                            ->setTextBody(Yii::t('form', 'This is a test email generated by {app}.', ['app' => Yii::$app->formsettings->get("app.name")]))
                            ->setHtmlBody(Yii::t('form', 'This is a test email generated by {app}.', ['app' => Yii::$app->formsettings->get("app.name")]))
                            ->send();

                        // Show success alert
                        if ($success) {
                            Yii::$app->getSession()->setFlash(
                                'success',
                                Yii::t('form', "Test email has been successfully sent.")
                            );
                        } else {
                            echo "shibai";
                            die();
                            Yii::$app->getSession()->setFlash(
                                'danger',
                                Yii::t('form', "Test email was not sent.")
                            );
                        }

                    }

                } elseif (Yii::$app->request->post('app_mailerTransport')) {

                    // Get settings
                    $mailerTransport = Yii::$app->request->post('app_mailerTransport', Yii::$app->formsettings->get('app.mailerTransport'));

                    // Test SMTP connection
                    if ($mailerTransport === 'smtp') {
                        $host = Yii::$app->request->post('smtp_host', Yii::$app->formsettings->get('smtp.host'));
                        $port = Yii::$app->request->post('smtp_port', Yii::$app->formsettings->get('smtp.port'));
                        $encryption = Yii::$app->request->post('smtp_encryption', Yii::$app->formsettings->get('smtp.encryption'));
                        $username = Yii::$app->request->post('smtp_username', Yii::$app->formsettings->get('smtp.username'));
                        $password = Yii::$app->request->post('smtp_password', Yii::$app->formsettings->get('smtp.password'));
                        $async = Yii::$app->request->post('app_async', null);
                        // Test SMTP connection
                        $transport = Swift_SmtpTransport::newInstance($host, $port);
                        if ($encryption !== 'none') {
                            $transport = Swift_SmtpTransport::newInstance($host, $port, $encryption);
                        }
                        $transport->setUsername($username);
                        $transport->setPassword($password);
                        $mailer = Swift_Mailer::newInstance($transport);
                        $mailer->getTransport()->start();
                        // Save settings
                        Yii::$app->formsettings->set('smtp.host', $host);
                        Yii::$app->formsettings->set('smtp.port', $port);
                        Yii::$app->formsettings->set('smtp.encryption', $encryption);
                        Yii::$app->formsettings->set('smtp.username', $username);
                        Yii::$app->formsettings->set('smtp.password', $password);
                        Yii::$app->formsettings->set('app.async', is_null($async) ? 0 : 1);
                        // Save Mailer Transport
                        Yii::$app->formsettings->set('app.mailerTransport', $mailerTransport);
                        // Show success alert
                        Yii::$app->getSession()->setFlash(
                            'success',
                            Yii::t('form', 'The SMTP Server settings have been successfully updated.')
                        );
                    } elseif ($mailerTransport === 'sendinblue') {
                        $apiKey = Yii::$app->request->post('sendinblue_key');
                        if (!empty($apiKey)) {
                            Yii::$app->formsettings->set('sendinblue.key', $apiKey);
                            // Save Mailer Transport
                            Yii::$app->formsettings->set('app.mailerTransport', $mailerTransport);
                            // Show success alert
                            Yii::$app->getSession()->setFlash(
                                'success',
                                Yii::t('form', 'The SMTP Server settings have been successfully updated.')
                            );
                        } else {
                            Yii::$app->formsettings->set('sendinblue.key', '');
                            // Show success alert
                            Yii::$app->getSession()->setFlash(
                                'danger',
                                Yii::t('form', 'Your Sendinblue Api Key is empty. Try it again.')
                            );
                        }
                    } elseif ($mailerTransport === 'php') {
                        // Save Mailer Transport
                        Yii::$app->formsettings->set('app.mailerTransport', $mailerTransport);
                        // Show success alert
                        Yii::$app->getSession()->setFlash(
                            'success',
                            Yii::t('form', 'The SMTP Server settings have been successfully updated.')
                        );
                    }
                }

            } catch (\Exception $e) {
                // Log error
                Yii::error($e);
                // Show error alert
                Yii::$app->getSession()->setFlash(
                    'danger',
                    $e->getMessage()
                );
            }
        }

        return $this->render('mail');

    }

    public function actionPerformance()
    {
        $this->layout = 'admin'; // In @app/views/layouts

        if ($post = Yii::$app->request->post()) {

            // Run cron
            if (isset($post['action']) && $post['action'] === 'cron') {
                Console::run('cron');
                Yii::$app->getSession()->setFlash(
                    'success',
                    Yii::t('form', 'Cron ran successfully.')
                );
            }

            // Refresh cache & assets
            if (isset($post['action']) && $post['action'] === 'cache') {

                $writable = true;

                $subdirectories = FileHelper::scandir(Yii::getAlias('@runtime/cache'));

                foreach ($subdirectories as $subdirectory) {
                    if (!is_writable(Yii::getAlias('@runtime/cache') . DIRECTORY_SEPARATOR . $subdirectory)) {
                        $writable = false;
                    }
                }

                // Flush all cache
                $flushed = Yii::$app->cache->flush();

                // Remove all assets
                foreach (glob(Yii::$app->assetManager->basePath . DIRECTORY_SEPARATOR . '*') as $asset) {
                    if (is_link($asset)) {
                        @unlink($asset);
                    } elseif (is_dir($asset)) {
                        FileHelper::removeDirectory($asset);
                    } else {
                        @unlink($asset);
                    }
                }

                // Show success alert
                if ($writable && $flushed) {
                    Yii::$app->getSession()->setFlash(
                        'success',
                        Yii::t('form', 'The cache and assets have been successfully refreshed.')
                    );
                } else {
                    Yii::$app->getSession()->setFlash(
                        'danger',
                        Yii::t('form', 'There was a problem clearing the cache. Please retry later.')
                    );
                }
            }

            // Update Form Fields
            if (isset($post['action']) && $post['action'] === 'update-form-fields') {

                $data = FormData::find()
                    ->indexBy('id')
                    ->all();;

                // Add Alias option to Field Settings, if this option doesn't exists
                // @since v1.6.6

                $aliasField = ['alias' => Json::decode('{
                    "label": "component.alias",
                    "type": "input",
                    "value": "",
                    "advanced": true
                }', true)];

                /** @var FormData $d */
                foreach ($data as $d) {
                    // Get Form Builder configuration
                    $builder = Json::decode($d->builder, true);
                    // Get configuration of each field
                    $formFields = ArrayHelper::getValue($builder, 'initForm');
                    $i = 0;
                    foreach ($formFields as $formField) {
                        // Add Alias option to field settings
                        // Except by: heading, paragraph, snippet, recaptcha, pagebreak and button
                        if (!in_array($formField['name'], ['heading', 'paragraph', 'snippet', 'recaptcha', 'pagebreak', 'button'])) {
                            $fields = $formField['fields'];
                            // Check if Alias doesn't exists
                            $alias = ArrayHelper::getValue($builder, 'initForm.' . $i . '.fields.alias');
                            if (!$alias) {
                                if ($formField['name'] === 'hidden') { // Insert before Disabled option
                                    ArrayHelper::insert($fields, 'disabled', $aliasField);
                                } else { // Insert after ContainerClass option
                                    ArrayHelper::insert($fields, 'containerClass', $aliasField, true);
                                }
                                // Replace each settings of each field in the form
                                ArrayHelper::setValue($builder, 'initForm.' . $i . '.fields', $fields);
                            }
                        }
                        $i++;
                    }
                    // Update Form Builder configuration
                    $d->builder = Json::htmlEncode($builder);
                    $d->save();
                }

                $template = Template::find()
                    ->indexBy('id')
                    ->all();;

                /** @var Template $t */
                foreach ($template as $t) {
                    // Get Form Builder configuration
                    $builder = Json::decode($t->builder, true);
                    // Get configuration of each field
                    $formFields = ArrayHelper::getValue($builder, 'initForm');
                    $i = 0;
                    foreach ($formFields as $formField) {
                        // Add Alias option to field settings
                        // Except by: heading, paragraph, snippet, recaptcha, pagebreak and button
                        if (!in_array($formField['name'], ['heading', 'paragraph', 'snippet', 'recaptcha', 'pagebreak', 'button'])) {
                            $fields = $formField['fields'];
                            // Check if Alias doesn't exists
                            $alias = ArrayHelper::getValue($builder, 'initForm.' . $i . '.fields.alias');
                            if (!$alias) {
                                if ($formField['name'] === 'hidden') { // Insert before Disabled option
                                    ArrayHelper::insert($fields, 'disabled', $aliasField);
                                } else { // Insert after ContainerClass option
                                    ArrayHelper::insert($fields, 'containerClass', $aliasField, true);
                                }
                                // Replace each settings of each field in the form
                                ArrayHelper::setValue($builder, 'initForm.' . $i . '.fields', $fields);
                            }
                        }
                        $i++;
                    }
                    // Update Form Builder configuration
                    $t->builder = Json::htmlEncode($builder);
                    $t->save();
                }

                Yii::$app->getSession()->setFlash(
                    'success',
                    Yii::t('form', 'The Form Builder fields have been successfully updated.')
                );
            }
        }

        return $this->render('performance');
    }

    public function actionImportExport()
    {
        $this->layout = 'admin'; // In @app/views/layouts

        if ($post = Yii::$app->request->post()) {
            // Import
            if (isset($post['action']) && $post['action'] === 'import') {
                $transaction = Form::getDb()->beginTransaction();
                try {
                    $file = UploadedFile::getInstanceByName('file');
                    $content = Json::decode(file_get_contents($file->tempName));
                    if (!empty($content['forms'])) {
                        foreach ($content['forms'] as $form) {
                            // Form
                            $formModel = new Form();
                            $formModel->attributes = $form['form'];
                            $formModel->id = null;
                            $formModel->isNewRecord = true;
                            $formModel->save();

                            // Form Data
                            $formDataModel = new FormData();
                            $formDataModel->attributes = $form['data'];
                            $formDataModel->id = null;
                            $formDataModel->form_id = $formModel->id;
                            $formDataModel->isNewRecord = true;
                            $formDataModel->save();

                            // Confirmation
                            $formConfirmationModel = new FormConfirmation();
                            $formConfirmationModel->attributes = $form['confirmation'];
                            $formConfirmationModel->id = null;
                            $formConfirmationModel->form_id = $formModel->id;
                            $formConfirmationModel->isNewRecord = true;
                            $formConfirmationModel->save();

                            // Notification
                            $formEmailModel = new FormEmail();
                            $formEmailModel->attributes = $form['email'];
                            $formEmailModel->id = null;
                            $formEmailModel->form_id = $formModel->id;
                            $formEmailModel->isNewRecord = true;
                            $formEmailModel->save();

                            // UI
                            $formUIModel = new FormUI();
                            $formUIModel->id = null;
                            $formUIModel->form_id = $formModel->id;
                            $formUIModel->isNewRecord = true;
                            $formUIModel->save();

                            // Conditional Rules
                            foreach ($form['rules'] as $rule) {
                                $formRuleModel = new FormRule();
                                $formRuleModel->attributes = $rule;
                                $formRuleModel->id = null;
                                $formRuleModel->form_id = $formModel->id;
                                $formRuleModel->isNewRecord = true;
                                $formRuleModel->save();
                            }
                        }

                        $transaction->commit();
                        Yii::$app->getSession()->setFlash('success', Yii::t('form', 'The form has been successfully imported'));
                    }

                } catch (\Exception $e) {
                    $transaction->rollBack();
                    Yii::$app->getSession()->setFlash('danger', Yii::t('form', 'The form could not be imported'));
                }
            }

            // Export
            if (isset($post['action']) && $post['action'] === 'export') {
                if (empty($post['forms'])) {
                    Yii::$app->getSession()->setFlash(
                        'danger',
                        Yii::t('form', 'Please select a form to export the migration file')
                    );
                } else {
                    $filename = '';
                    $content = [];
                    $ids = $post['forms'];
                    $forms = Form::findAll(['id' => $ids]);

                    foreach ($forms as $form) {
                        $content[] = [
                            'form' => ArrayHelper::toArray($form),
                            'data' => ArrayHelper::toArray($form->formData),
                            'confirmation' => ArrayHelper::toArray($form->formConfirmation),
                            'email' => ArrayHelper::toArray($form->formEmail),
                            'rules' => ArrayHelper::toArray($form->formRules),
                        ];
                        $filename = SlugHelper::slug($form->name, ['delimiter' => '_']);
                    }
                    $content = Json::encode([
                        'forms' => $content
                    ]);
                    $filename = count($forms) > 1 ? 'forms' : $filename;
                    $filename = $filename . '_' . Carbon::today()->toDateString() . '.json';

                    $options = [
                        'mimeType' => 'application/json',
                        'inline' => false,
                    ];

                    return Yii::$app->response->sendContentAsFile($content, $filename, $options);
                }
            }
        }

        // Select id & name of all forms in the system
        $forms = Form::find()->select(['id', 'name'])->orderBy('updated_at DESC')->asArray()->all();
        $forms = ArrayHelper::map($forms, 'id', 'name');

        return $this->render('import-export', [
            'forms' => $forms,
        ]);
    }

    /**
     * Delete logo
     *
     * @return array|string
     */
    public function actionLogoDelete()
    {

        // Delete for ajax request
        if (Yii::$app->request->isAjax) {

            Yii::$app->response->format = Response::FORMAT_JSON;

            $image = Yii::$app->settings->get('app.logo');
            if (!@unlink($image)) {
                Yii::$app->session->setFlash(
                    'error',
                    Yii::t("app", "Has occurred an error deleting your logo.")
                );
                return [
                    'success' => 0,
                ];
            };
            Yii::$app->settings->set('app.logo', '');
            Yii::$app->session->setFlash("success", Yii::t("app", "Your logo has been deleted."));
            return [
                'success' => 1,
            ];
        }

        Yii::$app->session->setFlash(
            'error',
            Yii::t("app", "Bad request.")
        );

        return [
            'success' => 0,
        ];
    }
}

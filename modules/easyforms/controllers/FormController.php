<?php

namespace app\modules\easyforms\controllers;

use app\modules\easyforms\helpers\ArrayHelper;
use app\modules\easyforms\helpers\Honeypot;
use app\modules\easyforms\helpers\Html;
use app\modules\easyforms\helpers\Pager;
use app\modules\easyforms\helpers\UrlHelper;
use app\modules\easyforms\models\Form;
use app\modules\easyforms\models\FormConfirmation;
use app\modules\easyforms\models\FormData;
use app\modules\easyforms\models\FormEmail;
use app\modules\easyforms\models\FormRule;
use app\modules\easyforms\models\forms\PopupForm;
use app\modules\easyforms\models\FormSubmission;
use app\modules\easyforms\models\FormUI;
use app\modules\easyforms\models\FormSearch;
use app\modules\easyforms\models\Template;
use app\modules\easyforms\models\Theme;
use app\modules\easyforms\models\User;

use League\Csv\Writer;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use SplTempFileObject;
use Yii;
use yii\base\Model;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Class FormController
 * @package app\controllers
 */
class FormController extends Controller
{

    /** @inheritdoc */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'copy' => ['post'],
                    'delete' => ['post'],
                ],
            ],
        ];
    }

    public function actions()
    {
        return [
            'delete-multiple' => [
                'class' => 'app\modules\easyforms\components\actions\DeleteMultipleAction',
                'modelClass' => 'app\modules\easyforms\models\Form',
                'afterDeleteCallback' => function () {
                    Yii::$app->getSession()->setFlash(
                        'success',
                        Yii::t('form', 'The selected items have been successfully deleted.')
                    );
                },
            ],
        ];
    }

    /**
     * Lists all Form models.
     *
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new FormSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        // Only for admin users
//        if (!empty(Yii::$app->user) && Yii::$app->user->can("admin") && ($dataProvider->totalCount == 0)) {
//            Yii::$app->getSession()->setFlash(
//                'warning',
//                Html::tag('strong', Yii::t("app", "You don't have any form!")) . ' ' .
//                Yii::t("app", "Click the blue button on the left to start building your first form.")
//            );
//        }

        // Select slug & name of all promoted templates in the system. Limit to 5 results.
        $templates = Template::find()->select(['slug', 'name'])->where([
            'promoted' => Template::PROMOTED_ON,
        ])->limit(5)->orderBy('updated_at DESC')->asArray()->all();
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'templates' => $templates,
        ]);
    }

    /**
     * Show form builder to create a Form model.
     *
     * @param string $template
     * @return string
     */
    public function actionCreate($template = 'default')
    {

        $this->disableAssets();
        return $this->render('create', [
            'template' => $template
        ]);
    }

    /**
     * Show form builder to update Form model.
     *
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id = null)
    {
        $this->disableAssets();

        $model = $this->findFormModel($id);

        return $this->render('update', [
            'model' => $model,
        ]);

    }

    /**
     * Enable / Disable multiple Forms
     *
     * @param $status
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     * @throws \Exception
     */
    public function actionUpdateStatus($status)
    {

        $forms = Form::findAll(['id' => Yii::$app->getRequest()->post('ids')]);

        if (empty($forms)) {
            throw new NotFoundHttpException(Yii::t('form', 'Page not found.'));
        } else {
            foreach ($forms as $form) {
                $form->status = $status;
                $form->update();
            }
            Yii::$app->getSession()->setFlash(
                'success',
                Yii::t('form', 'The selected items have been successfully updated.')
            );
            return $this->redirect(['index']);
        }
    }

    /**
     * Updates an existing Form model (except id).
     * Updates an existing FormData model (only data field).
     * Updates an existing FormConfirmation model (except id & form_id).
     * Updates an existing FormEmail model (except id & form_id).
     * If update is successful, the browser will be redirected to the 'index' page.
     *
     * @param int|null $id
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException
     * @throws \Exception
     * @throws \yii\db\Exception
     */
    public function actionSettings($id = null)
    {
        /** @var \app\models\Form $formModel */
        $formModel = $this->findFormModel($id);
        $formDataModel = $formModel->formData;
        $formConfirmationModel = $formModel->formConfirmation;
        $formEmailModel = $formModel->formEmail;
        $formUIModel = $formModel->ui;

        $postData = Yii::$app->request->post();


        if ($formModel->load($postData) && $formConfirmationModel->load($postData)
            && $formEmailModel->load($postData) && $formUIModel->load($postData)
            && Model::validateMultiple([$formModel, $formConfirmationModel, $formEmailModel, $formUIModel])) {

            // Save data in single transaction
            $transaction = Form::getDb()->beginTransaction();
            try {
                // Save Form Model
                if (!$formModel->save()) {
                    throw new \Exception(Yii::t("app", "Error saving Form Model"));
                }
                // Save data field in FormData model
                if (isset($postData['Form']['name'])) {
                    // Convert JSON Data of Form Data Model to PHP Array
                    /** @var \app\components\JsonToArrayBehavior $builderField */
                    $builderField = $formDataModel->behaviors['builderField'];
                    // Set form name by json key path. If fail, throw \ArrayAccessException
                    $builderField->setSafeValue(
                        'settings.name',
                        $postData['Form']['name']
                    );
                    // Save to DB
                    $builderField->save(); // If fail, throw \Exception
                }

                // Convert data images to stored images in the email messages
                $location = $formModel::FILES_DIRECTORY . '/' . $formModel->id;
                if (!empty($formConfirmationModel->mail_message)) {
                    // Confirmation email message
                    $html = $formConfirmationModel->mail_message;
                    $formConfirmationModel->mail_message = Html::storeBase64ImagesOnLocation($html, $location);
                }
                if (!empty($formEmailModel->message)) {
                    // Notification email message
                    $html = $formEmailModel->message;
                    $formEmailModel->message = Html::storeBase64ImagesOnLocation($html, $location);
                }

                // Save FormConfirmation Model
                if (!$formConfirmationModel->save()) {
                    throw new \Exception(Yii::t("app", "Error saving Form Confirmation Model"));
                }
                // Save FormEmail Model
                if (!$formEmailModel->save()) {
                    throw new \Exception(Yii::t("app", "Error saving Form Email Model"));
                }
                // Save FormUI Model
                if (!$formUIModel->save()) {
                    throw new \Exception(Yii::t("app", "Error saving Form UI Model"));
                }

                $transaction->commit();

                Yii::$app->getSession()->setFlash(
                    'success',
                    Yii::t('form', 'The form settings have been successfully updated')
                );

                if (isset($postData['continue'])) {
                    return $this->refresh();
                }

                return $this->redirect(['index']);
            } catch (\Exception $e) {
                // Rolls back the transaction
                $transaction->rollBack();
                // Rethrow the exception
                throw $e;
            }

        } else {
            //           $users = [];
//            if (Yii::$app->user->can('admin')) {
//                // Select id & name of all themes in the system
//                $themes = Theme::find()->select(['id', 'name'])->asArray()->all();
//                // Select id & name of all users with admin and advanced roles
//                $users = User::find()->select(['id', 'username'])
//                    ->where(['in', 'role_id', [Role::ROLE_ADMIN, Role::ROLE_ADVANCED_USER]])
//                    ->asArray()->all();
//                $users = ArrayHelper::map($users, 'id', 'username');
//            } else {
//               //  Only themes of the current user and administrators
//                $userAndAdmins = User::find()->where(['role_id' => Role::ROLE_ADMIN])->asArray()->all();
//                $userAndAdmins = ArrayHelper::getColumn($userAndAdmins, 'id');
//                $userAndAdmins[] = Yii::$app->user->id;
//                $themes = Theme::find()->select(['id', 'name'])
//                    ->where(['created_by' => $userAndAdmins])
//                    ->asArray()->all();
//            }
            $users = User::find()->select(['id', 'username'])->asArray()->all();
            $users = ArrayHelper::map($users, 'id', 'username');
            $themes = Theme::find()->select(['id', 'name'])->asArray()->all();
            $themes = ArrayHelper::map($themes, 'id', 'name');

            return $this->render('settings', [
                'formModel' => $formModel,
                'formDataModel' => $formDataModel,
                'formConfirmationModel' => $formConfirmationModel,
                'formEmailModel' => $formEmailModel,
                'formUIModel' => $formUIModel,
                'themes' => $themes,
                'users' => $users,
            ]);
        }

    }

    /**
     * Displays a single Form Data Model.
     *
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $formModel = $this->findFormModel($id);

        return $this->render('view', [
            'formModel' => $formModel,
        ]);
    }

    /**
     * Displays a single Form Rule Model.
     *
     * @param integer $id
     * @return mixed
     */
    public function actionRules($id)
    {
        $formModel = $this->findFormModel($id);
        $formDataModel = $formModel->formData;

        return $this->render('rule', [
            'formModel' => $formModel,
            'formDataModel' => $formDataModel,
        ]);
    }

    /**
     * Displays share options.
     *
     * @param integer $id
     * @return mixed
     */
    public function actionShare($id)
    {

        $formModel = $this->findFormModel($id);
        $formDataModel = $formModel->formData;
        $popupForm = new PopupForm();
        //print_r($formDataModel);die();

        //  print_r($popupForm);die();
        return $this->render('share', [
            'formModel' => $formModel,
            'formDataModel' => $formDataModel,
            'popupForm' => $popupForm
        ]);
    }

    /**
     * Preview a PopUp Form.
     *
     * @param integer $id
     * @return mixed
     */
    public function actionPopupPreview($id)
    {
        $this->layout = false;

        $popupForm = new PopupForm();

        if ($popupForm->load(Yii::$app->request->post()) && $popupForm->validate()) {

            $formModel = $this->findFormModel($id);
            $formDataModel = $formModel->formData;

            return $this->render('popup-preview', [
                'formModel' => $formModel,
                'formDataModel' => $formDataModel,
                'popupForm' => $popupForm,
            ]);

        }

        return $this->redirect(['form/share', 'id' => $id]);

    }

    /**
     * Displays the PopUp Form Generated Code.
     *
     * @param integer $id
     * @return mixed
     */
    public function actionPopupCode($id)
    {
        $this->layout = false;

        $popupForm = new PopupForm();

        if ($popupForm->load(Yii::$app->request->post()) && $popupForm->validate()) {

            $formModel = $this->findFormModel($id);
            $formDataModel = $formModel->formData;

            return $this->render('popup-code', [
                'formModel' => $formModel,
                'formDataModel' => $formDataModel,
                'popupForm' => $popupForm,
            ]);

        }

        return $this->redirect(['form/share', 'id' => $id]);

    }

    /**
     * Display form performance analytics page.
     *
     * @param integer $id
     * @return mixed
     */
    public function actionAnalytics($id)
    {
        $formModel = $this->findFormModel($id);
        $formDataModel = $formModel->formData;

        return $this->render('analytics', [
            'formModel' => $formModel,
            'formDataModel' => $formDataModel,
        ]);
    }

    /**
     * Displays form submissions stats page.
     *
     * @param integer $id
     * @return mixed
     */
    public function actionStats($id)
    {
        $formModel = $this->findFormModel($id);
        $formDataModel = $formModel->formData;

        return $this->render('stats', [
            'formModel' => $formModel,
            'formDataModel' => $formDataModel,
        ]);
    }

    /**
     * Reset form submissions stats and performance analytics
     * If the delete is successful, the browser will be redirected to the 'index' page.
     *
     * @param integer $id
     * @return mixed
     */
    public function actionResetStats($id)
    {
        // Delete all Stats related to this form
        $rowsDeleted = $this->findFormModel($id)->deleteStats();

        if ($rowsDeleted > 0) {
            Yii::$app->getSession()->setFlash('success', Yii::t('form', 'The stats have been successfully deleted.'));
        } else {
            Yii::$app->getSession()->setFlash('danger', Yii::t('form', 'There are no items to delete.'));
        }

        return $this->redirect(['index']);
    }

    /**
     * Copy an existing Form model (and relations).
     * If the copy is successful, the browser will be redirected to the 'index' page.
     *
     * @param integer $id
     * @return mixed
     */
    public function actionCopy($id)
    {
        // Source
        $form = $this->findFormModel($id);

        $transaction = Form::getDb()->beginTransaction();

        try {

            // Form
            $formModel = new Form();
            $formModel->attributes = $form->attributes;
            $formModel->id = null;
            $formModel->isNewRecord = true;
            $formModel->save();

            // Form Data
            $formDataModel = new FormData();
            $formDataModel->attributes = $form->formData->attributes;
            $formDataModel->id = null;
            $formDataModel->form_id = $formModel->id;
            $formDataModel->isNewRecord = true;
            $formDataModel->save();

            // Confirmation
            $formConfirmationModel = new FormConfirmation();
            $formConfirmationModel->attributes = $form->formConfirmation->attributes;
            $formConfirmationModel->id = null;
            $formConfirmationModel->form_id = $formModel->id;
            $formConfirmationModel->isNewRecord = true;
            $formConfirmationModel->save();

            // Notification
            $formEmailModel = new FormEmail();
            $formEmailModel->attributes = $form->formEmail->attributes;
            $formEmailModel->id = null;
            $formEmailModel->form_id = $formModel->id;
            $formEmailModel->isNewRecord = true;
            $formEmailModel->save();

            // UI
            $formUIModel = new FormUI();
            $formUIModel->attributes = $form->ui->attributes;
            $formUIModel->id = null;
            $formUIModel->form_id = $formModel->id;
            $formUIModel->isNewRecord = true;
            $formUIModel->save();

            // Conditional Rules
            foreach ($form->formRules as $rule) {
                $formRuleModel = new FormRule();
                $formRuleModel->attributes = $rule->attributes;
                $formRuleModel->id = null;
                $formRuleModel->form_id = $formModel->id;
                $formRuleModel->isNewRecord = true;
                $formRuleModel->save();
            }

            Yii::$app->getSession()->setFlash('success', Yii::t('form', 'The form has been successfully copied'));

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->getSession()->setFlash('danger', Yii::t('form', 'There was an error copying your form.'));
        }

        return $this->redirect(['index']);
    }

    /**
     * Deletes an existing Form model (and relations).
     * If the delete is successful, the browser will be redirected to the 'index' page.
     *
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        // Delete Form model
        $this->findFormModel($id)->delete();

        Yii::$app->getSession()->setFlash('success', Yii::t('form', 'The form has been successfully deleted'));

        return $this->redirect(['index']);
    }

    /**
     * Show form submissions.
     *
     * @param integer $id
     * @return mixed
     */
    public function actionSubmissions($id = null)
    {
        $formModel = $this->findFormModel($id);
        $formDataModel = $formModel->formData;

        return $this->render('submissions', [
            'formModel' => $formModel,
            'formDataModel' => $formDataModel
        ]);
    }

    /**
     * Export form submissions.
     *
     * @param int $id
     * @param string|null $start
     * @param string|null $end
     */
    public function actionExportSubmissions($id, $start = null, $end = null, $format = 'csv')
    {


        $formModel = $this->findFormModel($id);
        $formDataModel = $formModel->formData;

        $query = FormSubmission::find()
            ->select(['id', 'data', 'sender', 'created_at'])
            ->where('{{%form_submission}}.form_id=:form_id', [':form_id' => $id])
            ->orderBy('created_at DESC')
            ->with('files');

        if (!is_null($start) && !is_null($end)) {
            $startAt = strtotime(trim($start));
            // Add +1 day to the endAt
            $endAt = strtotime(trim($end)) + (24 * 60 * 60);
            $query->andFilterWhere(['between', '{{%form_submission}}.created_at', $startAt, $endAt]);
        }

        $query->asArray();

        // Insert fields names as the header
        $allLabels = $formDataModel->getFieldsForEmail();
        $labels = [];
        foreach ($allLabels as $key => $label) {
            // Exclude Signature Field
            if (substr($key, 0, 16) !== 'hidden_signature') {
                $labels[$key] = $label;
            }
        }
        $header = array_values($labels);

        // Add File Fields
        $fileFields = $formDataModel->getFileLabels();
        $header = array_merge($header, array_values($fileFields)); // Add only labels
        array_unshift($header, '#');
        array_push($header, Yii::t('form', 'Submitted'));
        $keys = array_keys($labels);

        // Add Sender Information
        $sender = array(
            Yii::t('form', 'Country'),
            Yii::t('form', 'City'),
            Yii::t('form', 'Latitude'),
            Yii::t('form', 'Longitude'),
            Yii::t('form', 'User Agent'),
        );
        $header = array_merge($header, $sender); // Add only labels

        // File Name To Export
        $fileNameToExport = !is_null($start) && !is_null($end) ? $formModel->name . '_' . $start . '_' . $end : $formModel->name;

        if ($format == 'xlsx') {
            // Create new Spreadsheet object
            $spreadsheet = new Spreadsheet();
            // Set document properties
            $spreadsheet->getProperties()->setCreator('Easy Forms')
                ->setLastModifiedBy('Easy Forms')
                ->setTitle($formModel->name)
                ->setSubject($formModel->name)
                ->setDescription('Spreadsheet document generated by Easy Forms.');
            // Add data
            $arrayData = array(
                $header
            );
            // To iterate the row one by one
            $i = 1;
            foreach ($query->each() as $submission) {
                // $submission represents one row of data from the form_submission table
                $data = json_decode($submission['data'], true);
                if (is_array($data) && !empty($data)) {
                    // Stringify fields with multiple values
                    foreach ($data as $name => &$field) {
                        if (is_array($field)) {
                            $field = implode(', ', $field);
                        }
                    }
                    // Only take data of current fields
                    $fields = [];
                    $fields["id"] = $i++;
                    foreach ($keys as $key) {
                        // Exclude Signature Field
                        if (substr($key, 0, 16) !== 'hidden_signature') {
                            $fields[$key] = isset($data[$key]) ? $data[$key] : '';
                        }
                    }
                    // Add files
                    $f = 0;
                    foreach ($fileFields as $name => $label) {
                        if (isset($submission['files'], $submission['files'][$f])) {
                            $file = $submission['files'][$f];
                            $fileName = $file['name'] . '.' . $file['extension'];
                            $fields[$name] = Url::base(true) . '/' . Form::FILES_DIRECTORY . '/' . $formModel->id . '/' . $fileName;
                        } else {
                            $fields[$name] = '';
                        }
                        $f++;
                    }

                    $fields["created_at"] = Yii::$app->formatter->asDatetime($submission['created_at']);
                    // $submission represents one row of data from the form_submission table
                    $sender = json_decode($submission['sender'], true);
                    // Stringify fields with multiple values
                    foreach ($sender as $key => $value) {
                        $fields[$key] = empty($value) ? '' : $value;
                    }
                    array_push($arrayData, $fields);
                }
            }

            $spreadsheet->getActiveSheet()
                ->fromArray(
                    $arrayData,     // The data to set
                    NULL,   // Array values with this value will not be set
                    'A1'     // Top left coordinate of the worksheet range where
                );

            // Redirect output to a client’s web browser (Xlsx)
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header(sprintf('Content-Disposition: attachment;filename="%s"', $fileNameToExport . '.xlsx'));
            header('Cache-Control: max-age=0');
            // If you're serving to IE 9, then the following may be needed
            header('Cache-Control: max-age=1');
            // If you're serving to IE over SSL, then the following may be needed
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
            header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
            header('Pragma: public'); // HTTP/1.0
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
            exit;
        } else {
            // Create the CSV into memory
            $csv = Writer::createFromFileObject(new SplTempFileObject());
            $csv->insertOne($header);

            // To iterate the row one by one
            $i = 1;
            foreach ($query->each() as $submission) {
                // $submission represents one row of data from the form_submission table
                $data = json_decode($submission['data'], true);
                if (is_array($data) && !empty($data)) {
                    // Stringify fields with multiple values
                    foreach ($data as $name => &$field) {
                        if (is_array($field)) {
                            $field = implode(', ', $field);
                        }
                    }
                    // Only take data of current fields
                    $fields = [];
                    $fields["id"] = $i++;
                    foreach ($keys as $key) {
                        // Exclude Signature Field
                        if (substr($key, 0, 16) !== 'hidden_signature') {
                            $fields[$key] = isset($data[$key]) ? $data[$key] : '';
                        }
                    }
                    // Add files
                    $f = 0;
                    foreach ($fileFields as $name => $label) {
                        if (isset($submission['files'], $submission['files'][$f])) {
                            $file = $submission['files'][$f];
                            $fileName = $file['name'] . '.' . $file['extension'];
                            $fields[$name] = Url::base(true) . '/' . Form::FILES_DIRECTORY . '/' . $formModel->id . '/' . $fileName;
                        } else {
                            $fields[$name] = '';
                        }
                        $f++;
                    }

                    $fields["created_at"] = Yii::$app->formatter->asDatetime($submission['created_at']);
                    // $submission represents one row of data from the form_submission table
                    $sender = json_decode($submission['sender'], true);
                    // Stringify fields with multiple values
                    foreach ($sender as $key => $value) {
                        $fields[$key] = empty($value) ? '' : $value;
                    }
                    $csv->insertOne($fields);
                }
            }

            $csv->output($fileNameToExport . '.csv');
            exit;
        }
    }

    /**
     * Download the Html Code.
     *
     * @param $id
     * @param int $js
     * @throws NotFoundHttpException
     */
    public function actionDownloadHtmlCode($id, $js = 1)
    {

        $this->layout = false;

        $formModel = $this->findFormModel($id);
        $formDataModel = $formModel->formData;
        $filename = !empty($formModel->slug) ? $formModel->slug . '.zip' : $formModel->id . '.zip';

        if (class_exists("ZipArchive")) {
            $zip = new \ZipArchive;
            $res = $zip->open($filename, \ZipArchive::CREATE);

            if ($res === TRUE) {

                // Brand
                $appName = Yii::$app->formsettings->get("app.name");
                $brandLabel = Html::tag("span", $appName, ["class" => "app-name"]);
                if ($logo = Yii::$app->formsettings->get("logo", "app", null)) {
                    $brandLabel = Html::img(Url::to('@web/' . $logo, true), [
                        'height' => '26px',
                        'alt' => $appName,
                        'title' => $appName,
                    ]);
                }

                $title = Html::a(
                    $brandLabel,
                    Url::home(true),
                    [
                        "title" => Yii::$app->formsettings->get("app.description"),
                        "style" => 'text-decoration:none',
                    ]
                );

                $form = Html::decode($formDataModel->html);

                // Add pagination
                $pager = new Pager($form);
                $form = $pager->getPaginatedData();

                // Add honeypot
                if ($formModel->honeypot === $formModel::HONEYPOT_ACTIVE) {
                    $honeypot = new Honeypot(Html::decode($form));
                    $form = $honeypot->getData();
                }

                // Add endpoint
                $endpoint = Url::to(['app/f', 'id' => $formModel->id], true);
                $form = str_ireplace(
                    "<form id=\"form-app\"",
                    "<form action=\"{$endpoint}\" method=\"post\" enctype=\"multipart/form-data\" accept-charset=\"UTF-8\" id=\"form-app\"",
                    $form
                );

                // Javascript code
                $scripts = '';
                $options = '';
                if ($js) {
                    /** @var $rules array Conditions and Actions of active rules */
                    $rules = [];
                    foreach ($formModel->formRules as $formRuleModel) {
                        $rule = [
                            'conditions' => $formRuleModel['conditions'],
                            'actions' => $formRuleModel['actions'],
                            'opposite' => (boolean)$formRuleModel['opposite'],
                        ];
                        array_push($rules, $rule);
                    }
                    // jQuery
                    $zip->addFile(Yii::getAlias('@app/static_files/js/libs/jquery.js'), 'js/libs/jquery.js');
                    $scripts .= '<script src="js/libs/jquery.js"></script>';
                    // Signature Pad
                    $zip->addFile(Yii::getAlias('@app/static_files/js/libs/signature_pad.umd.js'), 'js/libs/signature_pad.umd.js');
                    $scripts .= '<script src="js/libs/signature_pad.umd.js"></script>';
                    if ($formModel->recaptcha === Form::RECAPTCHA_ACTIVE) {
                        $scripts .= '<script src="https://www.google.com/recaptcha/api.js"></script>';
                    }
                    // jQuery Form
                    $zip->addFile(Yii::getAlias('@app/static_files/js/libs/jquery.form.js'), 'js/libs/jquery.form.js');
                    $scripts .= '<script src="js/libs/jquery.form.js"></script>';
                    // jQuery Easing
                    if ($pager->getNumberOfPages() > 1) {
                        $zip->addFile(Yii::getAlias('@app/static_files/js/libs/jquery.easing.min.js'), 'js/libs/jquery.easing.min.js');
                        $scripts .= '<script src="js/libs/jquery.easing.min.js"></script>';
                    }
                    // Form Utilities
                    $zip->addFile(Yii::getAlias('@app/static_files/js/form.utils.min.js'), 'js/form.utils.min.js');
                    $scripts .= '<script src="js/form.utils.min.js"></script>';
                    // Form Resume
                    if ($formModel->resume) {
                        $zip->addFile(Yii::getAlias('@app/static_files/js/form.resume.min.js'), 'js/form.resume.min.js');
                        $scripts .= '<script src="js/form.resume.min.js"></script>';
                    }
                    if (count($rules) > 0) {
                        // Numeral.js
                        $zip->addFile(Yii::getAlias('@app/static_files/js/libs/numeral.min.js'), 'js/libs/numeral.min.js');
                        $scripts .= '<script src="js/libs/numeral.min.js"></script>';
                        $zip->addFile(Yii::getAlias('@app/static_files/js/libs/locales/numeral.min.js'), 'js/libs/locales/numeral.min.js');
                        $scripts .= '<script src="js/libs/locales/numeral.min.js"></script>';
                        // Rules Engine
                        $zip->addFile(Yii::getAlias('@app/static_files/js/rules.engine.min.js'), 'js/rules.engine.min.js');
                        $scripts .= '<script src="js/rules.engine.min.js"></script>';
                        $zip->addFile(Yii::getAlias('@app/static_files/js/rules.engine.run.min.js'), 'js/rules.engine.run.min.js');
                        $scripts .= '<script src="js/rules.engine.run.min.js"></script>';
                    }
                    // Form Tracker
                    $zip->addFile(Yii::getAlias('@app/static_files/js/form.tracker.js'), 'js/form.tracker.js');
                    // Form
                    $zip->addFile(Yii::getAlias('@app/static_files/js/form.embed.min.js'), 'js/form.embed.min.js');
                    $scripts .= '<script src="js/form.embed.min.js"></script>';
                    // Add custom js file after all
                    if (!empty($formModel->ui->js_file)) {
                        $scripts .= '<script src="' . $formModel->ui->js_file . '"></script>';
                    }

                    // PHP options required by embed.js
                    $options = array(
                        "id" => $formModel->id,
                        "app" => UrlHelper::removeScheme(Url::to(['/app'], true)),
                        "tracker" => "js/form.tracker.js",
                        "name" => "#form-app",
                        "actionUrl" => Url::to(['app/f', 'id' => $formModel->id], true),
                        "validationUrl" => Url::to(['app/check', 'id' => $formModel->id], true),
                        "_csrf" => Yii::$app->request->getCsrfToken(),
                        "resume" => $formModel->resume,
                        "autocomplete" => $formModel->autocomplete,
                        "novalidate" => $formModel->novalidate,
                        "analytics" => $formModel->analytics,
                        "confirmationType" => $formModel->formConfirmation->type,
                        "confirmationMessage" => false,
                        "confirmationUrl" => $formModel->formConfirmation->url,
                        "showOnlyMessage" => FormConfirmation::CONFIRM_WITH_ONLY_MESSAGE,
                        "redirectToUrl" => FormConfirmation::CONFIRM_WITH_REDIRECTION,
                        "rules" => $rules,
                        "fieldIds" => $formDataModel->getFieldIds(),
                        "submitted" => false,
                        "runOppositeActions" => true,
                        "skips" => [],
                        "defaultValues" => false,
                        "i18n" => [
                            'complete' => Yii::t('form', 'Complete'),
                            'unexpectedError' => Yii::t('form', 'An unexpected error has occurred. Please retry later.'),
                        ]
                    );
                    // Pass php options to javascript
                    $options = "var options = " . json_encode($options) . ";";
                }

                // Add CSS Theme
                $css = '';
                if (!empty($formModel->theme->css)) {
                    $css .= <<<CSS
{$formModel->theme->css}
CSS;
                }

                $htmlCode = <<<HTML
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>{$formModel->name}</title>
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/public.min.css">
  <style>
    body { background-color: #EFF3F6; padding: 20px; }
    .legend { margin-top: 0 }
    .g-recaptcha { min-height: 78px; }
    {$css}
  </style>
  <script>
  {$options}
  </script>
</head>
<body>
<div class="container">
    <div class="row">
    <div class="col-sm-8 col-sm-offset-2 col-md-6 col-md-offset-3">
            <div class="form-view">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            {$title}
                        </h3>
                    </div>
                    <div class="panel-body" style="padding: 20px">
                        <div id="messages"></div>
                        {$form}
                        <div id="progress" class="progress" style="display: none;">
                            <div id="bar" class="progress-bar" role="progressbar" style="width: 0;">
                                <span id="percent" class="sr-only">0% Complete</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
{$scripts}
</body>
</html>
HTML;

                $zip->addFromString('index.html', $htmlCode);
                $zip->addFile(Yii::getAlias('@app/static_files/css/bootstrap.min.css'), 'css/bootstrap.min.css');
                $zip->addFile(Yii::getAlias('@app/static_files/css/public.min.css'), 'css/public.min.css');
                $zip->close();

            } else {
                throw new NotFoundHttpException("Can't open {$filename}");
            }

            return Yii::$app->response->sendFile($filename)->on(Response::EVENT_AFTER_SEND, function ($event) {
                unlink($event->data);
            }, $filename);

        }

        throw new NotFoundHttpException("Can't create {$filename}");
    }

    /**
     * Show form submissions report.
     *
     * @param integer $id
     * @return mixed
     */
    public function actionReport($id = null)
    {
        $formModel = $this->findFormModel($id);
        $formDataModel = $formModel->formData;
        $charts = $formModel->getFormCharts()->asArray()->all();

        return $this->render('report', [
            'formModel' => $formModel,
            'formDataModel' => $formDataModel,
            'charts' => $charts
        ]);
    }

    /**
     * Finds the Form model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * If the user does not have access, a Forbidden Http Exception will be thrown.
     *
     * @param $id
     * @return Form
     * @throws NotFoundHttpException
     */
    protected function findFormModel($id)
    {
        if (($model = Form::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException(Yii::t("app", "The requested page does not exist."));
        }
    }

    /**
     * Disable Assets
     */
    private function disableAssets()
    {
        Yii::$app->assetManager->bundles['app\modules\easyforms\bundles\AppBundle'] = false;
        Yii::$app->assetManager->bundles['yii\web\JqueryAsset'] = false;
        Yii::$app->assetManager->bundles['yii\bootstrap\BootstrapPluginAsset'] = false;
        Yii::$app->assetManager->bundles['yii\web\YiiAsset'] = false;
    }
}

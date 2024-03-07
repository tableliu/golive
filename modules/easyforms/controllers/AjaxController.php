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


use app\components\Settings;
use app\modules\easyforms\models\forms\FormBuilder;
use Exception;
use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;
use yii\base\InvalidParamException;
use yii\helpers\Html;
use yii\helpers\Json;
use app\modules\easyforms\helpers\FormDOM;
use app\modules\easyforms\helpers\ArrayHelper;
use app\modules\easyforms\models\Form;
use app\modules\easyforms\models\FormData;
use app\modules\easyforms\models\FormUI;
use app\modules\easyforms\models\FormSubmission;
use app\modules\easyforms\models\FormConfirmation;
use app\modules\easyforms\models\FormEmail;
use app\modules\easyforms\models\FormChart;
use app\modules\easyforms\models\Template;
use app\components\analytics\Analytics;
use app\modules\easyforms\events\FormEvent;

/**
 * Class AjaxController
 * @package app\controllers
 */
class AjaxController extends Controller
{

    /**
     * @event FormEvent an event fired when a form is updated.
     */
    const EVENT_FORM_UPDATED = 'app.form.updated';

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        // This actions can be performed by any user
                        'actions' => ['grid-view-page-size', 'submission-manager-settings'],
                        'allow' => true,
//                        'matchCallback' => function () {
//                            // Check for user permission
//                            if (!empty(Yii::$app->user) && !Yii::$app->user->isGuest) {
//                                return true;
//                            }
//                            return false;
//                        }
                    ],
                    [
                        // This actions can be performed by any user with form access
                        'actions' => ['report', 'analytics', 'stats'],
                        'allow' => true,
//                        'matchCallback' => function () {
//                            // Check for user permission
//                            if (!empty(Yii::$app->user) && !Yii::$app->user->isGuest) {
//                                // Form ID
//                                $id = Yii::$app->request->getQueryParam('id');
//                                return Yii::$app->user->canAccessToForm($id);
//                            }
//                            return false;
//                        }
                    ],
                    [
                        // This actions can be performed by advanced users
                        'actions' => ['create-form', 'create-template', 'builder-phrases', 'builder-components'],
                        'allow' => true,
//                        'matchCallback' => function () {
//                            // Check for user permission
//                            if (!empty(Yii::$app->user) && Yii::$app->user->can("edit_own_content")) {
//                                return true;
//                            }
//                            return false;
//                        }
                    ],
                    [
                        // This actions can be performed by advanced users (if for update: Check form access)
                        'actions' => ['init-form'],
                        'allow' => true,
//                        'matchCallback' => function () {
//                            // Check for user permission
//                            if (!empty(Yii::$app->user)) {
//                                if (Yii::$app->user->can("admin")) {
//                                    return true;
//                                } elseif (Yii::$app->user->can("edit_own_content")) {
//                                    if ($id = Yii::$app->request->getQueryParam('id')) {
//                                        if (is_null($id)) {
//                                            return true;
//                                        }
//                                        // Template ID
//                                        return Yii::$app->user->canAccessToTemplate($id);
//                                    }
//                                    return true;
//                                }
//                            }
//                            return false;
//                        }
                    ],
                    [
                        // This actions can be performed by advanced users (if for update: Check form access)
                        'actions' => ['init-template', 'update-template'],
                        'allow' => true,
//                        'matchCallback' => function () {
//                            // Check for user permission
//                            if (!empty(Yii::$app->user)) {
//                                if (Yii::$app->user->can("admin")) {
//                                    return true;
//                                } elseif (Yii::$app->user->can("edit_own_content")) {
//                                    if ($id = Yii::$app->request->getQueryParam('id')) {
//                                        // Template ID
//                                        return Yii::$app->user->canAccessToTemplate($id);
//                                    }
//                                    return true;
//                                }
//                            }
//                            return false;
//                        }
                    ],
                    [
                        // The rest of actions can be performed by a advanced users with form access
                        'allow' => true,
//                        'matchCallback' => function () {
//                            if (!empty(Yii::$app->user)) {
//                                if (Yii::$app->user->can("admin")) {
//                                    return true;
//                                } elseif (Yii::$app->user->can("edit_own_content")) {
//                                    // Form ID
//                                    $id = Yii::$app->request->getQueryParam('id');
//                                    return Yii::$app->user->canAccessToForm($id);
//                                }
//                            }
//                            return false;
//                        }
                    ],
                ],
            ],
        ];
    }

    /**********************************************
     * /* Builder i18n
     * /**********************************************/

    /**
     * @return string
     */
    public function actionBuilderPhrases()
    {
        if (Yii::$app->request->isAjax) {

            Yii::$app->response->format = Response::FORMAT_JSON;

            $i18n = [
                "phrases" => [
                    "form.name" => Yii::t('form', 'Form Name'),
                    "form.description" => Yii::t('form', 'Used for identify the form on administration pages.'),
                    "form.layout" => Yii::t('form', 'Form Layout'),
                    "form.disabled" => Yii::t('form', 'Disable form elements'),
                    "form.sourceCode" => Yii::t('form', 'Source Code preview'),
                    "formSteps.title" => Yii::t('form', 'Form Steps'),
                    "formSteps.id" => Yii::t('form', 'ID / Name'),
                    "formSteps.steps" => Yii::t('form', 'Steps'),
                    "formSteps.progressBar" => Yii::t('form', 'Progress Bar'),
                    "formSteps.noTitles" => Yii::t('form', 'No Titles'),
                    "formSteps.noStages" => Yii::t('form', 'No Stages'),
                    "formSteps.noSteps" => Yii::t('form', 'No Form Steps'),
                    "popover.save" => Yii::t('form', 'Save'),
                    "popover.copy" => Yii::t('form', 'Copy'),
                    "popover.delete" => Yii::t('form', 'Delete'),
                    "popover.cancel" => Yii::t('form', 'Cancel'),
                    "popover.more" => Yii::t('form', 'More'),
                    "tab.fields" => Yii::t('form', 'Fields'),
                    "tab.settings" => Yii::t('form', 'Settings'),
                    "tab.code" => Yii::t('form', 'Code'),
                    "alert.warning" => Yii::t('form', 'Warning!'),
                    "alert.errorSavingData" => Yii::t('form', 'There was a problem saving data. Please retry later'),
                    "alert.unsavedChanges" => Yii::t(
                        'app',
                        'YOU HAVE UNSAVED CHANGES! ALL CHANGES IN THE FORM WILL BE LOST!'
                    ),
                    "alert.confirmToDeleteField" => Yii::t(
                        'app',
                        "Are you sure you want to delete this field? If you do, any data associated with this field will be deleted too. If this form has at least one form submission, you should export your data first."
                    ),
                    "widget.button" => Yii::t('form', 'Submit'),
                    "widget.camera" => Yii::t('form', 'Camera'),
                    "widget.checkbox" => Yii::t('form', 'Checkboxes'),
                    "widget.date" => Yii::t('form', 'Date Field'),
                    "widget.email" => Yii::t('form', 'Email Field'),
                    "widget.file" => Yii::t('form', 'File Upload'),
                    "widget.heading" => Yii::t('form', 'Heading'),
                    "widget.hidden" => Yii::t('form', 'Hidden Field'),
                    "widget.number" => Yii::t('form', 'Number Field'),
                    "widget.pageBreak" => Yii::t('form', 'Page Break'),
                    "widget.paragraph" => Yii::t('form', 'Paragraph'),
                    "widget.radio" => Yii::t('form', 'Radio Buttons'),
                    "widget.recaptcha" => Yii::t('form', 'reCaptcha'),
                    "widget.selectList" => Yii::t('form', 'Select List'),
                    "widget.snippet" => Yii::t('form', 'Snippet'),
                    "widget.text" => Yii::t('form', 'Text Field'),
                    "widget.textArea" => Yii::t('form', 'Text Area'),
                    "widget.spacer" => Yii::t('form', 'Spacer'),
                    "widget.signature" => Yii::t('form', 'Signature'),
                    "heading.title" => Yii::t('form', 'Heading'),
                    "paragraph.title" => Yii::t('form', 'Paragraph'),
                    "text.title" => Yii::t('form', 'Text'),
                    "number.title" => Yii::t('form', 'Number'),
                    "date.title" => Yii::t('form', 'Date'),
                    "email.title" => Yii::t('form', 'Email'),
                    "textarea.title" => Yii::t('form', 'Text Area'),
                    "signature.title" => Yii::t('form', 'Signature'),
                    "checkbox.title" => Yii::t('form', 'Checkbox'),
                    "radio.title" => Yii::t('form', 'Radio'),
                    "selectlist.title" => Yii::t('form', 'Select List'),
                    "hidden.title" => Yii::t('form', 'Hidden'),
                    "file.title" => Yii::t('form', 'File'),
                    "snippet.title" => Yii::t('form', 'Snippet'),
                    "recaptcha.title" => Yii::t('form', 'reCAPTCHA'),
                    "pagebreak.title" => Yii::t('form', 'Page Break'),
                    "spacer.title" => Yii::t('form', 'Spacer'),
                    "button.title" => Yii::t('form', 'Button'),
                    "component.id" => Yii::t('form', 'ID / Name'),
                    "component.text" => Yii::t('form', 'Text'),
                    "component.inputType" => Yii::t('form', 'Input Type'),
                    "component.type" => Yii::t('form', 'Type'),
                    "component.size" => Yii::t('form', 'Size'),
                    "component.label" => Yii::t('form', 'Label'),
                    "component.placeholder" => Yii::t('form', 'Placeholder'),
                    "component.required" => Yii::t('form', 'Required'),
                    "component.predefinedValue" => Yii::t('form', 'Predefined Value'),
                    "component.helpText" => Yii::t('form', 'Help Text'),
                    "component.fieldSize" => Yii::t('form', 'Field Size'),
                    "component.groupName" => Yii::t('form', 'Group Name'),
                    "component.checkboxes" => Yii::t('form', 'Checkboxes'),
                    "component.radios" => Yii::t('form', 'Radios'),
                    "component.options" => Yii::t('form', 'Options'),
                    "component.accept" => Yii::t('form', 'Accept'),
                    "component.pattern" => Yii::t('form', 'Pattern'),
                    "component.integerPattern" => Yii::t('app', 'Integer Pattern'),
                    "component.numberPattern" => Yii::t('app', 'Number Pattern'),
                    "component.prev" => Yii::t('app', 'Text of Previous Button'),
                    "component.next" => Yii::t('app', 'Text of Next Button'),
                    "component.buttonText" => Yii::t('app', 'Button Text'),
                    "component.src" => Yii::t('app', 'Image Source'),
                    "component.inline" => Yii::t('app', 'Inline'),
                    "component.unique" => Yii::t('app', 'Unique'),
                    "component.readOnly" => Yii::t('app', 'Read Only'),
                    "component.integerOnly" => Yii::t('app', 'Integer Only'),
                    "component.minNumber" => Yii::t('app', 'Min number'),
                    "component.maxNumber" => Yii::t('app', 'Max number'),
                    "component.stepNumber" => Yii::t('app', 'Step number'),
                    "component.minDate" => Yii::t('app', 'Min date'),
                    "component.maxDate" => Yii::t('app', 'Max date'),
                    "component.minSize" => Yii::t('app', 'Min Size'),
                    "component.maxSize" => Yii::t('app', 'Max Size'),
                    "component.htmlCode" => Yii::t('app', 'HTML Code'),
                    "component.theme" => Yii::t('app', 'Theme'),
                    "component.checkDNS" => Yii::t('app', 'Check DNS'),
                    "component.multiple" => Yii::t('app', 'Multiple'),
                    "component.disabled" => Yii::t('app', 'Disabled'),
                    "component.cssClass" => Yii::t('app', 'CSS Class'),
                    "component.labelClass" => Yii::t('app', 'Label CSS Class'),
                    "component.containerClass" => Yii::t('app', 'Container CSS Class'),
                    "component.alias" => Yii::t('app', 'Alias'),
                    "component.clear" => Yii::t('app', 'Clear'),
                    "component.clearText" => Yii::t('app', 'Clear Button Text'),
                    "component.undo" => Yii::t('app', 'Undo'),
                    "component.undoText" => Yii::t('app', 'Undo Button Text'),
                    "component.color" => Yii::t('app', 'Color'),
                    "component.width" => Yii::t('app', 'Width'),
                    "component.height" => Yii::t('app', 'Height'),
                    "style.global" => Yii::t('app', 'Global'),
                    "style.background" => Yii::t('app', 'Background'),
                    "style.text" => Yii::t('app', 'Text'),
                    "style.spacing" => Yii::t('app', 'Spacing'),
                    "style.form" => Yii::t('app', 'Form'),
                    "style.border" => Yii::t('app', 'Border'),
                    "style.extra" => Yii::t('app', 'Extra'),
                    "style.formGroup" => Yii::t('app', 'Form Group'),
                    "style.formControl" => Yii::t('app', 'Form Control'),
                    "style.size" => Yii::t('app', 'Size'),
                    "style.focusEffect" => Yii::t('app', 'Focus Effect'),
                    "style.button" => Yii::t('app', 'Button'),
                    "style.hoverEffect" => Yii::t('app', 'Hover Effect'),
                    "style.label" => Yii::t('app', 'Label'),
                    "style.placeholder" => Yii::t('app', 'Placeholder'),
                    "style.heading" => Yii::t('app', 'Heading'),
                    "style.paragraph" => Yii::t('app', 'Paragraph'),
                    "style.helpText" => Yii::t('app', 'Help Text'),
                    "style.link" => Yii::t('app', 'Link'),
                    "style.formSteps" => Yii::t('app', 'Form Steps'),
                    "style.others" => Yii::t('app', 'Others'),
                    "style.currentStep" => Yii::t('app', 'Current Step'),
                    "style.succeedStep" => Yii::t('app', 'Succeed Step'),
                    "style.previousButton" => Yii::t('app', '"Previous" Button'),
                    "style.previousButtonHoverEffect" => Yii::t('app', '"Previous" Button Hover Effect'),
                    "style.nextButton" => Yii::t('app', '"Next" Button'),
                    "style.nextButtonHoverEffect" => Yii::t('app', '"Next" Button Hover Effect'),
                    "style.formAlerts" => Yii::t('app', 'Form Alerts'),
                    "style.successMessage" => Yii::t('app', 'Success Message'),
                    "style.errorMessage" => Yii::t('app', 'Error Message'),
                    "style.fieldValidation" => Yii::t('app', 'Field Validation'),
                    "style.asteriskSymbol" => Yii::t('app', 'Asterisk Symbol'),
                    "style.textMessage" => Yii::t('app', 'Text Message'),
                    "style.field" => Yii::t('app', 'Field'),
                    "style.otherComponents" => Yii::t('app', 'Other Components'),
                    "style.signaturePad" => Yii::t('app', 'Signature Pad'),
                    "style.checkbox" => Yii::t('app', 'Checkbox'),
                    "style.input" => Yii::t('app', 'Input'),
                    "style.customControl" => Yii::t('app', 'Custom Control'),
                    "style.checked" => Yii::t('app', 'Checked'),
                    "style.mark" => Yii::t('app', 'Mark'),
                    "style.radioButton" => Yii::t('app', 'Radio Button'),
                    "style.gradients" => Yii::t('app', 'Gradients'),
                    "style.patterns" => Yii::t('app', 'Patterns'),
                    "style.none" => Yii::t('app', 'None'),
                    "style.type" => Yii::t('app', 'Type'),
                    "style.radial" => Yii::t('app', 'Radial'),
                    "style.linear" => Yii::t('app', 'Linear'),
                    "style.repeatingRadial" => Yii::t('app', 'Repeating Radial'),
                    "style.repeatingLinear" => Yii::t('app', 'Repeating Linear'),
                    "style.direction" => Yii::t('app', 'Direction'),
                    "style.top" => Yii::t('app', 'Top'),
                    "style.right" => Yii::t('app', 'Right'),
                    "style.center" => Yii::t('app', 'Center'),
                    "style.bottom" => Yii::t('app', 'Bottom'),
                    "style.left" => Yii::t('app', 'Left'),
                    "style.backgroundColor" => Yii::t('app', 'Background Color'),
                    "style.selectColor" => Yii::t('app', 'Select color...'),
                    "style.color" => Yii::t('app', 'Color'),
                    "style.backgroundImageGradient" => Yii::t('app', 'Background Image / Gradient'),
                    "style.bgSize" => Yii::t('app', 'BG Size'),
                    "style.bgRepeat" => Yii::t('app', 'BG Repeat'),
                    "style.backgroundPosition" => Yii::t('app', 'Background Position'),
                    "style.borderStyle" => Yii::t('app', 'Border Style'),
                    "style.borderWidth" => Yii::t('app', 'Border Width'),
                    "style.borderColor" => Yii::t('app', 'Border Color'),
                    "style.borderRadius" => Yii::t('app', 'Border Radius'),
                    "style.boxShadow" => Yii::t('app', 'Box Shadow'),
                    "style.textShadow" => Yii::t('app', 'Text Shadow'),
                    "style.basic" => Yii::t('app', 'Basic'),
                    "style.hard" => Yii::t('app', 'Hard'),
                    "style.double" => Yii::t('app', 'Double'),
                    "style.downAndDistant" => Yii::t('app', 'Down and Distant'),
                    "style.closeAndHeavy" => Yii::t('app', 'Close and Heavy'),
                    "style.glowing" => Yii::t('app', 'Glowing'),
                    "style.superhero" => Yii::t('app', 'Superhero'),
                    "style.multipleLightSources" => Yii::t('app', 'Multiple Light Sources'),
                    "style.softEmboss" => Yii::t('app', 'Soft Emboss'),
                    "style.margin" => Yii::t('app', 'Margin'),
                    "style.padding" => Yii::t('app', 'Padding'),
                    "style.width" => Yii::t('app', 'Width'),
                    "style.height" => Yii::t('app', 'Height'),
                    "style.float" => Yii::t('app', 'Float'),
                    "style.textAlignment" => Yii::t('app', 'Text Alignment'),
                    "style.fontFamily" => Yii::t('app', 'Font Family'),
                    "style.selectFont" => Yii::t('app', 'Select font...'),
                    "style.fontSize" => Yii::t('app', 'Font Size'),
                    "style.fontWeight" => Yii::t('app', 'Font Weight'),
                    "style.textTransform" => Yii::t('app', 'Text Transform'),
                    "style.textDecoration" => Yii::t('app', 'Text Decoration'),
                    "style.lineHeight" => Yii::t('app', 'Line Height'),
                    "style.letterSpacing" => Yii::t('app', 'Letter Spacing'),
                    "style.transition" => Yii::t('app', 'Transition'),
                    "style.display" => Yii::t('app', 'Display'),
                    "style.choose" => Yii::t('app', 'Choose'),
                    "style.cancel" => Yii::t('app', 'Cancel'),
                    "style.progressBar" => Yii::t('app', 'Progress Bar'),
                    "style.progressBarContainer" => Yii::t('app', 'Progress Bar Container'),
                ]
            ];

            return $i18n;
        }

        return '';
    }

    /**
     * @return string
     */
    public function actionBuilderComponents()
    {
        if (Yii::$app->request->isAjax) {

            Yii::$app->response->format = Response::FORMAT_JSON;

            $i18n = [
                'paragraphText' => Yii::t('app', 'You can edit this paragraph by clicking here.'),
                'numberField' => Yii::t('app', 'Number Field'),
                'dateField' => Yii::t('app', 'Date Field'),
                'emailField' => Yii::t('app', 'Email Field'),
                'textArea' => Yii::t('app', 'Text Area'),
                'checkAllThatApply' => Yii::t('app', 'Check All That Apply'),
                'selectAChoice' => Yii::t('app', 'Select a Choice'),
                'signature' => Yii::t('app', 'Signature'),
                'clear' => Yii::t('app', 'Clear'),
                'undo' => Yii::t('app', 'Undo'),
                'color' => Yii::t('app', 'Color'),
                'attachAFile' => Yii::t('app', 'Attach a File'),
                'replaceThisCode' => Yii::t('app', 'Replace this {startTag}code{endTag} with your html snippet.', [
                    'startTag' => '<code>',
                    'endTag' => '</code>',
                ]),
                'buttonText' => Yii::t('app', 'Submit'),
            ];

            $json = <<<EOD
[
    {
        "name": "heading",
        "title": "heading.title",
        "fields": {
            "id": {
                "label": "component.id",
                "type": "input",
                "value": "heading"
            },
            "text": {
                "label": "component.text",
                "type": "input",
                "value": "Heading"
            },
            "type": {
                "label": "component.type",
                "type": "select",
                "value": [
                    {
                        "value": "h1",
                        "label": "H1",
                        "selected": false
                    },
                    {
                        "value": "h2",
                        "label": "H2",
                        "selected": false
                    },
                    {
                        "value": "h3",
                        "label": "H3",
                        "selected": true
                    },
                    {
                        "value": "h4",
                        "label": "H4",
                        "selected": false
                    },
                    {
                        "value": "h5",
                        "label": "H5",
                        "selected": false
                    },
                    {
                        "value": "h6",
                        "label": "H6",
                        "selected": false
                    }
                ]
            },
            "cssClass": {
                "label": "component.cssClass",
                "type": "input",
                "value": "legend",
                "advanced": true
            },
            "containerClass": {
                "label": "component.containerClass",
                "type": "input",
                "value": "col-xs-12",
                "advanced": true
            }
        }
    },
    {
        "name": "paragraph",
        "title": "paragraph.title89",
        "fields": {
            "id": {
                "label": "component.id",
                "type": "input",
                "value": "paragraph"
            },
            "text": {
                "label": "component.text",
                "type": "textarea",
                "value": "{$i18n['paragraphText']}"
            },
            "cssClass": {
                "label": "component.cssClass",
                "type": "input",
                "value": "",
                "advanced": true
            },
            "containerClass": {
                "label": "component.containerClass",
                "type": "input",
                "value": "col-xs-12",
                "advanced": true
            }
        }
    },
    {
        "name": "text",
        "title": "text.title",
        "fields": {
            "id": {
                "label": "component.id",
                "type": "input",
                "value": "text"
            },
            "inputType": {
                "label": "component.inputType",
                "type": "select",
                "value": [
                    {
                        "value": "text",
                        "label": "Text",
                        "selected": true
                    },
                    {
                        "value": "tel",
                        "label": "Tel",
                        "selected": false
                    },
                    {
                        "value": "url",
                        "label": "URL",
                        "selected": false
                    },
                    {
                        "value": "color",
                        "label": "Color",
                        "selected": false
                    },
                    {
                        "value": "password",
                        "label": "Password",
                        "selected": false
                    }
                ]
            },
            "label": {
                "label": "component.label",
                "type": "input",
                "value": "Text Field"
            },
            "placeholder": {
                "label": "component.placeholder",
                "type": "input",
                "value": ""
            },
            "required": {
                "label": "component.required",
                "type": "checkbox",
                "value": false
            },
            "predefinedValue": {
                "label": "component.predefinedValue",
                "type": "input",
                "value": "",
                "advanced": true
            },
            "helpText": {
                "label": "component.helpText",
                "type": "textarea",
                "value": "",
                "advanced": true
            },
            "pattern": {
                "label": "component.pattern",
                "type": "input",
                "value": "",
                "advanced": true
            },
            "cssClass": {
                "label": "component.cssClass",
                "type": "input",
                "value": "form-control",
                "advanced": true
            },
            "labelClass": {
                "label": "component.labelClass",
                "type": "input",
                "value": "control-label",
                "advanced": true
            },
            "containerClass": {
                "label": "component.containerClass",
                "type": "input",
                "value": "col-xs-12",
                "advanced": true
            },
             "alias": {
                "label": "component.alias",
                "type": "input",
                "value": "",
                "advanced": true
            },
            "unique": {
                "label": "component.unique",
                "type": "checkbox",
                "value": false,
                "advanced": true
            },
            "readOnly": {
                "label": "component.readOnly",
                "type": "checkbox",
                "value": false,
                "advanced": true
            },
            "disabled": {
                "label": "component.disabled",
                "type": "checkbox",
                "value": false,
                "advanced": true
            }
        }
    },
    {
        "name": "number",
        "title": "number.title",
        "fields": {
            "id": {
                "label": "component.id",
                "type": "input",
                "value": "number"
            },
            "inputType": {
                "label": "component.inputType",
                "type": "select",
                "value": [
                    {
                        "value": "number",
                        "label": "Number",
                        "selected": true
                    },
                    {
                        "value": "range",
                        "label": "Range",
                        "selected": false
                    }
                ]
            },
            "label": {
                "label": "component.label",
                "type": "input",
                "value": "{$i18n['numberField']}"
            },
            "placeholder": {
                "label": "component.placeholder",
                "type": "input",
                "value": ""
            },
            "integerOnly": {
                "label": "component.integerOnly",
                "type": "checkbox",
                "value": false
            },
            "required": {
                "label": "component.required",
                "type": "checkbox",
                "value": false
            },
            "predefinedValue": {
                "label": "component.predefinedValue",
                "type": "input",
                "value": "",
                "advanced": true
            },
            "helpText": {
                "label": "component.helpText",
                "type": "textarea",
                "value": "",
                "advanced": true
            },
            "min": {
                "label": "component.minNumber",
                "type": "input",
                "value": "",
                "advanced": true
            },
            "max": {
                "label": "component.maxNumber",
                "type": "input",
                "value": "",
                "advanced": true
            },
            "step": {
                "label": "component.stepNumber",
                "type": "input",
                "value": "",
                "advanced": true
            },
            "integerPattern": {
                "label": "component.integerPattern",
                "type": "input",
                "value": "",
                "advanced": true
            },
            "numberPattern": {
                "label": "component.numberPattern",
                "type": "input",
                "value": "",
                "advanced": true
            },
            "cssClass": {
                "label": "component.cssClass",
                "type": "input",
                "value": "form-control",
                "advanced": true
            },
            "labelClass": {
                "label": "component.labelClass",
                "type": "input",
                "value": "control-label",
                "advanced": true
            },
            "containerClass": {
                "label": "component.containerClass",
                "type": "input",
                "value": "col-xs-12",
                "advanced": true
            },
             "alias": {
                "label": "component.alias",
                "type": "input",
                "value": "",
                "advanced": true
            },
            "unique": {
                "label": "component.unique",
                "type": "checkbox",
                "value": false,
                "advanced": true
            },
            "readOnly": {
                "label": "component.readOnly",
                "type": "checkbox",
                "value": false,
                "advanced": true
            },
            "disabled": {
                "label": "component.disabled",
                "type": "checkbox",
                "value": false,
                "advanced": true
            }
        }
    },
    {
        "name": "date",
        "title": "date.title",
        "fields": {
            "id": {
                "label": "component.id",
                "type": "input",
                "value": "date"
            },
            "inputType": {
                "label": "component.inputType",
                "type": "select",
                "value": [
                    {
                        "value": "date",
                        "label": "Date",
                        "selected": true
                    },
                    {
                        "value": "datetime-local",
                        "label": "DateTime-Local",
                        "selected": false
                    },
                    {
                        "value": "time",
                        "label": "Time",
                        "selected": false
                    },
                    {
                        "value": "month",
                        "label": "Month",
                        "selected": false
                    },
                    {
                        "value": "week",
                        "label": "Week",
                        "selected": false
                    }
                ]
            },
            "label": {
                "label": "component.label",
                "type": "input",
                "value": "{$i18n['dateField']}"
            },
            "placeholder": {
                "label": "component.placeholder",
                "type": "input",
                "value": ""
            },
            "required": {
                "label": "component.required",
                "type": "checkbox",
                "value": false
            },
            "predefinedValue": {
                "label": "component.predefinedValue",
                "type": "input",
                "value": "",
                "advanced": true
            },
            "helpText": {
                "label": "component.helpText",
                "type": "textarea",
                "value": "",
                "advanced": true
            },
            "min": {
                "label": "component.minDate",
                "type": "input",
                "value": "",
                "advanced": true
            },
            "max": {
                "label": "component.maxDate",
                "type": "input",
                "value": "",
                "advanced": true
            },
            "step": {
                "label": "component.stepNumber",
                "type": "input",
                "value": "",
                "advanced": true
            },
            "cssClass": {
                "label": "component.cssClass",
                "type": "input",
                "value": "form-control",
                "advanced": true
            },
            "labelClass": {
                "label": "component.labelClass",
                "type": "input",
                "value": "control-label",
                "advanced": true
            },
            "containerClass": {
                "label": "component.containerClass",
                "type": "input",
                "value": "col-xs-12",
                "advanced": true
            },
             "alias": {
                "label": "component.alias",
                "type": "input",
                "value": "",
                "advanced": true
            },
            "unique": {
                "label": "component.unique",
                "type": "checkbox",
                "value": false,
                "advanced": true
            },
            "readOnly": {
                "label": "component.readOnly",
                "type": "checkbox",
                "value": false,
                "advanced": true
            },
            "disabled": {
                "label": "component.disabled",
                "type": "checkbox",
                "value": false,
                "advanced": true
            }
        }
    },
    {
        "name": "email",
        "title": "email.title",
        "fields": {
            "id": {
                "label": "component.id",
                "type": "input",
                "value": "email"
            },
            "label": {
                "label": "component.label",
                "type": "input",
                "value": "{$i18n['emailField']}"
            },
            "placeholder": {
                "label": "component.placeholder",
                "type": "input",
                "value": ""
            },
            "required": {
                "label": "component.required",
                "type": "checkbox",
                "value": false
            },
            "predefinedValue": {
                "label": "component.predefinedValue",
                "type": "input",
                "value": "",
                "advanced": true
            },
            "helpText": {
                "label": "component.helpText",
                "type": "textarea",
                "value": "",
                "advanced": true
            },
            "pattern": {
                "label": "component.pattern",
                "type": "input",
                "value": "",
                "advanced": true
            },
            "cssClass": {
                "label": "component.cssClass",
                "type": "input",
                "value": "form-control",
                "advanced": true
            },
            "labelClass": {
                "label": "component.labelClass",
                "type": "input",
                "value": "control-label",
                "advanced": true
            },
            "containerClass": {
                "label": "component.containerClass",
                "type": "input",
                "value": "col-xs-12",
                "advanced": true
            },
             "alias": {
                "label": "component.alias",
                "type": "input",
                "value": "",
                "advanced": true
            },
            "checkdns": {
                "label": "component.checkDNS",
                "type": "checkbox",
                "value": false,
                "advanced": true
            },
            "multiple": {
                "label": "component.multiple",
                "type": "checkbox",
                "value": false,
                "advanced": true
            },
            "unique": {
                "label": "component.unique",
                "type": "checkbox",
                "value": false,
                "advanced": true
            },
            "readOnly": {
                "label": "component.readOnly",
                "type": "checkbox",
                "value": false,
                "advanced": true
            },
            "disabled": {
                "label": "component.disabled",
                "type": "checkbox",
                "value": false,
                "advanced": true
            }
        }
    },
    {
        "name": "textarea",
        "title": "textarea.title",
        "fields": {
            "id": {
                "label": "component.id",
                "type": "input",
                "value": "textarea"
            },
            "label": {
                "label": "component.label",
                "type": "input",
                "value": "{$i18n['textArea']}"
            },
            "placeholder": {
                "label": "component.placeholder",
                "type": "input",
                "value": ""
            },
            "predefinedValue": {
                "label": "component.predefinedValue",
                "type": "textarea",
                "value": ""
            },
            "required": {
                "label": "component.required",
                "type": "checkbox",
                "value": false
            },
            "helpText": {
                "label": "component.helpText",
                "type": "textarea",
                "value": "",
                "advanced": true
            },
            "fieldSize": {
                "label": "component.fieldSize",
                "type": "input",
                "value": 3,
                "advanced": true
            },
            "cssClass": {
                "label": "component.cssClass",
                "type": "input",
                "value": "form-control",
                "advanced": true
            },
            "labelClass": {
                "label": "component.labelClass",
                "type": "input",
                "value": "control-label",
                "advanced": true
            },
            "containerClass": {
                "label": "component.containerClass",
                "type": "input",
                "value": "col-xs-12",
                "advanced": true
            },
             "alias": {
                "label": "component.alias",
                "type": "input",
                "value": "",
                "advanced": true
            },
            "unique": {
                "label": "component.unique",
                "type": "checkbox",
                "value": false,
                "advanced": true
            },
            "readOnly": {
                "label": "component.readOnly",
                "type": "checkbox",
                "value": false,
                "advanced": true
            },
            "disabled": {
                "label": "component.disabled",
                "type": "checkbox",
                "value": false,
                "advanced": true
            }
        }
    },
    {
        "name": "checkbox",
        "title": "checkbox.title",
        "fields": {
            "id": {
                "label": "component.groupName",
                "type": "input",
                "value": "checkbox"
            },
            "label": {
                "label": "component.label",
                "type": "input",
                "value": "{$i18n['checkAllThatApply']}"
            },
            "checkboxes": {
                "label": "component.checkboxes",
                "type": "textarea-split",
                "value": [
                    "First Choice|check",
                    "Second Choice",
                    "Third Choice"
                ]
            },
            "required": {
                "label": "component.required",
                "type": "checkbox",
                "value": false
            },
            "helpText": {
                "label": "component.helpText",
                "type": "textarea",
                "value": "",
                "advanced": true
            },
            "cssClass": {
                "label": "component.cssClass",
                "type": "input",
                "value": "checkbox-inline",
                "advanced": true
            },
            "labelClass": {
                "label": "component.labelClass",
                "type": "input",
                "value": "control-label",
                "advanced": true
            },
            "containerClass": {
                "label": "component.containerClass",
                "type": "input",
                "value": "col-xs-12",
                "advanced": true
            },
             "alias": {
                "label": "component.alias",
                "type": "input",
                "value": "",
                "advanced": true
            },
            "disabled": {
                "label": "component.disabled",
                "type": "checkbox",
                "value": false,
                "advanced": true
            },
            "readOnly": {
                "label": "component.readOnly",
                "type": "checkbox",
                "value": false,
                "advanced": true
            },
            "inline": {
                "label": "component.inline",
                "type": "checkbox",
                "value": false,
                "advanced": true
            }
        }
    },
    {
        "name": "radio",
        "title": "radio.title",
        "fields": {
            "id": {
                "label": "component.groupName",
                "type": "input",
                "value": "radio"
            },
            "label": {
                "label": "component.label",
                "type": "input",
                "value": "{$i18n['selectAChoice']}"
            },
            "radios": {
                "label": "component.radios",
                "type": "textarea-split",
                "value": [
                    "First Choice|select",
                    "Second Choice",
                    "Third Choice"
                ]
            },
            "required": {
                "label": "component.required",
                "type": "checkbox",
                "value": false
            },
            "helpText": {
                "label": "component.helpText",
                "type": "textarea",
                "value": "",
                "advanced": true
            },
            "cssClass": {
                "label": "component.cssClass",
                "type": "input",
                "value": "radio-inline",
                "advanced": true
            },
            "labelClass": {
                "label": "component.labelClass",
                "type": "input",
                "value": "control-label",
                "advanced": true
            },
            "containerClass": {
                "label": "component.containerClass",
                "type": "input",
                "value": "col-xs-12",
                "advanced": true
            },
             "alias": {
                "label": "component.alias",
                "type": "input",
                "value": "",
                "advanced": true
            },
            "disabled": {
                "label": "component.disabled",
                "type": "checkbox",
                "value": false,
                "advanced": true
            },
            "readOnly": {
                "label": "component.readOnly",
                "type": "checkbox",
                "value": false,
                "advanced": true
            },
            "inline": {
                "label": "component.inline",
                "type": "checkbox",
                "value": false,
                "advanced": true
            }
        }
    },
    {
        "name": "selectlist",
        "title": "selectlist.title",
        "fields": {
            "id": {
                "label": "component.id",
                "type": "input",
                "value": "selectlist"
            },
            "label": {
                "label": "component.label",
                "type": "input",
                "value": "{$i18n['selectAChoice']}"
            },
            "options": {
                "label": "component.options",
                "type": "textarea-split",
                "value": [
                    "First Choice|select",
                    "Second Choice",
                    "Third Choice"
                ]
            },
            "placeholder": {
                "label": "component.placeholder",
                "type": "input",
                "value": ""
            },
            "required": {
                "label": "component.required",
                "type": "checkbox",
                "value": false
            },
            "helpText": {
                "label": "component.helpText",
                "type": "textarea",
                "value": "",
                "advanced": true
            },
            "cssClass": {
                "label": "component.cssClass",
                "type": "input",
                "value": "form-control",
                "advanced": true
            },
            "labelClass": {
                "label": "component.labelClass",
                "type": "input",
                "value": "control-label",
                "advanced": true
            },
            "containerClass": {
                "label": "component.containerClass",
                "type": "input",
                "value": "col-xs-12",
                "advanced": true
            },
             "alias": {
                "label": "component.alias",
                "type": "input",
                "value": "",
                "advanced": true
            },
            "readOnly": {
                "label": "component.readOnly",
                "type": "checkbox",
                "value": false,
                "advanced": true
            },
            "disabled": {
                "label": "component.disabled",
                "type": "checkbox",
                "value": false,
                "advanced": true
            },
            "multiple": {
                "label": "component.multiple",
                "type": "checkbox",
                "value": false,
                "advanced": true
            }
        }
    },
    {
        "name": "hidden",
        "title": "hidden.title",
        "fields": {
            "id": {
                "label": "component.id",
                "type": "input",
                "value": "hidden"
            },
            "label": {
                "label": "component.label",
                "type": "input",
                "value": ""
            },
            "predefinedValue": {
                "label": "component.predefinedValue",
                "type": "input",
                "value": ""
            },
             "alias": {
                "label": "component.alias",
                "type": "input",
                "value": "",
                "advanced": true
            },
            "disabled": {
                "label": "component.disabled",
                "type": "checkbox",
                "value": false
            }
        }
    },
    {
        "name": "file",
        "title": "file.title",
        "fields": {
            "id": {
                "label": "component.id",
                "type": "input",
                "value": "file"
            },
            "label": {
                "label": "component.label",
                "type": "input",
                "value": "{$i18n['attachAFile']}"
            },
            "accept": {
                "label": "component.accept",
                "type": "input",
                "value": ".gif, .jpg, .png"
            },
            "required": {
                "label": "component.required",
                "type": "checkbox",
                "value": false
            },
            "helpText": {
                "label": "component.helpText",
                "type": "textarea",
                "value": "",
                "advanced": true
            },
            "minSize": {
                "label": "component.minSize",
                "type": "input",
                "value": "",
                "advanced": true
            },
            "maxSize": {
                "label": "component.maxSize",
                "type": "input",
                "value": "",
                "advanced": true
            },
            "cssClass": {
                "label": "component.cssClass",
                "type": "input",
                "value": "",
                "advanced": true
            },
            "labelClass": {
                "label": "component.labelClass",
                "type": "input",
                "value": "control-label",
                "advanced": true
            },
            "containerClass": {
                "label": "component.containerClass",
                "type": "input",
                "value": "col-xs-12",
                "advanced": true
            },
             "alias": {
                "label": "component.alias",
                "type": "input",
                "value": "",
                "advanced": true
            },
            "readOnly": {
                "label": "component.readOnly",
                "type": "checkbox",
                "value": false,
                "advanced": true
            },
            "disabled": {
                "label": "component.disabled",
                "type": "checkbox",
                "value": false,
                "advanced": true
            }
        }
    },
    {
        "name": "snippet",
        "title": "snippet.title",
        "fields": {
            "id": {
                "label": "component.id",
                "type": "input",
                "value": "snippet"
            },
            "snippet": {
                "label": "component.htmlCode",
                "type": "textarea",
                "value": "{$i18n['replaceThisCode']}"
            },
            "containerClass": {
                "label": "component.containerClass",
                "type": "input",
                "value": "col-xs-12",
                "advanced": true
            }
        }
    },
    {
        "name": "recaptcha",
        "title": "recaptcha.title",
        "fields": {
            "id": {
                "label": "component.id",
                "type": "input",
                "value": "recaptcha"
            },
            "theme": {
                "label": "component.theme",
                "type": "select",
                "value": [
                    {
                        "value": "light",
                        "label": "Light",
                        "selected": true
                    },
                    {
                        "value": "dark",
                        "label": "Dark",
                        "selected": false
                    }
                ]
            },
            "type": {
                "label": "component.type",
                "type": "select",
                "value": [
                    {
                        "value": "image",
                        "label": "Image",
                        "selected": true
                    },
                    {
                        "value": "audio",
                        "label": "Audio",
                        "selected": false
                    }
                ],
                "advanced": true
            },
            "size": {
                "label": "component.size",
                "type": "select",
                "value": [
                    {
                        "value": "normal",
                        "label": "Normal",
                        "selected": true
                    },
                    {
                        "value": "compact",
                        "label": "Compact",
                        "selected": false
                    }
                ],
                "advanced": true
            },
            "containerClass": {
                "label": "component.containerClass",
                "type": "input",
                "value": "col-xs-12",
                "advanced": true
            }
        }
    },
    {
        "name": "pagebreak",
        "title": "pagebreak.title",
        "fields": {
            "id": {
                "label": "component.id",
                "type": "input",
                "value": "pagebreak"
            },
            "prev": {
                "label": "component.prev",
                "type": "input",
                "value": ""
            },
            "next": {
                "label": "component.next",
                "type": "input",
                "value": ""
            }
        }
    },
    {
        "name": "spacer",
        "title": "spacer.title",
        "fields": {
            "id": {
                "label": "component.id",
                "type": "input",
                "value": "spacer"
            },
            "height": {
                "label": "component.height",
                "type": "number",
                "value": "50"
            },
            "cssClass": {
                "label": "component.cssClass",
                "type": "input",
                "value": "",
                "advanced": true
            },
            "containerClass": {
                "label": "component.containerClass",
                "type": "input",
                "value": "col-xs-12",
                "advanced": true
            }
        }
    },
    {
		"name": "signature",
		"title": "signature.title",
		"fields": {
			"id": {
				"label": "component.id",
				"type": "input",
				"value": "signature"
			},
            "label": {
                "label": "component.label",
                "type": "input",
				"value": "{$i18n['signature']}"
            },
            "required": {
                "label": "component.required",
                "type": "checkbox",
                "value": false
            },
            "clear": {
                "label": "component.clear",
                "type": "checkbox",
                "value": true
            },
            "undo": {
                "label": "component.undo",
                "type": "checkbox",
                "value": true
            },
            "helpText": {
                "label": "component.helpText",
                "type": "textarea",
                "value": "",
                "advanced": true
            },
            "width": {
                "label": "component.width",
                "type": "input",
                "value": "400",
                "advanced": true
            },
            "height": {
                "label": "component.height",
                "type": "input",
                "value": "200",
                "advanced": true
            },
            "color": {
                "label": "component.color",
                "type": "input",
                "value": "black",
                "advanced": true
            },
            "clearText": {
                "label": "component.clearText",
                "type": "input",
                "value": "{$i18n['clear']}",
                "advanced": true
            },
            "undoText": {
                "label": "component.undoText",
                "type": "input",
                "value": "{$i18n['undo']}",
                "advanced": true
            },
            "cssClass": {
                "label": "component.cssClass",
                "type": "input",
                "value": "",
                "advanced": true
            },
            "labelClass": {
                "label": "component.labelClass",
                "type": "input",
                "value": "control-label",
                "advanced": true
            },
            "containerClass": {
                "label": "component.containerClass",
                "type": "input",
                "value": "col-xs-12",
                "advanced": true
            }
		}
	},
    {
        "name": "button",
        "title": "button.title",
        "fields": {
            "id": {
                "label": "component.id",
                "type": "input",
                "value": "button"
            },
            "inputType": {
                "label": "component.type",
                "type": "select",
                "value": [
                    {
                        "value": "submit",
                        "label": "Submit",
                        "selected": true
                    },
                    {
                        "value": "reset",
                        "label": "Reset",
                        "selected": false
                    },
                    {
                        "value": "image",
                        "label": "Image",
                        "selected": false
                    },
                    {
                        "value": "button",
                        "label": "Button",
                        "selected": false
                    }
                ]
            },
            "buttonText": {
                "label": "component.buttonText",
                "type": "input",
                "value": "{$i18n['buttonText']}"
            },
            "label": {
                "label": "component.label",
                "type": "input",
                "value": "",
                "advanced": true
            },
            "src": {
                "label": "component.src",
                "type": "input",
                "value": "",
                "advanced": true
            },
            "cssClass": {
                "label": "component.cssClass",
                "type": "input",
                "value": "btn btn-primary",
                "advanced": true
            },
            "labelClass": {
                "label": "component.labelClass",
                "type": "input",
                "value": "control-label",
                "advanced": true
            },
            "containerClass": {
                "label": "component.containerClass",
                "type": "input",
                "value": "col-xs-12",
                "advanced": true
            },
            "readOnly": {
                "label": "component.readOnly",
                "type": "checkbox",
                "value": false,
                "advanced": true
            },
            "disabled": {
                "label": "component.disabled",
                "type": "checkbox",
                "value": false,
                "advanced": true
            }
        }
    },
     {
        "name": "camera",
        "title": "camera.title",
        "fields": {
            "id": {
                "label": "component.id",
                "type": "input",
                "value": "camera"
            },
            "inputType": {
                "label": "component.type",
                "type": "select",
                "value": [
                    {
                        "value": "camera",
                        "label": "Camera",
                        "selected": true
                    },
                    {
                        "value": "video",
                        "label": "Video",
                        "selected": false
                    }
                ]
            },
            "buttonText": {
                "label": "Camera text",
                "type": "input",
                "value": "{$i18n['buttonText']}"
            },
            "label": {
                "label": "component.label",
                "type": "input",
                "value": "",
                "advanced": true
            },
            "src": {
                "label": "component.src",
                "type": "input",    
                "value": "",
                "advanced": true
            },
            "cssClass": {
                "label": "component.cssClass",
                "type": "input",
                "value": "btn btn-primary",
                "advanced": true
            },
            "labelClass": {
                "label": "component.labelClass",
                "type": "input",
                "value": "control-label",
                "advanced": true
            },
            "containerClass": {
                "label": "component.containerClass",
                "type": "input",
                "value": "col-xs-12",
                "advanced": true
            },
            "readOnly": {
                "label": "component.readOnly",
                "type": "checkbox",
                "value": false,
                "advanced": true
            },
            "disabled": {
                "label": "component.disabled",
                "type": "checkbox",
                "value": false,
                "advanced": true
            }
        }
    }
]
EOD;

            $res = Json::decode($json, true);

            return $res;
        }

        return '';
    }

    /**********************************************
     * /* Form
     * /**********************************************/

    /**
     * Return form data for initialize the builder
     *
     * @param null $id
     * @param string $template
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function actionInitForm($id = null, $template = 'default')
    {

        if (Yii::$app->request->isAjax) {

            Yii::$app->response->format = Response::FORMAT_JSON;

            // For create form
            if (!isset($id)) {

                if ($template == 'default') {

                    $i18n = [
                        'untitledForm' => Yii::t('app', 'Untitled Form'),
                        'thisIsMyForm' => Yii::t('app', 'This is my form. Please fill it out. Thanks!'),
                    ];

                    // Default template
                    $json = <<<EOD
{
    "initForm": [
        {
            "name": "heading",
            "title": "heading.title",
            "fields": {
                "id": {
                    "label": "component.id",
                    "type": "input",
                    "value": "heading_0"
                },
                "text": {
                    "label": "component.text",
                    "type": "input",
                    "value": "{$i18n['untitledForm']}"
                },
                "type": {
                    "label": "component.type",
                    "type": "select",
                    "value": [
                        {
                            "value": "h1",
                            "label": "H1",
                            "selected": false
                        },
                        {
                            "value": "h2",
                            "label": "H2",
                            "selected": false
                        },
                        {
                            "value": "h3",
                            "label": "H3",
                            "selected": true
                        },
                        {
                            "value": "h4",
                            "label": "H4",
                            "selected": false
                        },
                        {
                            "value": "h5",
                            "label": "H5",
                            "selected": false
                        },
                        {
                            "value": "h6",
                            "label": "H6",
                            "selected": false
                        }
                    ]
                },
                "cssClass": {
                    "label": "component.cssClass",
                    "type": "input",
                    "value": "legend",
                    "advanced": true
                },
                "containerClass": {
                    "label": "component.containerClass",
                    "type": "input",
                    "value": "",
                    "advanced": true
                }
            },
            "fresh": false
        },
        {
            "name": "paragraph",
            "title": "paragraph.title",
            "fields": {
                "id": {
                    "label": "component.id",
                    "type": "input",
                    "value": "paragraph_0"
                },
                "text": {
                    "label": "component.text",
                    "type": "textarea",
                    "value": "{$i18n['thisIsMyForm']}"
                },
                "cssClass": {
                    "label": "component.cssClass",
                    "type": "input",
                    "value": "",
                    "advanced": true
                },
                "containerClass": {
                    "label": "component.containerClass",
                    "type": "input",
                    "value": "",
                    "advanced": true
                }
            },
            "fresh": false
        }
    ],
    "settings": {
        "name": "{$i18n['untitledForm']}",
        "canvas": "#canvas",
        "disabledFieldset": false,
        "layoutSelected": "",
        "layouts": [
            {
                "id": "",
                "name": "Vertical"
            },
            {
                "id": "form-horizontal",
                "name": "Horizontal"
            },
            {
                "id": "form-inline",
                "name": "Inline"
            }
        ],
        "formSteps": {
            "title": "formSteps.title",
            "fields": {
                "id": {
                    "label": "formSteps.id",
                    "type": "input",
                    "value": "formSteps"
                },
                "steps": {
                    "label": "formSteps.steps",
                    "type": "textarea-split",
                    "value": []
                },
                "progressBar": {
                    "label": "formSteps.progressBar",
                    "type": "checkbox",
                    "value": false
                },
                "noTitles": {
                    "label": "formSteps.noTitles",
                    "type": "checkbox",
                    "value": false
                },
                "noStages": {
                    "label": "formSteps.noStages",
                    "type": "checkbox",
                    "value": false
                },
                "noSteps": {
                    "label": "formSteps.noSteps",
                    "type": "checkbox",
                    "value": false
                }
            }
        }
    }
}
EOD;

                } else {
                    /** @var $templateModel \app\models\Template */
                    $templateModel = Template::findOne([
                        'slug' => $template
                    ]);

                    // If template doesn't exist
                    if ($templateModel === null) {
                        throw new InvalidParamException(Yii::t("app", "Invalid template."));
                    }

                    $json = $templateModel->builder;
                }

                $res = Json::decode($json, true);
                return $res;
            }

            // For update form
            $formModel = $this->findFormModel($id);

            if (isset($formModel)) {
                $formDataModel = $formModel->formData;
                $res = $formDataModel->behaviors['builderField']->getArray();
                return $res;
            }
        }

        return '';
    }

    /**
     * Create form
     *
     * @return array
     * @throws Exception
     * @throws \yii\db\Exception
     */
    public function actionCreateForm()
    {

        if (Yii::$app->request->isAjax) {

            // Response fornat
            Yii::$app->response->format = Response::FORMAT_JSON;

            // Extract FormBuilder data from post request
            $post = Yii::$app->request->post();

            $data = [
                'FormBuilder' => Json::decode($post['FormBuilder'], true), // Convert to array
            ];

            $formBuilder = new FormBuilder();

            // Flags variables
            $success = false;
            $id = false;
            $message = '';
            $code = 0;

            // Form Builder Validation
            if ($formBuilder->load($data) && $formBuilder->validate()) {

                // Save data in single transaction

                $transaction = Form::getDb()->beginTransaction();

                try {

                    // Parse html form fields to array
                    $formDOM = new FormDOM();
                    // If two elements has same id, throw a exception
                    $formDOM->loadHTML($data['FormBuilder']['html']);
                    $formDOM->loadXpath();
                    $formDOM->loadFields();

                    // Filter reCaptcha component
                    $reCaptchaComponent = ArrayHelper::filter(
                        ArrayHelper::getValue($data, 'FormBuilder.data.initForm'),
                        'recaptcha',
                        'name'
                    );

                    // Update form name with form title
                    $name = ArrayHelper::getValue($data, 'FormBuilder.data.settings.name');
                    if ($name === Yii::t('app', 'Untitled Form')) {
                        $name = $formDOM->getFormTitle();
                        ArrayHelper::setValue($data, 'FormBuilder.data.settings.name', $name);
                    }

                    // Populate Form Model and Save
                    $postForm = [
                        'Form' => [
                            'name' => $name, // Form name
                            'language' => Yii::$app->language, // By default, the form language is the user language
                            // If reCaptchaComponent exists, enable reCaptcha validation
                            'recaptcha' => count($reCaptchaComponent) > 0 ? 1 : 0,
                        ]
                    ];

                    // Save Form Model
                    $formModel = new Form();
                    $formModel->analytics =0;
                    if ($formModel->load($postForm) && $formModel->save()) {
                        // Set form id
                        $id = $formModel->primaryKey;
                    } else {
                        throw new Exception(Yii::t('app', 'Error saving data'), 1);
                    }

                    // Post Form Data
                    $postFormData = [
                        'FormData' => [
                            'form_id' => $id, // Form Model id
                            'builder' => Json::htmlEncode(ArrayHelper::getValue($data, 'FormBuilder.data')),
                            'fields' => $formDOM->getFieldsAsJSON(), // (array) JSON
                            // Encodes special characters into HTML entities
                            'html' => Html::encode(ArrayHelper::getValue($data, 'FormBuilder.html')),
                            'height' => ArrayHelper::getValue($data, 'FormBuilder.data.height'), // Form Height
                        ]
                    ];

                    // Populate FormData Model and Save
                    $formDataModel = new FormData();
                    if (!$formDataModel->load($postFormData) || !$formDataModel->save()) {
                        throw new Exception(Yii::t('app', 'Error saving data'), 2);
                    }

                    // Save FormUI Model
                    $formUIModel = new FormUI();
                    $formUIModel->link('form', $formModel);

                    // Save FormConfirmation Model
                    $formConfirmationModel = new FormConfirmation();
                    $formConfirmationModel->link('form', $formModel);

                    // Save FormEmail Model
                    $formEmailModel = new FormEmail();
                    $formEmailModel->link('form', $formModel);

                    $transaction->commit();

                    // Change success flag and message
                    $success = true;
                    $message = Yii::t("app", "The form has been successfully created.");

                } catch (Exception $e) {
                    // Rolls back the transaction
                    $transaction->rollBack();
                    // Rethrow the exception
                    // throw $e;
                    $message = $e->getMessage();
                    $code = $e->getCode();
                }

            }

            // Response to Client
            $res = array(
                'success' => $success,
                'id' => $id,
                'action' => 'create',
                'message' => $message,
                'code' => $code,
            );

            return $res;
        }

        return '';
    }

    /**
     * Update form
     *
     * @param $id
     * @return array|string
     * @throws \yii\db\Exception
     */
    public function actionUpdateForm($id)
    {


        if (Yii::$app->request->isAjax) {

            // Response fornat
            Yii::$app->response->format = Response::FORMAT_JSON;

            // Extract FormBuilder data from post request
            $post = Yii::$app->request->post();
            $data = [
                'FormBuilder' => Json::decode($post['FormBuilder'], true), // Convert to array
            ];

            $formBuilder = new FormBuilder;
            $success = false;
            $message = '';
            $code = 0;

            // Form Builder Validation
            if ($formBuilder->load($data) && $formBuilder->validate()) {

                // Save data in single transaction

                $transaction = Form::getDb()->beginTransaction();

                try {

                    // Parse html form fields to array
                    $formDOM = new FormDOM();
                    // If two elements has same id, throw a exception
                    $formDOM->loadHTML(ArrayHelper::getValue($data, 'FormBuilder.html'));
                    $formDOM->loadXpath();
                    $formDOM->loadFields();

                    // Get Form Model
                    $formModel = $this->findFormModel($id);
                    // Get FormData Model
                    $formDataModel = $formModel->formData;
                    // Get FormEmail Model
                    $formEmailModel = $formModel->formEmail;

                    // Filter reCaptcha component
                    $reCaptchaComponent = ArrayHelper::filter(
                        ArrayHelper::getValue($data, 'FormBuilder.data.initForm'),
                        'recaptcha',
                        'name'
                    );

                    // Post Form
                    $postForm = [
                        'Form' => [
                            'name' => ArrayHelper::getValue(
                                $data,
                                'FormBuilder.data.settings.name'
                            ), // Extract the form name
                            // If reCaptchaComponent exists, enable reCaptcha validation
                            'recaptcha' => count($reCaptchaComponent) > 0 ? 1 : 0,
                        ]
                    ];

                    // Post Form Data
                    $postFormData = [
                        'FormData' => [
                            'form_id' => $id, // Form Model id
                            'builder' => Json::htmlEncode(ArrayHelper::getValue($data, 'FormBuilder.data')), // JSON
                            'fields' => $formDOM->getFieldsAsJSON(), // (array) JSON
                            // Encodes special characters into HTML entities
                            'html' => Html::encode(ArrayHelper::getValue($data, 'FormBuilder.html')),
                            'height' => ArrayHelper::getValue($data, 'FormBuilder.data.height'), // Form Height
                        ]
                    ];

                    if (!$formModel->load($postForm) || !$formModel->save()) {
                        throw new Exception(Yii::t('app', 'Error saving data'), 1);
                    }

                    // Old Form Data Model
                    $oldFormDataModel = clone $formDataModel;

                    if (!$formDataModel->load($postFormData) || !$formDataModel->validate() ||
                        !$formDataModel->save()) {
                        throw new Exception(Yii::t('app', 'Error saving data'), 2);
                    }

                    // Trigger event
                    Yii::$app->trigger($this::EVENT_FORM_UPDATED, new FormEvent([
                        'sender' => $this,
                        'form' => $formModel,
                        'formData' => $formDataModel,
                        'oldFormData' => $oldFormDataModel,
                        'formDOM' => $formDOM,
                        'formBuilder' => $formBuilder,
                    ]));

                    $transaction->commit();

                    // Change success flag and message
                    $success = true;
                    $message = Yii::t("app", "The form has been successfully updated");

                } catch (Exception $e) {
                    // Rolls back the transaction
                    $transaction->rollBack();
                    // Rethrow the exception
                    // throw $e;
                    $message = $e->getMessage();
                    $code = $e->getCode();
                }

            }

            // Response to Client
            $res = array(
                'success' => $success,
                'id' => $id,
                'action' => 'update',
                'message' => $message,
                'code' => $code,
            );

            return $res;
        }

        return '';
    }

    /**********************************************
     * /* Template
     * /**********************************************/

    /**
     * Return template data for initialize the builder
     *
     * @param null $id
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function actionInitTemplate($id = null)
    {
        if (Yii::$app->request->isAjax) {

            Yii::$app->response->format = Response::FORMAT_JSON;

            // For create form
            if (!isset($id)) {

                $i18n = [
                    'untitledForm' => Yii::t('app', 'Untitled Form'),
                    'thisIsMyForm' => Yii::t('app', 'This is my form. Please fill it out. Thanks!'),
                ];

                // Default template
                $json = <<<EOD
{
    "initForm": [
        {
            "name": "heading",
            "title": "heading.title",
            "fields": {
                "id": {
                    "label": "component.id",
                    "type": "input",
                    "value": "heading_0"
                },
                "text": {
                    "label": "component.text",
                    "type": "input",
                    "value": "{$i18n['untitledForm']}"
                },
                "type": {
                    "label": "component.type",
                    "type": "select",
                    "value": [
                        {
                            "value": "h1",
                            "label": "H1",
                            "selected": false
                        },
                        {
                            "value": "h2",
                            "label": "H2",
                            "selected": false
                        },
                        {
                            "value": "h3",
                            "label": "H3",
                            "selected": true
                        },
                        {
                            "value": "h4",
                            "label": "H4",
                            "selected": false
                        },
                        {
                            "value": "h5",
                            "label": "H5",
                            "selected": false
                        },
                        {
                            "value": "h6",
                            "label": "H6",
                            "selected": false
                        }
                    ]
                },
                "cssClass": {
                    "label": "component.cssClass",
                    "type": "input",
                    "value": "legend",
                    "advanced": true
                },
                "containerClass": {
                    "label": "component.containerClass",
                    "type": "input",
                    "value": "",
                    "advanced": true
                }
            },
            "fresh": false
        },
        {
            "name": "paragraph",
            "title": "paragraph.title",
            "fields": {
                "id": {
                    "label": "component.id",
                    "type": "input",
                    "value": "paragraph_0"
                },
                "text": {
                    "label": "component.text",
                    "type": "textarea",
                    "value": "{$i18n['thisIsMyForm']}"
                },
                "cssClass": {
                    "label": "component.cssClass",
                    "type": "input",
                    "value": "",
                    "advanced": true
                },
                "containerClass": {
                    "label": "component.containerClass",
                    "type": "input",
                    "value": "",
                    "advanced": true
                }
            },
            "fresh": false
        }
    ],
    "settings": {
        "name": "{$i18n['untitledForm']}",
        "canvas": "#canvas",
        "disabledFieldset": false,
        "layoutSelected": "",
        "layouts": [
            {
                "id": "",
                "name": "Vertical"
            },
            {
                "id": "form-horizontal",
                "name": "Horizontal"
            },
            {
                "id": "form-inline",
                "name": "Inline"
            }
        ],
        "formSteps": {
            "title": "formSteps.title",
            "fields": {
                "id": {
                    "label": "formSteps.id",
                    "type": "input",
                    "value": "formSteps"
                },
                "steps": {
                    "label": "formSteps.steps",
                    "type": "textarea-split",
                    "value": []
                },
                "progressBar": {
                    "label": "formSteps.progressBar",
                    "type": "checkbox",
                    "value": false
                },
                "noTitles": {
                    "label": "formSteps.noTitles",
                    "type": "checkbox",
                    "value": false
                },
                "noStages": {
                    "label": "formSteps.noStages",
                    "type": "checkbox",
                    "value": false
                },
                "noSteps": {
                    "label": "formSteps.noSteps",
                    "type": "checkbox",
                    "value": false
                }
            }
        }
    }
}
EOD;
                return Json::decode($json);
            }

            // For update templates
            $templateModel = $this->findTemplateModel($id);

            if (isset($templateModel)) {
                $res = $templateModel->behaviors['builderField']->getArray();
                return $res;
            }
        }

        return '';
    }

    /**
     * Create Template model
     * @return array
     */
    public function actionCreateTemplate()
    {

        if (Yii::$app->request->isAjax) {

            // Response fornat
            Yii::$app->response->format = Response::FORMAT_JSON;

            // Extract FormBuilder data from post request
            $post = Yii::$app->request->post();

            $data = [
                'FormBuilder' => Json::decode($post['FormBuilder'], true), // Convert to array
            ];

            $formBuilder = new FormBuilder();

            // Flags variables
            $success = false;
            $id = false;
            $message = '';
            $code = 0;

            // Form Builder Validation
            if ($formBuilder->load($data) && $formBuilder->validate()) {

                // Save data in single transaction

                $transaction = Template::getDb()->beginTransaction();

                try {

                    // Parse html form fields to array
                    $formDOM = new FormDOM();
                    // If two elements has same id, throw a exception
                    $formDOM->loadHTML(ArrayHelper::getValue($data, 'FormBuilder.html'));

                    // Update form name with form title
                    $name = ArrayHelper::getValue($data, 'FormBuilder.data.settings.name');
                    if ($name === Yii::t('app', 'Untitled Form')) {
                        $name = $formDOM->getFormTitle();
                        ArrayHelper::setValue($data, 'FormBuilder.data.settings.name', $name);
                    }

                    // Populate Template Model and Save
                    $postTemplate = [
                        'Template' => [
                            'name' => $name, // Form name
                            'builder' => Json::htmlEncode(ArrayHelper::getValue($data, 'FormBuilder.data')),
                            // Encodes special characters into HTML entities
                            'html' => Html::encode(ArrayHelper::getValue($data, 'FormBuilder.html')),
                        ]
                    ];

                    // Save Template Model
                    $templateModel = new Template();
                    if ($templateModel->load($postTemplate) && $templateModel->save()) {
                        // Set form id
                        $id = $templateModel->primaryKey;
                    } else {
                        throw new Exception(Yii::t('app', 'Error saving data'), 1);
                    }

                    $transaction->commit();

                    // Change success flag and message
                    $success = true;
                    $message = Yii::t("app", "The template has been successfully created.");

                    // Show message after redirection
                    Yii::$app->getSession()->setFlash(
                        'success',
                        Yii::t('app', 'The template has been successfully created.')
                    );

                } catch (Exception $e) {
                    // Rolls back the transaction
                    $transaction->rollBack();
                    // Rethrow the exception
                    // throw $e;
                    $message = $e->getMessage();
                    $code = $e->getCode();
                }

            }

            // Response to Client
            $res = array(
                'success' => $success,
                'id' => $id,
                'action' => 'create',
                'message' => $message,
                'code' => $code,
            );

            return $res;
        }

        return '';
    }

    /**
     * Update Template model
     *
     * @param $id
     * @return array
     * @throws \yii\db\Exception
     */
    public function actionUpdateTemplate($id)
    {

        if (Yii::$app->request->isAjax) {

            // Response fornat
            Yii::$app->response->format = Response::FORMAT_JSON;

            // Extract FormBuilder data from post request
            $post = Yii::$app->request->post();

            $data = [
                'FormBuilder' => Json::decode($post['FormBuilder'], true), // Convert to array
            ];

            $formBuilder = new FormBuilder;
            $success = false;
            $message = '';
            $code = 0;

            // Form Builder Validation
            if ($formBuilder->load($data) && $formBuilder->validate()) {

                // Save data in single transaction

                $transaction = Template::getDb()->beginTransaction();

                try {

                    // Parse html form fields to array
                    $formDOM = new FormDOM();
                    // If two elements has same id, throw a exception
                    $formDOM->loadHTML(ArrayHelper::getValue($data, 'FormBuilder.html'));

                    // Get Template Model
                    $templateModel = $this->findTemplateModel($id);

                    // Post Template
                    $postTemplate = [
                        'Template' => [
                            'name' => ArrayHelper::getValue($data, 'FormBuilder.data.settings.name'), // Form name
                            'builder' => Json::htmlEncode(ArrayHelper::getValue($data, 'FormBuilder.data')), // JSON
                            // Encodes special characters into HTML entities
                            'html' => Html::encode(ArrayHelper::getValue($data, 'FormBuilder.html')),
                        ]
                    ];

                    if (!$templateModel->load($postTemplate) || !$templateModel->save()) {
                        throw new Exception(Yii::t('app', 'Error saving data'), 1);
                    }

                    $transaction->commit();

                    // Change success flag and message
                    $success = true;
                    $message = Yii::t("app", "The template has been successfully updated");

                    // Show message after redirection
                    Yii::$app->getSession()->setFlash(
                        'success',
                        Yii::t('app', 'The template has been successfully updated')
                    );

                } catch (Exception $e) {
                    // Rolls back the transaction
                    $transaction->rollBack();
                    // Rethrow the exception
                    // throw $e;
                    $message = $e->getMessage();
                    $code = $e->getCode();
                }

            }

            // Response to Client
            $res = array(
                'success' => $success,
                'id' => $id,
                'action' => 'update',
                'message' => $message,
                'code' => $code,
            );

            return $res;
        }

        return '';
    }

    /**********************************************
     * /* Analytics
     * /**********************************************/

    /**
     * Show & Save form report
     *
     * @param $id
     * @return array
     * @throws Exception
     */
    public function actionReport($id)
    {

        Yii::$app->response->format = Response::FORMAT_JSON;

        // Extract report data from post request
        $post = Yii::$app->request->post();

        // The raw report data
        $rawReport = isset($post) && isset($post['report']) ? $post['report'] : null;

        if (!is_null($rawReport)) {

            // Convert to charts array
            $charts = Json::decode($rawReport, true);

            // Save data in single transaction

            $transaction = FormChart::getDb()->beginTransaction();
            $success = false;

            try {

                // Delete old charts if there are
                FormChart::deleteAll(['form_id' => $id]);

                if (count($charts) > 0) {

                    // Populate each Form Chart Model and Save
                    foreach ($charts as $chart) {

                        $formChartModel = new FormChart();

                        // Add form_id to chart
                        $chart['form_id'] = $id;

                        // Prepare new model data
                        $postFormChart = [
                            'FormChart' => $chart
                        ];

                        // Load & Save the model
                        if (!$formChartModel->load($postFormChart) || !$formChartModel->save()) {
                            throw new Exception(Yii::t("app", "Error saving the chart"));
                        }
                    }

                }

                $transaction->commit();

                // Change success flag and message
                $success = true;
                $message = Yii::t("app", "The report has been successfully updated.");

            } catch (Exception $e) {
                // Rolls back the transaction
                $transaction->rollBack();
                // Rethrow the exception
                // throw $e;
                $message = $e->getMessage();
            }

            $res = array(
                'success' => $success,
                'id' => $id,
                'action' => 'update',
                'message' => $message
            );

            return $res;
        }

        $submissions = array();

        foreach (FormSubmission::find()->select(['data', 'created_at'])->where(
            'form_id=:form_id',
            [':form_id' => $id]
        )->each(10) as $submissionModel) {
            $submission = $submissionModel->data;
            $submission['created_at'] = $submissionModel->created_at;
            array_push($submissions, $submission);
        }

        return $submissions;

    }

    /**
     * Show form performance stats in csv format
     *
     * @param $id
     */
    public function actionAnalytics($id)
    {

        // Analytics Report
        $report = Analytics::report();

        // Set params
        $report->app($id)->report('performance')->prepare()->performAsCSV();

    }

    /**
     * Show form submissions stats in csv format
     *
     * @param $id
     */
    public function actionStats($id)
    {

        // Analytics Report
        $report = Analytics::report();

        // Set params
        $report->app($id)->report('submissions')->prepare()->performAsCSV();

    }

    /**
     * Save User Preference: GridView Pagination PageSize
     *
     * @return array
     */
    public function actionGridViewPageSize()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $success = false;
        $post = Yii::$app->request->post();

        if (isset($post['size']) && !empty($post['size'])) {
            Yii::$app->user->preferences->set('GridView.pagination.pageSize', $post['size']);
            $success = Yii::$app->user->preferences->save();
        }

        return ['success' => $success];
    }

    public function actionSubmissionManagerSettings()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $success = false;
        $post = Yii::$app->request->post();

        if (isset($post['form_id']) && !empty($post['form_id'])) {
            Yii::$app->user->preferences->set('GridView.submissions.settings.' . $post['form_id'], $post['settings']);
            $success = Yii::$app->user->preferences->save();
        }

        return ['success' => $success];
    }

    /**********************************************
     * /* Models
     * /**********************************************/

    /**
     * Finds the Form model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Form the loaded model
     * @throws NotFoundHttpException if the model cannot be found
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
     * Finds the Template model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Form the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findTemplateModel($id)
    {
        if (($model = Template::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException(Yii::t("app", "The requested page does not exist."));
        }
    }
}

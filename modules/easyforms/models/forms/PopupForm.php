<?php
/**
 * Copyright (C) Baluart.COM - All Rights Reserved
 *
 * @since 1.5
 * @author Balu
 * @copyright Copyright (c) 2015 - 2017 Baluart.COM
 * @license http://codecanyon.net/licenses/faq Envato marketplace licenses
 * @link http://easyforms.baluart.com/ Easy Forms
 */

namespace app\modules\easyforms\models\forms;

use Yii;

class PopupForm extends \yii\base\Model
{
    public $button_text;
    public $button_placement;
    public $button_color;
    public $popup_width;
    public $popup_padding;
    public $popup_margin;
    public $popup_radius;
    public $animation_effect;
    public $animation_duration;
    public $popup_color;
    public $overlay_color;

    public function rules()
    {
        return [
            [
                [
                    'button_text',
                    'button_placement',
                    'button_color',
                    'popup_width',
                    'popup_margin',
                    'popup_padding',
                    'popup_radius',
                    'animation_effect',
                    'animation_duration',
                    'popup_color',
                    'overlay_color'
                ],
                'required'
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'button_text' => Yii::t('form', 'Button Text'),
            'button_placement' => Yii::t('form', 'Button Placement'),
            'button_color' => Yii::t('form', 'Button Color'),
            'popup_width' => Yii::t('form', 'Popup Width'),
            'popup_margin' => Yii::t('form', 'Popup Margin'),
            'popup_padding' => Yii::t('form', 'Popup Padding'),
            'popup_radius' => Yii::t('form', 'Popup Radius'),
            'animation_effect' => Yii::t('form', 'Animation Effect'),
            'animation_duration' => Yii::t('form', 'Animation Duration'),
            'popup_color' => Yii::t('form', 'Popup Color'),
            'overlay_color' => Yii::t('form', 'Overlay Color'),
        ];
    }
}
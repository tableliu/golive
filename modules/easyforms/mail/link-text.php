<?php

use yii\helpers\Url;

/* @var $this \yii\web\View view component instance */
/* @var $message string Custom Message */
/* @var $fields array Submission Fields */
/* @var $formID integer Form ID */
/* @var $submissionID integer Submission ID */
/* @var $message string Custom Message */

?>
<?= Yii::t('form', 'Your form has received a new submission') ?>.
<?= Yii::t('form', 'For more details') ?>, <?= Yii::t('form', 'please go here') ?>:
<?= Url::to(['form/submissions', 'id' => $formID, '#' => 'view/' . $submissionID], true) ?>

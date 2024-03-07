<?php

use yii\helpers\Url;

/* @var $this \yii\web\View view component instance */
/* @var $message string Custom Message */
/* @var $fields array Submission Fields */
/* @var $formID integer Form ID */
/* @var $submissionID integer Submission ID */
/* @var $message string Custom Message */

?>
<p>
    <?= Yii::t('form', 'Your form has received a new submission') ?>.
    <?= Yii::t('form', 'For more details') ?>,
    <a href="<?= Url::to(['form/submissions', 'id' => $formID, '#' => 'view/' . $submissionID], true) ?>">
        <?= Yii::t('form', 'please click here') ?>
    </a>.
</p>

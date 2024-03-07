<?php
return [
    'class' => \yii\queue\amqp_interop\Queue::className(),
    'port' => 5672,
    'user' => '',
    'password' => '',
    'queueName' => 'close_room_delay',
    'exchangeName' => 'close_room_delay',
    'driver' => yii\queue\amqp_interop\Queue::ENQUEUE_AMQP_LIB,
    'as log' => \yii\queue\LogBehavior::className(),
    'ttr' => 60, // Max time for job execution
    'attempts' => 3, // Max number of attempts
];
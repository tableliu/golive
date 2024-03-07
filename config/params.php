<?php

return [
    'GridView.pagination.pageSize' => 10,
    'GridView.pagination.pageSizeOptions' => [10 => "10", 50 => "50", 100 => "100", 500 => "500", 1000 => "1000"],
    'LoginDuration' => 2592000,
    'App.Cron.cronKey' => '',

    //socket
    'ZMQ_SOCKET_DSN' => 'tcp://127.0.0.1:5555',

    //cos
    'COS_SECRET_ID' => '',
    'COS_SECRET_KEY' => '',
    'COS_URL' => 'https://sts.tencentcloudapi.com/',
    'COS_DOMAIN' => 'sts.tencentcloudapi.com',
    'COS_BUCKET' => [
        'live_job_img' => [
            'name' => 'live-job-img-1257704912',
            'region' => 'ap-chengdu'
        ],
        'live_job_video' => [
            'name' => 'live-job-video-1257704912',
            'region' => 'ap-chengdu'
        ],
        'live_msg_file' => [
            'name' => 'live-msg-file-1257704912',
            'region' => 'ap-chengdu'
        ],
        'user_avatar' => [
            'name' => 'user-avatar-1257704912',
            'region' => 'ap-chengdu'
        ]
    ],

    // user role order
    "Role.orders" => [
        "普通用户" => 1,
        "高级用户" => 2,
        "系统管理员" => 3,
    ],

    // live streaming
    'LiveStreaming.Domain' => 'http://a1.easemob.com',
    'LiveStreaming.OrgName' => '1115200729041583',
    'LiveStreaming.AppName' => 'iip',
    'LiveStreaming.ClientId' => '',
    'LiveStreaming.ClientSecret' => '',
    'LiveStreaming.ConfrAdminUserName' => 'liu',
    'LiveStreaming.ConfrAdminUserPassword' => '',
    'LiveStreaming.Role.ADMIN' => 7,
    'LiveStreaming.Role.TALKER' => 3,
    'LiveStreaming.Role.AUDIENCE' => 1,

    // live room life circle
    'LiveRoom.LifetimeHour' => 2,
    'LiveRoom.LeftMinutesForSingleUser' => 10,
    'LiveRoom.StreamerLeaveMinutes' => 5,
    'LiveRoom.StreamerLeaveSecs' => 180,
    'LiveRoom.TimeoutSecs' => 10800,
    'LiveRoom.OnlyStreamerSecs' => 180,
    'Room.Destroy.Reason' => [
        "ROOM_DESTROY_REASON_ROOM_CLOSE" => ["code" => 0, "msg" => "正常关闭", "jobs" => []],
        "ROOM_DESTROY_REASON_ROOM_TIMEOUT" => [
            "code" => 1,
            "msg" => "房间超时",
            'jobs' => [
                'app\models\closeRoomDelay\RoomDestroyReasonRoomTimeout'
            ],
        ],

        "ROOM_DESTROY_REASON_ROOM_STREAMER_LEAVE" => [
            "code" => 2,
            "msg" => "播主离开后未返回超时",
            'jobs' => [
                'app\models\closeRoomDelay\RoomDestroyReasonRoomStreamerLeave',
            ]
        ],
        "ROOM_DESTROY_REASON_ROOM_ONLY_STREAMER" => [
            "code" => 3,
            "msg" => "仅剩播主未退出房间超时",
            'jobs' => [
                'app\models\closeRoomDelay\RoomDestroyReasonRoomOnlyStreamer',
            ]
        ],
        "ROOM_DESTROY_REASON_ROOM_ACCIDENT" => ["code" => 9, "msg" => "意外关闭", "jobs" => []],
    ],

    'Baidu.Map.Appkey' => '',
    "Languages" => [
        "zh-CN" => [
            "lang" => "zh-CN",
            "table" => "menu"
        ],
        "en-US" => [
            "lang" => "en-US",
            "table" => "menu_en"
        ]
    ],

];
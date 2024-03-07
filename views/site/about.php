<?php

/* @var $this yii\web\View */

use yii\helpers\Html;

$this->title = 'About';
$this->params['breadcrumbs'][] = $this->title;

$entryData = ['msg' => ['val' => 'backend sent.', 'field' => 'somebody'], 'article' => 'aaaa', 'when' => '11'];
\app\helpers\SocketHelper::pushMsg(new \app\helpers\ZmqEntryData([
    'msg' => ['val' => 'backend sent.', 'field' => 'somebody']
]));
?>
    <div class="site-about">
        <h1><?= Html::encode($this->title) ?></h1>

        <p>
            FOR TEST
        </p>
        <button id="send_msg">publish</button>
        <button id="send_msg_rpc">RPC</button>
        <button id="send_msg_rpc_m">RPC M</button>


        <code><?= __FILE__ ?></code>
    </div>

    <div class="site-about">
        <input id="get_temp_key_file" type="file">
        <input id="get_temp_key_bucket" type="text" value="live_msg_file">
        <button id="get_temp_key">TempKey</button>
    </div>

<?php

$this->registerJsFile('/node_modules/when/dist/browser/when.js');
$this->registerJsFile('/static_files/autobahn.js');
$this->registerJsFile('/static_files/cos-auth.min.js');

$js = <<<JS
    ab.debug(true,true);

    var session = new ab.Session(
        // 'ws://127.0.0.1:8090',
        // 'ws://localhost:8090',
        'wss://www.goliveback.qualisafe.com.cn:8090',
        function() {
                session.subscribe('iip_base', function(topic, data) {
                console.log('published to topic "' + topic + '" : ' + data.title);
            });
        },
        function() {
       
            console.warn('WebSocket connection closed');
        },
        {'skipSubprotocolCheck': true}
    );

    $('#send_msg_rpc').on('click',function(e) {
        debugger
        session.call('rpc/live/rect-mark',100,90,100,200,888).promise.then(function(e) {
            console.log('result 111111111111111');
        });

    })
    
    $('#send_msg_rpc_m').on('click',function(e) {
      session.call('onsite/rpc/live/update-location',104.046215,30.634415,"16697029585f8088628f6b6709904359").promise.then(function(e) {
            console.log('result 222222222222222222222');
        });
    });
    
    var payload = {
        'uri':'pull/live/send-msg',
        'topic':'iip_base', 
        'data':
        {
             'room_id':100,
             'type':'text',
             'msg':'hello world'
        }
    };
    $('#send_msg').on('click',function(e) {
      session.publish('iip_base',JSON.stringify(payload));
    });
    
    
    /////////////////
            // 请求用到的参数
        // var Bucket = 'live-msg-file-1257704912';
        // var Region = 'ap-chengdu';
        // var protocol = location.protocol === 'https:' ? 'https:' : 'http:';
        // var prefix = protocol + '//' + Bucket + '.cos.' + Region + '.myqcloud.com/';

        // 对更多字符编码的 url encode 格式
        var camSafeUrlEncode = function (str) {
            return encodeURIComponent(str)
                .replace(/!/g, '%21')
                .replace(/'/g, '%27')
                .replace(/\(/g, '%28')
                .replace(/\)/g, '%29')
                .replace(/\*/g, '%2A');
        };

        // 计算签名
        var getAuthorization = function (type, ext, options, callback) {
            var url = '../cos/temp-key'
            var data=JSON.stringify({"type": type,"exts": ext});
            var xhr = new XMLHttpRequest();

            xhr.open('POST', url, true);
            xhr.setRequestHeader("Content-Type", "application/json");
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.onload = function (e) {
                debugger
                var credentials;
                var url;
                var path_name;
                try {
                    var res = JSON.parse(xhr.responseText);
                    credentials = res['data']['tmp_keys'].credentials;
                    url = res['data']['files'][0]['url'];
                    path_name = res['data']['files'][0]['path_name']
                    debugger
                } catch (e) {}
                if (credentials) {
                    callback(null, {
                        XCosSecurityToken: credentials.sessionToken,
                        Authorization: CosAuth({
                            SecretId: credentials.tmpSecretId,
                            SecretKey: credentials.tmpSecretKey,
                            Method: options.Method,
                            Pathname: path_name
                            //options.Pathname,
                        })
                    },url);
                } else {
                    console.error(xhr.responseText);
                    callback('获取签名出错');
                }
            };
            xhr.onerror = function (e) {
                callback('获取签名出错');
            };
            xhr.send(data);
        };

        // 上传文件
        var uploadFile = function (file, type, callback) {
            var Key = file.name; // 这里指定上传目录和文件名
            var ext = ['doc'];
            debugger
            getAuthorization(type, ext, {Method: 'PUT'}, function (err, info, url) {
                debugger
                if (err) {
                    alert(err);
                    return;
                }
                var auth = info.Authorization;
                debugger
                var XCosSecurityToken = info.XCosSecurityToken;
                // var url = prefix + camSafeUrlEncode(Key).replace(/%2F/g, '/');
                
                
                var xhr = new XMLHttpRequest();
                xhr.open('PUT', url, true);
                xhr.setRequestHeader('Authorization', auth);
                XCosSecurityToken && xhr.setRequestHeader('x-cos-security-token', XCosSecurityToken);
                xhr.upload.onprogress = function (e) {
                    console.log('上传进度 ' + (Math.round(e.loaded / e.total * 10000) / 100) + '%');
                };
                xhr.onload = function () {
                    if (xhr.status === 200 || xhr.status === 206) {
                        var ETag = xhr.getResponseHeader('etag');
                        callback(null, {url: url, ETag: ETag});
                    } else {
                        callback('文件 ' + Key + ' 上传失败，状态码：' + xhr.status);
                    }
                };
                xhr.onerror = function () {
                    callback('文件 ' + Key + ' 上传失败，请检查是否没配置 CORS 跨域规则');
                };
                xhr.send(file);
            });
        };
    
    $('#get_temp_key').on('click',function(e) {
            var file = document.getElementById('get_temp_key_file').files[0];
            var type = $('#get_temp_key_bucket').val();
                  
            if (!file) {
                alert('未选择上传文件');
                return;
            }
            file && uploadFile(file, type, function (err, data) {
                console.log(err || data);
               let text = err ? err : ('上传成功，ETag=' + data.ETag);
               alert(text);
            });
    })
    
    

JS;
$this->registerJs($js);

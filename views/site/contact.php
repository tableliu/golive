<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\ContactForm */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\captcha\Captcha;

$this->title = 'Contact';
$this->params['breadcrumbs'][] = $this->title;
?>

    <form action="/iip/web/onsite/video-job/save" method="post" enctype="multipart/form-data">
        <table>
            <tr>
                <td>视频库id<input type="text" name="v_id"/></td>
                <td>视频<input type="file" name="video"/></td>
                <td>开始时间<input type="text" name="start_time"/></td>
                <td>结束时间<input type="text" name="end_time"/></td>
                <td>经度<input type="text" name="longitude"/></td>
                <td>纬度<input type="text" name="latitude"/></td>
            </tr>
            <tr>
                <td>第一步骤<input type="text" id="step_id1" name="step_data[0][step_id]"/></td>
                <td>步骤时间轴<input type="text" name="step_data[0][timeline]"/></td>
                <td>照片<input type="file" name="step_data[0][image_data][0][image]"/></td>
                <td>图片的时间轴<input type="text" name="step_data[0][image_data][0][image_timeline]"/></td>
                <td>描述<input type="text" name="step_data[0][image_data][0][content]"/></td>
                <td>照片<input type="file" name="step_data[0][image_data][1][image]"/></td>
                <td>图片的时间轴<input type="text" name="step_data[0][image_data][1][image_timeline]"/></td>
                <td>描述<input type="text" name="step_data[0][image_data][1][content]"/></td>

            </tr>
            <tr>
                <td>第二步骤<input type="text" id="step_id1" name="step_data[1][step_id]"/></td>
                <td>步骤时间轴<input type="text" name="step_data[1][timeline]"/></td>
                <td>照片<input type="file" name="step_data[1][image_data][0][image]"/></td>
                <td>图片的时间轴<input type="text" name="step_data[1][image_data][0][image_timeline]"/></td>
                <td>描述<input type="text" name="step_data[1][image_data][0][content]"/></td>
                <td>照片<input type="file" name="step_data[1][image_data][1][image]"/></td>
                <td>图片的时间轴<input type="text" name="step_data[1][image_data][1][image_timeline]"/></td>
                <td>描述<input type="text" name="step_data[1][image_data][1][content]"/></td>
            </tr>
            <!--            <tr>-->
            <!--                <td>第三步骤<input type="text" id="step_id1" name="step_data[2][step_id]"/></td>-->
            <!--                <td>步骤时间轴<input type="text" name="step_data[2][timeline]"/></td>-->
            <!--                <td>照片<input type="file" name="step_data[2][image_data][0][image]"/></td>-->
            <!--                <td>图片的时间轴<input type="text" name="step_data[2][image_data][0][image_timeline]"/></td>-->
            <!--                <td>描述<input type="text" name="step_data[2][image_data][0][content]"/></td>-->
            <!--                <td>照片<input type="file" name="step_data[2][image_data][1][image]"/></td>-->
            <!--                <td>图片的时间轴<input type="text" name="step_data[2][image_data][1][image_timeline]"/></td>-->
            <!--                <td>描述<input type="text" name="step_data[2][image_data][1][content]"/></td>-->
            <!--            </tr>-->
        </table>
        <input type="submit" value="Submit"/>
    </form>


<div class="site-contact">
    <button id="send_msg_rpc_m">RPC</button>
    <h1><?= Html::encode($this->title) ?></h1>
    <input id="fileIpt" type="file" value="" name=""/>
</div>

<?php
$this->registerJsFile('/node_modules/when/dist/browser/when.js');
$this->registerJsFile('/static_files/autobahn.js');

$js = <<<JS
    var ipt = document.querySelector('#fileIpt')
    var uploadFile = function() {
      var file = ipt.files[0]
      var fileReader = new FileReader()
      fileReader.onload = function(e) {
        var base64 = e.target.result
        session.call('rpc/live/send-msg',14,'image', base64,'file.name').promise.then(function(e) {
            console.log('result 222222222222222222222');
        })
      }
      fileReader.readAsDataURL(file)
    }
    
    ipt.addEventListener('change', uploadFile)
    var session = new ab.Session('ws://192.168.3.20:8090',
        function() {
            session.subscribe('iip_base', function(topic, data) {
                // This is where you would add the new article to the DOM (beyond the scope of this tutorial)
                console.log('published to topic "' + topic + '" : ' + data.title);
            });
        },
        function() {
            console.warn('WebSocket connection closed');
        },
        {'skipSubprotocolCheck': true}
    );

    // var session2 = new ab.Session('ws://localhost:8080',
    //     function() {
    //         session.subscribe('com.myapp.oncounter2', function(topic, data) {
    //             // This is where you would add the new article to the DOM (beyond the scope of this tutorial)
    //             console.log('New article published to category "' + topic + '" : ' + data.title);
    //         });
    //     },
    //     function() {
    //         console.warn('WebSocket connection closed');
    //     },
    //     {'skipSubprotocolCheck': true}
    // );
   $('#send_msg_rpc_m').on('click',function(e) {
      session.call('rpc/live/send-msg',14,'image','JREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIlESiJREoiURKIv/2Q==','3434.png').promise.then(function(e) {
            console.log('result 222222222222222222222');
        });
    });
    
    var payload = {
        'uri':'onsite/rpc/live/send-msg',
        'topic':'iip_base', 
        'data':
        {
             'room_id':18,
             'type':'text',
             'msg':'hello world mobile'
        }
    };
    $('#send_msg').on('click',function(e) {
      session.publish('iip_base',JSON.stringify(payload));
    });


JS;
$this->registerJs($js);

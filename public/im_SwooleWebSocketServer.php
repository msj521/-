<?php
require_once('./RedisInstance.php');

function getRedis(){
    return  RedisInstance::getInstance();
}

class Websocket {

    public $server;
    public $dates;

    public function __construct() {
        $this->dates =date("Y-m-d H:i:s");
        $this->server = new swoole_websocket_server("0.0.0.0", 9502);
        //设置 websocket 进程名称
        swoole_set_process_name("Msj");
        $this->server->set(
            [   
				'worker_num' => 2, //worker进程数
				'task_worker_num' => 2, //task进程数
				//心跳检测,每62秒检测一次，30秒没活动就断开
				//'heartbeat_idle_time'=>600,//心跳空闲时间
				//'heartbeat_check_interval'=>60 //心跳检查间隔
            ]
        );
        $this->server->on('open', [$this,'onOpen']);
        $this->server->on('message', [$this,'onMessage']);
        $this->server->on('close', [$this,'onClose']);
        $this->server->on("task", [$this, 'onTask']);
        $this->server->on("finish", [$this, 'onFinish']);
        $this->server->start();
    }

    //监听WebSocket连接打开事件
    public function onOpen($server, $req){

		echo "{$this->dates}:{$req->fd}-建立了连接".PHP_EOL;
    }

    //监听WebSocket 消息事件
    public function onMessage($server, $frame){
		echo "{$this->dates}:{$frame->fd}-服务器发送消息".PHP_EOL;
        $request_data = json_decode($frame->data,true);
        $redis = getRedis();
        if($request_data['type']==1){
            $redis->hset('online_map',$request_data['user_id'],$frame->fd);
        }
        $db_conf = array(
            'host' => '47.100.226.80',
            'user' => 'root',
            'password' => 'msj586',
            'database' => 'webim',
        );
        $mysqlObj = new Swoole\MySQL;
        $save_add_time = date('Y-m-d H:i:s',time());
        $user_online_info = $server->connection_info($frame->fd);
        if(!isset($request_data['userToken'])){
            return false;
        }
        /* 
        {
            "type":2,
            "msg":"45378678",
            "user_id":"1",
            "user_name":"msj",
            "flag":"3",
            "send_head_url":"http://www.qqzhi.com/uploadpic/2014-09-23/000247589.jpg",
            "response_user":"msj1",
            "response_user_head_url":"http://www.qqzhi.com/uploadpic/2014-09-23/000247589.jpg",
            "userToken":"23ab718041acd4e573f9a10d66229edf"
        }
        array(11) {
                ["id"]=>
                string(1) "1"
                ["user_id"]=>
                string(1) "2"
                ["username"]=>
                string(4) "msj1"
                ["user_avatar"]=>
                string(55) "http://www.qqzhi.com/uploadpic/2014-09-23/000247589.jpg"
                ["response_id"]=>
                string(1) "1"
                ["response_name"]=>
                string(3) "msj"
                ["response_avatar"]=>
                string(55) "http://www.qqzhi.com/uploadpic/2014-09-23/000247589.jpg"
                ["content"]=>
                string(5) "11111"
                ["add_time"]=>
                string(19) "2018-11-15 22:24:52"
                ["flag"]=>
                string(1) "3"
                ["user_ip"]=>
                string(15) "203.110.178.229"
            }
            */
        //type==2
        if($request_data['type']==2){
            $response_id = $request_data['flag'] - $request_data['user_id'];
            $resopnse_frameId = $redis->hget('online_map',$response_id);
            $resopnse_user = json_decode($redis->hget('user_map',$request_data['user_id']),true);
            $save_user_id = $request_data['user_id'];
            $save_user_name = $request_data['user_name'];
            $save_user_head_url = $request_data['send_head_url'];
            $save_response_id = $response_id;
            $save_response_user = $request_data['response_user'];
            $save_response_user_head_url = $request_data['response_user_head_url'];
            $save_response_id = $response_id;
            $save_content = htmlspecialchars($request_data['msg']);
            $save_flag = $request_data['flag'];

            $sql = 'INSERT INTO chat(
                    user_id,
                    username,
                    user_avatar,
                    response_id,
                    response_name,
                    response_avatar,
                    content,
                    add_time,
                    flag,
                    user_ip
                    )
                VALUES ('
                .$save_user_id.','
                ."'".$save_user_name."'".','
                ."'".$save_user_head_url."'".','
                .$save_response_id.','
                ."'".$save_response_user."'".','
                ."'".$save_response_user_head_url."'".','
                ."'".$save_content."'".','
                ."'".$save_add_time."'".','
                ."'".$save_flag."'".','."'".$user_online_info['remote_ip']."'".');';
            $mysqlObj->connect($db_conf,function($db, $result) use ($sql){
                $db->query($sql, function (Swoole\MySQL $db, $result) {
                    if ($result === false) {
                        //var_dump($db->error, $db->errno);
                    } elseif ($result === true) {
                        ///var_dump($db->affected_rows, $db->insert_id);
                    } 
                    $db->close();
                });

            });

            if(!empty($resopnse_frameId)){
                $conn_info = $server->connection_info($resopnse_frameId);

                if($conn_info!=false) {
                    $server->push($resopnse_frameId,
                        json_encode(
                            array(
                                'type' => 2,
                                'data' => [
                                    'content' => $request_data['msg'],
                                    'flag' => $request_data['flag'],
                                    'add_time' => $save_add_time,
                                    'username' => $resopnse_user['username'],
                                    'head_url' => $request_data['send_head_url']
                                ],
                                'code' => '1111',
                                'msg' => ''
                            ), JSON_UNESCAPED_UNICODE));
                }
            }
        }
        //type==3
        if($request_data['type']==3){
            $sql = 'INSERT INTO allchat(
                  user_id,
                  username,
                  content,
                  user_ip,
                  add_time
                  )
                VALUES ('
                .$request_data['user_id'].','
                ."'".$request_data['user_name']."'".','
                ."'".htmlspecialchars($request_data['msg'])."'".','
                ."'".$user_online_info['remote_ip']."'".','
                ."'".$save_add_time."'".');';

            $mysqlObj->connect($db_conf,function($db, $result) use ($sql){
                $db->query($sql, function (Swoole\MySQL $db, $result) {
                    if ($result === false) {
                        //var_dump($db->error, $db->errno);
                    } elseif ($result === true) {
                        //var_dump($db->affected_rows, $db->insert_id);
                    } else {
                        //var_dump($result);
                        $db->close();
                    }
                });
            });
            foreach ($server->connections as $fd) {
                if($fd!=$frame->fd){
                    $server->push($fd, json_encode(
                        array(
                            'type'=>3,
                            'data'=>[
                                'content'=>$request_data['msg'],
                                'flag'=>$request_data['flag'],
                                'username'=>'群聊',
                                'add_time'=> $save_add_time,
                                'head_url'=>$request_data['send_head_url']
                            ],
                            'code'=>'1111',
                            'msg'=>"$fd"
                        ),JSON_UNESCAPED_UNICODE));
                }
            }
        }
		//worker进程异步投递任务到task_worker进程中
		//$server->task($request_data);
    }

    public function onTask($server, $task_id, $worker_id, $data){
		var_dump($data);

		//模拟慢速任务
		sleep(5);

		//返回字符串给worker进程——>触发onFinish
		return "success";
    }

    public function onFinish($server,$task_id, $data){
		//task_worker进程将任务处理结果发送给worker进程
		echo "{$this->dates}:{$task_id}-异步完成任务".PHP_EOL;
    }


    public function onClose($server, $fd){
		echo "{$this->dates}:服务断开".PHP_EOL;
        $redis = getRedis();
        $redis->del('person'.$fd);
        $redis->sRem('online',$fd);
    }

}
new Websocket();
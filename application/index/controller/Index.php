<?php
namespace app\index\controller;
use Mockery\CountValidator\Exception;
use think\Db;
class Index extends Common
{

    public function index() {
//        $redis = new \Redis();
//        $redis->connect('127.0.0.1','6379');
//        $redis->hset("msj",'2',"1");
//        //$redis->hget("user",1);
//        $data=json_decode($redis->hget('test',1),true);
//        var_dump($redis->hget('test',1));]
        //父进程也开启一个与子进程同样多的循环.
        //self::MSJ();
        //创建一个子进程
        //self::new_child("MSJ", "资进程", 10000);

        //运行结果, 我这里运行父进程输出50个左右, 子进程开始运行.

        return view();
    }
    /**
     * 创建子进程入口
     * @author selfimpr
     * @blog http://blog.csdn.net/lgg201
     * @mail lgg860911@yahoo.com.cn
     * @param $func_name 代表子进程处理过程的函数名
     * @param other 接受不定参数, 提供给子进程的过程函数.
     */
    public function new_child($func_name)
    {
        $args = func_get_args();
        unset($args[0]);
        $pid = pcntl_fork();
        if ($pid == 0) {
            function_exists($func_name) and exit(call_user_func_array($func_name, $args)) or exit(-1);
        }
        else if ($pid == -1) {
            echo "创建子进程失败";
        }
    }
//测试处理函数, 输出$prefix连接的数组
    public function MSJ()
    {
        declare(ticks=1);  
        $bWaitFlag = FALSE; /// 是否等待进程结束  
        $intNum = 10;           /// 进程总数  
        $pids = array();        ///  进程PID数组  
        echo ("Start\n");  
        for($i = 0; $i < $intNum; $i++) {  
        $pids[$i] = pcntl_fork();/// 产生子进程，而且从当前行之下开试运行代码，而且不继承父进程的数据信息  
        if(!$pids[$i]) {  
            // 子进程进程代码段_Start  
            $str="";  
            sleep(5+$i);  
            for ($j=0;$j<$i;$j++) {$str.="*";}  
            echo "$i -> " . time() . " $str \n";  
            exit();  
            // 子进程进程代码段_End  
        }  
        }  
        if ($bWaitFlag)  
        {  
        for($i = 0; $i < $intNum; $i++) {  
            pcntl_waitpid($pids[$i], $status, WUNTRACED);  
            echo "wait $i -> " . time() . "\n";  
        }  
        }  
        echo ("End\n");
    }
	//初始化首页聊天记录
    public function getChat(){
		//检测当前登录有效性
        checkRequest();
        $res = $this->checkToken();
        $user = [];
        if($res){
            $user = $res;
        }else{
            return ['code'=>1010,'data'=>'','msg'=>'登录过期,请重新登录！'];
        }

        $subQuery = Db::name('chat')
            ->alias('c')
            ->field(['c.*','u.head_url'])
            ->where('c.response_id','=',$user['user_id'])
            ->whereOr('c.user_id','=',$user['user_id'])
            ->join('__USERS__ u', 'u.user_id = c.user_id')
            ->order('c.id desc')
            ->buildSql();
        $chat = Db::table($subQuery . ' a')
            ->group('flag')
            ->order('add_time desc')
            ->select();
        $allchat = Db::name('allchat')->order('id desc')->find();
        $data['allchat'] = empty($allchat) ? []:$allchat;
        $data['chat'] = $chat;
        $data['user_id'] = $user['user_id'];
        if(!empty($chat)){
            return ['code'=>100,'data'=>$data,'msg'=>'success'];
        }
        if(empty($chat) && $allchat){
            return ['code'=>1000,'data'=>$data,'msg'=>'success'];
        }else{
            return ['code'=>101,'data'=>$data,'msg'=>'没有聊天内容！'];
        }

    }
	
	//获取聊天记录
    public function getChatByUser(){
        checkRequest();
        $res = $this->checkToken();
        $user = [];
        if($res){
            $user = $res;
        }else{
            return ['code'=>1010,'data'=>'','msg'=>'非法访问,请重新登录！'];
        }
        $flag = input('flag/d');
        $response_id = input('response_id/d');
        if($flag>0){
            $response_user = Db::name('users')->field('username,head_url')->where(['user_id'=>$response_id])->find();
            $chat = Db::name('chat')
                ->alias('c')
                ->field(['c.*','u.head_url'])
                ->where("c.response_id=".$user['user_id']." or c.user_id=".$user['user_id'])
                ->where('c.flag','=',$flag)
                ->join('__USERS__ u', 'u.user_id = c.user_id')
                ->order('c.id desc')
                ->limit(10)
                ->select();
            $data['chat'] = array_reverse($chat);
            $data['me'] = $user['user_id'];
            $data['response_user'] = $response_user['username'];
            $data['response_user_head_url'] = $response_user['head_url'];
        }else{
            $result = Db::name('allchat')
                ->alias('a')
                ->field(['a.*','u.head_url'])
                ->join('__USERS__ u', 'u.user_id = a.user_id')
                ->order('id desc')
                ->limit(10)
                ->select();
            $data['chat'] = empty($result) ? [] : array_reverse($result);
            $data['me'] = $user['user_id'];
            $data['response_user'] = '群聊';
            $data['response_user_head_url'] = '';
        }

        if(!empty($chat)){
            return ['code'=>100,'data'=>$data,'msg'=>'success'];
        }else{
            return ['code'=>101,'data'=>$data,'msg'=>'没有最新聊天内容！'];
        }
    }

	//聊天内容保存
    public function saveChat(){
        checkRequest();
        $res = $this->checkToken();
        $user = [];
        if($res){
            $user = $res;
        }else{
            return ['code'=>1010,'data'=>'','msg'=>'非法访问,请重新登录！'];
        }
        $flag = input('flag/d');
        $content = input('content/s');
        if($flag==0){
            $arr['user_id'] = $user['user_id'];
            $arr['username'] = $res['username'];
            $arr['content'] = $content;
            $arr['add_time'] = date('Y-m-d H:i:s',time());
            $chat = Db::name('allchat')->insert($arr);
        }else{
            $response_user = Db::name('users')->field('username,head_url')->where(['user_id'=>$flag-$user['user_id']])->find();
            $arr['user_id'] = $user['user_id'];
            $arr['username'] = $res['username'];
            $arr['user_avatar'] = $user['head_url'];
            $arr['response_id'] = $flag-$user['user_id'];
            $arr['response_name'] = $response_user['username'];
            $arr['response_avatar'] = $response_user['head_url'];
            $arr['content'] = $content;
            $arr['add_time'] = date('Y-m-d H:i:s',time());
            $arr['flag'] = $flag;
            $chat = Db::name('chat')->insert($arr);
        }
        if(!empty($chat)){
            return ['code'=>100,'data'=>'','msg'=>'success'];
        }else{
            return ['code'=>101,'data'=>'','msg'=>'添加失败！'];
        }
    }
	
	//获取用户列表
    public function getUserList(){
        checkRequest();
        $res = $this->checkToken();
        $user = [];
        if($res){
            $user = $res;
        }else{
            return ['code'=>1010,'data'=>'','msg'=>'非法访问,请重新登录！'];
        }
        $userList = Db::name('users')->field('user_id,username,head_url')->where('user_id','<>',$user['user_id'])->select();
        if(!empty($userList)){
            return ['code'=>100,'data'=>$userList,'msg'=>'success'];
        }else{
            return ['code'=>101,'data'=>'','msg'=>'查询失败！'];
        }
    }
}

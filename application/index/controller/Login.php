<?php
namespace app\index\controller;
use think\Db;
class Login
{
    public function login()
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1',6379);
        $password = '****';
        $redis->auth($password);
        $userlist = Db::name('users')->field('user_id,username,head_url')->select();
        foreach($userlist as $k=>$v){
            $redis->hset('user_map',$v['user_id'],json_encode($v));
        }
        return view();
    }

    public function loginHandle()
    {
        checkRequest();
        $username = input('username/s');
        $password = input('password');
        if(empty($username)){
            return ['code'=>101,'msg'=>'账号不能为空!'];
        }
        if(empty($password)){
            return ['code'=>102,'msg'=>'密码不能为空!'];
        }
        $map['username'] = htmlspecialchars($username);
        $map['password'] = md5(trim($password));
        $res =Db::name('users')->where($map)->find();
        if($res){
            $token = md5(uniqid(time()));
            $result = Db::name('users')->where(['user_id'=>$res['user_id']])->update(['token'=>$token]);
            $redis = new \Redis();
            $redis->connect('127.0.0.1',6379);
            $password = '****';
            $redis->auth($password);
            $redis->sadd("userlist",$res['user_id']);
            if($result){
                return [
                    'code'=>100,'msg'=>'登录成功!',
                    'data'=>$token,
                    'user_id'=>$res['user_id'],
                    'head_url'=>$res['head_url'],
                    'user_name'=>$res['username']
                ];
            }else{
                return ['code'=>103,'msg'=>'系统繁忙,请稍后再试!'];
            }
        }else{
            return ['code'=>104,'msg'=>'用户名或密码错误!'];
        }
    }

}

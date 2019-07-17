<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
class  Common  extends Controller
{

    public function checkToken()
    {
        $token = input('token/s');
        if(!empty($token)){
            $res = Db::name('users')->field('user_id,username,head_url,token')->where(['token'=>$token])->find();
            if(empty($res)){
                return false;
            }else{
                return $res;
            }
        }else{
            return false;
        }
    }

}

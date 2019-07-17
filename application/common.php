<?php

function p($arr)
{
    echo '<pre>';
    print_r($arr);
    echo '</pre>';
}

function d($arr)
{
    echo '<pre>';
    print_r($arr);
    echo '</pre>';
    die;
}
// 递归删除文件夹
function delFile($dir,$file_type='') {
    if(is_dir($dir)){
        $files = scandir($dir);
        //打开目录 //列出目录中的所有文件并去掉 . 和 ..
        foreach($files as $filename){
            if($filename!='.' && $filename!='..'){
                if(!is_dir($dir.'/'.$filename)){
                    if(empty($file_type)){
                        unlink($dir.'/'.$filename);
                    }else{
                        if(is_array($file_type)){
                            //正则匹配指定文件
                            if(preg_match($file_type[0],$filename)){
                                unlink($dir.'/'.$filename);
                            }
                        }else{
                            //指定包含某些字符串的文件
                            if(false!=stristr($filename,$file_type)){
                                unlink($dir.'/'.$filename);
                            }
                        }
                    }
                }else{
                    delFile($dir.'/'.$filename);
                    rmdir($dir.'/'.$filename);
                }
            }
        }
    }else{
        if(file_exists($dir)) unlink($dir);
    }
}

/**
 * 获取随机字符串
 */
function get_rand_str($length=6){
    $chars='abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $len=strlen($chars);
    $randStr='';
    for ($i=0;$i<$length;$i++){
        $randStr.=$chars[rand(0,$len-1)];
    }
    return $randStr;
}
/**
 * 获取随机4位数字
 */
function get_rand_num($length=4){
    $chars='1234567890';
    $len=strlen($chars);
    $randStr='';
    for ($i=0;$i<$length;$i++){
        $randStr.=$chars[rand(0,$len-1)];
    }
    return $randStr;
}

//不同环境下获取真实的IP
function get_ip(){
    //判断服务器是否允许$_SERVER
    if(isset($_SERVER)){
        if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
            $realip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }elseif(isset($_SERVER['HTTP_CLIENT_IP'])) {
            $realip = $_SERVER['HTTP_CLIENT_IP'];
        }else{
            $realip = $_SERVER['REMOTE_ADDR'];
        }
    }else{
        //不允许就使用getenv获取
        if(getenv("HTTP_X_FORWARDED_FOR")){
            $realip = getenv( "HTTP_X_FORWARDED_FOR");
        }elseif(getenv("HTTP_CLIENT_IP")) {
            $realip = getenv("HTTP_CLIENT_IP");
        }else{
            $realip = getenv("REMOTE_ADDR");
        }
    }

    return $realip;
}

/**
 * 检查手机号码格式
 */
function check_mobile($mobile){
    if(preg_match('/1[3|4|5|6|7|8|9]\d{9}$/',$mobile))
        return true;
    return false;
}
/**
 * 检查QQ码格式
 */
function check_qq($qq){
    if(preg_match('/[1-9][0-9]{4,}$/',$qq))
        return true;
    return false;
}
/**
 * 检查邮箱地址格式
 */
function check_email($email){
    if(filter_var($email,FILTER_VALIDATE_EMAIL))
        return true;
    return false;
}

/**
 * $url
 * $data
 * $method
 * string
 * post请求
 */
function curlPost($url,$data,$method){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER,array('Accept-Encoding: gzip, deflate'));
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
    if($method=="POST"){
        if(is_array($data)){
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }else{
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $tmpInfo = curl_exec($ch);
    if (curl_errno($ch)) {
        return curl_error($ch);
    }
    curl_close($ch);
    return $tmpInfo;
}

/**
 * 功能：生成二维码
 * $qrData 手机扫描后要跳转的网址
 * $qrLevel 默认纠错比例 分为L、M、Q、H四个等级，H代表最高纠错能力
 * $qrSize 二维码图大小，1－10可选，数字越大图片尺寸越大
 * $savePath 图片存储路径
 * $savePrefix 图片名称前缀
 */
function createQRcode($savePath, $qrData = 'code', $qrLevel = 'H', $qrSize = 4)
{

    if (!isset($savePath)) return '';
    //设置生成png图片的路径
    $PNG_TEMP_DIR = $savePath;

    //检测并创建生成文件夹
    if (!file_exists($PNG_TEMP_DIR)) {
        mkdir($PNG_TEMP_DIR,0777,true);
    }
    $filename = $PNG_TEMP_DIR . 'test.png';
    $errorCorrectionLevel = 'L';
    if (isset($qrLevel) && in_array($qrLevel, ['L', 'M', 'Q', 'H'])) {
        $errorCorrectionLevel = $qrLevel;
    }
    $matrixPointSize = 4;
    if (isset($qrSize)) {
        $matrixPointSize = min(max((int)$qrSize, 1), 10);
    }
    if (isset($qrData)) {
        if (trim($qrData) == '') {
            die('data cannot be empty!');
        }
        //生成文件名 文件路径+图片名字前缀+md5(名称)+.png
        $filename = $PNG_TEMP_DIR . md5($qrData . '|' . $errorCorrectionLevel . '|' . $matrixPointSize) . '.png';
        //开始生成
        \PHPQRCode\QRcode::png($qrData, $filename, $errorCorrectionLevel, $matrixPointSize, 2);
    } else {
        //默认生成
        \PHPQRCode\QRcode::png('PHP QR Code :)', $filename, $errorCorrectionLevel, $matrixPointSize, 2);
    }
    if (file_exists($PNG_TEMP_DIR . basename($filename)))
        return basename($filename);
    else
        return FALSE;
}

function validation_filter_id_card($id_card){
    if(strlen($id_card)==18){
        return idcard_checksum18($id_card);
    }elseif((strlen($id_card)==15)){
        $id_card=idcard_15to18($id_card);
        return idcard_checksum18($id_card);
    }else{
        return false;
    }
}
// 计算身份证校验码，根据国家标准GB 11643-1999
function idcard_verify_number($idcard_base){
    if(strlen($idcard_base)!=17){
        return false;
    }
    //加权因子
    $factor=array(7,9,10,5,8,4,2,1,6,3,7,9,10,5,8,4,2);
    //校验码对应值
    $verify_number_list=array('1','0','X','9','8','7','6','5','4','3','2');
    $checksum=0;
    for($i=0;$i<strlen($idcard_base);$i++){
        $checksum += substr($idcard_base,$i,1) * $factor[$i];
    }
    $mod=$checksum % 11;
    $verify_number=$verify_number_list[$mod];
    return $verify_number;
}
// 将15位身份证升级到18位
function idcard_15to18($idcard){
    if(strlen($idcard)!=15){
        return false;
    }else{
        // 如果身份证顺序码是996 997 998 999，这些是为百岁以上老人的特殊编码
        if(array_search(substr($idcard,12,3),array('996','997','998','999')) !== false){
            $idcard=substr($idcard,0,6).'18'.substr($idcard,6,9);
        }else{
            $idcard=substr($idcard,0,6).'19'.substr($idcard,6,9);
        }
    }
    $idcard=$idcard.idcard_verify_number($idcard);
    return $idcard;
}
// 18位身份证校验码有效性检查
function idcard_checksum18($idcard){
    if(strlen($idcard)!=18){
        return false;
    }
    $idcard_base=substr($idcard,0,17);
    if(idcard_verify_number($idcard_base)!=strtoupper(substr($idcard,17,1))){
        return false;
    }else{
        return true;
    }
}

//检测当前登录有效性
function checkRequest()
{
    $time = input('time/d');
    $sign = input('sign/s');
    $randstr = input('randstr/s');
    if(time()-$time>40){
        return ['code'=>101,'msg'=>'请求超时！','data'=>''];
    }
    $str = '';
    for($i=0;$i<10;$i++){
        $str.=$time[$i].$randstr[9-$i];
    }
    if($str!=$sign){
        return ['code'=>102,'msg'=>'签名有误！','data'=>''];
    }
    return ;
}



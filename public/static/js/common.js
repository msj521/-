function  commonAjaxSubmit(url,formid,status,type,back){
	var type = type?type:'post';
	$.ajax({
		'url':url,
		'data':$('#'+formid).serialize(),
		'dateType':'json',
		'type':type,
		success:function(d){
			
			if(d.code==100){
				layer.msg(d.msg,{'icon':6});
				setTimeout(function(){
					if(status==1){
						history.go(-1);
					}
					if(status==2){
						location.href=back;
					}
				},1000);
			}else{
				layer.msg(d.msg,{'icon':5});
			}
			
		},
		error:function(){
			layer.alert('网络异常!',{'icon':5});
		},
	})
}

function commonAjaxDelete(url,data,obj){
	var layers = layer.confirm('您确定要删除吗？', {
	  btn: ['确定','取消'] 
	}, function(){
	  	$.ajax({
			'url':url,
			'data':data,
			'dateType':'json',
			'type':'post',
			success:function(d){
				if(typeof d=='string'){
					var d = JSON.parse(d);
					layer.alert(d.msg,{'icon':5});
					return
				}
				if(d.code==100){
					layer.msg(d.msg,{'icon':6});
					$(obj).parent().parent().remove();
				}else{
					layer.msg(d.msg,{'icon':5});
				}
			},
			error:function(){
				layer.alert('网络异常!',{'icon':5});
			},
		})
	}, function(){
	  	layer.close(layers);
	});
	
}

function clickTrans(o){
	$(o).siblings('input').click();
}



function uploadImg(url,o,id,filedir){  //此处用了change事件，当选择好图片打开，关闭窗口时触发此事件
	$.ajaxFileUpload({
		url:url,   //处理图片的脚本路径
		type: 'post',       //提交的方式
		secureuri :false,   //是否启用安全提交
		data:{'filedir':filedir},
		fileElementId :id,     //file控件ID
		dataType : 'json',  //服务器返回的数据类型
		success : function (data, status){  //提交成功后自动执行的处理函数

			if(data.code==1){
				var url = data.url;
				$('#'+id).siblings('img').attr('src',url);
				$('#'+id).siblings('input').val(data.url);
			}else{
				alert('上传错误!');
			}
		},
		error: function(data, status, e){   //提交失败自动执行的处理函数
			//console.log(data);
		}
	})
}

function isUrl(str_url){
	var strRegex = "^((https|http|ftp|rtsp|mms)?://)"
		+ "?(([0-9a-z_!~*'().&=+$%-]+: )?[0-9a-z_!~*'().&=+$%-]+@)?" //ftp的user@
		+ "(([0-9]{1,3}\.){3}[0-9]{1,3}" // IP形式的URL- 199.194.52.184
		+ "|" // 允许IP和DOMAIN（域名）
		+ "([0-9a-z_!~*'()-]+\.)*" // 域名- www.
		+ "([0-9a-z][0-9a-z-]{0,61})?[0-9a-z]\." // 二级域名
		+ "[a-z]{2,6})" // first level domain- .com or .museum
		+ "(:[0-9]{1,4})?" // 端口- :80
		+ "((/?)|" // a slash isn't required if there is no file name
		+ "(/[0-9a-z_!~*'().;?:@&=+$,%#-]+)+/?)$";
	var re=new RegExp(strRegex);
	if (re.test(str_url)){
		return true;
	}else{
		return false;
	}
}

/**
 * 邮箱格式判断
 * @param str
 */
function checkEmail(str){
	var reg = /^[a-z0-9]([a-z0-9\\.]*[-_]{0,4}?[a-z0-9-_\\.]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+([\.][\w_-]+){1,5}$/i;
	if(reg.test(str)){
		return true;
	}else{
		return false;
	}
}
/**
 * 手机号码格式判断
 * @param tel
 * @returns {boolean}
 */
function checkMobile(tel) {
	var reg = /(^1[3|4|5|6|7|8|9][0-9]{9}$)/;
	if (reg.test(tel)) {
		return true;
	}else{
		return false;
	};
}
/**
 * 获取时间戳10位
 * @returns {string}
 */
function getTimestamp() {
	var tmp = Date.parse( new Date() ).toString();
	tmp = tmp.substr(0,10);
	return tmp;
}

function randomString(len) {
	len = len || 32;
	var $chars = 'ABCDEFGHJKMNPQRSTWXYZabcdefhijkmnprstwxyz2345678';
	var maxPos = $chars.length;
	var pwd = '';
	for (i = 0; i < len; i++) {
		pwd += $chars.charAt(Math.floor(Math.random() * maxPos));
	}
	return pwd;
}


function createSign(time_data,rand_str_data){
	var str = '';
	for(var i=0;i<10;i++){
		str+=time_data[i]+rand_str_data[9-i];
	}
	return str;
}

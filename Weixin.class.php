<?php 
/*
微信高级接口类
*/

class Weixin{
	private $appid;
	private $appsecret;
	public $access_token;
	public function __construct($appid = MYID,$appsecret = MYSECRET){
		$this->appid = $appid;
		$this->appsecret = $appsecret;
		//获取token
		$this->accessToken();
	}

	public function accessToken(){
		$mysql = new Mysql();
		$time = time();
		$lasttime = $mysql->getRow("select * from token where id= 1;");
		if($time > ($lasttime['lasttime']+7000)){
			//token过期了或快过期了,去取token
			$appid = $this->appid;
			$secret = $this->appsecret;
			$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret;
			$a = $this->https_request($url);
			$strjson = json_decode($a);//json解析成对象
			$access_token = $strjson->access_token;//获取access_token
			$this->access_token = $access_token;
			$data = array('ak'=>$access_token,'lasttime'=>$time);
			$mysql->Exec('token',$data,'update','id=1');//将新token更新到数据库
		}else{
			//token还没过期,直接拿数据库中的token
			$this->access_token = $lasttime['ak'];
		}
	}

	//HTTP请求(get和post)
	//没有第二个参数执行GET请求,如果有第二个参数则执行POST请求
	//返回json数据
	public function https_request($url,$data = null){
		$curl = curl_init();
		curl_setopt($curl,CURLOPT_URL,$url);
		curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,FALSE);
		curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,FALSE);
		if(!empty($data)){
			curl_setopt($curl,CURLOPT_POST,1);
			curl_setopt($curl,CURLOPT_POSTFIELDS,$data);
		}
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
		$output = curl_exec($curl);
		curl_close($curl);
		return $output;
	}

	//获取用户基本信息
	//传入一个openid 返回一个数组
	public function get_user_info($openid){
		$url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$this->access_token."&openid=".$openid;
		$res = $this->https_request($url);
		return json_decode($res,true);
	}

	//获取关注者列表 默认next_openid为null 不填参数表示获取前10000个用户的openid 返回一个数组
	public function get_user_list($next_openid = null){
		$url = "https://api.weixin.qq.com/cgi-bin/user/get?access_token=".$this->access_token."&next_openid=".$next_openid;
		$res = $this->https_request($url);
		return json_decode($res,true);
	}

	//创建菜单 传入一个json字符串,发送POST数据
	public function create_menu($data){
		$url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$this->access_token;
		$res = $this->https_request($url,$data);
		return json_decode($res,true);
	}
	
	//删除菜单 成功返回true 失败返回false
	public function delete_menu(){
		$url = "https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=".$this->access_token;
		$result = $this->https_request($url);
		$arr = json_decode($result,true);
		if($arr['errcode'] == 0){
			return true;
		}else{
			return false;
		}
	}

	//创建分组 返回一个数组如array('id'=>100,'name'="$name");
	public function create_group($name){
		$data = '"{"group":{"name":"'.$name.'"}}"';
		$url = "https://api.weixin.qq.com/cgi-bin/groups/create?access_token=".$this->access_token;
		$result = $this->https_request($url,$data);
		$arr = json_decode($result,true);
		return $arr['group'];
	}

	//下载图片 默认保存为.jpg格式
	public function down_media($mediaid,$type='.jpg'){
		$url = "https://file.api.weixin.qq.com/cgi-bin/media/get?access_token=".$this->access_token."&media_id=".$mediaid;
		$fileInfo = $this->downfile($url);
		$path = $this->createDir();
		$name = $this->randStr().$type;
		$filename = '.'.$path.'/'.$name;
		$this->savefile($filename,$fileInfo['body']);
		return $filename;
	}
	
	//保存头像
	public function down_headimg($url){
		$file = $this->downfile($url);
		$path = '/download/headimg';
		$fpath = ROOT.$path;
		$name = $this->randStr(8).'.jpg';
		$filename = '.'.$path.'/'.$name;
		if(is_dir($fpath)||mkdir($fpath,0777,true)){
			$this->savefile($filename,$file['body']);
			return $filename;
		}
		return false;
	}

	//下载文件 输出数组array('header'=>'','body'=>'');
	public function downfile($url){
		$ch = curl_init($url);
		curl_setopt($ch,CURLOPT_HEADER,0);
		curl_setopt($ch,CURLOPT_NOBODY,0);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		$package = curl_exec($ch);
		$httpinfo = curl_getinfo($ch);
		curl_close($ch);
		$imageAll = array_merge(array('header'=>$httpinfo),array('body'=>$package));
		return $imageAll;
	}
	//保存下载下来的文件
	public function savefile($filename,$filecontent){
		$localfile = fopen($filename,'w');
		fwrite($localfile,$filecontent);
		fclose($localfile);
	}
	//获取一个6位随机字符串
	public function randStr($len = 6){
		$str = str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjklmnpqrstuvwxyz23456789');
		$str = substr($str, 0 , $len);
		return $str;
	}

	//按日期创建download目录
	public function createDir(){
		$path = '/download/'.date('Y/m/d');
		$fpath = ROOT.$path;
		if(is_dir($fpath)||mkdir($fpath,0777,true)){
			return $path;
		}else{
			return false;
		}
	}

	//获取OAuth2.0授权code
	public function authUrl($url){
		$url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$this->appid."&redirect_uri=".$url."&response_type=code&scope=snsapi_userinfo&state=1#wechat_redirect";	
		return $url;
	}
	//获取到授权code之后
	public function getInfo($code){
		$url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$this->appid."&secret=".$this->appsecret."&code=".$code."&grant_type=authorization_code";
		$data = $this->https_request($url);
		$data = json_decode($data);
		$url = "https://api.weixin.qq.com/sns/userinfo?access_token=".$data->access_token."&openid=".$data->openid;
		$data = $this->https_request($url);
		$res = json_decode($data,true);
		return $res;
	}
}


?>
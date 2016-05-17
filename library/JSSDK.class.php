<?php
/**
 * 作者:一个放羊娃
 */
class JSSDK{
    private $appId;
    private $appSecret;
    public function __construct($appid = MYID,$appSecret = MYSECRET){
        $this->appId = $appid;
        $this->appSecret = $appSecret;
    }
    public function getSignPackage(){
        $jsapiTicket = $this->getJsApiTicket();
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443)? "https://":"http://";
        $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $timestamp = time();
        $nonceStr = $this->createNonceStr();
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
        $signature = sha1($string);
        $signPackage = array(
            "appId"=>$this->appId,
            "nonceStr"=>$nonceStr,
            "timestamp"=>$timestamp,
            "url"=>$url,
            "signature"=>$signature,
            "rawString"=>$string
        );
        return $signPackage;
    }

    private function createNonceStr($length = 16){
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ123456789";
        $str = "";
        for($i=0;$i<$length;$i++){
            $str .=substr($chars,mt_rand(0,strlen($chars) -1),1);
        }
        return $str;
    }

    private function getJsApiTicket(){
        $data = json_decode(file_get_contents(ROOT."/json/jsapi_ticket.json"));
        if($data->expire_time <time()){
            $wx = new Weixin();
            $accessToken = $wx->access_token;
            //企业号用以下url
            //$url = "https://qyapi.weixin.qq.com/cig-bin/get_jsapi_ticket?access_token=$accessToken";
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
            $res = json_decode($wx->https_request($url));
            $ticket = $res->ticket;
            if($ticket){
                $data->expire_time = time() + 7000;
                $data->jsapi_ticket = $ticket;
                $fp = fopen(ROOT."/json/jsapi_ticket.json","w");
                fwrite($fp,json_encode($data));
                fclose($fp);
            }
        }else{
            $ticket = $data->jsapi_ticket;
        }
        return $ticket;
    }
}

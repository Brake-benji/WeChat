<?php
//WeChat sample
//define your token
require_once('./sendMsg.php');
define("TOKEN", "weixin");
$wechatObj = new wechatCallbackapiTest();
$wechatObj->responseMsg();
$wechatObj->valid();

class wechatCallbackapiTest
{
	public function valid()
    {
        $echoStr = $_GET["echostr"];

        //valid signature , option
        if($this->checkSignature()){
            echo $echoStr;
            exit();
        }
    }

    public function responseMsg()
    {
		//get post data, May be due to the different environments
		$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        //处理消息类型，并设置回复的消息和内容，并把微信推送过来的XML转换为字符串
        libxml_disable_entity_loader(true);
        $postObj = simplexml_load_string($postStr,'SimpleXMLElement',LIBXML_NOCDATA);
        //判断是否为订阅事件
        if(strtolower($postObj->MsgType == "event")){
            //事件消息。如果是关注走这里
            if (strtolower($postObj->Event == "subscribe")) {
                /*//回复纯文本
                $Content = "欢迎关注青椒白饭的微信公众号，相关功能还在开发当中，你可以试试发送语音“点歌+歌名”试试点歌功能！";
                $subscribe = new sendMsg();
                $subscribe->autoMsg($postObj,$Content);*/

                //回复图文消息
                $Article = array(
                    array(
                        'title' => "欢迎关注青椒白饭的公众号",
                        'description' => "欢迎关注和调戏，这里有不少新功能，都是自己开发玩玩的哈哈！",
                        'picurl' => 'http://img2.imgtn.bdimg.com/it/u=3334080215,3872286295&fm=21&gp=0.jpg',
                        'url' => 'http://www.xx-star.cn'
                        ),
                    );
                $subscribe = new sendMsg();
                $subscribe->autoPics($postObj,$Article);
            }
        }

        //自动回复，纯文本和图文推送，语音回复
        if(strtolower($postObj->MsgType == "text") && strtolower($postObj->Content) == "图文"){
             $Articles = array(
                        array(
                    "title" => "测试内容，多图文1",
                    "description" => "测试内容，点击跳转至度娘",
                    "picurl" => "http://www.baidu.com/img/bd_logo1.png",
                    "url" => "http://www.baidu.com"
                ),
                        array(
                    "title" => "测试内容，多图文2",
                    "description" => "测试内容，点击后跳转至新浪微博",
                    "picurl" => "http://img4.imgtn.bdimg.com/it/u=3417992050,1165765150&fm=21&gp=0.jpg",
                    "url" => "http://www.weibo.com"
                ),
                        );
             $news = new sendMsg();
             $news->autoPics($postObj,$Articles);
        }else if(strtolower($postObj->MsgType == "text")){
            if (strpos($postObj->Content,'天气') !== false) {
                $Content = trim($postObj->Content);  //天气+城市
                $weather = new sendMsg();
                $weather->getWeather($postObj,$Content);

            }else if(strpos($postObj->Content, '手机号码') !== false){
                $Content = trim($postObj->Content);  //手机号码+号码
                $phone = new sendMsg();
                $phone->getPhone($postObj,$Content);

            }else if(strpos($postObj->Content, '点歌') !== false){
                $Content = $postObj->Content;
                $msg = preg_replace("/^\x{4e00}-\x{9fa5}/u", '', $Content);  //点歌+歌名
                $music = new sendMsg();
                $music->autoMusic($postObj,$msg);

            }else{
                switch (strtolower(trim($postObj->Content))) {
                case '联系方式':
                    $Content = "18814109396";
                    break;
                case '个人网站':
                    $Content = "<a href='http://www.xx-star.cn'>个人网站入口</a>";
                    break;
                case '个人信息':
                    $Content = "谢新，本科，计算机专业，热爱互联网，PHPer,在成为大牛的路上努力着！喜欢打篮球，热爱音乐，外向兼内向的boy!";
                    break;
                default :
                    $Content = "什么，你说我帅？";
                    break;
                }
            $msg = new sendMsg();
            $msg->autoMsg($postObj,$Content);
            }            
        }else if(strtolower($postObj->MsgType) == "voice"){
            //语音点歌功能
            if(isset($postObj->Recognition) && !empty($postObj->Recognition)){
                $Content = $postObj->Recognition;  //获取语音识别结果
            }else{
                $Content = "请确保开启语音识别功能，内容无法识别或者为空，请重试！语音点歌格式为：“点歌+歌名”，如“点歌喜欢你”，玩玩吧嘻嘻！";
                $msg = new sendMsg();
                $msg->autoMsg($postObj,$Content);
            }
            $msg = preg_replace("/^\x{4e00}-\x{9fa5}/u", '', $Content);  //对非法字符进行替换
            if (strpos($msg,'点歌') !== false) {
                //判断识别语音内容，要有关键词
                $music = new sendMsg();
                $music->autoMusic($postObj,$msg);
            }              
        }
    }
		
	private function checkSignature()
	{
        // you must define TOKEN by yourself
        if (!defined("TOKEN")) {
            throw new Exception('TOKEN is not defined!');
        }
        
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        		
		$token = TOKEN;
		$tmpArr = array($token, $timestamp, $nonce);
        // use SORT_STRING rule
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		
		if( $tmpStr == $signature ){
            return true;
		}else{
			return false;
		}
	}

}

?>
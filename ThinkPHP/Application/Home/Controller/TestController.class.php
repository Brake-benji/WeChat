<?php
namespace Home\Controller;
use Home\Model\IndexModel;
use Think\Controller;

class IndexController extends Controller {
    public function index(){
        //获得signature nonce token timestamp
        $nonce = $_GET['nonce'];
        $token = 'weixin';
        $timestamp = $_GET['timestamp'];
        $echostr = $_GET['echostr'];
        $signature = $_GET['signature'];
        //形成数组然后按字典序排序
        $array = array();
        $array = array($nonce,$timestamp,$token);
        sort($array);
        //拼接成字符串，sha1加密，然后与signature进行比对
        $str = sha1(implode($array));
        if ($str == $signature && $echostr) {
            //第一次接入微信api接口的时候
            echo $echostr;
            exit();
        }else{
            $this->responseMsg();  //调用回复方法
        }
    }

    //接受时间推送并回复
    public function responseMsg(){
        //获取微信推送的数据，xml格式
        $postArr = $GLOBALS['HTTP_RAW_POST_DATA'];
        //处理消息类型，把微信xml，转为字符串，并设置相关的回复内容
        libxml_disable_entity_loader(true);
        $postObj = simplexml_load_string($postArr,'SimpleXMLElement',LIBXML_NOCDATA);
        //判断该数据包是否是订阅的事件推送
        if (strtolower($postObj->MsgType == "event")) {
            //如果是关注走这里
            if (strtolower($postObj->Event) == "subscribe") {
                $Content = "欢迎关注青椒白饭的微信公众号，相关功能还在开发当中，你可以试试发送语音“点歌+歌名”试试点歌功能！";
                $this->subscribed($postObj,$Content);
            }
        }

        //自动回复，纯文本和图文推送，语音回复
        if(strtolower($postObj->MsgType == "text") && strtolower($postObj->Content) == "图文"){
             $Article = array(
                        array(
                    "title" => "【今日头条】富顺一个80的老人流浪汉被拒载，高中生花钱给...",
                    "description" => "百度的描述",
                    "picurl" => "http://www.baidu.com/img/bd_logo1.png",
                    "url" => "http://www.baidu.com"
                ),
                        array(
                    "title" => "你为什么不好好参加同学会，来我们好好谈谈",
                    "description" => "微博的描述",
                    "picurl" => "http://u1.img.mobile.sina.cn/public/files/image/620x300_img570b26e084465.png",
                    "url" => "http://www.weibo.com"
                ),
                        );
            $this->autoPics($postObj,$Article);
        }else if (strtolower($postObj->MsgType == "text")) {
            switch (strtolower(trim($postObj->Content))) {
                case '联系方式':
                    $Content = "18814109396";
                    break;
                case '个人网站':
                    $Content = "<a href='http://www.xx-star.cn'>个人网站入口</a>";
                    break;
                case 'abc':
                    $Content = "<a href='http://www.xx-star.cn'>个人网站入口</a>";
                    break;
                case '个人信息':
                    $Content = "谢新，本科，计算机专业，热爱互联网，PHPer,在成为大牛的路上努力着！喜欢打篮球，热爱音乐，外向兼内向的boy!";
                    break;
                default :
                    $Content = "什么，你说我帅？";
                    break;
            }
            $this->autoMsg($postObj,$Content);
        }else if(strtolower($postObj->MsgType) == "voice"){
            //语音点歌功能
            if(isset($postObj->Recognition) && !empty($postObj->Recognition)){
                $Content = $postObj->Recognition;  //获取语音识别结果
            }else{
                $Content = "请确保开启语音识别功能，内容无法识别或者为空，请重试！语音点歌格式为：“点歌+歌名”，如“点歌喜欢你”，玩玩吧嘻嘻！";
                $this->autoMsg($postObj,$Content);
            }
            $msg = preg_replace("/^\x{4e00}-\x{9fa5}/u", '', $Content);  //对非法字符进行替换
            if (strpos($msg,'点歌') !== false) {
                //判断识别语音内容，要有关键词
                $this->autoMusic($postObj,$msg);
            }              
        }
    }

    //关注事件回复
    public function subscribed($postObj,$Content){
        $toUsername = $postObj->FromUserName;
        $fromUsername = $postObj->ToUser;
        $time = time();
        $msgType = "text";
        $msgTpl = "<xml>
                   <ToUserName><![CDATA[%s]]></ToUserName>
                   <FromUserName><![CDATA[%s]]></FromUserName>
                   <CreateTime>%s</CreateTime>
                   <MsgType><![CDATA[%s]]></MsgType>
                   <Content><![CDATA[%s]]></Content>
                   </xml>";
        $result = sprintf($msgTpl,$toUsername,$fromUsername,$time,$msgType,$Content);
        echo $result;
    }

    //自动回复消息
    public function autoMsg($postObj,$Content){
        $toUsername = $postObj->FromUserName;
        $fromUsername = $postObj->ToUser;
        $time = time();
        $msgType = "text";
        $msgTpl = "<xml>
                   <ToUserName><![CDATA[%s]]></ToUserName>
                   <FromUserName><![CDATA[%s]]></FromUserName>
                   <CreateTime>%s</CreateTime>
                   <MsgType><![CDATA[%s]]></MsgType>
                   <Content><![CDATA[%s]]></Content>
                   </xml>";
        $result = sprintf($msgTpl,$toUsername,$fromUsername,$time,$msgType,$Content);
        echo $result;
    }

    //语音点歌功能
    public function autoMusic($postObj,$msg){
        $keyword = mb_substr($msg,2,mb_strlen($msg,'utf-8'),'utf-8');  //截取歌名
        $url = "http://s.music.163.com/search/get/?type=1&s=[$keyword]&limit=1";  //接口API地址
        $content = file_get_contents($url);  //获取接口数据到当前

        //转换接口Json数据为数组
        $music = json_decode($content,true);
        $musicUrl = $music["result"]["songs"]["0"]["audio"];  //歌曲地址
        $title = $music["result"]["songs"]["0"]["name"];  //歌曲名称
        $singer = $music["result"]["songs"]["0"]["artists"]["0"]["name"];  //歌手名称

        $toUser = $postObj->FromUserName;
        $fromUserName = $postObj->ToUserName;
        $time = time();
        $msgType = "music";
        $musicTpl = "<xml>
                     <ToUserName><![CDATA[%s]]></ToUserName>
                     <FromUserName><![CDATA[%s]]></FromUserName>
                     <CreateTime>%s</CreateTime>
                     <MsgType><![CDATA[%s]]></MsgType>
                     <Music>
                     <Title><![CDATA[%s]]></Title>
                     <Description><![CDATA[%s]]></Description>
                     <MusicUrl><![CDATA[%s]]></MusicUrl>
                     <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
                     </Music>
                     </xml>";
        $info = sprintf($musicTpl,$toUser,$fromUserName,$time,$msgType,$title,$singer,$musicUrl,$musicUrl);
        echo $info;

    }

    //图文回复功能
    public function autoPics($postObj, $Articles)
    {
        $toUsername = $postObj->FromUserName;
        $FromUsername = $postObj->ToUserName;
        $time = time();
        $msgType = "news";
        //$Content="感谢关注我的个人微信".$FromUserName;
        $template = "<xml>
                            <ToUserName><![CDATA[%s]]></ToUserName>
                            <FromUserName><![CDATA[%s]]></FromUserName>
                            <CreateTime>%s</CreateTime>
                            <MsgType><![CDATA[%s]]></MsgType>
                            <ArticleCount>" . count($Articles) . "</ArticleCount>
                            <Articles>";
        foreach ($Articles as $key => $value) {
            $template .= "
                            <item>
                            <Title><![CDATA[" . $value['title'] . "]]></Title>
                            <Description><![CDATA[" . $value['description'] . "]]></Description>
                            <PicUrl><![CDATA[" . $value['picurl'] . "]]></PicUrl>
                            <Url><![CDATA[" . $value['url'] . "]]></Url>
                            </item>";
        }
        $template .= " 
                            </Articles>
                            </xml>";
        $result = sprintf($template, $toUsername, $FromUsername, $time, $msgType);
        echo $result;
    }

    public function httpCurl(){
        //1.初始化curl
        $ch = curl_init();
        $url = 'http://www.baidu.com';
        //2.设置curl的参数
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //3.采集
        $output = curl_exec($ch);
        //4.关闭
        curl_close($ch);
        var_dump($output);
    }

    //获取access_token
    public function getAccessToken(){
        //1.请求地址
        $appid = 'wxc54bbd960bbd2dae';
        $appsecret = 'fd5db7f8eaedf8f99f31328913031424';
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$appsecret";
        //2.初始化
        $ch = curl_init();
        //3.设置参数
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //4.调用接口
        $result = curl_exec($ch);
        //5.关闭curl
        curl_close($ch);
        if (curl_error($ch)) {
            var_dump(curl_error($ch));
        }
        $arr = json_decode($result,true);
        var_dump($arr);
    }

    //获取微信服务器IP地址
    public function getServerIP(){
        $accessToken = "45Pwpc4WYRxjnymnXAxugJCsymWF9sju0bkqyuELv95UFLsbd1qGp5AC7rqbC2xPq-051o9QWv2DcRcZZNaiqNnmigkTNpm0FaseL8q11YRt-N1YiNWCrx8qNybuvfJBYKIhABAMYW";
        $url = "https://api.weixin.qq.com/cgi-bin/getcallbackip?access_token=$accessToken";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        if (curl_error($ch)) {
            var_dump(curl_error($ch));
        }
        $arr = json_decode($result，true);
        echo '<pre>';
        var_dump($result);
    }

}
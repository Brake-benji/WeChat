<?php
namespace Home\Controller;
use Home\Model\IndexModel;
use Think\Controller;

class IndexController extends Controller{
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
                //回复纯文本
                /*$Content = "欢迎关注青椒白饭的微信公众号，相关功能还在开发当中，你可以试试发送语音“点歌+歌名”试试点歌功能！";
                $subscribe = new IndexModel();
                $subscribe->subscribed($postObj,$Content);*/

                //回复图文消息
                $Article = array(
                    array(
                        'title' => "欢迎关注青椒白饭的公众号",
                        'description' => "欢迎关注和调戏，这里有不少新功能，都是自己开发玩玩的哈哈！",
                        'picurl' => 'http://img2.imgtn.bdimg.com/it/u=3334080215,3872286295&fm=21&gp=0.jpg',
                        'url' => 'http://www.xx-star.cn'
                        ),
                    );
                $news = new IndexModel();
                $news->autoPics($postObj,$Article);
            }

            //如果是重扫二维码
            if (strtolower($postObj->Event) == "scan") {
                if ($postObj->EventKey == 2000) {
                    //临时二维码扫码
                    $tmp = '临时二维码接口展示！';
                }
                if ($postObj->EventKey == 5000) {
                    $tmp = '永久二维码展示！';
                }

                $arr = array(
                    array(
                        'title' => $tmp,
                        'description' => '扫码测试事件！',
                        'picurl' => 'http://img2.imgtn.bdimg.com/it/u=3334080215,3872286295&fm=21&gp=0.jpg',
                        'url' => 'http://www.qq.com',
                        ));
                $news = new IndexModel();
                $news->autoPics($postObj,$arr);
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
             $news = new IndexModel();
             $news->autoPics($postObj,$Article);
        }else if(strtolower($postObj->MsgType == "text")){
            if (strpos($postObj->Content,'天气') !== false) {
                $Content = trim($postObj->Content);  //天气+城市
                $weather = new IndexModel();
                $weather->getWeather($postObj,$Content);

            }else if(strpos($postObj->Content, '手机号码') !== false){
                $Content = trim($postObj->Content);  //手机号码+号码
                $phone = new IndexModel();
                $phone->getPhone($postObj,$Content);

            }else if(strpos($postObj->Content, '点歌') !== false){
                $Content = $postObj->Content;
                $msg = preg_replace("/^\x{4e00}-\x{9fa5}/u", '', $Content);  //点歌+歌名
                $music = new IndexModel();
                $music->autoMusic($postObj,$msg);

            }else{
                switch (strtolower(trim($postObj->Content))) {
                case '联系方式':
                    $Content = "1562212****";
                    break;
                case '个人网站':
                    $Content = "<a href='http://www.xx-star.cn'>个人网站入口</a>";
                    break;
                case '个人信息':
                    $Content = "青椒白饭Me.Xie，本科，计算机专业，热爱互联网，PHPer,在成为大牛的路上努力着！喜欢打篮球，热爱音乐，外向兼内向的boy!";
                    break;
                default :
                    $Content = $postObj->Content;
                    $msg = new IndexModel();
                    $msg->msgRobot($postObj,$Content);
                    break;
                }
            $msg = new IndexModel();
            $msg->autoMsg($postObj,$Content);
            }            
        }else if(strtolower($postObj->MsgType) == "voice"){
            //语音点歌功能
            if(isset($postObj->Recognition) && !empty($postObj->Recognition)){
                $Content = $postObj->Recognition;  //获取语音识别结果
            }else{
                $Content = "请确保开启语音识别功能，内容无法识别或者为空，请重试！语音点歌格式为：“点歌+歌名”，如“点歌喜欢你”，玩玩吧嘻嘻！";
                $msg = new IndexModel();
                $msg->autoMsg($postObj,$Content);
            }
            $msg = preg_replace("/^\x{4e00}-\x{9fa5}/u", '', $Content);  //对非法字符进行替换
            if (strpos($msg,'点歌') !== false) {
                //判断识别语音内容，要有关键词
                $music = new IndexModel();
                $music->autoMusic($postObj,$msg);
            }              
        }
    }

    /**
     * @url  接口url
     * @type  请求类型
     * @res  返回数据参数
     * @arr  post请求参数
     */
    public function httpCurl($url,$type='get',$res='json',$arr=''){
        //1.初始化curl
        $ch = curl_init();
        //2.设置curl的参数
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        if($type == 'post'){
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $arr);
        }
        //3.采集
        $output = curl_exec($ch);
        //4.关闭
        curl_close($ch);
        if ($res == 'json') {
            if (curl_error($ch)) {
                //请求失败
                return curl_error($ch);
            }else{
                //请求成功
                return json_decode($output,true);
            }          
        }
    }

    //获取accessToken
    public function getWxAccessToken(){
        //将access_token存在session或者cookie中
        if ($_SESSION['access_token'] && $_SESSION['expire_time'] > time()) {
            //如果access_token在session中并没有过期
            return $_SESSION['access_token'];
        }else{
            //如果session不存在或者已过期则重新获取
            //1.请求地址
        $appid = 'wxc54bbd960bbd2dae';
        $appsecret = 'fd5db7f8eaedf8f99f31328913031424';
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$appsecret;
        $res = $this->httpCurl($url,'get','json');
        $access_token = $res['access_token'];  //将重新获取到的access_token存到session
        $_SESSION['access_token'] = $access_token;
        $_SESSION['expire_time'] = time()+7000;
        return $access_token;
        }
    }

    //自定义菜单栏创建
    public function defineMenu(){
        //微信接口的调用方式都是通过curl post/get调用
        header('content-type:text/html;charset=utf-8');
        $access_token = $this->getWxAccessToken();  //获取access_token        
        $url = "https://api.weixin.qq.com/cgi-bin/menu/get?access_token=".$access_token;
        //这个方式成功
        $postArr = '{
                "button":[
                {
            "name":"PHP",
           "sub_button":[
            {
               "type":"view",
               "name":"PHP官网",
               "url":"http://php.net/"
            },
            {
               "type":"view",
               "name":"ThinkPHP",
               "url":"http://www.thinkphp.cn/"
            },
            {
               "type":"view",
               "name":"Yii框架",
               "key":"http://www.yiichina.com/"
            },
            {
               "type":"view",
               "name":"Linux",
               "url":"http://www.linuxidc.com/"
            },
            {
                "type":"view",
                "name":"github",
                "url":"https://github.com/"
            }]
       },
       {
           "name":"篮球",
           "sub_button":[
            {
               "type":"view",
               "name":"NBA",
               "key":"http://sports.qq.com/nba/"
            },
            {
                "type":"click",
                "name":"骑士队",
                "key":"骑士队"
            }]
 },
     {
           "name":"音乐",
           "sub_button":[
            {
               "type":"view",
               "name":"网易云音乐",
               "key":"http://music.163.com/"
            },
            {
               "type":"click",
               "name":"点歌",
               "key":"点歌"
            }]
 }';
        $postJson = urldecode(json_encode($postArr));
        $res = $this->httpCurl($url,'post','json',$postJson);
    }

    //模版消息接口
    public function sendTemplateMsg(){
        //1.获取access_token
        echo $access_token = $this->getWxAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=".$access_token;
        //2.组装数组
        $array = array(
            'touser' => 'oZMCRwsEe88j7So9LETw0xjhtqSw',
            'template_id' => 'eWxR3gAA8Q9RXe31Ysdcm0p1v8R9f9tjxMZaAYcpMME',
            'url' => 'http://2016.qq.com/a/20160821/012861.htm',
            'data' => array(
                'title' => array('value' => '热烈祝贺，努力才能换来好的结果','color' => "#173177"),
                'name'  => array('value' => '被朱婷，惠若琪，丁霞，张常宁圈粉啦','color' => "#173177"),
                'time'  => array('value' => date("Y-m-d H:i:s"),'color' => "#173177"),
                ),
            );
        //3.将数组转成json
        $postJson = json_encode($array);
        //4.调用curl函数
        $res = $this->httpCurl($url,'post','json',$postJson);
        var_dump($res);
    }

    //临时二维码接口
    public function getQrCode(){
        header("content-type:text/html;charset=utf-8");  //防止乱码
        //获取全局ticket票据
        $access_token = $this->getWxAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=".$access_token;
        $postArray = array(
            'expire_seconds' => 604800,  //7天
            'action_name'    => 'QR_SCENE',  //临时二维码类型
            'action_info'    => array(
                'scene' => array('scene_id' => 2000),  //场景值ID,参数只支持1-100000
                ),   //二维码详细信息
            );
        $postJson = json_encode($postArray);
        $res = $this->httpCurl($url,'post','json',$postJson);
        $ticket = $res['ticket'];
        //2.使用ticket获取二维码图片
        $url = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=".urlencode($ticket);
        //下载二维码图片至本地
        header('Content-type:application/jpg');
        header('Content-disposition:attachment;filename='.$url);
        header('Content-length:'.filesize($url));
        readfile($url);
        //输出二维码图片
        echo "临时二维码Demo\n"."<img src='".$url."'/>";
    }

    //永久二维码接口
    public function getForeverQrCode(){
        header("content-type:text/html;charset=utf-8");  //防止乱码
        //获取全局ticket票据
        $access_token = $this->getWxAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=".$access_token;
        $postArray = array(
            'action_name'    => 'QR_LIMIT_SCENE',  //永久二维码类型
            'action_info'    => array(
                'scene' => array('scene_id' => 5000),  //场景值ID,参数只支持1-100000
                ),   //二维码详细信息
            );
        $postJson = json_encode($postArray);
        $res = $this->httpCurl($url,'post','json',$postJson);
        $ticket = $res['ticket'];
        //2.使用ticket获取二维码图片
        $url = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=".urlencode($ticket);
        //输出二维码图片
        echo "永久二维码Demo\n"."<img src='".$url."'/>";
    }

    //网页授权接口,snsapi_base
    public function getBaseInfo(){
        //1.获取code
        $appid = 'wxc54bbd960bbd2dae';
        $redirect_uri = urlencode("http://1.xxsafe.applinzi.com/index.php/Home/Index/getOpenID");
        //接口权限表->网页账号设置那边不用加http头，加了之后会显示redirect_uri参数错误
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$appid."&redirect_uri=".$redirect_uri."&response_type=code&scope=snsapi_base&state=123#wechat_redirect";
        header('location:'.$url);
    }

    //获取OpenID
    public function getOpenID(){
       //2.获取网页授权的access_token
       $appid = 'wxc54bbd960bbd2dae';
       $appsecret = 'fd5db7f8eaedf8f99f31328913031424';
       $code = $_GET['code'];
       $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$appid."&secret=".$appsecret."&code=".$code."&grant_type=authorization_code";
       //拉取用户的openid
       $res = $this->httpCurl($url,'get');
       var_dump($res);
       //$openid = $res['openid'];
       //这边可以进行页面展示
       //$this->display('');
    }

    //网页授权接口(高级),snsapi_userinfo
    public function getUserInfo(){
        //1.获取code
        $appid = 'wxc54bbd960bbd2dae';
        $redirect_uri = urlencode("http://1.xxsafe.applinzi.com/index.php/Home/Index/getUserDetail");
        //接口权限表->网页账号设置那边不用加http头，加了之后会显示redirect_uri参数错误
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$appid."&redirect_uri=".$redirect_uri."&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect";
        header('location:'.$url);
    }

    public function getUserDetail(){
       $appid = 'wxc54bbd960bbd2dae';
       $appsecret = 'fd5db7f8eaedf8f99f31328913031424';
       $code = $_GET['code'];
       $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$appid."&secret=".$appsecret."&code=".$code."&grant_type=authorization_code";
       //拉取用户的openid
       $res = $this->httpCurl($url,'get');
       $access_token = $res['access_token'];  //获取网页授权access_token
       $openid = $res['openid'];  //获取用户详细信息

       $url = "https://api.weixin.qq.com/sns/userinfo?access_token=".$access_token."&openid=".$openid."&lang=zh_CN ";
       $result = $this->httpCurl($url);
       var_dump($result);
    }  

    //以下是JS-SDK的内容
    //获取微信jsapi_ticket票据
    public function getJsApiTicket(){
        //session全局缓存access_token
        if ($_SESSION['jsapi_ticket'] && $_SESSION['jsapi_ticket_expire_time'] > time()) {
            $jsapi_ticket = $_SESSION['jsapi_ticket'];
        }else{
            $access_token = $this->getWxAccessToken();
            var_dump($access_token);
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=.".$access_token."&type=jsapi";
            $res = $this->httpCurl($url);
            var_dump($res);
            $jsapi_ticket = $res['ticket'];
            var_dump($jsapi_ticket);
            $_SESSION['jsapi_ticket'] = $jsapi_ticket;
            $_SESSION['jsapi_ticket_expire_time'] = time()+7000;
        }
        return $jsapi_ticket;    
    }

    //获取16位随机码
    public function getRandCode($length = 16){
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $tempStr = '';
        for ($i=0; $i < $length; $i++) { 
            $tempStr .= substr($chars, mt_rand(0,strlen($chars) - 1),1);
        }
        return $tempStr;
    }

    //JSSDK分享页面
    public function jsShare(){
        //1.获得jsapi_ticket票据
        $jsapi_ticket = $this->getJsApiTicket();
        var_dump($jsapi_ticket);
        $timestamp = time();
        $noncestr = $this->getRandCode();
        //$url = "http://1.xxsafe.applinzi.com/index.php/Home/Index/jsShare";
        // 注意 URL 一定要动态获取，不能 hardcode.
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        //2.获取signature签名算法
        $string = "jsapi_ticket=$jsapi_ticket&noncestr=$noncestr&timestamp=$timestamp&url=$url";
        $signature = sha1($string);
        var_dump($signature);
        $this->assign('timestamp',$timestamp);
        $this->assign('nonceStr',$noncestr);
        $this->assign('signature',$signature);
        $this->display('Index/share');
    }

}
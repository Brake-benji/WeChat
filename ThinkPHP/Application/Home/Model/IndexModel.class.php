<?php
namespace Home\Model;
use Think\Model;

class IndexModel extends Model{
	//关注事件推送
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

  //点歌功能(语音，文字点歌均可)
	public function autoMusic($postObj,$msg){
		$keyword = mb_substr($msg, 2, mb_strlen($msg,'utf-8'),'utf-8');  //对关键词进行截取，只要点歌后的歌名
		$url = "http://s.music.163.com/search/get/?type=1&s=[$keyword]&limit=1";  //获取API关键词地址
		$content = file_get_contents($url);  //获取网页内容
		$music = json_decode($content,true);  //把json内容转换为数组

		$musicUrl  = $music["result"]["songs"]["0"]["audio"];  //歌曲地址
    $musicName = $music["result"]["songs"]["0"]["name"];  //歌曲名
    $singer = $music["result"]["songs"]["0"]["artists"]["0"]["name"];  //歌手

    $toUsername = $postObj->FromUserName;
    $fromUsername = $postObj->ToUserName;
    $time = time();
    $msgType = "music";  //消息回复类型为音乐类型
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
    $result = sprintf($musicTpl,$toUsername,$fromUsername,$time,$msgType,$musicName,$singer,$musicUrl,$musicUrl);
    echo $result;
	}

  //图文回复功能
  public function autoPics($postObj, $Articles){
        $toUsername = $postObj->FromUserName;
        $FromUsername = $postObj->ToUserName;
        $time = time();
        $msgType = "news";
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

  //天气查询功能
  public function getWeather($postObj,$Content){
    $keyword = mb_substr($Content, 2,mb_strlen($Content,'utf-8'),'utf-8');  //截取关键词
    if (!empty($keyword)) {
      //调用接口API
      $ch = curl_init();
      $url = "http://apis.baidu.com/apistore/weatherservice/cityname?cityname=$keyword";
      $header = array(
        'apikey: 76db67296902bbc666cd929fcf50b93c',
    );
    // 添加apikey到header
    curl_setopt($ch, CURLOPT_HTTPHEADER  , $header);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // 执行HTTP请求
    curl_setopt($ch , CURLOPT_URL , $url);
    $res = curl_exec($ch);
    $weather = json_decode($res,true);  //把json数据转换为数组
    $city = $weather['retData']['city'];  //城市
    $date = $weather['retData']['date'];  //日期
    $wea  = $weather['retData']['weather'];  //天气情况
    $temp = $weather['retData']['temp'];  //温度
    $h_temp = $weather['retData']['h_tmp'];  //最高气温
    $l_temp = $weather['retData']['l_tmp'];  //最低气温
    $wd = $weather['retData']['WD'];  //风向
    
    //组装回复内容
    $Content = $city."的天气情况如下：\n"."城市：".$city."\n日期：".$date."\n天气情况：".$wea."\n温度：".$temp.";最高温：".$h_temp.";最低温：".$l_temp."\n风向：".$wd;
    self::autoMsg($postObj,$Content);  //回复纯文本
    }else{
      $Content = "请正确输入要查询的城市天气，如“天气广州”";
      self::autoMsg($postObj,$Content);
    }    
  }

  //手机号码归属地查询
  public function getPhone($postObj,$Content){
    $keyword = mb_substr($Content, 4,11,'utf-8');  //截取关键词，只保留11位号码
    if (!empty($keyword)) {
      //调用接口API
      $ch = curl_init();
      $url = "http://apis.baidu.com/apistore/mobilenumber/mobilenumber?phone=$keyword";
      $header = array(
        'apikey: 76db67296902bbc666cd929fcf50b93c',
        );
      // 添加apikey到header
      curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      // 执行HTTP请求
      curl_setopt($ch, CURLOPT_URL, $url);
      $res = curl_exec($ch);
      $phone = json_decode($res,true);  //把json数据转换为数组
      if($phone['errNum'] == '-1' && $phone['retData'] == null){
        //判断手机号码是否正确，不正确输出错误信息
        $Content = $phone['retMsg'];
        self::autoMsg($postObj,$Content);
      }else{
        $tel = $phone['retData']['phone'];  //手机号码
        $supply = $phone['retData']['supplier'];  //供应商
        $city = $phone['retData']['city'];  //手机号码归属地
        $suit = $phone['retData']['suit'];  //卡类型

        //组合回复语句
        $Content = "你的手机号为：".$tel."\n供应商是：".$supply."\n所在归属地为：".$city."\n手机卡类型为：".$suit;
        self::autoMsg($postObj,$Content);
      }
    }
  }

  //图灵机器人自动回复，要有图灵还有百度的apiKey,图灵机器人功能不是特别强大不够智能
  public function msgRobot($postObj,$Content){
    $words = trim($Content);
    $ch = curl_init();
    $url = 'http://apis.baidu.com/turing/turing/turing?key=图灵apiKey&info='.$words;
    $header = array(
        'apikey: 百度apiKey',
    );
    // 添加apikey到header
    curl_setopt($ch, CURLOPT_HTTPHEADER  , $header);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // 执行HTTP请求
    curl_setopt($ch , CURLOPT_URL , $url);
    $res = curl_exec($ch);

    $Content = json_decode($res,true);  //把json转为array
    self::autoMsg($postObj,$Content['text']);
  }
}
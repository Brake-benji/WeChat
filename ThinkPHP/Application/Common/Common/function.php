<?php
    //获取微信服务器IP地址
    function getServerIP(){
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
        return $arr;
    }
<?php
//苹果会回调在get_code.php去获取code所传递的state，和记录在session的state做一个对比，防止被修改
if ($_SESSION['state'] != $_POST['state']) {
    return ['code' => 0, 'msg' => "state error", 'data' => []];
}
//判断是否有回调code
if (empty($_POST['code'])) {
    exit("code error");
}
//构造参数，去获取access_token，主要这里的client_secret是通过ruby_script的key.rb脚本生成的公钥，最长生成有效期是180天
$postData = [
    'grant_type' => 'authorization_code',
    'code' => $_POST['code'],
    'client_id' => '你的client_id',
    'client_secret' => '你的client_secret',

];
$response = httpRequest('https://appleid.apple.com/auth/token', $postData);
if (!isset($response->access_token)) {
    exit("access_token error");
}
//解析苹果参数
$appleInfoArray = explode('.', $response->id_token);
print_r($appleInfoArray);
$appleInfo = json_decode(base64_decode($appleInfoArray[1]), 1);
print_r($appleInfo);

//得到苹果用户id
$authId = $appleInfo['sub'];

function httpRequest($url, $params = false)
{
    try {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($params) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'User-Agent: curl', # Apple requires a user agent header at the token endpoint
        ]);
        $response = curl_exec($ch);
        return json_decode($response);
    } catch (\Exception $e) {
        echo $e->getMessage();
    }

}
<?php
//苹果会回调在get_code.php去获取code所传递的state，和记录在session的state做一个对比，防止被修改
if ($_SESSION['state'] != $_POST['state']) {
    return ['code' => 0, 'msg' => "state error", 'data' => []];
}
//判断是否有回调code
if (empty($_POST['code'])) {
    exit("code error");
}
$privateKey = <<<EOD
-----BEGIN PRIVATE KEY-----
xxxxxxxxxxxxxxxxxxxxxx
-----END PRIVATE KEY-----
EOD;

$kid = '你的key_id';
$iss = '你的team_id';
$client_id = '你的client_id';
//和method01不同，该方案是构建实时的公钥，去获取access_token
$appleJwt = new AppleJwt();
$myJwt = $appleJwt->createJwt($kid, $iss, $client_id, $privateKey);
$postData = [
    'grant_type' => 'authorization_code',
    'code' => $_POST['code'],
    'client_id' => '你的client_id',
    'client_secret' => $myJwt,

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

class AppleJwt
{
    public function createJwt($kid, $iss, $sub, $privateKey)
    {

        $header = ['alg' => 'ES256', 'kid' => $kid];
        $body = ['iss' => $iss, 'iat' => time(), 'exp' => time() + 3600, 'aud' => 'https://appleid.apple.com', 'sub' => $sub];
        $privKey = $privateKey;
        if (!$privKey) {
            return false;
        }
        $payload = $this->encode(json_encode($header)) . '.' . $this->encode(json_encode($body));
        $signature = '';
        $success = openssl_sign($payload, $signature, $privKey, OPENSSL_ALGO_SHA256);
        if (!$success) {
            return false;
        }
        $raw_signature = $this->fromDer($signature, 64);
        return $payload . '.' . $this->encode($raw_signature);
    }

    public function encode($data)
    {
        $encoded = strtr(base64_encode($data), '+/', '-_');
        return rtrim($encoded, '=');
    }

    public function fromDer($der, $partLength)
    {
        $hex = unpack('H*', $der)[1];
        if ('30' !== mb_substr($hex, 0, 2, '8bit')) { // SEQUENCE
            throw new \RuntimeException();
        }
        if ('81' === mb_substr($hex, 2, 2, '8bit')) { // LENGTH > 128
            $hex = mb_substr($hex, 6, null, '8bit');
        } else {
            $hex = mb_substr($hex, 4, null, '8bit');
        }
        if ('02' !== mb_substr($hex, 0, 2, '8bit')) { // INTEGER
            throw new \RuntimeException();
        }
        $Rl = hexdec(mb_substr($hex, 2, 2, '8bit'));
        $R = self::retrievePositiveInteger(mb_substr($hex, 4, $Rl * 2, '8bit'));
        $R = str_pad($R, $partLength, '0', STR_PAD_LEFT);
        $hex = mb_substr($hex, 4 + $Rl * 2, null, '8bit');
        if ('02' !== mb_substr($hex, 0, 2, '8bit')) { // INTEGER
            throw new \RuntimeException();
        }
        $Sl = hexdec(mb_substr($hex, 2, 2, '8bit'));
        $S = self::retrievePositiveInteger(mb_substr($hex, 4, $Sl * 2, '8bit'));
        $S = str_pad($S, $partLength, '0', STR_PAD_LEFT);
        return pack('H*', $R . $S);
    }

    private static function retrievePositiveInteger($data)
    {
        while ('00' === mb_substr($data, 0, 2, '8bit') && mb_substr($data, 2, 2, '8bit') > '7f') {
            $data = mb_substr($data, 2, null, '8bit');
        }
        return $data;
    }
}

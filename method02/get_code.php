<?php

session_start();

$_SESSION['state'] = bin2hex(random_bytes(5));

$postData = [
    'response_mode' => 'form_post',
    'response_type' => 'code',
    'client_id' => '你的client_id',
    'redirect_uri' => 'https://换成你的回调域名/callback',
    'state' => $_SESSION['state'],
    'scope' => 'name email',
];

$authUrl = 'https://appleid.apple.com/auth/authorize' . '?' . http_build_query($postData);

header("Location:$authUrl");

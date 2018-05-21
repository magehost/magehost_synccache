#!/usr/bin/php
<?php

require_once __DIR__ . '/../../../../autoload.php';

// REPLACE WITH YOUR ACTUAL DATA OBTAINED WHILE CREATING NEW INTEGRATION
$consumerKey = '1rwc58a4nwptqe6hdeq3qsxuayjadpfk';
$consumerSecret = 'jlaeh17a9yofyhar8mwujutlj7boyq4t';
$accessToken = '4ddrva49aygsepi8i9k49052ecmjasin';
$accessTokenSecret = 'x6gxvibhy0o5fhr638tmg0ude73ro3ug';

$method = 'GET';
$host = 'm2dev.magehost.pro';
$from = gethostname();
$mode = 'matchingTag';
$tags = ['aa','bb','cc','dd'];
$url = sprintf(
    'https://%s/rest/V1/magehost/synccache/clean/%s/%s/%s/',
    $host,
    $from,
    urlencode($mode),
    urlencode(json_encode($tags))
);

echo "Calling ".$url."\n";

$data = [
    'oauth_consumer_key' => $consumerKey,
    'oauth_nonce' => md5(uniqid(rand(), true)),
    'oauth_signature_method' => 'HMAC-SHA1',
    'oauth_timestamp' => time(),
    'oauth_token' => $accessToken,
    'oauth_version' => '1.0'
];

$util = new \Zend_Oauth_Http_Utility();
$data['oauth_signature'] = $util->sign(
    $data,
    $data['oauth_signature_method'],
    $consumerSecret,
    $accessTokenSecret,
    $method,
    $url
);

//echo "oauth_signature: " . $data['oauth_signature'] . "\n";

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_URL => $url,
    CURLOPT_HTTPHEADER => [
        'Authorization: OAuth ' . http_build_query($data, '', ','),
        'Cookie: XDEBUG_SESSION=XDEBUG_ECLIPSE'
    ]
]);

$result = curl_exec($curl);
curl_close($curl);
var_dump($result);

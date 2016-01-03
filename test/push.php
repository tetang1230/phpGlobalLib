<?php
//php需要开启ssl(OpenSSL)支持
$apnsCert    = "/Users/chester/mls/innerpandora/library/push/cert/apns-dist-d.pem";//连接到APNS时的证书许可文件，证书需格外按要求创建
//$pass        = "123456";//证书口令
//$serverUrl   = "ssl://gateway.sandbox.push.apple.com:2195";//push服务器，这里是开发测试服务器
$serverUrl   = "tls://gateway.push.apple.com:2195";//push服务器，这里是开发测试服务器
$deviceToken = "73a0726dd8fe41a4ed3f2703c1f5e258c83545802fc18f907ed1c4a6383bf0d7";//ios设备id，中间不能有空格，每个ios设备一个id

$body    = array('aps' => array('alert' => 'higo', 'badge' => 2 , 'sound' => 'default'));
$streamContext = stream_context_create();
stream_context_set_option ( $streamContext, 'ssl', 'local_cert', $apnsCert );
stream_context_set_option ( $streamContext, 'ssl', 'passphrase', '');
$apns = stream_socket_client ( $serverUrl, $error, $errorString, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $streamContext);//连接服务器
if ($apns) {
    echo "Connection OK <br/>";
} else {
    echo "Failed to connect $errorString";
    return;
}
$payload = json_encode($body);

$deviceToken = "73a0726dd8fe41a4ed3f2703c1f5e258c83545802fc18f907ed1c4a6383bf0d7";//ios设备id，中间不能有空格，每个ios设备一个id
$expiration = time() + 864000;

$bin = pack('CnH*', 1, 32, $deviceToken)
//token
. pack('CnA*', 2, strlen($payload), $payload)
//msg
. pack('CnN', 3, 4, 0)
//identifier always in the loop, auto_increment value
. pack('CnN', 4, 4, $expiration)
//expiration
. pack('CnC', 5, 1, 10);
//priority
$msg = pack('CN', 2, strlen($bin)) . $bin;
//whole msg

$result  = fwrite($apns,$msg);//发送消息
$date = date('Y-m-d H:i:s');
file_put_contents('date.txt', $date."\n", FILE_APPEND);
//$cmd = "nohup php ./multi_fwrite.php {$apns} {$msg} &";
//echo $cmd;
//system($cmd);
//exec("nohup php multi_fwrite.php $apns $msg &");




/*
$deviceToken = "73a0726dd8fe41a4ed3f2703c1f5e258c83545802fc18f907ed1c4a6383bf0d7";//ios设备id，中间不能有空格，每个ios设备一个id
$expiration = time() + 864000;

$bin = pack('CnH*', 1, 32, $deviceToken)
. pack('CnA*', 2, strlen($payload), $payload)
. pack('CnN', 3, 4, 1)
. pack('CnN', 4, 4, $expiration)
. pack('CnC', 5, 1, 10);
$msg = pack('CN', 2, strlen($bin)) . $bin;

$result  = fwrite($apns,$msg);//发送消息

$deviceToken = "ffffba158f4ecfbfde6f4f539917bf1060344f7d04686d7874e8ccc46a61d860";//ios设备id，中间不能有空格，每个ios设备一个id
$bin = pack('CnH*', 1, 32, $deviceToken)
. pack('CnA*', 2, strlen($payload), $payload)
. pack('CnN', 3, 4, 2)
. pack('CnN', 4, 4, $expiration)
. pack('CnC', 5, 1, 10);
$msg = pack('CN', 2, strlen($bin)) . $bin;
$result  = fwrite ( $apns, $msg);//发送消息
*/
//$msg = chr(0) . pack('n', 32) . pack('H*', str_replace(' ', '', $deviceToken)) . pack('n', strlen($payload)) . $payload;

$aread = array($apns);
$awrite = array();
$aexcept = array();

$num_changed_streams = stream_select($aread, $awrite, $aexcept, 2);

if($num_changed_streams){
    $str = fread($apns, 6);
    if(!empty($str)){
        $retinfo_unpack = unpack("Ccommand/Cstatus/Nidentifier", $str);
        print_r($retinfo_unpack);
    }
}

fclose($apns);
/*
if ($result)
    echo "Sending message successfully: " . $payload . "\n";
else
    echo "Message not deliveredn". "\n";
*/
?>

<?php
$url = 'http://ad.souma.wang/api/task/sync';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url );
//参数为1表示传输数据，为0表示直接输出显示。
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//参数为0表示不带头文件，为1表示带头文件
curl_setopt($ch, CURLOPT_HEADER,0);
$output = curl_exec($ch);
echo $output;
curl_close($ch);

exit();
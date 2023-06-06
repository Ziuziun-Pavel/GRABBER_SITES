<?php
header("Content-Type: text/html; charset=utf-8");
$content = file_get_contents('motobur.db.json');
$arr = json_decode($content, true);
print'<pre>';
print_r($arr);
print'</pre>';
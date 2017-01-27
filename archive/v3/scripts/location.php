<?php

require_once('config.php');

$url = $feed_url . '?count=1';
$feed = simplexml_load_file($url);
echo json_encode($feed->Folder);

?>

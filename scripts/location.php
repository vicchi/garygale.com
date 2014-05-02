<?php

$feed = simplexml_load_file('https://feeds.foursquare.com/history/847e3859509eb57e04dba9fb6b4c136a.kml?count=1');
echo json_encode($feed->Folder);

?>

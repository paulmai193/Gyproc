<?php
require 'iOSPush.php';
$push = new iOSPush ( array (
		'ccf59004277ad384c2727e03377bdfb80421d24a6c81d7787a8974f8b5cd9d6e'
), 'Test push bang tool tu server', 'asodvnowe' );
$result = $push->sendPush ();
?>
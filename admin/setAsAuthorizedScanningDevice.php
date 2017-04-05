<?php

// Core laden
require("./../src/core/main.php");

$deviceIP = $_SERVER["REMOTE_ADDR"];

if (empty($_REQUEST["testCookie"]))
{
	sendMail( ADMIN_EMAIL, "Neues autorisiertes scanning-Gerät hinzugefügt: ".$deviceIP , $deviceIP , "From: MCG-Kartensystem <<EMAIL>>");
	var_dump( setcookie( AUTHORIZED_SCANNING_DEVICES_COOKIE_NAME , AUTHORIZED_SCANNING_DEVICES_COOKIE_TOKEN , time()+ AUTHORIZED_SCANNING_DEVICES_COOKIE_EXPIRE, "/") );

	accessLog("Neues autorisiertes Scanning-Gerät: ".$deviceIP );

	echo "<hr><a href='?testCookie=1'>Test</a>";
}else{
	echo "<hr>";
	var_dump($_COOKIE);
}

?>

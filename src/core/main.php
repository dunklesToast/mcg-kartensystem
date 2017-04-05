<?php
// Core laden
if (defined("CORE")) Die("ERROR! Core already loaded!");
define("CORE", true);

require("config.inc.php");		// Config
require("functions.inc.php");	// Funktionen

accessLog("======= Neue Anfrage von ".$_SERVER["REMOTE_ADDR"]." für '".$_SERVER["REQUEST_URI"]."'");

require("dbConnect.inc.php");	// Datenbankverbindung herstellen
accessLog("Datenbank-Verbindung hergestellt.");

?>

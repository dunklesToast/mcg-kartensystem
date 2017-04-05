<?php
if (!defined("CORE")) exit(0);

/*
System-Konfiguration

MCG-Kartensystem v.1.0
*/

define("ADMIN_EMAIL",				"<ADMIN-Email>");		// Email des Administrators
define("TIMESTAMP_FORMAT",			"d.m.Y H:i");				// Format der Zeitstempel
define("TIMESTAMP_FORMAT_LOG",		"d.m.Y H:i:s");				// Internes Zeitstempel-Format (z.B. in den Logs.)

define("TIMEZONE", 					"Europe/Berlin");			// Zeitzone des Servers
date_default_timezone_set(TIMEZONE);

// Logs
define("LOG_DIR",					__DIR__."/../../admin/log");
define("LOG_ERROR_FILENAME",		LOG_DIR."/".date("d.m").".error.log.txt"	);
define("LOG_ACCESS_FILENAME",		LOG_DIR."/".date("d.m").".access.log.txt"	);
define("LOG_RESERVE_FILENAME",		LOG_DIR."/reserve.log.txt"	);

define("LOG_ACCESS_ENABLE", 		true);
define("DEFAULT_PUB_ERROR", 		"Interner Fehler aufgetreten. Bitte kontaktieren Sie den Administrator: ".ADMIN_EMAIL);


define("PLACE_SEPARATION_SIGN",		"@");						// Zeichen, mit denen die Sitzplätze in der Datenbank und den GET-Requests zusammengefügt werden; NICHT VERÄNDERN!!!
define("MAX_NAME_LENGTH",			27);						// Maximale Länge der akzeptierten Namen in Zeichen
define("MAX_SEAT_WIDTH", 			43);						// Maximale Anzahl der reservierbaren Sitzplätze in Zeichen

define("TICKET_TOKEN_SALT",		"<Random Salt>");				// Salt für die Ticket-Token (Dateiname)
// Text auf der Fußzeile der Einlasskarten:
define("TICKET_FOOTER_TEXT",		"Bei Fragen, Anregungen und Stornierungen: <EMAIL>");

define("MAIL_ADMIN_ON_RESERVE",		true);						// Bei jeder neuen Reservierung eine email an den Administrator schicken

define("DOMAIN_ROOT",			"http://<DOMAIN>");

// Datenbank-Verbindung
$dbCredentials = array();
$dbCredentials["dbHost"]	= "<DB Host>";
$dbCredentials["dbName"]	= "<DB Name>";
$dbCredentials["dbCharset"]	= "utf8";
$dbCredentials["dbUser"]	= "<DB Name>";
$dbCredentials["dbPass"]	= "<DB Pass>";

// Ticket-Reservierung: Authorisierte Geräte
define("AUTHORIZED_SCANNING_DEVICES_COOKIE_NAME", 	"authorized_device");
define("AUTHORIZED_SCANNING_DEVICES_COOKIE_TOKEN",	"<Random Token>");
define("AUTHORIZED_SCANNING_DEVICES_COOKIE_EXPIRE",	60*24); // Cookie läuft nach 24h ab

define("SCAN_TIME_WARN",	10800); // Zeitdifferenz in Sekunden, bei der gewarnt wird, wenn der Zeitpunkt des reservierten Termins vom Zeitpunkt des Scannens signifikant abweicht (-> falscher Termin); aktuell 3 Stunden
?>

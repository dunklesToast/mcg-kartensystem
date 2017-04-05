<?php
if (!defined("CORE")) exit(0);

/**** Datenbank-Verbindung herstellen ****/

// Data: $dbCredentials
$dbHandle = @mysqli_connect(
	$dbCredentials["dbHost"],
	$dbCredentials["dbUser"],
	$dbCredentials["dbPass"],
	$dbCredentials["dbName"]
);

if ($dbHandle === false)
	exitError(DEFAULT_PUB_ERROR, "Fehler bei Datenbank-Verbindung: ".mysqli_connect_error());

// Zeichensatz setzen
if (!@mysqli_set_charset($dbHandle, $dbCredentials["dbCharset"]))
	exitError(DEFAULT_PUB_ERROR, "Fehler beim Setzen des Datenbank-Zeichensatzes: ".mysqli_error($dbHandle));

// Anmeldeinformationen aus Speicher löschen
unset($dbCredentials);

?>
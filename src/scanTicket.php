<html>
	<head>
		<meta charset="UTF-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<style type="text/css">
		html body{
			font-family: arial, verdana;
		}
		.symbol{
			width: 100%;
			font-size: 350px;
			height: 100%;
			color: white;
			text-align: center;
		}
		.symbol.red{
			background-color: red;
		}
		.symbol.green{
			background-color: green;
		}
		</style>
	</head>
	<body style="width: 350px; margin: 0; padding: 3px;">
<?php

/***** Ticket Scan ****/

// Core laden
require("core/main.php");

accessLog("Registriere Scan eines Tickets [scanTicket.php]");

if(empty($_REQUEST["ticket_id"]) || !is_numeric($_REQUEST["ticket_id"]) || empty($_REQUEST["ticket_hash"]))
	exitError("Fehlerhafte Daten übergeben! (scanTicket.php)", "Scan-Script nicht korrekt aufgerufen, es fehlen Werte oder sind fehlerhaft; REQUEST: ".serialize($_REQUEST));

// Auf registriertes Gerät überprüfen
if (!empty($_COOKIE[AUTHORIZED_SCANNING_DEVICES_COOKIE_NAME]) && $_COOKIE[AUTHORIZED_SCANNING_DEVICES_COOKIE_NAME] == AUTHORIZED_SCANNING_DEVICES_COOKIE_TOKEN)
{
	
	// Ticket formal überprüfen [Ticket Hash]
	$ticketHashGeneric = sha1($_REQUEST["ticket_id"].TICKET_TOKEN_SALT);
	
	if ( $ticketHashGeneric != $_REQUEST["ticket_hash"] )
	{ // Fehler - Code nicht valide
		Die( "<div class='symbol red'>×</div><hr><b>Fehler</b><hr>Der übergebene Ticket-Überprüfungscode '{$ticketHashGeneric}' stimmt nicht mit dem validen Code '".$_REQUEST["ticket_hash"]."' überein.");
	}
	
	// Reservierung abrufen
	$sql = "SELECT * FROM reservierungen WHERE ID = '".$_REQUEST["ticket_id"]."'";
	$result = @mysqli_query( $dbHandle, $sql ) or Die("<div class='symbol red'>×</div><hr><b>Fehler</b><hr>Fehler bei Datenbankabfrage des Tickets; ERROR:".mysqli_error($dbHandle));

	$reservierung = mysqli_fetch_assoc( $result );
	
	// Termin validieren
	$termin = getTermin( $reservierung["termin"] );
	
	if ( $termin == false ) Die("<div class='symbol red'>×</div><hr><b>Fehler</b><hr>Termin nicht gefunden!");

	if ( abs( time() - $termin["datetime"] ) > SCAN_TIME_WARN )
	{
		echo "<div class='symbol red'>×</div><hr><strong>Fehler</strong><hr><i>Der reservierte Termin ist zeitlich deutlich entfernt;</i> <u>Stellen Sie sicher, dass der bevorstehende Termin folgender ist: </u><br><center><strong>".$termin["name"]."</strong></center><hr>";
		exit();
	}
	
	// Sitze trennen
	$seatArray = explode( PLACE_SEPARATION_SIGN, $reservierung["plaetze"] );
	
	// Setzen der Anwesenheit
	// Zunächst überprüfen, ob nicht bereits als anwesend gesetzt
	if ( $reservierung["erschienen"] != "0" )
	{
		echo "<div class='symbol red'>×</div><hr><b>Fehler</b><hr>Reservierung bereits als anwesend markiert!! (Eintrag: '".$reservierung["erschienen"]."')";
		exit();
	}
	
	// Als erschienen setzen
	$sql = "UPDATE reservierungen SET erschienen='1' WHERE ID='".$reservierung["ID"]."'";
	$result = @mysqli_query($dbHandle, $sql) or Die("<div class='symbol red'>×</div><hr><b>Fehler</b><hr>Fehler beim Setzen der Anwesenheit von Reservierung ".$reservierung["ID"].".; ERROR: ".mysqli_error($dbHandle ) );
	
	// Evtl. wurden mehrere Datensätze betroffen, was allerdings nie der Fall sein darf und nicht passieren sollte
	if ( mysqli_affected_rows($dbHandle) != 1)
	{
		Die("<div class='symbol red'>×</div><hr><b>Fehler</b><hr>Es wurden '".mysqli_affected_rows($dbHandle)."' Zeilen aktualisiert; es sollte eigentlich nur 1 Zeile aktualisiert werden!!!!");
	}
	
	echo "<div class='symbol green'>✓</div>";
	
	// Ticket valide:
	//	- richtiger Termin [nähe zum Termin < 5 Stunden]
	//	- nicht bereits anwesend


	// Informations-Tabelle ausgeben (unterhalb)
	echo "<hr><table border='1' style='width: 100%;'>
			<tr>
				<td>Reservierungs-ID</td>
				<td>".$reservierung["ID"]."</td>
			</tr>
			<tr>
				<td>Name</td>
				<td>".$reservierung["name"]."</td>
			</tr>
			<tr>
				<td>Sitze</td>
				<td>".sizeof($seatArray)."</td>
			</tr>
			<tr><td></td><td><ul>";
				foreach( $seatArray as $s )
					echo "<li>".$s."</li>";
				echo "</ul></td>
			</tr>
			<tr>
				<td>Termin</td>
				<td>".$termin["name"]."</td>
			</tr>
		</table><hr>";

	// Zu Ende prüfen / nachfragen, ob alle Personen anwesend sind und ggf. nachträglich manuell freigeben

}else
{
	// Unautorisiertes Gerät
	echo "Der Versuch, einen unautorisierten Ticket-Entwertungsvorgang vorzunehmen, wurde verhindert.<br><b>Bitte scannen Sie den Code auf Ihrem Ticket nicht selbst!</b>";
}

?>
	</body>
</html>
<?php

/***** Ausführen der Reservierung ****/

try{
		
// Core laden
require("core/main.php");

accessLog("Termin-Reservierung initialisiert.");

// Termin muss übergeben sein
if (empty($_REQUEST["termin"]) || !is_numeric($_REQUEST["termin"]))
	exitError("Fehler - Kein oder ungültiger Termin übergeben. Wenden Sie sich im Zweifelsfall an den Administrator:".ADMIN_EMAIL, "Fehlerhafter Termin: ".$_GET["termin"]);

accessLog("Termin-ID: ".$_REQUEST["termin"]);

$termin = getTermin($_REQUEST["termin"]);

// Termin validieren (geöffnet und freigegeben?)
if ($termin["open"] < 2)
	exitError(DEFAULT_PUB_ERROR, "Termin noch nicht freigeschaltet: ".$termin["ID"]);

if ($termin["datetime"] < time())
	exitError(DEFAULT_PUB_ERROR, "Termin in der Vergangenheit!: ".$termin["ID"]);

if (!is_numeric($termin["plan"]))
	exitError(DEFAULT_PUB_ERROR, "FEHLER - Gespeicherte Plan-ID nicht numerisch!!: ".$termin["plan"]);

require("template/template.head.inc.php");

// Zweiter Teil der Reservierung?
/*
Übergebene Felder:
	- name
	- email
	- termin
	- plaetze (getrennt von PLACE_SEPARATION_SIGN (deault '@')

	Platz: Muss in entsprechendem Plan-Template enthalten sein und es darf noch keine Reservierung existieren, die 

*/

// Vollen Namen zusammensetzen
if ( !empty($_REQUEST["lastname"]) )
	$_REQUEST["name"] = (empty($_REQUEST["firstname"]) ? "" : $_REQUEST["firstname"]." ").$_REQUEST["lastname"];

if (!empty($_REQUEST["name"]) && !empty($_REQUEST["email"]) && !empty($_REQUEST["plaetze"]))
{
	
	accessLog("Eingaben übergeben; validiere Eingaben.");
	// Überprüfung der Eingaben
	
	// Name
	//if (!preg_match("/^[a-zA-Z -äÄöÖüÜ]*$/", $_REQUEST["name"]))
	//	exitError("Fehler - In ihrem Namen sind unerlaubte Zeichen enthalten. Falls dies ein Irrtum ist, kontaktieren Sie bitte den Administrator: ".ADMIN_EMAIL, "Fehlerhafter Name: '".$_REQUEST["name"]."'");

    $_REQUEST["name"] = mysqli_real_escape_string($dbHandle, $_REQUEST["name"]);

	// Email
	if (!filter_var($_REQUEST["email"], FILTER_VALIDATE_EMAIL))
  		exitError("Bitte geben Sie eine gültige Email-Adresse ein.", "Ungültige Email: ".$_REQUEST["email"]);
	
	// Decodierung des Platz-JSON-Strings
	$seats = @json_decode($_REQUEST["plaetze"], true);
	if ($seats == false || !is_array($seats) || sizeof($seats) <= 0)
		exitError(DEFAULT_PUB_ERROR, "Ungültige Plätze: ".$_REQUEST["plaetze"]);
	
	// Maximale Anzahl, falls dies eingestellt ist
	if($termin["platzAuswahlMax"] > 0 && sizeof(array_unique($seats)) > $termin["platzAuswahlMax"])
		Die("Zu viele Sitze ausgewählt; Sie dürfen maximal ".$termin["platzAuswahlMax"]." Plätze auswählen.");
	
	// Doppeleinträge vorhanden
	if (sizeof(array_unique($seats)) != sizeof($seats))
		Die( "Fehler - Sitzplätze doppelt belegt! :".$_GET["plaetze"]."<br /> Kontaktieren Sie bitte den Administrator: ".ADMIN_EMAIL );
	
	// Doppelungen entfernungen
	$seats = array_unique($seats);
	
	// Sortieren
	sort($seats);
	
	// Einzelne Sitzplätze validieren (numerisch)
	foreach($seats as $s)
		if (!is_numeric($s) || $s <= 0)
			exitError("Fehler - Sitzplatz '".$s."' ist ungültig.", "Sitzplatz '".$s."' ungültig.");
	
	// Sitzplätze nacheinander reservieren
	foreach($seats as $s)
	{
		// Überprüfen, ob $s gültig und im Plan enthalten ist

		$sql = "SELECT * FROM plaetze WHERE nummer='".$s."' AND plan='".$termin["plan"]."'";
		$result = @mysqli_query($dbHandle, $sql);
		if ($result == false)
			exitError(DEFAULT_PUB_ERROR, "Interner Fehler beim überprüfen der Existenz des Platzes: ".$sql);
		
		if (@mysqli_num_rows($result) == 0)
			exitError("Fehler - Sitzplatz ".$s." nicht gefunden. Kontaktieren Sie den Administrator: ".ADMIN_EMAIL, "Platz ".$s." in Plan ".$termin["plan"]." nicht gefunden.");
		
		if (@mysqli_num_rows($result) > 1)
			exitError(DEFAULT_PUB_ERROR, "SCHWERWIEGENDER FEHLER - Platznummer nicht einzigartig in Plan: ".$sql.": ".mysqli_num_rows($result));
		
		// Anscheinend ist die Platznummer existent. Überprüfe, ob bereits reserviert
		
		$sql = "SELECT * FROM reservierungen WHERE termin='".$termin["ID"]."' AND plaetze LIKE '%".$s."%'";
		$result = @mysqli_query($dbHandle, $sql);
		if ($result == false)
			exitError(DEFAULT_PUB_ERROR, "Fehler beim überprüfen, ob Platz bereits reserviert: ".$sql);
		
		$platz_bereits_reserviert = false;
		
		while($line = @mysqli_fetch_assoc($result))
		{
			$reservedSeats = explode(PLACE_SEPARATION_SIGN, $line["plaetze"]);
			if (in_array($s, $reservedSeats))
			{
				accessLog("Platz ".$s." bereits reserviert.");
				exitError("Fehler - Der Platz mit der Platznummer ".$s." wurde bereits reserviert. Eventuell wurde Ihnen zuvorgekommen. Wiederholen Sie bitte die Sitzplatzauswahl oder kontaktieren Sie den Administrator: ".ADMIN_EMAIL, "Platz bereits reserviert: ".$termin["ID"]."::".$s);
			}
		}
		
	}
	
	// Reservierung vornehmen
	
	accessLog("Alle Tests erfolgreich.");
	accessLog("Name:		".$_REQUEST["name"]);
	accessLog("Email:		".$_REQUEST["email"]);
	accessLog("Plätze:		".implode(",", $seats));
	accessLog("Reservierung wird vorgenommen...");
	
	// Fallback-Speicherung
	reserveLog("====================");
	reserveLog("Name:	".$_REQUEST["name"]);
	reserveLog("Email:	".$_REQUEST["email"]);
	reserveLog("Plätze:	".implode(",", $seats));
	
	// Reservierung in Datenbank
	$sql = "INSERT INTO reservierungen VALUES(NULL, '".$termin["ID"]."', UNIX_TIMESTAMP() , '".$_REQUEST["name"]."', '".$_REQUEST["email"]."', '".$_SERVER["REMOTE_ADDR"]."', '".implode(PLACE_SEPARATION_SIGN, $seats)."', '0', '0')";
	accessLog("SQL: ".$sql);
	
	$resultDB = @mysqli_query($dbHandle, $sql);
	if ($resultDB == false || mysqli_affected_rows($dbHandle) != 1)
		exitError("Fehler bei Reservierung aufgetreten. Kontaktieren Sie umgehend den Administrator.".ADMIN_EMAIL, "FEHLER BEI RESERVIERUNG!!!: ".mysqli_error($dbHandle));
	
	// ID des erzeugten Reservierungs-Eintrags auslesen
	$reservierungsID = mysqli_insert_id($dbHandle);
	
	if (!is_numeric($reservierungsID))
		exitError("Schwerwiegender Fehler bei Reservierung aufgetreten!! Kontaktieren Sie bitte umgehend den Administrator: ".ADMIN_EMAIL, "FEHLER - Kann die Reservierungs-ID nach Einfügen des Termins nicht auslesen; mysqli_insert_id gibt '".$reservierungsID."' zurück!");
	
	accessLog("Einfügen des Reservierungs-Eintrags in die Datenbank erfolgreich; Reservierungs-ID:	".$reservierungsID);
	reserveLog("==> Reservierung ".$reservierungsID);
	
	$ticketFileName = createTicket($reservierungsID);

	if ($ticketFileName == false)
		exitError("Fehler beim Erstellen ihrer Einlasskarte. Kontaktieren Sie den Administrator: ".ADMIN_EMAIL, "FEHLER BEIM ERSTELLEN DES TICKETS!!!");
	
	// Email verschicken
    
	//$mailTo		= $_REQUEST["name"]."<".$_REQUEST["email"].">";
	$mailTo		= $_REQUEST["email"];
	$mailSubject	= "Ihre Reservierung am MCG-Kartensystem";
	$mailSender	= "MCG Kartensystem<<EMAIL>>";

	$mailHeader  = "MIME-Version: 1.0\r\n";
	$mailHeader .= "Content-type: text/html; charset=utf8\r\n";
	$mailHeader .= "From: ".$mailSender."\r\n";
	//$mailHeader .= "Reply-To: ".ADMIN_EMAIL."\r\n";

	$absoluteTicketPath = DOMAIN_ROOT."/ticket/".$ticketFileName;

	$mailBody = "<html>
	<head>
		<meta charset='UTF-8' />
	</head>
	<body>
		<p>Guten Tag, Herr/Frau ".$_REQUEST["name"]."</p>
		<br>Sie haben soeben eine Einlasskarte im Ticketsystem des MCG bestellt.
		<p>Sie können Ihre Einlasskarte <a href='".$absoluteTicketPath."'>hier</a> herunterladen und entweder direkt ausdrucken oder beim Einlass digital (auf ihrem Smartphone) vorzeigen.</p>
		<p>Wir wünschen Ihnen viel Vergnügen!</p>
		<br><br>Mit freundlichem Gruß
		<br><i>Das MCG-Ticketsystem</i>
		<br><br><small><i>Dies ist eine automatisch generierte Email.</i></small>
	</body>
</html>";

	if (!sendMail($mailTo, $mailSubject, $mailBody, $mailHeader))
		exitError("Fehler beim Versenden der Bestätigungs-Email an die angegebene Email-Adresse (".$_REQUEST["email"]."). Wenden Sie sich mit der Reservierungsnummer an den Administrator: ".ADMIN_EMAIL, "Fehler beim Senden an ".$_REQUEST["email"]);

	echo "<style type='text/css'>
	.box{
		border: 1px solid grey;
		margin-left: 20px;
		margin-top: 20px;
		width: 700px;
		border-radius: 9px;
		min-height: 50px;
		
		margin-bottom: 20px;

	}

	.box .head{
		color: white;
		padding-top: 7px;
		padding-left: 10px;
		padding-bottom: 7px;

		margin: 0px;
		font-weight: bold;
		text-shadow: 0 -2px 0 rgba(0,0,0,0.3);
		font-size: 12px;

		background-color: #00cc65;

		border-top-left-radius: inherit;
		border-top-right-radius: inherit;

		border-bottom: 2px solid rgba(0,0,0, 0.3);
	}

	.box.green .head{ background-color: #35e46e; }

	.box.red .head{ background-color: #cb3301; }

	.box .content{
		
		margin: 0;
		background-color: #ededed;
		color: #4d4958;
		padding: 10px;

		border-bottom-left-radius: inherit;
		border-bottom-right-radius: inherit;
	}

</style>";

echo "
<div class='box green'>
	<div class='head'>Reservierung erfolgreich</div>
	<div class='content'>
		Die Reservierung war erfolgreich; Ihre Reservierungsnummer lautet <b>".$reservierungsID."</b>.<br><br>
		Ihre Einlasskarte wurde erfolgreich an <b>".$_REQUEST["email"]."</b> gesendet.
	</div>
</div>
<i>Sie können diese Seite nun verlassen. <a href=\"javascript:window.close()\">Fenster schließen</a>";

	try{ // Email to Admin, if enabled
		if (MAIL_ADMIN_ON_RESERVE)
		{
			$head = "Reservierung: ".$_REQUEST["name"];
			$body = date(TIMESTAMP_FORMAT)."\r\n"."Name:	".$_REQUEST["name"]."\r\nEmail:	".$_REQUEST["email"]."\r\n"."Termin:	".$_REQUEST["termin"]."\r\nSitze:	".implode(",", $seats)."\r\nReservierungs-ID: ".$reservierungsID;
			@sendMail(ADMIN_EMAIL, $head, $body, "From: MCG-Kartensystem");
		}
	}catch(Exception $e){}
	
}else
{
	// Übersicht ausgeben

	// Eingabemaske für persönliche Informationen
echo "
<style type='text/css'>

	#formEnvelope{
		
		margin: 10px auto;
		border-radius: 10px;
		padding: 12px;
		width: 800px;
	}

	#formHeader{
		font-size: 24px;
		text-align: center;
		padding-bottom: 12px;
	}

	#formBody{
		width: 500px;
		margin: 0 auto;
	}

	.inputBox{
		margin-top: 10px;
		margin-bottom: 5px;
		margin-right: 5px;

		padding: 10px;
		border-radius: 5px;
		border: 1px solid #7ac9b7;
	}

	.label{
		font-size: 13pt;
		margin-top: 20px;
	}

	#submitButtom{
		width: 475px;
		padding: 15px;
		margin-top: 20px;

		border-radius: 5px;
		border: 1px solid #7ac9b7;
		background-color: #218BFF; 
		color: aliceblue;
		font-size: 15px;
		cursor: pointer;
	}

	#submitButtom:hover{
		background-color: #4180C5;
	}

</style>
";

	echo "
	<div id='formEnvelope'>
		<div id='formHeader'>
			Angaben zur Person
		</div>
		<Form id='formBody' Action='' method='post' style=''>
			<div class='label'>Ihr Name</div>
			<input class='inputBox' name='firstname' type='text' placeholder='Max' style='width:140px;'/>
			<input class='inputBox' name='lastname' type='text' placeholder='Mustermann' style='width: 330px;' required/>
			<br><small><i>Vorname optional. Ihr Name befindet sich auf Ihrem Ticket und auf Ihrem Sitzplatz.</i></small>
			<div class='label'>Ihre Email-Adresse</div>
			<input class='inputBox' name='email' type='email' placeholder='max@mustermann.de' style='width:475px;' required/>
			<br><small><i>Ihr Ticket wird an Ihre Email-Adresse gesendet.</i></small>

			<br><br><small><a href='/impressum/' target='_blank'>Datenschutzhinweise</a></small>
			<input type='hidden' name='plaetze' value='".$_REQUEST["plaetze"]."' />
			<input type='submit' id='submitButtom' value='Reservieren' />
		</Form>
	</div><input type='button' value='Zurück' onclick='javascript:history.back();' />";
	
}

require("template/template.foot.inc.php");

} catch(Exception $e){
	echo 'Fehler abgefangen: ',  $e->getMessage(), "\n Kontaktieren Sie umgehend den Administrator: ".ADMIN_EMAIL;
	exit;
}

?>

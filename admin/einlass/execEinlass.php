<?php

/* Einlass */

require(dirname(__FILE__)."/../../src/core/main.php");
require(dirname(__FILE__)."/../../src/template/template.head.inc.php");

// Custom CSS
?>
<style type="text/css">
html, body{
	font-family: sans-serif, arial;
	font-size: 14px;
}
.box{
	border-radius: 9px;
	padding: 5px;
	margin-bottom: 15px;
	margin-top: 5px;
	margin-left: 10px;
}

.box.error{
	background-color: red;
	border: 2px solid darkred;
	color: white;
}

.box.green{
	background-color: green;
	border: 2px solid darkgreen;
	color: white;
}

</style>
<?php
accessLog("Lade Einlass-Exec; Gerät: ".$_SERVER["REMOTE_ADDR"]);

if (!isset($_REQUEST["rn"]) || !is_numeric( $_REQUEST["rn"] ) )
	Die("Fehler - Reservierungsnummer '".$_REQUEST["rn"]."' ist nicht numerisch!");

$rn = $_REQUEST["rn"]; // validated

// Make ticket request
$sql = "SELECT * FROM reservierungen WHERE ID = '".$rn."'";

$result = @mysqli_query( $dbHandle, $sql ) or Die("Fehler bei Datenbankabfrage; ".mysqli_error($dbHandle) );

if ( mysqli_num_rows( $result ) == 0 )
	Die("Fehler - Keine Übereinstimmung in der Datenbank für die übergebene Reservierungsnummer.");
// evtl. > 1?? - NOPE, lässt die Datenbank nicht zu

$reservierung = mysqli_fetch_assoc( $result );

$reserviertePlaetze = explode(PLACE_SEPARATION_SIGN ,$reservierung["plaetze"]);

// Termin abrufen und Informationen anzeigen
// Überprüfen, ob Termin zu weit entfernt
$termin = getTermin( $reservierung["termin"] );

if ( abs( time() - $termin["datetime"] ) > SCAN_TIME_WARN )
{ // WARNUNG: Termin zu weit entfernt
	Die("<div class='box error'><b>Fehler: Termin zeitlich zu weit entfernt!</b><br />Stellen Sie sicher, dass der aktuelle Termin <br /><b>".$termin["name"]."</b><br /> ist!</div>");
}


if ( $reservierung["erschienen"] != "0" )
	Die("<div class='box error'><b>Fehler: Reservierung bereits als anwesend markiert!!</b><br /> (Eintrag: '".$reservierung["erschienen"]."')</div>");

// Übersicht ausgeben
?>
<table border="1" style="border-collapse: collapse; min-width: 50%;" >
	<tr>
		<td style="min-width: 200px;"><b>Reservierung</b></td>
		<td></td>
	<tr>
	<tr>
		<td>Reservierungsnummer</td>
		<td><?php echo $reservierung["ID"]; ?></td>
	</tr>
	<tr>
		<td>Sitzplätze</td>
		<td>(<?php echo sizeof($reserviertePlaetze); ?>)</td>
	</tr>
	<tr>
		<td></td>
		<td><ul><?php foreach($reserviertePlaetze as $p){ echo "<li>".$p."</li>"; } ?></ul></td>
	</tr>
	<tr>
		<td>Name</td>
		<td><?php echo $reservierung["name"]; ?></td>
	</tr>
	<tr>
		<td><b>Termin</b></td>
		<td><?php echo $termin["ID"]; ?></td>
	</tr>
	<tr>
		<td></td>
		<td><?php echo $termin["name"]; ?></td>
	</tr>
</table>
<?php
// HIER: Evtl. überprüfen, ob bereits bezahlt

// Reservierung vornehmen

// Als erschienen setzen
$sql = "UPDATE reservierungen SET erschienen='1' WHERE ID='".$reservierung["ID"]."'";
$result = @mysqli_query($dbHandle, $sql) or Die("<div class='box error'><b>Fehler</b><hr>Fehler beim Setzen der Anwesenheit von Reservierung ".$reservierung["ID"].".; ERROR: ".mysqli_error($dbHandle )."</div>" );

// Evtl. wurden mehrere Datensätze betroffen, was allerdings nie der Fall sein darf und nicht passieren sollte
if ( mysqli_affected_rows($dbHandle) != 1)
	Die("Es wurden '".mysqli_affected_rows($dbHandle)."' Zeilen aktualisiert; es sollte eigentlich nur 1 Zeile aktualisiert werden!!!!<hr>");

echo "<div class='box green'>✓ Reservierung aktualisert: anwesend</div>";
echo "<script type='text/javascript'>window.open( 'viewSaal.php?termin=".$termin["ID"]."&places=".$reservierung["plaetze"]."', 'saalView', 'resizable');</script>";

?>

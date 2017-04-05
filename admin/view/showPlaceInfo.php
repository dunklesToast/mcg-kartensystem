<?php

if (empty($_REQUEST["termin"]) || !is_numeric($_REQUEST["termin"]) || empty($_REQUEST["nummer"]) || !is_numeric($_REQUEST["nummer"]) )
{
    var_dump($_REQUEST);
    Die("FEHLER - UNGÜLTIGE ABFRAGE!!");
}

require(dirname(__FILE__)."/../../src/core/main.php");

$terminID = $_REQUEST["termin"];
$termin = getTermin($terminID);

$sitzplatzID = $_REQUEST["nummer"];

if ($termin == false || $termin == 0 || !is_array($termin))
    Die("Fehler beim Abfragen des Termins ".$terminID );

// Reservierungen abrufen
$sql = "SELECT * FROM reservierungen WHERE termin='".$termin["ID"]."'";
$r = @mysqli_query($dbHandle, $sql);
if ($r === false) exitError("Fehler bei Datenbankabfrage der Reservierungen; ".mysqli_error($dbHandle));

$reservierungen = array();
while($t = mysqli_fetch_assoc($r) ){ $reservierungen[] = $t; }

$takenReserve = -1;
// In den Reservierungen nachsehen
foreach($reservierungen as $r) {
    if ( in_array( $sitzplatzID, explode( PLACE_SEPARATION_SIGN, $r["plaetze"] ) ) ){
        $takenReserve = $r;
    }
}

?><html>
    <head>
        <title>Sitzplatz-Information</title>
	<meta charset="UTF-8" />
    </head>
    <body>
        
        <table border="1" cellpadding="0" cellspacing="0" style="border-collapse:collapse; margin: 5px; padding: 5px;">
            <tr>
                <td style="min-width: 200px;"><strong>Termin</strong></td>
                <td style="min-width: 200px;"></td>
            </tr>
            <tr>
                <td>Termin-ID</td>
                <td><?php echo $terminID; ?></td>
            </tr>
            <tr>
                <td>Name</td>
                <td><?php echo $termin["name"]; ?></td>
            </tr>
            <tr>
                <td>Datum & Zeit</td>
                <td><?php echo date(TIMESTAMP_FORMAT, $termin["datetime"]); ?> Uhr</td>
            </tr>
            <tr>
                <td><strong>Platz</td>
                <td></td>
            <tr>
            <tr>
                <td>Sitzplatz-Nummer</td>
                <td><?php echo $sitzplatzID; ?></td>
            </tr>
            <tr>
                <td>Reserviert</td>
                <td><?php echo ($takenReserve == -1) ? "Nein" : "Ja"; ?></td>
            </tr>
            <?php
                if ($takenReserve != -1)
                {
                    echo "<tr>
                        <td>Reservierungs-Nummer</td>
                        <td>".$takenReserve["ID"]."  ( auf '".$takenReserve["name"]."' )</td>
                    </tr>
                    <tr>
                        <td>Anwesend</td>
                        <td>".( ($takenReserve["erschienen"] != 0) ? "Ja" : "Nein" )."</td>
                    </tr>";
                }
            ?>
        </table>
    </body>
</html>

<?php

/* Saal-Übersicht */

require(dirname(__FILE__)."/../../src/core/main.php");
require(dirname(__FILE__)."/../../src/template/template.head.inc.php");

accessLog("Lade Saal-Übersicht; Gerät: ".$_SERVER["REMOTE_ADDR"]);

// Termin-Übersicht anzeigen
$sql = "SELECT * FROM termine"; // ohne Ausnahme; auch noch nicht geöffnete
$result = @mysqli_query($dbHandle, $sql) or exitError(DEFAULT_PUB_ERROR, "Fehler bei Abfrage der Termine aufgetreten. Fehler: ".mysqli_error($dbHandle));

if (!$result === false)
{
	echo "<Form action='' method='get'>Termin: <select name='termin'>";
	while($termin = mysqli_fetch_assoc($result) )
	{
		echo "<option value='".$termin["ID"]."'>".$termin["ID"]." - ".$termin["name"].", ".date(TIMESTAMP_FORMAT, $termin["datetime"])." Uhr</option>";
	}
	echo "</select><input type='submit' value='Termin Auswählen' /></Form>";
}

echo "<hr>";
?>

<style type="text/css">
#selectSeat_mainWrapper {
	position: relative;
}

.seat {
	position: absolute;
	height: 20px;
	width: 20px;
}

.seat_info_box {
    display: none;
}

</style>

<div id="selectSeat_mainWrapper">

<?php

if (!empty($_REQUEST["termin"]) && is_numeric($_REQUEST["termin"]))
{ // Termin anzeigen

	// Termin laden
	$termin = getTermin($_REQUEST["termin"]);

	// Kleine Übersicht über den Termin anzeigen
	// TODO

	// Saalübersicht
	echo '<img src="/data/plans/'.$termin["plan"].'.png" width="800px" height="600px" />';

	$sql = "SELECT * FROM plaetze WHERE plan='".$termin["plan"]."'";
	$result = @mysqli_query($dbHandle, $sql);

	if ($result === false)
		exitError("Fehler bei Datenbankabfrage der Plätze; ".mysqli_error($dbHandle));
	
	$sql = "SELECT * FROM reservierungen WHERE termin='".$termin["ID"]."'";
	$r = @mysqli_query($dbHandle, $sql);
	if ($r === false) exitError("Fehler bei Datenbankabfrage der Reservierungen; ".mysqli_error($dbHandle));

	$reservierungen = array();
	while($t = mysqli_fetch_assoc($r) ){ $reservierungen[] = $t; }

	while( $platz = mysqli_fetch_assoc($result) )
	{

		$takenReserve = -1;
		// In den Reservierungen nachsehen
		foreach($reservierungen as $r) {
			if ( in_array( $platz["nummer"], explode( PLACE_SEPARATION_SIGN, $r["plaetze"] ) ) ){
				$takenReserve = $r;
			}
		}

        $sitzURL = "";

		// Ausgabe
		if ($takenReserve != -1)
		{ // Reserivert
            // $sitzURL = "/data/seats/seat.red.png";
            $sitzURL = ( $takenReserve["erschienen"] != 0 ) ? "/data/seats/seat.green.png" : "/data/seats/seat.red.png";

        }else
        { // nicht reserviert; grün
            $sitzURL = "/data/seats/seat.yellow.png";
        }
        
        // Sitz
        echo '<img class="seat" src="'.$sitzURL.'" style="top: '.$platz["y"].'px; left: '.$platz["x"].'px;" title="Platz '.$platz["nummer"].'" onclick=\'window.open("showPlaceInfo.php?termin='.$termin["ID"].'&nummer='.$platz["nummer"].'", "placeInfoWindow", "width=600, height=730"); return false;\'/>';

        /*
        Platz Info Box
        
        // Platz-Informationen zusammensetzen
            $msg = "\\nPlatz Nummer    ".$platz["nummer"];
            $msg .= "\\nReserviert    Ja ( ".$takenReserve["ID"]." )";
            $msg .= "\\nName          ".$takenReserve["name"];
        */
        
		/*
			Möglichkeiten:
				Reserviert [y/n]
				Anwesend   [y/n]
				Bezahlt    [y/n]
		*/		

	}

}

?>

<?php

/*** Sitzplatz-Auswahl ***/

// Core laden
require("core/main.php");

accessLog("Initialisiere Sitzplatz-Auswahl.");

// Termininformationen abrufen
if (empty($_GET["termin"]) || !is_numeric($_GET["termin"]))
	exitError("Fehler - Kein oder ungültiger Termin: '".$_GET["termin"]."'", "Kein/ungültiger Termin: ".$_GET["termin"]);

accessLog("Übergebener Termin: ".$_GET["termin"]);
$terminID = $_GET["termin"];
$termin = getTermin($terminID);

// Termin validieren
if ($termin["open"] < 2)
	exitError(DEFAULT_PUB_ERROR, "Termin noch nicht zur Reservierung freigegeben.");

// Evtl. Termin ausgebucht?

// Header
require("template/template.head.inc.php");
?>
	<style type="text/css">
		#selectSeat_mainWrapper {
				position: relative;
				width: 500px;
			}

			#selectSeat_title {
				font-size: 16pt;
				font-weight: normal;
			}


			.seat {
				position: absolute;
				height: 20px;
				width: 20px;
			}
			
			#selectSeat_selectedSeatsListWrapper {
				position: fixed;
				bottom: 0px;
				left: 0px;

				width: 100%;
				height: 130px;
				overflow: hidden;

				background-color: rgba(0,0,0,0.2);
			}

			#selectSeat_selectedSeatsList {

				margin: 10px auto;
				padding: 10px;

				width: 900px;

				background-color: #efefef;
			}

	</style>

	<script type="text/javascript">
			var selectedSeats = [];
			var maxSeatsSelect = <?php echo ($termin["platzAuswahlMax"] <= 0) ? "-1" : $termin["platzAuswahlMax"]; ?>;

			function gs(t)
			{ // Auswählen eines Sitzes
				if (selectedSeats.indexOf(t) == -1)
				{ // Limit
					if(maxSeatsSelect != -1 && selectedSeats.length + 1 > maxSeatsSelect)
						{ alert("Sie dürfen maximal " + maxSeatsSelect + " Sitz(e) auswählen."); return; }
					selectedSeats.push(t);
					document.getElementById("seat_" + t).src = '/data/seats/seat.white.png';
				}else{ // Deselektieren
					selectedSeats.splice(selectedSeats.indexOf(t), 1);
					document.getElementById("seat_" + t).src = '/data/seats/seat.green.png';
				}
				selectedSeats.sort();
				
				var temp = "<p>" + selectedSeats.join(", ") + "</p>";
				
				document.getElementById("selectSeat_selectedSeatsListDetail").innerHTML = temp;
				document.getElementById("selectSeat_selectedSeatsReserveFormInput").value = JSON.stringify(selectedSeats);
				document.getElementById("selectSeat_selectedSeatsReserveFormSubmit").style.display = (selectedSeats.length > 0) ? "block" : "none";
			}
		</script>
		
		<!-- force reload -->
		<meta http-equiv="pragma" content="no-cache" />
		<meta http-equiv="cache-control" content="no-cache" />
		<meta http-equiv="expires" content="0" />

		<p id="selectSeat_title">Sitzplatzauswahl</p>
		
		<!-- Info -->
		<small><i>Termininformationen</i></small>
		<div id="selectSeat_terminInfoTable">
			<table border="0" style="border: 1px solid black; padding: 5px; margin-top: 3px; width: 80%; margin-bottom: 20px; ">
				<tr>
					<td style="width: 190px;"><b>Veranstaltung</b></td>
					<td><?php echo $termin["name"]; ?></td>
				</tr>
				<tr>
					<td><b>Zeit</b></td>
					<td><?php echo date(TIMESTAMP_FORMAT, $termin["datetime"]); ?></td>
				</tr>
				<tr>
					<td><b>Ort</b></td>
					<td><?php echo $termin["ort"]; ?></td>
				</tr>
				<tr>
					<td><b>Beschreibung</b></td>
					<td><?php echo $termin["description"]; ?></td>
				</tr>
			</table>
		</div>
		<hr />
		<div id="selectSeat_mainWrapper">
			<img src="/data/plans/<?php echo $termin["plan"]; ?>.png" width="800px" height="600px" />
<?php

$sql = "SELECT * FROM plaetze WHERE plan='".$termin["plan"]."'";
$result = @mysqli_query($dbHandle, $sql);

if ($result === false)
	exitError(DEFAULT_PUB_ERROR, "Fehler bei Datenbankabfrage der Plätze; ".mysqli_error($dbHandle) );

while($line = mysqli_fetch_assoc($result))
{
	
	$sqlR = "SELECT * FROM reservierungen WHERE termin='".$termin["ID"]."' AND plaetze LIKE '%".$line["nummer"]."%'";
	$r = @mysqli_query($dbHandle, $sqlR);
	if ($r == false)
		exitError(DEFAULT_PUB_EROR);
	
	$reserved = false;
	
	while($l = mysqli_fetch_assoc($r))
	{
		$temp = explode(PLACE_SEPARATION_SIGN, $l["plaetze"]);
		if (in_array($line["nummer"], $temp))
		{ // reserviert
			$reserved = true;
			break;
		}
	}
	
	if ($reserved)
	{ // Reserviert
		echo '<img id="seat_'.$line["nummer"].'" class="seat" src="/data/seats/seat.red.png" style="top: '.$line["y"].'px; left: '.$line["x"].'px;" title="Platz '.$line["nummer"].' - Reserviert" />';
	}else
	{
		echo '<img id="seat_'.$line["nummer"].'" class="seat" src="/data/seats/seat.green.png" style="top: '.$line["y"].'px; left: '.$line["x"].'px;" title="Platz '.$line["nummer"].'" onclick="javascript:gs('.$line["nummer"].');" />';
	}
	
}

?>
	</div>
		<div id="selectSeat_selectedSeatsListWrapper">
		<div id="selectSeat_selectedSeatsList">
			<b>Ausgewählte Sitze:</b>
			<div id="selectSeat_selectedSeatsListDetail">
				Noch kein Sitzplatz ausgewählt.
				<i>Klicken Sie auf einen Sitzplatz, um diesen zu reservieren.</i>
				<p>
				<?php
					if ($termin["platzAuswahlMax"] >= 0) echo "<i> Sie können insgesamt maximal ".$termin["platzAuswahlMax"]." Plätze reservieren.";
				?>
				</p>
			</div>
			<div id="selectSeat_selectedSeatsReserveForm">
				<Form action='ausfuehren/' method='post' accept-charset="UTF-8">
					<input id='selectSeat_selectedSeatsReserveFormInput' type='hidden' name='plaetze' value='' />
					<input id='selectSeat_selectedSeatsReserveFormSubmit' style='display: none;' type='submit' value='Reservieren >>' />
				</Form>
			</div>
		</div>
	</div>

<?php
// Footer
require("template/template.foot.inc.php");
?>

<?php

/* Einlass */

require(dirname(__FILE__)."/../../src/core/main.php");
require(dirname(__FILE__)."/../../src/template/template.head.inc.php");

if (!is_numeric($_REQUEST["termin"]) )
	Die("ungültiger Termin!");

$termin = getTermin($_REQUEST["termin"]);

$seatsToShow = explode(PLACE_SEPARATION_SIGN , $_REQUEST["places"] );

?><html>
	<head>
		<title>Sitzplatz-Übersicht</title>
		<meta charset="UTF-8" />
	</head>
	<body>
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
		<div style="width: 840px; margin: 0 auto;">
			<h3>Ihre Sitzplätze</h3>
			<div id="selectSeat_mainWrapper">
			<img src="/data/plans/<?php echo $termin["plan"]; ?>.png" width="800px" height="600px" />
<?php

$sql = "SELECT * FROM plaetze WHERE plan='".$termin["plan"]."'";
$result = @mysqli_query($dbHandle, $sql);

if ($result === false)
	exitError(DEFAULT_PUB_ERROR, "Fehler bei Datenbankabfrage der Plätze; ".mysqli_error($dbHandle) );

while($line = mysqli_fetch_assoc($result))
{
	echo '<img id="seat_'.$line["nummer"].'" class="seat" src="/data/seats/seat.'.( ( in_array($line["nummer"], $seatsToShow) ) ? 'green': 'white' ).'.png" style="top: '.$line["y"].'px; left: '.$line["x"].'px;" title="Platz '.$line["nummer"].'" />';
}

?>
			</div>
		</div>
	</body>
</html>

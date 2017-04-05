<?php
if (!defined("CORE")) exit(0);

/*** Logging-Funktionen ***/

// Zentrale Log-Funktion
function myLog($text, $file) {
	$line = $line = "\n".date(TIMESTAMP_FORMAT_LOG).":\t".$text;
	@file_put_contents($file, $line, FILE_APPEND) or Die("SCHWERWIEGENDER SYSTEMFEHLER AUFGETRETEN. KONTAKTIEREN SIE BITTE UMBEDINGT DEN ADMINISTRATOR: ".ADMIN_EMAIL);
}

function accessLog($text) { if (LOG_ACCESS_ENABLE) myLog($text, LOG_ACCESS_FILENAME); }
function reserveLog($text) { myLog($text, LOG_RESERVE_FILENAME); }
function exitError($pub, $priv) { myLog($priv, LOG_ERROR_FILENAME); accessLog("////// ERROR OCCURRED !!!!!"); Die($pub); }

/*** Zugriff auf Termine ***/
function getTermin($terminID)
{
	global $dbHandle;
	
	if (empty($terminID) || !is_numeric($terminID) || $terminID <= 0)
		exitError("Fehlerhafte ID übergeben: '".$terminID."'. Wenden Sie sich im Zweifelsfall an den Administrator.", "Fehlerhafte Termin-ID übergeben: '".$terminID."'");
	
	$sql = "SELECT * FROM termine WHERE ID = '".$terminID."'";
	$result = @mysqli_query($dbHandle, $sql);
	
	if ($result == false)
		exitError(DEFAULT_PUB_ERROR, "Fehler bei Datenbankabfrage für Termin '".$terminID."': ".@mysqli_error($dbHandle));

	if (@mysqli_num_rows($result) == 0){
		exitError("Fehler - Termin nicht gefunden. Wenden Sie sich an den Administrator: ".ADMIN_EMAIL, "Termin nicht gefunden: '".$terminID."'");
	} else if (@mysqli_num_rows($result) > 1) {
		exitError(DEFAULT_PUB_ERROR, "Mehrere Termine für die ID ".$terminID." in Datenbank gefunden (".$terminID.")!!");
	}
	
    $result = @mysqli_fetch_assoc($result) or exitError(DEFAULT_PUB_ERROR, "Konnte Informationen aus Termin-Abfrage nicht extrahieren!! (fetch_assoc failed)!!");
    
	// evtl. abfangen, wenn termin noch nicht offen
	if (empty($result["open"]) || $result["open"] <= 0) exitError(DEFAULT_PUB_ERROR, "Termin nicht geöffnet: ".$terminID. " !!!");
    
    $result["open"] = intval($result["open"]); // erzwingen, dass open ein Integer ist
    
	return $result;
}

function Encode($text) {
		return mb_convert_encoding($text, "ISO-8859-15", "UTF-8");
}

/*** Ticket-Erstellung im PDF-Format ***/
// Reservierung vorgenommen; Erstellung des korrespondierenden PDFs
function createTicket($reserveID, $directOutput = false)
{
	global $dbHandle;

	// Validierung der reserveID
	if (empty($reserveID) || !is_numeric($reserveID)) {
		accessLog("Fehler beim Erstellen des Tickets; Reservierungs-ID '".$reserveID."' fehlerhaft.");
		return false;
	}
	
	// Reservierung aus Datenbank laden
	$sql = "SELECT * FROM reservierungen WHERE ID='".$reserveID."'";
	$result = @mysqli_query($dbHandle, $sql);
	if ($result == false) {
		accessLog("Fehler beim Erstellen des Tickets; Fehler bei Datenbankabfrage: ".mysqli_error($dbHandle));
		return false;
	}
	
	if (mysqli_num_rows($result) != 1) {
		accessLog("Fehler beim Erstellen des Tickets; Keine oder mehrere Ergebnisse für Ticket ".$reserveID. ":". mysqli_num_rows($result));
		return false;
	}
	
	if (!$reservierung = mysqli_fetch_assoc($result))
		exitError(DEFAULT_PUB_ERROR, "Fehler beim Erstellen des Tickets; Fehler beim Auslesen des Results.");
	
	// Zugehörigen Termin finden
	$termin = getTermin($reservierung["termin"]);
	
	// Maximale Längen überprüfen und ggf. beschneiden
	$data = array();
	$data["name"] = (strlen($reservierung["name"]) > MAX_NAME_LENGTH) ? substr($reservierung["name"], 0, MAX_NAME_LENGTH)."..." : $reservierung["name"];
	$data["terminName"] = $termin["name"];
	$data["terminDatum"] = date(TIMESTAMP_FORMAT, $termin["datetime"])." Uhr";
	$data["terminOrt"] = $termin["ort"];
	$data["seats"] = explode(PLACE_SEPARATION_SIGN, $reservierung["plaetze"]);
	
	// ======== PDF generieren =============== //
	
	require_once(__DIR__."/../fpdf/fpdf.php"); // FPDF-Bibliothek einbinden
	
	$pdf = new fpdf('L','mm',array(213,80));
	$pdf->AddPage();
	
	$pdf->SetDrawColor(0, 0, 0);
	$pdf->SetTextColor(0, 0, 0);
	$pdf->SetAutoPageBreak(False); // wichtig
	
	// MCG-Header
	$pdf->SetFont('Arial', '', 9);
	$pdf->SetXY(10, 10);
	$pdf->Cell(0, 0, "MATTHIAS-CLAUDIUS-GYMNASIUM GEHRDEN");
	
	// Einlasskarte-Header
	$pdf->SetFont('Arial', 'B', 14);
	$pdf->SetXY(10, 14);
	$pdf->Cell(115, 10, 'Einlasskarte', 1, 0, 'C');
	
	// Veranstaltung
	$pdf->SetFont('Arial', 'I', 8);
	$pdf->SetXY(10, 29);
	$pdf->Cell(0, 0, "Veranstaltung");
	
	$pdf->SetFont('Arial', 'B', 14);
	$pdf->SetXY(10, 34);
	$pdf->Cell(0, 0, Encode($data["terminName"]));
	
	// Datum
	$pdf->SetFont('Arial', 'I', 8);
	$pdf->SetXY(10, 40);
	$pdf->Cell( 0, 0, "Datum" );
	
	$pdf->SetFont('Arial', 'B', 11);
	$pdf->SetXY(10, 45);
	$pdf->Cell( 0, 0, Encode($data["terminDatum"]) );
	
	// Ort
	$pdf->SetFont('Arial', 'I', 8);
	$pdf->SetXY(70, 40);
	$pdf->Cell( 0, 0, "Ort" );
		
	$pdf->SetFont('Arial', 'B', 11);
	$pdf->SetXY(70, 45);
	$pdf->Cell( 0, 0, Encode($data["terminOrt"]) );
	
	// Name
	$pdf->SetFont('Arial', 'I', 8);
	$pdf->SetXY(10, 54);
	$pdf->Cell( 0, 0, "Name" );
		
	$pdf->SetFont('Arial', 'B', 11);
	$pdf->SetXY(10, 59);
	$pdf->Cell( 0, 0, Encode($data["name"]) );
	
	// Plätze
	$pdf->SetFont('Arial', 'I', 8);
	$pdf->SetXY(70, 54);
	$pdf->Cell( 0, 0, (sizeof($data["seats"]) > 1) ? Encode("Sitzplätze") : Encode("Sitzplatz") );
	
	$pdf->SetFont('Arial', 'B', 11);
	$pdf->SetXY(70, 59);
	
	$seatString = implode(",", $data["seats"]);
	if (strlen($seatString) > MAX_SEAT_WIDTH) $seatString = substr($seatString, 0, MAX_SEAT_WIDTH - 2)."...";
	
	$pdf->Cell( 0, 0, $seatString );
	
	// Abrisslinie rechts
	$pdf->SetLineWidth(0.4);
	$pdf->Line(150, 5, 150, 72);
	
	// QR-Code
	$qrData = "http://<DOMAIN>/scan/".$reserveID."_".sha1($reserveID.TICKET_TOKEN_SALT);
	// https://chart.googleapis.com/chart?cht=qr&chs=400x400&chl=N
	$pdf->Image('https://chart.googleapis.com/chart?cht=qr&chs=400x400&chl='.$qrData, 155, 5, 50, 50, 'PNG');	
	
	// Reservierungsnummer ausgeben
	$pdf->SetFont('Arial', 'BI', 9);
	$pdf->SetXY(160, 59);
	$pdf->Cell( 0, 0, "RN: ".$reserveID );
	
	// Footer
	$pdf->SetXY(10, 76);
	$pdf->SetFont('Helvetica', '', 6);
	$pdf->Cell(0, 0, TICKET_FOOTER_TEXT );
	
	// *** Speichern *** //
	$path = __DIR__."/../../data/tickets/";
	$file = $reserveID."_".sha1($reserveID.TICKET_TOKEN_SALT).".pdf";
	$fileName = $path.$file;
	if (file_exists($fileName)) @unlink($fileName);
	
    $pdf->Output($fileName, "F"); // In jedem Fall als Datei speichern
    
    if ($directOutput)
    { $pdf->Output(); } // Falls angefordert, direkt ausgeben

	return $file;
}

// Sendmail
// Ehemals API
function sendMail($to, $subj, $body, $header)
{
	mail($to, $subj, $body, $header);
	//mail($to, $subj, $body, $header) or exitError("Fehler beim Versenden der Email an ".$to."; bitte wenden Sie sich an den Administrator: ".ADMIN_EMAIL, "Interner Fehler beim Versenden der Email aufgetreten: ".error_get_last());
	return true;
}

?>

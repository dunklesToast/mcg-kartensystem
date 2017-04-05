<?php

/***** Ticket erstellen *****/

// Core laden
require("core/main.php");

accessLog("Registriere das Abrufen eines Tickets [getTicket.php]");

// Gegeben:
// ticket_id
// ticket_hash

if (empty($_REQUEST["ticket_id"]) || !is_numeric($_REQUEST["ticket_id"]) || empty($_REQUEST["ticket_hash"]))
    exitError(DEFAULT_PUB_ERROR, "Fehlerhafte Daten übergeben: ".serialize($_REQUEST));

// ist das Ticket valide?
$sql = "SELECT * FROM reservierungen WHERE ID='".$_REQUEST["ticket_id"]."'";
$result = @mysqli_query($dbHandle, $sql) or exitError(DEFAULT_PUB_ERROR, "Fehler beim Überprüfen des Tickets; ".$_REQUEST["ticket_id"]);

// Formale Übereinstimmung der Hashes
$valid_hash = sha1($_REQUEST["ticket_id"].TICKET_TOKEN_SALT);
if ( $_REQUEST["ticket_hash"] != $valid_hash )
    exitError("Das angeforderte Ticket konnte nicht abgerufen werden; der Sicherheitscode ist invalide. Kontaktieren Sie im Zweifelsfall den Administrator: ".ADMIN_EMAIL, "Sicherheitscode invalide. ".serialize($_REQUEST)); 

if ($result == false || mysqli_num_rows($result) < 1)
{ // Ticket abgelaufen
    exitError("Das von Ihnen angeforderte Ticket existiert nicht [mehr]. Stellen Sie sicher, dass es nicht storniert wurde. Wenden Sie sich im Zweifelsfall an den Administrator: ".ADMIN_EMAIL, "Ticket existert nicht mehr, aber ID und Hash valide. ".serialize($_REQUEST));

}else if(mysqli_num_rows($result) == 1)
{ // Alles OK
    
    // Ticket neu erstellen und automatisch ausgeben lassen
    $ticketFileName = createTicket($_REQUEST["ticket_id"], true);
    
    exit();

}else if(mysqli_num_rows($result) > 1)
{ // Mehr als 1 mit der selben ID????
    exitError(DEFAULT_PUB_ERROR, "WARNUNG: MEHRERE TICKETS MIT DER SELBEN ID!!");
}

?>
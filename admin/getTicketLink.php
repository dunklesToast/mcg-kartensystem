<?php

// Include Core
require("./../src/core/main.php");

if ( !empty($_REQUEST["id"]) && is_numeric($_REQUEST["id"]) )
{
    $link = $_REQUEST["id"]."_".sha1($_REQUEST["id"].TICKET_TOKEN_SALT);
    
    echo "Ticket PDF: <a href='/ticket/".$link.".pdf' target='_blank'>".$link."</a>";
    echo "<hr>Fake manual scan: <a href='/scan/".$link."' target='_blank'>".$link."</a>";
}else {
    echo "<Form action=''><input type='number' name='id' /><input type='submit' value='Link generieren' /></Form>";
}

?>
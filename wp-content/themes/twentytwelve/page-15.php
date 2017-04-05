<?php
/* Template Name: Custom--Termine */

/**
 * The template for displaying all pages
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site will use a
 * different template.
 *
 * @package WordPress
 * @subpackage Twenty_Twelve
 * @since Twenty Twelve 1.0
 */

get_header(); ?>

<script type="text/javascript" src="/data/popupScript.js"></script>

<style type="text/css">
	#main{
		min-width: 700px !important;
	}
    #custom_table{
        background-color: white;
    }
</style>

	<div id="primary" class="site-content">
		<div id="content" role="main">			
			<article id="post-23" class="post-23 page type-page status-publish hentry">
			<?php
				require_once(dirname(__FILE__)."/./../../../src/core/main.php");

				if (!empty($_REQUEST["termin"]) && is_numeric($_REQUEST["termin"]))
				{
                    
// ========== Termin-Detail ========== //

$termin = getTermin($_REQUEST["termin"]);

					echo '
<header class="entry-header">
	<h1 class="entry-title">Informationen zum Termin</h1>
</header>
<div class="entry-content">
	<a href="?termin=">Zurück zur Übersicht</a>
	<h3>Information zum Termin</h3>
	<table id="custom_table">
		<tr>
			<td><strong>Name</strong></td>
			<td>'.$termin["name"].'</td>
		</tr>
		<tr>
			<td><strong>Zeit</strong></td>
			<td>'.date(TIMESTAMP_FORMAT, $termin["datetime"]).' Uhr</td>
		</tr>
		<tr>
			<td><strong>Ort</strong></td>
			<td>'.$termin["ort"].'</td>
		</tr>
	</table>
	<small>Zusätzliche Beschreibung:</small>
	<p><i>'.(empty($termin["description"]) ? "/" : $termin["description"]).'</i></p>';

if ($termin["open"] >= 2)
{ // Resevierungs-Link nur anzeigen, wenn zur Reservierung freigegeben
    $reserveURL = "/reservierung/".$termin["ID"]."/";
    echo '<a href="'.$reserveURL.'" target="reservePopup" onclick="return popup(this, 860, 820)" );">Reservieren</a>';
}else{
    echo "<p><b>Information zur Reservierung</b><br>Dieser Termin ist zwar gelistet, aber noch nicht zur Reservierung freigegeben worden.</p>";
}

echo '</div><!-- .entry-content -->';
				}else
				{
                    
// ========== Termin-Übersicht ========== //
					echo '
<header class="entry-header">
	<h1 class="entry-title">Veranstaltungen - Übersicht</h1>
</header>
<div class="entry-content">
	<table id="custom_table">
		<tr>
			<td><strong>Name</strong></td>
			<td><strong>Zeit</strong></td>
			<td><strong>Ort</strong></td>
			<td><strong>Reservieren</strong></td>
		</tr>';

// Öffene Termine aus Datenbank lesen
$sql = "SELECT * FROM termine WHERE open > 0 AND UNIX_TIMESTAMP() < datetime ORDER BY datetime";
$result = @mysqli_query($dbHandle, $sql) or Die("Fehler bei Datenbankabfrage; kontaktieren Sie den Admin:".ADMIN_EMAIL);

while($termin = mysqli_fetch_assoc($result))
{
	echo "<tr>";
	echo "<td>".$termin["name"]."</td>";
	echo "<td>".date(TIMESTAMP_FORMAT, $termin["datetime"])." Uhr</td>";
	echo "<td>".$termin["ort"]."</td>";
    
	echo "<td><a href=\"?termin=".$termin["ID"]."\">Details</a>";
    if ($termin["open"] >= 2)
        echo ", <a href=\"/reservierung/".$termin["ID"]."/\" target=\"reservePopup\" onclick=\"return popup(this, 860, 820)\" );\">Reservieren</a>";
    echo "</td>";
	
    echo "</tr>";
}

if (mysqli_num_rows($result) === 0)
	echo "<tr><td><i>Zur sind zur Zeit [noch] keine anstehenden Termine gelistet.</i></td></tr>";

echo '
	</table>
</div><!-- .entry-content -->
';

				}
			?>
			</article><!-- #post -->
		</div><!-- #content -->
	</div><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>

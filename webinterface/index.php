<?php $tab="1"; $pagetitle="Home"; include("menu.php"); contentHeader();

####################################
# SURFnet IDS 2.10.00              #
# Changeset 002                    #
# 03-03-2008                       #
# Jan van Lith & Kees Trippelvitz  #
# Peter Arts                       #
####################################

#############################################
# Changelog:
# 002 Added some comments, split up sql for readability
# 001 Added language support
#############################################

session_start();

$sql_modssel = "SELECT phppage FROM indexmods_selected, indexmods ";
$sql_modssel .= " WHERE indexmods_selected.login_id = $s_userid AND indexmods_selected.indexmod_id = indexmods.id";
$debuginfo[] = $sql_modssel;
$result_modssel = pg_query($pgconn, $sql_modssel);
while ($row_modssel = pg_fetch_assoc($result_modssel)) {
  $phppage = $row_modssel['phppage'];
  $ar_phppage[] = $phppage;
}
$countpages = count($ar_phppage)-1; 

for ($oe = 0; $oe <= $countpages; $oe++) {
  if (($oe % 2) == 0) {
    echo "<div class='all'>\n";
    echo "<div class='leftmed'>\n";
    include "../include/indexmods/$ar_phppage[$oe]";
    echo "</div>\n"; #</leftmed>
    if ($oe == $countpages) {
      echo "</div>\n"; #</all>
    } 	
  }
  if (($oe % 2) != 0) {
    echo "<div class='rightmed'>\n";
    include "../include/indexmods/$ar_phppage[$oe]";
    echo "</div>\n"; #</rightmed>
    echo "</div>\n"; #</all>
  }
}

debug_sql();
?>
<?php footer(); ?>

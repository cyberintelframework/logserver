<?php $tab="1"; $pagetitle="Home"; include("menu.php"); contentHeader();

####################################
# SURFnet IDS                      #
# Version 2.10.02                  #
# 13-12-2007                       #
# Jan van Lith & Kees Trippelvitz  #
# Peter Arts                       #
####################################

#############################################
# Changelog:
# 2.10.02 Added some comments, split up sql for readability
# 2.10.01 Added language support
# 2.00.02 Fixed bug with attacks.dport=0
# 2.00.01 version 2.00
# 1.04.07 Fixed a port link when today was selected
# 1.04.06 Fixed some layout issues
# 1.04.05 Added dropdown box
# 1.04.04 Added empty flag for unknown countries
# 1.04.03 Added geoip and p0f stuff
# 1.04.02 Added some graphs and stats 
# 1.04.01 Added changelog and GD check
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

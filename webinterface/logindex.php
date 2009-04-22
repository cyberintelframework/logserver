<?php $tab="3.1"; $pagetitle="Attacks"; include("menu.php"); contentHeader(); ?>
<?php

####################################
# SURFids 3.00                     #
# Changeset 003                    #
# 13-02-2009                       #
# Jan van Lith & Kees Trippelvitz  #
####################################
# Contributors:                    #
# Peter Arts                       #
####################################

#############################################
# Changelog:
# 003 Using the indexmodule instead
# 002 Added ARP exclusion stuff
# 001 Added language support
#############################################

echo "<div class='all'>\n";
  echo "<div class='leftmed'>\n";
    include "../include/indexmods/mod_attacks.php";
  echo "</div>\n"; #</leftmed>
echo "</div>\n"; #</all>

pg_close($pgconn);
debug_sql();
?>
<?php footer(); ?>

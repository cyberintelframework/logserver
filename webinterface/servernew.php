<?php include("menu.php"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.02.03                  #
# 08-08-2006                       #
# Kees Trippelvitz                 #
####################################

#############################################
# Changelog:
# 1.02.03 Added intval() to $s_org and $s_admin
# 1.02.02 Added failsafe exit command
# 1.02.01 Initial release
#############################################

include 'include/config.inc.php';
include 'include/connect.inc.php';
include 'include/functions.inc.php';

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);

if ( $s_admin == 0 ) {
  $err = 1;
  $m = 91;
  header("location: serveradmin.php?m=$m");
  exit;
}
else {
  echo "<h3><a href='useradmin.php'>User administration</a> | <a href='orgadmin.php'>Organisations Admin</a> | Server Admin</h3>\n";
}

if ($err != 1) {
  echo "<form action='serversave.php' method='POST'>\n";
    echo "<table class='datatable'>\n";
      echo "<tr>\n";
        echo "<td class='datatd'>Server</td>\n";
        echo "<td class='datatd'><input type='text' name='f_server' value='' style='width: 100%;' /></td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td class='datatd' colspan='2' align='right'><input type='submit' name='submit_server' value='Save' class='button' /></td>\n";
      echo "</tr>\n";
    echo "</table>\n";
  echo "</form>\n";
}
?>
<?php footer(); ?>

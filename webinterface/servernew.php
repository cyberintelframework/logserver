<?php include("menu.php"); set_title("Server Admin");  ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.02.02                  #
# 28-07-2006                       #
# Kees Trippelvitz                 #
####################################

#############################################
# Changelog:
# 1.02.02 Added exit after admin check
# 1.02.01 Initial release
#############################################

$s_org = $_SESSION['s_org'];
$s_admin = $_SESSION['s_admin'];
$s_access = $_SESSION['s_access'];

if ( $s_admin == 0 ) {
  $err = 1;
  $m = 91;
  header("location: serveradmin.php?m=$m");
  exit;
}

if ($err != 1) {
  echo "<form action='serversave.php' method='POST'>\n";
    echo "<table class='datatable'>\n";
      echo "<tr>\n";
        echo "<td class='datatd'>Server</td>\n";
        echo "<td class='datatd'><input type='text' name='f_server' value='' style='width: 100%;' /></td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td class='datatd' colspan='2' align='center'><input type='submit' name='submit_server' value='Save' class='button' /></td>\n";
      echo "</tr>\n";
    echo "</table>\n";
  echo "</form>\n";
}
?>
<?php footer(); ?>

<?php include("menu.php"); set_title("Server Admin");  ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.01                  #
# 06-11-2006                       #
# Kees Trippelvitz                 #
####################################

#############################################
# Changelog:
# 1.04.01 Rereleased as 1.04.01
# 1.03.01 Released as part of the 1.03 package
# 1.02.02 Added exit after admin check
# 1.02.01 Initial release
#############################################

$s_admin = intval($_SESSION['s_admin']);

if ( $s_admin == 0 ) {
  $err = 1;
  $m = 91;
  pg_close($pgconn);
  header("location: serveradmin.php?int_m=$m");
  exit;
}

if ($err != 1) {
  echo "<form action='serversave.php' method='POST'>\n";
    echo "<table class='datatable'>\n";
      echo "<tr>\n";
        echo "<td class='datatd'>Server</td>\n";
        echo "<td class='datatd'><input type='text' name='strip_html_escape_server' value='' style='width: 100%;' /></td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td class='datatd' colspan='2' align='center'><input type='submit' name='submit_server' value='Save' class='button' /></td>\n";
      echo "</tr>\n";
    echo "</table>\n";
  echo "</form>\n";
}
?>
<?php footer(); ?>

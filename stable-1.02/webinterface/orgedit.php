<?php include("menu.php"); set_title("Organisation Administration"); ?>
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
# 1.02.02 Changed some input checks
# 1.02.01 Initial release
#############################################

include 'include/config.inc.php';
include 'include/connect.inc.php';
include 'include/functions.inc.php';

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_user = $s_access{2};

if ( $s_access_user < 2 ) {
  $err = 1;
}

if ( ! isset($_GET['orgid'] )) {
  $err = 1;
}
else {
  $orgid = intval($_GET['orgid']);
}

if ($err != 1) {
  $sql_orgs = "SELECT * FROM organisations WHERE id = '" .$orgid. "'";
  $result_orgs = pg_query($pgconn, $sql_orgs);
  $row = pg_fetch_assoc($result_orgs);
  $org = $row['organisation'];
  $ranges = $row['ranges'];
  $ranges = str_replace(";", "\n", $ranges);

  echo "<form action='orgsave.php' method='POST'>\n";
    echo "<table class='datatable'>\n";
      echo "<tr class='datatr'>\n";
        echo "<td class='datatd' width='100'>ID</td>\n";
        echo "<td class='datatd' width='300'>$orgid<input type='hidden' name='f_orgid' value='$orgid' /></td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td class='datatd'>Organisation</td>\n";
        echo "<td class='datatd'><input type='text' name='f_org' value='$org' style='width: 100%;' /></td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td class='datatd' valign='top'>Ranges</td>\n";
        echo "<td class='datatd'><textarea name='f_ranges' cols='40' rows='10'>$ranges</textarea></td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td class='datatd' colspan='2' align='right'><input type='submit' name='submit_org' value='Save' class='button' /></td>\n";
      echo "</tr>\n";
    echo "</table>\n";
  echo "</form>\n";
}
?>
<?php footer(); ?>

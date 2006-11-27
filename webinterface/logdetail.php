<?php include("menu.php"); set_title("Log Detail"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.02.04                  #
# 08-08-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.02.04 Changed the access check to correctly handle $s_access
# 1.02.03 intval() for $s_org and $s_admin
# 1.02.02 Changed some input checks
# 1.02.01 Initial release
#############################################

include 'include/config.inc.php';
include 'include/connect.inc.php';
include 'include/functions.inc.php';
include 'include/variables.inc.php';

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_search = $s_access{1};

$err = 0;

### Variables check
if (isset($_GET['id'])) {
  $id = intval($_GET['id']);
}
else {
  echo "Wrong or missing attack ID in the querystring.<br />\n";
  echo "<a href='logindex.php'>Logging Overview</a>\n";
  $err = 1;
}

### Admin check
if ($err != 1) {
  if ($s_access_search == 9) {
    $sql_details = "SELECT attackid, text FROM details WHERE attackid = " .$id;
  }
  else {
    $sql_details = "SELECT details.attackid, details.text FROM details, sensors WHERE details.attackid = " .$id. " AND details.sensorid = sensors.id AND sensors.organisation = '" .$s_org. "'";
  }
  $result_details = pg_query($pgconn, $sql_details);

  echo "<table class='datatable'>\n";
    echo "<tr>\n";
      echo "<td class='dataheader' width='100'>AttackID</td>\n";
      echo "<td class='dataheader' width='300'>Logging</td>\n";
      echo "<td class='dataheader' width='200'>ClamAV result</td>\n";
    echo "</tr>\n";

  while ($row = pg_fetch_assoc($result_details)) {
    $attackid = $row['attackid'];
    $logging = $row['text'];
    $infofile = $vir_dir . "/" . $logging . $vir_suffix;
    echo "<tr>\n";
      echo "<td class='datatd'>$attackid</td>\n";
      echo "<td class='datatd'>$logging</td>\n";
      if (file_exists($infofile)) {
        $virusinfo = getVirusinfo($infofile);
        echo "<td class='datatd'>$virusinfo</td>\n";
      }
      else {
        echo "<td class='datatd'>&nbsp;</td>\n";
      }
    echo "</tr>\n";
  }

  echo "</table>\n";
}
pg_close($pgconn);
?>
<?php footer(); ?>

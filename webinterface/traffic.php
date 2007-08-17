<?php include("menu.php"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.03.01                  #
# 28-07-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.03.01 Released as part of the 1.03 package
# 1.02.04 Storing images in the database
# 1.02.03 Removed includes
# 1.02.02 Changed the way sensor is passed along the querystring
# 1.02.01 Initial release
#############################################

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);

$sql_getorg = "SELECT organisation FROM organisations WHERE id = $s_org";
$result_getorg = pg_query($pgconn, $sql_getorg);
$db_org_name = pg_result($result_getorg, 0);

if ($s_admin == 1) {
  $sql_sensors = "SELECT id, label, orgid FROM rrd WHERE type = 'day'";
} else {
  $sql_sensors = "SELECT id, label, orgid FROM rrd WHERE orgid = $s_org AND type = 'day'";
}
$result_sensors = pg_query($pgconn, $sql_sensors);
$numrows_result_sensors = pg_numrows($result_sensors);

if ($numrows_result_sensors == 0) {
  echo "You have no sensors active";
} else { 
  if ($s_admin == 1) {
    echo "<h3>Traffic analysis for: All</h3>\n";
  } else {
    echo "<h3>Traffic analysis for: $db_org_name</h3>\n";
  }
  
  echo "<table>\n";
  while ($row = pg_fetch_assoc($result_sensors)) {
    $imgid = $row['id'];
    $orgid = $row['orgid'];
    $label = $row['label'];

    echo "<tr>\n";
      echo "<td><a href='trafficview.php?label=$label'><img src='showtraffic.php?imgid=$imgid' alt='$sensor' border='1' /></a></td>\n";
    echo "</tr>\n";
  } 
  echo "</table>\n";
}
?>
<?php footer(); ?>

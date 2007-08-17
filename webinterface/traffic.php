<?php include("menu.php"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.02.04                  #
# 08-08-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.02.04 Added intval() to $s_admin
# 1.02.03 Added intval() to $s_org
# 1.02.02 Changed the way sensor is passed along the querystring
# 1.02.01 Initial release
#############################################

include 'include/config.inc.php';
include 'include/connect.inc.php';
include 'include/functions.inc.php';

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);

$sql_getorg = "SELECT organisation FROM organisations WHERE id = $s_org";
$result_getorg = pg_query($pgconn, $sql_getorg);
$db_org_name = pg_result($result_getorg, 0);

if ($s_admin == 1) {
  $sql_sensors = "SELECT id, keyname, organisation FROM sensors WHERE tap != ''";
}
else {
  $sql_sensors = "SELECT id, keyname FROM sensors WHERE organisation = " . $s_org . " AND tap != ''";
}
$result_sensors = pg_query($pgconn, $sql_sensors);
$numrows_result_sensors = pg_numrows($result_sensors);
if ($numrows_result_sensors == 0) {
  echo "You have no sensors active";
}
else { 
  if ($s_admin == 1) {
    echo "<h3>Traffic analysis for: All</h3>\n";
    echo "<table>\n";
    echo "<tr>\n";
      echo "<td><a href='trafficview.php?view=0'><img src='$imagedir/allsensors-day.png' alt='all sensors' border='1' /></a></td>\n";
    echo "</tr>\n";
  }
  else {
    echo "<h3>Traffic analysis for: $db_org_name</h3>\n";
    echo "<table>\n";
  }
  
  while ($row = pg_fetch_assoc($result_sensors)) {
    $sid = $row['id'];
    $sensor = $row['keyname'];
    echo "<tr>\n";
      echo "<td><a href='trafficview.php?view=$sid'><img src='$imagedir/$sensor-day.png' alt='$sensor' border='1' /></a></td>\n";
    echo "</tr>\n";
  } 
  echo "</table>\n";
}
pg_close($pgconn);
?>
<?php footer(); ?>

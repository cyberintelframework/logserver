<?php include("menu.php"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.02.03                  #
# 08-08-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.02.03 Added intval() to $s_org and $s_admin
# 1.02.02 Added some more input checks
# 1.02.01 Initial release
#############################################

include 'include/config.inc.php';
include 'include/connect.inc.php';
include 'include/functions.inc.php';

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);

if (isset($_GET['view'])) {
  $sid = intval($_GET['view']);
  if ($sid == 0) {
    $sensor = "allsensors";
  } else {
    $sql_sensors = "SELECT keyname, organisation FROM sensors WHERE id = $sid";
    $result_sensors = pg_query($pgconn, $sql_sensors);
    $row = pg_fetch_assoc($result_sensors);
    $sensor = $row['keyname'];
  }
  $dayfile = $imagedir . "/" . $sensor . "-day.png";
  if (file_exists($dayfile)) {
    $keyname = $row['keyname'];
    $q_org = $row['organisation'];

    if ($q_org == $s_org || $s_admin == 1) {  
      echo "<h3>Traffic analysis for: $sensor</h3>";
      echo "<table>\n";
        echo "<tr>\n";
          echo "<td>\n";
            echo "Daily Graph (5 minute averages)<br />\n";
            echo "<img alt='$sensor Daily' src='$imagedir/$sensor-day.png' /><br />\n";
            echo "Weekly Graph (30 minute averages)<br />\n";
            echo "<img alt='$sensor Weekly' src='$imagedir/$sensor-week.png' /><br />\n";
            echo "Monthly Graph (2 hour averages)<br />\n";
            echo "<img alt='$sensor Monthly' src='$imagedir/$sensor-month.png' /><br />\n";
            echo "Yearly Graph (12 hour averages)<br />\n";
            echo "<img alt='$sensor Yearly' src='$imagedir/$sensor-year.png' /><br />\n";
          echo "</td>\n";
        echo "</tr>\n";
      echo "</table>\n";
    }
    else {
      echo "You are not allowed to view this sensors traffic statistics.";
    }
  }
  else {
    echo "The sensor requested is not an existing or active sensor.";
  }
}
else {
  echo "<h2>No sensor given.</h2>";
}
pg_close($pgconn);
?>
<?php footer(); ?>

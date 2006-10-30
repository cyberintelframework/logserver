<?php
####################################
# SURFnet IDS                      #
# Version 1.02.13                  #
# 25-10-2006                       #
# Jan van Lith & Kees Trippelvitz  #
# Modified by Peter Arts           #
####################################

#########################################################################
# Changelog:
# 1.02.13 Added No Data! picture
# 1.02.12 Added intval() to $s_org and $s_admin
# 1.02.11 input checks for $sev and $bin
# 1.02.10 Added another intval()
# 1.02.09 Fixed a $_GET vulnerability
# 1.02.08 Fixed a bug with the destination address search
# 1.02.07 Added debugging option
# 1.02.06 Bugfix organisation_id in query string
# 1.02.05 Added Classification and additional info to the IDMEF report
# 1.02.04 Multiple sensor-select
# 1.02.03 Query tuning
#########################################################################

session_start();
if (intval(@strlen($_SESSION["s_user"])) == 0) {
  // User not logged in
  header("Location: login.php");
  exit;
}

header("Content-type: image/png");
header("Cache-control: no-cache");
header("Pragma: no-cache");

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';
include '../include/variables.inc.php';
require_once("../libchart/libchart.php");

$charttype = intval($_GET['type']);
$org = intval($_GET['org']);
$sql = cleansql($_SESSION['chartsql']);

if ($charttype == 0) {
  $chart =  new PieChart();
} elseif ($charttype == 1) {
  $chart = new HorizontalChart();
} elseif ($charttype == 2) {
  $chart = new VerticalChart();
} else {
  echo "Wrong type selected<br />\n";
  $err = 1;
}

if ($err != 1) {
  $result_chart = pg_query($pgconn, $sql);
  $totalrows = pg_num_rows($result_chart);
  if ($totalrows == 0) { 
    $drawerr = 1;
    readfile("images/nodata.gif");
  } else {
    $chart->setTitle($title);
    while ($row = pg_fetch_row($result_chart)) {
      $key = $row[0];
      $value = $row[1];
      $dia = substr_count($key, "Dialogue");
      if ($dia > 0) {
        $key = $attacks_ar[$key]["Attack"];
      }
      $chart->addPoint(new Point("$key ($value)", $value));
    }
    $chart->render();
  }
}

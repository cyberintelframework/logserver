<?php
####################################
# SURFnet IDS                      #
# Version 1.04.01                  #
# 16-11-2006                       #
# Jan van Lith & Kees Trippelvitz  #
# Modified by Peter Arts           #
####################################

#############################################
# Changelog:
# 1.04.01 Released as 1.04.01
# 1.03.02 Added No Data! picture
# 1.03.01 Query tuning
#############################################

session_start();
if (intval(@strlen($_SESSION["s_user"])) == 0) {
  // User not logged in
  header("Location: login.php");
  exit;
}

#header("Content-type: image/png");
#header("Cache-control: no-cache");
#header("Pragma: no-cache");

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
?>

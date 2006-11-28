<?php

####################################
# SURFnet IDS                      #
# Version 1.03.02                  #
# 28-11-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.03.02 pg_escape_string added for $action
# 1.03.01 Released as part of the 1.03 package
# 1.02.04 SQL injection fix
# 1.02.03 Added some more input checks
# 1.02.02 Initial release
#############################################

session_start();
header("Cache-control: private");

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';

if (!isset($_SESSION['s_admin'])) {
  $address = getaddress($web_port);
  header("location: ${address}login.php");
  exit;
}

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_sensor = intval($s_access{0});

if ($s_access_sensor == 0) {
  $m = 90;
  pg_close($pgconn);
  header("location: sensorstatus.php?selview=$selview&m=$m");
  exit;
}

if ($s_admin == 1) {
  $sql_sensors = "SELECT keyname, ssh FROM sensors";
} else {
  $sql_sensors = "SELECT keyname, ssh FROM sensors WHERE organisation = $s_org";
}
$result_sensors = pg_query($pgconn, $sql_sensors);

if (isset($_GET['selview'])) {
  $selview = intval($_GET['selview']);
}

while ($row = pg_fetch_assoc($result_sensors)) {
  $keyname = $row['keyname'];
  $ssh = $row['ssh'];
  $formkey = "f_" . $keyname;
  $action = pg_escape_string(stripinput($_POST[$formkey]));
  $tapkey = "tapip_" . $keyname;
  if (isset($_POST[$tapkey])) {
    $tapip = pg_escape_string(stripinput($_POST[$tapkey]));
    if (preg_match($ipregexp, $tapip)) {
      $sql_checkip = "SELECT tapip FROM sensors WHERE tapip = '$tapip' AND NOT keyname = '$keyname'";
      $result_checkip = pg_query($pgconn, $sql_checkip);
      $checkip = pg_num_rows($result_checkip);
      if ($checkip > 0) {
        $m = 101;
        break;
      } else {
        $sql_updatestatus = "UPDATE sensors SET action = '" .$action. "', tapip = '$tapip' WHERE keyname = '$keyname'";
        $result_updatestatus = pg_query($pgconn, $sql_updatestatus);
        $m = 7;
      }
    } else {
      $m = 102;
      break;
    }
  } else {
    $sql_updatestatus = "UPDATE sensors SET action = '" .$action. "' WHERE keyname = '$keyname'";
    $result_updatestatus = pg_query($pgconn, $sql_updatestatus);
    $m = 7;
  }
}
pg_close($pgconn);
if ($m != 1) {
  header("location: sensorstatus.php?selview=$selview&m=$m&key=$keyname");
} else {
  header("location: sensorstatus.php?selview=$selview&m=$m");
}
?>

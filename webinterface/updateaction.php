<?php

####################################
# SURFnet IDS                      #
# Version 1.02.04                  #
# 31-07-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.02.04 SQL injection fix
# 1.02.03 Added some more input checks
# 1.02.02 Initial release
#############################################

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';

session_start();
header("Cache-control: private");

if (!isset($_SESSION['s_admin'])) {
  $address = getaddress($web_port);
  header("location: ${address}login.php");
  exit;
}

$s_org = $_SESSION['s_org'];
$s_admin = $_SESSION['s_admin'];
$s_access = $_SESSION['s_access'];
$s_access_sensor = $s_access{0};

if ($s_access_sensor == 0) {
  $m = 90;
  header("location: sensorstatus.php?selview=$selview&m=$m");
  exit;
}

if ($s_admin == 1) {
  $sql_sensors = "SELECT keyname, ssh FROM sensors";
}
else {
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
  $action = stripinput($_POST[$formkey]);
  $tapkey = "tapip_" . $keyname;
  if (isset($_POST[$tapkey])) {
    $tapip = pg_escape_string(stripinput($_POST[$tapkey]));
    if (preg_match($ipregexp, $tapip)) {
      $sql_checkip = "SELECT tapip FROM sensors WHERE tapip = '$tapip' AND NOT keyname = '$keyname'";
      $result_checkip = pg_query($pgconn, $sql_checkip);
      $checkip = pg_num_rows($result_checkip);
      if ($checkip > 0) {
        $m = 91;
        break;
      }
      else {
        $sql_updatestatus = "UPDATE sensors SET action = '" .$action. "', tapip = '$tapip' WHERE keyname = '$keyname'";
        $result_updatestatus = pg_query($pgconn, $sql_updatestatus);
        $m = 1;
      }
    }
    else {
      $m = 92;
      break;
    }
  }
  else {
    $sql_updatestatus = "UPDATE sensors SET action = '" .$action. "' WHERE keyname = '$keyname'";
    $result_updatestatus = pg_query($pgconn, $sql_updatestatus);
    $m = 1;
  }
}
pg_close($pgconn);
if ($m != 1) {
  header("location: sensorstatus.php?selview=$selview&m=$m&key=$keyname");
}
else {
  header("location: sensorstatus.php?selview=$selview&m=$m");
}
?>

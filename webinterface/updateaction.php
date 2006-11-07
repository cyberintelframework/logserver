<?php

####################################
# SURFnet IDS                      #
# Version 1.04.01                  #
# 06-11-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.04.01 Code layout
# 1.02.05 Added VLAN support 
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
  pg_close($pgconn);
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

if (isset($_GET['selview'])) {
  $selview = intval($_GET['selview']);
}
      
$error = 0;
$keyname = $_POST['keyname'];
$vlanid = $_POST['vlanid'];
$action = $_POST['action'];
if (isset($_POST[tapip])) {
  $tapip = pg_escape_string(stripinput($_POST[tapip]));
  if (preg_match($ipregexp, $tapip)) {
    $sql_checkip = "SELECT tapip FROM sensors WHERE tapip = '$tapip' AND NOT keyname = '$keyname'";
    $result_checkip = pg_query($pgconn, $sql_checkip);
    $checkip = pg_num_rows($result_checkip);
    if ($checkip > 0) {
      $m = 101;
      $error = 1;
    } else {
      $sql_updatestatus = "UPDATE sensors SET tapip = '$tapip' WHERE keyname = '$keyname' AND vlanid ='$vlanid'";
      $result_updatestatus = pg_query($pgconn, $sql_updatestatus);
      $m = 7;
    }
  } else {
    $m = 102;
    $error = 1;
  }
} 
if ($error == 0) {
  $sql_updatestatus = "UPDATE sensors SET action = '" .$action. "' WHERE keyname = '$keyname'";
  $result_updatestatus = pg_query($pgconn, $sql_updatestatus);
  $m = 7;
}

pg_close($pgconn);
if ($m != 1) {
  header("location: sensorstatus.php?selview=$selview&m=$m&key=$keyname");
} else {
  header("location: sensorstatus.php?selview=$selview&m=$m");
}
?>

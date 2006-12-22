<?php

####################################
# SURFnet IDS                      #
# Version 1.04.04                  #
# 15-12-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.04.04 Changed data input handling
# 1.04.03 Added input checks for $action, $vlanid and $keyname
# 1.04.02 Added VLAN support 
# 1.04.01 Released as 1.04.01
# 1.03.01 Released as part of the 1.03 package
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
  $address = getaddress();
  header("location: ${address}login.php");
  exit;
}

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_sensor = intval($s_access{0});

$allowed_get = array(
                "int_selview"
);
$check = extractvars($_GET, $allowed_get);
#debug_input();

if ($s_access_sensor == 0) {
  $m = 91;
  pg_close($pgconn);
  header("location: sensorstatus.php?int_selview=" .$clean['selview']. "&int_m=" .$m);
  exit;
}

if (isset($clean['selview'])) {
  $selview = $clean['selview'];
} elseif (isset($selview)) {
  $selview = intval($selview);
}

$allowed_post = array(
                "int_sid",
                "int_vlanid",
                "action",
		"ip_tapip"
);
$check = extractvars($_POST, $allowed_post);
#debug_input();

$err = 0;
$sid = $clean['sid'];
$vlanid = $clean['vlanid'];
$action = $tainted['action'];
$action_pattern = '/^(NONE|REBOOT|SSHOFF|SSHON|CLIENT|RESTART|BLOCK|UNBLOCK)$/';
if (preg_match($action_pattern, $action) != 1) {
  $m = 92;
  $err = 1;
}

if (isset($clean['sid'])) {
  $sql_sid = "SELECT keyname FROM sensors WHERE id = '$sid'";
  $result_sid = pg_query($pgconn, $sql_sid);
  $row_sid = pg_fetch_assoc($result_sid);
  $keyname = $row_sid['keyname'];
  if ($keyname == "") {
    $m = 95;
    $err = 1;
  }
}
if (isset($clean[tapip])) {
  $tapip = $clean[tapip];
  $sql_checkip = "SELECT tapip FROM sensors WHERE tapip = '$tapip' AND NOT id = '$sid'";
  $result_checkip = pg_query($pgconn, $sql_checkip);
  $checkip = pg_num_rows($result_checkip);
  if ($checkip > 0) {
    $m = 101;
    $err = 1;
  } else {
    $sql_updatestatus = "UPDATE sensors SET tapip = '$tapip' WHERE id = '$sid' AND vlanid = '$vlanid'";
    $result_updatestatus = pg_query($pgconn, $sql_updatestatus);
    $m = 1;
  }
} else {
  $sql_m = "SELECT netconf FROM sensors WHERE id = '$sid'";
  $result_m = pg_query($pgconn, $sql_m);
  $row_m = pg_fetch_assoc($result_m);
  $netconf = $row_m['netconf'];

  if ($netconf == "vlans" || $netconf == "static") {
    $sql_updatestatus = "UPDATE sensors SET tapip = NULL WHERE id = '$sid' AND vlanid = '$vlanid'";
    $result_updatestatus = pg_query($pgconn, $sql_updatestatus);
    $m = 94;
  }
}
if ($err != 1) {
  $sql_updatestatus = "UPDATE sensors SET action = '" .$action. "' WHERE id = '$sid'";
  $result_updatestatus = pg_query($pgconn, $sql_updatestatus);
  if ($m == "") {
    $m = 1;
  }
}

pg_close($pgconn);
if ($m != 1) {
  header("location: sensorstatus.php?int_selview=$selview&int_m=$m&key=$keyname");
} else {
  header("location: sensorstatus.php?int_selview=$selview&int_m=$m");
}
?>

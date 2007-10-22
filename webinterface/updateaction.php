<?php

####################################
# SURFnet IDS                      #
# Version 2.00.01                  #
# 12-09-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 2.00.01 version 2.00
# 1.04.08 Added ARP actions
# 1.04.07 Fixed redirection typo, ip check on same sensor
# 1.04.06 Added ignore/unignore stuff
# 1.04.05 Saving action for all sensors with the same keyname
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

# Starting the session
session_start();
header("Cache-control: private");

# Checking if the user is logged in
if (!isset($_SESSION['s_admin'])) {
  pg_close($pgconn);
  $address = getaddress();
  header("location: ${address}login.php");
  exit;
}

# Retrieving some session variables
$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_sensor = intval($s_access{0});

# Retrieving posted variables from $_GET
$allowed_get = array(
                "int_selview"
);
$check = extractvars($_GET, $allowed_get);
#debug_input();

# Checking access
if ($s_access_sensor == 0) {
  $m = 101;
  pg_close($pgconn);
  header("location: sensorstatus.php?int_selview=" .$clean['selview']. "&int_m=" .$m);
  exit;
}

if (isset($clean['selview'])) {
  $selview = $clean['selview'];
} elseif (isset($selview)) {
  $selview = intval($selview);
}

# Retrieving posted variables from $_POST
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

if (isset($clean['vlanid'])) {
  $vlanid = $clean['vlanid'];
} else {
  $m = 127;
  $err = 1;
}

$action = $tainted['action'];
$action_pattern = '/^(NONE|REBOOT|SSHOFF|SSHON|RESTART|DISABLE|ENABLE|START|STOP|IGNORE|UNIGNORE|ENABLEARP|DISABLEARP)$/';
if (preg_match($action_pattern, $action) != 1) {
  $m = 128;
  $err = 1;
}

if (isset($clean['sid'])) {
  $sql_sid = "SELECT keyname, status, arp FROM sensors WHERE id = '$sid'";
  $result_sid = pg_query($pgconn, $sql_sid);
  $row_sid = pg_fetch_assoc($result_sid);
  $keyname = $row_sid['keyname'];
  $status = $row_sid['status'];
  $arp = $row_sid['arp'];
  if ($keyname == "") {
    $m = 110;
    $err = 1;
  } else {
    if ($action == "ENABLEARP" && $arp == 0) {
      $sql_updatearp = "UPDATE sensors SET arp = 1 WHERE id = '$sid' AND vlanid = '$vlanid'";
      $result_updatearp = pg_query($pgconn, $sql_updatearp);
      $m = 3;
      pg_close($pgconn);
      header("location: sensorstatus.php?int_selview=$selview&int_m=$m");
      exit;
    } elseif ($action == "DISABLEARP" && $arp == 1) {
      $sql_updatearp = "UPDATE sensors SET arp = 0 WHERE id = '$sid' AND vlanid = '$vlanid'";
      $result_updatearp = pg_query($pgconn, $sql_updatearp);
      $m = 3;
      pg_close($pgconn);
      header("location: sensorstatus.php?int_selview=$selview&int_m=$m");
      exit;
    }
  }

  if (isset($clean['tapip'])) {
    $tapip = $clean['tapip'];
    $sql_checkip = "SELECT tapip FROM sensors WHERE tapip = '$tapip' AND NOT keyname = '$keyname'";
    $result_checkip = pg_query($pgconn, $sql_checkip);
    $checkip = pg_num_rows($result_checkip);
    if ($checkip > 0) {
      $m = 129;
      $err = 1;
    } else {
      $sql_updatestatus = "UPDATE sensors SET tapip = '$tapip' WHERE id = '$sid' AND vlanid = '$vlanid'";
      $result_updatestatus = pg_query($pgconn, $sql_updatestatus);
      $m = 3;
    }
  } else {
    $sql_m = "SELECT netconf FROM sensors WHERE id = '$sid'";
    $result_m = pg_query($pgconn, $sql_m);
    $row_m = pg_fetch_assoc($result_m);
    $netconf = $row_m['netconf'];

    if ($netconf == "vlans" || $netconf == "static") {
      $sql_updatestatus = "UPDATE sensors SET tapip = NULL WHERE id = '$sid' AND vlanid = '$vlanid'";
      $result_updatestatus = pg_query($pgconn, $sql_updatestatus);
      $m = 3;
    }
  }
} else {
  $m = 110;
  $err = 1;
}
if ($err != 1) {
  $action_pattern = '/^(IGNORE|UNIGNORE)$/';
  if (!preg_match($action_pattern, $action)) {
    $sql_updatestatus = "UPDATE sensors SET action = '" .$action. "' WHERE keyname = '$keyname'";
    $result_updatestatus = pg_query($pgconn, $sql_updatestatus);
    if ($m == "") {
      $m = 3;
    }
  } else {
    if ($action == "IGNORE") {
      if ($status != 1) {
        $sql_updatestatus = "UPDATE sensors SET status = 3 WHERE keyname = '$keyname' AND vlanid = $vlanid";
        $result_updatestatus = pg_query($pgconn, $sql_updatestatus);
        $m = 3;
      }
    } else {
      if ($status == 3) {
        $sql_updatestatus = "UPDATE sensors SET status = 0 WHERE keyname = '$keyname' AND vlanid = $vlanid";
        $result_updatestatus = pg_query($pgconn, $sql_updatestatus);
        $m = 3;
      }
    }
  }
}

# Close connection and redirect
pg_close($pgconn);
header("location: sensorstatus.php?int_selview=$selview&int_m=$m");
?>

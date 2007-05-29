<?php

####################################
# SURFnet IDS                      #
# Version 1.04.01                  #
# 18-05-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.04.01 Initial release
#############################################

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';

session_start();
header("Cache-control: private");

# Checking if the user is logged in
if (!isset($_SESSION['s_admin'])) {
  $address = getaddress();
  pg_close($pgconn);
  header("location: ${address}login.php");
  exit;
}

$s_org = intval($_SESSION['s_org']);
$s_access = $_SESSION['s_access'];
$s_access_sensor = intval($s_access{0});
$s_hash = md5($_SESSION['s_hash']);

$allowed_post = array(
                "mac_macaddr",
                "ip_ipaddr",
                "int_sensor",
		"md5_hash"
);
$check = extractvars($_POST, $allowed_post);

$allowed_get = array(
		"int_org",
		"int_filter"
);
$check = extractvars($_GET, $allowed_get);
#debug_input();

if (isset($clean['filter'])) {
  $filter = $clean['filter'];
} else {
  $filter = 0;
}

if ($s_access_sensor < 2) {
  $err = 1;
  $m = 90;
}

if ($clean['hash'] != $s_hash) {
  $err = 1;
  $m = 91;
}

if (isset($clean['macaddr'])) {
  $mac = $clean['macaddr'];

  $sql_check = "SELECT mac FROM arp_static WHERE arp_static.mac = '$mac'";
  $debuginfo[] = $sql_check;
  $result_check = pg_query($pgconn, $sql_check);
  $numrows_check = pg_num_rows($result_check);
  if ($numrows_check == 1) {
    $m = 96;
    $err = 1;
  }
} else {
  $err = 1;
  $m = 92;
}

if (isset($clean['ipaddr'])) {
  $ip = $clean['ipaddr'];
} else {
  $err = 1;
  $m = 93;
}

if (isset($clean['sensor'])) {
  $sensorid = $clean['sensor'];
  if ($sensorid == 0) {
    $err = 1;
    $m = 94;
  }
} elseif (isset($clean['filter'])) {
  $sensorid = $clean['filter'];
  if ($sensorid == 0) {
    $err = 1;
    $m = 94;
  }
} else {
  $err = 1;
  $m = 94;
}

if ($s_access_sensor == 9) {
  if (isset($clean['org'])) {
    $q_org = $clean['org'];
  } else {
    $q_org = $s_org;
  }
} else {
  $q_org = $s_org;
}

if ($err == 0) {
  $sql = "SELECT mac FROM arp_static WHERE sensorid = $sensorid AND ip = '$ip'";
  $debuginfo[] = $sql;
  $result_check = pg_query($pgconn, $sql);
  $rows = pg_num_rows($result_check);
  if ($rows == 1) {
    $err = 1;
    $m = 98;
  } 
}

$sql = "SELECT keyname FROM sensors WHERE id = $sensorid AND organisation = $q_org";
$debuginfo[] = $sql;
$result_user = pg_query($pgconn, $sql);
$rows = pg_num_rows($result_user);
if ($rows == 0) {
  $err = 1;
  $m = 90;
} 

if ($err != 1) {
  $sql = "INSERT INTO arp_static (ip, mac, sensorid) ";
  $sql .= "VALUES ('$ip', '$mac', '$sensorid')";
  $debuginfo[] = $sql;
  $execute = pg_query($pgconn, $sql);
  $m = 1;
}

pg_close($pgconn);
#debug_sql();
header("location: arpadmin.php?int_m=$m&int_org=$q_org&int_filter=$filter");
?>
<?php

####################################
# SURFnet IDS                      #
# Version 2.00.01                  #
# 12-09-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 2.00.01 Initial release
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
$q_org = $_SESSION['q_org'];
$s_access = $_SESSION['s_access'];
$s_access_sensor = intval($s_access{2});
$s_hash = md5($_SESSION['s_hash']);
$err = 0;

# Retrieving posted variables from $_GET
$allowed_get = array(
                "int_id",
		"int_org",
		"int_type",
		"action",
		"int_sid",
		"md5_hash"
);
$check = extractvars($_GET, $allowed_get);
#debug_input();

# Checking $_GET'ed variables
if (isset($clean['id']) ) {
  $id = $clean['id'];
} else {
  $m = 117;
  $err = 1;
}

if ($clean['hash'] != $s_hash) {
  $err = 1;
  $m = 116;
}

if (isset($clean['type'])) {
  $type = $clean['type'];
} else {
  $err = 1;
  $m = 119;
}

if (isset($tainted['action'])) {
  $action = $tainted['action'];
  $pattern = '/^(del|add)$/';
  if (!preg_match($pattern, $action)) {
    $err = 1;
    $m = 120;
  }
} else {
  $err = 1;
  $m = 120;
}

if ($s_access_sensor < 2) {
  $m = 101;
  $err = 1;
} elseif ($s_access_sensor < 9 && $err == 0) {
  $sql_check = "SELECT organisation FROM sensors, arp_static WHERE arp_static.sensorid = sensors.id AND arp_static.id = $id AND sensors.organisation = $q_org";
  $debuginfo[] = $sql_check;
  $result_check = pg_query($pgconn, $sql_check);
  $numrows_check = pg_num_rows($result_check);
  if ($numrows_check == 0) {
    $m = 101;
    $err = 1;
  }
}

if (isset($clean['sid'])) {
  $sid = $clean['sid'];
} else {
  $err = 1;
  $m = 110;
}

if ($err == 0) {
  if ($action == "del") {
    $sql = "DELETE FROM sniff_hosttypes WHERE staticid = $id AND type = $type";
    $debuginfo[] = $sql;
    $execute = pg_query($pgconn, $sql);
    $m = 2;
  } elseif ($action == "add") {
    $sql = "INSERT INTO sniff_hosttypes (staticid, type) VALUES ($id, $type)";
    $debuginfo[] = $sql;
    $execute = pg_query($pgconn, $sql);
    $m = 1;
  }
}

# Close connection and redirect
pg_close($pgconn);
#debug_sql();
header("location: arp_static.php?int_m=$m&int_sid=$sid");
?>

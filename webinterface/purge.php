<?php

####################################
# SURFnet IDS                      #
# Version 2.00.01                  #
# 05-09-2007                       #
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
$s_access = $_SESSION['s_access'];
$s_access_sensor = intval($s_access{0});
$q_org = $_SESSION['q_org'];
$err = 0;

# Retrieving posted variables from $_GET
$allowed_get = array(
                "int_sid",
		"int_time"
);
$check = extractvars($_GET, $allowed_get);
#debug_input();

if (isset($clean['sid'])) {
  $sid = $clean['sid'];
} else {
  $err = 1;
  $m = 110;
}

if (isset($clean['time'])) {
  $time = $clean['time'];
} else {
  $err = 1;
  $m = 113;
}

# Checking access
if ($s_access_sensor == 0) {
  $err = 1;
  $m = 101;
}

# Checking sensor ownership
if ($s_access_sensor < 9) {
  $sql = "SELECT id FROM sensors WHERE id = '$sid' AND organisation = '$q_org'";
  $result = pg_query($pgconn, $sql);
  $num = pg_num_rows($result);
  if ($num == 0) {
    $err = 1;
    $m = 101;
  }
}

if ($err == 0) {
  $ts = date("U");
  $ts = $ts - $time;

  $sql = "DELETE FROM sensors_log WHERE timestamp < $ts AND sensorid = '$sid'";
  $result = pg_query($pgconn, $sql);
  $m = 8;
}

# Close connection and redirect
pg_close($pgconn);
header("location: sensordetails.php?int_sid=$sid&int_m=$m");
?>
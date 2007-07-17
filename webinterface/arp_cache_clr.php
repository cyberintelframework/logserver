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
$s_hash = md5($_SESSION['s_hash']);
$err = 0;

# Retrieving posted variables from $_GET
$allowed_get = array(
		"int_org",
		"int_filter",
		"md5_hash"
);
$check = extractvars($_GET, $allowed_get);
#debug_input();

# Checking access
if ($s_access_sensor < 2) {
  $m = 90;
  $err = 1;
}

# Checking $_GET'ed variables
if (isset($clean['org'])) {
  $q_org = $clean['org'];
} else {
  $q_org = $s_org;
}

if ($s_access_sensor < 2) {
  $m = 90;
  $err = 1;
}

if ($clean['hash'] != $s_hash) {
  $err = 1;
  $m = 91;
}

if (isset($clean['filter'])) {
  $filter = $clean['filter'];
} else {
  $err = 1;
  $m = 97;
}

if ($err == 0) {
  # No errors found, delete records
  if ($filter != 0) {
    $sql = "DELETE FROM arp_cache WHERE sensorid = $filter AND sensorid IN (SELECT id FROM sensors WHERE sensors.organisation = $q_org)";
  } else {
    $sql = "DELETE FROM arp_cache WHERE sensorid IN (SELECT id FROM sensors WHERE organisation = $q_org)";
  }
  $debuginfo[] = $sql;
  $execute = pg_query($pgconn, $sql);

  $m = 3;
}

# Close connection and redirect
pg_close($pgconn);
#debug_sql();
header("location: arpadmin.php?int_m=$m&int_org=$q_org&int_filter=$filter");
?>

<?php

####################################
# SURFids 2.10                     #
# Changeset 001                    #
# 03-03-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 001 Initial release
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
$q_org = intval($_SESSION['q_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_sensor = intval($s_access{0});
$s_hash = md5($_SESSION['s_hash']);
$err = 0;

# Retrieving posted variables from $_GET
$allowed_get = array(
		"int_sid",
		"md5_hash"
);
$check = extractvars($_GET, $allowed_get);
#debug_input();

# Checking access
if ($s_access_sensor < 2) {
  $m = 101;
  $err = 1;
}

if ($clean['hash'] != $s_hash) {
  $err = 1;
  $m = 116;
}

if (isset($clean['sid'])) {
  $sid = $clean['sid'];
  if ($sid == 0) {
    $err = 1;
    $m = 110;
  }
} else {
  $err = 1;
  $m = 110;
}

if ($err == 0) {
  # No errors found, delete records
  if ($q_org == 0) {
    $sql = "DELETE FROM arp_cache WHERE sensorid = $sid";
  } else {
    $sql = "DELETE FROM arp_cache WHERE sensorid IN (SELECT id FROM sensors WHERE sensors.organisation = $q_org)";
  }
  $debuginfo[] = $sql;
  $execute = pg_query($pgconn, $sql);

  $m = 4;
}

# Close connection and redirect
pg_close($pgconn);
#debug_sql();
header("location: arp_cache.php?int_m=$m&int_sid=$sid");
?>

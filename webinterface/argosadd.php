<?php

####################################
# SURFnet IDS                      #
# Version 2.00.03                  #
# 24-09-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 2.00.03 Added hash check
# 2.00.02 Fixed a bug with missing imageid
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
$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_sensor = intval($s_access{0});
$s_hash = md5($_SESSION['s_hash']);

# Checking access
if ($s_access_sensor == 0) {
  $m = 91;
  pg_close($pgconn);
  header("location: argosconfig.php?int_m=" .$m);
  exit;
}

# Retrieving posted variables from $_POST
$allowed_post = array(
                "int_sensorid",
                "int_imageid",
                "int_templateid",
                "strip_html_escape_timespan",
		"md5_hash"
);
$check = extractvars($_POST, $allowed_post);
#debug_input();

$err = 0;

# Checking if the logged in user actually requested this action.
if ($clean['hash'] != $s_hash) {
  $err = 1;
  $m = 116;
}

# Checking $_POST'ed variables
if (isset($clean['sensorid'])) {
  $sensorid = $clean['sensorid'];
} else {
  $m = 110;
  $err = 1;
}
if (isset($clean['templateid'])) {
  $templateid = $clean['templateid'];
} else {
  $m = 111;
  $err = 1;
}
if (isset($clean['imageid'])) {
  $imageid = $clean['imageid'];
} else {
  $m = 112;
  $err = 1;
}
if (isset($clean['timespan'])) {
  $timespan = $clean['timespan'];
} else {
  $m = 113;
  $err = 1;
}

$sql = "SELECT sensorid FROM argos";
$debuginfo[] = $sql;
$query = pg_query($pgconn, $sql);
while ($row = pg_fetch_assoc($query)) {
  $sqlsensorid = $row["sensorid"];
  if ($sqlsensorid == $sensorid) {
    $m = 110;
    $err = 1;
  }
}

if ($err == 0) {
  $sql = "SELECT organisationid FROM argos_images WHERE id = '$imageid'";
  $debuginfo[] = $sql;
  $query = pg_query($pgconn, $sql);
  while ($row = pg_fetch_assoc($query)) {
    $orgid = $row["organisationid"];
    if ($orgid == $s_org || $orgid == 0) {
    } else { 
      $m = 107;
      $err = 1;
    }
  }
}

if ($err == 0) {
  # No errors found, insert record
  $sql = "INSERT INTO argos (sensorid, imageid, templateid, timespan) VALUES ($sensorid, $imageid, $templateid, '$timespan')";
  $debuginfo[] = $sql;
  $query = pg_query($pgconn, $sql);

  $m = 1;
}

# Close connection and redirect
pg_close($pgconn);
#debug_sql();
header("location: argosconfig.php?int_m=$m");
?>

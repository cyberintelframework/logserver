<?php

####################################
# SURFnet IDS                      #
# Version 1.04.01                  #
# 01-06-2007                       #
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
  pg_close($pgconn);
  $address = getaddress();
  header("location: ${address}login.php");
  exit;
}

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_user = intval($s_access{2});
$err = 0;

$allowed_post = array(
                "int_argosid",
                "int_sensorid",
);
$check = extractvars($_POST, $allowed_post);
#debug_input();

if (!isset($clean['argosid']) ) {
  $m = 99;
  $err = 1;
} else {
  $argosid = $clean['argosid'];
}

if (!isset($clean['sensorid']) ) {
  $m = 99;
  $err = 1;
} else {
  $sensorid = $clean['sensorid'];
}

if ($err == 0) {
  # Delete argosid 
  $sql = "DELETE FROM argos WHERE id = '$argosid'";
  $debuginfo[] = $sql;
  $query = pg_query($pgconn, $sql);
  $sql = "DELETE FROM argos_ranges WHERE sensorid = '$sensorid'";
  $debuginfo[] = $sql;
  $query = pg_query($pgconn, $sql);

  $m = 2;
}

pg_close($pgconn);
#debug_sql();
header("location: argosadmin.php?int_m=$m");
?>

<?php

####################################
# SURFids 2.10                     #
# Changeset 001                    #
# 18-11-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################
# Contributors:                    #
# Peter Arts                       #
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
  $address = getaddress();
  pg_close($pgconn);
  header("location: ${address}login.php");
  exit;
}

# Retrieving some session variables
$s_org = intval($_SESSION['s_org']);
$s_access = $_SESSION['s_access'];
$s_access_sensor = intval($s_access{0});
$s_hash = md5($_SESSION['s_hash']);

# Retrieving posted variables from $_GET
$allowed_get = array(
                "int_nid",
                "int_all",
                "int_sid",
                "md5_hash"
);
$check = extractvars($_GET, $allowed_get);
#debug_input();

# Checking if the logged in user actually requested this action.                                    
if ($clean['hash'] != $s_hash) {
  $err = 1;
  $m = 116;
}

if (isset($clean['nid'])) {
  $nid = $clean['nid'];
} else {
  $err = 1;
  $m = 160;
}

if (isset($clean['sid'])) {
  $sid = $clean['sid'];
} else {
  $err = 1;
  $m = 110;
}

if ($err == 0) {
  if ($s_access_sensor < 9) {
    $sql = "SELECT id FROM sensors WHERE organisation = '$s_org' AND id = '$sid' ";
    $result = pg_query($pgconn, $sql);
    $num = pg_num_rows($result);
    if ($num == 0) {
      $err = 1;
      $m = 101;
    }
  }
}

if ($err == 0) {
  $sql = "SELECT keyname, vlanid FROM sensor_notes WHERE id = '$nid'";
  $debuginfo[] = $sql;
  $result = pg_query($pgconn, $sql);
  $num = pg_num_rows($result);
  if ($num == 0) {
    $err = 1;
    $m = 160;
  }  
}

if ($err == 0) {
  $sql = "DELETE FROM sensor_notes WHERE id = '$nid'";
  $debuginfo[] = $sql;
  $execute = pg_query($pgconn, $sql);
  $m = 1;
}

# Close connection and redirect
pg_close($pgconn);
#debug_sql();
header("location: sensordetails.php?int_sid=$sid&int_m=$m");
?>

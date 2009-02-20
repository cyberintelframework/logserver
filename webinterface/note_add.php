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

# Retrieving posted variables from $_POST
$allowed_post = array(
                "strip_html_escape_note",
                "int_type",
        		"md5_hash",
                "int_sid",
                "int_all"
);
$check = extractvars($_POST, $allowed_post);
#debug_input();

# Checking if the logged in user actually requested this action.                                    
if ($clean['hash'] != $s_hash) {
  $err = 1;
  $m = 116;
}

if (isset($clean['all'])) {
  $all = $clean['all'];
} else {
  $m = 159;
  $err = 1;
}

if ($s_access_sensor == 9) {
  $admin = 1;
} else {
  $admin = 0;
}

if (isset($clean['sid'])) {
  $sid = $clean['sid'];
} else {
  $err = 1;
  $m = 110;
}

if (isset($clean['type'])) {
  $type = $clean['type'];
} else {
  $err = 1;
  $m = 118;
}

if (isset($clean['note'])) {
  $note = $clean['note'];
} else {
  $err = 1;
  $m = 158;
}

if ($s_access_sensor < 9) {
  $sql = "SELECT id, keyname, vlanid FROM sensors WHERE organisation = '$s_org' AND id = $sid ";
  $result = pg_query($pgconn, $sql);
  $num = pg_num_rows($result);
  if ($num == 0) {
    $err = 1;
    $m = 101;
  } else {
    $row = pg_fetch_assoc($result);
    $keyname = $row['keyname'];
    $vlanid = $row['vlanid'];
  }
} else {
  $sql = "SELECT id, keyname, vlanid FROM sensors WHERE id = $sid ";
  $result = pg_query($pgconn, $sql);
  $num = pg_num_rows($result);
  $row = pg_fetch_assoc($result);
  $keyname = $row['keyname'];
  $vlanid = $row['vlanid'];
}

if ($err != 1) {
  if ($all == 1) {
    $sql = "INSERT INTO sensor_notes (keyname, note, admin, type) ";
    $sql .= "VALUES ('$keyname', '$note', '$admin', '$type')";
  } else {
    $sql = "INSERT INTO sensor_notes (keyname, note, vlanid, admin, type) ";
    $sql .= "VALUES ('$keyname', '$note', '$vlanid', '$admin', '$type')";
  }
  $debuginfo[] = $sql;
  $execute = pg_query($pgconn, $sql);
  $m = 1;
}

# Close connection and redirect
pg_close($pgconn);
#debug_sql();
header("location: sensordetails.php?int_sid=$sid&int_m=$m");
?>

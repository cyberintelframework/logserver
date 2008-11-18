<?php

####################################
# SURFids 2.00.04                  #
# Changeset 002                    #
# 24-09-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 002 Added hash check
# 001 Initial release
#############################################

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';

session_start();
header("Cache-control: private");

if (!isset($_SESSION['s_admin'])) {
  pg_close($pgconn);
  $address = getaddress();
  header("location: ${address}login.php");
  exit;
}

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_sensor = intval($s_access{0});
$s_hash = md5($_SESSION['s_hash']);

if ($s_access_sensor == 0) {
  $m = 91;
  pg_close($pgconn);
  header("location: argosconfig.php?int_m=" .$m);
  exit;
}

$allowed_post = array(
                "int_argosid",
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

if (isset($clean['argosid'])) {
  $argosid = $clean['argosid'];
} else {
  $m = 115;
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

if ($err == 0) {
  $sql = "UPDATE argos SET imageid = '$imageid', templateid = '$templateid', timespan = '$timespan' WHERE id = '$argosid'";
  $debuginfo[] = $sql;
  $query = pg_query($pgconn, $sql);
  $m = 3;
}

# Close connection and redirect
pg_close($pgconn);
#debug_sql();
header("location: argosconfig.php?int_m=$m");
?>

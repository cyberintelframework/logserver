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
  $m = 101;
  pg_close($pgconn);
  header("location: argosadmin.php?int_m=" .$m);
  exit;
}

$allowed_post = array(
                "int_imageid",
                "strip_html_escape_name",
                "strip_html_escape_imagename",
                "ip_serverip",
                "mac_macaddr",
                "strip_html_escape_osname",
                "strip_html_escape_oslang",
                "int_orgid",
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

if (isset($clean['imageid'])) {
  $imageid = $clean['imageid'];
} else {
  $m = 112;
  $err = 1;
}
if (isset($clean['name'])) {
  $name = $clean['name'];
} else {
  $m = 102;
  $err = 1;
}
if (isset($clean['imagename'])) {
  $imagename = $clean['imagename'];
} else {
  $m = 105;
  $err = 1;
}
if (isset($clean['serverip'])) {
  $serverip = $clean['serverip'];
} else {
  $m = 106;
  $err = 1;
}
if (isset($clean['osname'])) {
  $osname = $clean['osname'];
} else {
  $m = 103;
  $err = 1;
}
if (isset($clean['oslang'])) {
  $oslang = $clean['oslang'];
} else {
  $m = 104;
  $err = 1;
}
if (isset($clean['orgid'])) {
  $orgid = $clean['orgid'];
} else {
  $m = 107;
  $err = 1;
}

if (isset($tainted['macaddr'])) {
  $m = 120;
  $err = 1;
}

if ($err == 0) {
  if (isset($clean['macaddr'])) {
    $macaddr = $clean['macaddr'];
    $sql = "UPDATE argos_images SET name = '$name', serverip = '$serverip', macaddr = '$macaddr', imagename = '$imagename', osname = '$osname', oslang = '$oslang', organisationid = '$orgid' WHERE id = '$imageid'";
    $debuginfo[] = $sql;
    $query = pg_query($pgconn, $sql);
    $m = 3;
  } else {
    $sql = "UPDATE argos_images SET name = '$name', serverip = '$serverip', imagename = '$imagename', osname = '$osname', oslang = '$oslang', organisationid = '$orgid'  WHERE id = '$imageid'";
    $debuginfo[] = $sql;
    $query = pg_query($pgconn, $sql);
    $m = 3;	
  }
}

# Close connection and redirect
pg_close($pgconn);
#debug_sql();
header("location: argosadmin.php?int_m=$m");
?>

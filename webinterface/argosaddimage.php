<?php

####################################
# SURFids 2.00.03                  #
# Changeset 003                    #
# 24-09-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 003 Added hash check
# 002 Fixed typo
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
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_sensor = intval($s_access{0});
$s_hash = md5($_SESSION['s_hash']);

# Checking access
if ($s_access_sensor == 0) {
  $m = 91;
  pg_close($pgconn);
  header("location: argosadmin.php?int_m=" .$m);
  exit;
}

# Retrieving posted variables from $_POST
$allowed_post = array(
                "ip_serverip",
                "mac_macaddr",
                "strip_html_escape_name",
                "strip_html_escape_imagename",
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

# Checking $_POST'ed variables
if (isset($clean['name'])) {
  $name = $clean['name'];
} else {
  $m = 102;
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
if (isset($clean['orgid'])) {
  $orgid = $clean['orgid'];
} else {
  $m = 107;
  $err = 1;
}

# Checking for existance of a record with $_POST'ed name and imagename
$sql = "SELECT name, imagename FROM argos_images";
$debuginfo[] = $sql;
$query = pg_query($pgconn, $sql);
while ($row = pg_fetch_assoc($query)) {
  $sqlname = $row["name"];
  $sqlimagename = $row["imagename"];
  if ($sqlname == $name) {
    $m = 108;
    $err = 1;
  }
  if ($sqlimagename == $imagename) {
    $m = 108;
    $err = 1;
  }
}

# Checking for existance of $_POST'ed MAC address
if (isset($clean['macaddr'])) {
  $macaddr = $clean['macaddr'];
  $sql = "SELECT macaddr FROM argos_images";
  $debuginfo[] = $sql;
  $query = pg_query($pgconn, $sql);
  while ($row = pg_fetch_assoc($query)) {
    $sqlmacaddr = $row["macaddr"];
    if ($sqlmacaddr == $macaddr) {
      $m = 109;
      $err = 1;
    }
  }
}

if ($err == 0) {
  # No errors found, insert record
  if (isset($clean['macaddr'])) {
    $macaddr = $clean['macaddr'];
    $sql = "INSERT INTO argos_images (name, serverip, macaddr, imagename, osname, oslang, organisationid) VALUES ('$name', '$serverip', '$macaddr', '$imagename', '$osname', '$oslang', '$orgid')";
    $debuginfo[] = $sql;
    $query = pg_query($pgconn, $sql);
    $m = 1;	
  } else {
    $sql = "INSERT INTO argos_images (name, serverip, imagename, osname, oslang, organisationid) VALUES ('$name', '$serverip', '$imagename', '$osname', '$oslang', '$orgid')";
    $debuginfo[] = $sql;
    $query = pg_query($pgconn, $sql);
    $m = 1;	
  }
}

# Close connection and redirect
pg_close($pgconn);
#debug_sql();
header("location: argosadmin.php?int_m=$m");
?>

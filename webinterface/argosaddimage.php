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


if ($s_access_sensor == 0) {
  $m = 91;
  pg_close($pgconn);
  header("location: argosadmin.php?int_m=" .$m);
  exit;
}

$allowed_post = array(
                "ip_serverip",
                "mac_macaddr",
                "strip_html_name",
                "strip_html_imagename",
                "strip_html_osname",
                "strip_html_oslang",
);
$check = extractvars($_POST, $allowed_post);
#debug_input();

$err = 0;

if (isset($clean['name'])) {
  $name = $clean['name'];
} else {
  $m = 99;
  $err = 1;
}
if (isset($clean['osname'])) {
  $osname = $clean['osname'];
} else {
  $m = 99;
  $err = 1;
}
if (isset($clean['oslang'])) {
  $oslang = $clean['oslang'];
} else {
  $m = 99;
  $err = 1;
}
if (isset($clean['imagename'])) {
  $imagename = $clean['imagename'];
} else {
  $m = 99;
  $err = 1;
}
if (isset($clean['serverip'])) {
  $serverip = $clean['serverip'];
} else {
  $m = 99;
  $err = 1;
}

$sql = "SELECT name, imagename FROM argos_images";
$debuginfo[] = $sql;
$query = pg_query($pgconn, $sql);
while ($row = pg_fetch_assoc($query)) {
  $sqlname = $row["name"];
  $sqlimagename = $row["imagename"];
  if ($sqlname == $name) {
    $m = 93;
    $err = 1;
  }
  if ($sqlimagename == $imagename) {
    $m = 93;
    $err = 1;
  }
}

if (isset($clean['macaddr'])) {
  $macaddr = $clean['macaddr'];
  $sql = "SELECT macaddr FROM argos_images";
  $debuginfo[] = $sql;
  $query = pg_query($pgconn, $sql);
  while ($row = pg_fetch_assoc($query)) {
    $sqlmacaddr = $row["macaddr"];
    if ($sqlmacaddr == $macaddr) {
      $m = 94;
      $err = 1;
    }
  }
}


if ($err == 0) {
  if (isset($clean['macaddr'])) {
    $macaddr = $clean['macaddr'];
    $sql = "INSERT INTO argos_images (name, serverip, macaddr, imagename, osname, oslang) VALUES ('$name', '$serverip', '$macaddr', '$imagename', '$osname', '$oslang')";
    $debuginfo[] = $sql;
    $query = pg_query($pgconn, $sql);
    $m = 11;	
  } else {
    $sql = "INSERT INTO argos_images (name, serverip, imagename, osname, oslang) VALUES ('$name', '$serverip', '$imagename', '$osname', '$oslang')";
    $debuginfo[] = $sql;
    $query = pg_query($pgconn, $sql);
    $m = 11;	
  }
}

pg_close($pgconn);
#debug_sql();
header("location: argosadmin.php?int_m=$m");
?>

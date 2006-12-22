<?php

####################################
# SURFnet IDS                      #
# Version 1.02.03                  #
# 09-08-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.02.03 Changed access handling + intval() to session variables
# 1.02.02 Added some more input checks + logged in check
# 1.02.01 Initial release
#############################################

include 'include/config.inc.php';
include 'include/connect.inc.php';
include 'include/functions.inc.php';

# starting session
session_start();
header("Cache-control: private");

# Check if the user is logged in
if (!isset($_SESSION['s_admin'])) {
  $address = getaddress();
  header("location: ${address}login.php");
  exit;
}

# getting the session variables
$s_org = intval($_SESSION['s_org']);
$s_access = $_SESSION['s_access'];
$s_access_sensor = intval($s_access{0});

# checking access
if ($s_access_sensor < 2) {
  $err = 1;
  $m = 80;
}

if (!isset($_GET['arpid'])) {
  $m = 71;
  $err = 1;
}
else {
  # if arpid is set
  $arpid = intval($_GET['arpid']);
  if ($s_access_sensor != 9) {
    # if the user does not have sensor admin rights, check if the record is owned by his organisation
    $arpid = intval($_GET['arpid']);
    $sql_arp = "SELECT * FROM arp_static, sensors WHERE arp_static.id = $arpid AND sensors.id = arp_static.sensor AND sensors.organisation = $s_org";
    $result_arp = pg_query($pgconn, $sql_arp);
    $numrows_arp = pg_num_rows($result_arp);
    if ($numrows_arp == 0) {
      $err = 1;
      $m = 72;
    }
  }
  # check if there even is a record with this arpid
  $sql_arp = "SELECT * FROM arp_static WHERE id = $arpid";
  $result_arp = pg_query($pgconn, $sql_arp);
  $numrows_arp = pg_num_rows($result_arp);
  if ($numrows_arp == 0) {
    $err = 1;
    $m = 73;
  }
}

if ($err != 1) {
  # no errors, deleting record
  $sql = "DELETE FROM arp_static WHERE id = $arpid";
  $execute = pg_query($pgconn, $sql);
  $m = 12;
}
pg_close($pgconn);
header("location: arpadmin.php?m=$m");
?>

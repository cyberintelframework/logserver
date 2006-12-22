<?php

####################################
# SURFnet IDS                      #
# Version 1.02.03                  #
# 09-08-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.02.03 intval() to session variables + access handling
# 1.02.02 Added some more input checks
# 1.02.01 Initial release
#############################################

include 'include/config.inc.php';
include 'include/connect.inc.php';
include 'include/functions.inc.php';

session_start();
header("Cache-control: private");

# Checking if the user is logged in
if (!isset($_SESSION['s_admin'])) {
  $address = getaddress();
  header("location: ${address}login.php");
  exit;
}

$s_org = intval($_SESSION['s_org']);
$s_access = $_SESSION['s_access'];
$s_access_sensor = intval($s_access{0});

# Fetching POST data.
$f_mac = trim(pg_escape_string($_POST['f_mac']));
$f_ip = pg_escape_string($_POST['f_ip']);
$f_sensor = intval($_POST['f_sensor']);

if ($s_access_sensor < 2) {
  $err = 1;
  $m = 80;
}

### MAC check
if (empty($f_mac)) {
  $m = 81;
  $err = 1;
}

### IP check
if (empty($f_ip)) {
  $m = 82;
  $err = 1;
} else {
  if (!preg_match($ipregexp, $f_ip)) {
    $m = 87;
    $err = 1;
  }
}

if (!preg_match("/^..:..:..:..:..:..$/", $f_mac)) {
  $m = 83;
  $err = 1;
}

# a check
if (isset($_GET['a'])) {
  $a = $_GET['a'];
  if (empty($a)) {
    $err = 1;
    $m = 84;
  }
} else {
  $err = 1;
  $m = 85;
}

if ($err != 1) {
  # check for record ownership
  if ($s_access_sensor != 9 && $a == "u") {
    $f_id = intval($_POST['f_id']);
    $sql_arp = "SELECT arp_static.* FROM arp_static, sensors WHERE arp_static.id = $f_id AND sensors.id = arp_static.sensorid AND sensors.organisation = $s_org";
    $result_arp = pg_query($pgconn, $sql_arp);
    $numrows_arp = pg_num_rows($result_arp);
    if ($numrows_arp == 0) {
      $err = 1;
      $m = 86;
    }
  }
}

if ($err != 1) {
  # if action is an update
  if ($a == "u") {
    $f_id = intval($_POST['f_id']);
    $sql = "UPDATE arp_static SET mac = '$f_mac', ip = '$f_ip', sensorid = $f_sensor WHERE id = $f_id";
    $m = 11;
  # else an insert
  } else {
    $sql = "INSERT INTO arp_static (mac, ip, sensorid) VALUES ('$f_mac', '$f_ip', $f_sensor)";
    $m = 10;
  }
  $execute = pg_query($pgconn, $sql);
}
pg_close($pgconn);
header("location: arpadmin.php?m=$m");
?>

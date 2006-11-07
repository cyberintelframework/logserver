<?php

####################################
# SURFnet IDS                      #
# Version 1.04.01                  #
# 06-11-2006                       #
# Kees Trippelvitz                 #
####################################

####################################
# Changelog:
# 1.04.01 Initial release
####################################

session_start();
if (intval(@strlen($_SESSION["s_user"])) == 0) {
  // User not logged in
  header("Location: login.php");
  exit;
}

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);

header("Content-type: image/png");
header("Cache-control: no-cache");
header("Pragma: no-cache");

if ($s_admin == 1) {
  if (isset($_GET['orgid'])) {
    $orgid = intval($_GET['orgid']);
  } else {
    $err = 1;
  }
} else {
  $orgid = $s_org;
}

if (isset($_GET['imgid'])) {
  $imgid = intval($_GET['imgid']);
} else {
  $err = 1;
}

$type = pg_escape_string($_GET['type']);
$pattern = '/^(day|week|month|year)$/';
if (preg_match($pattern, $type) != 1) {
  $type = "day";
}

if ($s_admin == 1) {
  $sql_check = "SELECT image FROM rrd WHERE id = $imgid";
} else {
  $sql_check = "SELECT image FROM rrd WHERE id = $imgid AND orgid = $s_org";
}
$result_check = pg_query($pgconn, $sql_check);
$row = pg_fetch_assoc($result_check);

$encoded_image = $row['image'];
$decoded_image = base64_decode($encoded_image);
echo "$decoded_image";

pg_close($pgconn);
?>

<?php

####################################
# SURFnet IDS                      #
# Version 2.00.01                  #
# 12-09-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

####################################
# Changelog:
# 2.00.01 version 2.00
# 1.04.03 Added header content-disposition
# 1.04.02 Changed data input handling
# 1.04.01 Released as 1.04.01
# 1.03.01 Released as part of the 1.03 package
# 1.02.01 Initial release
####################################

# Starting the session
session_start();

# Checking if the user is logged in
if (intval(@strlen($_SESSION["s_user"])) == 0) {
  # User not logged in
  header("Location: login.php");
  exit;
}

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';

# Retrieving some session variables
$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);

# Retrieving posted variables from $_GET
$allowed_get = array(
		"int_imgid",
                "type"
);
$check = extractvars($_GET, $allowed_get);

# Setting up the header info
header("Content-type: image/png");
header("Cache-control: no-cache");
header("Pragma: no-cache");
header("Content-disposition: attachment; filename=trafficstats.jpg");

# Checking $_GET'ed variables
if (isset($clean['imgid'])) {
  $imgid = $clean['imgid'];
} else {
  $err = 1;
}

$type = $tainted['type'];
$pattern = '/^(day|week|month|year)$/';
if (!preg_match($pattern, $type)) {
  $type = "day";
}

if ($err != 1) {
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
}

pg_close($pgconn);
?>

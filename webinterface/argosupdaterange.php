<?php

####################################
# SURFids 3.00                     #
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
  $m = 91;
  pg_close($pgconn);
  header("location: argosconfig.php?int_m=" .$m);
  exit;
}

$allowed_post = array(
                "int_rangeid",
                "inet_range",
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

if (isset($clean['rangeid'])) {
  $rangeid = $clean['rangeid'];
} else {
  $m = 141;
  $err = 1;
}
if (isset($clean['range'])) {
  $range = $clean['range'];
} else {
  $m = 114;
  $err = 1;
}

if ($err == 0) {
    $sql = "UPDATE argos_ranges SET range = '$range' WHERE id = '$rangeid'";
    $debuginfo[] = $sql;
    $query = pg_query($pgconn, $sql);
    $m = 3;
}

# Close connection and redirect
pg_close($pgconn);
#debug_sql();
header("location: argosconfig.php?int_m=$m");
?>

<?php

####################################
# SURFnet IDS                      #
# Version 1.02.04                  #
# 08-08-2006                       #
# Kees Trippelvitz                 #
####################################

#############################################
# Changelog:
# 1.02.04 Added intval() to $s_org and $s_admin
# 1.02.03 Changed some input checks
# 1.02.02 Fixed a $_GET vulnerability
# 1.02.01 Initial release
#############################################

include 'include/config.inc.php';
include 'include/connect.inc.php';
include 'include/functions.inc.php';

session_start();
header("Cache-control: private");

# Checking if the user is logged in
if (!isset($_SESSION['s_admin'])) {
  $absfile = $_SERVER['SCRIPT_NAME'];
  $file = basename($absfile);
  $dir = str_replace($file, "", $absfile);
  $dir = ltrim($dir, "/");
  $https = $_SERVER['HTTPS'];
  if ($https == "") {
    $http = "http";
  }
  else {
    $http = "https";
  }
  $servername = $_SERVER['SERVER_NAME'];
  $address = "$http://$servername:$web_port/$dir";
  header("location: ${address}login.php");
  exit;
}

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_user = $s_access{2};
$err = 0;

$orgid = intval($_POST['f_orgid']);
$org = pg_escape_string(trim($_POST['f_org']));
$ranges = $_POST['f_ranges'];

if ($s_access_user < 2) {
  $err = 1;
  $m = 91;
}

if (empty($org)) {
  $err = 1;
  $m = 92;
}
else {
  $sql_check = "SELECT * FROM organisations WHERE organisation = '$org' AND NOT id = $orgid";
  $result_check = pg_query($pgconn, $sql_check);
  $numrows_check = pg_num_rows($result_check);
  if ($numrows_check != 0) {
    $err = 1;
    $m = 93;
  }
}

if ($err != 1) {
  $ranges = str_replace("\r", ";", $ranges);
  $ranges = str_replace("\n", "", $ranges);
  $ranges = pg_escape_string($ranges);
  $sql = "UPDATE organisations SET organisation = '" .$org. "', ranges = '" .$ranges. "' WHERE id = $orgid";
  $execute = pg_query($pgconn, $sql);
  $m = 1;
}

header("location: orgadmin.php?m=$m");

pg_close($pgconn);
?>

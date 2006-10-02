<?php

####################################
# SURFnet IDS                      #
# Version 1.02.05                  #
# 08-08-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.02.05 Added intval() for $s_admin
# 1.02.04 Added intval() for $s_org
# 1.02.03 Added some more input checks
# 1.02.02 Added login check
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
}

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_user = $s_access{2};
$err = 0;

if ( ! isset($_GET['userid']) ) {
  $m = 83;
  $err = 1;
}
else {
  $userid = intval($_GET['userid']);
}

if ($s_access_user < 2) {
  $m = 82;
  $err = 1;
}
elseif ($s_access_user < 9) {
  $sql_check = "SELECT organisation FROM login WHERE id = $userid AND organisation = $s_org";
  $result_check = pg_query($pgconn, $sql_check);
  $numrows_check = pg_num_rows($result_check);
  if ($numrows_check == 0) {
    $m = 81;
    $err = 1;
  }
}

if ($err == 0) {
  $userid = intval($_GET['userid']);
  $sql = "DELETE FROM login WHERE id = $userid";
  $execute = pg_query($pgconn, $sql);
  $m = 80;
}

header("location: useradmin.php?m=$m");
pg_close($pgconn);
?>

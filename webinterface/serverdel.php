<?php

####################################
# SURFnet IDS                      #
# Version 1.02.07                  #
# 08-08-2006                       #
# Kees Trippelvitz                 #
####################################

#############################################
# Changelog:
# 1.02.07 Added intval() for $s_org and $s_admin
# 1.02.06 Added intval() to $default_server
# 1.02.05 Added check to make sure at least 1 server is present
# 1.02.04 Changed some input checks
# 1.02.03 Added login check
# 1.02.02 Initial release
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
$err = 0;

if ($s_admin != 1) {
  $err = 1;
  $m = 91;
}

if (!isset($_GET['serverid'])) {
  $err = 1;
  $m = 92;
}

$sql_check = "SELECT count(id) as total FROM servers";
$result_check = pg_query($pgconn, $sql_check);
$row_check = pg_fetch_assoc($result_check);
$numrows_check = $row_check['total'];
if ($numrows_check == 1) {
  $err = 1;
  $m = 94;
}

if ($err == 0) {
  $serverid = intval($_GET['serverid']);
  $sql_check = "SELECT * FROM sensors WHERE server = $serverid";
  $result_check = pg_query($pgconn, $sql_check);
  $numrows_check = pg_num_rows($result_check);

  if ($numrows_check != 0) {
    $sql_default = "SELECT * FROM servers WHERE NOT id = $serverid ORDER BY id ASC LIMIT 1";
    $result_default = pg_query($pgconn, $sql_default);
    $row_default = pg_fetch_assoc($result_default);
    $default_server = intval($row_default['id']);

    $sql_reset = "UPDATE sensors SET server = $default_server WHERE server = $serverid";
    $result_reset = pg_query($pgconn, $sql_reset);
  }

  $sql = "DELETE FROM servers WHERE id = $serverid";
  $execute = pg_query($pgconn, $sql);
  $m = 11;
}

if ($m == 11) {
  header("location: serveradmin.php?m=$m&c=$numrows_check");
}
else {
  header("location: serveradmin.php?m=$m");
}
pg_close($pgconn);
?>

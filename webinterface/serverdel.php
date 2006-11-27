<?php

####################################
# SURFnet IDS                      #
# Version 1.03.01                  #
# 10-10-2006                       #
# Kees Trippelvitz                 #
####################################

#############################################
# Changelog:
# 1.03.01 Released as part of the 1.03 package
# 1.02.03 Added some more input checks + login check
# 1.02.02 Fixed a bug with the server resetting
# 1.02.01 Initial release                   
#############################################

session_start();
header("Cache-control: private");

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';

if (!isset($_SESSION['s_admin'])) {
  $address = getaddress($web_port);
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
  $m = 30;
}

$sql_check = "SELECT count(id) as total FROM servers";
$result_check = pg_query($pgconn, $sql_check);
$row_check = pg_fetch_assoc($result_check);
$numrows_check = $row_check['total'];
if ($numrows_check == 1) {
  $err = 1;
  $m = 32;
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
    $default_server = $row_default['id'];

    $sql_reset = "UPDATE sensors SET server = $default_server WHERE server = $serverid";
    $result_reset = pg_query($pgconn, $sql_reset);
  }

  $sql = "DELETE FROM servers WHERE id = $serverid";
  $execute = pg_query($pgconn, $sql);
  $m = 100;
}

pg_close($pgconn);
if ($m == 100) {
  header("location: serveradmin.php?m=$m&c=$numrows_check");
} else {
  header("location: serveradmin.php?m=$m");
}
?>

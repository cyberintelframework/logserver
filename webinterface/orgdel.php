<?php

####################################
# SURFnet IDS                      #
# Version 1.03.01                  #
# 10-10-2006                       #
# Kees Trippelvitz                 #
####################################

####################################
# Changelog:
# 1.03.01 Released as part of the 1.03 package
# 1.02.03 Added intval() to session variables + $s_admin check
# 1.02.02 Added some more input checks
# 1.02.01 Initial release
####################################

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';

if (!isset($_SESSION['s_admin'])) {
  $address = getaddress($web_port);
  header("location: ${address}login.php");
  exit;
}

session_start();
header("Cache-control: private");

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$err = 0;

if ($s_admin != 1) {
  $err = 1;
  $m = 91;
}

if (!isset($_GET['orgid'])) {
  $err = 1;
  $m = 36;
}

if (!isset($_GET['ident'])) {
  $err = 1;
  $m = 35;
}

if ($err == 0) {
  $orgid = intval($_GET['orgid']);
  $ident = intval($_GET['ident']);
  $sql_check = "SELECT * FROM org_id WHERE orgid = $orgid";
  $result_check = pg_query($pgconn, $sql_check);
  $numrows_check = pg_num_rows($result_check);

  if ($numrows_check != 0) {
    $sql_del = "DELETE FROM org_id WHERE id = $ident AND orgid = $orgid";
    $execute = pg_query($pgconn, $sql_del);
    $m = 11;
  } else {
    $m = 4;
  }
}
pg_close($pgconn);
header("location: orgedit.php?orgid=$orgid&m=$m");
?>

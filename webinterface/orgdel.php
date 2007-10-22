<?php

####################################
# SURFnet IDS                      #
# Version 2.00.01                  #
# 12-09-2007                       #
# Kees Trippelvitz & Jan van Lith  #
####################################

####################################
# Changelog:
# 2.00.01 version 2.00
# 1.04.04 Added hash check
# 1.04.03 Changed data input handling
# 1.04.02 Modified error messages
# 1.04.01 Added pg_close when not logged in
# 1.03.01 Released as part of the 1.03 package
# 1.02.03 Added intval() to session variables + $s_admin check
# 1.02.02 Added some more input checks
# 1.02.01 Initial release
####################################

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';

# Starting the session
session_start();
header("Cache-control: private");

# Checking if the user is logged in
if (!isset($_SESSION['s_admin'])) {
  pg_close($pgconn);
  $address = getaddress();
  header("location: ${address}login.php");
  exit;
}

$err = 0;

# Retrieving posted variables from $_GET
$allowed_get = array(
                "int_orgid",
		"int_ident",
		"md5_hash"
);
$check = extractvars($_GET, $allowed_get);
#debug_input();

# Checking $_GET'ed variables
if ($clean['hash'] != $s_hash) {
  $err = 1;
  $m = 116;
}

if ($s_admin != 1) {
  $err = 1;
  $m = 101;
}

if (!isset($clean['orgid'])) {
  $err = 1;
  $m = 107;
}

if (!isset($clean['ident'])) {
  $err = 1;
  $m = 126;
}

if ($err == 0) {
  $orgid = $clean['orgid'];
  $ident = $clean['ident'];
  $sql_check = "SELECT * FROM org_id WHERE orgid = $orgid";
  $result_check = pg_query($pgconn, $sql_check);
  $numrows_check = pg_num_rows($result_check);

  if ($numrows_check != 0) {
    $sql_del = "DELETE FROM org_id WHERE id = $ident AND orgid = $orgid";
    $execute = pg_query($pgconn, $sql_del);
    $m = 2;
  }
}
pg_close($pgconn);
header("location: orgedit.php?int_orgid=$orgid&int_m=$m");
?>

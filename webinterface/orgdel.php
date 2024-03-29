<?php

####################################
# SURFids 3.00                     #
# Changeset 002                    #
# 23-06-2009                       #
# Kees Trippelvitz & Jan van Lith  #
####################################

####################################
# Changelog:
# 002 Fixed permission issue
# 001 Initial release
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

$s_admin = intval($_SESSION['s_admin']);
$s_hash = md5($_SESSION['s_hash']);
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

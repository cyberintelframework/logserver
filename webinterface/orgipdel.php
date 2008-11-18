<?php

####################################
# SURFids 2.04                     #
# Changeset 002                    #
# 17-11-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 002 Added md5_hash check against CRSS attacks
# 001 Initial release
#############################################

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

# Retrieving some session variables
$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_user = intval($s_access{2});
$err = 0;

# Retrieving posted variables from $_GET
$allowed_get = array(
                "int_id",
        		"int_orgid",
                "md5_hash"
);
$check = extractvars($_GET, $allowed_get);
#debug_input();

# Checking if the logged in user actually requested this action.
if ($clean['hash'] != $s_hash) {
  $err = 1;
  $m = 116;
}

if ($s_admin == 1) {
  if (isset($clean['orgid'])) {
    $org = $clean['orgid'];
  } else {
    $org = $s_org;
  }
} else {
  $org = $s_org;
}

if (!isset($clean['id']) ) {
  $m = 92;
  $err = 1;
} else {
  $id = $clean['id'];
}

# Checking access
if ($s_access_user < 2) {
  $m = 91;
  $err = 1;
}

if ($err == 0) {
  $sql = "DELETE FROM org_excl WHERE id = $id AND orgid = $org";
  $debuginfo[] = $sql;
  $execute = pg_query($pgconn, $sql);
  
  $m = 2;
}

# Close connection and redirect
pg_close($pgconn);
#debug_sql();
header("location: orgipadmin.php?int_m=$m&int_orgid=$org");
?>

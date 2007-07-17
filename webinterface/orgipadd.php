<?php

####################################
# SURFnet IDS                      #
# Version 1.04.01                  #
# 18-05-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.04.01 Initial release
#############################################

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';

# Starting the session
session_start();
header("Cache-control: private");

# Checking if the user is logged in
if (!isset($_SESSION['s_admin'])) {
  $address = getaddress();
  pg_close($pgconn);
  header("location: ${address}login.php");
  exit;
}

# Retrieving some session variables
$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_user = intval($s_access{2});
$s_hash = md5($_SESSION['s_hash']);

# Retrieving posted variables from $_POST
$allowed_post = array(
                "int_orgid",
                "ip_exclusion",
		"md5_hash"
);
$check = extractvars($_POST, $allowed_post);
#debug_input();

# Checking access
if ($s_access_user < 2) {
  $err = 1;
  $m = 91;
}

# Setting up organisation
if ($s_admin == 1) {
  if (isset($clean['orgid'])) {
    $org = $clean['orgid'];
    if ($org == 0) {
      $err = 1;
      $m = 93;
    }
  } else {
    $org = $s_org;
  }
} else {
  $org = $s_org;
}


if ($clean['hash'] != $s_hash) {
  $err = 1;
  $m = 91;
}

# Checking if the username was set.
if (!isset($clean['exclusion'])) {
  $m = 92;
  $err = 1;
} else {
  $exclusion = $clean['exclusion'];
}

if ($err != 1) {
  $sql = "INSERT INTO org_excl (orgid, exclusion) ";
  $sql .= "VALUES ($org, '$exclusion')";
  $debuginfo[] = $sql;
  $execute = pg_query($pgconn, $sql);
  $m = 1;
}

# Close connection and redirect
pg_close($pgconn);
#debug_sql();
header("location: orgipadmin.php?int_m=$m&int_orgid=$org");
?>

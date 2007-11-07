<?php

####################################
# SURFnet IDS                      #
# Version 2.10.01                  #
# 06-11-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 2.10.01 Initial release
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
		"md5_hash"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

# Checking if the logged in user actually requested this action.
if ($clean['hash'] != $s_hash) {
  $err = 1;
  $m = 116;
}

# Checking $_GET'ed variables
if (!isset($clean['id']) ) {
  $m = 117;
  $err = 1;
} else {
  $id = $clean['id'];
}

# Checking access
if ($s_access_user < 2) {
  $m = 101;
  $err = 1;
} elseif ($s_access_user < 9) {
  $sql_check = "SELECT owner FROM groups WHERE id = $id AND owner = '$s_org'";
  $debuginfo[] = $sql_check;
  $result_check = pg_query($pgconn, $sql_check);
  $numrows_check = pg_num_rows($result_check);
  if ($numrows_check == 0) {
    $m = 101;
    $err = 1;
  }
}

if ($err == 0) {
  # Deleting all mailreports from the user
  $sql = "DELETE FROM groupmembers WHERE groupid = '$id'";
  $debuginfo[] = $sql;
  $query = pg_query($pgconn, $sql);

  # Deleting the actual user records
  $sql = "DELETE FROM groups WHERE id = $id";
  $debuginfo[] = $sql;
  $execute = pg_query($pgconn, $sql);
  
  $m = 2;
}

# Close connection and redirect
pg_close($pgconn);
#debug_sql();
#header("location: groupadmin.php?int_m=$m");
?>

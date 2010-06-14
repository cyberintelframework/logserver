<?php

####################################
# SURFids 3.00                     #
# Changeset 002                    #
# 03-11-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################
# Contributors:                    #
# Peter Arts                       #
####################################

#############################################
# Changelog:
# 002 Added md5 hash check
# 001 version 2.10
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
$s_hash = md5($_SESSION['s_hash']);
$err = 0;

# Retrieving posted variables from $_GET
$allowed_get = array(
                "int_userid",
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
if (!isset($clean['userid']) ) {
  $m = 139;
  $err = 1;
} else {
  $userid = $clean['userid'];
}

# Checking access
if ($s_access_user < 2) {
  $m = 101;
  $err = 1;
} elseif ($s_access_user < 9) {
  $sql_check = "SELECT organisation FROM login WHERE id = $userid AND organisation = $s_org";
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
#  $sql = "DELETE FROM report_content WHERE user_id = '$userid'";
#  $debuginfo[] = $sql;
#  $query = pg_query($pgconn, $sql);

  # Deleting all page configs from the user
#  $sql = "DELETE FROM pageconf WHERE userid = '$userid'";
#  $debuginfo[] = $sql;
#  $query = pg_query($pgconn, $sql);

  # Deleting the actual user records
  $sql = "DELETE FROM login WHERE id = $userid";
  $debuginfo[] = $sql;
  $execute = pg_query($pgconn, $sql);
  
  $m = 2;
}

# Close connection and redirect
pg_close($pgconn);
#debug_sql();
header("location: useradmin.php?int_m=$m");
?>

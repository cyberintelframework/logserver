<?php

####################################
# SURFids 3.00                     #
# Changeset 001                    #
# 18-11-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
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
  $address = getaddress();
  pg_close($pgconn);
  header("location: ${address}login.php");
  exit;
}

# Retrieving some session variables
$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_userid = intval($_SESSION['s_userid']);
$s_access = $_SESSION['s_access'];
$s_access_user = intval($s_access{2});
$s_hash = md5($_SESSION['s_hash']);
$err = 0;

# Retrieving posted variables from $_GET
$allowed_get = array(
                "int_userid",
                "a",
		"md5_hash"
);
$check = extractvars($_GET, $allowed_get);
#debug_input();

# Make sure all access rights are correct
if (isset($clean['userid'])) {
  $user_id = $clean['userid'];
  if ($s_access_user < 1) {
    header("location: myreports.php?int_userid=$user_id&int_m=90");
    pg_close($pgconn);
    exit;
  } elseif ($s_access_user < 2) {
    $user_id = $s_userid;
  } elseif ($s_access_user < 9) {
    $sql_login = "SELECT id FROM login WHERE organisation = $s_org AND id = $user_id";
    $result_login = pg_query($pgconn, $sql_login);
    $numrows_login = pg_num_rows($result_login);
    if ($numrows_login == 0) {
      $m = 101;
      geterror($m);
      footer();
      exit;
    } else {
      $user_id = $clean['userid'];
    }
  } else {
    $user_id = $clean['userid'];
  }
} else {
  $user_id = $s_userid;
}

$action = $tainted['a'];
$pattern = '/^(d|e|r)$/';
if (!preg_match($pattern, $action)) {
  $err = 1;
  $m = 119;
}

if ($clean['hash'] != $s_hash) {
  $err = 1;
  $m = 116;
}

if ($err == 0) {
  $sql_mod = "UPDATE report_content ";
  if ($action == "d") {
    $sql_mod .= "SET active = 'f' ";
    $m = 6;
  } elseif ($action == "e") {
    $sql_mod .= "SET active = 't' ";
    $m = 7;
  } elseif ($action == "r") {
    $sql_mod .= "SET last_sent = NULL ";
    $m = 5;
  }
  $sql_mod .= "WHERE user_id = '$user_id'";

  $query = pg_query($sql_mod);
}

# Close connection and redirect
pg_close($pgconn);
header("location: myreports.php?int_userid=$user_id&int_m=$m");
?>

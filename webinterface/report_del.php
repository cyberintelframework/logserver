<?php

####################################
# SURFids 2.10                     #
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
$s_hash = md5($_SESSION['s_hash']);
$s_access = $_SESSION['s_access'];
$s_access_user = intval($s_access{2});
$err = 0;

# Retrieving posted variables from $_GET
$allowed_get = array(
                "int_userid",
		"int_rcid"
);
$check = extractvars($_GET, $allowed_get);
#debug_input();

$err = 0;

if (!isset($clean['rcid'])) {
  $err = 1;
  $m = 135;
} else {
  $reportid = $clean['rcid'];
}

if ($err == 0) {
  if ($s_access_user == 0) {
    $err = 1;
    $m = 101;
  } elseif ($s_access_user == 2) {
    $sql_check = "SELECT report_content.id FROM report_content, login ";
    $sql_check .= " WHERE login.id = report_content.user_id AND login.organisation = '$s_org' AND report_content.id = '$reportid'";
    $result = pg_query($pgconn, $sql_check);
    $numrows = pg_num_rows($result);
    if ($numrows == 0) {
      $err = 1;
      $m = 101;
    }
  } elseif ($s_access_user == 1) {
    $sql_check = "SELECT report_content.id FROM report_content, login ";
    $sql_check .= " WHERE login.id = report_content.user_id AND login.organisation = '$s_org' AND report_content.id = '$reportid'";
    $sql_check .= " AND report_content.user_id = '$s_userid'";
    $result = pg_query($pgconn, $sql_check);
    $numrows = pg_num_rows($result);
    if ($numrows == 0) {
      $err = 1;
      $m = 101;
    }
  }
}

if ($err == 0) {
  $sql .= "DELETE FROM report_content WHERE id = '$reportid'";
  $debuginfo[] = $sql;
  $ec = pg_query($pgconn, $sql);
  $m = 2;
}

# Close connection and redirect
pg_close($pgconn);
#debug_sql();
header("location: myreports.php?int_m=$m");
?>

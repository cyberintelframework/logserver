<?php

####################################
# SURFnet IDS                      #
# Version 1.04.04                  #
# 16-03-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################
# Contributors:                    #
# Peter Arts                       #
####################################

#############################################
# Changelog:
# 1.04.04 Added hash check
# 1.04.03 Changed data input handling
# 1.04.02 Added debug info
# 1.04.01 Rereleased as 1.04.01
# 1.03.02 Removed and changed some stuff referring to the report table
# 1.03.01 Released as part of the 1.03 package
# 1.02.04 SQL injection fix
# 1.02.03 Added some more input checks
# 1.02.02 Removed old maillogging and email data
# 1.02.01 Initial release
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
$s_access = $_SESSION['s_access'];
$s_access_user = intval($s_access{2});
$s_hash = md5($_SESSION['s_hash']);

# Retrieving posted variables from $_POST
$allowed_post = array(
                "strip_html_escape_username",
                "int_asensor",
                "int_asearch",
                "int_auser",
                "int_userid",
                "md5_pass",
                "md5_confirm",
                "int_org",
                "strip_html_escape_email",
                "int_gpg",
		"md5_hash"
);
$check = extractvars($_POST, $allowed_post);
#debug_input();

# Checking if the logged in user actually requested this action.                                    
if ($clean['hash'] != $s_hash) {
  $err = 1;
  $m = 91;
}

# Checking MD5sums
if (isset($clean['pass'])) {
  $pass = $clean['pass'];
} else {
  $pass = "";
}
if (isset($clean['confirm'])) {
  $confirm = $clean['confirm'];
} else {
  $confirm = "";
}

# Checking if the username was set.
if (!isset($clean['username'])) {
  $m = 92;
  $err = 1;
} else {
  $username = $clean['username'];
}

# Fetching POST data
$asensor = $clean['asensor'];
$asearch = $clean['asearch'];
$auser = $clean['auser'];
$userid = $clean['userid'];
$org = $clean['org'];
$email = $clean['email'];
$gpg = $clean['gpg'];

### Password check
if (empty($pass) || empty($confirm)) { 
  $m = 93;
  $err = 1;
} elseif ($pass != $confirm) {
  $m = 94;
  $err = 1;
} elseif (empty($org) || $org == 0) {
  $m = 95;
  $err = 1;
}

# Checking access
if ($s_access_user < 2) {
  $m = 91;
  $err = 1;
} elseif ($s_access_user == 2) {
  $org = $s_org;
  if ($asensor >= 9) {
    $err = 1;
    $m = 91;
  } elseif ($asearch >= 9) {
    $err = 1;
    $m = 91;
  } elseif ($auser >= 9) {
    $err = 1;
    $m = 91;
  } else {
    $access = $asensor . $asearch . $auser;
  }
} elseif ($s_access_user == 9) {
  $access = $asensor . $asearch . $auser;
}

$sql = "SELECT username FROM login WHERE username = '$username'";
$debuginfo[] = $sql;
$result_user = pg_query($pgconn, $sql);
$rows = pg_num_rows($result_user);
if ($rows == 1) {
  $m = 92;
  $err = 1;
}

if ($err != 1) {
  $sql = "INSERT INTO login (username, password, organisation, access, email, gpg) ";
  $sql .= "VALUES ('$username', '$pass', '$org', '$access', '$email', $gpg)";
  $debuginfo[] = $sql;
  $execute = pg_query($pgconn, $sql);
  $m = 1;
  if ($c_default_mail_sensor == 1) {
    $sql_getuid = "SELECT id FROM login WHERE username = '$username'";
    $debuginfo[] = $sql_getuid;
    $result_getuid = pg_query($pgconn, $sql_getuid);
    $row_getuid = pg_fetch_assoc($result_getuid);
    $uid = $row_getuid['id'];
    if ($uid) {
      $title = "Hourly sensor status";
      $sql_report = "INSERT INTO report_content (user_id, title, template, active, frequency, interval, priority, subject) ";
      $sql_report .= "VALUES ($uid, '$title', 4, 't', 1, 0, 1, '$title')";
      $debuginfo[] = $sql_report;
      $execute = pg_query($pgconn, $sql_report);
    }
  }
}

# Close connection and redirect
pg_close($pgconn);
#debug_sql();
header("location: useradmin.php?int_m=$m");
?>

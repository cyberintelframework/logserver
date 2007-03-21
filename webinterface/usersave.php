<?php

####################################
# SURFnet IDS                      #
# Version 1.04.03                  #
# 15-12-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.04.03 Changed data input handling
# 1.04.02 Fixed a bug with access handling
# 1.04.01 Fixed username check
# 1.03.02 Removed and changed some stuff referring to the report table
# 1.03.01 Released as part of the 1.03 package
# 1.02.03 SQL injection fix
# 1.02.02 Added some more input checks and removed includes
# 1.02.01 Initial release
#############################################

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';

session_start();
header("Cache-control: private");

# Checking if the user is logged in
if (!isset($_SESSION['s_admin'])) {
  pg_close($pgconn);
  $address = getaddress();
  header("location: ${address}login.php");
  exit;
}

$s_org = intval($_SESSION['s_org']);
$s_userid = intval($_SESSION['s_userid']);
$s_access = $_SESSION['s_access'];
$s_auser = intval($s_access{2});
$s_hash = md5($_SESSION['s_hash']);
$err = 0;

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
  $err = 1;
  $m = 92;
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

# Setting default $access value
$access = "111";

# Checking for access rights.
if ($s_auser == 0) {
  $err = 1;
  $m = 91;
} elseif ($s_auser == 1) {
  $userid = $s_userid;
  $org = $s_org;
  $access = $s_access;
} elseif ($s_auser == 2) {
  $f_org = $s_org;
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
} elseif ($s_auser == 9) {
  $access = $asensor . $asearch . $auser;
}

# Checking if the passwords were correct.
if ($pass != $confirm) {
  $err = 1;
  $m = 94;
}

# Checking if the organisation is set correctly.
if ($clean['org'] == 0) {
  $err = 1;
  $m = 95;
}

$sql = "SELECT username FROM login WHERE username = '$username' AND NOT id = $userid";
$debuginfo[] = $sql;
$result_user = pg_query($pgconn, $sql);
$rows = pg_num_rows($result_user);
if ($rows == 1) {
  $m = 92;
  $err = 1;
}

if ($err != 1) {
  if ($pass == "") {
    $passwordstring = "";
  } else {
    $passwordstring = ", password = '$pass'";
  }
  if ($s_auser < 2) {
    $m = 3;
    $sql_save = "UPDATE login SET username = '$username', email = '$email', gpg = $gpg $passwordstring WHERE id = $userid";
  } elseif ($s_auser < 9) {
    $m = 3;
    $sql_save = "UPDATE login SET username = '$username', email = '$email', gpg = $gpg $passwordstring, access = '$access' WHERE id = $userid";
  } elseif ($s_auser == 9) {
    $sql_save = "UPDATE login SET username = '$username', email = '$email', gpg = $gpg $passwordstring, access = '$access', organisation = '$org' WHERE id = $userid";
    $m = 3;
  }
  $debuginfo[] = $sql_save;
  $execute_save = pg_query($pgconn, $sql_save);
}
pg_close($pgconn);
#debug_sql();
header("location: useradmin.php?int_m=$m");
?>

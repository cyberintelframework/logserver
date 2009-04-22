<?php

####################################
# SURFids 3.00                     #
# Changeset 001                    #
# 18-11-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################
# Contributors:                    #
# Peter Arts                       #
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
  $m = 116;
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
  $m = 144;
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
  $m = 137;
  $err = 1;
} elseif ($pass != $confirm) {
  $m = 137;
  $err = 1;
}

if (empty($org) || $org == 0) {
  $m = 107;
  $err = 1;
}

# Checking access
if ($s_access_user < 2) {
  $m = 101;
  $err = 1;
} elseif ($s_access_user == 2) {
  $org = $s_org;
  if ($asensor >= 9) {
    $err = 1;
    $m = 101;
  } elseif ($asearch >= 9) {
    $err = 1;
    $m = 101;
  } elseif ($auser >= 9) {
    $err = 1;
    $m = 101;
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
  $m = 138;
  $err = 1;
}

if ($err != 1) {
  $sql = "INSERT INTO login (username, password, organisation, access, email, gpg) ";
  $sql .= "VALUES ('$username', '$pass', '$org', '$access', '$email', $gpg)";
  $debuginfo[] = $sql;
  $execute = pg_query($pgconn, $sql);
  $m = 1;

  $sql = "SELECT id FROM login WHERE username = '$username'";
  $debuginfo[] = $sql;
  $result_user = pg_query($pgconn, $sql);
  $row = pg_fetch_assoc($result_user);
  $db_uid = $row['id'];

  $sql = "INSERT INTO pageconf (userid, pageid, config) values ($db_uid, 1, '1,3,4,6,9,11')";
  $debuginfo[] = $sql;
  $execute = pg_query($pgconn, $sql);
}

# Close connection and redirect
pg_close($pgconn);
#debug_sql();
header("location: useradmin.php?int_m=$m");
?>

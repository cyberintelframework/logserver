<?php

####################################
# SURFnet IDS                      #
# Version 1.02.03                  #
# 31-07-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
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
  $address = getaddress($web_port);
  header("location: ${address}login.php");
  exit;
}

$s_org = $_SESSION['s_org'];
$s_userid = $_SESSION['s_userid'];
$s_access = $_SESSION['s_access'];
$s_access_user = $s_access{2};
$err = 0;

# Checking if the username was set.
if ( ! isset($_POST['f_username']) ) {
  $err = 1;
  $m = 71;
}
else {
  $f_username = stripinput(trim(pg_escape_string($_POST['f_username'])));
  if ($f_username == "") {
    $err = 1;
    $m = 71;
  }
}

# Fetching POST data
$f_access_sensor = intval($_POST['f_access_sensor']);
$f_access_search = intval($_POST['f_access_search']);
$f_access_user = intval($_POST['f_access_user']);
$f_userid = intval($_POST['f_userid']);
$f_pass = pg_escape_string(stripinput($_POST['f_pass']));
$f_confirm = pg_escape_string(stripinput($_POST['f_confirm']));
$f_org = intval($_POST['f_org']);
$f_maillog = intval($_POST['f_maillog']);
$f_email = stripinput(pg_escape_string($_POST['f_email']));
$f_username = stripinput(trim(pg_escape_string($_POST['f_username'])));

# Checking for access rights.
if ($s_access_user == 0) {
  $err = 1;
  $m = 74;
}
elseif ($s_access_user == 1) {
  $f_userid = $s_userid;
  $f_org = $s_org;
  $access = $s_access;
}
elseif ($s_access_user == 2) {
  $f_org = $s_org;
  $access = $f_access_sensor . $f_access_search . $f_access_user;
}
else {
  $access = $f_access_sensor . $f_access_search . $f_access_user;
}

# Checking if the passwords were correct.
if ($f_pass != $f_confirm) {
  $err = 1;
  $m = 72;
}

# Checking if the organisation is set correctly.
if ( $_POST['f_org'] == "none" ) {
  $err = 1;
  $m = 73;
}

if ($err != 1) {
  if ($f_pass == "") {
    $passwordstring = "";
  }
  else {
    $passwordstring = ", password = '$f_pass'";
  }
  if ($s_access_user < 2) {
    $m = 70;
    $sql_save = "UPDATE login SET username = '$f_username', email = '$f_email'$passwordstring WHERE id = $f_userid";
  }
  elseif ($s_access_user < 9) {
    $m = 70;
    $sql_save = "UPDATE login SET username = '$f_username', email = '$f_email'$passwordstring, access = '$access' WHERE id = $f_userid";
  }
  elseif ($s_access_user == 9) {
    $sql_save = "UPDATE login SET username = '$f_username', email = '$f_email'$passwordstring, access = '$access', organisation = '$f_org' WHERE id = $f_userid";
    $m = 70;
  }
  else {
    $m = 79;
  }
  $execute_save = pg_query($pgconn, $sql_save);
}
pg_close($pgconn);
header("location: useradmin.php?m=$m");
?>

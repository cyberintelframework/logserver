<?php

####################################
# SURFnet IDS                      #
# Version 1.02.06                  #
# 08-08-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.02.06 Removed intval() from $s_access
# 1.02.05 intval() for $s_admin and $s_access
# 1.02.04 Fixed an SQL injection vulnerability
# 1.02.03 Added some more input checks
# 1.02.02 Added login check
# 1.02.01 Initial release
#############################################

include 'include/config.inc.php';
include 'include/connect.inc.php';
include 'include/functions.inc.php';

session_start();
header("Cache-control: private");

# Checking if the user is logged in
if (!isset($_SESSION['s_admin'])) {
  $absfile = $_SERVER['SCRIPT_NAME'];
  $file = basename($absfile);
  $dir = str_replace($file, "", $absfile);
  $dir = ltrim($dir, "/");
  $https = $_SERVER['HTTPS'];
  if ($https == "") {
    $http = "http";
  }
  else {
    $http = "https";
  }
  $servername = $_SERVER['SERVER_NAME'];
  $address = "$http://$servername:$web_port/$dir";
  header("location: ${address}login.php");
  exit;
}

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_userid = intval($_SESSION['s_userid']);
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
$f_maillog = intval(pg_escape_string($_POST['f_maillog']));
$f_email = stripinput(pg_escape_string($_POST['f_email']));
$f_username = stripinput(pg_escape_string($_POST['f_username']));

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
    $sql_save = "UPDATE login SET username = '$f_username', email = '$f_email', maillog = $f_maillog$passwordstring WHERE id = $s_userid";
  }
  elseif ($s_access_user < 9) {
    $m = 70;
    $sql_save = "UPDATE login SET username = '$f_username', email = '$f_email', maillog = $f_maillog$passwordstring, access = '$access' WHERE id = $f_userid AND organisation = $s_org";
  }
  elseif ($s_access_user == 9) {
    $sql_save = "UPDATE login SET username = '$f_username', email = '$f_email', maillog = $f_maillog$passwordstring, access = '$access', organisation = '$f_org' WHERE id = $f_userid";
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

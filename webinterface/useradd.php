<?php

####################################
# SURFnet IDS                      #
# Version 1.02.04                  #
# 31-07-2006                       #
# Jan van Lith & Kees Trippelvitz  #
# Modified by Peter Arts           #
####################################

#############################################
# Changelog:
# 1.02.04 SQL injection fix
# 1.02.03 Added some more input checks
# 1.02.02 Removed old maillogging and email data
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
$s_access = $_SESSION['s_access'];
$s_access_user = $s_access{2};

# Fetching POST data.
$f_username = stripinput(trim(pg_escape_string($_POST['f_username'])));
$f_org = intval($_POST['f_org']);
$f_pass = pg_escape_string(stripinput($_POST['f_pass']));
$f_confirm = pg_escape_string(stripinput($_POST['f_confirm']));
$f_access_user = intval($_POST['f_access_user']);
$f_access_search = intval($_POST['f_access_search']);
$f_access_sensor = intval($_POST['f_access_sensor']);
$f_access = $f_access_sensor . $f_access_search . $f_access_user;

### Password check
if (empty($f_pass) || empty($f_confirm)) { 
  $m = 91;
  $err = 1;
}
elseif ($f_pass != $f_confirm) {
  $m = 92;
  $err = 1;
}
elseif (empty($f_username)) {
  $m = 93;
  $err = 1;
}
elseif (empty($f_org) || $f_org == "none") {
  $m = 94;
  $err = 1;
}

if ($s_access_user < 2) {
  $m = 96;
  $err = 1;
}
elseif ($s_access_user == 2) {
  $f_org = $s_org;
}

$sql = "SELECT username FROM login WHERE username = '$f_username'";
$result_user = pg_query($pgconn, $sql);
$rows = pg_num_rows($result_user);
if ($rows == 1) {
  $m = 97;
  $err = 1;
}

if ($err != 1) {
  $sql = "INSERT INTO login (username, password, organisation, access) VALUES ('$f_username', '$f_pass', '$f_org', '$f_access')";
  $execute = pg_query($pgconn, $sql);
  $m = 90;
}
pg_close($pgconn);
header("location: useradmin.php?m=$m");
?>

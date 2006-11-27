<?php

####################################
# SURFnet IDS                      #
# Version 1.03.02                  #
# 16-11-2006                       #
# Jan van Lith & Kees Trippelvitz  #
# Modified by Peter Arts           #
####################################

#############################################
# Changelog:
# 1.03.02 Removed and changed some stuff referring to the report table
# 1.03.01 Released as part of the 1.03 package
# 1.02.04 SQL injection fix
# 1.02.03 Added some more input checks
# 1.02.02 Removed old maillogging and email data
# 1.02.01 Initial release
#############################################

session_start();
header("Cache-control: private");

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';

# Checking if the user is logged in
if (!isset($_SESSION['s_admin'])) {
  $address = getaddress($web_port);
  header("location: ${address}login.php");
  exit;
}

$s_org = intval($_SESSION['s_org']);
$s_access = $_SESSION['s_access'];
$s_access_user = intval($s_access{2});

# Fetching POST data.
$f_username = stripinput(trim(pg_escape_string($_POST['f_username'])));
$f_org = intval($_POST['f_org']);
$f_pass = pg_escape_string(stripinput($_POST['f_pass']));
$f_confirm = pg_escape_string(stripinput($_POST['f_confirm']));
$f_access_user = intval($_POST['f_access_user']);
$f_access_search = intval($_POST['f_access_search']);
$f_access_sensor = intval($_POST['f_access_sensor']);
$f_access = $f_access_sensor . $f_access_search . $f_access_user;
$f_gpg = intval($_POST['f_gpg']);
$f_email = pg_escape_string(trim(stripinput($_POST['f_email'])));

### Password check
if (empty($f_pass) || empty($f_confirm)) { 
  $m = 20;
  $err = 1;
} elseif ($f_pass != $f_confirm) {
  $m = 21;
  $err = 1;
} elseif (empty($f_username)) {
  $m = 22;
  $err = 1;
} elseif (empty($f_org) || $f_org == "none") {
  $m = 23;
  $err = 1;
}

if ($s_access_user < 2) {
  $m = 90;
  $err = 1;
} elseif ($s_access_user == 2) {
  $f_org = $s_org;
}

$sql = "SELECT username FROM login WHERE username = '$f_username'";
$result_user = pg_query($pgconn, $sql);
$rows = pg_num_rows($result_user);
if ($rows == 1) {
  $m = 27;
  $err = 1;
}

if ($err != 1) {
  $sql = "INSERT INTO login (username, password, organisation, access, email, gpg) ";
  $sql .= "VALUES ('$f_username', '$f_pass', '$f_org', '$f_access', '$f_email', '$f_gpg')";
  $execute = pg_query($pgconn, $sql);
  $m = 1;
  if ($default_mail_sensor == 1) {
    $sql_getuid = "SELECT id FROM login WHERE username = '$f_username'";
    $result_getuid = pg_query($pgconn, $sql_getuid);
    $row_getuid = pg_fetch_assoc($result_getuid);
    $uid = $row_getuid['id'];
    if ($uid) {
      $title = "Hourly sensor status";
      $sql_report = "INSERT INTO report_content (user_id, title, template, active, frequency, interval, priority, subject) ";
      $sql_report .= "VALUES ($uid, '$title', 4, 't', 1, 0, 1, '$title')";
      $execute = pg_query($pgconn, $sql_report);
    }
  }
}
pg_close($pgconn);
header("location: useradmin.php?m=$m");
?>

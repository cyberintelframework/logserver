<?php

####################################
# SURFnet IDS                      #
# Version 1.04.01                  #
# 06-11-2006                       #
# Peter Arts                       #
####################################

#############################################
# Changelog:
# 1.04.01 Initial release
#############################################

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';

session_start();
header("Cache-control: private");

# Checking if the user is logged in
if (!isset($_SESSION['s_admin'])) {
  $address = getaddress($web_port);
  pg_close($pgconn);
  header("location: ${address}login.php");
  exit;
}

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_userid = intval($_SESSION['s_userid']);
$s_access = $_SESSION['s_access'];
$s_access_user = intval($s_access{2});
$err = 0;

// Make sure all access rights are correct
if (isset($_GET['userid'])) {
  $user_id = intval($_GET['userid']);
  if ($s_access_user < 1) {
    header("location: mailadmin.php?userid=$user_id&m=90");
    pg_close($pgconn);
    exit;
  } elseif ($s_access_user < 2) {
    $user_id = $s_userid;
  } elseif ($s_access_user < 9) {
    $sql_login = "SELECT * FROM login WHERE organisation = $s_org AND id = $user_id";
    $result_login = pg_query($pgconn, $sql_login);
    $numrows_login = pg_num_rows($result_login);
    if ($numrows_login == 0) {
      echo "<p style='color:red;'><b>You don't have sufficient rights to perform the requested action.</b></p>\n";
      footer();
      exit;
    } else {
      $user_id = intval($_GET['userid']);
    }
  } else {
    $user_id = intval($_GET['userid']);
  }
} else {
  $user_id = $s_userid;
}

$action = $_GET['a'];
$pattern = '/^(d|e|r)$/';
if (!preg_match($pattern, $action)) {
  $err = 1;
  $m = 44;
}

if ($err == 0) {
  $sql_mod = "UPDATE report_content ";
  if ($action == "d") {
    $sql_mod .= "SET active = 'f' ";
    $m = 9;
  } elseif ($action == "e") {
    $sql_mod .= "SET active = 't' ";
    $m = 10;
  } elseif ($action == "r") {
    $sql_mod .= "SET last_sent = NULL ";
    $m = 11;
  }
  $sql_mod .= "WHERE user_id = '$user_id'";

  $query = pg_query($sql_mod);
}

pg_close($pgconn);
header("location: mailadmin.php?userid=$user_id&m=$m");
?>

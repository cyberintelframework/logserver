<?php

####################################
# SURFnet IDS                      #
# Version 1.02.03                  #
# 31-07-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.02.03 Added a precautionary intval for $db_org
# 1.02.02 Added some more input checks
# 1.02.01 Initial release
#############################################

session_start();
header("Cache-control: private");

include 'include/config.inc.php';
include 'include/connect.inc.php';
include 'include/functions.inc.php';

$f_user = pg_escape_string($_POST['f_user']);
$f_pass = pg_escape_string($_POST['f_pass']);

$sql_user = "SELECT * FROM login WHERE username = '" .$f_user. "'";
$result_user = pg_query($pgconn, $sql_user);
$numrows_user = pg_num_rows($result_user);

if ($numrows_user > 0) {
  $row = pg_fetch_assoc($result_user);
  $id = $row['id'];
  $access = $row['access'];
  $pass = $row['password'];
  $db_org = intval($row['organisation']);
  if ($pass == $f_pass) {
    $sql_getorg = "SELECT organisation FROM organisations WHERE id = " . $db_org;
    $result_getorg = pg_query($pgconn, $sql_getorg);
    $db_org_name = pg_result($result_getorg, 0);
    if ($db_org_name == "ADMIN") {
      $_SESSION['s_admin'] = 1;
      $_SESSION['s_access'] = $access;
    }
    else {
      $_SESSION['s_admin'] = 0;
      $_SESSION['s_access'] = $access;
    }
    $_SESSION['s_org'] = $db_org;
    $_SESSION['s_user'] = $f_user;
    $_SESSION['s_userid'] = $id;

    $timestamp = time();
    $sql_lastlogin = "UPDATE login SET lastlogin = $timestamp WHERE username = '" .$f_user. "'";
    $result_lastlogin = pg_query($pgconn, $sql_lastlogin);

    header("location: index.php");
  }
  else {
    header("location: login.php");
  }
}
else {
  header("location: login.php");
}
pg_close($pgconn);
?>

<?php

####################################
# SURFnet IDS                      #
# Version 1.04.02                  #
# 11-12-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#########################################################################
# Changelog:
# 1.04.02 Added support for user agent checking
# 1.04.01 Released as 1.04.01
# 1.03.01 Released as part of the 1.03 package
# 1.02.08 $numrows_user == 1, intval() to $id and $db_org
# 1.02.07 Changed the url redirection when $_GET['url'] is present
# 1.02.06 Changed the location of the pg_close command
# 1.02.05 Added some more input checks
# 1.02.04 Server-client login handshake added
# 1.02.03 Added modifications for the org_id table + url querystring support
# 1.02.02 Added identifier column to organisations table
# 1.02.01 Initial release
#########################################################################

session_start();
header("Cache-control: private");

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';

$f_user = pg_escape_string(stripinput($_POST['f_user']));
$f_pass = $_POST['f_pass'];

$sql_user = "SELECT * FROM login WHERE username = '" .$f_user. "'";
$result_user = pg_query($pgconn, $sql_user);
$numrows_user = pg_num_rows($result_user);

if ($numrows_user == 1) {
  $row = pg_fetch_assoc($result_user);
  $id = $row['id'];
  $access = $row['access'];
  $pass = $row['password'];

  if ($login_method == 1) {
    $checkstring = $pass;
  } else {
    $serverhash = $row['serverhash'];
    $serverhash = md5($serverhash);
    $check = "$pass" . "$serverhash";
    $checkstring = md5($check);
  }

  $db_org = intval($row['organisation']);
  if ($checkstring == $f_pass) {
    $sql_getorg = "SELECT organisation FROM organisations WHERE id = " . $db_org;
    $result_getorg = pg_query($pgconn, $sql_getorg);
    $db_org_name = pg_result($result_getorg, 0);
    if ($db_org_name == "ADMIN") {
      $_SESSION['s_admin'] = 1;
      $_SESSION['s_access'] = $access;
    } else {
      $_SESSION['s_admin'] = 0;
      $_SESSION['s_access'] = $access;
    }
    $_SESSION['s_org'] = intval($db_org);
    $_SESSION['s_user'] = $f_user;
    $_SESSION['s_userid'] = intval($id);

    // Adding the session - IP pair to the sessions table
    $timestamp = time();
    $remoteip = pg_escape_string($_SERVER['REMOTE_ADDR']);
    $useragent = md5($_SERVER['HTTP_USER_AGENT']);
    $sid = pg_escape_string(session_id());

    $sql_session = "SELECT * FROM sessions WHERE ip = '$remoteip'";
    $result_session = pg_query($pgconn, $sql_session);
    $numrows_session = pg_num_rows($result_session);
    if ($numrows_session == 0) {
      $sql_ins_session = "INSERT INTO sessions (sid, ip, ts, username, useragent) VALUES ('$sid', '$remoteip', '$timestamp', '$id', '$useragent')";
      $result_ins_session = pg_query($sql_ins_session);
    } else {
      $sql_upd_session = "UPDATE sessions SET sid = '$sid', ts = '$timestamp', username = '$id', useragent = '$useragent' WHERE ip = '$remoteip'";
      $result_upd_session = pg_query($sql_upd_session);
    }

    // Cleaning up the sessions table
    $sql_session = "SELECT * FROM sessions";
    $result_session = pg_query($sql_session);
    while ($row = pg_fetch_assoc($result_session)) {
      $db_ts = $row['ts'];
      $db_id = $row['id'];
      $ts_check = $timestamp - $conf_session_timeout;
      if ($db_ts < $ts_check) {
        $sql_del_session = "DELETE FROM sessions WHERE id = '$db_id'";
        $result_del = pg_query($sql_del_session);
      }
    }

    if ($login_method == 1) {
      $sql_lastlogin = "UPDATE login SET lastlogin = $timestamp WHERE username = '" .$f_user. "'";
      $result_lastlogin = pg_query($pgconn, $sql_lastlogin);
    } else {
      $newserverhash = genpass();
      $sql_lastlogin = "UPDATE login SET lastlogin = $timestamp, serverhash = '$newserverhash' WHERE username = '" .$f_user. "'";
      $result_lastlogin = pg_query($pgconn, $sql_lastlogin);
    }
    if (isset($_GET['url'])) {
      $url = stripinput($_GET['url']);
      pg_close($pgconn);
      $address = getaddress($web_port);
      header("location: $address$url");
    } else {
      pg_close($pgconn);
      header("location: index.php");
    }
  } else {
    pg_close($pgconn);
    header("location: login.php?m=43");
  }
} else {
  pg_close($pgconn);
  header("location: login.php?m=43");
}
?>

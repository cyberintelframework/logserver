<?php

####################################
# SURFids 3.00                     #
# Changeset 004                    #
# 30-10-2009                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 004 Fixed login problem due to changes in extractvars
# 003 Added pageconfs to cookie
# 002 Removed URL redirect option
# 001 Admin users always have 999 access
#############################################

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';

# Retrieving posted variables from $_POST
$allowed_post = array(
                "strip_html_escape_user",
                "md5_pass",
                "strip_html_url"
);
$check = extractvars($_POST, $allowed_post);
#debug_input();

$f_user = $clean['user'];
$f_pass = $clean['pass'];

# Retrieving posted variables from $_GET
$allowed_get = array(
                "strip_html_url"
);
$check = extractvars($_GET, $allowed_get);
#debug_input();

$sql_user = "SELECT id, access, password, serverhash, organisation, d_plotter, d_plottype, d_utc FROM login WHERE username = '" .$f_user. "'";
$result_user = pg_query($pgconn, $sql_user);
$numrows_user = pg_num_rows($result_user);

# Checking if the user exists
if ($numrows_user == 1) {
  $row = pg_fetch_assoc($result_user);
  $id = $row['id'];
  $access = $row['access'];
  $pass = $row['password'];
  $hash = $row['serverhash'];

  # Checking which login method is configured
  if ($c_login_method == 1) {
    $checkstring = $pass;
  } else {
    $serverhash = $row['serverhash'];
    $serverhash = md5($serverhash);
    $check = "$pass" . "$serverhash";
    $checkstring = md5($check);
  }

  $db_org = intval($row['organisation']);
  # Checking if the supplied password was correct
  if ($checkstring == $f_pass) {
    $sql_getorg = "SELECT organisation FROM organisations WHERE id = " . $db_org;
    $result_getorg = pg_query($pgconn, $sql_getorg);
    $db_org_name = pg_result($result_getorg, 0);

    # Starting session and making sure a new SID is generated
    session_start();
    session_regenerate_id();
    header("Cache-control: private");

    $d_plotter = $row['d_plotter'];
    $d_plottype = $row['d_plottype'];
    $d_utc = $row['d_utc'];
    addcookie("int_dplotter", $d_plotter);
    addcookie("int_dplottype", $d_plottype);
    addcookie("int_dutc", $d_utc);

    # Getting pageconfs
    $sql_pc = "SELECT pageid, config FROM pageconf WHERE userid = '$id'";
    $result_pc = pg_query($pgconn, $sql_pc);
    while ($row_pc = pg_fetch_assoc($result_pc)) {
      $pid = $row_pc['pageid'];
      $config = $row_pc['config'];
      addcookie("pageconf[$pid]", $config);
    }

    if ($db_org_name == "ADMIN") {
      $_SESSION['s_admin'] = 1;
      $access = "999";
      $_SESSION['s_access'] = $access;
    } else {
      $_SESSION['s_admin'] = 0;
      $_SESSION['s_access'] = $access;
    }
    $_SESSION['s_org'] = intval($db_org);
    $_SESSION['s_user'] = $f_user;
    $_SESSION['s_userid'] = intval($id);
    $_SESSION['s_hash'] = $hash;

    # Adding the session - IP pair to the sessions table
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

    # Cleaning up the sessions table
    $sql_session = "SELECT * FROM sessions";
    $result_session = pg_query($sql_session);
    while ($row = pg_fetch_assoc($result_session)) {
      $db_ts = $row['ts'];
      $db_id = $row['id'];
      $ts_check = $timestamp - $c_session_timeout;
      if ($db_ts < $ts_check) {
        $sql_del_session = "DELETE FROM sessions WHERE id = '$db_id'";
        $result_del = pg_query($sql_del_session);
      }
    }

    # Generate a new serverhash and update it to the database
    $newserverhash = genpass();
    $sql_lastlogin = "UPDATE login SET lastlogin = $timestamp, serverhash = '$newserverhash' WHERE username = '" .$f_user. "'";
    $result_lastlogin = pg_query($pgconn, $sql_lastlogin);

    pg_close($pgconn);
    header("location: index.php");
  } else {
    pg_close($pgconn);
    header("location: login.php?int_m=125");
  }
} else {
  pg_close($pgconn);
  header("location: login.php?int_m=125");
}
?>

<?php

####################################
# SURFids 3.00                     #
# Changeset 002                    #
# 04-04-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 002 Added default UTC value
# 001 Initial version
#############################################

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';

# Starting the session
session_start();
header("Cache-control: private");

# Checking if the user is logged in
if (!isset($_SESSION['s_admin'])) {
  pg_close($pgconn);
  $address = getaddress();
  header("location: ${address}login.php");
  exit;
}

# Retrieving some session variables
$s_org = intval($_SESSION['s_org']);
$s_userid = intval($_SESSION['s_userid']);
$s_access = $_SESSION['s_access'];
$s_auser = intval($s_access{2});
$s_hash = md5($_SESSION['s_hash']);
$err = 0;

# Retrieving posted variables from $_GET
$allowed_get = array(
		"int_plotter",
		"int_plottype",
		"int_userid",
		"md5_hash",
		"int_my",
		"int_utc",
        "int_censor"
);
$check = extractvars($_GET, $allowed_get);
#debug_input();

# Checking if the logged in user actually requested this action.                                    
if ($clean['hash'] != $s_hash) {
  $err = 1;
  $m = 116;
}

if (isset($clean['plotter'])) {
  $d_plotter = $clean['plotter'];
} else {
  $err = 1;
  $m = 153;
}

if (isset($clean['plottype'])) {
  $d_plottype = $clean['plottype'];
} else {
  $err = 1;
  $m = 154;
}

if (isset($clean['utc'])) {
  $d_utc = $clean['utc'];
} else {
  $d_utc = 0;
}

if (isset($clean['censor'])) {
  $d_censor = $clean['censor'];
} else {
  $d_censor = 0;
}

if ($s_auser == 9) {
  if (isset($clean['userid'])) {
    $uid = $clean['userid'];
  } else {
    $err = 1;
    $m = 139;
  }
} elseif ($s_auser > 1) {
  $uid = $clean['userid'];
  $sql_chk = "SELECT id FROM login WHERE id = '$uid' AND organisation = '$s_org'";
  $result_chk = pg_query($pgconn, $sql_chk);
  $numr = pg_num_rows($result_chk);
  if ($numr == 0) {
    $err = 1;
    $m = 101;
  }
} elseif ($s_auser == 1) {
  $uid = $s_userid;
} else {
  $err = 1;
  $m = 101;
}

if ($err != 1) {
  $m = 3;
  $sql_save = "UPDATE login SET d_plotter = '$d_plotter', d_plottype = '$d_plottype', d_utc = '$d_utc', d_censor = '$d_censor' WHERE id = $uid";
  $debuginfo[] = $sql_save;
  $execute_save = pg_query($pgconn, $sql_save);

  if (isset($_COOKIE[SURFids])) {
    delcookie("int_dplotter");
    delcookie("int_dplottype");
    delcookie("int_dutc");
    delcookie("int_dcensor");
  }

  addcookie("int_dplotter", $d_plotter);
  addcookie("int_dplottype", $d_plottype);
  addcookie("int_dutc", $d_utc);
  addcookie("int_dcensor", $d_censor);
}

# Close connection and redirect
pg_close($pgconn);
#debug_sql();
if (isset($clean['my'])) {
  header("location: myaccount.php?int_m=$m");
  exit;
}
header("location: useradmin.php?int_m=$m");
?>

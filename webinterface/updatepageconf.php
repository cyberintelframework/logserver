<?php

####################################
# SURFids 2.10                     #
# Changeset 001                    #
# 03-06-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 001 version 2.10
#############################################

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';

session_start();
header("Cache-control: private");

if (!isset($_SESSION['s_admin'])) {
  pg_close($pgconn);
  $address = getaddress();
  header("location: ${address}login.php");
  exit;
}

$s_userid = intval($_SESSION['s_userid']);
$q_org = intval($_SESSION['q_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_user = intval($s_access{2});
$s_hash = md5($_SESSION['s_hash']);

$allowed_post = array(
                "mods",
		"md5_hash",
		"int_userid",
		"int_pageid"
);
$check = extractvars($_POST, $allowed_post);
debug_input();
$err = 0;

if (isset($clean['userid'])) {
  $userid = $clean['userid'];
  $redirectpage = 1;
} else {
  $userid = $s_userid;
  $redirectpage = 0;
}

# Checking if the logged in user actually requested this action.
if ($clean['hash'] != $s_hash) {
  $err = 1;
  $m = 116;
}

# Checking for config and creating config string
if (@is_array($tainted["mods"])) {
  foreach ($tainted["mods"] as $mod_id) {
    $config_id = intval($mod_id);
    $config .= $config_id .",";
  }
  $config = trim($config, ",");
} else {
  $err = 1;
  $m = 156;
}

# Checking for page ID
if (isset($clean['pageid'])) {
  $pageid = $clean['pageid'];
} else {
  $err = 1;
  $m = 157;
}

# Checking if user exists
if ($s_access_user == 9) {
  $sql_user = "SELECT id FROM login WHERE id = $userid";
} else {
  $sql_user = "SELECT id FROM login WHERE id = $userid AND organisation = $q_org";
}
$debuginfo[] = $sql_user;
$result_user = pg_query($pgconn, $sql_user);
$numrows_user = pg_num_rows($result_user);

# Checking if the user exists
if ($numrows_user == 0) {
  $err = 1;
  $m = 139;
}

# Checking if user has the correct access
if ($s_access_user < 1) {
  $err = 1;
  $m = 101;
}

if ($err == 0) {
  $sql_c = "SELECT userid FROM pageconf WHERE userid = '$userid' AND pageid = '$pageid'";
  $debuginfo[] = $sql_c;
  $query = pg_query($pgconn, $sql_c);
  $num = pg_num_rows($query);

  if ($num == 0) {
    $sql = "INSERT INTO pageconf (config, pageid, userid) VALUES ('$config', '$pageid', '$userid')";
    $debuginfo[] = $sql;
    $query = pg_query($pgconn, $sql);
  } else {
    $sql = "UPDATE pageconf SET config = '$config' WHERE userid = '$userid' AND pageid = '$pageid'";
    $debuginfo[] = $sql;
    $query = pg_query($pgconn, $sql);
  }

  if (isset($_COOKIE[SURFids])) {
    delcookie("pageconf[$pageid]", "FALSE");
  }

  addcookie("pageconf[$pageid]", $config);
}
# Close connection and redirect
pg_close($pgconn);
#debug_sql();

if ($redirectpage == 1) {
  header("location: useredit.php?int_userid=$userid&int_m=$m");
} else {
  header("location: myaccount.php?int_m=$m");
}
?>

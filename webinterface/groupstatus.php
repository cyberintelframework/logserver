<?php

####################################
# SURFnet IDS                      #
# Version 2.10.01                  #
# 06-11-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 2.10.01 Initial release
#############################################

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';
include '../include/variables.inc.php';
include "../lang/${c_language}.php";

# Starting the session
session_start();
header("Cache-control: private");

# Checking if the user is logged in
if (!isset($_SESSION['s_admin'])) {
  $address = getaddress();
  pg_close($pgconn);
  header("location: ${address}login.php");
  exit;
}

# Retrieving some session variables
$s_org = intval($_SESSION['s_org']);
$s_access = $_SESSION['s_access'];
$s_access_user = intval($s_access{2});
$s_hash = md5($_SESSION['s_hash']);

# Retrieving posted variables from $_POST
$allowed_get = array(
                "int_gid",
		"md5_hash",
		"int_app"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

# Checking if the logged in user actually requested this action.                                    
if ($clean['hash'] != $s_hash) {
  $err = 1;
  $m = 116;
}

if (isset($clean['gid'])) {
  $gid = $clean['gid'];
} else {
  $m = 117;
  $err = 1;
}

if (isset($clean['app'])) {
  $status = $clean['app'];
} else {
  $m = 149;
  $err = 1;
}

if ($s_access_user < 9) {
  $m = 101;
  $err = 1;
}

if ($err != 1) {
  $sql = "SELECT id FROM groups WHERE id = '$gid'";
  $debuginfo[] = $sql;
  $result_user = pg_query($pgconn, $sql);
  $rows = pg_num_rows($result_user);
  if ($rows == 0) {
    $m = 117;
    $err = 1;
  }
}

if ($err != 1) {
  $sql = "UPDATE groups SET approved = '$status' WHERE id = '$gid'";
  $debuginfo[] = $sql;
  $execute = pg_query($pgconn, $sql);
  $m = 3;

  if ($status == 0) { $message = "notice"; }
  elseif ($status == 1) { $message = "ok"; }
  elseif ($status == 2) { $message = "warning"; }
  echo "<div id='status$gid' class='$message'>" .$v_group_status_ar[$status]. "</div>\n";
}

# Close connection and redirect
pg_close($pgconn);
#debug_sql();
#header("location: groupadmin.php?int_m=$m");
?>

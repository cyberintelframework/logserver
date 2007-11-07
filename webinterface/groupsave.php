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
		"int_id",
                "strip_html_escape_name",
		"md5_hash"
);
$check = extractvars($_GET, $allowed_get);
#debug_input();

# Checking if the logged in user actually requested this action.                                    
if ($clean['hash'] != $s_hash) {
  $err = 1;
  $m = 116;
}

if (isset($clean['id'])) {
  $id = $clean['id'];
} else {
  $m = 117;
  $err = 1;
}

# Checking MD5sums
if (isset($clean['name'])) {
  $name = $clean['name'];
} else {
  $m = 145;
  $err = 1;
}

if ($err != 1) {
  $sql = "SELECT name FROM groups WHERE name = '$name'";
  $debuginfo[] = $sql;
  $result_user = pg_query($pgconn, $sql);
  $rows = pg_num_rows($result_user);
  if ($rows == 1) {
    $m = 145;
    $err = 1;
  }
}

if ($err != 1) {
  $sql = "UPDATE groups SET name = '$name' WHERE id = '$id'";
  $debuginfo[] = $sql;
  $execute = pg_query($pgconn, $sql);
  $m = 1;
}

# Close connection and redirect
pg_close($pgconn);

geterror($m);

#debug_sql();
#header("location: groupedit.php?int_m=$m&int_id=$id");
?>

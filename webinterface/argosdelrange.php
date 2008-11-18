<?php

####################################
# SURFids 2.00.04                  #
# Changeset 002                    #
# 24-09-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 002 Added hash check
# 001 Initial release
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
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_user = intval($s_access{2});
$s_hash = md5($_SESSION['s_hash']);
$err = 0;

# Retrieving posted variables from $_POST
$allowed_post = array(
                "int_rangeid",
		"md5_hash"
);
$check = extractvars($_POST, $allowed_post);
#debug_input();

# Checking if the logged in user actually requested this action.
if ($clean['hash'] != $s_hash) {
  $err = 1;
  $m = 116;
}

# Checking $_POST'ed variables
if (!isset($clean['rangeid']) ) {
  $m = 114;
  $err = 1;
} else {
  $rangeid = $clean['rangeid'];
}

if ($err == 0) {
  # No errors found, delete record
  $sql = "DELETE FROM argos_ranges WHERE id = '$rangeid'";
  $debuginfo[] = $sql;
  $query = pg_query($pgconn, $sql);

  $m = 2;
}

# Close connection and redirect
pg_close($pgconn);
#debug_sql();
header("location: argosconfig.php?int_m=$m");
?>

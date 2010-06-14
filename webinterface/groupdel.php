<?php

####################################
# SURFids 3.00                     #
# Changeset 001                    #
# 03-03-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 001 Initial release
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

# Retrieving posted variables from $_GET
$allowed_get = array(
                "int_gid",
                "md5_hash"
);
$check = extractvars($_GET, $allowed_get);
#debug_input();

# Checking if the logged in user actually requested this action.
if ($clean['hash'] != $s_hash) {
  $err = 1;
  $m = 116;
}

# Checking $_GET'ed variables
if (!isset($clean['gid']) ) {
  $m = 117;
  $err = 1;
} else {
  $gid = $clean['gid'];
}

# Checking access
if ($s_access_user < 2) {
  $m = 101;
  $err = 1;
} elseif ($s_access_user < 9) {
  $sql_check = "SELECT owner FROM groups WHERE id = $gid AND owner = '$s_org'";
  $debuginfo[] = $sql_check;
  $result_check = pg_query($pgconn, $sql_check);
  $numrows_check = pg_num_rows($result_check);
  if ($numrows_check == 0) {
    $m = 101;
    $err = 1;
  }
}

header("Content-type: application/xml");
echo '<?xml version="1.0" encoding="UTF-8"?>';

if ($err == 0) {
  $sql = "DELETE FROM groupmembers WHERE groupid = '$gid'";
  $debuginfo[] = $sql;
  $query = pg_query($pgconn, $sql);

  $sql = "DELETE FROM groups WHERE id = $gid";
  $debuginfo[] = $sql;
  $execute = pg_query($pgconn, $sql);
  
  $m = 2;

  echo "<result>";
    echo "<status>OK</status>";
    echo "<error>" .$v_errors[$m]. "</error>";
    echo "<data>";
      echo "<id>$gid</id>";
    echo "</data>";
  echo "</result>";
} else {
  echo "<result>";
    echo "<status>FAILED</status>";
    echo "<error>" .$v_errors[$m]. "</error>";
  echo "</result>";
}

# Close connection and redirect
pg_close($pgconn);
#debug_sql();
#header("location: groupadmin.php?int_m=$m");
?>

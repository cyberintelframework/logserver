<?php

####################################
# SURFids 3.00                     #
# Changeset 001                    #
# 18-11-2008                       #
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
                "strip_html_escape_name",
                "int_type",
                "md5_hash"
);
$check = extractvars($_GET, $allowed_get);
#debug_input();

# Checking if the logged in user actually requested this action.                                    
if ($clean['hash'] != $s_hash) {
  $err = 1;
  $m = 116;
}

# Checking MD5sums
if (isset($clean['name'])) {
  $name = $clean['name'];
} else {
  $m = 145;
  $err = 1;
}

if ($s_access_user < 2) {
  $m = 101;
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

header("Content-type: application/xml");
echo '<?xml version="1.0" encoding="UTF-8"?>';

if ($err != 1) {
  $sql = "INSERT INTO groups (name, owner) ";
  $sql .= "VALUES ('$name', '$s_org')";
  $debuginfo[] = $sql;
  $execute = pg_query($pgconn, $sql);
  $m = 1;

  $sql_owner = "SELECT organisation FROM organisations WHERE id = '$s_org'";
  $result_owner = pg_query($pgconn, $sql_owner);
  $row = pg_fetch_assoc($result_owner);
  $owner = $row['organisation'];

  $sql_owner = "SELECT id FROM groups WHERE name = '$name'";
  $result_owner = pg_query($pgconn, $sql_owner);
  $row = pg_fetch_assoc($result_owner);
  $gid = $row['id'];

  echo "<result>";
    echo "<status>OK</status>";
    echo "<error>" .$v_errors[$m]. "</error>";
    echo "<data>";
      echo "<gid>$gid</gid>";
      echo "<name>$name</name>";
      echo "<owner>$owner</owner>";
      echo "<members>0</members>";
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
?>

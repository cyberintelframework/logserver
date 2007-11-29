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
$c_debug_input = 1;
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
if (isset($clean['type'])) {
  $type = $clean['type'];
} else {
  if ($s_access_user == 9) {
    $type = 1;
  } else {
    $type = 0;
  }
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

if ($err != 1) {
  $sql = "INSERT INTO groups (name, type, owner) ";
  $sql .= "VALUES ('$name', '$type', '$s_org')";
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

  echo "<tr id='$gid'>\n";
    echo "<td>$name</td>\n";
    echo "<td>" .$v_group_type_ar[$type]. "</td>\n";
    echo "<td>$owner</td>\n";
    echo "<td>0</td>\n";
    echo "<td>";
      echo "[<a href='groupedit.php?int_gid=$gid'>" .$l['g_edit_l']. "</a>]\n";
      echo "[<a onclick=\"javascript: submitform('', 'groupdel.php?int_gid=$gid', 'd', '$gid', '" .$l['ga_confirmdel']. "');\">" .$l['g_delete_l']. "</a>]";
    echo "</td>\n";
  echo "</tr>\n";
} else {
  echo "ERROR\n";
  geterror($m, 1);
}

# Close connection and redirect
pg_close($pgconn);
$c_debug_sql = 1;
#debug_sql();
#header("location: groupadmin.php?int_m=$m");
?>

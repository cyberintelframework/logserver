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

# Retrieving posted variables from $_GET
$allowed_get = array(
		"int_gid",
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

if (isset($clean['gid'])) {
  $gid = $clean['gid'];
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

if ($s_access_user < 2) {
  $m = 101;
  $err = 1;
}

if ($err != 1) {
  $sql = "SELECT name FROM groups WHERE name = '$name' AND NOT id = '$gid'";
  $debuginfo[] = $sql;
  $result_user = pg_query($pgconn, $sql);
  $rows = pg_num_rows($result_user);
  if ($rows == 1) {
    $m = 145;
    $err = 1;
  }
}

if ($err != 1) {
  $sql = "SELECT organisation, type ";
  $sql .= " FROM groups, organisations WHERE groups.owner = organisations.id AND groups.id = '$gid'";
  $debuginfo[] = $sql;
  $result = pg_query($pgconn, $sql);
  $row = pg_fetch_assoc($result);
  $owner = $row['organisation'];
  $type = $row['type'];

  $sql = "UPDATE groups SET name = '$name' WHERE id = '$gid'";
  $debuginfo[] = $sql;
  $execute = pg_query($pgconn, $sql);
  $m = 1;

  echo "<tr id='grouprow'>\n";
    echo "<td><input type='text' name='strip_html_escape_name' value='$name' size='40' /></td>\n";
    echo "<td>" .$v_group_type_ar[$type]. "</td>\n";
    echo "<td>$owner</td>\n";
    echo "<td><input type='button' onclick=\"submitform('groupedit', 'groupsave.php', 'u', 'grouprow');\" class='button' value='" .$l['g_update']. "' /></td>\n";
  echo "</tr>\n";
} else {
  echo "ERROR\n";
  geterror($m, 1);
}

# Close connection and redirect
pg_close($pgconn);

#debug_sql();
#header("location: groupedit.php?int_m=$m&int_gid=$gid");
?>

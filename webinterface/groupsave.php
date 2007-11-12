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

# Retrieving posted variables from $_GET
$allowed_get = array(
		"int_gid",
                "strip_html_escape_name",
		"int_type",
		"int_detail",
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
  $sql = "SELECT type, detail, approved, organisation ";
  $sql .= " FROM groups, organisations WHERE groups.owner = organisations.id AND groups.id = '$gid'";
  $debuginfo[] = $sql;
  $result = pg_query($pgconn, $sql);
  $row = pg_fetch_assoc($result);
  $status = $row['approved'];
  $owner = $row['organisation'];

  if ($status == 0 || ($type == 1 && $status != 0)) {
    if (isset($clean['type'])) {
      $type = $clean['type'];
    } else {
      $type = $row['type'];
    }
    if (isset($clean['detail'])) {
      $detail = $clean['detail'];
    } else {
      $detail = $row['detail'];
    }
  }

  $sql = "UPDATE groups SET name = '$name', type = '$type', detail = '$detail' WHERE id = '$gid'";
  $debuginfo[] = $sql;
  $execute = pg_query($pgconn, $sql);
  $m = 1;

  if ($status == 0) { $message = "notice"; }
  elseif ($status == 1) { $message = "ok"; }
  elseif ($status == 2) { $message = "warning"; }

  echo "<tr id='grouprow'>\n";
    echo "<td><input type='text' name='strip_html_escape_name' value='$name' /></td>\n";
    if ($status == 0 || ($type == 1 && $status != 0)) {
      echo "<td>";
        echo "<select name='int_type'>\n";
          foreach ($v_group_type_ar as $key=>$val) {
            echo printOption($key, $val, $type);
          }
        echo "</select>\n";
      echo "</td>\n";
      echo "<td>";
        echo "<select name='int_detail'>\n";
          foreach ($v_group_detail_ar as $key=>$val) {
            echo printOption($key, $val, $detail);
          }
        echo "</select>\n";
      echo "</td>\n";
    } else {
      echo "<td>" .$v_group_type_ar[$type]. "</td>\n";
      echo "<td>" .$v_group_detail_ar[$detail]. "</td>\n";
    }
    echo "<td>$owner</td>\n";
    echo "<td><div class='$message'>" .$v_group_status_ar[$status]. "</div></td>\n";
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

<?php

####################################
# SURFids 3.00                     #
# Changeset 002                    #
# 03-06-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 002 Fixed an authorization issue
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
);
$check = extractvars($_POST, $allowed_post);
#debug_input();
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

if (@is_array($tainted["mods"])) {
  if ($tainted['mods'][0] != 0) {
    $ar_modsid = array();
    foreach ($tainted["mods"] as $mod_id) {
      $ar_modsid[intval($mod_id)] = intval($mod_id);
    }
  } else {
    $mod_id = 0;
    $ar_modsid[$mod_id] = $mod_id;
  }
} else {
  $mod_id = intval($tainted['mods']);
  $ar_modsid[$mod_id] = $mod_id;
}

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

if ($s_access_user < 1) {
  $err = 1;
  $m = 101;
}

if ($err == 0) {
  $sql_mods = "SELECT indexmod_id FROM indexmods_selected WHERE login_id = $userid";
  $debuginfo[] = $sql_mods;
  $result_mods = pg_query($pgconn, $sql_mods);
  while ($row_mods = pg_fetch_assoc($result_mods)) {
    $dbmod_id = $row_mods['indexmod_id'];
    $mods[$dbmod_id] = $dbmod_id;
  }
  if (count($mods) == 0) {
    $mods[0] = 0;
  }
  $delmods = array_diff($mods, $ar_modsid); 	
  $addmods = array_diff($ar_modsid, $mods); 	
  $m = "";
  if (!empty($delmods)) {
    foreach ($delmods as $key=>$modid) {
      $sql = "DELETE FROM indexmods_selected WHERE login_id = $userid AND indexmod_id = $modid";
      $debuginfo[] = $sql;
      $query = pg_query($pgconn, $sql);
    }
    $m = 3;
  }
  if (!empty($addmods)) {
    foreach ($addmods as $key=>$modid) {
      $sql = "INSERT INTO indexmods_selected (login_id, indexmod_id) VALUES ($userid, $modid)";
      $debuginfo[] = $sql;
      $query = pg_query($pgconn, $sql);
    }
    $m = 3;
  }
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

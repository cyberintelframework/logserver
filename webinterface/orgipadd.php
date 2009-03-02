<?php

####################################
# SURFids 2.10                     #
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
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_user = intval($s_access{2});
$s_hash = md5($_SESSION['s_hash']);

# Retrieving posted variables from $_POST
$allowed_post = array(
                "int_orgid",
                "ip_exclusion",
                "mac_exclusion",
                "md5_hash",
                "int_type"
);
$check = extractvars($_POST, $allowed_post);
#debug_input();

# Checking access
if ($s_access_user < 2) {
  $err = 1;
  $m = 101;
}

if (!isset($clean['type'])) {
  $err = 1;
  $m = 181;  
} else {
  $type = $clean['type'];
  if ($type == 0 || $type > 2) {
    $err = 1;
    $m = 181;  
  }
}

# Setting up organisation
if ($type == 1) {
  if ($s_admin == 1) {
    if (isset($clean['orgid'])) {
      $org = $clean['orgid'];
      if ($org == 0) {
        $err = 1;
        $m = 107;
      }
    } else {
      $org = $s_org;
    }
  } else {
    $org = $s_org;
  }
}

if ($clean['hash'] != $s_hash) {
  $err = 1;
  $m = 116;
}

if (!isset($clean['exclusion'])) {
  $err = 1;
  $m = 121;
} else {
  $exclusion = $clean['exclusion'];
}

if ($err != 1) {
  if ($type == 1) {
    $sql = "INSERT INTO org_excl (orgid, exclusion) ";
    $sql .= "VALUES ($org, '$exclusion')";
  } elseif ($type == 2) {
    $sql = "INSERT INTO arp_excl (mac) VALUES ('$exclusion')";
  }
  $debuginfo[] = $sql;
  $execute = pg_query($pgconn, $sql);
  $m = 1;
}

# Close connection and redirect
pg_close($pgconn);
#debug_sql();
header("location: orgipadmin.php?int_m=$m");
?>

<?php

####################################
# SURFnet IDS                      #
# Version 2.00.01                  #
# 12-09-2007                       #
# Kees Trippelvitz & Jan van Lith  #
####################################

#############################################
# Changelog:
# 2.00.01 version 2.00
# 1.04.10 Fixed network ranges regexp
# 1.04.09 Added hash check
# 1.04.08 Added pattern check for organisation IP ranges
# 1.04.07 Fixed bug with organisation existancy check. Case insensitive search.
# 1.04.06 Removed orgname check when type = ident
# 1.04.05 Added more checks on the ranges
# 1.04.04 Changed data input handling
# 1.04.03 Fixed a bug where it wouldn't save organisation changes
# 1.04.02 Added identifier type
# 1.04.01 pg_close() when not logged in
# 1.03.01 Released as part of the 1.03 package
# 1.02.03 Added some more input checks
# 1.02.02 Added identifier column to table.
# 1.02.01 Initial release
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

# Retrieving posted variables from $_GET
$allowed_get = array(
                "savetype",
		"int_orgid",
		"md5_hash"
);
$check = extractvars($_GET, $allowed_get);
#debug_input();

# Retrieving posted variables from $_POST
$allowed_post = array(
		"int_orgid",
		"strip_html_escape_ranges",
		"int_identtype",
		"strip_html_escape_orgident",
		"strip_html_escape_orgname",
		"md5_hash"
);
$check = extractvars($_POST, $allowed_post);
#debug_input();

if ($clean['hash'] != $s_hash) {
  $err = 1;
  $m = 89;
}

# Get the type of update
$type = $tainted['savetype'];
$pattern = '/^(org|ident|md5)$/';
if (!preg_match($pattern, $type)) {
  $err = 1;
  $m = 95;
}

# Checking access
if ($s_admin != 1) {
  $err = 1;
  $m = 91;
}

if ($type == "ident") {
  # Save type is ident (orgedit.php)
  $orgid = $clean['orgid'];
  $orgname = $clean['orgname'];
  $ranges = $clean['ranges'];
  $identtype = $clean['identtype'];
  $orgident = $clean['orgident'];

  if (empty($orgid)) {
    $err = 1;
    $m = 92;
  }

  if (empty($orgname)) {
    $err = 1;
    $m = 96;
  }

  $ranges = str_replace("\r", ";", $ranges);
  $ranges = str_replace("\n", ";", $ranges);
  $ranges = preg_replace("/;+/", ";", $ranges);
  $ranges = preg_replace("/ +;/", ";", $ranges);
  $ranges = preg_replace("/; +/", ";", $ranges);
  $ranges = preg_replace("/^ +/", "", $ranges);
  $ranges = preg_replace("/ +$/", "", $ranges);
  $ranges = preg_replace("/^;+/", "", $ranges);
  $ranges = preg_replace("/;+$/", "", $ranges);

  if ($ranges != "") {
    $ranges = rtrim($ranges, ";");
    $ranges .= ";";

    $pattern = '/^(([0-9]|[1-9][0-9]|1([0-9][0-9])|2([0-4][0-9]|5[0-5]))';
    $pattern .= '\.([0-9]|[1-9][0-9]|1([0-9][0-9])|2([0-4][0-9]|5[0-5]))';
    $pattern .= '\.([0-9]|[1-9][0-9]|1([0-9][0-9])|2([0-4][0-9]|5[0-5]))';
    $pattern .= '\.([0-9]|[1-9][0-9]|1([0-9][0-9])|2([0-4][0-9]|5[0-5]))(\/([1-9]|[1-2][0-9]|3[0-2]))?;{1})*$/';
    if (!preg_match($pattern, $ranges)) {
      $err = 1;
      $m = 90;
    }
  }
} elseif ($type == "org") {
  # Save type is org (orgadmin.php)
  if (isset($clean['orgname'])) {
    $orgname = $clean['orgname'];
    $orgcheck = strtoupper($orgname);

    $sql_org = "SELECT organisation FROM organisations WHERE upper(organisation) = '$orgcheck'";
    $debuginfo[] = $sql_org;
    $result_org = pg_query($pgconn, $sql_org);
    $rows = pg_num_rows($result_org);
    if ($rows > 0) {
      $m = 99;
      $err = 1;
    }
  } else {
    $m = 96;
    $err = 1;
  }
} elseif ($type == "md5") {
  # Save type is md5 (RIS)
  if (isset($clean['orgid'])) {
    $orgid = $clean['orgid'];
    $ident = genpass(32);
    $ident = md5($ident);
    $identtype = 1;
  } else {
    $err = 1;
    $m = 92;
  }
}

if ($err != 1) {
  if ($type == "ident") {

    $sql = "UPDATE organisations SET organisation = '" .$orgname. "', ranges = '" .$ranges. "' WHERE id = $orgid";
    $execute = pg_query($pgconn, $sql);

    if (isset($clean['orgident'])) {
      if ($identtype == 0) {
        $err = 1;
        $m = 98;
      }

      if (empty($orgident)) {
        $err = 1;
        $m = 97;
      }
      if ($err == 0) {
        $sql = "INSERT INTO org_id (identifier, orgid, type) VALUES ('$orgident', $orgid, $identtype)";
        $execute = pg_query($pgconn, $sql);
      } else {
        pg_close($pgconn);
        header("location: orgedit.php?int_orgid=$orgid&int_m=$m");
      }
    }
  } elseif ($type == "org") {
    $sql = "INSERT INTO organisations (organisation) VALUES ('$orgname')";
    $execute = pg_query($pgconn, $sql);
  } elseif ($type == "md5") {
    $sql = "INSERT INTO org_id (identifier, orgid, type) VALUES ('$ident', $orgid, $identtype)";
    $execute = pg_query($pgconn, $sql);
  }
  $m = 1;
}

# Close connection and redirect
pg_close($pgconn);
if ($type == "org") {
  header("location: orgadmin.php?int_m=$m");
} else {
  header("location: orgedit.php?int_orgid=$orgid&int_m=$m");
}
?>

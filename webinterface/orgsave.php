<?php

####################################
# SURFnet IDS                      #
# Version 1.04.04                  #
# 15-12-2006                       #
# Kees Trippelvitz                 #
####################################

#############################################
# Changelog:
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

session_start();
header("Cache-control: private");

if (!isset($_SESSION['s_admin'])) {
  pg_close($pgconn);
  $address = getaddress($web_port);
  header("location: ${address}login.php");
  exit;
}

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_user = intval($s_access{2});
$err = 0;

$allowed_get = array(
                "savetype",
		"int_orgid"
);
$check = extractvars($_GET, $allowed_get);
#debug_input();

$allowed_post = array(
		"int_orgid",
		"strip_html_escape_ranges",
		"int_identtype",
		"strip_html_escape_orgident",
		"strip_html_escape_orgname"
);
$check = extractvars($_POST, $allowed_post);
#debug_input();

# Get the type of update
$type = $tainted['savetype'];
$pattern = '/^(org|ident|md5)$/';
if (!preg_match($pattern, $type)) {
  $err = 1;
  $m = 95;
}

if ($s_admin != 1) {
  $err = 1;
  $m = 91;
}

if ($type == "ident") {
  $orgid = $clean['orgid'];
  $orgname = $clean['orgname'];
  $ranges = $clean['ranges'];
  $identtype = $clean['identtype'];
  $orgident = $clean['orgident'];

  $sql_org = "SELECT organisation FROM organisations WHERE organisation = '$orgname'";
  $debuginfo[] = $sql_org;
  $result_org = pg_query($pgconn, $sql_org);
  $rows = pg_num_rows($result_org);
  if ($rows > 0) {
    $m = 99;
    $err = 1;
  }

  if (empty($orgid)) {
    $err = 1;
    $m = 92;
  }

  if (empty($orgname)) {
    $err = 1;
    $m = 96;
  }

} elseif ($type == "org") {
  if (isset($clean['orgname'])) {
    $orgname = $clean['orgname'];

    $sql_org = "SELECT organisation FROM organisations WHERE organisation = '$orgname'";
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
  if (isset($clean['orgid'])) {
    $orgid = $clean['orgid'];
    $ident = genpass(16);
    $identtype = 1;
  } else {
    $err = 1;
    $m = 92;
  }
}

if ($err != 1) {
  if ($type == "ident") {
    $ranges = str_replace("\r", ";", $ranges);
    $ranges = str_replace("\n", "", $ranges);
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
    $sql = "INSERT INTO org_id (identifier, orgid, type) VALUES ('$ident', $orgid, $identftype)";
    $execute = pg_query($pgconn, $sql);
  }
  $m = 1;
}
pg_close($pgconn);
if ($type == "org") {
  header("location: orgadmin.php?int_m=$m");
} else {
  header("location: orgedit.php?int_orgid=$orgid&int_m=$m");
}
?>

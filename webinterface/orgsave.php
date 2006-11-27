<?php

####################################
# SURFnet IDS                      #
# Version 1.03.01                  #
# 10-10-2006                       #
# Kees Trippelvitz                 #
####################################

#############################################
# Changelog:
# 1.03.01 Released as part of the 1.03 package
# 1.02.03 Added some more input checks
# 1.02.02 Added identifier column to table.
# 1.02.01 Initial release
#############################################

session_start();
header("Cache-control: private");

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';

if (!isset($_SESSION['s_admin'])) {
  $address = getaddress($web_port);
  header("location: ${address}login.php");
  exit;
}

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_user = intval($s_access{2});
$err = 0;

# Get the type of update
$type = $_GET['type'];
$pattern = '/^(org|ident)$/';
if (!preg_match($pattern, $type)) {
  $err = 1;
  $m = 38;
}

if ($s_admin != 1) {
  $err = 1;
  $m = 91;
}

if ($type == "ident") {
  $orgid = intval($_POST['f_orgid']);
  $org = trim(pg_escape_string(stripinput($_POST['f_org'])));
  $ranges = stripinput($_POST['f_ranges']);

  if (empty($orgid)) {
    $err = 1;
    $m = 36;
  }

  if (empty($org)) {
    $err = 1;
    $m = 23;
  }
} elseif ($type == "org") {
  if (!empty($_POST['orgname'])) {
    $orgname = pg_escape_string($_POST['orgname']);
  } else {
    $err = 1;
    $m = 23;
  }
}

if ($err != 1) {
  if ($type == "ident") {
    $ranges = str_replace("\r", ";", $ranges);
    $ranges = str_replace("\n", "", $ranges);
    $sql = "UPDATE organisations SET organisation = '" .$org. "', ranges = '" .$ranges. "' WHERE id = $orgid";
    $execute = pg_query($pgconn, $sql);

    if (!empty($_POST['f_org_ident'])) {
      $ident = pg_escape_string($_POST['f_org_ident']);
      $sql = "INSERT INTO org_id (identifier, orgid) VALUES ('$ident', $orgid)";
      $execute = pg_query($pgconn, $sql);
    }
  } elseif ($type == "org") {
    $sql = "INSERT INTO organisations (organisation) VALUES ('$orgname')";
    $execute = pg_query($pgconn, $sql);
  }
  $m = 4;
}
pg_close($pgconn);
if ($type == "org") {
  header("location: orgadmin.php?m=$m");
} else {
  header("location: orgedit.php?orgid=$orgid&m=$m");
}
?>

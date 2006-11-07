<?php

####################################
# SURFnet IDS                      #
# Version 1.04.01                  #
# 06-11-2006                       #
# Kees Trippelvitz                 #
####################################

#############################################
# Changelog:
# 1.04.01 pg_close() when not logged in
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

# Get the type of update
$type = $_GET['type'];
$pattern = '/^(org|ident)$/';
if (!preg_match($pattern, $type)) {
  $err = 1;
  $m = 84;
}

if ($s_admin != 1) {
  $err = 1;
  $m = 81;
}

if ($type == "ident") {
  $orgid = intval($_POST['f_orgid']);
  $org = trim(pg_escape_string(stripinput($_POST['f_org'])));
  $ranges = stripinput($_POST['f_ranges']);

  if (empty($orgid)) {
    $err = 1;
    $m = 82;
  }

  if (empty($org)) {
    $err = 1;
    $m = 83;
  }
} elseif ($type == "org") {
  if (!empty($_POST['orgname'])) {
    $orgname = pg_escape_string($_POST['orgname']);
  } else {
    $err = 1;
    $m = 92;
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
  $m = 12;
}
pg_close($pgconn);
if ($type == "org") {
  header("location: orgadmin.php?m=$m");
} else {
  header("location: orgedit.php?orgid=$orgid&m=$m");
}
?>

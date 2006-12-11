<?php

####################################
# SURFnet IDS                      #
# Version 1.04.03                  #
# 07-12-2006                       #
# Kees Trippelvitz                 #
####################################

#############################################
# Changelog:
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

# Get the type of update
$type = $_GET['type'];
$pattern = '/^(org|ident|md5)$/';
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
  $ftype = intval($_POST['f_type']);
  $ident = pg_escape_string($_POST['f_org_ident']);

  if (empty($orgid)) {
    $err = 1;
    $m = 36;
  }

  if (empty($org)) {
    $err = 1;
    $m = 38;
  }

} elseif ($type == "org") {
  if (!empty($_POST['orgname'])) {
    $orgname = pg_escape_string($_POST['orgname']);
  } else {
    $err = 1;
    $m = 38;
  }
} elseif ($type == "md5") {
  if (isset($_GET['orgid'])) {
    $orgid = intval($_GET['orgid']);
    $ident = genpass(16);
    $ftype = 1;
  } else {
    $err = 1;
    $m = 36;
  }
}

if ($err != 1) {
  if ($type == "ident") {
    $ranges = str_replace("\r", ";", $ranges);
    $ranges = str_replace("\n", "", $ranges);
    $sql = "UPDATE organisations SET organisation = '" .$org. "', ranges = '" .$ranges. "' WHERE id = $orgid";
    $execute = pg_query($pgconn, $sql);

    if (!empty($_POST['f_org_ident'])) {
      if ($ftype == 0) {
        $err = 1;
        $m = 45;
      }

      if (empty($ident)) {
        $err = 1;
        $m = 46;
      }
      if ($err == 0) {
        $sql = "INSERT INTO org_id (identifier, orgid, type) VALUES ('$ident', $orgid, $ftype)";
        $execute = pg_query($pgconn, $sql);
      } else {
        pg_close($pgconn);
        header("location: orgedit.php?orgid=$orgid&m=$m");
      }
    }
  } elseif ($type == "org") {
    $sql = "INSERT INTO organisations (organisation) VALUES ('$orgname')";
    $execute = pg_query($pgconn, $sql);
  } elseif ($type == "md5") {
    $sql = "INSERT INTO org_id (identifier, orgid, type) VALUES ('$ident', $orgid, $ftype)";
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

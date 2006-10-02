<?php

####################################
# SURFnet IDS                      #
# Version 1.02.03                  #
# 28-07-2006                       #
# Kees Trippelvitz                 #
####################################

#############################################
# Changelog:
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
  $address = getaddress($web_port);
  header("location: ${address}login.php");
  exit;
}

$s_org = $_SESSION['s_org'];
$s_admin = $_SESSION['s_admin'];
$s_access = $_SESSION['s_access'];
$s_access_user = $s_access{2};
$err = 0;

$orgid = intval($_POST['f_orgid']);
$org = stripinput(trim(pg_escape_string($_POST['f_org'])));
$ranges = stripinput($_POST['f_ranges']);

if ($s_admin != 1) {
  $err = 1;
  $m = 81;
}

if (empty($orgid)) {
  $err = 1;
  $m = 82;
}

if (empty($org)) {
  $err = 1;
  $m = 83;
}

if ($err != 1) {
  $ranges = str_replace("\r", ";", $ranges);
  $ranges = str_replace("\n", "", $ranges);
  $sql = "UPDATE organisations SET organisation = '" .$org. "', ranges = '" .$ranges. "' WHERE id = $orgid";
  $execute = pg_query($pgconn, $sql);

  if (!empty($_POST['f_org_ident'])) {
    $ident = $_POST['f_org_ident'];
    $sql = "INSERT INTO org_id (identifier, orgid) VALUES ('$ident', $orgid)";
    $execute = pg_query($pgconn, $sql);
  }  
  $m = 12;
}
pg_close($pgconn);
header("location: orgedit.php?orgid=$orgid&m=$m");
?>

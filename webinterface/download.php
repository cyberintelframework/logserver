<?php

####################################
# SURFnet IDS                      #
# Version 1.04.01                  #
# 15-12-2006                       #
# Jan van Lith & Kees Trippelvitz  #
# Modified by Peter Arts           #
####################################

#############################################
# Changelog:
# 1.04.01 Initial release
#############################################

include '../include/config.inc.php';
include '../include/functions.inc.php';

$allowed_get = array(
                "md5_binname"
);
$check = extractvars($_GET, $allowed_get);

session_start();
header("Cache-control: private");
header('HTTP/1.1 200 OK');
header('Status: 200 OK');
header('Accept-Ranges: bytes');
header('Content-Transfer-Encoding: Binary');
header('Content-Type: application/force-download');
header("Content-Disposition: inline; filename=\"" .$clean['binname']. ".bin\"");

$s_admin = intval($_SESSION['s_admin']);

if ($s_admin != 1) {
  $absfile = $_SERVER['SCRIPT_NAME'];
  $file = basename($absfile);
  $address = getaddress();
  header("location: ${address}index.php");
  exit;
}

$fn = "$c_surfidsdir/binaries/" .$clean['binname'];
if (file_exists("$fn")) {
  readfile($fn);
}

?>
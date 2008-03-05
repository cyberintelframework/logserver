<?php

####################################
# SURFnet IDS 2.10.00              #
# Changeset 001                    #
# 03-03-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 001 Initial release
#############################################

include '../include/config.inc.php';
include '../include/functions.inc.php';

# Retrieving posted variables from $_GET
$allowed_get = array(
                "md5_binname"
);
$check = extractvars($_GET, $allowed_get);

# Starting the session and setting some headers
session_start();
header("Cache-control: private");
header('HTTP/1.1 200 OK');
header('Status: 200 OK');
header('Accept-Ranges: bytes');
header('Content-Transfer-Encoding: Binary');
header('Content-Type: application/force-download');
header("Content-Disposition: inline; filename=\"" .$clean['binname']. ".bin\"");

$s_admin = intval($_SESSION['s_admin']);

# Checking if downloads are enabled and if the user is an administrator
if ($s_admin != 1 && $c_download_binaries == 1) {
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

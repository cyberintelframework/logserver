<?php

####################################
# SURFnet IDS                      #
# Version 1.04.04                  #
# 05-01-2007                       #
# Hiroshi Suzuki of NTT-CERT       #
# Modified by Kees Trippelvitz     #
####################################

#############################################
# Changelog:
# 1.04.04 Added header content-disposition
# 1.04.03 Changed data input handling
# 1.04.02 Changed the database storage to base64
# 1.04.01 Initial release by Mr. Hiroshi Suzuki
#############################################

# Starting the session
session_start();

# Checking if the user is logged in
if (intval(@strlen($_SESSION["s_user"])) == 0) {
  # User not logged in
  header("Location: login.php");
  exit;
}

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';

# Retrieving some session variables
$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);

# Retrieving posted variables from $_GET
$allowed_get = array(
                "int_imgid",
		"type"
);
$check = extractvars($_GET, $allowed_get);

# Setting up the header info
header("Content-type: image/png");
header("Cache-control: no-cache");
header("Pragma: no-cache");
header("Content-disposition: attachment; filename=serverstats.jpg");

# Checking access
if ($s_admin != 1) {
  header("Location: index.php");
  exit;
}

# Checking $_GET'ed variables
if (isset($clean['imgid'])) {
  $imgid = $clean['imgid'];
  $err = 0;
} else {
  $err = 1;
}

$type = $tainted['type'];
$pattern = '/^(day|week|month|year)$/';
if (preg_match($pattern, $type) != 1) {
  $type = "day";
}

if ($err != 1) {
  $sql_check = "SELECT image FROM serverstats WHERE id = $imgid";
  $result_check = pg_query($pgconn, $sql_check);
  $row = pg_fetch_assoc($result_check);

  $encoded_image = $row['image'];
  $decoded_image = base64_decode($encoded_image);
  echo "$decoded_image";
}

pg_close($pgconn);
?>

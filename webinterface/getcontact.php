<?php

####################################
# SURFids 2.10                     #
# Changeset 001                    #
# 18-08-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 001 Initial release
#############################################

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';
include '../include/variables.inc.php';
include "../lang/${c_language}.php";

# Starting the session
session_start();
header("Cache-control: private");
header("Content-type: application/xml");
echo '<?xml version="1.0" encoding="UTF-8"?>';

# Checking if the user is logged in
if (!isset($_SESSION['s_admin'])) {
  $m = 100;
  echo "An error occurred: <br />\n";
  echo $v_errors[$m];

  pg_close($pgconn);
  exit;
}

# Retrieving some session variables
$s_org = intval($_SESSION['s_org']);
$s_access = $_SESSION['s_access'];
$s_access_user = intval($s_access{2});
$s_hash = md5($_SESSION['s_hash']);
$s_admin = $_SESSION['s_admin'];
$err = 0;

# Checking access
if ($s_admin != 1) {
  $err = 1;
  $m = 101;
}

$err = 0;

# Retrieving posted variables from $_GET
$allowed_get = array(
                "int_orgid",
	    	    "int_sid"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

if (isset($clean['orgid'])) {
  $orgid = $clean['orgid'];
} else {
  $err = 1;
  $m = 107;
}

if (isset($clean['sid'])) {
  $sid = $clean['sid'];
} else {
  $err = 1;
  $m = 110;
}

if ($err == 0) {
  $sql_sid = "SELECT keyname, vlanid, label FROM sensors WHERE id = '$sid'";
  $debuginfo[] = $sql_sid;
  $result_sid = pg_query($pgconn, $sql_sid);
  $row = pg_fetch_assoc($result_sid);
  $keyname = $row['keyname'];
  $vlanid = $row['vlanid'];
  $label = $row['label'];
  $sensor = sensorname($keyname, $vlanid, $label);

  $sql_email = "SELECT email FROM login WHERE organisation = '$orgid'";
  $debuginfo[] = $sql_email;
  $result_email = pg_query($pgconn, $sql_email);
  while ($row = pg_fetch_assoc($result_email)) {
    $email = $row['email'];
    echo "$email <br />\n";
  }
} else {
  echo "An error occurred: <br />\n";
  echo $v_errors[$m];

  pg_close($pgconn);
}

#pg_close($pgconn);
debug_sql();
?>

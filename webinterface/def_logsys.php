<?php

####################################
# SURFids 3.00                     #
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
  echo "<result>";
    echo "<status>FAILED</status>";
    echo "<error>" .$v_errors[$m]. "</error>";
  echo "</result>";

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
  $m = 101;
  echo "<result>";
    echo "<status>FAILED</status>";
    echo "<error>" .$v_errors[$m]. "</error>";
  echo "</result>";

  pg_close($pgconn);
  exit;
}

# Defaults
$err = 0;
$m = '';
$log = array();

# Retrieving posted variables from $_GET
$allowed_get = array(
                "strip_html_escape_error",
                "strip_html_escape_prefix",
                "strip_html_escape_dev",
                "int_sid",
                "int_level",
                "int_levelop",
                "int_prefixop",
                "int_devop",
                "int_sidop",
                "int_errorop"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

# Getting all operators
if (isset($clean['levelop']) && $clean['levelop'] != 1) {
  $levelop = $clean['levelop'];
  addcookie("int_levelop", $levelop);
  $m = 10;
} else {
  delcookie("int_levelop");
}
if (isset($clean['prefixop']) && $clean['prefixop'] != 1) {
  $prefixop = $clean['prefixop'];
  addcookie("int_prefixop", $prefixop);
  $m = 10;
} else {
  delcookie("int_prefixop");
}
if (isset($clean['devop']) && $clean['devop'] != 1) {
  $devop = $clean['devop'];
  addcookie("int_devop", $devop);
  $m = 10;
} else {
  delcookie("int_devop");
}
if (isset($clean['sidop']) && $clean['sidop'] != 1) {
  $sidop = $clean['sidop'];
  addcookie("int_sidop", $sidop);
  $m = 10;
} else {
  delcookie("int_sidop");
}
if (isset($clean['errorop']) && $clean['errorop'] != 1) {
  $errorop = $clean['errorop'];
  addcookie("int_errorop", $errorop);
  $m = 10;
} else {
  delcookie("int_errorop");
}

if (isset($clean['error']) && $clean['error'] != -1) {
  $error = $clean['error'];
  addcookie("strip_html_escape_error", $error);
  $m = 10;
} else {
  delcookie("strip_html_escape_error");
}

if (isset($clean['prefix']) && $clean['prefix'] != -1) {
  $prefix = $clean['prefix'];
  addcookie("strip_html_escape_prefix", $prefix);
  $m = 10;
} else {
  delcookie("strip_html_escape_prefix");
}

if (isset($clean['dev']) && $clean['dev'] != -1) {
  $dev = $clean['dev'];
  addcookie("strip_html_escape_dev", $dev);
  $m = 10;
} else {
  delcookie("strip_html_escape_dev");
}

if (isset($clean['sid']) && $clean['sid'] != -1) {
  $sid = $clean['sid'];
  addcookie("int_sid", $sid);
  $m = 10;
} else {
  delcookie("int_sid");
}

if (isset($clean['level']) && $clean['level'] != -1) {
  $level = $clean['level'];
  addcookie("int_level", $level);
  $m = 10;
} else {
  delcookie("int_level");
}

echo "<result>";
  echo "<status>OK</status>";
  echo "<error>" .$v_errors[$m]. "</error>";
  echo "<logs>";
  foreach ($log as $key => $val) {
    echo "<log>$val</log>";
  }
  echo "</logs>";
echo "</result>";

#pg_close($pgconn);
debug_sql();
?>

<?php

####################################
# SURFids 3.00                     #
# Changeset 001                    #
# 18-11-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 001 Initial release
#############################################

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';

# Starting the session
session_start();
header("Cache-control: private");

# Checking if the user is logged in
if (!isset($_SESSION['s_admin'])) {
  pg_close($pgconn);
  $address = getaddress();
  header("location: ${address}login.php");
  exit;
}

# Retrieving some session variables
$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_sensor = intval($s_access{0});

# Retrieving posted variables from $_GET
$allowed_get = array(
		"int_sid",
		"md5_hash"
);
$check = extractvars($_GET, $allowed_get);
#debug_input();

# Checking access
if ($s_admin != 1) {
  $m = 101;
  pg_close($pgconn);
  header("location: sensorstatus.php?int_m=" .$m);
  exit;
}

if (isset($clean['sid'])) {
  $sid = $clean['sid'];
} else {
  $m = 110;
  $err = 1;
}

if ($err == 0) {
  # Checking direction
  $sql_chk = "SELECT COUNT(id) as total FROM sensors WHERE id = '$sid'";
  $result_chk = pg_query($pgconn, $sql_chk);
  $row = pg_fetch_assoc($result_chk);
  $total = $row['total'];
  if ($total == 0) {
    $from = "deactivated_";
    $to = "";
    $selview = "";
  } else {
    $from = "";
    $to = "deactivated_";
    $selview = 9;
  }  

  if ($total == 0) {
    #     SENSORS      #
    ####################
    $sql_get = "SELECT * FROM " .$from. "sensors WHERE id = '$sid'";
    $debuginfo[] = $sql_get;
    $result_get = pg_query($pgconn, $sql_get);

    while ($row_get = pg_fetch_assoc($result_get)) {
      $keystring = "";
      $valstring = "";
      foreach ($row_get as $key => $val) {
        $keystring .= $key .", ";
        if ($val == "") {
          $valstring .= "NULL, ";
        } else {
          $valstring .= "'". $val . "', ";
        }
      }
      $keystring = rtrim($keystring);
      $valstring = rtrim($valstring);
      $keystring = rtrim($keystring, ",");
      $valstring = rtrim($valstring, ",");

      $sql_put = "INSERT INTO " .$to. "sensors ($keystring) VALUES ($valstring)";
      $debuginfo[] = $sql_put;
      $ec = pg_query($pgconn, $sql_put);
    }

    $sql_del = "DELETE FROM " .$from. "sensors WHERE id = '$sid'";
    $ec = pg_query($pgconn, $sql_del);

    #     ATTACKS      #
    ####################
    $sql_get = "SELECT * FROM " .$from. "attacks WHERE sensorid = '$sid'";
    $debuginfo[] = $sql_get;
    $result_get = pg_query($pgconn, $sql_get);

    while ($row_get = pg_fetch_assoc($result_get)) {
      $keystring = "";
      $valstring = "";
      foreach ($row_get as $key => $val) {
        $keystring .= $key .", ";
        if ($val == "") {
          $valstring .= "NULL, ";
        } else {
          $valstring .= "'". $val . "', ";
        }
      }
      $keystring = rtrim($keystring);
      $valstring = rtrim($valstring);
      $keystring = rtrim($keystring, ",");
      $valstring = rtrim($valstring, ",");

      $sql_put = "INSERT INTO " .$to. "attacks ($keystring) VALUES ($valstring)";
      $debuginfo[] = $sql_put;
      $ec = pg_query($pgconn, $sql_put);
    }

    $sql_del = "DELETE FROM " .$from. "attacks WHERE sensorid = '$sid'";
    $ec = pg_query($pgconn, $sql_del);

    #     DETAILS      #
    ####################
    $sql_get = "SELECT * FROM " .$from. "details WHERE sensorid = '$sid'";
    $debuginfo[] = $sql_get;
    $result_get = pg_query($pgconn, $sql_get);

    while ($row_get = pg_fetch_assoc($result_get)) {
      $keystring = "";
      $valstring = "";
      foreach ($row_get as $key => $val) {
        $keystring .= $key .", ";
        if ($val == "") {
          $valstring .= "NULL, ";
        } else {
          $valstring .= "'". $val . "', ";
        }
      }
      $keystring = rtrim($keystring);
      $valstring = rtrim($valstring);
      $keystring = rtrim($keystring, ",");
      $valstring = rtrim($valstring, ",");

      $sql_put = "INSERT INTO " .$to. "details ($keystring) VALUES ($valstring)";
      $debuginfo[] = $sql_put;
      $ec = pg_query($pgconn, $sql_put);
    }

    $sql_del = "DELETE FROM " .$from. "details WHERE sensorid = '$sid'";
    $ec = pg_query($pgconn, $sql_del);
  } else {
    #     DETAILS      #
    ####################
    $sql_get = "SELECT * FROM " .$from. "details WHERE sensorid = '$sid'";
    $debuginfo[] = $sql_get;
    $result_get = pg_query($pgconn, $sql_get);

    while ($row_get = pg_fetch_assoc($result_get)) {
      $keystring = "";
      $valstring = "";
      foreach ($row_get as $key => $val) {
        $keystring .= $key .", ";
        if ($val == "") {
          $valstring .= "NULL, ";
        } else {
          $valstring .= "'". $val . "', ";
        }
      }
      $keystring = rtrim($keystring);
      $valstring = rtrim($valstring);
      $keystring = rtrim($keystring, ",");
      $valstring = rtrim($valstring, ",");

      $sql_put = "INSERT INTO " .$to. "details ($keystring) VALUES ($valstring)";
      $debuginfo[] = $sql_put;
      $ec = pg_query($pgconn, $sql_put);
    }

    $sql_del = "DELETE FROM " .$from. "details WHERE sensorid = '$sid'";
    $ec = pg_query($pgconn, $sql_del);

    #     ATTACKS      #
    ####################
    $sql_get = "SELECT * FROM " .$from. "attacks WHERE sensorid = '$sid'";
    $debuginfo[] = $sql_get;
    $result_get = pg_query($pgconn, $sql_get);

    while ($row_get = pg_fetch_assoc($result_get)) {
      $keystring = "";
      $valstring = "";
      foreach ($row_get as $key => $val) {
        $keystring .= $key .", ";
        if ($val == "") {
          $valstring .= "NULL, ";
        } else {
          $valstring .= "'". $val . "', ";
        }
      }
      $keystring = rtrim($keystring);
      $valstring = rtrim($valstring);
      $keystring = rtrim($keystring, ",");
      $valstring = rtrim($valstring, ",");

      $sql_put = "INSERT INTO " .$to. "attacks ($keystring) VALUES ($valstring)";
      $debuginfo[] = $sql_put;
      $ec = pg_query($pgconn, $sql_put);
    }

    $sql_del = "DELETE FROM " .$from. "attacks WHERE sensorid = '$sid'";
    $ec = pg_query($pgconn, $sql_del);

    #     SENSORS      #
    ####################
    $sql_get = "SELECT * FROM " .$from. "sensors WHERE id = '$sid'";
    $debuginfo[] = $sql_get;
    $result_get = pg_query($pgconn, $sql_get);

    while ($row_get = pg_fetch_assoc($result_get)) {
      $keystring = "";
      $valstring = "";
      foreach ($row_get as $key => $val) {
        $keystring .= $key .", ";
        if ($val == "") {
          $valstring .= "NULL, ";
        } else {
          $valstring .= "'". $val . "', ";
        }
      }
      $keystring = rtrim($keystring);
      $valstring = rtrim($valstring);
      $keystring = rtrim($keystring, ",");
      $valstring = rtrim($valstring, ",");

      $sql_put = "INSERT INTO " .$to. "sensors ($keystring) VALUES ($valstring)";
      $debuginfo[] = $sql_put;
      $ec = pg_query($pgconn, $sql_put);
    }

    $sql_del = "DELETE FROM " .$from. "sensors WHERE id = '$sid'";
    $ec = pg_query($pgconn, $sql_del);
  }
}

# Close connection and redirect
pg_close($pgconn);
#$c_debug_sql = 1;
debug_sql();
header("location: sensorstatus.php?int_selview=$selview&int_m=$m");
?>

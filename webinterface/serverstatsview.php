<?php include("menu.php"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.05                  #
# 22-06-2007                       #
# Hiroshi Suzuki of NTT-CERT       #
# Modified by Kees Trippelvitz     #
####################################

#############################################
# Changelog:
# 1.04.05 Added support for multiple servers 
# 1.04.04 Changed data input handling
# 1.04.03 Changed debug stuff
# 1.04.02 Changed the database storage to base64 + pg_close
# 1.04.01 Initial release by Mr. Hiroshi Suzuki
#############################################

# Retrieving some session variables
$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);

# Checking access
if ($s_admin != 1) {
  pg_close($pgconn);
  header("Location: index.php");
  exit;
}

# Retrieving posted variables from $_GET
$allowed_get = array(
                "int_imgid"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

# Checking $_GET'ed variables
if (isset($clean['imgid'])) {
  $iid = $clean['imgid'];
  $err = 0;
} else {
  $err = 1;
  header("Location: index.php");
  exit;
}

$sql_server = "SELECT * FROM serverstats ";
$sql_server .= " WHERE label = (SELECT label FROM serverstats WHERE id = $iid) ";
$sql_server .= " AND type = (SELECT type FROM serverstats WHERE id = $iid) AND server = (SELECT server FROM serverstats WHERE id = $iid) ORDER BY id";
$result_server = pg_query($pgconn, $sql_server);
$debuginfo[] = $sql_server;

$checklabel = 0;

while ($row = pg_fetch_assoc($result_server)) {
  $imgid = $row['id'];
  $label = $row['label'];
  $type = $row['type'];
  $interval = $row['interval'];

  if ($checklabel == 0) {
    if ($type == "memory" || $type == "cpu") {
      echo "<h3>$type</h3>\n";
    } else {
      echo "<h3>$type - $label</h3>\n";
    }
    $checklabel = 1;
  }

  echo "<table>\n";
    echo "<tr>\n";
      echo "<td>\n";
        if ($interval == "day") {
          echo "Daily Graph (5 minute averages)<br />\n";
          echo "<img alt='$label Daily' src='showserver.php?int_imgid=$imgid' /><br />\n";
        } elseif ($interval == "week") {
          echo "Weekly Graph (30 minute averages)<br />\n";
          echo "<img alt='$label Weekly' src='showserver.php?int_imgid=$imgid' /><br />\n";
        } elseif ($interval == "month") {
          echo "Monthly Graph (2 hour averages)<br />\n";
          echo "<img alt='label Monthly' src='showserver.php?int_imgid=$imgid' /><br />\n";
        } elseif ($interval == "year") {
          echo "Yearly Graph (12 hour averages)<br />\n";
          echo "<img alt='$label Yearly' src='showserver.php?int_imgid=$imgid' /><br />\n";
          $labelcheck = 0;
        }
      echo "</td>\n";
    echo "</tr>\n";
  echo "</table>\n";
} 
pg_close($pgconn);
debug_sql();
?>
<?php footer(); ?>

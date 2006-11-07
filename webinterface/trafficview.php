<?php include("menu.php"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.01                  #
# 06-11-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.04.01 Rereleased as 1.04.01
# 1.02.03 Removed includes
# 1.02.02 Added some more input checks
# 1.02.01 Initial release
#############################################

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);

if (isset($_GET['label'])) {
  $label = pg_escape_string($_GET['label']);
  if ($s_admin == 1) {
    $sql_rrd = "SELECT id, orgid, type FROM rrd WHERE label = '$label'";
  } else {
    $sql_rrd = "SELECT id, orgid, type FROM rrd WHERE label = '$label' AND orgid = $s_org";
  }
  $result_rrd = pg_query($pgconn, $sql_rrd);

  echo "<h3>Traffic analysis for: $label</h3>";
  echo "<table>\n";
    echo "<tr>\n";
      echo "<td>\n";

      while ($row = pg_fetch_assoc($result_rrd)) {
        $imgid = $row['id'];
        $type = $row['type'];

        if ($type == "day") {
          echo "Daily Graph (5 minute averages)<br />\n";
          echo "<img alt='$sensor Daily' src='showtraffic.php?imgid=$imgid' /><br />\n";
        } elseif ($type == "week") {
          echo "Weekly Graph (30 minute averages)<br />\n";
          echo "<img alt='$sensor Weekly' src='showtraffic.php?imgid=$imgid' /><br />\n";
        } elseif ($type == "month") {
          echo "Monthly Graph (2 hour averages)<br />\n";
          echo "<img alt='$sensor Monthly' src='showtraffic.php?imgid=$imgid' /><br />\n";
        } elseif ($type == "year") {
          echo "Yearly Graph (12 hour averages)<br />\n";
          echo "<img alt='$sensor Yearly' src='showtraffic.php?imgid=$imgid' /><br />\n";
        }
      }

      echo "</td>\n";
    echo "</tr>\n";
  echo "</table>\n";
} else {
  echo "<h2>No sensor given.</h2>";
}
pg_close($pgconn);
?>
<?php footer(); ?>

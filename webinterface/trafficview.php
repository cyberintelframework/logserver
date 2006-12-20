<?php include("menu.php"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.03                  #
# 15-12-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.04.03 Changed data input handling
# 1.04.02 Added debug info
# 1.04.01 Rereleased as 1.04.01
# 1.03.01 Released as part of the 1.03 package
# 1.02.04 Storing images in the database
# 1.02.03 Removed includes
# 1.02.02 Added some more input checks
# 1.02.01 Initial release
#############################################

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);

$allowed_get = array(
                "int_imgid"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

if (isset($clean['imgid'])) {
  $imgid = $clean['imgid'];
  if ($s_admin == 1) {
    $sql_rrd = "SELECT id, orgid, type, label FROM rrd WHERE label = (SELECT label FROM rrd WHERE id = '$imgid') ORDER BY id";
  } else {
    $sql_rrd = "SELECT id, orgid, type, label FROM rrd WHERE label = (SELECT label FROM rrd WHERE id = '$imgid') AND orgid = $s_org ORDER BY id";
  }
  $debuginfo[] = $sql_rrd;
  $result_rrd = pg_query($pgconn, $sql_rrd);
  $row_rrd = pg_fetch_assoc($result_rrd);
  $label = $row_rrd['label'];
  pg_result_seek($result_rrd, 0);

  echo "<h3>Traffic analysis for: $label</h3>";
  echo "<table>\n";
    echo "<tr>\n";
      echo "<td>\n";

      while ($row = pg_fetch_assoc($result_rrd)) {
        $imgid = $row['id'];
        $type = $row['type'];

        if ($type == "day") {
          echo "Daily Graph (5 minute averages)<br />\n";
          echo "<img alt='$sensor Daily' src='showtraffic.php?int_imgid=$imgid' /><br />\n";
        } elseif ($type == "week") {
          echo "Weekly Graph (30 minute averages)<br />\n";
          echo "<img alt='$sensor Weekly' src='showtraffic.php?int_imgid=$imgid' /><br />\n";
        } elseif ($type == "month") {
          echo "Monthly Graph (2 hour averages)<br />\n";
          echo "<img alt='$sensor Monthly' src='showtraffic.php?int_imgid=$imgid' /><br />\n";
        } elseif ($type == "year") {
          echo "Yearly Graph (12 hour averages)<br />\n";
          echo "<img alt='$sensor Yearly' src='showtraffic.php?int_imgid=$imgid' /><br />\n";
        }
      }

      echo "</td>\n";
    echo "</tr>\n";
  echo "</table>\n";
} else {
  $m = geterror(92);
  echo $m;
}
pg_close($pgconn);
debug_sql();
?>
<?php footer(); ?>

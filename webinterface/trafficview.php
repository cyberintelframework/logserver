<?php $tab="2.4"; $pagetitle="Traffic - Detail"; include("menu.php"); contentHeader(); ?>
<?php

####################################
# SURFids 2.04                     #
# Changeset 001                    #
# 12-09-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 001 version 2.00
#############################################

# Retrieving posted variables from $_GET
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

  echo "<div class='left'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockHeader'>Traffic analysis for: $label</div>\n";
        echo "<div class='blockContent'>\n";
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
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</dataBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</left>
} else {
  # Showing info/error messages if any
  $m = 112;
  geterror($m);
}

pg_close($pgconn);
debug_sql();
?>
<?php footer(); ?>

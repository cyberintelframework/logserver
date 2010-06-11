<?php $tab="2.4"; $pagetitle="Traffic - Detail"; include("menu.php"); contentHeader(); ?>
<?php

####################################
# SURFids 3.00                     #
# Changeset 001                    #
# 18-11-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 001 Added language support
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
        echo "<div class='blockHeader'>" .$l['tv_header']. ": $label</div>\n";
        echo "<div class='blockContent'>\n";
          echo "<table>\n";
            echo "<tr>\n";
              echo "<td>\n";
                while ($row = pg_fetch_assoc($result_rrd)) {
                  $imgid = $row['id'];
                  $type = $row['type'];

                  if ($type == "day") {
                    echo $l['sv_dg']. "<br />\n";
                    echo "<img alt='$sensor " .$l['sv_daily']. "' src='showtraffic.php?int_imgid=$imgid' /><br />\n";
                  } elseif ($type == "week") {
                    echo $l['sv_wg']. "<br />\n";
                    echo "<img alt='$sensor " .$l['sv_weekly']. "' src='showtraffic.php?int_imgid=$imgid' /><br />\n";
                  } elseif ($type == "month") {
                    echo $l['sv_mg']. "<br />\n";
                    echo "<img alt='$sensor " .$l['sv_monthly']. "' src='showtraffic.php?int_imgid=$imgid' /><br />\n";
                  } elseif ($type == "year") {
                    echo $l['sv_yg']. "<br />\n";
                    echo "<img alt='$sensor " .$l['sv_yearly']. "' src='showtraffic.php?int_imgid=$imgid' /><br />\n";
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

debug_sql();
pg_close($pgconn);
?>
<?php footer(); ?>

<?php $tab="3.5"; $pagetitle="Malware Downloaded"; include("menu.php"); contentHeader(); ?>
<?php

####################################
# SURFids 3.00                     #
# Changeset 001                    #
# 03-03-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 001 Added language support
#############################################

if ($q_org != 0) {
  add_to_sql("sensors", "table");
  add_to_sql("sensors.organisation = $q_org", "where");
  add_to_sql("attacks.sensorid = sensors.id", "where");
}

### Checking for period.
add_to_sql("attacks", "table");
add_to_sql("attacks.id = details.attackid", "where");
add_to_sql("attacks.timestamp >= $from", "where");
add_to_sql("attacks.timestamp <= $to", "where");

echo "<div class='centerbig'>\n";
  echo "<div class='block'>\n";
    echo "<div class='dataBlock'>\n";
      echo "<div class='blockHeader'>" .$l['md_title']. "</div>\n";
      echo "<div class='blockContent'>\n";
        add_to_sql("details.text", "select");
        add_to_sql("COUNT(details.id) as total", "select");
        add_to_sql("details", "table");
        add_to_sql("attacks", "table");
        add_to_sql("attacks.severity = 32", "where");
        add_to_sql("attacks.id = details.attackid", "where");
        if ($q_org != 0) {
          add_to_sql("sensors", "table");
          add_to_sql("sensors.id = attacks.sensorid", "where");
          add_to_sql(gen_org_sql(), "where");
        }
        add_to_sql("details.type = 8", "where");
        add_to_sql("details.text", "group");
        add_to_sql("total DESC", "order");
        prepare_sql();

        $sql_down = "SELECT $sql_select ";
        $sql_down .= "FROM $sql_from ";
        $sql_down .= " $sql_where ";
        $sql_down .= " GROUP BY $sql_group ";
        $sql_down .= " ORDER BY $sql_order ";
        $debuginfo[] = $sql_down;
        $result_down = pg_query($pgconn, $sql_down);
        $numrows_down = pg_num_rows($result_down);

        if ($numrows_down > 0) {
          $sql_scanners = "SELECT * FROM scanners";
          $result_scanners = pg_query($pgconn, $sql_scanners);
          $numrows_scanners = pg_num_rows($result_scanners);
          $a = 0;
          while ($scanners = pg_fetch_assoc($result_scanners)) {
            $a++;
            $name = $scanners['name'];
            ##echo "<input type='button' class='button' id='scanner_$a' name='scanner_$a' value='$name' onclick='show_hide_column($a);' />\n";
          }
          pg_result_seek($result_scanners, 0);

          $virus_count_ar = array();
          echo "<table class='datatable'>\n";
            echo "<tr>\n";
              echo "<th width='5%'>" .$l['md_malware']. "</th>\n";
              while ($scanners = pg_fetch_assoc($result_scanners)) {
                $name = $scanners['name'];
                echo "<th width='15%'>$name</th>\n";
              }
              pg_result_seek($result_scanners, 0);
              echo "<th width='5%'>" .$l['md_stats']. "</th>\n";
            echo "</tr>\n";

            while ($row = pg_fetch_assoc($result_down)) {
              $malware = $row['text'];
              $malstring = substr($malware, 0, 4) . "..";
              $count = $row['total'];

              echo "<tr>\n";
                echo "<td>" .downlink("binaryhist.php?md5_binname=$malware", $malstring, $malware). "</td>\n";
                while ($scanners = pg_fetch_assoc($result_scanners)) {
                  $scanner_id = $scanners['id'];
                  $sql_virus = "SELECT DISTINCT stats_virus.name as virusname, binaries.timestamp FROM binaries, stats_virus, uniq_binaries ";
                  $sql_virus .= "WHERE binaries.scanner = $scanner_id ";
                  $sql_virus .= "AND uniq_binaries.id = binaries.bin AND uniq_binaries.name = '$malware' ";
                  $sql_virus .= "AND binaries.info = stats_virus.id ORDER BY binaries.timestamp DESC LIMIT 1";
                  $debuginfo[] = "$sql_virus";
                  $result_virus = pg_query($pgconn, $sql_virus);
                  $numrows_virus = pg_num_rows($result_virus);

                  if ($numrows_virus == 0) {
                    $virus = $l['md_notscanned'];
                  } else {
                    $virus = pg_result($result_virus, "virusname");
                  }

                  # Starting the count for the viri.
                  $virus_count_ar[$virus] = $virus_count_ar[$virus] + $count;

                  if ($virus == $l['md_notscanned']) {
                    $ignore[$scanner_id]++;
                    $virustext = "$virus";
                  } elseif ($virus == "Suspicious") {
                    $total[$scanner_id]++;
                    $virustext = "$virus";
                  } else {
                    $found[$scanner_id]++;
                    $total[$scanner_id]++;
                    if (strlen($virus) > 23) {
                      $virustext = substr($virus, 0, 20) ."...";
                      $virustext = "<font class='warning' " .printover($virus). ">$virustext</font>";
                    } else {
                      $virustext = "<font class='warning'>$virus</font>";
                    }
                  }

                  echo "<td>$virustext</td>\n";
                }
                echo "<td>" .downlink("logsearch.php?int_sev=32&int_org=$q_org&strip_html_escape_binname=$malware", $count). "</td>\n";
                pg_result_seek($result_scanners, 0);
              echo "</tr>\n";
            }
            echo "<tr>\n";
              echo "<th class='bottom'>total %</th>\n";
              while ($scanners = pg_fetch_assoc($result_scanners)) {
                $id = $scanners['id'];
                $name = $scanners['name'];

                if ($total[$id] == 0) {
                  echo "<th class='bottom'>0 " .$l['md_scanned']. "</th>\n";
                } else {
                  if (!$found[$id]) {
                    $found[$id] = 0;
                  }
                  $perc[$id] = floor($found[$id] / $total[$id] * 100);
                  echo "<th class='bottom'>$found[$id] / $total[$id] = $perc[$id] %</th>\n";
                }
              }
              echo "<th class='bottom'></th>\n";
            echo "</tr>\n";        
          echo "</table>\n";
        } else {
          echo "<span class='warning'>" .$l['g_nofound']. "</span>\n";
        }
      echo "</div>\n"; #</blockContent>
      echo "<div class='blockFooter'></div>\n";
    echo "</div>\n"; #</dataBlock>
  echo "</div>\n"; #</block>
echo "</div>\n"; #</center>

# Debug info
debug_sql();
pg_close($pgconn);
?>
<?php footer(); ?>

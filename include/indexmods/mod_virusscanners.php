<?php

####################################
# SURFids 3.00                     #
# Changeset 001                  #
# 15-02-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 001 Initial release
#############################################

$total = array();

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

echo "<div class='block'>\n";
  echo "<div class='dataBlock'>\n";
    echo "<div class='blockHeader'>" .$l['mod_virusscanners']. "</div>\n";
    echo "<div class='blockContent'>\n";
      add_to_sql("details.text", "select");
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
      prepare_sql();

      $sql_down = "SELECT $sql_select ";
      $sql_down .= "FROM $sql_from ";
      $sql_down .= " $sql_where ";
      $sql_down .= " GROUP BY $sql_group ";
      $debuginfo[] = $sql_down;
      $result_down = pg_query($pgconn, $sql_down);
      $numrows_down = pg_num_rows($result_down);

      if ($numrows_down > 0) {
        $sql_scanners = "SELECT name, version, id FROM scanners WHERE status = 1";
        $result_scanners = pg_query($pgconn, $sql_scanners);
        $numrows_scanners = pg_num_rows($result_scanners);

        $virus_count_ar = array();
        echo "<table class='datatable'>\n";
          echo "<tr>\n";
            echo "<th width='25%'>" .$l['mod_virusscan']. "</th>\n";
            echo "<th width='50%'>" .$l['mod_version']. "</th>\n";
            echo "<th width='25%'>" .$l['g_stats']. "</th>\n";
          echo "</tr>\n";
          while ($scanners = pg_fetch_assoc($result_scanners)) {
            $name = $scanners['name'];
            $version = $scanners['version'];
            $scanner_id = $scanners['id'];
            echo "<tr>\n";
              echo "<td width='15%'>$name</td>\n";
              echo "<td width='15%'>$version</td>\n";
                while ($row = pg_fetch_assoc($result_down)) {
                  $malware = $row['text'];
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
                  $virus_count_ar[$virus] = $virus_count_ar[$virus] + $count;

                  if ($virus == $l['md_notscanned']) {
                    $ignore[$scanner_id]++;
                  } elseif ($virus == "Suspicious") {
                    $total[$scanner_id]++;
                  } else {
                    $found[$scanner_id]++;
                    $total[$scanner_id]++;
                  }

                  if ($total[$scanner_id] != 0) {
                    if (!$found[$scanner_id]) {
                      $found[$scanner_id] = 0;
                    }
                    $perc[$scanner_id] = floor($found[$scanner_id] / $total[$scanner_id] * 100);
                  }
                }
              echo "<td>$found[$scanner_id] / $total[$scanner_id] = $perc[$scanner_id] %</td>\n";
            echo "</tr>\n";        
            pg_result_seek($result_down, 0);
          } 
        echo "</table>\n";
      } else {
        echo "<span class='warning'>" .$l['g_nofound']. "</span>\n";
      }
    echo "</div>\n"; #</blockContent>
    echo "<div class='blockFooter'></div>\n";
  echo "</div>\n"; #</dataBlock>
echo "</div>\n"; #</block>
reset_sql();
?>

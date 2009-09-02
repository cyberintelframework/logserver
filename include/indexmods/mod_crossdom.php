<?php

####################################
# SURFids 3.00                     #
# Changeset 002                    #
# 18-04-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 002 Added ARP exclusion stuff
# 001 Initial release
#############################################

$tsquery = "timestamp >= $from AND timestamp <= $to";

$sql_ranges = "SELECT ranges FROM organisations WHERE id = $q_org";
$debuginfo[] = $sql_ranges;
$result_ranges = pg_query($pgconn, $sql_ranges);
$row_rang = pg_fetch_assoc($result_ranges);

echo "<div class='block'>\n";
  echo "<div class='dataBlock'>\n";
    echo "<div class='blockHeader'>" .$l['lc_cross']. "</div>\n";
    echo "<div class='blockContent'>\n";
      if ($row_rang['ranges'] == "") {
        echo "<h3>" .$l['lc_noranges']. "</h3>\n";
        $err = 1;
      } else {
        echo "<table class='datatable'>\n";
          echo "<tr>\n";
              echo "<th width='300'><a href='logcheck.php?int_sort=1'>" .$l['lc_range']. "</a>&nbsp;</th>\n";
            echo "<th width='150'>" .$l['g_mal']. "</th>\n";
            echo "<th width='150'>" .$l['lc_uniqsource']. "</th>\n";
            echo "<th width='150'>" .$l['g_pos']. "</th>\n";
            echo "<th width='150'>" .$l['lc_uniqsource']. "</th>\n";
          echo "</tr>\n";

          ### Looping through organisation info retrieved by soap connection.
          $ranges_ar = explode(";", $row_rang['ranges']);
          if ($sort == 0) {
            sort($ranges_ar);
          } else {
            rsort($ranges_ar);
          }
          foreach ($ranges_ar as $range) {
            if (trim($range) != "") {
              add_to_sql("attacks", "table");
              add_to_sql("sensors", "table");
              add_to_sql("attacks.sensorid = sensors.id", "where");
              add_to_sql("attacks.source <<= '$range'", "where");
              add_to_sql("$tsquery", "where");

              # IP Exclusion stuff
              add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $q_org)", "where");
              # MAC Exclusion stuff
              add_to_sql("(attacks.src_mac IS NULL OR NOT attacks.src_mac IN (SELECT mac FROM arp_excl))", "where");

              prepare_sql();

              $sql_total1 = "SELECT COUNT(attacks.id) as total ";
              $sql_total1 .= " FROM $sql_from ";
              $sql_total1 .= " $sql_where AND attacks.severity = 1";

              $sql_uniq1 = "SELECT DISTINCT source ";
              $sql_uniq1 .= " FROM $sql_from ";
              $sql_uniq1 .= " $sql_where AND attacks.severity = 1";
    
              $sql_total0 = "SELECT COUNT(attacks.id) as total ";
              $sql_total0 .= " FROM $sql_from ";
              $sql_total0 .= " $sql_where AND attacks.severity = 0";

              $sql_uniq0 = "SELECT DISTINCT source ";
              $sql_uniq0 .= " FROM $sql_from ";
              $sql_uniq0 .= " $sql_where AND attacks.severity = 0";

              $debuginfo[] = $sql_total0;
              $debuginfo[] = $sql_total1;
              $debuginfo[] = $sql_uniq0;
              $debuginfo[] = $sql_uniq1;

              $result_total1 = pg_query($pgconn, $sql_total1);
              $row_total1 = pg_fetch_assoc($result_total1);
              $count_total1 = $row_total1['total'];

              $result_uniq1 = pg_query($pgconn, $sql_uniq1);
              $count_uniq1 = pg_num_rows($result_uniq1);
    
              $result_total0 = pg_query($pgconn, $sql_total0);
              $row_total0 = pg_fetch_assoc($result_total0);
              $count_total0 = $row_total0['total'];

              $result_uniq0 = pg_query($pgconn, $sql_uniq0);
              $count_uniq0 = pg_num_rows($result_uniq0);

              reset_sql();

              if ($count_total1 > 0 || $count_total0 > 0) {
                echo "<tr>\n";
                  echo "<td>$range</td>\n";
                  if ($count_total1 > 0 ) {
                    echo "<td>" .downlink("logsearch.php?inet_source=$range&amp;int_sev=1", nf($count_total1)). "</td>\n";
                    echo "<td><a href='loglist.php?inet_source=$range&int_sev=1'>" . nf($count_uniq1) . "</a></td>\n";
                  } else {
                    echo "<td>&nbsp;</td>\n";
                    echo "<td>&nbsp;</td>\n";
                  }
                  if ($count_total0 > 0) {
                    echo "<td>" .downlink("logsearch.php?inet_source=$range&amp;int_sev=0", nf($count_total0)). "</td>\n";
                    echo "<td><a href='loglist.php?inet_source=$range&int_sev=0'>" . nf($count_uniq0) . "</a></td>\n";
                  } else {
                    echo "<td>&nbsp;</td>\n";
                    echo "<td>&nbsp;</td>\n";
                  }
                echo "</tr>\n";
              }
            }
          }
        echo "</table>\n";
      }
    echo "</div>\n"; #</blockContent>
  echo "</div>\n"; #</dataBlock>
  echo "<div class='blockFooter'></div>\n";
echo "</div>\n"; #</block>

reset_sql();
?>

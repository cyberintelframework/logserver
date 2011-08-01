<?php

####################################
# SURFids 3.00                     #
# Changeset 002                    #
# 19-03-2010                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 002 Added country info
# 001 Initial release
#############################################

if ($s_access_search == 0) {
  geterror(101);
  exit;
}

if ($c_geoip_enable == 1) {
  include_once '../include/' .$c_geoip_module;
  $gi = geoip_open("../include/" .$c_geoip_data, GEOIP_STANDARD);
}

echo "<div class='block'>\n";
  echo "<div class='dataBlock'>\n";
    echo "<div class='blockHeader'>\n";
      echo "<div class='blockHeaderLeft'>" .$l['mo_top10']. " " .$l['me_malhosts']."</div>\n";
      echo "<div class='blockHeaderRight'></div>\n";
    echo "</div>\n"; #</blockHeader>
    echo "<div class='blockContent'>\n";
      $sql_count = "SELECT DISTINCT sub.furl, COUNT(sub.furl) as total FROM ";
      $sql_count .= " (SELECT split_part(details.text, '/', 3) as furl ";
      $sql_count .= " FROM details, attacks";
      if ($q_org != 0) {
        $sql_count .= ", sensors ";
      }
      $sql_count .= " WHERE NOT split_part(details.text, '/', 3) = '' ";
      $sql_count .= " AND attacks.timestamp >= $from AND attacks.timestamp <= $to ";
      if ($q_org != 0) {
        $sql_count .= " AND sensors.id = attacks.sensorid ";
        $sql_count .= " AND " .gen_org_sql(). " ";
      }
      $sql_count .= " AND type = 4 AND details.attackid = attacks.id AND attacks.severity = 16 AND ";
      $sql_count .= " NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $q_org)) as sub ";
      $sql_count .= " GROUP BY sub.furl ORDER BY total DESC ";
      $debuginfo[] = "$sql_count";
      $result_count = pg_query($pgconn, $sql_count);
      $numrows_count = pg_num_rows($result_count);

      if ($numrows_count > 0) {
        echo "<table class='datatable'>\n";
          echo "<tr>\n";
            echo "<th width='80%'>" .$l['g_host']. "</th>\n";
            echo "<th width='20%'>" .$l['g_stats']. "</th>\n";
          echo "</tr>\n";

          $total = 0;
          $pattern_before = '/.*\@/';
          $pattern_after = '/\:.*$/';
          while ($row = pg_fetch_assoc($result_count)) {
            $furl = $row['furl'];
            $host = preg_replace($pattern_before, "", $furl);
            $host = preg_replace($pattern_after, "", $host);
            $count = $row['total'];

            $new_results[$host] += $count;
          }
          if (count($new_results) > 0) {
            arsort($new_results, SORT_NUMERIC);
          }
          $i = 0;
          foreach ($new_results as $host => $count) {
            if ($i == 10) {
              break;
            }

            # GEO IP stuff
#            $record = geoip_record_by_addr($gi, $host);
#            $countrycode = strtolower($record->country_code);
#            $country = $record->country_name;
#            $cimg = "$c_surfidsdir/webinterface/images/worldflags/flag_" .$countrycode. ".gif";

            echo "<tr>\n";
              echo "<td>\n";
                printflagimg($host);
                echo "&nbsp;$host";
              echo "</td>\n";
              echo "<td>$count</td>\n";
            echo "</tr>\n";
            $total = $total + $count;
            $i++;
          }
          echo "<tr class='bottom'>\n";
            echo "<td>Total</td>\n";
            echo "<td>" .downlink("logsearch.php?int_sev=16", nf($total)). "</td>\n";
          echo "</tr>\n";
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

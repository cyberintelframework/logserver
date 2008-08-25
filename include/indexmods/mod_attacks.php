<?php

####################################
# SURFids 2.10.00                  #
# Changeset 003                    #
# 19-08-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 003 Fixed organisation stuff
# 002 Added ARP exclusion stuff
# 001 Initial release
#############################################

$tsquery = "timestamp >= $from AND timestamp <= $to";

add_to_sql("attacks", "table");
add_to_sql("$tsquery", "where");
if ($q_org != 0) {
  add_to_sql("sensors", "table");
  add_to_sql("sensors.id = attacks.sensorid", "where");
  add_to_sql(gen_org_sql(), "where");
}
add_to_sql("DISTINCT attacks.severity", "select");
add_to_sql("COUNT(attacks.severity) as total", "select");
add_to_sql("attacks.severity", "group");

# IP Exclusion stuff
add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $q_org)", "where");
# MAC Exclusion stuff
add_to_sql("(attacks.src_mac IS NULL OR NOT attacks.src_mac IN (SELECT mac FROM arp_excl))", "where");

prepare_sql();

$sql_severity = "SELECT $sql_select ";
$sql_severity .= " FROM $sql_from ";
$sql_severity .= " $sql_where ";
$sql_severity .= " GROUP BY $sql_group ";
$debuginfo[] = $sql_severity;
$result_severity = pg_query($pgconn, $sql_severity);
$num = pg_num_rows($result_severity);

echo "<div class='block'>\n";
  echo "<div class='dataBlock'>\n";
    echo "<div class='blockHeader'>" .$l['g_attacks']. "</div>\n";
    echo "<div class='blockContent'>\n";
      if ($num > 0) {
        echo "<table class='datatable'>\n";
          echo "<tr>\n";
            echo "<th width='80%'>" .$l['g_detconn']. "</td>\n";
            echo "<th width='20%'>" .$l['g_stats']. "</td>\n";
          echo "</tr>\n";

          $sql_type = "SELECT DISTINCT attacks.atype, COUNT(attacks.atype) as total ";
          $sql_type .= "FROM $sql_from ";
          $sql_type .= "$sql_where ";
          $sql_type .= " AND severity = 1 ";
          $sql_type .= "GROUP BY atype ";

          while($row = pg_fetch_assoc($result_severity)) {
            $severity = $row['severity'];
            $count = $row['total'];
            $description = $v_severity_ar[$severity];
            if ($severity == 0 || $severity == 16) {
              echo "<tr>\n";
                echo "<td>$description " .printhelp($severity). "</td>\n";
                echo "<td>" .downlink("logsearch.php?int_sev=$severity", nf($count)). "</td>\n";
              echo "</tr>\n";
            } elseif ($severity == 1) {
              echo "<tr>\n";
                echo "<td>$description " .printhelp($severity). "</td>\n";
                echo "<td>" .downlink("logsearch.php?int_sev=$severity", nf($count)). "</td>\n";
              echo "</tr>\n";

              $debuginfo[] = $sql_type;
              $result_type = pg_query($pgconn, $sql_type);

              while ($row_type = pg_fetch_assoc($result_type)) {
                $atype = $row_type['atype'];
                $total = $row_type['total'];
                $desc = $v_severity_atype_ar[$atype];
        
                echo "<tr>\n";
                  echo "<td class='indented'>$desc</td>\n";
                  echo "<td>" .downlink("logsearch.php?int_sev=$severity&int_sevtype=$atype", nf($total)). "</td>\n";
                echo "</tr>\n";
              }
            } elseif ($severity == 32) {
              echo "<tr>\n";
                echo "<td>$description " .printhelp($severity). "</td>\n";
                echo "<td>" .downlink("logsearch.php?int_sev=$severity", nf($count)). "</td>\n";
              echo "</tr>\n";
            } else {
              echo "<tr>\n";
                echo "<td>" .downlink("logsearch.php?int_sev=$severity", nf($count)). "</td>\n";
              echo "</tr>\n";
            }
          }
        echo "</table>\n";
      } else {
        echo "<font class='warning'>" .$l['g_nofound']. "</font>\n";
      }
    echo "</div>\n"; #</blockContent>
    echo "<div class='blockFooter'></div>\n";
  echo "</div>\n"; #</dataBlock>
echo "</div>\n"; #</block>

reset_sql();
?>

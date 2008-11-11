<?php

####################################
# SURFids 2.10                     #
# Changeset 001                    #
# 15-02-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 001 Initial release
#############################################

echo "<div class='block'>\n";
  echo "<div class='dataBlock'>\n";
    echo "<div class='blockHeader'>" .$l['in_ports']. "</div>\n";
    echo "<div class='blockContent'>\n";
      echo "<table class='datatable' width='100%'>\n";
        echo "<tr>\n";
          echo "<th width='40%'>" .$l['in_destports']. "</th>\n";
          echo "<th width='45%'>" .$l['in_desc']. "</th>\n";
          echo "<th width='100%'>" .$l['in_totalhits']. "</th>\n";
        echo "</tr>\n";

        $queryport = "attacks.sensorid = sensors.id ";
        $queryport .= "AND timestamp >= $from AND timestamp <= $to ";
        $queryport .= "AND NOT attacks.dport = 0 ";
        $queryport .= "AND NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $q_org) ";
        if ($q_org != 0) {
          $queryport .= "AND sensors.organisation = '$q_org' ";
        }
        $sql_port_countqry = "SELECT attacks.dport, count(attacks.dport) as total FROM attacks,sensors ";
        $sql_port_countqry .= "WHERE $queryport GROUP BY attacks.dport ORDER BY total DESC LIMIT 10 OFFSET 0";
        $result_portcountqry = pg_query($pgconn, $sql_port_countqry);
        while ($row = pg_fetch_assoc($result_portcountqry)) {
          echo "<tr>\n";
            echo "<td>$row[dport]</td>\n";
            echo "<td><a target='_blank' href='http://www.iss.net/security_center/advice/Exploits/Ports/$row[dport]'>".getPortDescr($row[dport])."</a></td>\n";
            echo "<td>" .downlink("logsearch.php?int_dport=$row[dport]", $row[total]). "</td>\n";
          echo "</tr>\n";
        }
      echo "</table>\n";
    echo "</div>\n"; #</blockContent>
    echo "<div class='blockFooter'></div>\n";
  echo "</div>\n"; #</block>
echo "</div>\n"; #</dataBlock>
reset_sql();
?>

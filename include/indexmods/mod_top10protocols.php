<?php
$tsquery = " timestamp >= $from AND timestamp <= $to";

$sql_topproto = "SELECT DISTINCT sub.proto, COUNT(sub.proto) as total FROM ";
  $sql_topproto .= "(SELECT split_part(details.text, '/', 1) as proto ";
  $sql_topproto .= "FROM details, attacks, sensors WHERE 1 = 1 ";
  if ($tsquery != "") {
    $sql_topproto .= " AND $tsquery ";
  }
  if ($q_org != 0 ) $sql_topproto .= " AND sensors.id = details.sensorid AND " .gen_org_sql();
  $sql_topproto .= " AND type = 4  AND details.attackid = attacks.id AND ";
  $sql_topproto .= "NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid=$q_org)) as sub ";
$sql_topproto .= "GROUP BY sub.proto ORDER BY total DESC LIMIT 10";
$debuginfo[] = $sql_topproto;


    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
      echo "<div class='blockHeader'>";
        if ($q_org != 0) echo "" .$l['ra_top']. " 10 " .$l['ra_proto_org']. "\n";
        else echo "" .$l['ra_top']. " 10 " .$l['ra_proto_all']. "\n";
        echo "</div>\n";
	echo "<div class='blockContent'>\n";
	
          $result_topproto = pg_query($pgconn, $sql_topproto);
        
          echo "<table class='datatable'>\n";

            echo "<tr>\n";
              echo "<th width='5%'>#</th>\n";
              echo "<th width='75%'>" .$l['ra_proto']. "</th>\n";
              echo "<th width='20%'>" .$l['ra_total']. "</th>\n";
            echo "</tr>\n";
            $i = 0;
            $grandtotal = 0;
            while ($row = pg_fetch_assoc($result_topproto)) {
              if ($i == $c_topprotocols) {
                break;
              }
              $tempproto = $row['proto'];
              $total = $row['total'];
              $proto = str_replace(":", "", $tempproto);
              $grandtotal = $grandtotal + $total;
              $proto_ar[$proto] = $total;
            }
            if ($proto_ar != "") {
              foreach ($proto_ar as $key => $val) {
                $i++;
                echo "<tr>\n";
                  echo "<td>$i</td>\n";
                  echo "<td>$key</td>\n";
                  $perc = round($val / $grandtotal * 100);
                  echo "<td>$val (${perc}%)</td>\n";
                echo "</tr>\n";
              }
            }
          echo "</table>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</dataBlock>

reset_sql();
?>

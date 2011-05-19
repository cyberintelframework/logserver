<?php

####################################
# SURFids 3.00                     #
# Changeset 001                    #
# 15-02-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 001 Initial release
#############################################

$tsquery = " timestamp >= $from AND timestamp <= $to";

$sql_topproto .= "SELECT DISTINCT split_part(details.text, '/', 1) as proto, COUNT(attacks.id) as total ";
if ($q_org != 0) {
  $sql_topproto .= "FROM details, attacks, sensors WHERE type = 4 AND sensors.id = details.sensorid AND " .gen_org_sql();
} else {
  $sql_topproto .= "FROM details, attacks WHERE type = 4 ";
}
$sql_topproto .= " AND details.attackid = attacks.id ";
#if ($q_org != 0 ) $sql_topproto .= " AND sensors.id = details.sensorid AND " .gen_org_sql();
if ($tsquery != "") {
  $sql_topproto .= " AND $tsquery ";
}
$sql_topproto .= " AND NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid=$q_org) ";
$sql_topproto .= "GROUP BY proto ORDER BY total DESC LIMIT 10";
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
          $prottotal = $row['total'];
          $proto = str_replace(":", "", $tempproto);
          $grandprottotal = $grandprottotal + $prottotal;
          $proto_ar[$proto] = $prottotal;
        }
        if ($proto_ar != "") {
          foreach ($proto_ar as $key => $val) {
            $i++;
            echo "<tr>\n";
              echo "<td>$i</td>\n";
              echo "<td>$key</td>\n";
              $protperc = round($val / $grandprottotal * 100);
              echo "<td>$val (${protperc}%)</td>\n";
            echo "</tr>\n";
          }
        }
      echo "</table>\n";
    echo "</div>\n"; #</blockContent>
    echo "<div class='blockFooter'></div>\n";
  echo "</div>\n"; #</dataBlock>
echo "</div>\n"; #</block>

reset_sql();
?>

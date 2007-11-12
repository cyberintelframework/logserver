<?php

$tsquery = " timestamp >= $from AND timestamp <= $to";

#########
# 3.01 Top sensors (all)
#########

add_to_sql("DISTINCT sensors.organisation", "select");
add_to_sql("sensors.keyname", "select");
add_to_sql("sensors.vlanid", "select");
add_to_sql("sensors.label", "select");
add_to_sql("sensors.id as sensorid", "select");
add_to_sql("COUNT(details.*) as total", "select");
add_to_sql("attacks", "table");
add_to_sql("details", "table");
add_to_sql("sensors", "table");
add_to_sql("details.type = 1", "where");
add_to_sql("sensors.id = attacks.sensorid", "where");
add_to_sql("attacks.id = details.attackid", "where");
add_to_sql("$tsquery", "where");
add_to_sql("sensors.keyname", "group");
add_to_sql("sensors.vlanid", "group");
add_to_sql("sensors.organisation", "group");
add_to_sql("sensors.id", "group");
add_to_sql("sensors.label", "group");
add_to_sql("total DESC LIMIT 10 OFFSET 0", "order");
# IP Exclusion stuff
add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl)", "where");

prepare_sql();
$sql_top = "SELECT $sql_select";
$sql_top .= " FROM $sql_from ";
$sql_top .= " $sql_where ";
$sql_top .= " GROUP BY $sql_group ORDER BY $sql_order";
$debuginfo[] = $sql_top;


add_to_sql(gen_org_sql(), "where");
prepare_sql();
$sql_top_org = "SELECT $sql_select";
$sql_top_org .= " FROM $sql_from ";
$sql_top_org .= " $sql_where ";
$sql_top_org .= " GROUP BY $sql_group ORDER BY $sql_order";
$debuginfo[] = $sql_top_org;

reset_sql();

$result_top = pg_query($pgconn, $sql_top);
$i=1;
$rank_ar = array();
while ($row = pg_fetch_assoc($result_top)) {
     $id = $row['sensorid'];
     $keyname = $row['keyname'];
     $vlanid = $row['vlanid'];
     $rank_ar["$keyname-$vlanid"] = $i;
     $i++;
}
$sql_qorg = "SELECT organisation FROM organisations WHERE id = $q_org";
$result_qorg = pg_query($pgconn, $sql_qorg);
$orgname = pg_result($result_qorg, 0);
$debuginfo[] = $sql_qorg;

echo "<div class='block'>\n";
    echo "<div class='dataBlock'>\n";
      echo "<div class='blockHeader'>\n";
        if ($q_org == 0) echo "<div class='blockHeaderLeft'>" .$l['ra_top']. " 10 " .$l['ra_sensors']. "</div>\n";
        else echo "<div class='blockHeaderLeft'>" .$l['ra_top']. " 10 " .$l['ra_sensorsof']. " $orgname</div>\n";
        echo "<div class='blockHeaderRight'>\n";
        echo "</div>\n"; 
      echo "</div>\n"; #</blockHeader>
          echo "<div class='blockContent'>\n";
            echo "<table class='datatable'>\n";
              echo "<tr>\n";
                echo "<th width='5%'>#</th>\n";
                echo "<th width='25%'>" .$l['ra_overallrank']. "</th>\n";
                echo "<th width='50%'>" .$l['g_sensor']. "</th>\n";
                echo "<th width='20%'>" .$l['ra_totalexpl']. "</th>\n";
              echo "</tr>\n";
              $i = 1;
              $result_top_org = pg_query($pgconn, $sql_top_org);
              while ($row_top_org = pg_fetch_assoc($result_top_org)) {
                $db_org = $row_top_org['organisation'];
		$sql_getorg = "SELECT organisation FROM organisations WHERE id = $db_org";
                $result_getorg = pg_query($pgconn, $sql_getorg);
		$db_org_name = pg_result($result_getorg, 0);
                $debuginfo[] = $sql_getorg;

                echo "<tr>\n";
                  echo "<td>$i.</td>\n";
                  $id = $row_top_org['sensorid'];
                  $keyname = $row_top_org['keyname'];
                  $vlanid = $row_top_org['vlanid'];
                  $sensor = sensorname($keyname, $vlanid);
                  $total = $row_top_org['total'];
                  $label = $row_top_org['label'];
                  if ($label != "") {
                    $str = $label;
                  } else {
                    $str = $sensor;
                  }
                  $rank_all = $rank_ar["$keyname-$vlanid"];

                  echo "<td># $rank_all</td>\n";
                  echo "<td>$str</td>\n";
                  echo "<td>" .downlink("logsearch.php?sensorid[]=$id&int_sev=1", nf($total)). "</td>\n";
                echo "</tr>\n";
                $i++;
              }
            echo "</table>\n";
          echo "</div>\n"; #</blockContent>
          echo "<div class='blockFooter'></div>\n";
        echo "</div>\n"; #</dataBlock>
      echo "</div>\n"; #</block>

reset_sql();
?>

<?php

####################################
# SURFids 3.00                     #
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
add_to_sql("ssh_version", "table");
add_to_sql("uniq_sshversion", "table");
add_to_sql("$tsquery", "where");
add_to_Sql("uniq_sshversion.id = ssh_version.version", "where");
if ($q_org != 0) {
  add_to_sql("sensors", "table");
  add_to_sql("sensors.id = attacks.sensorid", "where");
  add_to_sql(gen_org_sql(), "where");
}
add_to_sql("DISTINCT ssh_version.version as versionid", "select");
add_to_sql("uniq_sshversion.version", "select");
add_to_sql("COUNT(ssh_version.attackid) as total", "select");
add_to_sql("versionid", "group");
add_to_sql("uniq_sshversion.version", "group");
add_to_sql("ssh_version.attackid = attacks.id", "where");

# IP Exclusion stuff
add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $q_org)", "where");
# MAC Exclusion stuff
add_to_sql("(attacks.src_mac IS NULL OR NOT attacks.src_mac IN (SELECT mac FROM arp_excl))", "where");

prepare_sql();

$sql_severity = "SELECT $sql_select ";
$sql_severity .= " FROM $sql_from ";
$sql_severity .= " $sql_where ";
$sql_severity .= " GROUP BY $sql_group ";
$sql_severity .= " ORDER BY total DESC ";
$debuginfo[] = $sql_severity;
$result_severity = pg_query($pgconn, $sql_severity);
$num = pg_num_rows($result_severity);

echo "<div class='block'>\n";
  echo "<div class='dataBlock'>\n";
    echo "<div class='blockHeader'>" .$l['mod_sshversion']. "</div>\n";
    echo "<div class='blockContent'>\n";
      if ($num > 0) {
        echo "<table class='datatable'>\n";
          echo "<tr>\n";
            echo "<th width='80%'>" .$l['mod_version']. "</td>\n";
            echo "<th width='20%'>" .$l['g_stats']. "</td>\n";
          echo "</tr>\n";

          while($row = pg_fetch_assoc($result_severity)) {
            $versionid = $row['versionid'];
            $version = pg_escape_string(htmlentities(strip_tags($row['version'])));
            $count = $row['total'];
            echo "<tr>\n";
              echo "<td>$version</td>\n";
              echo "<td><a href='logsearch.php?int_sshversionid=$versionid'>$count</a></td>\n";
            echo "</tr>\n";
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

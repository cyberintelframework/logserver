<?php $tab="3.1"; $pagetitle="Attacks"; include("menu.php"); contentHeader(); ?>
<?php

####################################
# SURFids 2.04                     #
# Changeset 001                    #
# 06-09-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################
# Contributors:                    #
# Peter Arts                       #
####################################

#############################################
# Changelog:
# 001 version 2.00
#############################################

# Unsetting the total search result count if it is set
if (isset($_SESSION['s_total_search_records'])) {
  unset($_SESSION['s_total_search_records']);
}
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
add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $s_org)", "where");

prepare_sql();

$sql_severity = "SELECT $sql_select ";
$sql_severity .= " FROM $sql_from ";
$sql_severity .= " $sql_where ";
$sql_severity .= " GROUP BY $sql_group ";
$debuginfo[] = $sql_severity;
$result_severity = pg_query($pgconn, $sql_severity);
$num = pg_num_rows($result_severity);

echo "<div class='left'>\n";
  echo "<div class='block'>\n";
    echo "<div class='dataBlock'>\n";
      echo "<div class='blockHeader'>Attacks</div>\n";
      echo "<div class='blockContent'>\n";
        if ($num > 0) {
          echo "<table class='datatable'>\n";
            echo "<tr>\n";
              echo "<th width='80%'>Detected connections</td>\n";
              echo "<th width='20%'>Statistics</td>\n";
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
          echo "<font class='warning'>No records found!</font>\n";
        }
      echo "</div>\n"; #</blockContent>
      echo "<div class='blockFooter'></div>\n";
    echo "</div>\n"; #</dataBlock>
  echo "</div>\n"; #</block>
echo "</div>\n"; #</left>

pg_close($pgconn);
debug_sql();
?>
<?php footer(); ?>

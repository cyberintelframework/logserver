<?php $tab="3.3"; $pagetitle="Malware offered"; include("menu.php"); contentHeader(); ?>
<?php

####################################
# SURFids 3.00                     #
# Changeset 001                    #
# 18-11-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 001 Added language support
#############################################

# Retrieving posted variables from $_GET
$allowed_get = array(
                "show"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

if (isset($tainted['show'])) {
  $show = $tainted['show'];
  $pattern = '/^(top|all)$/';
  if (!preg_match($pattern, $show)) {
    $show = "top";
  } else {
    $show = $tainted['show'];
  }
} else {
  $show = "top";
}
if ($show == 'all') $showtext = $l['g_all'];
if ($show == 'top') $showtext = $l['mo_top10'];

echo "<input type='hidden' name='show' value='$show' class='pers' />\n";
echo "<div class='left'>\n";
  echo "<div class='block'>\n";
    echo "<div class='dataBlock'>\n";
      echo "<div class='blockHeader'>\n";
        echo "<div class='blockHeaderLeft'>" .$l['mo_offered']. " - $showtext</div>\n";
        echo "<div class='blockHeaderRight'>\n";
          echo "<div class='searchnav'>\n";
            if ($show != "all") {
              echo "<a href='maloffered.php?show=all'>&nbsp;" .$l['ls_all']. "&nbsp;</a>\n";
            } else {
              echo "<a href='maloffered.php?show=top'>&nbsp;" .$l['mo_top10']. "&nbsp;</a>\n";
            }
          echo "</div>\n"; 
        echo "</div>\n"; 
      echo "</div>\n"; #</blockHeader>
      echo "<div class='blockContent'>\n";
        if ($err != 1) {
          $sql_count = "SELECT DISTINCT sub.file, COUNT(sub.file) as total FROM ";
          $sql_count .= " (SELECT split_part(details.text, '/', 4) as file ";
          $sql_count .= " FROM details, attacks";
          if ($q_org != 0) {
            $sql_count .= ", sensors ";
          }
          $sql_count .= " WHERE NOT split_part(details.text, '/', 4) = '' ";
          $sql_count .= " AND attacks.timestamp >= $from AND attacks.timestamp <= $to ";
          if ($q_org != 0) {
            $sql_count .= " AND sensors.id = attacks.sensorid ";
            $sql_count .= " AND " .gen_org_sql(). " ";
          }
          $sql_count .= " AND type = 4 AND details.attackid = attacks.id AND attacks.severity = 16 AND ";
          $sql_count .= " NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $q_org)) as sub ";
          $sql_count .= " GROUP BY sub.file ORDER BY total DESC ";
          if ($show == "top") {
            $sql_count .= " LIMIT 10 ";
          }
          $debuginfo[] = "$sql_count";
          $result_count = pg_query($pgconn, $sql_count);
          $numrows_count = pg_num_rows($result_count);

          if ($numrows_count > 0) {
            echo "<table class='datatable'>\n";
              echo "<tr>\n";
                echo "<th width='80%'>" .$l['ls_filename']. "</th>\n";
                echo "<th width='20%'>" .$l['g_stats']. "</th>\n";
              echo "</tr>\n";

              $total = 0;
              while ($row = pg_fetch_assoc($result_count)) {
                $file = $row['file'];
                $count = $row['total'];
                echo "<tr>\n";
                  echo "<td>$file</td>\n";
                  echo "<td>" .downlink("logsearch.php?int_sev=16&strip_html_escape_filename=$file", nf($count)). "</td>\n";
                echo "</tr>\n";
                $total = $total + $count;
              }
              echo "<tr class='bottom'>\n";
                echo "<td>Total</td>\n";
                echo "<td>" .downlink("logsearch.php?int_sev=16", nf($total)). "</td>\n";
              echo "</tr>\n";
            echo "</table>\n";
          } else {
            echo "<span class='warning'>" .$l['g_nofound']. "</span>\n";
          }
        }
      echo "</div>\n"; #</blockContent>
      echo "<div class='blockFooter'></div>\n";
    echo "</div>\n"; #</dataBlock>
  echo "</div>\n"; #</block>
echo "</div>\n"; #</left>

# Debug info
debug_sql();
?>
<?php footer(); ?>

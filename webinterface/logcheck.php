<?php $tab="2.2"; $pagetitle="Cross Domain"; include("menu.php"); contentHeader(); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 2.10.01                  #
# 24-10-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################
# Contributors:                    #
# Peter Arts                       #
####################################

#############################################
# Changelog:
# 2.10.01 Added language support
# 2.00.01 version 2.00
# 1.04.10 Added yearly option 
# 1.04.09 Added IP exclusions stuff
# 1.04.08 Set default back to weekly  
# 1.04.07 Added possible attacks check 
# 1.04.06 wrong amount of attacks bug fixed
# 1.04.05 add_to_sql()
# 1.04.04 Changed some sql stuff
# 1.04.03 Changed data input handling
# 1.04.02 Changed debug stuff
# 1.04.01 Code layout
# 1.03.01 Released as part of the 1.03 package
# 1.02.08 Added intval() to session variables + pattern matching on $b + intval() for $month and $day
# 1.02.07 Added some more input checks and removed includes
# 1.02.06 Removed intval from date browsing
# 1.02.05 Minor bugfixes and code cleaning
# 1.02.04 Enhanced debugging
# 1.02.03 Added number formatting
# 1.02.02 Initial release
#############################################

$tsquery = "timestamp >= $from AND timestamp <= $to";

$sql_ranges = "SELECT ranges FROM organisations WHERE id = $q_org";
$debuginfo[] = $sql_ranges;
$result_ranges = pg_query($pgconn, $sql_ranges);
$row = pg_fetch_assoc($result_ranges);

# Retrieving posted variables from $_GET
$allowed_get = array(
                "int_sort"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

# Setting up sorting stuff
# FIXME
if (isset($clean['sort'])) {
  $sort = $clean['sort'];
} else {
  $sort = 0;
}

echo "<div class='left'>\n";
  echo "<div class='block'>\n";
    echo "<div class='dataBlock'>\n";
      echo "<div class='blockHeader'>" .$l['lc_cross']. "</div>\n";
      echo "<div class='blockContent'>\n";
        if ($row['ranges'] == "") {
          echo "<h3>" .$l['lc_noranges']. "</h3>\n";
          $err = 1;
        } else {
          echo "<table class='datatable'>\n";
            echo "<tr>\n";
              if ($sort == 0) {
                echo "<th width='300'><a href='logcheck.php?int_sort=1'>" .$l['lc_range']. "</a>&nbsp;<img src='images/up.gif' /></th>\n";
              } else {
                echo "<th width='300'><a href='logcheck.php?int_sort=0'>" .$l['lc_range']. "</a>&nbsp;<img src='images/down.gif' /></th>\n";
              }
              echo "<th width='150'>" .$l['g_mal']. "</th>\n";
              echo "<th width='150'>" .$l['lc_uniqsource']. "</th>\n";
              echo "<th width='150'>" .$l['g_pos']. "</th>\n";
              echo "<th width='150'>" .$l['lc_uniqsource']. "</th>\n";
            echo "</tr>\n";

            ### Looping through organisation info retrieved by soap connection.
            $ranges_ar = explode(";", $row['ranges']);
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
                add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $s_org)", "where");

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

                echo "<tr>\n";
                  echo "<td>$range</td>\n";
                  if ($count_total1 > 0) {
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
          echo "</table>\n";
        }
      echo "</div>\n";
    echo "</div>\n";
    echo "<div class='blockFooter'></div>\n";
  echo "</div>\n";
echo "</div>\n";
debug_sql();
?>
<?php footer(); ?>

<?php include("menu.php"); set_title("Log Overview"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.02                  #
# 21-11-2006                       #
# Jan van Lith & Kees Trippelvitz  #
# Modified by Peter Arts           #
####################################

#############################################
# Changelog:
# 1.04.02 Added extra check on severity in sql query when sev = 1
# 1.04.01 Rereleased as 1.04.01
# 1.03.03 Fixed a bug with 0 BD and AVG scans
# 1.03.02 Fixed an organisation bug when selecting ALL orgs and ALL logs
# 1.03.01 Released as part of the 1.03 package
# 1.02.13 Added intval() to session variables
# 1.02.12 Fixed typo in intval() function
# 1.02.11 Added some more input checks and removed includes
# 1.02.10 Fixed some organisation bugs
# 1.02.09 Minor bugfixes and code cleaning
# 1.02.08 Enhanced debugging
# 1.02.07 Added debug option
# 1.02.06 Fixed an organisation bug for normal users
# 1.02.05 Fixed some organisation bugs
# 1.02.04 Added number formatting
#############################################

$s_org = intval($_SESSION['s_org']);
$s_access = $_SESSION['s_access'];
$s_access_search = intval($s_access{1});
$querystring = "";
$q_org = $s_org;

if (isset($_GET['sev'])) {
  $sev = intval($_GET['sev']);
} else {
  $err = 1;
  echo "No severity given in the querystring.<br />\n";
  echo "<a href='logindex.php'>Back</a>\n";
}

### Making sure the correct organisation is set.
if ($s_access_search == 9) {
  if (isset($_GET['org'])) {
    if ($_GET['org'] != 0) {
      $q_org = intval($_GET['org']);
      add_db_table("sensors");
      $where[] = "sensors.organisation = $q_org";
      $querystring = $querystring . "&amp;org=$q_org";
    } else {
      $q_org = 0;
    }
  }
} else {
  add_db_table("sensors");
  $where[] = "sensors.organisation = $q_org";
}

### Checking for period.
if (isset($_GET['to']) && isset($_GET['from'])) {
  $start = intval($_GET['from']);
  $end = intval($_GET['to']);
  add_db_table("attacks");
  $where[] = "attacks.id = details.attackid";
  $where[] = "attacks.timestamp >= $start";
  $where[] = "attacks.timestamp <= $end";
  $dateqs = "&amp;from=$start&amp;to=$end";
}

if ($err != 1) {
  ######### Table for Malicious attacks (SEV: 1) #############

  if ($sev == 1) {

    $where[] = " attacks.severity = 1 ";
    $where[] = " details.type = 1 ";
    add_db_table("sensors");
    add_db_table("details");
    prepare_sql();

    ### Admin check.
    $sql_count = "SELECT count(DISTINCT details.attackid) as total, details.text ";
    $sql_count .= "FROM $sql_from ";
    $sql_count .= " $sql_where ";
    $sql_count .= " GROUP BY details.text ";
    $sql_count .= " ORDER BY total DESC ";
    $debuginfo[] = "$sql_count";
    $result_count = pg_query($pgconn, $sql_count);
    $numrows_count = pg_num_rows($result_count);

    if ($numrows_count > 0) {
      echo "<table class='datatable'>\n";
        echo "<tr>\n";
          echo "<td class='dataheader' width='500'>Malicious attacks</td>\n";
          echo "<td class='dataheader' width='100'>Statistics</td>\n";
        echo "</tr>\n";

        $total = 0;
        while ($row = pg_fetch_assoc($result_count)) {
          $dia = $row['text'];
          $count = $row['total'];
          $total = $total + $count;
          $attack = $attacks_ar[$dia]["Attack"];
          $attack_url = $attacks_ar[$dia]["URL"];
          echo "<tr>\n";
            if ($attack_url != "") {
              echo "<td class='datatd'><a href='$attack_url' target='new'>$attack</a></td>\n";
            } else {
              echo "<td class='datatd'>$attack</td>\n";
            }
            echo "<td class='datatd' align='right'><a href='logsearch.php?f_attack=$dia&amp;f_search=&amp;f_field=source&amp;c=0$querystring$dateqs'>" . nf($count) . "</a>&nbsp;</td>\n";
          echo "</tr>\n";
        }
        echo "<tr>\n";
          echo "<td class='dataheader' align='right'>Total&nbsp;</td>\n";
          echo "<td class='dataheader' align='right'><a href='logsearch.php?f_sev=$sev&amp;f_search=&amp;f_field=source&amp;c=0$querystring$dateqs'>" . nf($total) . "</a>&nbsp;</td>\n";
        echo "</tr>\n";
      echo "</table>\n";
    }
  }
  
  ######### Table for Downloaded Malware (SEV: 32) #############
  
  elseif ($sev == 32) {
    $where[] = " attacks.severity = 32 ";
    $where[] = " details.type = 8 ";
    $where[] = " details.text = uniq_binaries.name ";
    add_db_table("sensors");
    add_db_table("details");
    add_db_table("uniq_binaries");
    prepare_sql();

    $sql_down = "SELECT DISTINCT uniq_binaries.id, details.text, count(details.id) as total ";
    $sql_down .= "FROM $sql_from ";
    $sql_down .= " $sql_where ";
    $sql_down .= " GROUP BY uniq_binaries.id, details.text ";
    $sql_down .= " ORDER BY total DESC ";
    $debuginfo[] = "$sql_down";
    $result_down = pg_query($pgconn, $sql_down);
    $numrows_down = pg_num_rows($result_down);

#    # Debug info
#    if ($debug == 1) {
#      echo "<pre>";
#      echo "SQL_DOWN: $sql_down<br />\n";
#      echo "</pre>\n";
#    }

    echo "Malware statistics.<br /><br />\n";
    if ($numrows_down > 0) {
      $sql_scanners = "SELECT * FROM scanners";
      $result_scanners = pg_query($pgconn, $sql_scanners);
      $numrows_scanners = pg_num_rows($result_scanners);
      $a = 0;
      while ($scanners = pg_fetch_assoc($result_scanners)) {
        $a++;
        $name = $scanners['name'];
        echo "<input type='button' class='tabsel' id='scanner_$a' name='scanner_$a' value='$name' onclick='show_hide_column($a);' />\n";
      }
      pg_result_seek($result_scanners, 0);

      echo "<br /><br />\n";
      $virus_count_ar = array();
      echo "<table class='datatable' id='malwaretable' width='800'>\n";
        echo "<tr>\n";
          echo "<td class='dataheader'>Malware downloaded</td>\n";
          while ($scanners = pg_fetch_assoc($result_scanners)) {
            $name = $scanners['name'];
            echo "<td class='datatd'><b>$name</b></td>\n";
          }
          pg_result_seek($result_scanners, 0);
          echo "<td class='dataheader'>Statistics</td>\n";
        echo "</tr>\n";

        while ($row = pg_fetch_assoc($result_down)) {
          $bin_id = $row['id'];
          $malware = $row['text'];
          $count = $row['total'];

          echo "<tr>\n";
            echo "<td class='datatd'><a href='binaryhist.php?binname=$malware'>$malware</a></td>\n";
            while ($scanners = pg_fetch_assoc($result_scanners)) {
              $scanner_id = $scanners['id'];
              $sql_virus = "SELECT DISTINCT stats_virus.name as virusname, binaries.timestamp FROM binaries, stats_virus ";
              $sql_virus .= "WHERE binaries.bin = $bin_id AND binaries.scanner = $scanner_id ";
              $sql_virus .= "AND binaries.info = stats_virus.id ORDER BY binaries.timestamp DESC LIMIT 1";
              $debuginfo[] = "$sql_virus";
              $result_virus = pg_query($pgconn, $sql_virus);
              $numrows_virus = pg_num_rows($result_virus);

              # Debug info
#              if ($debug == 1) {
#                echo "<pre>";
#                echo "SQL_VIRUS: $sql_virus<br />\n";
#                echo "</pre>\n";
#              }

              if ($numrows_virus == 0) {
                $virus = "Not scanned";
              } else {
                $virus = pg_result($result_virus, "virusname");
              }

              # Starting the count for the viri.
              $virus_count_ar[$virus] = $virus_count_ar[$virus] + $count;

              if ($virus == "Not scanned") {
                $ignore[$scanner_id]++;
              } elseif ($virus == "Suspicious") {
                $total[$scanner_id]++;
                $virus = "<font color='red'>$virus</font>";
              } else {
                $found[$scanner_id]++;
                $total[$scanner_id]++;
                $virus = "<font color='red'>$virus</font>";
              }
              echo "<td class='datatd'>$virus</td>\n";
            }
            echo "<td class='datatd'><a href='logsearch.php?org=" . $q_org . "&f_bin=$bin_id$dateqs'>$count</a></td>\n";
            pg_result_seek($result_scanners, 0);
          echo "</tr>\n";
        }
        echo "<tr class='datatr'>\n";
          echo "<td class='dataheader'>Total recognition %</td>\n";
          while ($scanners = pg_fetch_assoc($result_scanners)) {
            $id = $scanners['id'];
            $name = $scanners['name'];

            if ($total[$id] == 0) {
            echo "<td class='dataheader'>0 scanned</td>\n";
            } else {
              $perc[$id] = floor($found[$id] / $total[$id] * 100);
              echo "<td class='dataheader'>$found[$id] / $total[$id] = $perc[$id] %</td>\n";
            }
          }
          echo "<td class='dataheader'>&nbsp;</td>\n";
        echo "</tr>\n";        
      echo "</table>\n";
    }
  }
}

# Debug info
debug();

pg_close($pgconn);
?>
<?php footer(); ?>

<?php include("menu.php"); set_title("Log Overview"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.01                  #
# 06-11-2006                       #
# Jan van Lith & Kees Trippelvitz  #
# Modified by Peter Arts           #
####################################

#############################################
# Changelog:
# 1.04.01 Rereleased as 1.04.01
# 1.03.02 Fixed organisation bug
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

    $where[] .= " details.type = 1 ";
    add_db_table("sensors");
    add_db_table("details");
    prepare_sql();

    ### Admin check.
    $sql_count = "SELECT count(DISTINCT details.attackid) as total, details.text ";
    $sql_count .= "FROM $sql_from ";
    $sql_count .= " $sql_where ";
    $sql_count .= " GROUP BY details.text ";
    $sql_count .= " ORDER BY total DESC ";
    $result_count = pg_query($pgconn, $sql_count);
    $numrows_count = pg_num_rows($result_count);

    # Debug info
    if ($debug == 1) {
      echo "<pre>";
      echo "SQL_COUNT: $sql_count<br />\n";
      echo "</pre>\n";
    }

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
    $where[] = " details.type = 8 ";
    add_db_table("sensors");
    add_db_table("details");
    prepare_sql();

    $sql_down = "SELECT DISTINCT details.text, count(details.id) as total ";
    $sql_down .= "FROM $sql_from ";
    $sql_down .= " $sql_where ";
    $sql_down .= " GROUP BY details.text ";
    $sql_down .= " ORDER BY total DESC ";
    $result_down = pg_query($pgconn, $sql_down);
    $numrows_down = pg_num_rows($result_down);

    # Debug info
    if ($debug == 1) {
      echo "<pre>";
      echo "SQL_DOWN: $sql_down<br />\n";
      echo "</pre>\n";
    }

    if ($numrows_down > 0) {
      echo "Malware statistics.<br /><br />\n";
      $virus_count_ar = array();
      echo "<table class='datatable' width='800'>\n";
        echo "<tr>\n";
          echo "<td class='dataheader'>Malware downloaded</td>\n";
          echo "<td class='dataheader'>ClamAV</td>\n";
          if ($bdc == 1) {
            echo "<td class='dataheader'>BitDefender</td>\n";
          }
          if ($antivir == 1) {
            echo "<td class='dataheader'>Antivir</td>\n";
          }
          echo "<td class='dataheader'>Statistics</td>\n";
        echo "</tr>\n";

        $clamav_found = 0;
        $bdc_found = 0;
        $antivir_found = 0;
        $clamav_total = 0;
        $bdc_total = 0;
        $antivir_total = 0;

        while ($row = pg_fetch_assoc($result_down)) {
          $malware = $row['text'];
          $count = $row['total'];

          $sql_clamav = "SELECT * FROM binaries WHERE bin = '$malware' AND scanner = 'ClamAV'";
          $result_clamav = pg_query($pgconn, $sql_clamav);
          $numrows_clamav = pg_num_rows($result_clamav);
          if ($numrows_clamav == 0) {
            $clamav_info = "Not scanned";
          } else {
            $clamav_info = pg_result($result_clamav, "info");
          }

          if ($bdc == 1) {
            $sql_bdc = "SELECT * FROM binaries WHERE bin = '$malware' AND scanner = 'BitDefender'";
            $result_bdc = pg_query($pgconn, $sql_bdc);
            $numrows_bdc = pg_num_rows($result_bdc);
            if ($numrows_bdc == 0) {
              $bdc_info = "Not scanned";
            } else {
              $bdc_info = pg_result($result_bdc, "info");
            }
          }

          if ($antivir == 1) {
            $sql_antivir = "SELECT * FROM binaries WHERE bin = '$malware' AND scanner = 'Antivir'";
            $result_antivir = pg_query($pgconn, $sql_antivir);
            $numrows_antivir = pg_num_rows($result_antivir);
            if ($numrows_antivir == 0) {
              $antivir_info = "Not scanned";
            } else {
              $antivir_info = pg_result($result_antivir, "info");
            }
          }          

          # Starting the count for the viri.
          if (!array_key_exists($virus, $virus_count_ar)) {
            $virus_count_ar[$virus] = $count;
          } else {
            $newcount = $virus_count_ar[$virus] + $count;
            $virus_count_ar[$virus] = $newcount;
          }

          # Table row showing the virus info.
          echo "<tr>\n";
            if ($clamav_info == "Not scanned" || $bdc_info == "Not scanned" || $antivir_info == "Not scanned") {
              echo "<td class='datatd'>$malware</td>\n";
            } else {
              echo "<td class='datatd'><a href='binaryhist.php?bin=$malware'>$malware</a></td>\n";
            }
            ### ClamAV ###
            if ($clamav_info == "Suspicious") {
              echo "<td class='datatd'>$clamav_info</td>\n";
              $clamav_total++;
            } elseif ($clamav_info == "Not scanned") {
              echo "<td class='datatd'>$clamav_info</td>\n";
            } else {
              echo "<td class='datatd'><font color='red'>$clamav_info</font></td>\n";
              $clamav_found++;
              $clamav_total++;
            }
            ### BitDefender ###
            if ($bdc_info == "Suspicious") {
              echo "<td class='datatd'>$bdc_info</td>\n";
              $bdc_total++;
            } elseif ($bdc_info == "Not scanned") {
              echo "<td class='datatd'>$bdc_info</td>\n";
            } else {
              echo "<td class='datatd'><font color='red'>$bdc_info</font></td>\n";
              $bdc_total++;
              $bdc_found++;
            }
            ### Antivir ###
            if ($antivir_info == "Suspicious") {
              echo "<td class='datatd'>$antivir_info</td>\n";
              $antivir_total++;
            } elseif ($antivir_info == "Not scanned") {
              echo "<td class='datatd'>$antivir_info</td>\n";
            } else {
              echo "<td class='datatd'><font color='red'>$antivir_info</font></td>\n";
              $antivir_total++;
              $antivir_found++;
            }
            //echo "<td class='datatd'><a href='logattacks.php?sev=detail$querystring&amp;bin=$malware$dateqs'>$count</a></td>\n";
            echo "<td class='datatd'><a href='logsearch.php?org=" . $q_org . "&f_bin=$malware$dateqs'>$count</a></td>\n";
          echo "</tr>\n";
        }
        echo "<tr class='datatr'>\n";
          echo "<td class='dataheader'>Total recognition %</td>\n";
          if ($clamav_total == 0) {
            echo "<td class='dataheader'>0 scanned</td>\n";
          } else {
            $clamav_perc = floor($clamav_found / $clamav_total * 100);
            echo "<td class='dataheader'>$clamav_found / $clamav_total = $clamav_perc %</td>\n";
          }
          if ($clamav_total == 0) {
            echo "<td class='dataheader'>0 scanned</td>\n";
          } else {
            $bdc_perc = floor($bdc_found / $bdc_total * 100);
            echo "<td class='dataheader'>$bdc_found / $bdc_total = $bdc_perc %</td>\n";
          }
          if ($clamav_total == 0) {
            echo "<td class='dataheader'>0 scanned</td>\n";
          } else {
            $antivir_perc = floor($antivir_found / $antivir_total * 100);
            echo "<td class='dataheader'>$antivir_found / $antivir_total = $antivir_perc %</td>\n";
          }
          echo "<td class='dataheader'>&nbsp;</td>\n";
        echo "</tr>\n";        
      echo "</table>\n";
    }
  }
}

pg_close($pgconn);
?>
<?php footer(); ?>

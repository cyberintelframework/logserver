<?php include("menu.php");?>
<?php
####################################
# SURFnet IDS                      #
# Version 1.04.05                  #
# 15-03-2007                       #
# Jan van Lith & Kees Trippelvitz  #
# Peter Arts                       #
####################################

#############################################
# Changelog:
# 1.04.05 Added dropdown box
# 1.04.04 Added empty flag for unknown countries
# 1.04.03 Added geoip and p0f stuff
# 1.04.02 Added some graphs and stats 
# 1.04.01 Added changelog and GD check
#############################################

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);

$allowed_post = array(
                "int_selperiod"
);
$check = extractvars($_POST, $allowed_post);
debug_input();

if (isset($clean['selperiod'])) {
  $sel = $clean['selperiod'];
} else {
  $sel = $c_sel_period;
}

### GEOIP STUFF
if ($c_geoip_enable == 1) {
  include '../include/' .$c_geoip_module;
  $gi = geoip_open("../include/" .$c_geoip_data, GEOIP_STANDARD);
}

# GD check
$phpext = get_loaded_extensions();
if (!in_array ("gd", $phpext)){
  echo "<p>\n";
    echo "<font color='red'>\n";
      echo "Warning: GD was not loaded with FreeType support!<br />\n";
      echo "This means that chart generation will most likely fail!<br />\n";
      echo "For more information check out the documentation <a href='http://ids.surfnet.nl/'>here</a>.\n";
    echo "</font>\n";
  echo "</p>\n";
} else {
  $gdinfo = gd_info();
  if ($gdinfo["FreeType Support"] != 1) {
    echo "<p>\n";
      echo "<font color='red'>\n";
        echo "Warning: GD was not loaded with FreeType support!<br />\n";
        echo "This means that chart generation will most likely fail!<br />\n";
        echo "For more information check out the documentation <a href='http://ids.surfnet.nl/'>here</a>.\n";
      echo "</font>\n";
    echo "</p>\n";
  }
}

echo "<table width='100%'>\n";
  echo "<tr>\n";
    echo "<td><h3>SURFnet IDS $c_version</h3></td>\n";
    echo "<td>\n";
      echo "<form name='viewform' action='index.php' method='post'>\n";
        echo "<table width='100%' id='sensortable'>\n";
          echo "<tr>\n";
            echo "<td align='right'>\n";
              echo "<select name='int_selperiod' onChange='javascript: this.form.submit();'>\n";
                foreach ($v_index_periods as $key => $value) {
                  echo printOption($key, $value, $sel) . "\n";
                }
              echo "</select>\n";
            echo "</td>\n";
          echo "</tr>\n";
        echo "</table>\n";
      echo "</form>\n";
    echo "</td>\n";
  echo "</tr>\n";
echo "</table>\n";

if ($sel == 0) {
  $ts_qs = "?strip_html_escape_tsselect=T";
  $day = date("d");
  $month = date("m");
  $year = date("Y");
  $start = getStartDay($day, $month, $year);
  $end = getEndDay($day, $month, $year);
  $interval_a = 3600;
  $interval_p = 3600;
} elseif ($sel == 1) {
  $start = date("U") - (7 * 24 * 60 * 60);
  $end = date("U");
  $startqs = date("d-m-Y H:i:s", $start);
  $endqs = date("d-m-Y H:i:s", $end);
  $ts_qs = "?strip_html_escape_tsstart=$startqs&strip_html_escape_tsend=$endqs";
  $interval_a = 3600;
  $interval_p = 86400;
}

echo "<table width='70%'>\n";
  echo "<tr>\n";
    echo "<td>\n";
      echo "<b>Attacks ($v_index_periods[$sel])</b>";
      echo "<br />";
      echo "<a href='plotter.php$ts_qs&sensorid%5B%5D=&severity%5B%5D=99&int_interval=$interval_a&int_type=1'>";
        echo "<img src='showplot.php$ts_qs&sensorid%5B%5D=&severity%5B%5D=99&int_interval=$interval_a&int_type=1&int_width=475&int_heigth=300'>";
      echo "</a>";
    echo "</td>\n";
    echo "<td>\n";
      ###### Display attacks by ports for today
      echo "<b>Attacks by Port ($v_index_periods[$sel])</b>\n";
      echo "<br />";
      echo "<a href='plotter.php$ts_qs&strip_html_escape_ports=all&severity%5B%5D=0&severity%5B%5D=1&int_interval=$interval_p&int_type=1'>";
        echo "<img src='showplot.php$ts_qs&strip_html_escape_ports=all&severity%5B%5D=0&severity%5B%5D=1&int_interval=$interval_p&int_type=1&int_width=475&int_heigth=300'>";
      echo "</a>";
    echo "</td>\n";
  echo "</tr>\n";
  echo "<tr>\n";
    echo "<td>\n";
    ###### Display todays attackers.
      echo "<b>Attackers ($v_index_periods[$sel])</b>\n";
      echo "<table class='datatable' width='100%'>\n";
        echo "<tr>\n";
          echo "<td class='dataheader' width='50%'>IP Address</td>\n";
          echo "<td class='dataheader' width='35%'>Last Seen</td>\n";
          echo "<td class='dataheader' width='15%'>Total Hits</td>\n";
        echo "</tr>\n";
        #### Get the data for todays attackers and display it.
        $query = "attacks.sensorid = sensors.id ";
        $query .= "AND timestamp >= $start AND timestamp <= $end ";
        if ($s_admin != 1) {
          $query .= " AND sensors.organisation = '$s_org' ";
        }

        $sql_attack_countqry = "SELECT count(*), source FROM attacks, sensors WHERE $query GROUP BY source ORDER BY count DESC LIMIT 10";
        $debuginfo[] = $sql_attack_countqry;
        $result_countqry = pg_query($pgconn, $sql_attack_countqry);
        while ($row = pg_fetch_assoc($result_countqry)) {
          $source = $row['source'];
          $sql_attack_ls = "SELECT timestamp FROM attacks, sensors WHERE source = '$source' AND attacks.sensorid = sensors.id ";
          if ($s_admin != 1) {
            $sql_attack_ls .= " AND sensors.organisation = '$s_org' ";
          }
          $sql_attack_ls .= " ORDER BY timestamp DESC LIMIT 1";
          $debuginfo[] = $sql_attack_ls;
          $result_ls = pg_query($pgconn, $sql_attack_ls);
          $lsdb = pg_fetch_assoc($result_ls);
          $ls = $lsdb['timestamp'];

          $chk = date("d", $ls);
          $cur = date("d");
          $dif = $cur - $chk;
          $ls = date("d-m-Y H:i:s", $ls);

          echo "<tr>\n";
            echo "<td class='datatd'>";
              if ($c_enable_pof == 1) {
                $sql_finger = "SELECT name FROM system WHERE ip_addr = '" .$source. "' ORDER BY last_tstamp DESC";
                $result_finger = pg_query($pgconn, $sql_finger);
                $numrows_finger = pg_num_rows($result_finger);

                $fingerprint = pg_result($result_finger, 0);
                $finger_ar = explode(" ", $fingerprint);
                $os = $finger_ar[0];
              } else {
                $numrows_finger = 0;
              }
              if ($numrows_finger != 0) {
                $osimg = "$c_surfidsdir/webinterface/images/$os.gif";
                if (file_exists($osimg)) {
                  echo "<img src='images/$os.gif' onmouseover='return overlib(\"$fingerprint\");' onmouseout='return nd();' />&nbsp;";
                } else {
                  echo "<img src='images/Blank.gif' onmouseover='return overlib(\"$fingerprint\");' onmouseout='return nd();' />&nbsp;";
                }
              } else {
                echo "<img src='images/Blank.gif' alt='No info' title='No info' />&nbsp;";
              }
              if ($c_geoip_enable == 1) {
                $record = geoip_record_by_addr($gi, $source);
                $countrycode = strtolower($record->country_code);
                $cimg = "$c_surfidsdir/webinterface/images/worldflags/flag_" .$countrycode. ".gif";
                if (file_exists($cimg)) {
                  $country = $record->country_name;
                  echo "<img src='images/worldflags/flag_" .$countrycode. ".gif' onmouseover='return overlib(\"$country\");' onmouseout='return nd();' />&nbsp;";
                } else {
                  echo "<img src='images/worldflags/flag.gif'  onmouseover='return overlib(\"No Country Info\");' onmouseout='return nd();' style='width: 18px;' />&nbsp;";
                }
              }
              echo "<a href='whois.php?ip_ip=$source'>$source</a>";
            echo "</td>\n";
            echo "<td class='datatd' style='background-color: $v_indexcolors[$dif];'>$ls</td>\n";
#            echo "<td class='datatd'>CHK: $chk, CUR: $cur, DIF: $dif</td>\n";
            echo "<td class='datatd'>$row[count]</td>\n";
          echo "</tr>\n";
        }
      echo "</table>\n";
    echo "</td>\n";
    echo "<td valign=top>\n";
      ###### Display todays ports.
      echo "<b>Ports ($v_index_periods[$sel])</b>\n";
      echo "<table class='datatable' width='100%'>\n";
        echo "<tr>\n";
          echo "<td class='dataheader' width='40%'>Destination Ports</td>\n";
          echo "<td class='dataheader' width='45%'>Description</td>\n";
          echo "<td class='dataheader' width='100%'>Total Hits</td>\n";
        echo "</tr>\n";

        $queryport = "attacks.sensorid = sensors.id ";
        $queryport .= "AND timestamp >= $start AND timestamp <= $end ";
        if ($s_admin != 1) {
          $queryport .= "AND sensors.organisation = '$s_org' ";
        }
        $sql_port_countqry = "SELECT attacks.dport, count(attacks.dport) as total FROM attacks,sensors ";
        $sql_port_countqry .= "WHERE $queryport GROUP BY attacks.dport ORDER BY total DESC LIMIT 10 OFFSET 0";
        $result_portcountqry = pg_query($pgconn, $sql_port_countqry);
        while ($row = pg_fetch_assoc($result_portcountqry)) {
          echo "<tr>\n";
            echo "<td class='datatd'><a href='logsearch.php?dradio=A&int_dport=$row[dport]&orderm=DESC&strip_html_escape_tsstart=$startqs%3A$startqsmin&strip_html_escape_tsend=$endqs%3A$endqsmin'>$row[dport]</a></td>\n";
            echo "<td class='datatd'><a target='_blank' href='http://www.iss.net/security_center/advice/Exploits/Ports/$row[dport]'>".getPortDescr($row[dport])."</a></td>\n";
            echo "<td class='datatd'>$row[total]</td>\n";
          echo "</tr>\n";
        }
      echo "</table>\n";
    echo "</td>\n";
  echo "</tr>\n";
echo "</table>\n";

debug_sql();
?>
<p>For more technical information you can surf to: <a href="http://ids.surfnet.nl/">http://ids.surfnet.nl/</a></p>

<?php footer(); ?>

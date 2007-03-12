<?php include("menu.php");?>
<?php
####################################
# SURFnet IDS                      #
# Version 1.04.03                  #
# 12-03-2007                       #
# Jan van Lith & Kees Trippelvitz  #
# Peter Arts                       #
####################################

#############################################
# Changelog:
# 1.04.03 Added geoip and p0f stuff
# 1.04.02 Added some graphs and stats 
# 1.04.01 Added changelog and GD check
#############################################

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);

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
      echo "For more information check out the documentation <a href='http://ids.surfnet.nl/documentation/faq_log.php#5'>here</a>.\n";
    echo "</font>\n";
  echo "</p>\n";
} else {
  $gdinfo = gd_info();
  if ($gdinfo["FreeType Support"] != 1) {
    echo "<p>\n";
      echo "<font color='red'>\n";
        echo "Warning: GD was not loaded with FreeType support!<br />\n";
        echo "This means that chart generation will most likely fail!<br />\n";
        echo "For more information check out the documentation <a href='http://ids.surfnet.nl/documentation/faq_log.php#5'>here</a>.\n";
      echo "</font>\n";
    echo "</p>\n";
  }
}

echo "<h3>SURFnet IDS $c_version</h3>\n";
$day = date("d");
$year = date("Y");
$month = date("n");
$start = getStartWeek($day, $month, $year);
$end = getEndWeek($day, $month, $year);

$startqs = date("d-m-Y+H", $start);
$endqs = date("d-m-Y+H", $end);
$startqsmin = date("i", $start);
$endqsmin = date("i", $end);

echo "<table width='70%'>\n";
  echo "<tr>\n";
    echo "<td>\n";
      echo "<b>This week Attacks</b>";
      echo "<br />";
      echo "<a href='plotter.php?strip_html_escape_tsstart=$startqs%3A$startqsmin&strip_html_escape_tsend=$endqs%3A$endqsmin&sensorid%5B%5D=&severity%5B%5D=99&int_interval=3600&int_type=1'><img src='showplot.php?strip_html_escape_tsstart=$startqs%3A$startqsmin&strip_html_escape_tsend=$endqs%3A$endqsmin&sensorid%5B%5D=&severity%5B%5D=99&int_interval=3600&int_type=1&int_width=475&int_heigth=300'></a>";
    echo "</td>\n";
    echo "<td>\n";
      ###### Display attacks by ports for today
      echo "<b>This week Attacks by Port</b>\n";
      echo "<br />";
      echo "<a href='plotter.php?strip_html_escape_tsstart=$startqs%3A$startqsmin&strip_html_escape_tsend=$endqs%3A$endqsmin&strip_html_escape_ports=all&severity%5B%5D=0&severity%5B%5D=1&int_interval=86400&int_type=1'><img src='showplot.php?strip_html_escape_tsstart=$startqs%3A$startqsmin&strip_html_escape_tsend=$endqs%3A$endqsmin&strip_html_escape_ports=all&severity%5B%5D=0&severity%5B%5D=1&int_interval=86400&int_type=1&int_width=475&int_heigth=300'></a>";
    echo "</td>\n";
  echo "</tr>\n";
  echo "<tr>\n";
    echo "<td>\n";
    ###### Display todays attackers.
      echo "<b>This week Attackers</b>\n";
      echo "<table class='datatable' width='100%'>\n";
        echo "<tr>\n";
          echo "<td class='dataheader' width='85%'>IP Address</td>\n";
          echo "<td class='dataheader' width='100%'>Total Hits</td>\n";
        echo "</tr>\n";
        #### Get the data for todays attackers and display it.
        $query = "attacks.sensorid = sensors.id ";
        $query .= "AND timestamp >= $start AND timestamp <= $end ";
        if ($s_admin != 1) {
          $query .= "AND sensors.organisation = '$s_org' ";
        }

        $sql_attack_countqry = "SELECT count(*),source FROM attacks,sensors WHERE $query GROUP BY source ORDER BY count DESC LIMIT 10";
        $result_countqry = pg_query($pgconn, $sql_attack_countqry);
        while ($row = pg_fetch_assoc($result_countqry)) {
          $source = $row['source'];
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
                }
              }
              echo "<a href='whois.php?ip_ip=$source'>$source</a>";
            echo "</td>\n";
            echo "<td class='datatd'>$row[count]</td>\n";
          echo "</tr>\n";
        }
      echo "</table>\n";
    echo "</td>\n";
    echo "<td valign=top>\n";
      ###### Display todays ports.
      echo "<b>This week Ports</b>\n";
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

?>
<p>For more technical information you can surf to: <a href="http://ids.surfnet.nl/">http://ids.surfnet.nl/</a></p>

<?php footer(); ?>

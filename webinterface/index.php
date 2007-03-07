<?php include("menu.php");?>
<?php
####################################
# SURFnet IDS                      #
# Version 1.04.02                  #
# 15-11-2006                       #
# Peter Arts                       #
####################################

#############################################
# Changelog:
# 1.04.02 Added some graphs and stats 
# 1.04.01 Added changelog and GD check
#############################################

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);


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
echo "<tr>\n";
#### Get the data for todays attackers and display it.
$query = "attacks.sensorid = sensors.id ";
$query .= "AND timestamp >= $start AND timestamp <= $end ";
if ($s_admin != 1) {
	$query .= "AND sensors.organisation = '$s_org' ";
}
$sql_attack_countqry = "SELECT count(*),source FROM attacks,sensors WHERE $query GROUP BY source ORDER BY count DESC LIMIT 10";
$result_countqry = pg_query($pgconn, $sql_attack_countqry);
while ($row = pg_fetch_assoc($result_countqry)) {

echo "<td class='datatd'>";
echo "<a href='whois.php?ip_ip=$row[source]'>$row[source]</a>";
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
echo "<tr>\n";
$queryport = "attacks.sensorid = sensors.id ";
$queryport .= "AND timestamp >= $start AND timestamp <= $end ";
if ($s_admin != 1) {
	$queryport .= "AND sensors.organisation = '$s_org' ";
}
$sql_port_countqry = "SELECT attacks.dport, count(attacks.dport) as total FROM attacks,sensors WHERE $queryport GROUP BY attacks.dport ORDER BY total DESC LIMIT 10 OFFSET 0";
$result_portcountqry = pg_query($pgconn, $sql_port_countqry);
while ($row = pg_fetch_assoc($result_portcountqry)) {
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

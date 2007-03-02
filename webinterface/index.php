<?php include("menu.php");?>
<?php
####################################
# SURFnet IDS                      #
# Version 1.04.01                  #
# 15-11-2006                       #
# Peter Arts                       #
####################################

#############################################
# Changelog:
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

echo "<h4>Attacks this week</h4>";
echo "<img src='showplot.php?strip_html_escape_tsstart=$startqs%3A$startqsmin&strip_html_escape_tsend=$endqs%3A$endqsmin&sensorid%5B%5D=&severity%5B%5D=0&severity%5B%5D=1&int_interval=3600&int_type=1&int_width=990&int_heigth=400'>";

###### Display attacks by ports for today
echo "<img alt='Attacks by Port' src='heanet-graph.php' />\n";
echo "<br />";
###### Display todays attackers.
echo "<td>\n";
echo "<b>This week Attackers</b>\n";
echo "<table class='datatable' width='20%'>\n";
echo "<tr>\n";
echo "<td class='dataheader' width='20%'>IP Address</td>\n";
echo "<td class='dataheader' width='20%'>Total Hits</td>\n";
echo "</tr>\n";
#### Get the data for todays attackers and display it.
$query = "attacks.sensorid = sensors.id ";
$query .= "AND timestamp >= $start AND timestamp <= $end ";
if ($s_admin != 1) {
	$s_org = $q_org;
	$query .= "AND sensors.organisation = $q_org ";
}
$sql_attack_countqry = "SELECT count(*),source FROM attacks,sensors WHERE $query GROUP BY source ORDER BY count DESC LIMIT 10";
$result_countqry = pg_query($pgconn, $sql_attack_countqry);
echo "$sql_attack_countqry";
while ($row = pg_fetch_assoc($result_countqry)) {

echo "<td class='datatd'><img src='images/Blank.gif' alt='No info' title='No info' />&nbsp;";
echo "<a href=http://www.dshield.org/ipinfo.html?ip=$row[source]>$row[source]</a>";
echo "</td>\n";
echo "<td class='datatd'>$row[count]</td>\n";
echo "</tr>\n";
}
echo "</table>\n";
echo "</td>\n";
echo "</tr>\n";
echo "</table>\n";


?>
<p>For more technical information you can surf to: <a href="http://ids.surfnet.nl/">http://ids.surfnet.nl/</a></p>

<?php footer(); ?>

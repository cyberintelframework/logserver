<?php include("menu.php"); set_title("Log History");?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.02                  #
# 11-12-2006                       #
# Peter Arts & Kees Trippelvitz    #
####################################

#################################################
# Changelog:
# 1.04.02 Changed debug stuff
# 1.04.01 Rereleased as 1.04.01
# 1.03.01 Released as part of the 1.03 package
# 1.02.07 Changed some access handling
# 1.02.06 Removed includes
# 1.02.05 Enhanced debugging
# 1.02.04 Removed the 'Dialogue' extensions from the attack names.
# 1.02.03 Bugfix full list (sensor querystring)
#################################################

$s_org = intval($_SESSION['s_org']);
$s_access = $_SESSION['s_access'];
$s_access_search = intval($s_access{1});

$month = intval($_GET["m"]);
$year = intval($_GET["y"]);
if (($month < 1) || ($month > 12)) $month = (date("n") - 1);
if ($year < 2004) $year = date("Y");
if (isset($_GET['org']) && $s_access_search == 9) $s_org = intval($_GET['org']);
$s_sensor = -1;
if (isset($_GET["sensor"])) {
	// check for permissions
	$query = pg_query("SELECT * FROM sensors WHERE id = '" . intval($_GET["sensor"]) . "' AND organisation = '" . $s_org . "' LIMIT 1");
	if (pg_num_rows($query) == 1) $s_sensor = intval($_GET["sensor"]);
}
if (intval($s_sensor) <= 0) {
	// set default sensor
	$query = pg_query("SELECT id FROM sensors WHERE organisation = '" . $s_org . "' ORDER BY keyname LIMIT 1");
	$s_sensor = pg_result($query, 0);
}

$sql_getorg = "SELECT organisation FROM organisations WHERE id = '$s_org'";
$result_getorg = pg_query($pgconn, $sql_getorg);
#$db_org_name = pg_result($result_getorg, 0);

$debuginfo[] = $sql_getorg;

if (!isset($_GET['org']) && $s_access_search == 9) {
  echo "Select an organisation.<br /><br />\n";
  $sql_org = "SELECT * FROM organisations WHERE NOT organisation = 'ADMIN'";
  $debuginfo = $sql_org;
  $result_org = pg_query($pgconn, $sql_org);
  echo "<form name='sel_org' action='loghistory.php' method='get'>\n";
    echo "<select name='org' onChange='javascript: this.form.submit();'>\n";
      echo "<option value='$s_org'>Select Organisation</option>\n";
      while ($row = pg_fetch_assoc($result_org)) {
        $org_id = $row['id'];
        $organisation = $row['organisation'];
        echo "<option value='$org_id'>$organisation</option>\n";
      }
    echo "</select>\n";
    echo "<input type='hidden' name='m' value='$month' />\n";
    echo "<input type='hidden' name='y' value='$year' />\n";
  echo "</form>\n";
  $err = 1;
}

if ($err != 1) {
  $prevmonth = $month - 1;
  $prevyear = $year;
  if ($prevmonth == 0) {
    $prevmonth = 12;
    $prevyear = $year - 1;
  }
  $nextmonth = $month + 1;
  $nextyear = $year;
  if ($nextmonth > 12) {
    $nextmonth = $nextmonth % 12;
    $nextyear = $year + 1;
  }

  echo "<form name='selectorg' method='get' action='loghistory.php'>\n";
    echo "<input type='button' value='Prev' class='button' onClick=window.location='loghistory.php?m=$prevmonth&y=$prevyear&org=$s_org';>\n";
    ### If user is admin, then enable organisation menu.
    if ($s_access_search == 9) {
      $err = 1;
      $sql_orgs = "SELECT * FROM organisations WHERE NOT organisation = 'ADMIN'";
      $debuginfo = $sql_orgs;
      $result_orgs = pg_query($pgconn, $sql_orgs);
      echo "<input type='hidden' name='m' value='$month' />\n";
      echo "<input type='hidden' name='y' value='$year' />\n";
      echo "<select name='org' onChange='javascript: this.form.submit();'>\n";
        while ($row = pg_fetch_assoc($result_orgs)) {
          $org_id = $row['id'];
          $organisation = $row['organisation'];
          if (isset($_GET['org']) && $_GET['org'] == $org_id) {
            echo "<option value='$org_id' selected>$organisation</option>\n";
          } else {
            echo "<option value='$org_id'>$organisation</option>\n";
          }
        }
      echo "</select>&nbsp;\n";
    }
    
    $sql = "SELECT * FROM sensors WHERE organisation = '$s_org' ORDER BY keyname";
    $debuginfo[] = $sql;
    $query = pg_query($sql);
    echo "<select name='sensor' onChange='javascript: this.form.submit();'>\n";
    while ($sensor_data = pg_fetch_assoc($query)) {
      $sql_check = "SELECT * FROM stats_history WHERE sensorid = '" . intval($sensor_data["id"]) . "' AND year = '" . intval($year) . "' AND month = '" . intval($month) . "'";
      $debuginfo[] = $sql_check;
      $query_check = pg_query($sql_check);
      if (pg_num_rows($query_check) > 0) echo printOption($sensor_data["id"], $sensor_data["keyname"], $s_sensor);
    }
    echo "</select>\n";
    echo "<input type='button' value='Next' class='button' onClick=window.location='loghistory.php?m=$nextmonth&y=$nextyear&org=$s_org';>\n";
    echo "</form>\n";

	$mts = mktime(0,0,0,$month,1,$year);
	$monthname = date("F", $mts);
	echo "<h4>History data for $monthname $year</h4>\n";
	
	$sql = "SELECT * FROM stats_history WHERE sensorid = '" . intval($s_sensor) . "' AND year = '" . intval($year) . "' AND month = '" . intval($month) . "' LIMIT 1";
        $debuginfo[] = $sql;
        
	$query = pg_query($sql);
	if (pg_num_rows($query) == 0) {
		echo "<p>No data present for this month.</p>\n";
	} else {
		$org_id = intval($_GET["org"]);
		$sensorid = intval($_GET["sensor"]);
		echo "<table border=0 cellspacing=0 cellpadding=0><tr><td valign=\"top\">\n";

		$sql = "SELECT * FROM stats_history WHERE sensorid = '" . intval($s_sensor) . "' AND year = '" . intval($year) . "' AND month = '" . intval($month) . "' LIMIT 1";
                $debuginfo = $sql;

		$query = pg_query($sql);
		$stats_history = pg_fetch_assoc($query);
		$history_id = intval($stats_history["id"]);

		echo "<table class='datatable'>\n";
		echo " <tr>\n";
		echo "  <td class='dataheader' width='300' colspan=2 align='center'><h3>General statistics</h3></td>\n";
		echo " </tr>\n";
		echo " <tr>\n";
		echo "  <td class='datatd' width=200>Possible malicious attack</td>\n";
		echo "  <td class='datatd' align='right'>" . number_format($stats_history["count_possible"], 0, '.', ',') . "&nbsp;</td>\n";
		echo " </tr>\n";
		$count_malicious = number_format($stats_history["count_malicious"], 0, '.', ',');
		echo " <tr>\n";
		echo "  <td class='datatd' width=200>Malicious attack</td>\n";
		echo "  <td class='datatd' align='right'>" . $count_malicious . "&nbsp;</td>\n";
		echo " </tr>\n";
		echo " <tr>\n";
		echo "  <td class='datatd' width=200>Malware offered</td>\n";
		echo "  <td class='datatd' align='right'>" . number_format($stats_history["count_offered"], 0, '.', ',') . "&nbsp;</td>\n";
		echo " </tr>\n";
		echo " <tr>\n";
		echo "  <td class='datatd' width=200>Malware downloaded</td>\n";
		echo "  <td class='datatd' align='right'>" . number_format($stats_history["count_downloaded"], 0, '.', ',') . "&nbsp;</td>\n";
		echo " </tr>\n";
		echo "</table>\n";
		
		echo "<p>&nbsp;</p>\n";
		
		echo "</td><td width=50>&nbsp;</td><td valign=\"top\">\n";
		
		// Malicious attacks
		if ($_GET["full"] == "malicious") {
			$show = "full list";
			$limit = "";
			$link = "<a href=\"loghistory.php?m=$month&y=$year&org=$org_id&sensor=$sensorid\">Show top 5</a><br /><br />\n";
		} else {
			$show = "top 5";
			$limit = "LIMIT 5";
			$link = "<a href=\"loghistory.php?m=$month&y=$year&org=$org_id&sensor=$sensorid&full=malicious\">Show full list</a><br /><br />\n";
		}
		echo "<table class='datatable'>\n";
		echo " <tr>\n";
		echo "  <td class='dataheader' width='400' colspan=2 align='center'><h3>Malicious attacks ($show)</h3></td>\n";
		echo " </tr>\n";
		$sql = "SELECT * FROM stats_history_dialogue AS shd, stats_dialogue AS sd WHERE shd.dialogueid = sd.id AND shd.historyid = '" . $history_id . "' ORDER BY count DESC $limit";
                $debuginfo[] = $sql;
		$query = pg_query($sql);
		$i = 1;
		$list_count_malicious = 0;

		while ($dia = pg_fetch_assoc($query)) {
			$attack_name = preg_replace("/Dialogue/", "", $dia["name"]);
			echo " <tr>\n";
			echo "  <td class='datatd' width=300>" . $i . ". " . $attack_name . "</td>\n";
			echo "  <td class='datatd' align='right'>" . number_format($dia["count"], 0, '.', ',') . "&nbsp;</td>\n";
			echo " </tr>\n";
			$list_count_malicious += intval($dia["count"]);
			$i++;
		}
		echo " <tr>\n";
		echo "  <td class='dataheader' colspan=2 align='right'>Total: ";
		if ($show == "top 5") echo number_format($list_count_malicious, 0, '.', ',') . " of ";
		echo $count_malicious . "</td>\n";
		echo " </tr>\n";
		echo "</table>\n";
		echo $link;
		
		// Viruses
		if ($_GET["full"] == "viruses") {
			$show = "full list";
			$limit = "";
			$link = "<a href=\"loghistory.php?m=$month&y=$year&org=$org_id&sensor=$sensorid\">Show top 5</a><br /><br />\n";
		} else {
			$show = "top 5";
			$limit = "LIMIT 5";
			$link = "<a href=\"loghistory.php?m=$month&y=$year&org=$org_id&sensor=$sensorid&full=viruses\">Show full list</a><br /><br />\n";
		}
		echo "<table class='datatable'>\n";
		echo " <tr>\n";
		echo "  <td class='dataheader' width='400' colspan=2 align='center'><h3>Viruses ($show)</h3></td>\n";
		echo " </tr>\n";

		$sql = "SELECT * FROM stats_history_virus AS shv, stats_virus AS sv WHERE shv.virusid = sv.id AND sv.name <> 'Suspicious' AND shv.historyid = '" . $history_id . "' ORDER BY count DESC $limit";
		$sql_count = "SELECT SUM(count) FROM stats_history_virus AS shv, stats_virus AS sv WHERE shv.virusid = sv.id AND shv.historyid = '" . $history_id . "'";
                $debuginfo[] = $sql;
                $debuginfo[] = $sql_count;
		$query_count = pg_query($sql_count);
		$total = pg_result($query_count, 0);
		$query = pg_query($sql);
		$i = 1;
		$list_count_virus = 0;

		while ($dia = pg_fetch_assoc($query)) {
			echo " <tr>\n";
			echo "  <td class='datatd' width=300>" . $i . ". " . $dia["name"] . "</td>\n";
			echo "  <td class='datatd' align='right'>" . number_format($dia["count"], 0, '.', ',') . "&nbsp;</td>\n";
			echo " </tr>\n";
			$list_count_virus += intval($dia["count"]);
			$i++;
		}
		echo " <tr>\n";
		echo "  <td class='dataheader' colspan=2 align='right'>Total: ";
		if ($show == "top 5") echo number_format($list_count_virus, 0, '.', ',') . " of ";
		echo number_format($total, 0, '.', ',') . "</td>\n";
		echo " </tr>\n";
		echo "</table>\n";
		echo $link;
		
		echo "</td></tr></table>\n";
	}
}
pg_close($pgconn);
debug_sql();
?>
<?php footer(); ?>

<?php include("menu.php"); set_title("ARP Logging"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.01                  #
# 16-05-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

####################################
# Changelog:
# 1.04.01 Initial release
####################################

# Retrieving some session and server variables
$s_org = intval($_SESSION['s_org']);
$s_access_sensor = intval($s_access{0});
$s_hash = md5($_SESSION['s_hash']);
$url = $_SERVER['REQUEST_URI'];

# Retrieving posted variables from $_GET
$allowed_get = array(
	"int_m",
	"int_filter",
	"int_org",
	"int_page",
	"sort",
	"int_from",
	"int_to",
	"tsselect",
	"strip_html_escape_tsstart",
	"strip_html_escape_tsend",
	"mac_source",
	"mac_sourcehid",
	"target",
	"targethid"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

# Showing info/error messages if any
if (isset($clean['m'])) {
  $m = $clean['m'];
  $m = geterror($m);
  echo $m;
}

# Checking $_GET'ed variables
if (isset($clean['filter'])) {
  $filter = $clean['filter'];
} else {
  $filter = 0;
}

# Setting up sorting stuff
if (isset($tainted['sort'])) {
  $sort = $tainted['sort'];
  $url = str_replace("&sort=" . $sort, "", $url);
  $pattern = '/^(ida|idd|timestampa|timestampd|sourcemaca|sourcemacd|targetmaca|targetmacd|sensorida|sensoridd|typea|typed)$/';
  if (!preg_match($pattern, $sort)) {
    $sort = "ida";
  }
  $sorttype = substr($sort, 0, (strlen($sort) - 1));
  $dir = substr($sort, -1, 1);
  if ($dir == "a") {
    $neworder = "d";
    $dir = "ASC";
  } else {
    $neworder = "a";
    $dir = "DESC";
  }
} else {
  $neworder = "d";
  $sorttype = "id";
  $dir = "ASC";
}

# URL check
$count = substr_count($url, "?");
if ($count == 0) {
  $op = "?";
} else {
  $op = "&";
}

####################
# WHEN timestamping stuff
####################
$ts_select = $tainted['tsselect'];
$ar_valid_values = array("H", "D", "T", "W", "M", "Y");
if (in_array($ts_select, $ar_valid_values)) {
  $dt = time();
  $date_min = 60;
  $date_hour = 60 * $date_min;
  $date_day = 24 * $date_hour;
  $date_week = 7 * $date_day;
  $date_month = 31 * $date_day;
  $date_year = 365 * $date_day;
  $dt_sub = 0;
  // determine substitute value
  //"H", "D", "T", "W", "M", "Y"
  switch ($ts_select) {
    case "Y":
      $dt_sub = $date_year;
      break;
    case "M":
      $dt_sub = $date_month;
      break;
    case "W":
      $dt_sub = $date_week;
      break;
    case "D":
      $dt_sub = $date_day;
      break;
    case "H":
      $dt_sub = $date_hour;
      break;
    case "T":
      // today
      $dt = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
      break;
  }
  if ($dt_sub > 0) $dt -= $dt_sub;
  $ts_start = date("d-m-Y H:i:s", $dt);
  $ts_end = date("d-m-Y H:i:s", time());
} else {
  $ts_start = $clean["tsstart"];
  $ts_end = $clean["tsend"];
}

####################
# Start timestamp
####################
if (!empty($ts_start)) {
  // Expect: 24-05-2006 11:30 (dd-mm-yyyy hh:mm)
  $ts_start = getepoch($ts_start);
} elseif (isset($clean['from'])) {
  $ts_start = $clean['from'];
}

####################
# End timestamp
####################
if (!empty($ts_end)) {
  // Expect: 24-05-2006 11:30 (dd-mm-yyyy hh:mm)
  $ts_end = getepoch($ts_end);
} elseif (isset($clean['to'])) {
  $ts_end = $clean['to'];
}

####################
# Source
####################
if (isset($clean['source'])) {
  $smac = $clean['source'];
  add_to_sql("arp_alert.sourcemac = '$smac'", "where");
} elseif (isset($clean['sourcedis'])) {
  $smac = $clean['source'];
  add_to_sql("arp_alert.sourcemac = '$smac'", "where");
}

####################
# Target
####################
if (isset($tainted['target'])) {
  $target = $tainted['target'];
  $macregexp = '/^([a-zA-Z0-9]{2}:){5}[a-zA-Z0-9]{2}$/';
  $ipregexp = "/$v_ipregexp/";
  if (preg_match($macregexp, $target)) {
    add_to_sql("arp_alert.targetmac = '$target'", "where");
  } elseif (preg_match($ipregexp, $target)) {
    add_to_sql("arp_alert.targetip = '$target'", "where");
  } elseif (isset($tainted['targetdis'])) {
    $target = $tainted['targetdis'];
    $macregexp = '/^([a-zA-Z0-9]{2}:){5}[a-zA-Z0-9]{2}$/';
    $ipregexp = "/$v_ipregexp/";
    if (preg_match($macregexp, $target)) {
      add_to_sql("arp_alert.targetmac = '$target'", "where");
    } elseif (preg_match($ipregexp, $target)) {
      add_to_sql("arp_alert.targetip = '$target'", "where");
    }
  }
} elseif (isset($tainted['targetdis'])) {
  $target = $tainted['targetdis'];
  $macregexp = '/^([a-zA-Z0-9]{2}:){5}[a-zA-Z0-9]{2}$/';
  $ipregexp = "/$v_ipregexp/";
  if (preg_match($macregexp, $target)) {
    add_to_sql("arp_alert.targetmac = '$target'", "where");
  } elseif (preg_match($ipregexp, $target)) {
    add_to_sql("arp_alert.targetip = '$target'", "where");
  }
}

if ($s_access_sensor > 1) {
  echo "<form name='selectors' action='arplog.php' method='get'>\n";
    if ($s_access_sensor == 9) {
      if (isset($clean['org'])) {
        $q_org = $clean['org'];
      } else {
        $q_org = $s_org;
      }
      $sql_orgs = "SELECT id, organisation FROM organisations WHERE NOT organisation = 'ADMIN' ORDER BY organisation";
      $debuginfo[] = $sql_orgs;
      $result_orgs = pg_query($pgconn, $sql_orgs);
      echo "<select name='int_org' onChange='javascript: this.form.submit();'>\n";
        echo printOption(0, "All", $q_org) . "\n";
        while ($row = pg_fetch_assoc($result_orgs)) {
          $org_id = $row['id'];
          $organisation = $row['organisation'];
          echo printOption($org_id, $organisation, $q_org) . "\n";
        }
      echo "</select>&nbsp;\n";
    } else {
      $q_org = $s_org;
    }

    $sql_sensors = "SELECT id, keyname, vlanid FROM sensors WHERE organisation = $q_org AND NOT status = 3 ORDER BY keyname";
    $debuginfo[] = $sql_sensors;
    $result_sensors = pg_query($pgconn, $sql_sensors);
    echo "<select name='int_filter' onChange='javascript: this.form.submit();'>\n";
      echo printOption(0, "All", $filter) . "\n";
      while ($row = pg_fetch_assoc($result_sensors)) {
        $id = $row['id'];
        $keyname = $row['keyname'];
        $vlanid = $row['vlanid'];
        if ($vlanid != 0) {
          $keyname = "$keyname-$vlanid";
        }
        echo printOption($id, $keyname, $filter) . "\n";
      }
    echo "</select>&nbsp;\n";
  echo "</form>\n";
}

if ($s_access_sensor > 1) {
  add_to_sql("arp_alert.id", "select");
  add_to_sql("arp_alert", "table");
  add_to_sql("sensors", "table");
  add_to_sql("arp_alert.sensorid = sensors.id", "where");
  if ($s_admin != 1) {
    add_to_sql("sensors.organisation = $q_org", "where");
  }
  if ($filter != 0) {
    add_to_sql("arp_alert.sensorid = $filter", "where");
  }
  if (!empty($ts_start)) {
    add_to_sql("arp_alert.timestamp >= $ts_start", "where");
  }
  if (!empty($ts_end)) {
    add_to_sql("arp_alert.timestamp <= $ts_end", "where");
  }
  prepare_sql();

  # COUNT ROWS QUERY
  $sql_arp_alert_c = "SELECT $sql_select ";
  $sql_arp_alert_c .= " FROM $sql_from ";
  $sql_arp_alert_c .= " $sql_where ";
  $debuginfo[] = $sql_arp_alert_c;
  $result_arp_alert_c = pg_query($pgconn, $sql_arp_alert_c);
  $num_rows = pg_num_rows($result_arp_alert_c);

  if ($num_rows > 0) {
    $per_page = 20;
    $last_page = ceil($num_rows / $per_page);
    if (isset($clean['page'])) {
      $page_nr = $clean['page'];
      if ($page_nr <= $last_page) {
        $offset = ($page_nr - 1) * $per_page;
      } else {
        $page_nr = 1;
        $offset = 0;
      }
    } else {
      $page_nr = 1;
      $offset = 0;
    }
    $sql_limit = "LIMIT $per_page OFFSET $offset";
    $first_result = number_format($offset, 0, ".", ",");
    if ($first_result == 0) $first_result++;
    $last_result = ($offset + $per_page);
    if ($last_result > $num_rows) $last_result = $num_rows;
    $last_result = number_format($last_result, 0, ".", ",");

    # DATA QUERY
    add_to_sql("arp_alert.*", "select");
    add_to_sql("sensors.keyname", "select");
    add_to_sql("sensors.vlanid", "select");
    if ("$sorttype" != "") {
      add_to_sql("$sorttype $dir $sql_limit", "order");
    }
    prepare_sql();

    $sql_arp_alert = "SELECT $sql_select ";
    $sql_arp_alert .= " FROM $sql_from ";
    $sql_arp_alert .= " $sql_where ";
    $sql_arp_alert .= " ORDER BY $sql_order ";

    $debuginfo[] = $sql_arp_alert;
    $result_arp_alert = pg_query($pgconn, $sql_arp_alert);

    ### Navigation
    $nav = "Result page: ";
    $url = str_replace("&int_page=" . $clean["page"], "", $url);
    for ($i = ($page_nr - 3); $i <= ($page_nr + 3); $i++) {
      if (($i > 0) && ($i <= $last_page)) {
        if ($i == $page_nr) $nav .= "<b>&laquo;$i&raquo;</b>&nbsp;";
        else $nav .= "<a href=\"$url&int_page=$i\">$i</a>&nbsp;";
      }
    }
    $nav .= "<br />\n";
    if ($page_nr == 1) $nav .= "&lt;&lt;&nbsp;First&nbsp;&nbsp;";
    else $nav .= "<a href=\"$url&int_page=1\">&lt;&lt;&nbsp;First</a>&nbsp;&nbsp;";
    if ($page_nr == 1) $nav .= "&lt;&nbsp;Prev&nbsp;&nbsp;";
    else $nav .= "<a href=\"$url&int_page=" . ($page_nr - 1) . "\">&lt;&nbsp;Prev</a>&nbsp;&nbsp;";
    $nav .= "<a href=\"search.php\">Search</a>";
    if ($page_nr < $last_page) $nav .= "&nbsp;&nbsp;<a href=\"$url&int_page=" . ($page_nr + 1) . "\">Next&nbsp;&gt;</a>\n";
    else $nav .= "&nbsp;&nbsp;Next&nbsp;&gt;\n";
    if ($page_nr == $last_page) $nav .= "&nbsp;&nbsp;Last&nbsp;&gt;&gt;";
    else $nav .= "&nbsp;&nbsp;<a href=\"$url&int_page=$last_page\">Last&nbsp;&gt;&gt;</a>";

    if ($last_page > 1) $page_lbl = "pages";
    else $page_lbl = "page";
    echo "<p>Results <b>$first_result</b> - <b>$last_result</b> of <b>" . number_format($num_rows, 0, ".", ",") . "</b> in <b>" . number_format($last_page, 0, ".", ",") . "</b> $page_lbl.</p>\n";

    echo "<div id=\"lognav\" align=\"center\">$nav</div>\n";
    echo "<br />\n";

    echo "<table class='datatable' width='100%'>\n";
      echo "<tr class='datatr'>\n";
        echo "<td width='5%' class='dataheader'><a href='$url&sort=id$neworder'>ID</a></td>\n";
        echo "<td width='20%' class='dataheader'><a href='$url&sort=timestamp$neworder'>Timestamp</a></td>\n";
        echo "<td width='20%' class='dataheader'><a href='$url&sort=sourcemac$neworder'>Source</a></td>\n";
        echo "<td width='25%' class='dataheader'><a href='$url&sort=targetmac$neworder'>Target</a></td>\n";
        echo "<td width='15%' class='dataheader'><a href='$url&sort=sensorid$neworder'>Sensor</a></td>\n";
        echo "<td width='15%' class='dataheader'><a href='$url&sort=type$neworder'>Type</a></td>\n";
      echo "</tr>\n";

      while ($row_alert = pg_fetch_assoc($result_arp_alert)) {
        $id = $row_alert['id'];
        $targetmac = $row_alert['targetmac'];
        $targetip = $row_alert['targetip'];
        $sourcemac = $row_alert['sourcemac'];
        $sensor = $row_alert['keyname'];
        $vlanid = $row_alert['vlanid'];
        $ts = date("d-m-Y H:i:s", $row_alert['timestamp']);
        $type = $row_alert['type'];
        $type = $v_arp_alerts[$type];
        if ($vlanid != 0) {
          $sensor = "$sensor-$vlanid";
        }

        echo "<tr class='datatr'>\n";
          echo "<td class='datatd'>$id</td>\n";
          echo "<td class='datatd'>$ts</td>\n";
          echo "<td class='datatd'>$sourcemac</td>\n";
          echo "<td class='datatd'>$targetip ($targetmac)</td>\n";
          echo "<td class='datatd'>$sensor</td>\n";
          echo "<td class='datatd'>$type</td>\n";
        echo "</tr>\n";
      }
    echo "</table>\n";
  } else {
    echo "<font color='red'>No results found!</font>\n";
  }
}

# Close connection
pg_close($pgconn);
debug_sql();
footer();
?>

<?php $tab="5.5"; $pagetitle="System logs"; include("menu.php"); contentHeader(0,1); ?>
<?php

####################################
# SURFids 3.00                     #
# Changeset 002                    #
# 15-10-2009                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 002 Tuned SQL queries a bit (#185)
# 001 Initial release
#############################################

# Checking access
if ($s_admin != 1) {
  geterror(101);
  footer();
  pg_close($pgconn);
  exit;
}

if ($c_logsys_flexi == 1) {
  # Check for minified option
  if ($c_minified_enable == 1) {
    $min = "-min";
  } else {
    $min = "";
  }

  $address = getaddress();
  echo "<link rel='stylesheet' href='${address}include/flexi/flexigrid.css' />\n";
  echo "<script type='text/javascript' src='${address}include/flexi/flexigrid${min}.js'></script>\n";
  echo "<script type='text/javascript' src='${address}include/surfids.logsys${min}.js'></script>\n";
} else {  # START_NO_FLEXI

# Retrieving default values from $_COOKIE
$allowed_cookie = array(
        "strip_html_escape_error",
        "strip_html_escape_prefix",
        "strip_html_escape_dev",
		"int_sid",
		"int_level",
		"int_levelop",
		"int_prefixop",
		"int_devop",
		"int_errorop"
);
$check = extractvars($_COOKIE[$c_cookie_name], $allowed_cookie);
debug_input();

add_to_sql("syslog.*", "select");
add_to_sql("sensors.label", "select");
add_to_sql("sensors.id as sid", "select");
add_to_sql("syslog", "table");
#add_to_sql("sensors", "table");

#add_to_sql("sensors.keyname = syslog.keyname", "where");

$from = $_SESSION['s_from'];
$to = $_SESSION['s_to'];
add_to_sql("ts_to_epoch(timestamp) >= $from", "where");
add_to_sql("ts_to_epoch(timestamp) <= $to", "where");

$operators_ar = array(
    	'' => "=",
        0 => "!=",
        1 => "=",
        2 => ">",
        3 => "<"
);

# Default values
$sel_error = -1;
$sel_prefix = -1;
$sel_level = -1;
$sel_sid = -1;
$sel_dev = -1;

$sel_errorop = 1;
$sel_prefixop = 1;
$sel_levelop = 1;
$sel_devop = 1;

if (isset($clean['errorop'])) {
  $sel_errorop = $clean['errorop'];
}
$errorop = $operators_ar[$sel_errorop];

if (isset($clean['error'])) {
  $sel_error = $clean['error'];
  add_to_sql("error $errorop '$sel_error'", "where");
}

if (isset($clean['prefixop'])) {
  $sel_prefixop = $clean['prefixop'];
}
$prefixop = $operators_ar[$sel_prefixop];

if (isset($clean['prefix'])) {
  $sel_prefix = $clean['prefix'];
  add_to_sql("source $prefixop '$sel_prefix'", "where");
}

if (isset($clean['devop'])) {
  $sel_devop = $clean['devop'];
}
$devop = $operators_ar[$sel_devop];

if (isset($clean['dev'])) {
  $sel_dev = $clean['dev'];
  add_to_sql("device $devop '$sel_dev'", "where");
}

if (isset($clean['sid'])) {
  $sel_sid = $clean['sid'];
  if ($sel_sid != "unknown") {
    $sql_get = "SELECT keyname, vlanid FROM sensors WHERE id = $sel_sid ";
    $res_get = pg_query($pgconn, $sql_get);
    $row_get = pg_fetch_assoc($res_get);
    $keyname = $row_get['keyname'];
    $vlanid = $row_get['vlanid'];
    add_to_sql("keyname = '$keyname' AND (vlanid = $vlanid OR vlanid = 0) ", "where");
  } else {
    add_to_sql("syslog.keyname = 'unknown'", "where");
  }
}

if (isset($clean['levelop'])) {
  $sel_levelop = $clean['levelop'];
}
$levelop = $operators_ar[$sel_levelop];

if (isset($clean['level'])) {
  $sel_level = $clean['level'];
  add_to_sql("level $levelop '$sel_level'", "where");
}

add_to_sql("timestamp ASC", "order");

prepare_sql();
$sql_count = "SELECT COUNT(syslog.keyname) as total ";
$sql_count .= " FROM $sql_from ";
$sql_count .= "LEFT JOIN sensors ";
$sql_count .= " ON sensors.keyname = syslog.keyname AND sensors.vlanid = syslog.vlanid ";
$sql_count .= " $sql_where";
$debuginfo[] = $sql_count;
$result_count = pg_query($pgconn, $sql_count);
$row_count = pg_fetch_assoc($result_count);
$count = $row_count['total'];

$sql = "SELECT $sql_select FROM $sql_from ";
$sql .= "LEFT JOIN sensors ";
$sql .= " ON sensors.keyname = syslog.keyname AND sensors.vlanid = syslog.vlanid ";
$sql .= " $sql_where ORDER BY $sql_order LIMIT $c_logsys_max";
$debuginfo[] = $sql;
$result = pg_query($pgconn, $sql);

$sql_prefix = "SELECT DISTINCT source FROM syslog WHERE ts_to_epoch(timestamp) >= $from AND ts_to_epoch(timestamp) <= $to";
$debuginfo[] = $sql_prefix;
$result_prefix = pg_query($pgconn, $sql_prefix);

$sql_error = "SELECT DISTINCT error FROM syslog WHERE ts_to_epoch(timestamp) >= $from AND ts_to_epoch(timestamp) <= $to";
$debuginfo[] = $sql_error;
$result_error = pg_query($pgconn, $sql_error);

$sql_sid = "SELECT DISTINCT syslog.vlanid, syslog.keyname, sensors.id as sid FROM syslog ";
$sql_sid .= " LEFT JOIN sensors ";
$sql_sid .= " ON sensors.keyname = syslog.keyname AND sensors.vlanid = syslog.vlanid ";
$sql_sid .= " WHERE ts_to_epoch(timestamp) >= $from AND ts_to_epoch(timestamp) <= $to ";
$debuginfo[] = $sql_sid;
$result_sid = pg_query($pgconn, $sql_sid);

$sql_dev = "SELECT DISTINCT device FROM syslog WHERE ts_to_epoch(timestamp) >= $from AND ts_to_epoch(timestamp) <= $to ";
$debuginfo[] = $sql_dev;
$result_dev = pg_query($pgconn, $sql_dev);

echo "<div class='leftbig'>\n";
  echo "<div class='block'>\n";
    echo "<div class='actionBlock'>\n";
      echo "<div class='blockHeader'>" .$l['g_actions']. "</div>\n";
      echo "<div class='blockContent'>\n";
        echo "<form id='syslogfilter'>\n";
          echo "<table class='actiontable'>\n";
            echo "<tr>\n";
              echo "<td width='60'>" .$l['ly_level']. "</td>";
              echo "<td width='80'>";
                echo "<select name='int_levelop' id='int_levelop'>\n";
                  echo printOption(1, "IS", $sel_levelop);
                  echo printOption(0, "IS NOT", $sel_levelop);
                  echo printOption(2, ">", $sel_levelop);
                  echo printOption(3, "<", $sel_levelop);
                echo "</select>\n";
              echo "</td>\n";
              echo "<td>\n";
                echo "<select name='int_level' id='int_level'>\n";
                  echo printOption(-1, $l['g_all'], $sel_level);
                  foreach ($v_syslog_levels_ar as $key => $val) {
                    echo printOption($key, $val, $sel_level);
                   }
                echo "</select>\n";
              echo "</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td>" .$l['ly_source']. "</td>";
              echo "<td>\n";
                echo "<select name='int_prefixop' id='int_prefixop'>\n";
                  echo printOption(1, "IS", $sel_prefixop);
                  echo printOption(0, "IS NOT", $sel_prefixop);
                echo "</select>\n";
              echo "</td>\n";
              echo "<td>\n";
                echo "<select name='strip_html_escape_prefix' id='strip_html_escape_prefix'>\n";
                  echo printOption(-1, $l['g_all'], $sel_prefix);
                  while ($row = pg_fetch_assoc($result_prefix)) {
                    $pref = $row['source'];
                    echo printOption($pref, $pref, $sel_prefix);
                  }
                echo "</select><br />\n";
              echo "</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td>" .$l['ly_error']. "</td>";
              echo "<td>\n";
                echo "<select name='int_errorop' id='int_errorop'>\n";
                  echo printOption(1, "IS", $sel_errorop);
                  echo printOption(0, "IS NOT", $sel_errorop);
                echo "</select>\n";
              echo "</td>\n";
              echo "<td>\n";
                echo "<select name='strip_html_escape_error' id='strip_html_escape_error'>\n";
                  echo printOption(-1, $l['g_all'], $sel_error);
                  while ($row = pg_fetch_assoc($result_error)) {
                    $error = $row['error'];
                    echo printOption($error, $error, $sel_error);
                  }
                echo "</select><br />\n";
              echo "</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td>" .$l['ly_sensorid']. "</td>";
              echo "<td>IS\n";
#                echo "<select name='int_sidop' id='int_sidop'>\n";
#                  echo printOption(1, "IS", $sel_sidop);
#                  echo printOption(0, "IS NOT", $sel_sidop);
#                echo "</select>\n";
              echo "</td>\n";
              echo "<td>\n";
                echo "<select name='int_sid' id='int_sid'>\n";
                  echo printOption(-1, $l['g_all'], $sel_sid);
                  while ($row = pg_fetch_assoc($result_sid)) {
                    $sid = $row['sid'];
                    $keyname = $row['keyname'];
                    $vlanid = $row['vlanid'];
                    $label = $row['label'];
                    $sensor = sensorname($keyname, $vlanid, $label);
                    if ($sensor == "unknown") {
                      echo printOption("unknown", $sensor, $sel_sid);
                    } else {
                      echo printOption($sid, $sensor, $sel_sid);
                    }
                  }
                echo "</select><br />\n";
              echo "</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td>" .$l['ly_dev']. "</td>";
              echo "<td>\n";
                echo "<select name='int_devop' id='int_devop'>\n";
                  echo printOption(1, "IS", $sel_devop);
                  echo printOption(0, "IS NOT", $sel_devop);
                echo "</select>\n";
              echo "</td>\n";
              echo "<td>\n";
                echo "<select name='strip_html_escape_dev' id='strip_html_escape_dev' class='fleft'>\n";
                  echo printOption(-1, $l['g_all'], $sel_dev);
                  while ($row = pg_fetch_assoc($result_dev)) {
                    $dev = $row['device'];
                    echo printOption($dev, $dev, $sel_dev);
                  }
                echo "</select>\n";
              echo "<input type='button' value='" .$l['re_filter']. "' class='pbutton fright' onclick='browsedata(\"filter\", \"syslogfilter\", \"xml_logsys.php\", \"logsys\");' />\n";
              echo "<input type='button' value='" .$l['ly_default']. "' class='pbutton fright' onclick='setdefault(\"syslogfilter\", \"def_logsys.php\", \"default_logsys\");' />\n";
#              echo "<input type='button' value='" .$l['ly_default']. "' class='pbutton fright' onclick='GB_show(\"test\",\"popup_login.php\",470,600);' />\n";
              echo "</td>\n";
            echo "</tr>\n";
#            echo "<tr><td colspan='3'>\n";
#              echo "<input type='button' value='" .$l['re_filter']. "' class='pbutton' onclick='browsedata(\"filter\", \"syslogfilter\", \"xml_logsys.php\", \"logsys\");' />\n";
#            echo "</td></tr>\n";
            echo "<input type='hidden' value='0' name='int_offset' id='int_offset' />\n";
            echo "<input type='hidden' value='$c_logsys_max' name='int_limit' id='int_limit' />\n";
            echo "<input type='hidden' value='$count' id='int_total' />\n";
            echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
          echo "</table>\n";
        echo "</form>\n";
      echo "</div>\n"; #</blockContent>
      echo "<div class='blockFooter'></div>\n";
    echo "</div>\n"; #</actionBlock>
  echo "</div>\n"; #</block>
echo "</div>\n"; #</leftsmall>
} # STOP_NO_FLEXI

echo "<div class='centerbig'>\n";
  echo "<div class='block'>\n";
    echo "<div class='dataBlock'>\n";
      echo "<div class='blockHeader'>" .$l['me_syslog']. "</div>\n";
      echo "<div class='blockContent'>\n";
        if ($c_logsys_flexi == 1) {
          echo "<div id='flexi'></div>\n";
        } else {
          echo "<table class='datatable'>\n";
            echo "<tr id='headerrow'>\n";
              echo "<th width='40'>" .$l['ly_level']. "</th>\n";
              echo "<th width='120'>" .$l['ly_ts']. "</th>\n";
              echo "<th width='120'>" .$l['ly_source']. "</th>\n";
              echo "<th width='90'>" .$l['ly_error']. "</th>\n";
              echo "<th width='340'>" .$l['ly_args']. "</th>\n";
              echo "<th width='50'>" .$l['g_sensor']. "</th>\n";
              echo "<th width='50'>" .$l['ly_dev']. "</th>\n";
            echo "</tr>\n";
            while ($row = pg_fetch_assoc($result)) {
              $level = $v_syslog_levels_ar[$row['level']];
              $ts = strtotime($row['timestamp']);
              $ts = date($c_date_format, $ts);
              $source = $row['source'];
              $pid = $row['pid'];
              $error = $row['error'];
              $args = $row['args'];
              $sid = $row['sid'];
              $tap = $row['device'] ? $row['device'] : "unknown";
              $vlanid = $row['vlanid'];
              $keyname = $row['keyname'];
              $sensor = sensorname($keyname, $vlanid);

              echo "<tr class='syslogrow'>";
                echo "<td class='syslog_$level'>$level</td>\n";
                echo "<td>$ts</td>\n";
                echo "<td>$source ($pid)</td>\n";
                echo "<td>$error</td>\n";
                echo "<td>$args</td>\n";
                if ($sensor != "unknown") {
                  echo "<td><a href='sensordetails.php?int_sid=$sid'>$sensor</a></td>\n";
                } else {
                  echo "<td>$sensor</td>\n";
                }
                echo "<td>$tap</td>\n";
              echo "</tr>";
            }
            echo "<tr id='edit_row'>\n";
              echo "<td colspan='6' class='acenter'>";
                echo "<a onclick='browsedata(\"start\", \"syslogfilter\", \"xml_logsys.php\", \"logsys\");'><img src='images/new_arrow_stop_left.png' height=16 width=16 /></a>";
                echo "<a onclick='browsedata(\"prev\", \"syslogfilter\", \"xml_logsys.php\", \"logsys\");'><img src='images/new_arrow_left.png' height=16 width=16 /></a>";
                echo "<span id='pagecounter'>0 - 20 from $count</span>";
                echo "<a onclick='browsedata(\"next\", \"syslogfilter\", \"xml_logsys.php\", \"logsys\");'><img src='images/new_arrow_right.png' height=16 width=16 /></a>";
                echo "<a onclick='browsedata(\"end\", \"syslogfilter\", \"xml_logsys.php\", \"logsys\");'><img src='images/new_arrow_stop_right.png' height=16 width=16 /></a>";
              echo "</td>\n";
            echo "</tr>\n";
          echo "</table>\n";
        }
      echo "</div>\n"; #</blockContent>
      echo "<div class='blockFooter'></div>\n";
    echo "</div>\n"; #</dataBlock>
  echo "</div>\n"; #</block>
echo "</div>\n"; #</leftmed>

debug_sql();
#pg_close($pgconn);
?>
<?php footer(); ?>

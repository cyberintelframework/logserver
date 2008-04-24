<?php $tab="2.6"; $pagetitle="Report - New"; include("menu.php"); contentHeader(0); ?>
<?php

####################################
# SURFnet IDS 2.10.00              #
# Changeset 004                    #
# 10-04-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 004 Added cookie defaults
# 003 Added UTC timestamp support
# 002 Added option to always send the report
# 001 Added language support
#############################################

# Retrieving cookie variables from $_COOKIE[SURFids]
$allowed_cookie = array(
                "int_dutc"
);
$check = extractvars($_COOKIE[SURFids], $allowed_cookie);
debug_input();

# Retrieving posted variables from $_GET
$allowed_get = array(
        "int_userid",
		"int_m"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

if (isset($clean['m'])) {
  $m = $clean['m'];
  geterror($m);
}

if (isset($clean['dutc'])) {
  $d_utc = $clean['dutc'];
} else {
  $d_utc = 1;
}

# Make sure all access rights are correct
if (isset($clean['userid'])) {
  $user_id = $clean['userid'];
  if ($s_access_user < 1) {
    header("location: index.php");
    pg_close($pgconn);
    exit;
  } elseif ($s_access_user < 2) {
    $user_id = $s_userid;
  } elseif ($s_access_user < 9) {
    $sql_login = "SELECT id FROM login WHERE organisation = $s_org AND id = $user_id";
    $debuginfo[] = $sql_login;
    $result_login = pg_query($pgconn, $sql_login);
    $numrows_login = pg_num_rows($result_login);
    if ($numrows_login = 0) {
      $m = 101;
      geterror($m);
      footer();
      exit;
    } else {
      $user_id = $clean['userid'];
    }
  } else {
    $user_id = $clean['userid'];
  }
} else {
  $user_id = $s_userid;
}

echo "<div class='leftmed'>\n";
  echo "<form id='reportform' name='reportform' action='report_add.php' method='post'>\n";
  echo "<input type='hidden' name='int_userid' value='$user_id' />\n";
  echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
  echo "<div class='block'>\n";
    echo "<div class='dataBlock'>\n";
      echo "<div class='blockHeader'>" .$l['re_mailopts']. "</div>\n";
      echo "<div class='blockContent'>\n";
        echo "<table class='datatable'>\n";
          echo "<tr>\n";
            echo "<td width='100'>" .$l['re_subject']. ":</td>\n";
            echo "<td width='200'><input type='text' name='strip_html_escape_subject' /></td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>" .$l['re_mailprio']. ":</td>\n";
            echo "<td>";
              echo "<select name='int_priority'>\n";
                foreach ($v_mail_priority_ar as $key => $val) {
                  echo printOption($key, $val, 2);
                }
              echo "</select>\n";
            echo "</td>\n";
          echo "</tr>\n";
        echo "</table>\n";
      echo "</div>\n"; #</blockContent>
      echo "<div class='blockFooter'></div>\n";
    echo "</div>\n"; #</dataBlock>
  echo "</div>\n"; #</block>

  echo "<div class='block'>\n";
    echo "<div class='dataBlock'>\n";
      echo "<div class='blockHeader'>" .$l['re_reportopts']. "</div>\n";
      echo "<div class='blockContent'>\n";
        echo "<table class='datatable'>\n";
          echo "<tr>\n";
            echo "<td width='100'>Sensor: </td>\n";
            echo "<td width='200'>\n";
              echo "<select name='int_sensorid'>\n";
                echo printOption(-1, $l['re_allsensors'], $sensor_id);
                if ($s_admin == 1) {
                  $sql = "SELECT * FROM sensors WHERE NOT status = 3 ORDER BY keyname ASC, vlanid ASC";
                } else {
                  $sql = "SELECT * FROM sensors WHERE organisation = '$s_org' AND NOT status = 3 ORDER BY keyname ASC, vlanid ASC";
                }
                $debuginfo[] = $sql;
                $query = pg_query($sql);
                while ($sensor_data = pg_fetch_assoc($query)) {
                  $keyname = $sensor_data["keyname"];
                  $vlanid = $sensor_data["vlanid"];
                  $sensor = sensorname($keyname, $vlanid);
                  if ($vlanid != 0) {
                    echo printOption($sensor_data["id"], $sensor, $sensor_id);
                  } else {
                    echo printOption($sensor_data["id"], $sensor, $sensor_id);
                  }
                }
              echo "</select>\n";
            echo "</td>\n";
          echo "</tr>\n";
          echo "<tr>";
            echo "<td width='100'>" .$l['re_reptemp']. ":</td>\n";
            echo "<td width='200'>";
              echo "<select id='int_template' name='int_template' onchange='javascript: sh_mailtemp(this.value);'>\n";
                foreach ($v_mail_template_ar as $key=>$val) {
                  if ($key != 6) {
                    echo printOption($key, $val, -1);
                  }
                }
              echo "</select>\n";
            echo "</td>\n";
          echo "</tr>\n";
          echo "<tr style='display:;' id='repdetail' name='repdetail'>";
            echo "<td width='100'>" .$l['re_reptype']. ":</td>\n";
            echo "<td width='200'>";
              echo "<select name='int_detail' id='int_detail' onchange='javascript: sh_mailreptype(this.value);'>\n";
                foreach ($v_mail_detail_ar as $key=>$val) {
                  echo printOption($key, $val, -1);
                }
              echo "</select>\n";
            echo "</td>\n";
          echo "</tr>\n";
          echo "<tr style='display: none;' id='filter' name='filter'>";
            echo "<td width='100'>" .$l['re_filter']. ":</td>\n";
            echo "<td width='200'>";
              echo "<select name='int_filter' id='int_filter'>\n";
                echo printOption(0, $l['re_exown'], -1);
                echo printOption(1, $l['re_incown'], -1);
              echo "</select>\n";
            echo "</td>\n";
          echo "</tr>\n";
          echo "<tr style='display:none;' id='srepdetail' name='srepdetail'>";
            echo "<td width='100'>" .$l['re_reptype']. ":</td>\n";
            echo "<td width='200'>";
              echo "<select name='int_sdetail' id='int_sdetail'>\n";
                foreach ($v_mail_sdetail_ar as $key=>$val) {
                  echo printOption($key, $val, -1);
                }
              echo "</select>\n";
            echo "</td>\n";
          echo "</tr>\n";
          echo "<tr style='display:;' id='attack_sev' name='attack_sev'>";
            echo "<td width='100'>" .$l['ls_sev']. ":</td>\n";
            echo "<td width='200'>";
              echo "<select name='int_sevattack'>\n";
                echo printOption(-1, $l['re_allsev'], -1);
                foreach ($v_severity_ar as $key=>$val) {
                  echo printOption($key, $val, -1);
                }
              echo "</select>\n";
            echo "</td>\n";
          echo "</tr>\n";
          echo "<tr style='display:;' id='timestamps' name='timestamps'>";
            echo "<td width='100'>" .$l['re_timeformat']. ":</td>\n";
            echo "<td width='200'>";
              echo "<select name='int_utc' id='int_filter'>\n";
                foreach ($v_timestamp_format_ar as $key=>$val) {
                  echo printOption($key, $val, $d_utc);
                }
              echo "</select>\n";
            echo "</td>\n";
          echo "</tr>\n";
          echo "<tr style='display: none;' id='sensor_sev' name='sensor_sev'>";
            echo "<td width='100'>" .$l['ls_sev']. ":</td>\n";
            echo "<td width='200'>";
              echo "<select name='int_sevsensor'>\n";
                echo printOption(-1, $l['re_allsev'], -1);
                foreach ($v_sensor_sev_ar as $key=>$val) {
                  echo printOption($key, $val, -1);
                }
              echo "</select>\n";
            echo "</td>\n";
          echo "</tr>\n";
        echo "</table>\n";
      echo "</div>\n"; #</blockContent>
      echo "<div class='blockFooter'></div>\n";
    echo "</div>\n"; #</dataBlock>
  echo "</div>\n"; #</block>

  echo "<div id='timeandthresh' name='timeandthresh' style='display: ;'>\n";
    echo "<div id='timeoptions' name='timeoptions' style='display: ;'>\n";
      echo "<div class='block'>\n";
        echo "<div class='dataBlock'>\n";
          echo "<div class='blockHeader'>" .$l['re_timeopts']. "</div>\n";
          echo "<div class='blockContent'>\n";
            echo "<table class='datatable'>\n";
              echo "<tr id='attack_time' name='attack_time' style='display: ;'>";
                echo "<td width='100'>" .$l['re_freq']. ":</td>\n";
                echo "<td width='200'>";
                  echo "<select name='int_freqattack' onchange='javascript: sh_mailfreq(this.value);'>\n";
                    foreach ($v_mail_frequency_attacks_ar as $key=>$val) {
                      echo printOption($key, $val, -1);
                    }
                  echo "</select>\n";
                echo "</td>\n";
              echo "</tr>\n";
              echo "<tr id='sensor_time' name='sensor_time' style='display: none;'>";
                echo "<td width='100'>" .$l['re_freq']. ":</td>\n";
                echo "<td width='200'>";
                  echo "<select name='int_freqsensor' onchange='javascript: sh_mailfreq(this.value);'>\n";
                    foreach ($v_mail_frequency_sensors_ar as $key=>$val) {
                      echo printOption($key, $val, -1);
                    }
                  echo "</select>\n";
                echo "</td>\n";
              echo "</tr>\n";
              echo "<tr id='daily_freq' name='daily_freq' style='display: none;'>\n";
                echo "<td>" .$l['re_time']. ":</td>\n";
                echo "<td>";
                  echo "<select name='int_intervalday'>\n";
                    for ($i = 0; $i < 24; $i++) {
                      $time = "$i:00 " .$l['g_hour_l'];
                      if ($i < 10) {
                        $time = "0" . $time;
                      }
                      echo printOption($i, $time, $interval_day);
                    }
                  echo "</select>\n";
                echo "</td>\n";
              echo "</tr>\n";
              echo "<tr id='weekly_freq' name='weekly_freq' style='display: none;'>";
                echo "<td>" .$l['re_day']. ":</td>\n";
                echo "<td>";
                  echo "<select name='int_intervalweek'>\n";
                    $j = (4 * 86400); // monday
                    for ($i = 1; $i < 8; $i++) {
                      // use php's date function to print the day
                      echo printOption($i, date("l", $j), $interval_week);
                      $j += 86400; // add one day
                    }
                  echo "</select>\n";
                echo "</td>\n";
              echo "</tr>\n";
              echo "<tr id='always'>\n";
                echo "<td>" .$l['re_always']. ":</td>\n";
                echo "<td>" .printCheckBox("", "int_always", 1, 0). "</td>\n";
              echo "</tr>\n";
            echo "</table>\n";
          echo "</div>\n"; #</blockContent>
          echo "<div class='blockFooter'></div>\n";
        echo "</div>\n"; #</dataBlock>
      echo "</div>\n"; #</block>
    echo "</div>\n"; #</timeoptions>

    echo "<div id='thresh_freq' name='thresh_freq' style='display: none;'>\n";
      echo "<div class='block'>\n";
        echo "<div class='dataBlock'>\n";
          echo "<div class='blockHeader'>" .$l['re_threshopts']. "</div>\n";
          echo "<div class='blockContent'>\n";
            echo "<table class='datatable'>\n";
              echo "<tr>\n";
                echo "<td width='100'>" .$l['re_op']. ":</td>\n";
                echo "<td width='200'>";
                  echo "<select name='int_operator'>\n";
                    foreach ($v_mail_operator_ar as $key => $val) {
                      echo printOption($key, $val, 0);
                    }
                  echo "</select>\n";
                echo "</td>\n";
              echo "</tr>\n";
              echo "<tr>\n";
                echo "<td width='100'>" .$l['re_thresh_amount']. ":</td>\n";
                echo "<td width='200'><input type='text' name='int_threshold' /></td>\n";
              echo "</tr>\n";
              echo "<tr>\n";
                echo "<td width='100'>" .$l['re_timespan']. ":</td>\n";
                echo "<td width='200'>";
                  echo "<select name='int_intervalthresh'>\n";
                    foreach ($v_mail_timespan_ar as $key => $val) {
                      echo printOption($key, $val, 0);
                    }
                  echo "</select>\n";
                echo "</td>\n";
              echo "</tr>\n";
            echo "</table>\n";
          echo "</div>\n"; #</blockContent>
          echo "<div class='blockFooter'></div>\n";
        echo "</div>\n"; #</dataBlock>
      echo "</div>\n"; #</block>
    echo "</div>\n"; #</thresh_freq>
  echo "</div>\n"; #</timeandthresh>

  echo "<div class='block'>\n";
    echo "<div class='dataBlock'>\n";
      echo "<div class='blockHeader'></div>\n";
      echo "<div class='blockContent'>\n";
        echo "<table class='datatable'>\n";
          echo "<tr>\n";
            echo "<td>";
              echo "<input type='submit' name='submit' value='" .$l['g_add']. "' class='button' />";
            echo "</td>\n";
          echo "</tr>\n";
        echo "</table>\n";
      echo "</div>\n"; #</blockContent>
      echo "<div class='blockFooter'></div>\n";
    echo "</div>\n"; #</dataBlock>
  echo "</div>\n"; #</block>
  echo "</form>\n";
echo "</div>\n"; #</leftmed>

footer();
?>

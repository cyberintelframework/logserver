<?php $tab="2.6"; $pagetitle="Report - Edit"; include("menu.php"); contentHeader(0); ?>
<?php

####################################
# SURFnet IDS 2.10.00              #
# Changeset 003                    #
# 04-04-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 003 Added UTC timestamp support
# 002 Added option to always send the report
# 001 Added language support
#############################################

# Retrieving posted variables from $_GET
$allowed_get = array(
	        "int_userid",
		"int_rcid",
		"int_m"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

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
    if ($numrows_login == 0) {
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

if (!isset($clean['rcid'])) {
  $m = 135;
  header("location: myreports.php?int_m=$m");
  pg_close($pgconn);
  exit;
} else {
  $reportid = $clean['rcid'];
}

# Showing info/error messages if any
if (isset($clean['m'])) {
  $m = $clean['m'];
  geterror($m);
}

$sql = "SELECT * FROM report_content WHERE id = '$reportid'";
$debuginfo[] = $sql;
$result = pg_query($pgconn, $sql);
$numrows = pg_num_rows($result);

if ($numrows > 0) {
  $row = pg_fetch_assoc($result);

  $subject = $row['subject'];
  $prio = $row['priority'];
  $sensorid = $row['sensor_id'];
  $temp = $row['template'];
  $sev = $row['severity'];
  $freq = $row['frequency'];
  $interval = $row['interval'];
  $operator = $row['operator'];
  $threshold = $row['threshold'];
  $active = $row['active'];
  $detail = $row['detail'];
  $always = $row['always'];
  $utc = $row['utc'];

  echo "<div class='leftmed'>\n";
  echo "<form id='reportform' name='reportform' action='report_save.php' method='post'>\n";
    echo "<input type='hidden' name='int_userid' value='$user_id' />\n";
    echo "<input type='hidden' name='int_rcid' value='$reportid' />\n";
    echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";

    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockHeader'>" .$l['re_mailopts']. "</div>\n";
        echo "<div class='blockContent'>\n";
          echo "<table class='datatable'>\n";
            echo "<tr>\n";
              echo "<td width='100'>" .$l['re_subject']. "</td>\n";
              echo "<td width='200'><input type='text' name='strip_html_escape_subject' value='$subject' /></td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td>" .$l['re_mailprio']. "</td>\n";
              echo "<td>";
                echo "<select name='int_priority'>\n";
                  foreach ($v_mail_priority_ar as $key => $val) {
                    echo printOption($key, $val, $prio);
                  }
                echo "</select>\n";
              echo "</td>\n";
            echo "</tr>\n";
          echo "</table>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #<dataBlock>
    echo "</div>\n"; #<block>

    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockHeader'>" .$l['re_reportopts']. "</div>\n";
        echo "<div class='blockContent'>\n";
          echo "<table class='datatable'>\n";
            echo "<tr>\n";
              echo "<td width='100'>" .$l['g_status']. ":</td>\n";
              echo "<td width='200'>\n";
                echo "<select name='bool_active'>\n";
                  echo printOption("t", $l['mr_active'], $active);
                  echo printOption("f", $l['mr_inactive'], $active);
                echo "</select>\n";
              echo "</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td>Sensor: </td>\n";
              echo "<td>\n";
                echo "<select name='int_sensorid'>\n";
                  echo printOption(-1, $l['re_allsensors'], $sensor);
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
                    echo printOption($sensor_data["id"], $sensor, $sensorid);
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
                      echo printOption($key, $val, $temp);
                    }
                  }
                echo "</select>\n";
              echo "</td>\n";
            echo "</tr>\n";
            if ($temp == 4 || $temp == 5 || $temp == 7) {
              echo "<tr id='repdetail' name='repdetail' style='display:none;'>";
            } else {
              echo "<tr id='repdetail' name='repdetail' style='display:;'>";
            }
              echo "<td width='100'>" .$l['re_reptype']. ":</td>\n";
              echo "<td width='200'>";
                echo "<select id='int_detail' name='int_detail' onchange='javascript: sh_mailreptype(this.value);'>\n";
                  foreach ($v_mail_detail_ar as $key=>$val) {
                    echo printOption($key, $val, $detail);
                  }
                echo "</select>\n";
              echo "</td>\n";
            echo "</tr>\n";

            if ($detail == 4 || $detail == 5) {
              echo "<tr style='display:;' id='filter' name='filter'>";
            } else {
              echo "<tr style='display: none;' id='filter' name='filter'>";
            }
              echo "<td width='100'>" .$l['re_filter']. ":</td>\n";
              echo "<td width='200'>";
                echo "<select name='int_filter' id='int_filter'>\n";
                  echo printOption(0, $l['re_exown'], $sev);
                  echo printOption(1, $l['re_incown'], $sev);
                echo "</select>\n";
              echo "</td>\n";
            echo "</tr>\n";
            if ($temp == 4) {
              echo "<tr style='display:;' id='srepdetail' name='srepdetail'>";
            } else {
              echo "<tr style='display:none;' id='srepdetail' name='srepdetail'>";
            }
              echo "<td width='100'>" .$l['re_reptype']. ":</td>\n";
              echo "<td width='200'>";
                echo "<select id='int_sdetail' name='int_sdetail'>\n";
                  foreach ($v_mail_sdetail_ar as $key=>$val) {
                    echo printOption($key, $val, $detail);
                  }
                echo "</select>\n";
              echo "</td>\n";
            echo "</tr>\n";
            if ($temp == 1 || $temp == 2) {
              if ($detail == 4 || $detail == 5) {
                echo "<tr style='display: none;' id='attack_sev' name='attack_sev'>";
              } else {
                echo "<tr style='display: ;' id='attack_sev' name='attack_sev'>";
              }
            } else {
              echo "<tr style='display: none;' id='attack_sev' name='attack_sev'>";
            }
              echo "<td width='100'>" .$l['ls_sev']. ":</td>\n";
              echo "<td width='200'>";
                echo "<select name='int_sevattack'>\n";
                  echo printOption(-1, $l['re_allsev'], $sev);
                  foreach ($v_severity_ar as $key=>$val) {
                    echo printOption($key, $val, $sev);
                  }
                echo "</select>\n";
              echo "</td>\n";
            echo "</tr>\n";
            if ($detail == 4 || $detail == 5) {
              echo "<tr style='display:;' id='timestamps' name='timestamps'>";
            } else {
              echo "<tr style='display: none;' id='timestamps' name='timestamps'>";
            }
              echo "<td width='100'>" .$l['re_timeformat']. ":</td>\n";
              echo "<td width='200'>";
                echo "<select name='int_utc' id='int_filter'>\n";
                  foreach ($v_timestamp_format_ar as $key=>$val) {
                    echo printOption($key, $val, $utc);
                  }
                echo "</select>\n";
              echo "</td>\n";
            echo "</tr>\n";
            if ($temp == 4) {
              echo "<tr style='display: ;' id='sensor_sev' name='sensor_sev'>";
            } else {
              echo "<tr style='display: none;' id='sensor_sev' name='sensor_sev'>";
            }
              echo "<td width='100'>" .$l['ls_sev']. ":</td>\n";
              echo "<td width='200'>";
                echo "<select name='int_sevsensor'>\n";
                  echo printOption(-1, $l['re_allsev'], $sev);
                  foreach ($v_sensor_sev_ar as $key=>$val) {
                    echo printOption($key, $val, $sev);
                  }
                echo "</select>\n";
              echo "</td>\n";
            echo "</tr>\n";
          echo "</table>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #<dataBlock>
    echo "</div>\n"; #<block>

    if ($detail < 10 && $temp < 5) {
      echo "<div id='timeandthresh' name='timeandthresh' style='display: ;'>\n";
    } else {
      echo "<div id='timeandthresh' name='timeandthresh' style='display: none;'>\n";
    }
      echo "<div id='timeoptions' name='timeoptions' style='display: ;'>\n";
        echo "<div class='block'>\n";
          echo "<div class='dataBlock'>\n";
            echo "<div class='blockHeader'>" .$l['re_timeopts']. "</div>\n";
            echo "<div class='blockContent'>\n";
              echo "<table class='datatable'>\n";
                if ($temp == 1 || $temp == 2) {
                  echo "<tr id='attack_time' name='attack_time' style='display: ;'>";
                } else {
                  echo "<tr id='attack_time' name='attack_time' style='display: none;'>";
                }
                  echo "<td width='100'>" .$l['re_freq']. ":</td>\n";
                  echo "<td width='200'>";
                    echo "<select name='int_freqattack' onchange='javascript: sh_mailfreq(this.value);'>\n";
                      foreach ($v_mail_frequency_attacks_ar as $key=>$val) {
                        echo printOption($key, $val, $freq);
                      }
                    echo "</select>\n";
                  echo "</td>\n";
                echo "</tr>\n";
                if ($temp == 4) {
                  echo "<tr id='sensor_time' name='sensor_time' style='display: ;'>";
                } else {
                  echo "<tr id='sensor_time' name='sensor_time' style='display: none;'>";
                }
                  echo "<td width='100'>" .$l['re_freq']. ":</td>\n";
                  echo "<td width='200'>";
                    echo "<select name='int_freqsensor' onchange='javascript: sh_mailfreq(this.value);'>\n";
                      foreach ($v_mail_frequency_sensors_ar as $key=>$val) {
                        echo printOption($key, $val, $freq);
                      }
                    echo "</select>\n";
                  echo "</td>\n";
                echo "</tr>\n";
                if ($freq == 2) {
                  echo "<tr id='daily_freq' name='daily_freq' style='display: ;'>\n";
                } else {
                  echo "<tr id='daily_freq' name='daily_freq' style='display: none;'>\n";
                }
                  echo "<td>" .$l['re_time']. ":</td>\n";
                  echo "<td>";
                    echo "<select name='int_intervalday'>\n";
                      for ($i = 0; $i < 24; $i++) {
                        $time = "$i:00 " .$l['g_hour_l'];
                        if ($i < 10) {
                          $time = "0" . $time;
                        }
                        echo printOption($i, $time, $interval);
                      }
                    echo "</select>\n";
                  echo "</td>\n";
                echo "</tr>\n";
                if ($freq == 3) {
                  echo "<tr id='weekly_freq' name='weekly_freq' style='display: ;'>";
                } else {
                  echo "<tr id='weekly_freq' name='weekly_freq' style='display: none;'>";
                }
                  echo "<td>" .$l['re_day']. ":</td>\n";
                  echo "<td>";
                    echo "<select name='int_intervalweek'>\n";
                      $j = (4 * 86400); // monday
                      for ($i = 1; $i < 8; $i++) {
                        // use php's date function to print the day
                        echo printOption($i, date("l", $j), $interval);
                        $j += 86400; // add one day
                      }
                    echo "</select>\n";
                  echo "</td>\n";
                echo "</tr>\n";
                if ($freq == 1 || $freq == 2 || $freq == 3) {
                  echo "<tr id='always' style='display: ;'>\n";
                } else {
                  echo "<tr id='always' style='display: none;'>\n";
                }
                  echo "<td>" .$l['re_always']. ":</td>\n";
                  echo "<td>" .printCheckBox("", "int_always", 1, $always). "</td>\n";
                echo "</tr>\n";
              echo "</table>\n";
            echo "</div>\n"; #</blockContent>
            echo "<div class='blockFooter'></div>\n";
          echo "</div>\n"; #<dataBlock>
        echo "</div>\n"; #<block>
      echo "</div>\n"; #<timeoptions>

      if ($freq == 4) {
        echo "<div id='thresh_freq' name='thresh_freq' style='display: ;'>\n";
      } else {
        echo "<div id='thresh_freq' name='thresh_freq' style='display: none;'>\n";
      }
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
                        echo printOption($key, $val, $operator);
                      }
                    echo "</select>\n";
                  echo "</td>\n";
                echo "</tr>\n";
                echo "<tr>\n";
                  echo "<td width='100'>" .$l['re_thresh_amount']. ":</td>\n";
                  echo "<td width='200'><input type='text' name='int_threshold' value='$threshold' /></td>\n";
                echo "</tr>\n";
                echo "<tr>\n";
                  echo "<td width='100'>" .$l['re_timespan']. ":</td>\n";
                  echo "<td width='200'>";
                    echo "<select name='int_intervalthresh'>\n";
                      foreach ($v_mail_timespan_ar as $key => $val) {
                        echo printOption($key, $val, $interval);
                      }
                    echo "</select>\n";
                  echo "</td>\n";
                echo "</tr>\n";
              echo "</table>\n";
            echo "</div>\n"; #</blockContent>
            echo "<div class='blockFooter'></div>\n";
          echo "</div>\n"; #<dataBlock>
        echo "</div>\n"; #<block>
      echo "</div>\n"; #</thresh_freq>
    echo "</div>\n"; #</timeandthresh>

    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
#        echo "<div class='blockHeader'></div>\n";
        echo "<div class='blockContent'>\n";
#          echo "<table class='datatable'>\n";
#            echo "<tr>\n";
#              echo "<td>";
                echo "<input type='submit' name='submit' value='" .$l['g_update']. "' class='button' />";
#              echo "</td>\n";
#            echo "</tr>\n";
#          echo "</table>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #<dataBlock>
    echo "</div>\n"; #<block>
  echo "</form>\n";
  echo "</div>\n"; #</leftmed>
  debug_sql();
  footer();
} else {
  $m = 135;
  header("location: myreports.php?int_m=$m");
  pg_close($pgconn);
  exit;
}
?>

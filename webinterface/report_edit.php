<?php include("menu.php"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.05                  #
# 19-03-2007                       #
# Peter Arts                       #
# Modified by Kees Trippelvitz     #
####################################

#############################################
# Changelog:
# 1.04.05 Added hash check
# 1.04.04 Fixed a bug with weekday count
# 1.04.03 Changed data input handling
# 1.04.02 Changed debug stuff
# 1.04.01 Released as 1.04.01
# 1.03.01 Split up report.php into seperate files
#############################################

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_userid = intval($_SESSION['s_userid']);
$s_hash = md5($_SESSION['s_hash']);
$s_access = $_SESSION['s_access'];
$s_access_user = intval($s_access{2});

### Extracting GET variables
$allowed_get = array(
                "int_userid",
		"int_rcid"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

// Make sure all access rights are correct
if (isset($clean['userid'])) {
  $user_id = $clean['userid'];
  if ($s_access_user < 1) {
    header("location: index.php");
    pg_close($pgconn);
    exit;
  } elseif ($s_access_user < 2) {
    $user_id = $s_userid;
  } elseif ($s_access_user < 9) {
    $sql_login = "SELECT * FROM login WHERE organisation = $s_org AND id = $user_id";
    $debuginfo[] = $sql_login;
    $result_login = pg_query($pgconn, $sql_login);
    $numrows_login = pg_num_rows($result_login);
    if ($numrows_login == 0) {
      $m = geterror(91);
      echo $m;
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
  $m = 91;
  header("location: mailadmin.php?int_m=$m");
  pg_close($pgconn);
  exit;
} else {
  $reportid = $clean['rcid'];
}

$sql = "SELECT * FROM report_content WHERE id = '$reportid'";
$debuginfo[] = $sql;
$result = pg_query($pgconn, $sql);
$numrows = pg_num_rows($result);

if ($numrows > 0) {
  $row = pg_fetch_assoc($result);

  $subject = $row['subject'];
  $prio = $row['priority'];
  $sensor = $row['sensor'];
  $temp = $row['template'];
  $sev = $row['severity'];
  $freq = $row['frequency'];
  $interval = $row['interval'];
  $operator = $row['operator'];
  $threshold = $row['threshold'];

  echo "<form id='reportform' name='reportform' action='report_save.php?int_userid=$user_id' method='post'>\n";
  echo "<h4>Mail options</h4>\n";
  echo "<table class='datatable'>\n";
    echo "<tr class='datatr'>\n";
      echo "<td class='datatd' width='100'>Subject</td>\n";
      echo "<td class='datatd' width='200'><input type='text' name='strip_html_escape_subject' value='$subject' /></td>\n";
    echo "</tr>\n";
    echo "<tr class='datatr'>\n";
      echo "<td class='datatd'>Priority</td>\n";
      echo "<td class='datatd'>";
        echo "<select name='int_priority'>\n";
          foreach ($v_mail_priority_ar as $key => $val) {
            echo printOption($key, $val, $prio);
          }
        echo "</select>\n";
      echo "</td>\n";
    echo "</tr>\n";
  echo "</table>\n";

  echo "<h4>Report options</h4>\n";
  echo "<table class='datatable'>\n";
    echo "<tr class='datatr'>\n";
      echo "<td class='datatd' width='100'>Sensor: </td>\n";
      echo "<td class='datatd' width='200'>\n";
        echo "<select name='int_sensorid'>\n";
          echo printOption(-1, "All sensors", $sensor_id);
          if ($s_admin == 1) {
            $sql = "SELECT * FROM sensors WHERE NOT status = 3 ORDER BY keyname ASC, vlanid ASC";
          } else {
            $sql = "SELECT * FROM sensors WHERE organisation = '$s_org' AND NOT status = 3 ORDER BY keyname ASC, vlanid ASC";
          }
          $debuginfo[] = $sql;
          $query = pg_query($sql);
          while ($sensor_data = pg_fetch_assoc($query)) {
            $label = $sensor_data["keyname"];
            $vlanid = $sensor_data["vlanid"];
            if ($vlanid != 0) {
              echo printOption($sensor_data["id"], "$label-$vlanid", $sensor);
            } else {
              echo printOption($sensor_data["id"], $label, $sensor);
            }
          }
        echo "</select>\n";
      echo "</td>\n";
    echo "</tr>\n";
    echo "<tr class='datatr'>";
      echo "<td class='datatd' width='100'>Report type:</td>\n";
      echo "<td class='datatd' width='200'>";
        echo "<select name='int_template' onclick='javascript: sh_mailtemp(this.value);'>\n";
          foreach ($v_mail_template_ar as $key=>$val) {
            echo printOption($key, $val, $temp);
          }
        echo "</select>\n";
      echo "</td>\n";
    echo "</tr>\n";
    if ($temp == 1 || $temp == 2) {
      echo "<tr class='datatr' style='display: ;' id='attack_sev' name='attack_sev'>";
    } else {
      echo "<tr class='datatr' style='display: none;' id='attack_sev' name='attack_sev'>";
    }
      echo "<td class='datatd' width='100'>Severity:</td>\n";
      echo "<td class='datatd' width='200'>";
        echo "<select name='int_severity'>\n";
          echo printOption(99, "All severities", $sev);
          foreach ($v_severity_ar as $key=>$val) {
            echo printOption($key, $val, $sev);
          }
        echo "</select>\n";
      echo "</td>\n";
    echo "</tr>\n";
    if ($temp == 4) {
      echo "<tr class='datatr' style='display: ;' id='sensor_sev' name='sensor_sev'>";
    } else {
      echo "<tr class='datatr' style='display: none;' id='sensor_sev' name='sensor_sev'>";
    }
      echo "<td class='datatd' width='100'>Severity:</td>\n";
      echo "<td class='datatd' width='200'>";
        echo "<select name='int_severity'>\n";
          echo printOption(99, "All severities", $sev);
          foreach ($v_sensor_sev_ar as $key=>$val) {
            echo printOption($key, $val, $sev);
          }
        echo "</select>\n";
      echo "</td>\n";
    echo "</tr>\n";
  echo "</table>\n";

  echo "<h4>Time options</h4>\n";
  echo "<table class='datatable'>\n";
    if ($temp == 1 || $temp == 2) {
      echo "<tr class='datatr' id='attack_time' name='attack_time' style='display: ;'>";
    } else {
      echo "<tr class='datatr' id='attack_time' name='attack_time' style='display: none;'>";
    }
      echo "<td class='datatd' width='100'>Frequency:</td>\n";
      echo "<td class='datatd' width='200'>";
        echo "<select name='int_frequency' onclick='javascript: sh_mailfreq(this.value);'>\n";
          foreach ($v_mail_frequency_attacks_ar as $key=>$val) {
            echo printOption($key, $val, $freq);
          }
        echo "</select>\n";
      echo "</td>\n";
    echo "</tr>\n";
    if ($temp == 4) {
      echo "<tr class='datatr' id='sensor_time' name='sensor_time' style='display: ;'>";
    } else {
      echo "<tr class='datatr' id='sensor_time' name='sensor_time' style='display: none;'>";
    }
      echo "<td class='datatd' width='100'>Frequency:</td>\n";
      echo "<td class='datatd' width='200'>";
        echo "<select name='int_frequency' onclick='javascript: sh_mailfreq(this.value);'>\n";
          foreach ($v_mail_frequency_sensors_ar as $key=>$val) {
            echo printOption($key, $val, $freq);
          }
        echo "</select>\n";
      echo "</td>\n";
    echo "</tr>\n";
    if ($freq == 2) {
      echo "<tr class='datatr' id='daily_freq' name='daily_freq' style='display: ;'>\n";
    } else {
      echo "<tr class='datatr' id='daily_freq' name='daily_freq' style='display: none;'>\n";
    }
      echo "<td class='datatd'>Time:</td>\n";
      echo "<td class='datatd'>";
        echo "<select name='int_intervalday'>\n";
          for ($i = 0; $i < 24; $i++) {
            $time = "$i:00 hour";
            if ($i < 10) {
              $time = "0" . $time;
            }
            echo printOption($i, $time, $interval);
          }
        echo "</select>\n";
      echo "</td>\n";
    echo "</tr>\n";
    if ($freq == 3) {
      echo "<tr class='datatr' id='weekly_freq' name='weekly_freq' style='display: ;'>";
    } else {
      echo "<tr class='datatr' id='weekly_freq' name='weekly_freq' style='display: none;'>";
    }
      echo "<td class='datatd'>Day:</td>\n";
      echo "<td class='datatd'>";
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
  echo "</table>\n";

  if ($freq == 4) {
    echo "<div id='thresh_freq' name='thresh_freq' style='display: ;'>\n";
  } else {
    echo "<div id='thresh_freq' name='thresh_freq' style='display: none;'>\n";
  }
    echo "<h4>Threshold options</h4>\n";
    echo "<table class='datatable'>\n";
      echo "<tr class='datatr'>\n";
        echo "<td class='datatd' width='100'>Operator:</td>\n";
        echo "<td class='datatd' width='200'>";
          echo "<select name='int_operator'>\n";
            foreach ($v_mail_operator_ar as $key => $val) {
              echo printOption($key, $val, $operator);
            }
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr class='datatr'>\n";
        echo "<td class='datatd' width='100'>Threshold amount:</td>\n";
        echo "<td class='datatd' width='200'><input type='text' name='int_threshold' value='$threshold' /></td>\n";
      echo "</tr>\n";
    echo "</table>\n";
  echo "</div>\n";

  echo "<br />\n";
  echo "<table class='datatable'>\n";
    echo "<tr class='datatr'>\n";
      echo "<td class='datatd' width='304' align='right'>";
        echo "<input type='reset' name='reset' value='Reset' class='button' />";
        echo "<input type='submit' name='submit' value='Add' class='button' />";
      echo "</td>\n";
    echo "</tr>\n";
  echo "</table>\n";
  echo "</form>\n";
  footer();
} else {
  $m = 92;
  header("location: mailadmin.php?int_m=$m");
  pg_close($pgconn);
  exit;
}
?>

<?php include("menu.php"); set_title("Mailreporting"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.04                  #
# 17-04-2007                       #
# Peter Arts                       #
# Modified by Kees Trippelvitz     #
####################################

#############################################
# Changelog:
# 1.04.04 Fixed a bug with the hash check stuff
# 1.04.03 Added hash check stuff
# 1.04.02 Changed debug stuff
# 1.04.01 Released as 1.04.01
# 1.03.01 Split up report.php into seperate files
#############################################

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_userid = intval($_SESSION['s_userid']);
$s_access = $_SESSION['s_access'];
$s_access_user = intval($s_access{2});
$s_hash = md5($_SESSION['s_hash']);
$err = 0;

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

$allowed_post = array(
                "strip_html_escape_title",
                "strip_html_escape_subject",
                "int_sensorid",
		"int_priority",
		"int_target",
		"int_timespan",
		"int_operator",
		"int_value",
		"int_intervalday",
		"int_intervalweek",
		"int_frequency",
		"bool_active",
		"int_valueuser",
		"int_deviation",
		"md5_hash",
		"submit"
);
$check = extractvars($_POST, $allowed_post);
debug_input();

$report_content_id = $clean["rcid"];
if ($report_content_id > 0) {
  # Getting data from database
  $sql_report_content = "SELECT * FROM report_content ";
  $sql_report_content .= "WHERE user_id = '$user_id' AND id = '$report_content_id'";
  $debuginfo[] = $sql_report_content;

  $result_report_content = pg_query($sql_report_content);
  if (pg_num_rows($result_report_content) == 1) {
    $report_content = pg_fetch_assoc($result_report_content);
    $template = $report_content["template"];
    $priority = $report_content["priority"];
    $frequency = $report_content["frequency"];
    $subject = $report_content['subject'];
    
    # Template: Thresholds
    if ($template == 3) {
      if (isset($tainted["submit"])) {
        # Submit data, update database record
        $title = $clean["title"];
        $sensor_id = $clean["sensorid"];
        $priority = $clean["priority"];
        $target = $clean["target"];
        $timespan = $clean["timespan"];
        $operator = $clean["operator"];
        $value = $clean["value"];
        $active = $clean["active"];
        if ($value == -2) {
          $value = $clean["valueuser"];
        }
        $deviation = $clean["deviation"];
        $subject = $clean['subject'];
        
        if (empty($title)) {
          $m = geterror(92);
          echo $m;
        } else {
          if ($clean['hash'] != $s_hash) {
            // Save data
            $sql_update = "UPDATE report_content SET ";
            $sql_update .= "title = '$title', sensor_id = '$sensor_id', active = '$active', priority = '$priority', ";
            $sql_update .= "frequency = '$timespan', interval = 0, subject = '$subject' WHERE id = '$report_content_id'";
            $debuginfo[] = $sql_update;

            $result = pg_query($sql_update);
            $msg = pg_errormessage();
            if (empty($msg)) {
              $sql_threshold = "UPDATE report_template_threshold SET ";
              $sql_threshold .= "target = '$target', operator = '$operator', value = '$value', deviation = '$deviation' ";
              $sql_threshold .= "WHERE report_content_id = '$report_content_id'";
              $debuginfo[] = $sql_threshold;

              $result = pg_query($sql_threshold);
              $msg = pg_errormessage();
              if (empty($msg)) {
                echo "<p style='color:green'><b>Data updated succesfully</b>.</p>\n";
                // Reload title/status var
                $sql_report_content = "SELECT * FROM report_content WHERE user_id = '$user_id' AND id = '$report_content_id'";
                $debuginfo[] = $sql_report_content;

                $result_report_content = pg_query($sql_report_content);
                $report_content = pg_fetch_assoc($result_report_content);
              } else {
                $m = geterror(94);
                echo $m;
              }
            } else {
              $m = geterror(93);
              echo $m;
            }
          } else {
            $m = geterror(95);
            echo $m;
          }
        }
      }
      
      $sql_template = "SELECT * FROM report_template_threshold WHERE report_content_id = '$report_content_id'";
      $debuginfo[] = $sql_template;
      $result_template = pg_query($sql_template);
      $report_template = pg_fetch_assoc($result_template);
      
      # Set all values:
      $title = $report_content["title"];
      $sensor_id = $report_content["sensor_id"];
      $priority = $report_content["priority"];
      $active = $report_content["active"];
      $target = $report_template["target"];
      $operator = $report_template["operator"];
      $frequency = $report_content["frequency"];
      $timespan = $frequency;
      $value = $report_template["value"];
      if ($value > 0) {
        // User defined
        $value_user = $value;
        $value = -2;
      } else {
        $value_user = -1;
      }
      $deviation = $report_template["deviation"];
      
      echo "<b>Edit " . $title . "</b><br /><br />\n";
      
      echo "<div name='threshold_user' id='threshold_user' style='position:absolute;left:400px;top:130px;border:1px solid black;padding:10px;'>Threshold: </div>";
      echo "<form method='post' onclick=\"updateThreshold();\">";
        echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
        echo "<input type='hidden' name='action' value='edit'>";
        echo "<input type='hidden' name='int_userid' value='$user_id'>";
        echo "<input type='hidden' name='submit' value='1'>";
        echo "<input type='hidden' name='int_rcid' value='$report_content_id'>\n";
        echo "<table border=0 cellspacing=2 cellpadding=2 class='datatable'>\n";
          echo "<tr class='datatr'>\n";
            echo "<td class='datatd'>Title: </td>\n";
            echo "<td class='datatd'><input type='text' name='strip_html_escape_title' value='$title'></td>\n";
          echo "</tr>\n";
          # Email subject
          echo "<tr class='datatr'>";
            echo "<td class='datatd'>Email subject: </td>\n";
            echo "<td class='datatd'><input type='text' name='strip_html_escape_subject' value='$subject' /></td>";
          echo "</tr>\n";
          echo "<tr class='datatr'>\n";
            echo "<td class='datatd'>Sensor: </td>\n";
            echo "<td class='datatd'>\n";
              echo "<select name='int_sensorid' style='background-color:white;'>\n";
                echo printOption(-1, "All sensors", $sensor_id);
                if ($s_admin == 1) {
                  $sql = "SELECT * FROM sensors ORDER BY sensors.keyname";
                } else {
                  $sql = "SELECT * FROM sensors WHERE organisation = '$s_org' ORDER BY keyname";
                }
                $debuginfo[] = $sql;
                $query = pg_query($sql);
                while ($sensor_data = pg_fetch_assoc($query)) {
                  $label = $sensor_data["keyname"];
                  $vlanid = $sensor_data["vlanid"];
		  if ($vlanid != 0) echo printOption($sensor_data["id"], "$label-$vlanid", $sensor_id);
		  else echo printOption($sensor_data["id"], $label, $sensor_id);
                }
              echo "</select>\n";
            echo "</td>\n";
          echo "</tr>\n";
          # Priority
          echo "<tr class='datatr'>";
            echo "<td class='datatd'>Alert priority: </td>\n";
            echo "<td class='datatd'>";
              echo "<select name='int_priority' id='priority' style='background-color:white;'>\n";
                foreach ($v_mail_priority_ar as $key=>$val) {
                  echo "    " . printOption($key, $val, $priority);
                }
              echo "</select>\n";
            echo "</td>\n";
          echo "</tr>\n";
          write_report_template_threshold_fields();
          echo "<tr class='datatr'>\n";
            echo "<td class='datatd'>Status: </td>\n";
            echo "<td class='datatd'>\n";
              echo "<select name='bool_active' style='background-color:white;'>\n";
                echo "    " . printOption('t', "Active", $active);
                echo "    " . printOption('f', "Inactive", $active);
              echo "</select>\n";
            echo "</td>\n";
          echo "</tr>\n";
        echo "</table>\n";
        echo "<input type='submit' name='submitBtn' value='Update' class='button' />\n";
        echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
        echo "&nbsp;&nbsp;";
        echo "<input type='button' name='b1' value='Back' onclick=\"window.location.href='mailadmin.php?int_userid=$user_id';\" class='button'>\n";
      echo "</form>\n";
      echo "<script language=\"javascript\" type=\"text/javascript\">updateThreshold();</script>\n";
    } else {
      # Handle submitted data
      if (isset($tainted["submit"])) {
        if ($clean['hash'] == $s_hash) {
          $title = $clean["title"];
          $sensor_id = $clean["sensorid"];
          $priority = $clean["priority"];
          $frequency = $clean["frequency"];
          $interval_day = $clean["intervalday"];
          $interval_week = $clean["intervalweek"];
          $active = $clean["active"];
          if ($frequency == 1) {
            $interval_db = 0;
          } elseif ($frequency == 2) {
            $interval_db = $interval_day;
          } elseif ($frequency == 3) {
            $interval_db = $interval_week;
          }
          if (empty($title)) {
            $m = geterror(92);
            echo $m;
          } else {
            // Save data
            $sql_update = "UPDATE report_content SET ";
            $sql_update .= "title = '$title', sensor_id = '$sensor_id', priority = '$priority', ";
            $sql_update .= "frequency = '$frequency', interval = '$interval_db', active = '$active', subject = '$subject' ";
            $sql_update .= "WHERE id = '$report_content_id'";
            $debuginfo[] = $sql_update;

            $result = pg_query($sql_update);
            $msg = pg_errormessage();
            if (empty($msg)) {
              echo "<p style='color:green'><b>Data updated succesfully</b>.</p>\n";
              // Reload title/status var
              $sql_report_content = "SELECT * FROM report_content ";
              $sql_report_content .= "WHERE user_id = '$user_id' AND id = '$report_content_id'";
              $debuginfo[] = $sql_report_content;

              $result_report_content = pg_query($sql_report_content);
              $report_content = pg_fetch_assoc($result_report_content);
            } else {
              $m = geterror(93);
              echo $m;
            }
          }
        }
      }
      
      # Prepare data
      $frequency = $report_content["frequency"];
      if ($frequency == 2) {
        $interval_day = $report_content["interval"];
      } else {
        $interval_day = -1;
      }
      if ($frequency == 3) {
        $interval_week = $report_content["interval"];
      } else {
        $interval_week = -1;
      }
      $title = $report_content["title"];
      $sensor_id = $report_content["sensor_id"];
      $priority = $report_content["priority"];
      $active = $report_content["active"];
      
      # Template: All attacks or Own ranges
      echo "<b>Edit " . $title . "</b><br /><br />\n";
      echo "<form method='post' onclick=\"updateThreshold();\">";
        echo "<input type='hidden' name='action' value='edit'>";
        echo "<input type='hidden' name='int_userid' value='$user_id'>";
        echo "<input type='hidden' name='submit' value='1'>";
        echo "<input type='hidden' name='int_rcid' value='$report_content_id'>\n";
        echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
        echo "<table border=0 cellspacing=2 cellpadding=2 class='datatable'>\n";
          echo "<tr class='datatr'>\n";
            echo "<td class='datatd'>Title: </td>\n";
            echo "<td class='datatd'><input type='text' name='strip_html_escape_title' value='$title'></td>\n";
          echo "</tr>\n";
          # Email subject
          echo "<tr class='datatr'>";
            echo "<td class='datatd'>Email subject: </td>\n";
            echo "<td class='datatd'><input type='text' name='strip_html_escape_subject' value='$subject' /></td>";
          echo "</tr>\n";
          echo "<tr class='datatr'>\n";
            echo "<td class='datatd'>Sensor: </td>\n";
            echo "<td class='datatd'>\n";
              echo "<select name='int_sensorid' style='background-color:white;'>\n";
                echo printOption(-1, "All sensors", $sensor_id);
                if ($s_admin == 1) {
                  $sql = "SELECT * FROM sensors ORDER BY keyname";
                } else {
                  $sql = "SELECT * FROM sensors WHERE organisation = '$s_org' ORDER BY keyname";
                }
                $debuginfo[] = $sql;
                $query = pg_query($sql);
                while ($sensor_data = pg_fetch_assoc($query)) {
                  $label = $sensor_data["keyname"];
                  $vlanid = $sensor_data["vlanid"];
		  if ($vlanid != 0) echo printOption($sensor_data["id"], "$label-$vlanid", $sensor_id);
		  else echo printOption($sensor_data["id"], $label, $sensor_id);
                }
              echo "</select>\n";
            echo "</td>\n";
          echo "</tr>\n";
          # Priority
          echo "<tr class='datatr'>";
            echo "<td class='datatd'>Alert priority: </td>\n";
            echo "<td class='datatd'>";
              echo "<select name='int_priority' id='priority' style='background-color:white;'>\n";
                foreach ($v_mail_priority_ar as $key=>$val) {
                  echo "    " . printOption($key, $val, $priority);
                }
              echo "</select>\n";
            echo "</td>\n";
          echo "</tr>\n";
          echo "<tr class='datatr'>";
            echo "<td class='datatd'>Frequency: </td>\n";
            echo "<td class='datatd'>";
              echo "<select name='int_frequency' style='background-color:white;' onclick=\"if (this.selectedIndex == 0) {document.getElementById('frequency-interval-day').style.display='none';document.getElementById('frequency-interval-week').style.display='none';} if (this.selectedIndex == 1) {document.getElementById('frequency-interval-day').style.display='';document.getElementById('frequency-interval-week').style.display='none'; }if (this.selectedIndex == 2) {document.getElementById('frequency-interval-day').style.display='none';document.getElementById('frequency-interval-week').style.display='';} \">\n";
                foreach ($v_mail_frequency_ar as $key=>$val) {
                  echo "    " . printOption($key, $val, $frequency);
                }
              echo "</select>\n";
            echo "</td>\n";
          echo "</tr>\n";
          if ($interval_day > -1) {
            $style = "";
          } else { 
            $style = " style='display:none;'";
          }
          echo "<tr class='datatr' $style id='frequency-interval-day' name='frequency-interval-day'>";
            echo "<td class='datatd'>At: </td>\n";
            echo "<td class='datatd'>";
              echo "<select name='int_intervalday' style='background-color:white;'>\n";
                for ($i = 0; $i < 24; $i++) {
                  $time = "$i:00 hour";
                  if ($i < 10) {
                    $time = "0" . $time;
                  }
                  echo "    " . printOption($i, $time, $interval_day);
                }
              echo "</select>\n";
            echo "</td>\n";
          echo "</tr>\n";
          if ($interval_week > -1) {
            $style = "";
          } else { 
            $style = " style='display:none;'";
          }
          echo "<tr class='datatr' $style id='frequency-interval-week' name='frequency-interval-week'>";
            echo "<td class='datatd'>At: </td>\n";
            echo "<td class='datatd'>";
              echo "<select name='int_intervalweek' style='background-color:white;'>\n";
                $j = (4 * 86400); // monday
                for ($i = 1; $i < 8; $i++) {
                  // Daynr 1 = monday, 0 = sunday
                  $daynr = $i;
                  $daynr = ($daynr % 7);
                  // use php's date function to print the day
                  echo "    " . printOption($daynr, date("l", $j), $interval_week);
                  $j += 86400; // add one day
                }
              echo "</select>\n";
            echo "</td>\n";
          echo "</tr>\n";
          echo "<tr class='datatr'>\n";
            echo "<td class='datatd'>Status: </td>\n";
            echo "<td class='datatd'>\n";
              echo "<select name='bool_active' style='background-color:white;'>\n";
                echo "    " . printOption('t', "Active", $active);
                echo "    " . printOption('f', "Inactive", $active);
              echo "</select>\n";
            echo "</td>\n";
          echo "</tr>\n";
        echo "</table>\n";
        echo "<input type='submit' class='button' name='submitBtn' value='Update'>\n";
        echo "<input type='button' name='b1' value='Back' onclick=\"window.location.href='mailadmin.php?int_userid=$user_id';\" class='button'>\n";
        echo "<br />";
      echo "</form>\n";
    }
  } else {
    $m = geterror(95);
    echo $m;
  }
} else {
  $m = geterror(96);
  echo $m;
}

function write_report_template_threshold_fields() {
  global $priority, $target, $timespan, $operator, $value, $value_user, $deviation, $v_mail_priority_ar, $v_mail_target_ar, $v_mail_timespan_ar, $v_mail_operator_ar;
  # Target
  echo "<tr class='datatr'>";
    echo "<td class='datatd'>Target: </td>\n";
    echo "<td class='datatd'>";
      echo "<select name='int_target' id='target' style='background-color:white;'>\n";
        foreach ($v_mail_target_ar as $key=>$val) {
          echo "    " . printOption($key, $val, $target);
        }
      echo "</select>\n";
    echo "</td>\n";
  echo "</tr>\n";
  # Timespan
  echo "<tr class='datatr'>";
    echo "<td class='datatd'>Timespan: </td>\n";
    echo "<td class='datatd'>";
      echo "<select name='int_timespan' id='timespan' style='background-color:white;'>\n";
        foreach ($v_mail_timespan_ar as $key=>$val) {
          echo "    " . printOption($key, $val, $timespan);
        }
      echo "</select>\n";
    echo "</td>\n";
  echo "</tr>\n";
  # Operator
  echo "<tr class='datatr'>";
    echo "<td class='datatd'>Operator: </td>\n";
    echo "<td class='datatd'>";
      echo "<select name='int_operator' id='operator' style='background-color:white;'>\n";
        foreach ($v_mail_operator_ar as $key=>$val) {
          echo "    " . printOption($key, htmlentities($val), $operator);
        }
      echo "</select>\n";
    echo "</td>\n";
  echo "</tr>\n";
  # Value
  echo "<tr class='datatr'>";
    echo "<td class='datatd'>Value: </td>\n";
    echo "<td class='datatd'>";
      echo "<select name='int_value' id='value' style='background-color:white;' onchange=\"if (this.selectedIndex == 1) { document.getElementById('value_user').style.display=''; } else { document.getElementById('value_user').style.display='none'; }\">\n";
        echo "    " . printOption(-1, "Average", $value);
        echo "    " . printOption(-2, "User defined", $value);
      echo "</select>\n";
      echo "<br>\n";
      if ($value_user > 0) {
        $value_user_style = "";
      } else {
        $value_user_style = " style=\"display:none\";";
      }
      echo "<input type='text' name='int_valueuser' id='value_user' value='$value_user'$value_user_style>";
    echo "</td>\n";
  echo "</tr>\n";
  echo "<tr class='datatr'>\n";
    echo "<td class='datatd'>Deviation: </td>\n";
    echo "<td class='datatd'><input type='text' name='int_deviation' id='deviation' value='$deviation' style='width:50px;'> %</td>\n";
  echo "</tr>\n";
}
debug_sql();
?>

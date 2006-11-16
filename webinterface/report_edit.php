<?php include("menu.php"); set_title("Mailreporting"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.01                  #
# 06-11-2006                       #
# Peter Arts                       #
####################################

#############################################
# Changelog:
# 1.04.01 Code layout
# 1.02.02 Removed includes
# 1.02.01 Initial release
#############################################

#############################################
# Todo:
# Show current average by selecting average
#############################################

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_userid = intval($_SESSION['s_userid']);
$s_access = $_SESSION['s_access'];
$s_access_user = intval($s_access{2});
$err = 0;

// Make sure all access rights are correct
if (isset($_GET['userid'])) {
  $user_id = intval($_GET['userid']);
  if ($s_access_user < 1) {
    header("location: index.php");
    pg_close($pgconn);
    exit;
  } elseif ($s_access_user < 2) {
    $user_id = $s_userid;
  } elseif ($s_access_user < 9) {
    $sql_login = "SELECT * FROM login WHERE organisation = $s_org AND id = $user_id";
    $result_login = pg_query($pgconn, $sql_login);
    $numrows_login = pg_num_rows($result_login);
    if ($numrows_login == 0) {
      echo "<p style='color:red;'><b>You don't have sufficient rights to perform the requested action.</b></p>\n";
      footer();
      exit;
    } else {
      $user_id = intval($_GET['userid']);
    }
  } else {
    $user_id = intval($_GET['userid']);
  }
} else {
  $user_id = $s_userid;
}


$report_content_id = intval($_GET["report_content_id"]);
if ($report_content_id > 0) {
  # Getting data from database
  $sql_report_content = "SELECT * FROM report_content ";
  $sql_report_content .= "WHERE user_id = '$user_id' AND id = '$report_content_id'";
  debug("SQL_REPORT_CONTENT", $sql_report_content);

  $result_report_content = pg_query($sql_report_content);
  if (pg_num_rows($result_report_content) == 1) {
    $report_content = pg_fetch_assoc($result_report_content);
    $template = $report_content["template"];
    $priority = $report_content["priority"];
    $frequency = $report_content["frequency"];
    $subject = $report_content['subject'];
    
    # Template: Thresholds
    if ($template == 3) {
      if (intval($_POST["submit"]) == 1) {
        # Submit data, update database record
        $title = pg_escape_string(htmlentities(strip_tags(trim($_POST["title"]))));
        $sensor_id = intval($_POST["sensor_id"]);
        $priority = intval($_POST["priority"]);
        $target = intval($_POST["target"]);
        $timespan = intval($_POST["timespan"]);
        $operator = intval($_POST["operator"]);
        $value = intval($_POST["value"]);
        $active = pgboolval($_POST["active"]);
        if ($value == -2) {
          $value = intval($_POST["value_user"]);
        }
        $deviation = intval($_POST["deviation"]);
        $subject = pg_escape_string(htmlentities(strip_tags(trim($_POST['subject']))));
        
        if (empty($title)) {
          echo "<p style='color:red;'><b>Invalid title</b></p>\n";
        } else {
          // Save data
          $sql_update = "UPDATE report_content SET ";
          $sql_update .= "title = '$title', sensor_id = '$sensor_id', active = '$active', priority = '$priority', ";
          $sql_update .= "frequency = '$timespan', interval = 0, subject = '$subject' WHERE id = '$report_content_id'";
          debug("SQL_UPDATE", $sql_update);

          $result = pg_query($sql_update);
          $msg = pg_errormessage();
          if (empty($msg)) {
            $sql_threshold = "UPDATE report_template_threshold SET ";
            $sql_threshold .= "target = '$target', operator = '$operator', value = '$value', deviation = '$deviation' ";
            $sql_threshold .= "WHERE report_content_id = '$report_content_id'";
            debug("SQL_THRESHOLD", $sql_threshold);

            $result = pg_query($sql_threshold);
            $msg = pg_errormessage();
            if (empty($msg)) {
              echo "<p style='color:green'><b>Data updated succesfully</b>.</p>\n";
              // Reload title/status var
              $sql_report_content = "SELECT * FROM report_content WHERE user_id = '$user_id' AND id = '$report_content_id'";
              debug("SQL_REPORT_CONTENT", $sql_report_content);

              $result_report_content = pg_query($sql_report_content);
              $report_content = pg_fetch_assoc($result_report_content);
            } else {
              echo "<p style='color:red;'>Data couldn't be saved (2).</p>\n";
            }
          } else {
            echo "<p style='color:red;'>Data couldn't be saved (1).</p>\n";
          }
        }
      }
      
      $sql_template = "SELECT * FROM report_template_threshold WHERE report_content_id = '$report_content_id'";
      debug("SQL_TEMPLATE", $sql_template);
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
        echo "<input type='hidden' name='action' value='edit'>";
        echo "<input type='hidden' name='userid' value='$user_id'>";
        echo "<input type='hidden' name='submit' value='1'>";
        echo "<input type='hidden' name='report_content_id' value='$report_content_id'>\n";
        echo "<table border=0 cellspacing=2 cellpadding=2 class='datatable'>\n";
          echo "<tr class='datatr'>\n";
            echo "<td class='datatd'>Title: </td>\n";
            echo "<td class='datatd'><input type='text' name='title' value='$title'></td>\n";
          echo "</tr>\n";
          # Email subject
          echo "<tr class='datatr'>";
            echo "<td class='datatd'>Email subject: </td>\n";
            echo "<td class='datatd'><input type='text' name='subject' value='$subject' /></td>";
          echo "</tr>\n";
          echo "<tr class='datatr'>\n";
            echo "<td class='datatd'>Sensor: </td>\n";
            echo "<td class='datatd'>\n";
              echo "<select name=\"sensor_id\" style=\"background-color:white;\">\n";
                echo printOption(-1, "All sensors", $sensor_id);
                if ($s_admin == 1) {
                  $sql = "SELECT * FROM sensors ORDER BY sensors.keyname";
                } else {
                  $sql = "SELECT * FROM sensors WHERE organisation = '$s_org' ORDER BY keyname";
                }
                $query = pg_query($sql);
                while ($sensor_data = pg_fetch_assoc($query)) {
                  $label = $sensor_data["keyname"];
                  echo printOption($sensor_data["id"], $label, $sensor_id);
                }
              echo "</select>\n";
            echo "</td>\n";
          echo "</tr>\n";
          # Priority
          echo "<tr class='datatr'>";
            echo "<td class='datatd'>Alert priority: </td>\n";
            echo "<td class='datatd'>";
              echo "<select name='priority' id='priority' style='background-color:white;'>\n";
                foreach ($mail_priority_ar as $key=>$val) {
                  echo "    " . printOption($key, $val, $priority);
                }
              echo "</select>\n";
            echo "</td>\n";
          echo "</tr>\n";
          write_report_template_threshold_fields();
          echo "<tr class='datatr'>\n";
            echo "<td class='datatd'>Status: </td>\n";
            echo "<td class='datatd'>\n";
              echo "<select name='active' style='background-color:white;'>\n";
                echo "    " . printOption('t', "Active", $active);
                echo "    " . printOption('f', "Inactive", $active);
              echo "</select>\n";
            echo "</td>\n";
          echo "</tr>\n";
        echo "</table>\n";
        echo "<input type='submit' name='submitBtn' value='Update' class='button'>\n";
        echo "&nbsp;&nbsp;";
        echo "<input type='button' name='b1' value='Back' onclick=\"window.location.href='mailadmin.php?userid=$user_id';\" class='button'>\n";
      echo "</form>\n";
      echo "<script language=\"javascript\" type=\"text/javascript\">updateThreshold();</script>\n";
    } else {
      # Handle submitted data
      if (intval($_POST["submit"]) == 1) {
        $title = pg_escape_string(htmlentities(strip_tags(trim($_POST["title"]))));
        $sensor_id = intval($_POST["sensor_id"]);
        $priority = intval($_POST["priority"]);
        $frequency = intval($_POST["frequency"]);
        $interval_day = intval($_POST["interval_day"]);
        $interval_week = intval($_POST["interval_week"]);
        $active = pgboolval($_POST["active"]);
        if ($frequency == 1) {
          $interval_db = 0;
        } elseif ($frequency == 2) {
          $interval_db = $interval_day;
        } elseif ($frequency == 3) {
          $interval_db = $interval_week;
        }
        if (empty($title)) {
          echo "<p style='color:red;'><b>Invalid title</b></p>\n";
        } else {
          // Save data
          $sql_update = "UPDATE report_content SET ";
          $sql_update .= "title = '$title', sensor_id = '$sensor_id', priority = '$priority', ";
          $sql_update .= "frequency = '$frequency', interval = '$interval_db', active = '$active', subject = '$subject' ";
          $sql_update .= "WHERE id = '$report_content_id'";
          debug("SQL_UPDATE", $sql_update);

          $result = pg_query($sql_update);
          $msg = pg_errormessage();
          if (empty($msg)) {
            echo "<p style='color:green'><b>Data updated succesfully</b>.</p>\n";
            // Reload title/status var
            $sql_report_content = "SELECT * FROM report_content ";
            $sql_report_content .= "WHERE user_id = '$user_id' AND id = '$report_content_id'";
            debug("SQL_REPORT_CONTENT", $sql_report_content);

            $result_report_content = pg_query($sql_report_content);
            $report_content = pg_fetch_assoc($result_report_content);
          } else {
            echo "<p style='color:red;'>Data couldn't be saved (1).</p>\n";
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
        echo "<input type='hidden' name='userid' value='$user_id'>";
        echo "<input type='hidden' name='submit' value='1'>";
        echo "<input type='hidden' name='report_content_id' value='$report_content_id'>\n";
        echo "<table border=0 cellspacing=2 cellpadding=2 class='datatable'>\n";
          echo "<tr class='datatr'>\n";
            echo "<td class='datatd'>Title: </td>\n";
            echo "<td class='datatd'><input type='text' name='title' value='$title'></td>\n";
          echo "</tr>\n";
          # Email subject
          echo "<tr class='datatr'>";
            echo "<td class='datatd'>Email subject: </td>\n";
            echo "<td class='datatd'><input type='text' name='subject' value='$subject' /></td>";
          echo "</tr>\n";
          echo "<tr class='datatr'>\n";
            echo "<td class='datatd'>Sensor: </td>\n";
            echo "<td class='datatd'>\n";
              echo "<select name=\"sensor_id\" style=\"background-color:white;\">\n";
                echo printOption(-1, "All sensors", $sensor_id);
                if ($s_admin == 1) {
                  $sql = "SELECT * FROM sensors ORDER BY keyname";
                } else {
                  $sql = "SELECT * FROM sensors WHERE organisation = '$s_org' ORDER BY keyname";
                }
                $query = pg_query($sql);
                while ($sensor_data = pg_fetch_assoc($query)) {
                  $label = $sensor_data["keyname"];
                  echo printOption($sensor_data["id"], $label, $sensor_id);
                }
              echo "</select>\n";
            echo "</td>\n";
          echo "</tr>\n";
          # Priority
          echo "<tr class='datatr'>";
            echo "<td class='datatd'>Alert priority: </td>\n";
            echo "<td class='datatd'>";
              echo "<select name='priority' id='priority' style='background-color:white;'>\n";
                foreach ($mail_priority_ar as $key=>$val) {
                  echo "    " . printOption($key, $val, $priority);
                }
              echo "</select>\n";
            echo "</td>\n";
          echo "</tr>\n";
          echo "<tr class='datatr'>";
            echo "<td class='datatd'>Frequency: </td>\n";
            echo "<td class='datatd'>";
              echo "<select name='frequency' style='background-color:white;' onclick=\"if (this.selectedIndex == 0) {document.getElementById('frequency-interval-day').style.display='none';document.getElementById('frequency-interval-week').style.display='none';} if (this.selectedIndex == 1) {document.getElementById('frequency-interval-day').style.display='';document.getElementById('frequency-interval-week').style.display='none'; }if (this.selectedIndex == 2) {document.getElementById('frequency-interval-day').style.display='none';document.getElementById('frequency-interval-week').style.display='';} \">\n";
                foreach ($mail_frequency_ar as $key=>$val) {
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
              echo "<select name='interval_day' style='background-color:white;'>\n";
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
              echo "<select name='interval_week' style='background-color:white;'>\n";
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
              echo "<select name='active' style='background-color:white;'>\n";
                echo "    " . printOption('t', "Active", $active);
                echo "    " . printOption('f', "Inactive", $active);
              echo "</select>\n";
            echo "</td>\n";
          echo "</tr>\n";
        echo "</table>\n";
        echo "<input type='submit' class='button' name='submitBtn' value='Update'>\n";
        echo "<input type='button' name='b1' value='Back' onclick=\"window.location.href='mailadmin.php?userid=$user_id';\" class='button'>\n";
        echo "<br />";
      echo "</form>\n";
    }
  } else {
    echo "<p style='color:red;'><b>You don't have sufficient rights to edit this report</b>.</p>\n";
  }
} else {
  echo "<p style='color:red;'><b>Invalid report</b>.</p>\n";
}

function write_report_template_threshold_fields() {
  global $priority, $target, $timespan, $operator, $value, $value_user, $deviation, $mail_priority_ar, $mail_target_ar, $mail_timespan_ar, $mail_operator_ar;
  # Target
  echo "<tr class='datatr'>";
    echo "<td class='datatd'>Target: </td>\n";
    echo "<td class='datatd'>";
      echo "<select name='target' id='target' style='background-color:white;'>\n";
        foreach ($mail_target_ar as $key=>$val) {
          echo "    " . printOption($key, $val, $target);
        }
      echo "</select>\n";
    echo "</td>\n";
  echo "</tr>\n";
  # Timespan
  echo "<tr class='datatr'>";
    echo "<td class='datatd'>Timespan: </td>\n";
    echo "<td class='datatd'>";
      echo "<select name='timespan' id='timespan' style='background-color:white;'>\n";
        foreach ($mail_timespan_ar as $key=>$val) {
          echo "    " . printOption($key, $val, $timespan);
        }
      echo "</select>\n";
    echo "</td>\n";
  echo "</tr>\n";
  # Operator
  echo "<tr class='datatr'>";
    echo "<td class='datatd'>Operator: </td>\n";
    echo "<td class='datatd'>";
      echo "<select name='operator' id='operator' style='background-color:white;'>\n";
        foreach ($mail_operator_ar as $key=>$val) {
          echo "    " . printOption($key, htmlentities($val), $operator);
        }
      echo "</select>\n";
    echo "</td>\n";
  echo "</tr>\n";
  # Value
  echo "<tr class='datatr'>";
    echo "<td class='datatd'>Value: </td>\n";
    echo "<td class='datatd'>";
      echo "<select name='value' id='value' style='background-color:white;' onchange=\"if (this.selectedIndex == 1) { document.getElementById('value_user').style.display=''; } else { document.getElementById('value_user').style.display='none'; }\">\n";
        echo "    " . printOption(-1, "Average", $value);
        echo "    " . printOption(-2, "User defined", $value);
      echo "</select>\n";
      echo "<br>\n";
      if ($value_user > 0) {
        $value_user_style = "";
      } else {
        $value_user_style = " style=\"display:none\";";
      }
      echo "<input type='text' name='value_user' id='value_user' value='$value_user'$value_user_style>";
    echo "</td>\n";
  echo "</tr>\n";
  echo "<tr class='datatr'>\n";
    echo "<td class='datatd'>Deviation: </td>\n";
    echo "<td class='datatd'><input type='text' name='deviation' id='deviation' value='$deviation' style='width:50px;'> %</td>\n";
  echo "</tr>\n";
}
?>

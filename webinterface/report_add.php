<?php include("menu.php"); set_title("Mailreporting"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.03                  #
# 15-12-2006                       #
# Peter Arts                       #
# Modified by Kees Trippelvitz     #
####################################

#############################################
# Changelog:
# 1.04.03 Changed data input handling
# 1.04.02 Changed debug stuff
# 1.04.01 Released as 1.04.01
# 1.03.01 Split up report.php into seperate files
#############################################

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_userid = intval($_SESSION['s_userid']);
$s_access = $_SESSION['s_access'];
$s_access_user = intval($s_access{2});
$err = 0;

### Extracting GET variables
$allowed_get = array(
                "int_userid"
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

### Extracting POST variables
$allowed_post = array(
		"int_userid",
                "int_nextstep",
		"strip_html_escape_subject",
		"strip_html_escape_title",
		"int_sensorid",
		"int_priority",
		"int_target",
		"int_timespan",
		"int_operator",
		"int_value",
		"int_valueuser",
		"int_deviation",
		"int_frequency",
		"int_intervalweek",
		"int_intervalday",
		"int_template"
);
$check = extractvars($_POST, $allowed_post);
debug_input();

echo "<h3>" . ucfirst($action) . " report wizard</h3>\n";
$step = $clean["nextstep"];
if (($step <= 0) || ($step > 3)) $step = 1;
$request_step = $step;

if (isset($clean["nextstep"])) {
  # Set submitted data
  // From step 1:
  $template = $clean["template"];
  if ($step > 2) {
    // From step 2:
    $subject = $clean["subject"];
    $title = $clean["title"];
    $sensor_id = $clean["sensorid"];
    $priority = $clean["priority"];
    if ($template == 3) {
      $target = $clean["target"];
      $timespan = $clean["timespan"];
      $frequency = $timespan;
      $operator = $clean["operator"];
      $value = $clean["value"];
      if ($value == -2) {
        $value = $clean["valueuser"];
      }
      $deviation = $clean["deviation"];
      $interval_db = 0;
    } else {
      $frequency = $clean["frequency"];
      $interval_day = $clean["intervalday"];
      $interval_week = $clean["intervalweek"];
      if ($frequency == 1) {
        $interval_db = 0;
      } elseif ($frequency == 2) {
        $interval_db = $interval_day;
      } elseif ($frequency == 3) {
        $interval_db = $interval_week;
      }
    }
  }
  
  # Check fields from previous steps (submitted data)
  // Step 1:
  if (($template < 1) || ($template > 4)) {
    $step = 1;
  }
  // Step 2: no checks needed
  
  if (($step == 3) && ($step == $request_step)) {
    # All clear, save data
    // Table report_content
    $sql_insert = "INSERT INTO report_content ";
    $sql_insert .= "(user_id, title, priority, sensor_id, interval, frequency, template, active, subject) ";
    $sql_insert .= "VALUES ('$user_id', '$title', '$priority', '$sensor_id', '$interval_db', '$frequency', '$template', 't', '$subject')";

    $debuginfo[] = $sql_insert;

    $query = pg_query($sql_insert);
    if (pg_affected_rows($query) == 1) {
      $query = pg_query("SELECT currval('report_content_id_seq') AS last_insert_id FROM report_content");
      $report_content_id = intval(@pg_result($query, 0));
      if ($template == 3) {                
        // Insert in table report_template_threshold
        $sql_threshold = "INSERT INTO report_template_threshold ";
        $sql_threshold .= "(report_content_id, target, operator, value, deviation) VALUES ";
        $sql_threshold .= "('$report_content_id', '$target', '$operator', '$value', '$deviation')";
        $debuginfo[] = $sql_threshold;

        $query = pg_query($sql_threshold);
      }
      echo "<p style='color:green;'>Data saved!</p>\n";
    } else {
      $m = geterror(92);
      echo $m;
    }
  }
}

# Display error message
if ($step != $request_step) {
  $m = geterror(93);
  echo $m;
}

echo "<h4>Step $step of 3</h4>\n";
if ($step == 3) {
  # Save data
  echo "<b>Report added succesfully</b>.<br />\n";
  echo "What would you like to do now:<br />\n";
  echo "<ul>\n";
    echo "<li><a href='report_add.php?int_userid=$user_id'>Add another report</a></li>\n";
    echo "<li><a href='mailadmin.php?int_userid=$user_id'>Go back to the mailreporting screen</a></li>\n";
  echo "</ul>\n";
}
echo "<form method='post'";
if (($step == 2) && ($template == 3)) echo " onclick=\"updateThreshold();\"";
echo ">\n";
  echo "<input type='hidden' name='int_userid' value='$user_id'>";
  echo "<input type='hidden' name='int_nextstep' id='nextstep' value='" . ($step + 1) . "'>\n";
  if ($step == 1) {
    echo "<table border=0 cellspacing=2 cellpadding=2 class='datatable'>\n";
      echo "<tr class='datatr'>";
        echo "<td class='datatd'>Template: </td>\n";
        echo "<td class='datatd'>";
          echo "<select name='int_template' style='background-color:white;'>\n";
            foreach ($mail_template_ar as $key=>$val) {
              echo "    " . printOption($key, $val, $template);
            }
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
    echo "</table>\n";
    echo "<br />";
  } elseif ($step == 2) {
    echo "<input type='hidden' name='int_template' value='$template'>";
    if ($template == 3) {
      # Thresholds
      if (!isset($priority)) $priority = 2;
      if (!isset($operator)) $operator = ">";
      if (!isset($deviation)) $deviation = 10;

      echo "<table border=0 cellspacing=2 cellpadding=2 class='datatable'>\n";

        # Report name
        echo "<tr class='datatr'>";
          echo "<td class='datatd'>Report name: </td>\n";
          echo "<td class='datatd'><input type='text' name='strip_html_escape_title' value='$title'></td>\n";
        echo "</tr>\n";

        # Email subject
        echo "<tr class='datatr'>";
          echo "<td class='datatd'>Email subject: </td>\n";
          echo "<td class='datatd'><input type='text' name='strip_html_escape_subject' value='$subject' /></td>";
        echo "</tr>\n";

        # Priority
        echo "<tr class='datatr'>";
          echo "<td class='datatd'>Alert priority: </td>\n";
          echo "<td class='datatd'>";
            echo "<select name='int_priority' id='priority' style='background-color:white;'>\n";
              foreach ($mail_priority_ar as $key=>$val) {
                echo "    " . printOption($key, $val, $priority);
              }
            echo "</select>\n";
          echo "</td>\n";
        echo "</tr>\n";

        # Sensors
        echo "<tr>\n";
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

        write_report_template_threshold_fields();
      echo "</table>\n";
      echo "<br />";
      echo "<input type='submit' class='button' name='submitBtn2' value='Previous' onclick=\"document.getElementById('nextstep').value=" . ($step - 1) . "\">\n";
    } else {
      # Default
      echo "<table border=0 cellspacing=2 cellpadding=2 class='datatable'>\n";

        # Template
        echo "<tr class='datatr'>";
          echo "<td class='datatd'>Template: </td>\n";
          echo "<td class='datatd'>$mail_template_ar[$template]</td>\n";
        echo "</tr>\n";

        # Report name
        echo "<tr class='datatr'>";
          echo "<td class='datatd'>Report name: </td>\n";
          echo "<td class='datatd'><input type='text' name='strip_html_escape_title' value='$title'></td>\n";
        echo "</tr>\n";

        # Email subject
        echo "<tr class='datatr'>";
          echo "<td class='datatd'>Email subject: </td>\n";
          echo "<td class='datatd'><input type='text' name='strip_html_escape_subject' value='$subject' /></td>";
        echo "</tr>\n";

        # Priority
        echo "<tr class='datatr'>";
          echo "<td class='datatd'>Alert priority: </td>\n";
          echo "<td class='datatd'>";
            echo "<select name='int_priority' id='priority' style='background-color:white;'>\n";
              foreach ($mail_priority_ar as $key=>$val) {
                echo "    " . printOption($key, $val, $priority);
              }
            echo "</select>\n";
          echo "</td>\n";
        echo "</tr>\n";

        # Sensors
        echo "<tr>\n";
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

        echo "<tr class='datatr'>";
          echo "<td class='datatd'>Frequency: </td>\n";
          echo "<td class='datatd'>";
            echo "<select name='int_frequency' style='background-color:white;' onclick=\"if (this.selectedIndex == 0) {document.getElementById('frequency-interval-day').style.display='none';document.getElementById('frequency-interval-week').style.display='none';} if (this.selectedIndex == 1) {document.getElementById('frequency-interval-day').style.display='';document.getElementById('frequency-interval-week').style.display='none'; }if (this.selectedIndex == 2) {document.getElementById('frequency-interval-day').style.display='none';document.getElementById('frequency-interval-week').style.display='';} \">\n";
              foreach ($mail_frequency_ar as $key=>$val) {
                echo "    " . printOption($key, $val, $frequency);
              }
            echo "</select>\n";
          echo "</td>\n";
        echo "</tr>\n";
        echo "<tr class='datatr' style='display:none;' id='frequency-interval-day' name='frequency-interval-day'>";
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
        echo "<tr class='datatr' style='display:none;' id='frequency-interval-week' name='frequency-interval-week'>";
          echo "<td class='datatd'>At: </td>\n";
          echo "<td class='datatd'>";
            echo "<select name='int_intervalweek' style='background-color:white;'>\n";
              $j = (4 * 86400); // monday
              for ($i = 0; $i < 7; $i++) {
                // use php's date function to print the day
                echo "    " . printOption($i, date("l", $j), $interval_week);
                $j += 86400; // add one day
              }
            echo "</select>\n";
          echo "</td>\n";
        echo "</tr>\n";
      echo "</table>\n";
      echo "<br />";
    }
  }
  echo "<input type='submit' class='button' name='submitBtn' value='Next'>\n";
  echo "<input type='button' name='b1' value='Back' onclick=\"window.location.href='mailadmin.php?int_userid=$user_id';\" class='button'>\n";
echo "</form>\n";
if (($step == 2) && ($template == 3)) {
  echo "<div name='threshold_user' id='threshold_user' style='position:absolute;left:400px;top:130px;border:1px solid black;padding:10px;'>Threshold: </div>";
  ?>
  <script language="javascript" type="text/javascript">
  updateThreshold();
  </script>
  <?
}

function write_report_template_threshold_fields() {
  global $priority, $target, $timespan, $operator, $value, $value_user, $deviation, $mail_priority_ar, $mail_target_ar, $mail_timespan_ar, $mail_operator_ar;
  # Target
  echo "<tr class='datatr'>";
    echo "<td class='datatd'>Target: </td>\n";
    echo "<td class='datatd'>";
      echo "<select name='int_target' id='target' style='background-color:white;'>\n";
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
      echo "<select name='int_timespan' id='timespan' style='background-color:white;'>\n";
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
      echo "<select name='int_operator' id='operator' style='background-color:white;'>\n";
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

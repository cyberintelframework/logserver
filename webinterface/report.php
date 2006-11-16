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

$s_org = $_SESSION['s_org'];
$s_admin = $_SESSION['s_admin'];
$s_userid = $_SESSION['s_userid'];
$s_access = $_SESSION['s_access'];
$s_access_user = $s_access{2};
$err = 0;

$userid = intval($_GET["userid"]);
// Permissions for this userid?
if ($s_access_user == 0) {
  echo "<p style='color:red;'><b>You don't have sufficient rights to perform the requested action.</b></p>\n";
  footer();
  exit;
} elseif ($s_userid <> $userid && $s_access_user < 2 ) {
  $userid = $s_userid;
} elseif ($s_access < 9) {
  $sql_login = "SELECT * FROM login WHERE organisation = $s_org AND id = $userid";
  $result_login = pg_query($pgconn, $sql_login);
  $numrows_login = pg_num_rows($result_login);
  if ($numrows_login == 0) {
    echo "<p style='color:red;'><b>You don't have sufficient rights to perform the requested action.</b></p>\n";
    footer();
    exit;
  }
}

$allowed_action = array("add", "edit", "del");
$action = $_GET["action"];

if (in_array($action, $allowed_action)) {
	switch ($action) {
		case "edit":
			$report_content_id = intval($_GET["report_content_id"]);
			if ($report_content_id > 0) {
				# Getting data from database
				$sql_report_content = "SELECT report_content.* FROM report, report_content WHERE report_content.report_id = report.id AND report.user_id = '$userid' AND report_content.id = '$report_content_id' LIMIT 1 OFFSET 0";
				$result_report_content = pg_query($sql_report_content);
				if (pg_num_rows($result_report_content) == 1) {
					$report_content = pg_fetch_assoc($result_report_content);
					$template = $report_content["template"];
					$priority = $report_content["priority"];
					$frequency = $report_content["frequency"];
					
					# Template: Thresholds
					if ($template == 3) {
						if (intval($_GET["submit"]) == 1) {
							# Submit data, update database record
							$title = pg_escape_string(htmlentities(strip_tags(trim($_GET["title"]))));
							$sensor_id = intval($_GET["sensor_id"]);
							$priority = intval($_GET["priority"]);
							$target = intval($_GET["target"]);
							$timespan = intval($_GET["timespan"]);
							$operator = intval($_GET["operator"]);
							$value = intval($_GET["value"]);
							$active = pgboolval($_GET["active"]);
							if ($value == -2) $value = intval($_GET["value_user"]);
							$deviation = intval($_GET["deviation"]);
							
							if (empty($title)) echo "<p style='color:red;'><b>Invalid title</b></p>\n";
							else {
								// Save data
								$result = pg_query("UPDATE report_content SET title = '$title', sensor_id = '$sensor_id', active = '$active', priority = '$priority', frequency = '$timespan', interval = 0 WHERE id = '$report_content_id'");
								$msg = pg_errormessage();
								if (empty($msg)) {
									$result = pg_query("UPDATE report_template_threshold SET target = '$target', operator = '$operator', value = '$value', deviation = '$deviation' WHERE report_content_id = '$report_content_id'");
									$msg = pg_errormessage();
									if (empty($msg)) {
										echo "<p style='color:green'><b>Data updated succesfully</b>.</p>\n";
										// Reload title/status var
										$sql_report_content = "SELECT report_content.* FROM report, report_content WHERE report_content.report_id = report.id AND report.user_id = '$userid' AND report_content.id = '$report_content_id' LIMIT 1 OFFSET 0";
										$result_report_content = pg_query($sql_report_content);
										$report_content = pg_fetch_assoc($result_report_content);
									}
									else echo "<p style='color:red;'>Data couldn't be saved (2).</p>\n";
								} else echo "<p style='color:red;'>Data couldn't be saved (1).</p>\n";
							}
						}
						
						$sql_report_template_threshold = "SELECT * FROM report_template_threshold WHERE report_content_id = '$report_content_id' LIMIT 1 OFFSET 0";
						$result_report_template_threshold = pg_query($sql_report_template_threshold);
						$report_template_threshold = pg_fetch_assoc($result_report_template_threshold);
						
						# Set all values:
						$title = $report_content["title"];
						$sensor_id = $report_content["sensor_id"];
						$priority = $report_content["priority"];
						$active = $report_content["active"];
						$target = $report_template_threshold["target"];
						$operator = $report_template_threshold["operator"];
						$frequency = $report_content["frequency"];
						$timespan = $frequency;
						$value = $report_template_threshold["value"];
						if ($value > 0) {
							// User defined
							$value_user = $value;
							$value = -2;
						} else $value_user = -1;
						$deviation = $report_template_threshold["deviation"];
						
						echo "<b>Edit " . $title . "</b><br /><br />\n";
						
						echo "<div name='threshold_user' id='threshold_user' style='position:absolute;left:400px;top:130px;border:1px solid black;padding:10px;'>Threshold: </div>";
						echo "<form method='get' onclick=\"updateThreshold();\">";
						echo "<input type='hidden' name='action' value='edit'>";
						echo "<input type='hidden' name='userid' value='$userid'>";
						echo "<input type='hidden' name='submit' value='1'>";
						echo "<input type='hidden' name='report_content_id' value='$report_content_id'>\n";
						echo "<table border=0 cellspacing=2 cellpadding=2 class='datatable'>\n";
						echo " <tr class='datatr'>\n";
						echo "  <td class='datatd'>Title: </td>\n";
						echo "  <td class='datatd'><input type='text' name='title' value='$title'></td>\n";
						echo " </tr>\n";
						echo " <tr class='datatr'>\n";
						echo "  <td class='datatd'>Sensor: </td>\n";
						echo "  <td class='datatd'>\n";
						echo "<select name=\"sensor_id\" style=\"background-color:white;\">\n";
					    echo printOption(-1, "All sensors", $sensor_id);
					    if ($s_admin == 1) $sql = "SELECT * FROM sensors, login WHERE login.id = '$userid' AND login.organisation = sensors.organisation ORDER BY sensors.keyname";
					    else $sql = "SELECT * FROM sensors WHERE organisation = '$s_org' ORDER BY keyname";
						$query = pg_query($sql);
						while ($sensor_data = pg_fetch_assoc($query)) {
							$label = $sensor_data["keyname"];
							echo printOption($sensor_data["id"], $label, $sensor_id);
						}
						echo "</select>\n";
						echo "  </td>\n";
						echo " </tr>\n";
						# Priority
						echo " <tr class='datatr'>";
						echo "  <td class='datatd'>Alert priority: </td>\n";
						echo "  <td class='datatd'>";
						echo "   <select name='priority' id='priority' style='background-color:white;'>\n";
						foreach ($mail_priority_ar as $key=>$val) {
							echo "    " . printOption($key, $val, $priority);
						}
						echo "   </select>\n";
						echo "  </td>\n";
						echo " </tr>\n";
						write_report_template_threshold_fields();
						echo " <tr class='datatr'>\n";
						echo "  <td class='datatd'>Status: </td>\n";
						echo "  <td class='datatd'>\n";
						echo "   <select name='active' style='background-color:white;'>\n";
						echo "    " . printOption('t', "Active", $active);
						echo "    " . printOption('f', "Inactive", $active);
						echo "   </select>\n";
						echo "  </td>\n";
						echo " </tr>\n";
						echo "</table>\n";
						echo "<input type='submit' name='submitBtn' value='Update' class='button'>\n";
						echo "&nbsp;&nbsp;";
						echo "<input type='button' name='b1' value='Back' onclick=\"window.location.href='./useredit.php?userid=$userid';\" class='button'>\n";
						echo "</form>\n";
						echo "<script language=\"javascript\" type=\"text/javascript\">updateThreshold();</script>\n";
					} else {
						# Handle submitted data
						if (intval($_GET["submit"]) == 1) {
							$title = pg_escape_string(htmlentities(strip_tags(trim($_GET["title"]))));
							$sensor_id = intval($_GET["sensor_id"]);
							$priority = intval($_GET["priority"]);
							$frequency = intval($_GET["frequency"]);
							$interval_day = intval($_GET["interval_day"]);
							$interval_week = intval($_GET["interval_week"]);
							$active = pgboolval($_GET["active"]);
							if ($frequency == 1) $interval_db = 0;
							elseif ($frequency == 2) $interval_db = $interval_day;
							elseif ($frequency == 3) $interval_db = $interval_week;
							if (empty($title)) echo "<p style='color:red;'><b>Invalid title</b></p>\n";
							else {
								// Save data
								$result = pg_query("UPDATE report_content SET title = '$title', sensor_id = '$sensor_id', priority = '$priority', frequency = '$frequency', interval = '$interval_db', active = '$active' WHERE id = '$report_content_id'");
								$msg = pg_errormessage();
								if (empty($msg)) {
									echo "<p style='color:green'><b>Data updated succesfully</b>.</p>\n";
									// Reload title/status var
									$sql_report_content = "SELECT report_content.* FROM report, report_content WHERE report_content.report_id = report.id AND report.user_id = '$userid' AND report_content.id = '$report_content_id' LIMIT 1 OFFSET 0";
									$result_report_content = pg_query($sql_report_content);
									$report_content = pg_fetch_assoc($result_report_content);
								} else echo "<p style='color:red;'>Data couldn't be saved (1).</p>\n";
							}
						}
						
						# Prepare data
						$frequency = $report_content["frequency"];
						if ($frequency == 2) $interval_day = $report_content["interval"];
						else $interval_day = -1;
						if ($frequency == 3) $interval_week = $report_content["interval"];
						else $interval_week = -1;
						$title = $report_content["title"];
						$sensor_id = $report_content["sensor_id"];
						$priority = $report_content["priority"];
						$active = $report_content["active"];
						
						# Template: All attacks or Own ranges
						echo "<b>Edit " . $title . "</b><br /><br />\n";
						echo "<form method='get' onclick=\"updateThreshold();\">";
						echo "<input type='hidden' name='action' value='edit'>";
						echo "<input type='hidden' name='userid' value='$userid'>";
						echo "<input type='hidden' name='submit' value='1'>";
						echo "<input type='hidden' name='report_content_id' value='$report_content_id'>\n";
						echo "<table border=0 cellspacing=2 cellpadding=2 class='datatable'>\n";
						echo " <tr class='datatr'>\n";
						echo "  <td class='datatd'>Title: </td>\n";
						echo "  <td class='datatd'><input type='text' name='title' value='$title'></td>\n";
						echo " </tr>\n";
						echo " <tr class='datatr'>\n";
						echo "  <td class='datatd'>Sensor: </td>\n";
						echo "  <td class='datatd'>\n";
						echo "<select name=\"sensor_id\" style=\"background-color:white;\">\n";
					    echo printOption(-1, "All sensors", $sensor_id);
					    if ($s_admin == 1) $sql = "SELECT * FROM sensors, login WHERE login.id = '$userid' AND login.organisation = sensors.organisation ORDER BY sensors.keyname";
					    else $sql = "SELECT * FROM sensors WHERE organisation = '$s_org' ORDER BY keyname";
						$query = pg_query($sql);
						while ($sensor_data = pg_fetch_assoc($query)) {
							$label = $sensor_data["keyname"];
							echo printOption($sensor_data["id"], $label, $sensor_id);
						}
						echo "</select>\n";
						echo "  </td>\n";
						echo " </tr>\n";
						# Priority
						echo " <tr class='datatr'>";
						echo "  <td class='datatd'>Alert priority: </td>\n";
						echo "  <td class='datatd'>";
						echo "   <select name='priority' id='priority' style='background-color:white;'>\n";
						foreach ($mail_priority_ar as $key=>$val) {
							echo "    " . printOption($key, $val, $priority);
						}
						echo "   </select>\n";
						echo "  </td>\n";
						echo " </tr>\n";
						echo " <tr class='datatr'>";
						echo "  <td class='datatd'>Frequency: </td>\n";
						echo "  <td class='datatd'>";
						echo "   <select name='frequency' style='background-color:white;' onclick=\"if (this.selectedIndex == 0) {document.getElementById('frequency-interval-day').style.display='none';document.getElementById('frequency-interval-week').style.display='none';} if (this.selectedIndex == 1) {document.getElementById('frequency-interval-day').style.display='';document.getElementById('frequency-interval-week').style.display='none'; }if (this.selectedIndex == 2) {document.getElementById('frequency-interval-day').style.display='none';document.getElementById('frequency-interval-week').style.display='';} \">\n";
						foreach ($mail_frequency_ar as $key=>$val) {
							echo "    " . printOption($key, $val, $frequency);
						}
						echo "   </select>\n";
						echo "  </td>\n";
						echo " </tr>\n";
						if ($interval_day > -1) $style = "";
						else $style = " style='display:none;'";
						echo " <tr class='datatr' $style id='frequency-interval-day' name='frequency-interval-day'>";
						echo "  <td class='datatd'>At: </td>\n";
						echo "  <td class='datatd'>";
						echo "   <select name='interval_day' style='background-color:white;'>\n";
						for ($i = 0; $i < 24; $i++) {
							$time = "$i:00 hour";
							if ($i < 10) $time = "0" . $time;
							echo "    " . printOption($i, $time, $interval_day);
						}
						echo "   </select>\n";
						echo "  </td>\n";
						echo " </tr>\n";
						echo " </tr>\n";
						if ($interval_week > -1) $style = "";
						else $style = " style='display:none;'";
						echo " <tr class='datatr' $style id='frequency-interval-week' name='frequency-interval-week'>";
						echo "  <td class='datatd'>At: </td>\n";
						echo "  <td class='datatd'>";
						echo "   <select name='interval_week' style='background-color:white;'>\n";
						$j = (4 * 86400); // monday
						for ($i = 1; $i < 8; $i++) {
							// Daynr 1 = monday, 0 = sunday
							$daynr = $i;
							$daynr = ($daynr % 7);
							// use php's date function to print the day
							echo "    " . printOption($daynr, date("l", $j), $interval_week);
							$j += 86400; // add one day
						}
						echo "   </select>\n";
						echo "  </td>\n";
						echo " </tr>\n";
						echo " <tr class='datatr'>\n";
						echo "  <td class='datatd'>Status: </td>\n";
						echo "  <td class='datatd'>\n";
						echo "   <select name='active' style='background-color:white;'>\n";
						echo "    " . printOption('t', "Active", $active);
						echo "    " . printOption('f', "Inactive", $active);
						echo "   </select>\n";
						echo "  </td>\n";
						echo " </tr>\n";
						echo "</table>\n";
						echo "<input type='submit' class='button' name='submitBtn' value='Update'>\n";
						echo "<input type='button' name='b1' value='Back' onclick=\"window.location.href='./useredit.php?userid=$userid';\" class='button'>\n";
						echo "<br />";
						echo "</form>\n";
					}
				} else echo "<p style='color:red;'><b>You don't have sufficient rights to edit this report</b>.</p>\n";
			} else echo "<p style='color:red;'><b>Invalid report</b>.</p>\n";
		break;
		case "add":
			echo "<h3>" . ucfirst($action) . " report wizard</h3>\n";
			$step = intval($_GET["nextstep"]);
			if (($step <= 0) || ($step > 3)) $step = 1;
			$request_step = $step;
			
			if (isset($_GET["nextstep"])) {
				# Set submitted data
				// From step 1:
				$title = pg_escape_string(htmlentities(strip_tags(trim($_GET["title"]))));
				$sensor_id = intval($_GET["sensor_id"]);
				$priority = intval($_GET["priority"]);
				$template = intval($_GET["template"]);
				if ($step > 2) {
					// From step 2:
					if ($template == 3) {
						$target = intval($_GET["target"]);
						$timespan = intval($_GET["timespan"]);
						$frequency = $timespan;
						$operator = intval($_GET["operator"]);
						$value = intval($_GET["value"]);
						if ($value == -2) $value = intval($_GET["value_user"]);
						$deviation = intval($_GET["deviation"]);
						$interval_db = 0;
					} else {
						$frequency = intval($_GET["frequency"]);
						$interval_day = intval($_GET["interval_day"]);
						$interval_week = intval($_GET["interval_week"]);
						if ($frequency == 1) $interval_db = 0;
						elseif ($frequency == 2) $interval_db = $interval_day;
						elseif ($frequency == 3) $interval_db = $interval_week;
					}
				}
				
				# Check fields from previous steps (submitted data)
				// Step 1:
				if (empty($title)) $step = 1;
				elseif (($template < 1) || ($template > 3)) $step = 1;
				// Step 2: no checks needed
				
				if (($step == 3) && ($step == $request_step)) {
				# All clear, save data
					// Get report_id:
//					$query = pg_query("SELECT id FROM report WHERE user_id = '$userid' LIMIT 1 OFFSET 0");
//					$report_id = intval(@pg_result($query, 0));
//					if ($report_id > 0) {
						// Table report_content
						$query = pg_query("INSERT INTO report_content (user_id, title, priority, sensor_id, interval, frequency, template, active) VALUES ('$userid', '$title', '$priority', '$sensor_id', '$interval_db', '$frequency', '$template', 't')");
						if (pg_affected_rows($query) == 1) {
							$query = pg_query("SELECT currval('report_content_id_seq') AS last_insert_id FROM report_content");
							$report_content_id = intval(@pg_result($query, 0));
							if ($template == 3) {								
								// Insert in table report_template_threshold
								$query = pg_query("INSERT INTO report_template_threshold (report_content_id, target, operator, value, deviation) VALUES ('$report_content_id', '$target', '$operator', '$value', '$deviation')");
							}
							echo "<p style='color:green;'>Data saved!</p>\n";
						} else echo "<p style='color:red;'>Data couldn't be saved.</p>\n";
//					} else echo "<p style='color:red;'>No reference found for this user, does this user have a record in table 'report'?</p>\n";
				}
			}
			
			# Display error message
			if ($step != $request_step) echo "<p style='color:red;'><b>Please complete all fields.</b></p>\n";
			
			echo "<h4>Step $step of 3</h4>\n";
			if ($step == 3) {
				# Save data
				echo "<b>Report added succesfully</b>.<br />\n";
				echo "What would you like to do now:<br />\n";
				echo "<ul>\n";
				echo " <li><a href='./report.php?action=add&userid=$userid'>Add another report</a></li>\n";
				echo " <li><a href='./useredit.php?userid=$userid'>Go back to the mailreporting screen</a></li>\n";
				echo "</ul>\n";
			}
			echo "<form method='get'";
			if (($step == 2) && ($template == 3)) echo " onclick=\"updateThreshold();\"";
			echo ">\n";
			echo "<input type='hidden' name='action' value='add'>";
			echo "<input type='hidden' name='userid' value='$userid'>";
			echo "<input type='hidden' name='nextstep' id='nextstep' value='" . ($step + 1) . "'>\n";
			if ($step == 1) {
				echo "<table border=0 cellspacing=2 cellpadding=2 class='datatable'>\n";
				echo " <tr class='datatr'>";
				echo "  <td class='datatd'>Title: </td>\n";
				echo "  <td class='datatd'><input type='text' name='title' value='$title'></td>\n";
				echo " </tr>\n";
				# Priority
				echo " <tr class='datatr'>";
				echo "  <td class='datatd'>Alert priority: </td>\n";
				echo "  <td class='datatd'>";
				echo "   <select name='priority' id='priority' style='background-color:white;'>\n";
				foreach ($mail_priority_ar as $key=>$val) {
					echo "    " . printOption($key, $val, $priority);
				}
				echo "   </select>\n";
				echo "  </td>\n";
				echo " </tr>\n";
				echo "  <td class='datatd'>Sensor: </td>\n";
				echo "  <td class='datatd'>\n";
				echo "<select name=\"sensor_id\" style=\"background-color:white;\">\n";
			    echo printOption(-1, "All sensors", $sensor_id);
			    if ($s_admin == 1) $sql = "SELECT * FROM sensors, login WHERE login.id = '$userid' AND login.organisation = sensors.organisation ORDER BY sensors.keyname";
			    else $sql = "SELECT * FROM sensors WHERE organisation = '$s_org' ORDER BY keyname";
				$query = pg_query($sql);
				while ($sensor_data = pg_fetch_assoc($query)) {
					$label = $sensor_data["keyname"];
					echo printOption($sensor_data["id"], $label, $sensor_id);
				}
				echo "</select>\n";
				echo "  </td>\n";
				echo " <tr class='datatr'>";
				echo "  <td class='datatd'>Template: </td>\n";
				echo "  <td class='datatd'>";
				echo "   <select name='template' style='background-color:white;'>\n";
				foreach ($mail_template_ar as $key=>$val) {
					echo "    " . printOption($key, $val, $template);
				}
				echo "   </select>\n";
				echo "  </td>\n";
				echo " </tr>\n";
				echo "</table>\n";
				echo "<br />";
			} elseif ($step == 2) {
				echo "<input type='hidden' name='title' value='$title'>";
				echo "<input type='hidden' name='priority' value='$priority'>";
				echo "<input type='hidden' name='template' value='$template'>";
				echo "<input type='hidden' name='sensor_id' value='$sensor_id'>";
				if ($template == 3) {
					# Thresholds
					if (!isset($priority)) $priority = 2;
					if (!isset($operator)) $operator = ">";
					if (!isset($deviation)) $deviation = 10;
					
					echo "<table border=0 cellspacing=2 cellpadding=2 class='datatable'>\n";
					write_report_template_threshold_fields();
					echo "</table>\n";
					echo "<br />";
					echo "<input type='submit' class='button' name='submitBtn2' value='Previous' onclick=\"document.getElementById('nextstep').value=" . ($step - 1) . "\">\n";
				} else {
					# Default
					echo "<table border=0 cellspacing=2 cellpadding=2 class='datatable'>\n";
					echo " <tr class='datatr'>";
					echo "  <td class='datatd'>Frequency: </td>\n";
					echo "  <td class='datatd'>";
					echo "   <select name='frequency' style='background-color:white;' onclick=\"if (this.selectedIndex == 0) {document.getElementById('frequency-interval-day').style.display='none';document.getElementById('frequency-interval-week').style.display='none';} if (this.selectedIndex == 1) {document.getElementById('frequency-interval-day').style.display='';document.getElementById('frequency-interval-week').style.display='none'; }if (this.selectedIndex == 2) {document.getElementById('frequency-interval-day').style.display='none';document.getElementById('frequency-interval-week').style.display='';} \">\n";
					foreach ($mail_frequency_ar as $key=>$val) {
						echo "    " . printOption($key, $val, $frequency);
					}
					echo "   </select>\n";
					echo "  </td>\n";
					echo " </tr>\n";
					echo " <tr class='datatr' style='display:none;' id='frequency-interval-day' name='frequency-interval-day'>";
					echo "  <td class='datatd'>At: </td>\n";
					echo "  <td class='datatd'>";
					echo "   <select name='interval_day' style='background-color:white;'>\n";
					for ($i = 0; $i < 24; $i++) {
						$time = "$i:00 hour";
						if ($i < 10) $time = "0" . $time;
						echo "    " . printOption($i, $time, $interval_day);
					}
					echo "   </select>\n";
					echo "  </td>\n";
					echo " </tr>\n";
					echo " </tr>\n";
					echo " <tr class='datatr' style='display:none;' id='frequency-interval-week' name='frequency-interval-week'>";
					echo "  <td class='datatd'>At: </td>\n";
					echo "  <td class='datatd'>";
					echo "   <select name='interval_week' style='background-color:white;'>\n";
					$j = (4 * 86400); // monday
					for ($i = 0; $i < 7; $i++) {
						// use php's date function to print the day
						echo "    " . printOption($i, date("l", $j), $interval_week);
						$j += 86400; // add one day
					}
					echo "   </select>\n";
					echo "  </td>\n";
					echo " </tr>\n";
					echo "</table>\n";
					echo "<br />";
				}
			}
			echo "<input type='submit' class='button' name='submitBtn' value='Next'>\n";
			echo "<input type='button' name='b1' value='Back' onclick=\"window.location.href='./useredit.php?userid=$userid';\" class='button'>\n";
			echo "</form>\n";
			if (($step == 2) && ($template == 3)) {
				echo "<div name='threshold_user' id='threshold_user' style='position:absolute;left:400px;top:130px;border:1px solid black;padding:10px;'>Threshold: </div>";
				?>
				<script language="javascript" type="text/javascript">
				updateThreshold();
				</script>
				<?
			}
		break;
		case "del":
			$report_content_id = intval($_GET["report_content_id"]);
			if ($report_content_id > 0) {
				# Getting data from database
				$sql_report_content = "SELECT report_content.* FROM report, report_content WHERE report_content.report_id = report.id AND report.user_id = '$userid' AND report_content.id = '$report_content_id' LIMIT 1 OFFSET 0";
				$result_report_content = pg_query($sql_report_content);
				if (pg_num_rows($result_report_content) == 1) {
					$report_content = pg_fetch_assoc($result_report_content);
					
					# Submit data
					if (intval($_GET["submit"]) == 1) {
						// First remove refence table
						if ($report_content["template"] == 3) {
							// Reference table: report_template_threshold
							$ref_table = "report_template_threshold";
							$sql = "DELETE FROM $ref_table WHERE report_content_id = '$report_content_id'";
							$result = pg_query($sql);
						}						
						$sql = "DELETE FROM report_content WHERE id = '$report_content_id'";
						$result = pg_query($sql);
						if (pg_affected_rows($result) == 1) {
							echo "<p style='color:green;'><b>Data succesfully removed.</b></p>\n";
							echo "<p><a href='./useredit.php?userid=$userid'>Back</a></p>\n";
							footer();
							exit;
						} else echo "<p style='color:red;'>Data couldn't be removed (2).</p>\n";
					}
					
					echo "<b>Delete " . $report_content["title"] . "</b><br /><br />\n";
					echo "Are you sure you want to delete this report?<br /><br />\n";
					echo "<form method='get'>\n";
					echo "<input type='hidden' name='action' value='del'>";
					echo "<input type='hidden' name='userid' value='$userid'>";
					echo "<input type='hidden' name='submit' value='1'>";
					echo "<input type='hidden' name='report_content_id' value='$report_content_id'>";
					echo "<input type='submit' name='submitBtn' value='Yes' class='button'>\n";
					echo "<input type='button' name='b1' value='No' onclick=\"window.location.href='./useredit.php?userid=$userid';\" class='button'>\n";
					echo "</form>\n";
				}
			}
		break;
	}
}

footer();

function write_report_template_threshold_fields() {
	global $priority, $target, $timespan, $operator, $value, $value_user, $deviation, $mail_priority_ar, $mail_target_ar, $mail_timespan_ar, $mail_operator_ar;
	# Target
	echo " <tr class='datatr'>";
	echo "  <td class='datatd'>Target: </td>\n";
	echo "  <td class='datatd'>";
	echo "   <select name='target' id='target' style='background-color:white;'>\n";
	foreach ($mail_target_ar as $key=>$val) {
		echo "    " . printOption($key, $val, $target);
	}
	echo "   </select>\n";
	echo "  </td>\n";
	echo " </tr>\n";
	# Timespan
	echo " <tr class='datatr'>";
	echo "  <td class='datatd'>Timespan: </td>\n";
	echo "  <td class='datatd'>";
	echo "   <select name='timespan' id='timespan' style='background-color:white;'>\n";
	foreach ($mail_timespan_ar as $key=>$val) {
		echo "    " . printOption($key, $val, $timespan);
	}
	echo "   </select>\n";
	echo "  </td>\n";
	echo " </tr>\n";
	# Operator
	echo " <tr class='datatr'>";
	echo "  <td class='datatd'>Operator: </td>\n";
	echo "  <td class='datatd'>";
	echo "   <select name='operator' id='operator' style='background-color:white;'>\n";
	foreach ($mail_operator_ar as $key=>$val) {
		echo "    " . printOption($key, htmlentities($val), $operator);
	}
	echo "   </select>\n";
	echo "  </td>\n";
	echo " </tr>\n";
	# Value
	echo " <tr class='datatr'>";
	echo "  <td class='datatd'>Value: </td>\n";
	echo "  <td class='datatd'>";
	echo "   <select name='value' id='value' style='background-color:white;' onchange=\"if (this.selectedIndex == 1) { document.getElementById('value_user').style.display=''; } else { document.getElementById('value_user').style.display='none'; }\">\n";
	echo "    " . printOption(-1, "Average", $value);
	echo "    " . printOption(-2, "User defined", $value);
	echo "   </select>\n";
	echo "   <br>\n";
	if ($value_user > 0) $value_user_style = "";
	else $value_user_style = " style=\"display:none\";";
	echo "   <input type='text' name='value_user' id='value_user' value='$value_user'$value_user_style>";
	echo "  </td>\n";
	echo " </tr>\n";
	echo " <tr class='datatr'>\n";
	echo "  <td class='datatd'>Deviation: </td>\n";
	echo "  <td class='datatd'><input type='text' name='deviation' id='deviation' value='$deviation' maxlength='2' style='width:30px;'> %</td>\n";
	echo " </tr>\n";
}
?>

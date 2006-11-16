<?php include("menu.php"); set_title("User Admin"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.01                  #
# 06-11-2006                       #
# Jan van Lith & Kees Trippelvitz  #
# Modified by Peter Arts           #
####################################

#############################################
# Changelog:
# 1.04.01 Rereleased as 1.04.01
# 1.02.07 Added some more input checks and removed includes
# 1.02.06 Enhanced debugging
# 1.02.05 Fixed a userid bug
# 1.02.04 Automatic table creation if user doesn't have mailreporting record
# 1.02.03 Extended mailreporting
# 1.02.02 Initial release
#############################################

$s_org = intval($_SESSION['s_org']);
$s_userid = intval($_SESSION['s_userid']);
$s_access = $_SESSION['s_access'];
$s_access_user = intval($s_access{2});
$err = 0;

if ($s_access_user < 2) {
  $userid = $s_userid;
} else {
  $userid = intval($_GET['userid']);
}

if (isset($_GET['m'])) {
  $m = intval($_GET['m']);
  $m = stripinput($errors[$m]);
  $m = "<p>$m</p>";
  echo "<font color='red'>" .$m. "</font>";
}

if ($s_access_user == 9) {
  $sql_user = "SELECT * FROM login WHERE id = $userid";
} else {
  $sql_user = "SELECT * FROM login WHERE id = $userid AND organisation = $s_org";
}
$result_user = pg_query($pgconn, $sql_user);
$numrows_user = pg_num_rows($result_user);

# Debug info
if ($debug == 1) {
  echo "<pre>";
  echo "SQL_USER: $sql_user";
  echo "</pre>\n";
}

if ($numrows_user == 0) {
  $err = 1;
  $m = 39;
  pg_close($pgconn);
  header("location: useradmin.php?m=$m");
  exit;
} else {
  $access_user = pg_result($result_user, "access");
  $access_user = intval($access_user{2});
}

if ($s_access_user < $access_user) {
  $err = 1;
  $m = 90;
  pg_close($pgconn);
  header("location: useradmin.php?m=$m");
  exit;
}

if ($err == 0) {
  $row = pg_fetch_assoc($result_user);

  $username = $row['username'];
  $org = $row['organisation'];
  $email = $row['email'];
  $maillog = $row['maillog'];
  $access = $row['access'];
  $gpg = $row['gpg'];
  $access_sensor = $access{0};
  $access_search = $access{1};
  $access_user = $access{2};
  
  echo "<table border=0 cellspacing=0 cellpadding=0><tr><td valign='top'>\n";

  echo "<form name='usermodify' action='usersave.php' method='post' onsubmit='return encrypt_pass();'>\n";
    echo "<table class='datatable'>\n";
      echo "<tr class='datatr'>\n";
        echo "<td class='datatd'>Username</td>\n";
        if ($s_access_user > 0) {
          echo "<td class='datatd'><input type='text' name='f_username' size='30' value='$username' /></td>\n";
        } else {
          echo "<td class='datatd'>$username<input type='hidden' /></td>\n";
        }
      echo "</tr>\n";
      echo "<tr class='datatr'>\n";
        echo "<td class='datatd'>Password</td>\n";
        echo "<td class='datatd'><input type='password' size='30' value='' /><input type='hidden' name='f_pass' /></td>\n";
      echo "</tr>\n";
      echo "<tr class='datatr'>\n";
        echo "<td class='datatd'>Confirm Password</td>\n";
        echo "<td class='datatd'><input type='password' size='30' value='' /><input type='hidden' name='f_confirm' /></td>\n";
      echo "</tr>\n";
      echo "<tr class='datatr'>\n";
        echo "<td class='datatd'>Organisation</td>\n";
        echo "<td>\n";
          if ($s_access_user == 9) {
            echo "<select name='f_org'>\n";
              echo "<option value='none'></option>\n";

              $sql_org = "SELECT id, organisation FROM organisations";
              $result_org = pg_query($pgconn, $sql_org);
              while ($row_org = pg_fetch_assoc($result_org)) {
                $d_org_id = $row_org['id'];
                $d_org_name = $row_org['organisation'];
                echo "" . printOption($d_org_id, $d_org_name, $org) . "\n";
              }
            echo "</select>\n";
          } else {
            $sql_org = "SELECT organisation FROM organisations WHERE id = $org";
            $result_org = pg_query($pgconn, $sql_org);
            $db_org_name = pg_result($result_org, 0);
            echo "<input type='hidden' name='f_org' value='$db_org_name' />\n";
            echo "$db_org_name";
          }
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td class='datatd'>Email address</td>\n";
        echo "<td class='datatd'>";
          echo "<input type='text' name='f_email' value='" . $email . "' size='30'><br />";
        echo "</td>\n";
      echo "</td>\n";
      echo "<tr>\n";
        echo "<td class='datatd'>Email signing</td>\n";
        echo "<td class='datatd'>\n";
          echo printRadio("Enable GPG signing", "f_gpg", 1, $gpg) . "<br />\n";
          echo printRadio("Disable GPG signing", "f_gpg", 0, $gpg) . "<br />\n";
        echo "</td>\n";
      echo "</tr>\n";


      if ($s_access_user > 1) {
        #### Access: Sensor ####
        echo "<tr class='datatr'>\n";
          echo "<td class='datatd' valign='top'>Access: Sensor</td>\n";
          echo "<td class='datatd'>\n";
            echo "" . printRadio("0 - $access_ar_sensor[0]", "f_access_sensor", 0, $access_sensor) . "<br />\n";
            echo "" . printRadio("1 - $access_ar_sensor[1]", "f_access_sensor", 1, $access_sensor) . "<br />\n";
            echo "" . printRadio("2 - $access_ar_sensor[2]", "f_access_sensor", 2, $access_sensor) . "<br />\n";
            if ($s_access_user == 9) {
              echo "" . printRadio("9 - $access_ar_sensor[9]", "f_access_sensor", 9, $access_sensor) . "<br />\n";
            }
          echo "</td>\n";
        echo "</tr>\n";
        #### Access: Search ####
        echo "<tr class='datatr'>\n";
          echo "<td class='datatd' valign='top'>Access: Search</td>\n";
          echo "<td class='datatd'>\n";
            echo "" . printRadio("1 - $access_ar_search[1]", "f_access_search", 1, $access_search) . "<br />\n";
            if ($s_access_user == 9) {
              echo "" . printRadio("9 - $access_ar_search[9]", "f_access_search", 9, $access_search) . "<br />\n";
            }
          echo "</td>\n";
        echo "</tr>\n";
        #### Access: User ####
        echo "<tr class='datatr'>\n";
          echo "<td class='datatd' valign='top'>Access: User Admin</td>\n";
          echo "<td class='datatd'>\n";
            echo "" . printRadio("0 - $access_ar_user[0]", "f_access_user", 0, $access_user) . "<br />\n";
            echo "" . printRadio("1 - $access_ar_user[1]", "f_access_user", 1, $access_user) . "<br />\n";
            if ($s_access_user > 1) {
              echo "" . printRadio("2 - $access_ar_user[2]", "f_access_user", 2, $access_user) . "<br />\n";
            }
            if ($s_access_user == 9) {
              echo "" . printRadio("9 - $access_ar_user[9]", "f_access_user", 9, $access_user) . "<br />\n";
            }
          echo "</td>\n";
        echo "</tr>\n";
      } else {
        echo "<tr class='datatr'>\n";
          echo "<td class='datatd'>Sensor access</td><td class='datatd'>$access_ar_sensor[$access_sensor]</td>\n";
        echo "</tr>\n";
        echo "<tr class='datatr'>\n";
          echo "<td class='datatd'>Search access</td><td class='datatd'>$access_ar_search[$access_search]</td>\n";
        echo "</tr>\n";
        echo "<tr class='datatr'>\n";
          echo "<td class='datatd'>User access</td><td class='datatd'>$access_ar_user[$access_user]</td>\n";
        echo "</tr>\n";
      }
      echo "<tr>\n";
        echo "<td><input type='hidden' name='f_userid' value='$userid' /></td>\n";
        echo "<td align='right'>\n";
          echo "<input type='submit' name='submit' value='update' class='button' />\n";
          echo "<input type='button' name='back' value='back' onclick=parent.location='useradmin.php' class='button' />\n";
        echo "</td>\n";
      echo "</tr>\n";
    echo "</table>\n";
    echo "</form>\n";
}

echo "</td><td width=50>&nbsp;</td><td valign='top'><div style='position:relative;top:0px;'>\n";

# Submit data
if (isset($_GET["submit"])) {
	$error = array();
	if ($_GET["enabled"] == "Y") $update["enabled"] = 't';
	else $update["enabled"] = 'f';
	if ($_GET["gpg_enabled"] == "Y") $update["gpg_enabled"] = 't';
	else $update["gpg_enabled"] = 'f';
	if (validate_email($_GET["email"])) $update["email"] = stripinput(strip_tags(pg_escape_string($_GET["email"])));
	else $error[] = "E-mail address";
	$update["subject"] = strip_tags(pg_escape_string($_GET["subject"]));
	if (empty($update["subject"])) $error[] = "Report subject";
	
	if (@count($error) > 0) {
		// Errors detected
		echo "<p style='color:red;'>An error occured, please complete the next fields:</p>\n";
		echo "<ul>";
		foreach ($error as $msg) echo "<li>$msg</li>\n";
		echo "</ul>\n";
	} else {
		// All clear, go ahead
		$query = pg_query("UPDATE report SET enabled = '" . $update["enabled"] . "', email = '" . $update["email"] . "', gpg_enabled = '" . $update["gpg_enabled"] . "', subject = '" . $update["subject"] . "' WHERE user_id = '$userid'");
		if (pg_affected_rows($query) == 1) echo "<p style='color:green;'>Data succesfully saved.</p>\n";
		else echo "<p style='color:red;'>Data <b>not</b> saved.</p>\n";
	}
}

# Get userdata for mailreporting:
$query = pg_query("SELECT * FROM report WHERE user_id = '$userid' LIMIT 1 OFFSET 0");
$row = pg_fetch_assoc($query);
if ($row === false) {
	$query = pg_query("INSERT INTO report (user_id, enabled, email, gpg_enabled, subject) VALUES ('$userid', 'f', '', 't', 'SURFnet IDS stats for %date%')");
	if (pg_affected_rows($query) <> 1) {
		echo "Report data could't be created.";
		footer;
		exit;
	} else {
		$query = pg_query("SELECT * FROM report WHERE user_id = '$userid' LIMIT 1 OFFSET 0");
		$row = pg_fetch_assoc($query);
	}
}
$report_id = intval($row["id"]);
if ($row["enabled"] == "t") {
	// mailreporting ON:
	$report["enabled"] = " checked";
	$report["style_enabled"] = "";
} else {
	// mailreporting OFF:
	$report["enabled"] = "";
	$report["style_enabled"] = " style=\"display:none;\"";
}
$report["subject"] = $row["subject"];
$report["email"] = $row["email"];
if ($row["gpg_enabled"] == 't') $report["gpg_enabled"] = " checked";
else $report["gpg_enabled"] = "";

echo "<form method='get' action='useredit.php'>\n";
echo "<input type='hidden' name='userid' value='$userid'>\n";
echo "<input type='checkbox' name='enabled' value='Y' id='enabled' style='cursor:pointer;'" . $report["enabled"] . " onclick=\"if(this.checked) { document.getElementById('reports_enabled').style.display='';document.getElementById('reports_disabled').style.display='none'; } else { document.getElementById('reports_enabled').style.display='none';document.getElementById('reports_disabled').style.display=''; }\"><label for='enabled' style='cursor:pointer;' onclick=\"if(this.checked) { document.getElementById('reports_enabled').style.display='';document.getElementById('reports_disabled').style.display='none'; } else { document.getElementById('reports_enabled').style.display='none';document.getElementById('reports_disabled').style.display=''; }\"> Enable mailreporting</label><br /><br />\n";
echo "<input type='submit' name='submitBtn' value='Update' class='button' id='reports_disabled' style='display:none;'>";
echo "<div id='reports_enabled'" . $report["style_enabled"] . "'>\n";
echo "<b>General data</b><br /><br />\n";
echo "<table border=0 cellspacing=2 cellpadding=2 class='datatable'>\n";
echo " <tr class='datatr'>\n";
echo "  <td class='datatd' colspan=2>\n";
echo "   <input type='checkbox' name='gpg_enabled' value='Y' id='gpg_enabled' style='cursor:pointer;'" . $report["gpg_enabled"] . "><label for='gpg_enabled' style='cursor:pointer;'> Sign e-mail messages (gpg)</label><br />\n";
echo "  </td>\n";
echo " </tr>\n";
echo " <tr class='datatr'>\n";
echo "  <td class='datatd'>Send reports to: </td>\n";
echo "  <td class='datatd'><input type='text' name='email' value='" . $report["email"] . "' style='width:200px;'> <i>(e-mail address)</i></td>\n";
echo " </tr>\n";
echo " <tr class='datatr'>\n";
echo "  <td class='datatd'>Report subject: </td>\n";
echo "  <td class='datatd'><input type='text' name='subject' value=\"" . $report["subject"] . "\" style='width:300px;'></td>\n";
echo " </tr>\n";
echo "</table>\n";
echo "<input type='hidden' name='submit' value='1'>\n";
echo "<input type='submit' name='submitBtn' value='Update' class='button'>";
echo "&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; <i>Usage:</i> %date%, %day%, %time%, %hour%<br />\n";
echo "<br /><br />\n";

# Reports
echo "<b>Reports</b><br /><br />";
echo "<table border=0 cellspacing=2 cellpadding=2 class='datatable' width=500>\n";
echo " <tr class='dataheader'>\n";
echo "  <td class='datatd'>Title</td><td class='datatd'>Last sent</td><td class='datatd'>Template</td><td class='datatd'>Status</td>\n";
echo " </tr>\n";

# Get reports
$sql = "SELECT * FROM report_content WHERE report_id = '$report_id' ORDER BY title";
$result_report_content = pg_query($sql);
while ($report_content = pg_fetch_assoc($result_report_content)) {
	$report_content_id = $report_content["id"];
	echo " <tr class='datatr'>\n";
	echo "  <td class='datatd'><a href='./report.php?action=edit&userid=$userid&report_content_id=$report_content_id'>" . $report_content["title"] . "</a></td>\n";
	if ($report_content["last_sent"] == null) $last_sent = "<i>never</i>";
	else $last_sent = date("d-m-Y H:i", $report_content["last_sent"]);
	echo "  <td class='datatd'>" . $last_sent . "</td>\n";
	echo "  <td class='datatd'>" . $mail_template_ar[$report_content["template"]] . "</td>\n";
	if ($report_content["active"] == "t") $status = "<font style='color:green;'>Active</font";
	else $status = "<font style='color:red;'>Inactive</font>";
	echo "  <td class='datatd'>" . $status . "</td>\n";
	echo "  <td width=10>[<a href='./report.php?action=del&userid=$userid&report_content_id=$report_content_id'>X</a>]</td>\n";
	echo " </tr>\n";
}

echo "</table>\n";
echo "<a href='./report.php?action=add&userid=" . intval($_GET["userid"]) . "'>Add report</a>";
echo "</form>\n";
echo "</div>\n";

echo "</div></td></tr></table>\n";

pg_close($pgconn);
footer();
?>

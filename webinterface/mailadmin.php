<?php include("menu.php"); set_title("Mail Admin"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.02                  #
# 11-12-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

####################################
# Changelog:
# 1.04.02 Changed debug stuff
# 1.04.01 Released as 1.04.01
# 1.03.01 Initial release
####################################

$s_org = intval($_SESSION['s_org']);
$s_userid = intval($_SESSION['s_userid']);

$allowed_get = array(
                "int_userid",
		"int_m"
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
    $result_login = pg_query($pgconn, $sql_login);
    $numrows_login = pg_num_rows($result_login);
    if ($numrows_login == 0) {
      $user_id = $s_userid;
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
                "int_gpg",
		"strip_html_escape_email"
);
$check = extractvars($_POST, $allowed_post);
debug_input();

if (isset($clean['email'])) {
  # POST is set. Do save.
  $f_email = $clean['email'];
  $f_gpg = $clean['f_gpg'];

  $sql_update = "UPDATE login ";
  $sql_update .= "SET email = '$f_email', gpg = $f_gpg ";
  if ($s_access_user < 9) {
    $sql_update .= "WHERE id = $user_id AND organisation = '$s_org' ";
  } else {
    $sql_update .= "WHERE id = $user_id ";
  }
  $debuginfo[] = $sql_update;
  $result_update = pg_query($sql_update);
  $clean['m'] = 8;
}

if (isset($clean['m'])) {
  $m = $clean['m'];
  $m = $errors[$m];
  $m = "<p>$m</p>\n";
  echo "<font color='red'>" .$m. "</font>";
}

if ($s_access_user > 0) {
  if ($s_access_user < 9) {
    $sql_user = "SELECT email, gpg FROM login ";
    $sql_user .= "WHERE id = $user_id AND login.organisation = '$s_org' ";
  } else {
    $sql_user = "SELECT email, gpg FROM login WHERE id = $user_id";
  }
  $debuginfo[] = $sql_user;
  $result_user = pg_query($sql_user);
  $row = pg_fetch_assoc($result_user);

  $email = $row['email'];
  $gpg = $row['gpg'];

  echo "<b>Email settings</b><br /><br />\n";
  echo "<form name='emailsettings' action='mailadmin.php?int_userid=$user_id' method='post'>\n";
  echo "<table border='0' class='datatable'>\n";
    echo "<tr>\n";
      echo "<td class='datatd' width='100'>Email address</td>\n";
      echo "<td class='datatd'>";
        echo "<input type='text' name='strip_html_escape_email' value='" . $email . "' size='30'><br />";
      echo "</td>\n";
    echo "</td>\n";
    echo "<tr>\n";
      echo "<td class='datatd'>Email signing</td>\n";
      echo "<td class='datatd'>\n";
        echo printRadio("Enable GPG signing", "int_gpg", 1, $gpg) . "<br />\n";
        echo printRadio("Disable GPG signing", "int_gpg", 0, $gpg) . "<br />\n";
      echo "</td>\n";
    echo "</tr>\n";
    echo "<tr>\n";
      echo "<td class='datatd' align='right' colspan='2'><input type='submit' class='button' value='Update' /></td>\n";
    echo "</tr>\n";
  echo "</table>\n";
  echo "</form>\n";

  echo "<br /><br />\n";

  echo "<b>Reports</b><br /><br />";
  echo "<input type='button' value='Add report' class='button' onClick=window.location='report_add.php?int_userid=$user_id';>&nbsp;&nbsp;|&nbsp;&nbsp;";
  echo "<input type='button' value='Disable all reports' class='button' onClick=window.location='report_mod.php?int_userid=$user_id&a=d';>&nbsp;&nbsp;|&nbsp;&nbsp;";
  echo "<input type='button' value='Enable all reports' class='button' onClick=window.location='report_mod.php?int_userid=$user_id&a=e';>&nbsp;&nbsp;|&nbsp;&nbsp;";
  echo "<input type='button' value='Reset all report timestamps' class='button' onClick=window.location='report_mod.php?int_userid=$user_id&a=r';><br /><br />";
  echo "<table border=0 cellspacing=2 cellpadding=2 class='datatable'>\n";
    echo "<tr class='dataheader'>\n";
      echo "<td class='datatd' width='400'>Title</td>\n";
      echo "<td class='datatd' width='150'>Last sent</td>\n";
      echo "<td class='datatd' width='100'>Template</td>\n";
      echo "<td class='datatd' width='100'>Status</td>\n";
      echo "<td class='datatd'>Delete</td>\n";
    echo "</tr>\n";

    # Get reports
    if ($s_access_user < 9) {
      $sql = "SELECT report_content.* FROM report_content, login ";
      $sql .= "WHERE user_id = $user_id AND report_content.user_id = login.id AND login.organisation = '$s_org' ";
      $sql .= "ORDER BY title";
    } else {
      $sql = "SELECT * FROM report_content WHERE user_id = $user_id ORDER BY title";
    }
    $result_report_content = pg_query($sql);
    $debuginfo[] = $sql;

    while ($report_content = pg_fetch_assoc($result_report_content)) {
      $report_content_id = $report_content["id"];
      if ($report_content["active"] == "t") {
        $status = "<font style='color:green;'>Active</font";
      } else {
        $status = "<font style='color:red;'>Inactive</font>";
      }
      if ($report_content["last_sent"] == null) {
        $last_sent = "<i>never</i>";
      } else {
        $last_sent = date("d-m-Y H:i", $report_content["last_sent"]);
      }

      echo "<tr class='datatr'>\n";
        echo "<td class='datatd'>";
          echo "<a href='report_edit.php?int_userid=$user_id&int_rcid=$report_content_id'>" . $report_content["title"] . "</a>";
        echo "</td>\n";
        echo "<td class='datatd'>" . $last_sent . "</td>\n";
        echo "<td class='datatd'>" . $mail_template_ar[$report_content["template"]] . "</td>\n";
        echo "<td class='datatd'>" . $status . "</td>\n";
        echo "<td align='center'><a href='report_del.php?int_userid=$user_id&int_rcid=$report_content_id'><img src='images/icons/email_delete_20.gif' alt='Delete Report' title='Delete Report' /></a></td>\n";
      echo "</tr>\n";
    }

  echo "</table>\n";
}

pg_close($pgconn);
debug_sql();
footer();
?>
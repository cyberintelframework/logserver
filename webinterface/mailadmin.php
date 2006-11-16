<?php include("menu.php"); set_title("Mail Admin"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.01                  #
# 13-11-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

####################################
# Changelog:
# 1.04.01 Initial release
####################################

$s_org = intval($_SESSION['s_org']);
$s_userid = intval($_SESSION['s_userid']);

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
      $user_id = $s_userid;
    } else {
      $user_id = intval($_GET['userid']);
    }
  } else {
    $user_id = intval($_GET['userid']);
  }
} else {
  $user_id = $s_userid;
}

if (isset($_POST['f_email'])) {
  # POST is set. Do save.
  $f_email = stripinput(pg_escape_string($_POST['f_email']));
  $f_gpg = intval($_POST['f_gpg']);

  $sql_update = "UPDATE login ";
  $sql_update .= "SET email = '$f_email', gpg = $f_gpg ";
  if ($s_access_user < 9) {
    $sql_update .= "WHERE id = $user_id AND organisation = '$s_org' ";
  } else {
    $sql_update .= "WHERE id = $user_id ";
  }
  debug("SQL_UPDATE", $sql_update);
  $result_update = pg_query($sql_update);
  $m = 8;
}

if (isset($m)) {
  $m = stripinput($errors[$m]);
  $m = "<p>$m</p>\n";
  echo "<font color='red'>" .$m. "</font>";
} elseif (isset($_GET['m'])) {
  $m = intval($_GET['m']);
  $m = stripinput($errors[$m]);
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
  $result_user = pg_query($sql_user);
  $row = pg_fetch_assoc($result_user);

  debug("SQL_USER", $sql_user);

  $email = $row['email'];
  $gpg = $row['gpg'];

  echo "<b>Email settings</b><br /><br />\n";
  echo "<form name='emailsettings' action='mailadmin.php?userid=$user_id' method='post'>\n";
  echo "<table border='0' class='datatable'>\n";
    echo "<tr>\n";
      echo "<td class='datatd' width='100'>Email address</td>\n";
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
    echo "<tr>\n";
      echo "<td class='datatd' align='right' colspan='2'><input type='submit' class='button' value='Update' /></td>\n";
    echo "</tr>\n";
  echo "</table>\n";
  echo "</form>\n";

  echo "<br /><br />\n";

  echo "<b>Reports</b><br /><br />";
  echo "<a href='report_add.php?userid=$user_id'>Add report</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
  echo "<a href='report_mod.php?userid=$user_id&a=d'>Disable all reports</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
  echo "<a href='report_mod.php?userid=$user_id&a=e'>Enable all reports</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
  echo "<a href='report_mod.php?userid=$user_id&a=r'>Reset all report timestamps</a><br />";
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

    debug("SQL", $sql);

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
          echo "<a href='report_edit.php?userid=$user_id&report_content_id=$report_content_id'>" . $report_content["title"] . "</a>";
        echo "</td>\n";
        echo "<td class='datatd'>" . $last_sent . "</td>\n";
        echo "<td class='datatd'>" . $mail_template_ar[$report_content["template"]] . "</td>\n";
        echo "<td class='datatd'>" . $status . "</td>\n";
        echo "<td width='10' align='center'>[<a href='report_del.php?userid=$user_id&report_content_id=$report_content_id'>X</a>]</td>\n";
      echo "</tr>\n";
    }

  echo "</table>\n";
}

pg_close($pgconn);
footer();
?>

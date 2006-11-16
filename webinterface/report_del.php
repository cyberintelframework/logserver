<?php include("menu.php"); set_title("Mailreporting"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.03.01                  #
# 16-11-2006                       #
# Peter Arts                       #
# Modified by Kees Trippelvitz     #
####################################

#############################################
# Changelog:
# 1.03.01 Split up report.php into seperate files
#############################################

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_userid = intval($_SESSION['s_userid']);
$s_access = $_SESSION['s_access'];
$s_access_user = intval($s_access{2});
$err = 0;

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
        echo "<p><a href='mailadmin.php?userid=$user_id'>Back</a></p>\n";
        footer();
        exit;
      } else {
        echo "<p style='color:red;'>Data couldn't be removed (2).</p>\n";
      }
    }
    
    echo "<b>Delete " . $report_content["title"] . "</b><br /><br />\n";
    echo "Are you sure you want to delete this report?<br /><br />\n";
    echo "<form method='get'>\n";
      echo "<input type='hidden' name='action' value='del'>";
      echo "<input type='hidden' name='userid' value='$user_id'>";
      echo "<input type='hidden' name='submit' value='1'>";
      echo "<input type='hidden' name='report_content_id' value='$report_content_id'>";
      echo "<input type='submit' name='submitBtn' value='Yes' class='button'>\n";
      echo "<input type='button' name='b1' value='No' onclick=\"window.location.href='mailadmin.php?userid=$user_id';\" class='button'>\n";
    echo "</form>\n";
  }
}
?>
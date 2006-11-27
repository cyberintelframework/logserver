<?php include("menu.php"); set_title("User Administration"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.02.05                  #
# 08-08-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.02.05 Added intval() for $s_admin
# 1.02.04 Added intval() to $s_org
# 1.02.03 Closed the <?php tag
# 1.02.02 Initial release
#############################################

include 'include/config.inc.php';
include 'include/connect.inc.php';
include 'include/functions.inc.php';
include 'include/variables.inc.php';

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_userid = intval($_SESSION['s_userid']);
$s_access = $_SESSION['s_access'];
$s_access_user = $s_access{2};
$err = 0;

# Setting default values for a new user
$access_sensor = 0;
$access_search = 1;
$access_user = 0;

if ($s_access_user < 2) {
  $err = 1;
  $m = 99;
}

if ($err == 0) {
  echo "<form name='usermodify' action='useradd.php' method='post' onsubmit='return encrypt_pass();'>\n";
    echo "<table class='datatable'>\n";
      echo "<tr class='datatr'>\n";
        echo "<td class='datatd'>Username</td>\n";
        echo "<td class='datatd'><input type='text' name='f_username' size='30' /></td>\n";
      echo "</tr>\n";
      echo "<tr class='datatr'>\n";
        echo "<td class='datatd'>Password</td>\n";
        echo "<td class='datatd'><input type='password' size='30' /><input type='hidden' name='f_pass' /></td>\n";
      echo "</tr>\n";
      echo "<tr class='datatr'>\n";
        echo "<td class='datatd'>Confirm Password</td>\n";
        echo "<td class='datatd'><input type='password' size='30' /><input type='hidden' name='f_confirm' /></td>\n";
      echo "</tr>\n";
      echo "<tr class='datatr'>\n";
        echo "<td class='datatd'>Organisation</td>\n";
        echo "<td>\n";
          if ($s_access_user == 9) {
            echo "<select name='f_org'>\n";
              echo "<option value='none' selected></option>\n";

              $sql_org = "SELECT DISTINCT * FROM organisations";
              $result_org = pg_query($pgconn, $sql_org);
              while ($row_org = pg_fetch_assoc($result_org)) {
                $d_org = $row_org['organisation'];
		$d_org_id = $row_org['id'];
                echo "<option value='$d_org_id'>$d_org</option>\n";
              }
            echo "</select>\n";
          }
          else {
            $sql_org = "SELECT organisation FROM organisations WHERE id = $s_org";
            $result_org = pg_query($pgconn, $sql_org);
            $db_org_name = pg_result($result_org, 0);
            echo "<input type='hidden' name='f_org' value='$s_org' />\n";
            echo "$db_org_name";
          }
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr class='datatr'>\n";
        echo "<td class='datatd'>Email</td>\n";
        echo "<td class='datatd'><input type='text' size='30' name='f_email' /></td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td class='datatd'>Maillogging:</td>\n";
        echo "<td class='datatd'>\n";
          echo "" . printRadio("0 - None", "f_maillog", 0, $maillog) . "<br />\n";
          echo "" . printRadio("1 - All attacks", "f_maillog", 1, $maillog) . "<br />\n";
          echo "" . printRadio("2 - Only attacks from own ranges", "f_maillog", 2, $maillog) . "<br />\n";
        echo "</td>\n";
      echo "</tr>\n";
      #### Access: Sensor ####
      echo "<tr class='datatr'>\n";
        echo "<td class='datatd' valign='top'>Access: Sensor</td>\n";
        echo "<td class='datatd'>\n";
          echo "" . printRadio("0 - $access_ar_sensor[0]", "f_access_sensor", 0, $access_sensor) . "<br />\n";
          echo "" . printRadio("1 - $access_ar_sensor[1]", "f_access_sensor", 1, $access_sensor) . "<br />\n";
          if ($s_access_user == 9) {
            echo "" . printRadio("9 - $access_ar_sensor[9]", "f_access_sensor", 9, $access_sensor) . "<br />\n";
          }
        echo "</td>\n";
      echo "</tr>\n";
      #### Access: Search ####
      echo "<tr class='datatr'>\n";
        echo "<td class='datatd' valign='top'>Access: Search</td>\n";
        echo "<td class='datatd'>\n";
#          echo "" . printRadio("0 - $access_ar_search[0]", "f_access_search", 0, $access_search) . "<br />\n";
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
      echo "<tr>\n";
        echo "<td></td>\n";
        echo "<td align='right'>\n";
          echo "<input type='submit' name='submit' value='insert' class='button' />\n";
          echo "<input type='button' name='back' value='back' onclick=parent.location='useradmin2.php' class='button' />\n";
        echo "</td>\n";
      echo "</tr>\n";
    echo "</table>\n";
}
?>

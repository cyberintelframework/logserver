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
# 1.02.04 Added some more intval() functions
# 1.02.03 Added login check & fixed an access bug
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

if ($s_access_user == 0) {
  echo "<p>You dont have the access rights to modify accounts!</p>";
  exit;
} elseif ($s_access_user == 1) {
  $userid = $s_userid;
} else {
  $userid = intval($_GET['userid']);
}

if (isset($_GET['m'])) {
  $m = intval($_GET['m']);

  if ($m == 70) { $m = '<p>Successfuly updated the user details!</p>'; }
  elseif ($m == 71) { $m = '<p>The username field was empty!</p>'; }
  elseif ($m == 72) { $m = '<p>The passwords did not match!</p>'; }
  elseif ($m == 73) { $m = '<p>The organisation was not set!</p>'; }
  elseif ($m == 74) { $m = '<p>You dont have the access rights to modify accounts!</p>'; }
  elseif ($m == 79) { $m = '<p>Unknown error (usersave). Try again and hope for the best...!</p>'; }
  else { $m = '<p>Unknown error (useredit). Try again and hope for the best...!</p>'; }
  if (isset($m)) {
    echo "<font color='red'>" .$m. "</font>";
  }
}

if ($s_access_user == 9) {
  $sql_user = "SELECT * FROM login WHERE id = $userid";
}
else {
  $sql_user = "SELECT * FROM login WHERE id = $userid AND organisation = $s_org";
}
$result_user = pg_query($pgconn, $sql_user);
$numrows_user = pg_num_rows($result_user);
if ($numrows_user == 0) {
  $err = 1;
  echo "No user with this userid.<br />\n";
}
else {
  $access_user = pg_result($result_user, "access");
  $access_user = $access_user{2};
}

if ($s_access_user < $access_user) {
  $err = 1;
  echo "You don't have the access rights to modify this account!<br />\n";  
}

if ($err == 0) {
  $row = pg_fetch_assoc($result_user);

  $username = $row['username'];
  $org = intval($row['organisation']);
  $email = $row['email'];
  $maillog = $row['maillog'];
  $access = $row['access'];
  $access_sensor = $access{0};
  $access_search = $access{1};
  $access_user = $access{2};

  echo "<form name='usermodify' action='usersave.php' method='post' onsubmit='return encrypt_pass();'>\n";
    echo "<table class='datatable'>\n";
      echo "<tr class='datatr'>\n";
        echo "<td class='datatd'>Username</td>\n";
        if ($s_access_user > 0) {
          echo "<td class='datatd'><input type='text' name='f_username' size='30' value='$username' /></td>\n";
        }
        else {
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
          }
          else {
            $sql_org = "SELECT organisation FROM organisations WHERE id = $org";
            $result_org = pg_query($pgconn, $sql_org);
            $db_org_name = pg_result($result_org, 0);
            echo "<input type='hidden' name='f_org' value='$db_org_name' />\n";
            echo "$db_org_name";
          }
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr class='datatr'>\n";
        echo "<td class='datatd'>Email</td>\n";
        if ($s_access_user > 0) {
          echo "<td class='datatd'><input type='text' size='30' name='f_email' value='$email' /></td>\n";
        }
        else {
          echo "<td class='datatd'>$email</td>\n";
        }
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td class='datatd'>Maillogging:</td>\n";
        echo "<td class='datatd'>\n";
          if ($s_access_user > 0) {
            echo "" . printRadio("$maillog_ar[0]", "f_maillog", 0, $maillog) . "<br />\n";
            echo "" . printRadio("$maillog_ar[1]", "f_maillog", 1, $maillog) . "<br />\n";
            echo "" . printRadio("$maillog_ar[2]", "f_maillog", 2, $maillog) . "<br />\n";
          }
          else {
            echo "$maillog_ar[$maillog]\n";
          }
        echo "</td>\n";
      echo "</tr>\n";
      if ($s_access_user > 1) {
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
#            echo "" . printRadio("0 - $access_ar_search[0]", "f_access_search", 0, $access_search) . "<br />\n";
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
      }
      else {
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
}

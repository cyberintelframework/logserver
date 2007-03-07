<?php include("menu.php"); set_title("User Admin"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.03                  #
# 15-12-2006                       #
# Jan van Lith & Kees Trippelvitz  #
# Modified by Peter Arts           #
####################################

#############################################
# Changelog:
# 1.04.03 Changed data input handling
# 1.04.02 Changed debug info
# 1.04.01 Rereleased as 1.04.01
# 1.03.02 Removed and changed some stuff referring to the report table
# 1.03.01 Released as part of the 1.03 package
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

$allowed_get = array("int_userid", "int_m");
$check = extractvars($_GET, $allowed_get);
debug_input();

if ($s_access_user < 2) {
  $userid = $s_userid;
} else {
  $userid = $clean['userid'];
}

if ($s_access_user == 9) {
  $sql_user = "SELECT * FROM login WHERE id = $userid";
} else {
  $sql_user = "SELECT * FROM login WHERE id = $userid AND organisation = $s_org";
}
$debuginfo[] = $sql_user;
$result_user = pg_query($pgconn, $sql_user);
$numrows_user = pg_num_rows($result_user);

if ($numrows_user == 0) {
  $err = 1;
  $clean['m'] = 96;
  pg_close($pgconn);
  debug_sql();
  exit;
} else {
  $access_user = pg_result($result_user, "access");
  $access_user = intval($access_user{2});
}

if ($s_access_user < $access_user) {
  $err = 1;
  $clean['m'] = 91;
  pg_close($pgconn);
  exit;
}

if (isset($clean['m'])) {
  $m = $clean['m'];
  $m = geterror($m);
  echo $m;
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

  echo "<form name='usermodify' action='usersave.php' method='post' onsubmit='return encrypt_pass();'>\n";
    echo "<table class='datatable'>\n";
      echo "<tr class='datatr'>\n";
        echo "<td class='datatd'>Username</td>\n";
        if ($s_access_user > 0) {
          echo "<td class='datatd'><input type='text' name='strip_html_escape_username' size='30' value='$username' /></td>\n";
        } else {
          echo "<td class='datatd'>$username<input type='hidden' /></td>\n";
        }
      echo "</tr>\n";
      echo "<tr class='datatr'>\n";
        echo "<td class='datatd'>Password</td>\n";
        echo "<td class='datatd'><input type='password' size='30' value='' /><input type='hidden' name='md5_pass' /></td>\n";
      echo "</tr>\n";
      echo "<tr class='datatr'>\n";
        echo "<td class='datatd'>Confirm Password</td>\n";
        echo "<td class='datatd'><input type='password' size='30' value='' /><input type='hidden' name='md5_confirm' /></td>\n";
      echo "</tr>\n";
      echo "<tr class='datatr'>\n";
        echo "<td class='datatd'>Organisation</td>\n";
        echo "<td>\n";
          if ($s_access_user == 9) {
            echo "<select name='int_org'>\n";
              echo "<option value='0'></option>\n";

              $sql_org = "SELECT id, organisation FROM organisations";
              $debuginfo[] = $sql_org;
              $result_org = pg_query($pgconn, $sql_org);
              while ($row_org = pg_fetch_assoc($result_org)) {
                $d_org_id = $row_org['id'];
                $d_org_name = $row_org['organisation'];
                echo "" . printOption($d_org_id, $d_org_name, $org) . "\n";
              }
            echo "</select>\n";
          } else {
            $sql_org = "SELECT organisation FROM organisations WHERE id = $org";
            $debuginfo[] = $sql_org;
            $result_org = pg_query($pgconn, $sql_org);
            $db_org_name = pg_result($result_org, 0);
            echo "<input type='hidden' name='int_org' value='$org' />\n";
            echo "$db_org_name";
          }
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td class='datatd'>Email address</td>\n";
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

      if ($s_access_user > 1) {
        #### Access: Sensor ####
        echo "<tr class='datatr'>\n";
          echo "<td class='datatd' valign='top'>Access: Sensor</td>\n";
          echo "<td class='datatd'>\n";
            echo "" . printRadio("0 - $v_access_ar_sensor[0]", "int_asensor", 0, $access_sensor) . "<br />\n";
            echo "" . printRadio("1 - $v_access_ar_sensor[1]", "int_asensor", 1, $access_sensor) . "<br />\n";
            echo "" . printRadio("2 - $v_access_ar_sensor[2]", "int_asensor", 2, $access_sensor) . "<br />\n";
            if ($s_access_user == 9) {
              echo "" . printRadio("9 - $v_access_ar_sensor[9]", "int_asensor", 9, $access_sensor) . "<br />\n";
            }
          echo "</td>\n";
        echo "</tr>\n";
        #### Access: Search ####
        echo "<tr class='datatr'>\n";
          echo "<td class='datatd' valign='top'>Access: Search</td>\n";
          echo "<td class='datatd'>\n";
            echo "" . printRadio("1 - $v_access_ar_search[1]", "int_asearch", 1, $access_search) . "<br />\n";
            if ($s_access_user == 9) {
              echo "" . printRadio("9 - $v_access_ar_search[9]", "int_asearch", 9, $access_search) . "<br />\n";
            }
          echo "</td>\n";
        echo "</tr>\n";
        #### Access: User ####
        echo "<tr class='datatr'>\n";
          echo "<td class='datatd' valign='top'>Access: User Admin</td>\n";
          echo "<td class='datatd'>\n";
            echo "" . printRadio("0 - $v_access_ar_user[0]", "int_auser", 0, $access_user) . "<br />\n";
            echo "" . printRadio("1 - $v_access_ar_user[1]", "int_auser", 1, $access_user) . "<br />\n";
            if ($s_access_user > 1) {
              echo "" . printRadio("2 - $v_access_ar_user[2]", "int_auser", 2, $access_user) . "<br />\n";
            }
            if ($s_access_user == 9) {
              echo "" . printRadio("9 - $v_access_ar_user[9]", "int_auser", 9, $access_user) . "<br />\n";
            }
          echo "</td>\n";
        echo "</tr>\n";
      } else {
        echo "<tr class='datatr'>\n";
          echo "<td class='datatd'>Sensor access</td><td class='datatd'>$v_access_ar_sensor[$access_sensor]</td>\n";
        echo "</tr>\n";
        echo "<tr class='datatr'>\n";
          echo "<td class='datatd'>Search access</td><td class='datatd'>$v_access_ar_search[$access_search]</td>\n";
        echo "</tr>\n";
        echo "<tr class='datatr'>\n";
          echo "<td class='datatd'>User access</td><td class='datatd'>$v_access_ar_user[$access_user]</td>\n";
        echo "</tr>\n";
      }
      echo "<tr>\n";
        echo "<td><input type='hidden' name='int_userid' value='$userid' /></td>\n";
        echo "<td align='right'>\n";
          echo "<input type='submit' name='submit' value='update' class='button' />\n";
          echo "<input type='button' name='back' value='back' onclick=parent.location='useradmin.php' class='button' />\n";
        echo "</td>\n";
      echo "</tr>\n";
    echo "</table>\n";
    echo "</form>\n";
}

pg_close($pgconn);
debug_sql();
footer();
?>

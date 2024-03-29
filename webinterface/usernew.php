<?php $tab="5.2"; $pagetitle="Users - New"; include("menu.php"); contentHeader(0,0); ?>
<?php

####################################
# SURFids 3.00                     #
# Changeset 001                    #
# 18-11-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################
# Contributors:                    #
# Peter Arts                       #
####################################

#############################################
# Changelog:
# 001 Initial release
#############################################

$err = 0;

# Setting up default access values
$access_sensor = 0;
$access_search = 1;
$access_user = 0;

# Checking access
if ($s_access_user < 2) {
  $err = 1;
  $m = 101;
  pg_close($pgconn);
  header("location: useradmin.php?int_m=$m");
  exit;
}

if ($err == 0) {
  echo "<script type='text/javascript' src='${address}include/md5.js'></script>\n";
?>
<script type='text/javascript'>
  $(function() {
    $('#password').pstrength({
      colors: ["#f00","#ffa500", "#c7c93a","#0d0","#080"],
    });
  });
</script>
<?php
  echo "<div class='left'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockHeader'>" .$l['un_new']. "</div>\n";
        echo "<div class='blockContent'>\n";
          echo "<form name='usermodify' action='useradd.php' method='post' onsubmit='return encrypt_pass();'>\n";
            echo "<table class='datatable'>\n";
              echo "<tr>\n";
                echo "<td>" .$l['lo_username']. "</td>\n";
                echo "<td><input type='text' name='strip_html_escape_username' size='30' /></td>\n";
              echo "</tr>\n";
              echo "<tr>\n";
                echo "<td>" .$l['lo_pass']. "</td>\n";
                echo "<td><input type='password' id='password' size='30' /><input type='hidden' name='md5_pass' /></td>\n";
              echo "</tr>\n";
              echo "<tr>\n";
                echo "<td>" .$l['ma_confirmp']. "</td>\n";
                echo "<td><input type='password' size='30' /><input type='hidden' name='md5_confirm' /></td>\n";
              echo "</tr>\n";
              echo "<tr>\n";
                echo "<td>" .$l['g_domain']. "</td>\n";
                echo "<td>\n";
                  if ($s_access_user == 9) {
                    echo "<select name='int_org'>\n";
                      echo "<option value='0' selected></option>\n";
                      $sql_org = "SELECT DISTINCT * FROM organisations";
                      $debuginfo[] = $sql_org;
                      $result_org = pg_query($pgconn, $sql_org);
                      while ($row_org = pg_fetch_assoc($result_org)) {
                        $d_org = $row_org['organisation'];
                        $d_org_id = $row_org['id'];
                        echo "<option value='$d_org_id'>$d_org</option>\n";
                      }
                    echo "</select>\n";
                  } else {
                    $sql_org = "SELECT organisation FROM organisations WHERE id = $s_org";
                    $debuginfo[] = $sql_org;
                    $result_org = pg_query($pgconn, $sql_org);
                    $db_org_name = pg_result($result_org, 0);
                    echo "<input type='hidden' name='int_org' value='$s_org' />\n";
                    echo "$db_org_name";
                  }
                echo "</td>\n";
              echo "</tr>\n";

              # Email
              echo "<tr>\n";
                echo "<td>" .$l['ma_email']. "</td>\n";
                echo "<td>";
                  echo "<input type='text' name='strip_html_escape_email' value='" . $email . "' size='30'><br />";
                echo "</td>\n";
              echo "</tr>\n";
              echo "<tr>\n";
                echo "<td>" .$l['ma_signing']. "</td>\n";
                echo "<td>\n";
                  echo printRadio($l['ma_enable_gpg'], "int_gpg", 1, $gpg) . "<br />\n";
                  echo printRadio($l['ma_disable_gpg'], "int_gpg", 0, $gpg) . "<br />\n";
                echo "</td>\n";
              echo "</tr>\n";

              #### Access: Sensor ####
              echo "<tr>\n";
                echo "<td valign='top'>" .$l['ma_asensor']. "</td>\n";
                echo "<td>\n";
                  echo printRadio("0 - $v_access_ar_sensor[0]", "int_asensor", 0, $access_sensor) . "<br />\n";
                  echo printRadio("1 - $v_access_ar_sensor[1]", "int_asensor", 1, $access_sensor) . "<br />\n";
                  if ($c_ethernet_module == 1 && $c_enable_argos == 1) {
                    echo printRadio("2 - $v_access_ar_sensor[2]", "int_asensor", 2, $access_sensor) . "<br />\n";
                  } elseif ($c_ethernet_module == 1) {
                    echo printRadio("2 - " .$l['ma_arpac'], "int_asensor", 2, $access_sensor) . "<br />\n";
                  } elseif ($c_enable_argos == 1) {
                    echo printRadio("2 - " .$l['ma_argosac'], "int_asensor", 2, $access_sensor) . "<br />\n";
                  }
                  if ($s_access_user == 9) {
                    echo printRadio("9 - $v_access_ar_sensor[9]", "int_asensor", 9, $access_sensor) . "<br />\n";
                  }
                echo "</td>\n";
              echo "</tr>\n";
              #### Access: Search ####
              echo "<tr>\n";
                echo "<td valign='top'>" .$l['ma_asearch']. "</td>\n";
                echo "<td>\n";
                  echo printRadio("1 - $v_access_ar_search[1]", "int_asearch", 1, $access_search) . "<br />\n";
                  if ($s_access_user == 9) {
                    echo printRadio("9 - $v_access_ar_search[9]", "int_asearch", 9, $access_search) . "<br />\n";
                  }
                echo "</td>\n";
              echo "</tr>\n";
              #### Access: User ####
              echo "<tr>\n";
                echo "<td valign='top'>" .$l['ma_auseradmin']. "</td>\n";
                echo "<td>\n";
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
              echo "<tr>\n";
                echo "<td></td>\n";
                echo "<td>\n";
                  echo "<input type='submit' name='submit' value='" .$l['g_insert']. "' class='button' />\n";
                echo "</td>\n";
              echo "</tr>\n";
            echo "</table>\n";
            echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
          echo "</form>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</dataBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</left>
  echo "<script type='text/javascript' src='${address}include/jquery.pstrength-min.1.2.js'></script>\n";
}
debug_sql();
footer();
?>

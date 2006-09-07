<?php include("menu.php"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.02.01                  #
# 03-05-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

include 'include/config.inc.php';
include 'include/connect.inc.php';

#$ipaddr = $_SERVER['REMOTE_ADDR'];
#$rand = rand(2000000, 5000000);
#echo "RAND: $rand<br />\n";

echo "<center>\n";
echo "<h3>Login</h3>\n";
  echo "<form name='login' action='checklogin.php' method='post' onsubmit='javascript:f_pass.value=hex_md5(login.elements[1].value);'>\n";
    echo "<table border='1'>\n";
      echo "<tr>\n";
        echo "<td>Username:</td>\n";
        echo "<td><input type='text' name='f_user' class='loginput' /></td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td>Password:</td>\n";
        echo "<td>\n";
          echo "<input type='password' class='loginput' />\n";
          echo "<input type='hidden' name='f_pass' />\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td colspan='2' align='center'><input type='submit' value='Login' class='button' /></td>\n";
      echo "</tr>\n";
    echo "</table>\n";
  echo "</form>\n";
echo "</center>\n";
?>
<?php footer(); ?>

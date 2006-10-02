<?php include("menu.php"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.02.04                  #
# 09-08-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.02.04 Added error message + pg_escape_string for $serverhash
# 1.02.03 Login procedure changed to handshake between client and server
# 1.02.02 Added url querystring support
# 1.02.01 Initial release
#############################################

if (isset($_GET['url'])) {
  $url = "?url=" .$_GET['url'];
} else {
  $url = "";
}

echo "<center>\n";
echo "<h3>Login</h3>\n";

if (isset($_GET['e'])) {
  $e = intval($_GET['e']);
  if ($e == 1) { $e = "<p><font color='red'>Username or password was incorrect!</font></p>\n"; }
  else { $e = "<p><font color='red'>Unknown error!</font></p>\n"; }
  echo "$e";
}
if ($login_method == 2) {
  if (isset($_POST['f_user'])) {
    $f_user = pg_escape_string($_POST['f_user']);
    $sql_user = "SELECT count(id) as total, password FROM login WHERE username = '$f_user' GROUP BY password";
    $result_user = pg_query($pgconn, $sql_user);
    $row = pg_fetch_assoc($result_user);
    $total = $row['total'];
    $password = $row['password'];
 
    if ($total == 1) {
      $serverhash = pg_escape_string(genpass());
      $sql = "UPDATE login SET serverhash = '$serverhash' WHERE username = '$f_user'";
      $execute = pg_query($pgconn, $sql);
      $serverhash = md5($serverhash);
    } else {
      echo "<p><font color='red'>Username or password was incorrect!</font></p>\n";
      echo "<form name='login' action='login.php' method='post'>\n";
        echo "<table border='1'>\n";
          echo "<tr>\n";
            echo "<td>Username:</td>\n";
            echo "<td><input type='text' name='f_user' class='loginput' /></td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td colspan='2' align='center'><input type='submit' value='Login' class='button' /></td>\n";
          echo "</tr>\n";
        echo "</table>\n";
      echo "</form>\n";
      footer();
      exit;
    }
    echo "<form name='login' action='checklogin.php$url' method='post' onsubmit='javascript:generatep();'>\n";
      echo "<table border='1'>\n";
        echo "<tr>\n";
          echo "<td>Password:</td>\n";
          echo "<td>\n";
            echo "<input type='password' class='loginput' />\n";
            echo "<input type='hidden' value='$serverhash' size='50' />\n";
            echo "<input type='hidden' name='f_pass' size='50' />\n";
            echo "<input type='hidden' name='f_user' value='$f_user' />\n";
          echo "</td>\n";
        echo "</tr>\n";
  } else {
    echo "<form name='login' action='login.php' method='post'>\n";
      echo "<table border='1'>\n";
        echo "<tr>\n";
          echo "<td>Username:</td>\n";
          echo "<td><input type='text' name='f_user' class='loginput' /></td>\n";
        echo "</tr>\n";
  }
      echo "<tr>\n";
        echo "<td colspan='2' align='center'><input type='submit' value='Login' class='button' /></td>\n";
      echo "</tr>\n";
    echo "</table>\n";
  echo "</form>\n";
} else {
  echo "<form name='login' action='checklogin.php$url' method='post' onsubmit='javascript:f_pass.value=hex_md5(login.elements[1].value);'>\n";
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
}
echo "</center>\n";
?>
<?php footer(); ?>

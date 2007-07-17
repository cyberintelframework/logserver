<?php include("menu.php"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.02                  #
# 15-12-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.04.02 Changed data input handling
# 1.04.01 Rereleased as 1.04.01
# 1.03.01 Released as part of the 1.03 package
# 1.02.04 Added error message + pg_escape_string for $serverhash
# 1.02.03 Login procedure changed to handshake between client and server
# 1.02.02 Added url querystring support
# 1.02.01 Initial release
#############################################

echo "<center>\n";
echo "<h3>Login</h3>\n";

# Retrieving posted variables from $_GET
$allowed_get = array(
                "int_m",
		"strip_html_url"
);
$check = extractvars($_GET, $allowed_get);

# Checking $_POST'ed variables
$allowed_post = array(
                "strip_html_escape_user"
);
$check = extractvars($_POST, $allowed_post);
debug_input();

if (isset($clean['url'])) {
  $url = "?strip_html_url=" .$clean['url'];
} else {
  $url = "";
}

if (isset($clean['m'])) {
  $m = $clean['m'];
  $m = geterror($m);
  echo $m;
}

##################
# LOGIN METHOD 2
##################
if ($c_login_method == 2) {
  if (isset($clean['user'])) {
    $f_user = $clean['user'];
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
            echo "<td><input type='text' name='strip_html_user' class='loginput' /></td>\n";
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
            echo "<input type='hidden' name='md5_pass' size='50' />\n";
            echo "<input type='hidden' name='strip_html_escape_user' value='$f_user' />\n";
          echo "</td>\n";
        echo "</tr>\n";
  } else {
    echo "<form name='login' action='login.php' method='post'>\n";
      echo "<table border='1'>\n";
        echo "<tr>\n";
          echo "<td>Username:</td>\n";
          echo "<td><input type='text' name='strip_html_escape_user' class='loginput' /></td>\n";
        echo "</tr>\n";
  }
      echo "<tr>\n";
        echo "<td colspan='2' align='center'><input type='submit' value='Login' class='button' /></td>\n";
      echo "</tr>\n";
    echo "</table>\n";
  echo "</form>\n";
##################
# LOGIN METHOD 1
##################
} else {
  echo "<form name='login' action='checklogin.php$url' method='post' onsubmit='javascript:md5_pass.value=hex_md5(login.elements[1].value);'>\n";
    echo "<table border='1'>\n";
      echo "<tr>\n";
        echo "<td>Username:</td>\n";
        echo "<td><input type='text' name='strip_html_escape_user' class='loginput' /></td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td>Password:</td>\n";
        echo "<td>\n";
          echo "<input type='password' class='loginput' />\n";
          echo "<input type='hidden' name='md5_pass' />\n";
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

<?php include("menu.php"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.02.04                  #
# 08-08-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.02.04 Removed intval() from $s_access
# 1.02.03 intval() for $s_org, $s_admin and $s_access
# 1.02.02 Added some more input checks
# 1.02.01 Initial release
#############################################

include 'include/config.inc.php';
include 'include/connect.inc.php';
include 'include/functions.inc.php';
include 'include/variables.inc.php';

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_user = $s_access{2};

if ($s_access_user == 9) {
  echo "<h3>User Admin | <a href='orgadmin.php'>Organisations Admin</a> | <a href='serveradmin.php'>Server Admin</a></h3>\n";
}
else {
  echo "<h3>User Admin</h3>\n";
}

if (isset($_GET['m'])) {
  $m = intval($_GET['m']);

  # useradd.php
  if ($m == 90) { $m = '<p>Successfully added a new user!</p>'; }
  elseif ($m == 91) { $m = '<p>One of the password fields was empty!</p>'; }
  elseif ($m == 92) { $m = '<p>The passwords did not match!</p>'; }
  elseif ($m == 93) { $m = '<p>The username field was empty!</p>'; }
  elseif ($m == 94) { $m = '<p>The organisation was not set!</p>'; }
  elseif ($m == 95) { $m = '<p>The email address was not set!</p>'; }
  elseif ($m == 96) { $m = '<p>Admin rights are required to add a new user!</p>'; }
  elseif ($m == 97) { $m = '<p>This username is already in use!</p>'; }
  elseif ($m == 99) { $m = '<p>Unknown error (useradd). Try again and hope for the best...!</p>'; }

  # userdel.php
  elseif ($m == 80) { $m = '<p>Successfully deleted the user!</p>'; }
  elseif ($m == 81) { $m = '<p>You can only delete users from your own organisation!</p>'; }
  elseif ($m == 82) { $m = '<p>Admin rights are required to delete a user!</p>'; }
  elseif ($m == 83) { $m = '<p>Userid was not set!</p>'; }
  elseif ($m == 89) { $m = '<p>Unknown error (userdel). Try again and hope for the best...!</p>'; }

  # useredit.php
  elseif ($m == 70) { $m = '<p>Successfully modified the user!</p>'; }
  elseif ($m == 71) { $m = '<p>The username was not set!</p>'; }
  elseif ($m == 72) { $m = '<p>The passwords did not match!</p>'; }
  elseif ($m == 73) { $m = '<p>The organisation was not set!</p>'; }
  elseif ($m == 74) { $m = '<p>You have no rights to modify this user!</p>'; }
  elseif ($m == 79) { $m = '<p>Unknown error (usersave). Try again and hope for the best...!</p>'; }

  # Unknown error
  else { $m = '<p>Unknown error. Try again and hope for the best...!</p>'; }

  echo "<font color='red'>" .$m. "</font>";
}

#if ($s_admin == 1) {

if ($s_access_user > 1) {
  echo "<a href='usernew.php'>Insert User</a>\n";
#  echo "<br />\n";
  echo "<br />\n";
  echo "<table class='datatable'>\n";
    echo "<tr class='datatr'>\n";
      echo "<td width='150' class='dataheader'>User</td>\n";
      echo "<td width='200' class='dataheader'>Last login</td>\n";
      echo "<td width='100' class='dataheader'>Access</td>\n";
      echo "<td width='50' class='dataheader'>Modify</td>\n";
      echo "<td width='50' class='dataheader'>Delete</td>\n";
    echo "</tr>\n";

    if ($s_access_user == 2) {
      $sql_user = "SELECT DISTINCT * FROM login WHERE organisation = $s_org AND NOT access LIKE '%9%'";
    }
    elseif ($s_access_user == 9) {
      $sql_user = "SELECT DISTINCT * FROM login";
    }
    $result_user = pg_query($pgconn, $sql_user);

    while ($row = pg_fetch_assoc($result_user)) {
      $id = $row['id'];
      $username = $row['username'];
      $lastlogin = $row['lastlogin'];
      $access = $row['access'];
      if ( $lastlogin ) {
        $lastlogin = date("d-m-Y H:i:s", $lastlogin);
      }
      else {
        $lastlogin = "";
      }
      echo "<tr>\n";
        echo "<td>$username</td>\n";
        echo "<td>$lastlogin</td>\n";
        echo "<td>$access</td>\n";
        echo "<td><a href='useredit.php?userid=$id'>Modify</a></td>\n";
        echo "<td><a href='userdel.php?userid=$id' onclick=\"javascript: return confirm('Are you sure you want to delete this user?');\">Delete</a></td>\n";
      echo "</tr>\n";
    }
  echo "</table>\n";
}
pg_close($pgconn);
?>


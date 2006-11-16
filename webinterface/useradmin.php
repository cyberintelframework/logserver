<?php include("menu.php"); set_title("User Admin"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.01                  #
# 06-11-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

####################################
# Changelog:
# 1.04.01 Code layout
# 1.03.02 Added sorting option and links to mailadmin
# 1.03.01 Released as part of the 1.03 package
# 1.02.05 Added some more input checks and removed includes
# 1.02.04 Enhanced debugging
# 1.02.03 Added admin_header
# 1.02.02 Changed the access for the admin pages
# 1.02.01 Initial release
####################################

$s_org = intval($_SESSION['s_org']);

if (isset($_GET['m'])) {
  $m = intval($_GET['m']);
  $m = stripinput($errors[$m]);
  $m = "<p>$m</p>\n";
  echo "<font color='red'>" .$m. "</font>";
}

if (isset($_GET['sort'])) {
  $sort = $_GET['sort'];
  $pattern = '/^(ua|ud|la|ld)$/';
  if (!preg_match($pattern, $sort)) {
    $sort = "ua";
  }

  $type = $sort{0};
  $order = $sort{1};
  if ($order == "a") {
    $neworder = "d";
    $direction = "ASC";
  } else {
    $neworder = "a";
    $direction = "DESC";
  }
  if ($type == "u") {
    $sqlsort = "ORDER BY username $direction";
  } elseif ($type == "l") {
    $sqlsort = "ORDER BY lastlogin $direction";
  }
} else {
  $neworder = "d";
}

if ($s_access_user > 1) {
  echo "<a href='usernew.php'>Insert User</a>\n";
  echo "<br />\n";
  echo "<table class='datatable'>\n";
    echo "<tr class='datatr'>\n";
      echo "<td width='150' class='dataheader'><a href='useradmin.php?sort=u$neworder'>User</a></td>\n";
      echo "<td width='200' class='dataheader'><a href='useradmin.php?sort=l$neworder'>Last login</a></td>\n";
      echo "<td width='100' class='dataheader'>Access</td>\n";
      echo "<td width='50' class='dataheader'>Modify</td>\n";
      echo "<td width='50' class='dataheader'>Delete</td>\n";
      echo "<td width='100' class='dataheader'>Email settings</td>\n";
    echo "</tr>\n";

    if ($s_access_user == 2) {
      $sql_user = "SELECT DISTINCT * FROM login WHERE organisation = $s_org AND NOT access LIKE '%9%' $sqlsort";
    } elseif ($s_access_user == 9) {
      $sql_user = "SELECT DISTINCT * FROM login $sqlsort";
    }
    $result_user = pg_query($pgconn, $sql_user);

    # Debug info
    if ($debug == 1) {
      echo "<pre>";
      echo "SQL_USER: $sql_user";
      echo "</pre>\n";
    }

    while ($row = pg_fetch_assoc($result_user)) {
      $id = $row['id'];
      $username = $row['username'];
      $lastlogin = $row['lastlogin'];
      $access = $row['access'];
      if ( $lastlogin ) {
        $lastlogin = date("d-m-Y H:i:s", $lastlogin);
      } else {
        $lastlogin = "";
      }
      echo "<tr>\n";
        echo "<td>$username</td>\n";
        echo "<td>$lastlogin</td>\n";
        echo "<td>$access</td>\n";
        echo "<td><a href='useredit.php?userid=$id'>Modify</a></td>\n";
        echo "<td><a href='userdel.php?userid=$id' onclick=\"javascript: return confirm('Are you sure you want to delete this user?');\">Delete</a></td>\n";
        echo "<td><a href='mailadmin.php?userid=$id'>Mail settings</a></td>\n";
      echo "</tr>\n";
    }
  echo "</table>\n";
}
pg_close($pgconn);

footer();
?>

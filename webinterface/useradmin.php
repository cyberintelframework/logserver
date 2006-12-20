<?php include("menu.php"); set_title("User Admin"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.03                  #
# 15-12-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

####################################
# Changelog:
# 1.04.03 Changed data input handling
# 1.04.02 Changed debug stuff
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

$allowed_get = array("int_m", "sort");
$check = extractvars($_GET, $allowed_get);
debug_input();

if (isset($clean['m'])) {
  $m = $clean['m'];
  $m = geterror($m);
  echo $m;
}

if (isset($tainted['sort'])) {
  $sort = $tainted['sort'];
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
  echo "<a href='usernew.php'><img src='images/icons/user_add_48.gif' alt='Add User' title='Add User' /></a>\n";
  echo "<br />\n";
  echo "<table class='datatable'>\n";
    echo "<tr class='datatr'>\n";
      echo "<td width='150' class='dataheader'><a href='useradmin.php?sort=u$neworder' title='Sort on Username'>User</a></td>\n";
      echo "<td width='200' class='dataheader'><a href='useradmin.php?sort=l$neworder' title='Sort on Last login'>Last login</a></td>\n";
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
    $debuginfo[] = $sql_user;
    $result_user = pg_query($pgconn, $sql_user);

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
        echo "<td align=center><a href='useredit.php?int_userid=$id'><img src='images/icons/user_info_20.gif' alt='Edit User' title='Edit User' /></a></td>\n";
        echo "<td align=center><a href='userdel.php?int_userid=$id' onclick=\"javascript: return confirm('Are you sure you want to delete this user?');\"><img src='images/icons/user_delete_20.gif' alt='Delete User' title='Delete User' /></a></td>\n";
        echo "<td align=center><a href='mailadmin.php?int_userid=$id'><img src='images/icons/email_20.gif' alt='Edit Mailsetting' title='Edit Mailsettings' /></a></td>\n";
      echo "</tr>\n";
    }
  echo "</table>\n";
}
pg_close($pgconn);
debug_sql();
footer();
?>

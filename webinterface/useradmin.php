<?php include("menu.php"); set_title("User Admin"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.04                  #
# 01-02-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

####################################
# Changelog:
# 1.04.04 Minor bugfix + organisation name
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

# Retrieving some session variables
$s_org = intval($_SESSION['s_org']);

# Retrieving posted variables from $_GET
$allowed_get = array(
		"int_m",
		"sort"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

# Showing info/error messages if any
if (isset($clean['m'])) {
  $m = $clean['m'];
  $m = geterror($m);
  echo $m;
}

# Setting up sorting stuff
if (isset($tainted['sort'])) {
  $sort = $tainted['sort'];
  $pattern = '/^(ua|ud|la|ld|oa|od)$/';
  if (!preg_match($pattern, $sort)) {
    $sort = "ua";
  }

  $type = $sort{0};
  $direction = $sort{1};
  if ($direction == "a") {
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
  } elseif ($type == "o") {
    $sqlsort = "ORDER BY organisations.organisation $direction";
  }
} else {
  $neworder = "d";
}

if ($s_access_user > 1) {
  echo "<h4><a href='usernew.php'>[Add User]</a></h4>\n";
  echo "<table class='datatable'>\n";
    echo "<tr class='datatr'>\n";
      echo "<td width='150' class='dataheader'><a href='useradmin.php?sort=u$neworder' title='Sort on Username'>User</a></td>\n";
      echo "<td width='150' class='dataheader'><a href='useradmin.php?sort=o$neworder' title='Sort on organisation'>Organisation</a></td>\n";
      echo "<td width='200' class='dataheader'><a href='useradmin.php?sort=l$neworder' title='Sort on Last login'>Last login</a></td>\n";
      echo "<td width='100' class='dataheader'>Access</td>\n";
      echo "<td width='50' class='dataheader'>Modify</td>\n";
      echo "<td width='50' class='dataheader'>Delete</td>\n";
      echo "<td width='100' class='dataheader'>Email settings</td>\n";
    echo "</tr>\n";

    if ($s_access_user == 2) {
      $sql_user = "SELECT login.id, login.username, login.lastlogin, login.access, organisations.organisation ";
      $sql_user .= "FROM login, organisations WHERE login.organisation = $s_org AND login.organisation = organisations.id ";
      $sql_user .= "AND NOT login.access LIKE '%9%' $sqlsort";
    } elseif ($s_access_user == 9) {
      $sql_user = "SELECT login.id, username, lastlogin, login.access, organisations.organisation ";
      $sql_user .= "FROM login, organisations WHERE login.organisation = organisations.id ";
      $sql_user .= " $sqlsort";
    }
    $debuginfo[] = $sql_user;
    $result_user = pg_query($pgconn, $sql_user);

    while ($row = pg_fetch_assoc($result_user)) {
      $id = $row['id'];
      $username = $row['username'];
      $lastlogin = $row['lastlogin'];
      $access = $row['access'];
      $orgname = $row['organisation'];
      if ( $lastlogin ) {
        $lastlogin = date("d-m-Y H:i:s", $lastlogin);
      } else {
        $lastlogin = "";
      }
      echo "<tr>\n";
        echo "<td>$username</td>\n";
        echo "<td>$orgname</td>\n";
        echo "<td>$lastlogin</td>\n";
        echo "<td>$access</td>\n";
        echo "<td align=center>[<a href='useredit.php?int_userid=$id'><font size=1>Modify</font></a>]</td>\n";
        echo "<td align=center>[<a href='userdel.php?int_userid=$id' onclick=\"javascript: return confirm('Are you sure you want to delete this user?');\"><font size=1>Delete</font></a>]</td>\n";
        echo "<td align=center>[<a href='mailadmin.php?int_userid=$id'><font size=1>Edit Mailsetting</font></a>]</td>\n";
      echo "</tr>\n";
    }
  echo "</table>\n";
}
pg_close($pgconn);
debug_sql();
footer();
?>

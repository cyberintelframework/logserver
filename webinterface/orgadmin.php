<?php include("menu.php"); set_title("Organisation Admin"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.01                  #
# 06-11-2006                       #
# Kees Trippelvitz & Jan van Lith  #
####################################

####################################
# Changelog:
# 1.04.01 Rereleased as 1.04.01
# 1.03.01 Released as part of the 1.03 package
# 1.02.06 Added intval() for session variables
# 1.02.05 Added some more input checks and removed includes
# 1.02.04 Enhanced debugging
# 1.02.03 Added modifications for org_id table
# 1.02.02 Added identifier column to table
# 1.02.01 Initial release
####################################

####################################
# ToDo:
# Removing $s_access_user from this page as access is only allowed for $s_admin == 1
####################################

### Access level: s_admin == 1

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_user = intval($s_access{2});
$err = 0;

if ($s_admin != 1) {
  $err = 1;
  $m = 91;
}

if (isset($_GET['m'])) {
  $m = intval($_GET['m']);
  $m = stripinput($errors[$m]);
  $m = "<p>$m</p>";
  echo "<font color='red'>" .$m. "</font>";
}

if ($err == 0) {
  if ($s_access_user == 9) {
    $sql_orgs = "SELECT * FROM organisations";
  } else {
    $sql_orgs = "SELECT * FROM organisations WHERE id = $s_org";
  }
  $result_orgs = pg_query($pgconn, $sql_orgs);

  # Debug info
  if ($debug == 1) {
    echo "<pre>";
    echo "SQL_ORGS: $sql_orgs";
    echo "</pre>\n";
  }

  echo "<form name='orgadmin' action='orgsave.php?type=org' method='post'>\n";
  echo "<table class='datatable'>\n";
    echo "<tr class='datatr'>\n";
      echo "<td class='dataheader' width='50'>ID</td>\n";
      echo "<td class='dataheader' width='200'>Organisation</td>\n";
      echo "<td class='dataheader' width='100'># of identifiers</td>\n";
      echo "<td class='dataheader' width='100'>Actions</td>\n";
    echo "</tr>\n";

    while ($row = pg_fetch_assoc($result_orgs)) {
      $id = $row['id'];
      $org = $row['organisation'];
      $sql_count = "SELECT id FROM org_id WHERE orgid = $id";
      $result_count = pg_query($pgconn, $sql_count);
      $count = pg_num_rows($result_count);
    
      echo "<tr class='datatr'>\n";
        echo "<td class='datatd'>$id</td>\n";
        echo "<td class='datatd'>$org</td>\n";
        echo "<td class='datatd'>$count</td>\n";
        echo "<td class='datatd'><a href='orgedit.php?orgid=$id' alt='Edit the organisation' class='linkbutton'>Edit</a></td>\n";
      echo "</tr>\n";
    }

    echo "<tr>\n";
      echo "<td class='datatd'>#</td>\n";
      echo "<td class='datatd' colspan='2'><input type='text' name='orgname' size='40' /></td>\n";
      echo "<td class='datatd'><input type='submit' class='button' style='width: 100%;' value='Insert' /></td>\n";
    echo "</tr>\n";
  echo "</table>\n";
  echo "</form>\n";
}
?>
<?php footer(); ?>

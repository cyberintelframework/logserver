<?php include("menu.php"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.02.03                  #
# 08-08-2006                       #
# Kees Trippelvitz                 #
####################################

#############################################
# Changelog:
# 1.02.03 Added intval() to $s_org and $s_admin
# 1.02.02 Added input check on the $m variable
# 1.02.01 Initial release
#############################################

include 'include/config.inc.php';
include 'include/connect.inc.php';
include 'include/functions.inc.php';

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_user = $s_access{2};
$err = 0;

if ( $s_access_user < 2 ) {
  $err = 1;
  $m = 91;
}

if ($s_admin == 1) {
  echo "<h3><a href='useradmin.php'>User Admin</a> | Organisations Admin | <a href='serveradmin.php'>Server Admin</a></h3>\n";
}
else {
  echo "<h3>Organisations Admin</h3>\n";
}

if (isset($_GET['m'])) {
  $m = intval($_GET['m']);

  if ($m == 1) { $m = '<p>Successfully updated the organisation details!</p>'; }
  elseif ($m == 91) { $m = '<p>Admin rights are required to modify organisation info!</p>'; }
  elseif ($m == 92) { $m = '<p>The organisation was not set!</p>'; }
  elseif ($m == 93) { $m = '<p>The organisation already exists!</p>'; }

  echo "<font color='red'>" .$m. "</font>";
}

if ($err == 0) {
  $sql_orgs = "SELECT * FROM organisations";
  $result_orgs = pg_query($pgconn, $sql_orgs);

  echo "<table class='datatable'>\n";
    echo "<tr>\n";
      echo "<td class='dataheader'>ID</td>\n";
      echo "<td class='dataheader'>Organisation</td>\n";
      echo "<td class='dataheader'>Actions</td>\n";
    echo "</tr>\n";
  while ($row = pg_fetch_assoc($result_orgs)) {
    $id = $row['id'];
    $org = $row['organisation'];
    echo "<tr>\n";
      echo "<td class='datatd'>$id</td>\n";
      echo "<td class='datatd'>$org</td>\n";
      echo "<td class='datatd'><a href='orgedit.php?orgid=$id' alt='Edit the organisation' class='linkbutton'>Edit</a></td>\n";
    echo "</tr>\n";
  }
  echo "</table>\n";
}
?>
<?php footer(); ?>

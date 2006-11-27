<?php include("menu.php"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.02.04                  #
# 08-08-2006                       #
# Kees Trippelvitz                 #
####################################

#############################################
# Changelog:
# 1.02.04 Added intval() to $s_org and $s_admin
# 1.02.03 Added check to make sure there's at least 1 server present
# 1.02.02 Added some more input checks
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
  echo "<h3><a href='useradmin.php'>User Admin</a> | <a href='orgadmin.php'>Organisations Admin</a> | Server Admin</h3>\n";
}
else {
  echo "<h3>Server Admin</h3>\n";
}

if (isset($_GET['m'])) {
  $m = intval($_GET['m']);

  if ($m == 10) { $m = "<p>Successfully added a new server!</p>"; }
  elseif ($m == 11) {
    $count = intval($_GET['c']);
    if ($count == 0) {
      $m = "<p>Successfully deleted a server!</p>";
    }
    elseif ($count == 1) {
      $m = "<p>Successfully deleted a server!<br />Reset 1 sensor to default server!</p>";
    }
    else {
      $m = "<p>Successfully deleted a server!<br />Reset $count sensors to default server!</p>";
    }
  }
  elseif ($m == 91) { $m = "<p>Admin rights are required to add or delete a server!</p>"; }
  elseif ($m == 92) { $m = "<p>Server ID was not set in the querystring!</p>"; }
  elseif ($m == 93) { $m = "<p>Server field was empty!</p>"; }
  elseif ($m == 94) { $m = "<p>There has to be at least 1 server. Create one first before deleting this one!</p>"; }

  echo "<font color='red'>" .$m. "</font>";
}

if ($err == 0) {
  $sql_servers = "SELECT * FROM servers";
  $result_servers = pg_query($pgconn, $sql_servers);

  echo "This feature is still BETA. Use with care and only for testing purposes!<br />\n";
  echo "There should always at least be 1 server present in this list, otherwise things might break.<br />\n";
  echo "Before deleting a server, make sure the sensors that were configured for this server are changed to another server.<br /><br />\n";

  echo "<a href='servernew.php'>Insert</a><br />\n";
  echo "<table class='datatable'>\n";
    echo "<tr>\n";
      echo "<td class='dataheader'>ID</td>\n";
      echo "<td class='dataheader'>Organisation</td>\n";
      echo "<td class='dataheader'>Actions</td>\n";
    echo "</tr>\n";
  while ($row = pg_fetch_assoc($result_servers)) {
    $id = $row['id'];
    $server = $row['server'];
    echo "<tr>\n";
      echo "<td class='datatd'>$id</td>\n";
      echo "<td class='datatd'>$server</td>\n";
      echo "<td class='datatd'><a href='serverdel.php?serverid=$id' alt='Delete the server' class='linkbutton'>Delete</a></td>\n";
    echo "</tr>\n";
  }
  echo "</table>\n";
}
?>
<?php footer(); ?>

<?php include("menu.php"); set_title("Server Admin");  ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.01                  #
# 16-11-2006                       #
# Kees Trippelvitz                 #
####################################

#############################################
# Changelog:
# 1.04.01 Removed s_access variables
# 1.03.01 Released as part of the 1.03 package
# 1.02.03 Added some more input checks and removed includes
# 1.02.02 Enhanced debugging
# 1.02.01 Initial release
#############################################

$s_org = $_SESSION['s_org'];
$s_admin = $_SESSION['s_admin'];
$err = 0;

if ( $s_admin != 1 ) {
  $err = 1;
  $m = 91;
}

if (isset($_GET['m'])) {
  $m = intval($_GET['m']);

  if ($m == 100) { 
    $count = intval($_GET['c']);
    if ($count == 0) {
      $m = "<p>Successfully deleted a server!</p>";
    } elseif ($count == 1) {
      $m = "<p>Successfully deleted a server!<br />Reset 1 sensor to default server!</p>";
    } else {
      $m = "<p>Successfully deleted a server!<br />Reset $count sensors to default server!</p>";
    }
  } else {
    $m = intval($_GET['m']);
    $m = stripinput($errors[$m]);
    $m = "<p>$m</p>\n";
  }
  echo "<font color='red'>" .$m. "</font>";
}

if ($err == 0) {
  $sql_servers = "SELECT * FROM servers";
  $result_servers = pg_query($pgconn, $sql_servers);

  # Debug info
  if ($debug == 1) {
    echo "<pre>";
    echo "SQL_SERVERS: $sql_servers";
    echo "</pre>\n";
  }

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
      echo "<td class='datatd'><a href='serverdel.php?serverid=$id' alt='Delete the server' class='linkbutton' onclick=\"javascript: return confirm('Are you sure you want to delete this server?');\">Delete</a></td>\n";
    echo "</tr>\n";
  }
  echo "</table>\n";
}
?>
<?php footer(); ?>

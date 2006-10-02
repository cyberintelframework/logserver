<?php include("menu.php"); set_title("Server Admin");  ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.02.03                  #
# 28-07-2006                       #
# Kees Trippelvitz                 #
####################################

#############################################
# Changelog:
# 1.02.03 Added some more input checks and removed includes
# 1.02.02 Enhanced debugging
# 1.02.01 Initial release
#############################################

$s_org = $_SESSION['s_org'];
$s_admin = $_SESSION['s_admin'];
$s_access = $_SESSION['s_access'];
$s_access_sensor = $s_access{0};
$s_access_search = $s_access{1};
$s_access_user = $s_access{2};
$err = 0;

if ( $s_admin != 1 ) {
  $err = 1;
  $m = 91;
}

if (isset($_GET['m'])) {
  $m = intval($_GET['m']);

  if ($m == 10) { $m = "<p>Successfully added a new server!</p>"; }
  elseif ($m == 11) {
    $count = $_GET['c'];
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
      echo "<td class='datatd'><a href='serverdel.php?serverid=$id' alt='Delete the server' class='linkbutton'>Delete</a></td>\n";
    echo "</tr>\n";
  }
  echo "</table>\n";
}
?>
<?php footer(); ?>

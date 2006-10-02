<?php
####################################
# SURFnet IDS                      #
# Version 1.02.01                  #
# 03-05-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

session_start();
header("Cache-control: private");

include 'include/config.inc.php';
include 'include/connect.inc.php';

$s_org = intval($_SESSION['s_org']);
$s_admin = $_SESSION['s_admin'];

$absfile = $_SERVER['SCRIPT_NAME'];
$file = basename($absfile);
$dir = str_replace($file, "", $absfile);
$dir = ltrim($dir, "/");
$https = $_SERVER['HTTPS'];
if ($https == "") {
  $http = "http";
}
else {
  $http = "https";
}
$servername = $_SERVER['SERVER_NAME'];
$address = "$http://$servername:$web_port/$dir";

if ($file != "login.php") {
  if (isset($_SESSION['s_admin'])) {
    $s_access = $_SESSION['s_access'];
    $s_access_user = $s_access{2};
  }
  else {
    header("location: ${address}login.php");
  }

  if ($s_admin == 1) {
    $sql_active = "SELECT COUNT(*) as total FROM sensors WHERE status = 1";
    $sql_sensors = "SELECT COUNT(id) as total FROM sensors";
  }
  else {
    $sql_active = "SELECT COUNT(tapip) as total FROM sensors WHERE status = 1 AND organisation = " .$s_org;
    $sql_sensors = "SELECT COUNT(tapip) as total FROM sensors WHERE organisation = " .$s_org;
  }
  $result_active = pg_query($pgconn, $sql_active);
  $row = pg_fetch_assoc($result_active);
  $total_active = $row['total'];

  $result_sensors = pg_query($pgconn, $sql_sensors);
  $row = pg_fetch_assoc($result_sensors);
  $total_sensors = $row['total'];
}

if ($file != "login.php") {
  $s_org = $_SESSION['s_org'];
  $s_user = $_SESSION['s_user'];
  $s_userid = $_SESSION['s_userid'];
}

echo "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>\n";
echo "<html xmlns='http://www.w3.org/1999/xhtml' lang='en' xml:lang='en'>\n";
  echo "<head>\n";
    echo "<title>SURFnet IDS</title>\n";
    echo "<link rel='stylesheet' href='${address}include/idsstyle.css' />\n";
    echo "<script src='${address}include/md5.js' type='text/javascript'>\n";
    echo "</script>\n";
    echo "<script type='text/javascript' language='javascript'>\n";
?>
    function changeId(id) {
      if (document.getElementById(id).style.display == 'none') {
        // Make this element visible
        document.getElementById(id).style.display='';
        document.getElementById(id+'_img').src='<?=${address};?>images/minus.gif';
      } else {
        // Make this element invisible
        document.getElementById(id).style.display='none';
        document.getElementById(id+'_img').src='<?=${address};?>images/plus.gif';
      }
    }

<?php
    echo "</script>\n";
  echo "</head>\n";
  echo "<body>\n";
    echo "<div class='nav-menu'>\n";
      echo "<table border='0' width='100%'>\n";
        echo "<tr>\n";
          echo "<td width='20%'></td>\n";
          echo "<td width='60%' align='center'><img src='${address}images/logo.jpg' alt='Logo' /></td>\n";
          echo "<td width='20%' class='menustats'>";
            if ($file != "login.php") {
              echo "logged in as: <b>$s_user</b><br />";
              echo "total sensors: <b>$total_sensors</b><br />";
              echo "total active: <b>$total_active</b><br />";
              echo "version: <b>$c_version</b>\n";
            }
          echo "</td>\n";
        echo "</tr>\n";
      echo "</table>\n";
    echo "</div>\n";
    echo "<div class='nav-menu'>\n";
      echo "<ul>\n";
#        echo "<li><a href='${address}index.php'>Home</a></li>\n";
        echo "<li><a href='${address}sensorstatus.php'>Sensor Status</a></li>\n";
        echo "<li><a href='${address}rank.php'>Ranking</a></li>\n";
        echo "<li><a href='${address}search.php'>Search</a></li>\n";
        echo "<li><a href='${address}logindex.php'>Log Overview</a></li>\n";
        echo "<li><a href='${address}loghistory.php'>Log History</a></li>\n";
        echo "<li><a href='${address}logcheck.php'>Check</a></li>\n";
#        echo "<li><a href='${address}chartindex.php'>Charts</a></li>\n";
        echo "<li><a href='${address}traffic.php'>Traffic</a></li>\n";
        if ($file != "login.php") {
          if ($s_access_user > 1) {
            echo "<li><a href='${address}useradmin.php'>Admin</a></li>\n";
          }
          else {
            if ($s_access_user == 0) {
              echo "<li><a href='#'>User admin</a></li>\n";
            } else {
              echo "<li><a href='${address}useredit.php?userid=$s_userid'>User admin</a></li>\n";
            }
          }
        }
        else {
          echo "<li><a href='${address}useradmin.php'>User admin</a></li>\n";
        }
        echo "<li><a href='${address}logout.php'>Logout</a></li>\n";
      echo "</ul>\n";
    echo "</div>\n";
    echo "<div class='content'>\n";

function set_title($title) {
  echo "<h3>$title</h3>\n";
}

function footer()
{
  echo "</div>\n"; 
  echo "<div id='footer'><a href='http://validator.w3.org/'>Valid XHTML</a> - &copy; SURFnet</div>\n"; 
 echo "</body>\n";
  echo "</html>\n";
}
?>

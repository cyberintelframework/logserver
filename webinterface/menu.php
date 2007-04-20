<?php
####################################
# SURFnet IDS                      #
# Version 1.04.08                  #
# 12-03-2007                       #
# Jan van Lith & Kees Trippelvitz  #
# Modified by Peter Arts           #
####################################

#############################################
# Changelog:
# 1.04.08 Added home button
# 1.04.07 Uncommented server admin
# 1.04.06 Fixed another redirection bug with the $url variable
# 1.04.05 Fixed a redirection bug with the $url variable
# 1.04.04 Added server info page
# 1.04.03 Changed REQUEST_URI to SCRIPT_NAME for $url
# 1.04.02 Added JavaScript functions submitSearchTemplate(), submitSearchTemplateFromResults(), URLDecode()
# 1.04.01 Released as 1.04.01
# 1.03.02 Replaced REQUEST_URI with SCRIPT_NAME for $url
# 1.03.01 Released as part of the 1.03 package
# 1.02.04 Added pg_close to footer()
# 1.02.03 Moved the include directory to the surfnetids root dir
# 1.02.02 Changed the url redirection when $_GET['url'] is present + added intval() to session variables
# 1.02.01 Initial release
#############################################

session_start();
header("Cache-control: private");

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';
include '../include/variables.inc.php';

$absfile = $_SERVER['SCRIPT_NAME'];
$file = basename($absfile);
$address = getaddress();

if ($file != "login.php") {
  if (isset($_SESSION['s_admin'])) {
    $s_admin = intval($_SESSION['s_admin']);
    $s_user = $_SESSION['s_user'];
    $s_access = $_SESSION['s_access'];
    $s_access_user = intval($s_access{2});
    $chk_sid = checkSID();
    if ($chk_sid == 1) {
      $url = basename($_SERVER['SCRIPT_NAME']);
      header("location: ${address}login.php?strip_html_url=$url");
      exit;
    }
  } else {
    $url = basename($_SERVER['SCRIPT_NAME']);
    header("location: ${address}login.php?strip_html_url=$url");
    exit;
  }
  $s_org = intval($_SESSION['s_org']);
  if ($s_admin == 1) {
    $sql_active = "SELECT COUNT(*) as total FROM sensors WHERE status = 1";
    $sql_sensors = "SELECT COUNT(id) as total FROM sensors";
  } else {
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

echo "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>\n";
echo "<html xmlns='http://www.w3.org/1999/xhtml' lang='en' xml:lang='en'>\n";
  echo "<head>\n";
    echo "<title>SURFnet IDS</title>\n";
    echo "<link rel='stylesheet' href='${address}include/idsstyle.css' />\n";
    echo "<script type='text/javascript' src='${address}include/md5.js'></script>\n";
    echo "<script type='text/javascript' src='${address}include/overlib/overlib.js'><!-- overLIB (c) Erik Bosrup --></script>\n";
    echo "<script type='text/javascript' src='${address}include/surfnetids.js'></script>\n";
  echo "</head>\n";
  echo "<body>\n";
    echo "<div id='overDiv' style='position:absolute; visibility:hidden; z-index:1000;'></div>\n";
    echo "<div class='banner'>\n";
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
    echo "<div class='filler'></div>\n";
    $popup_home = "Dashboard overview!";
    $popup_sensor = "Shows the status information of all your sensors!";
    $popup_rank = "Shows the current ranking of your sensors and organisation!";
    $popup_search = "Search engine for searching through the logging data!";
    $popup_logindex = "Summarized overview of the different attacks detected!";
    $popup_history = "Summarized data per month!";
    $popup_check = "Check for attacks originating from your own networks!";
    $popup_traffic = "Statistics about the traffic going through the sensors!";
    $popup_googlemap = "Mapping of malicious attacks!";
    $popup_plotter = "Plot attacks!";
    echo "<div class='nav-menu'>\n";
      echo "<ul>\n";
        echo "<li><a href='${address}index.php' onmouseover='return overlib(\"$popup_home\");' onmouseout='return nd();'>Home</a></li>\n";
        echo "<li><a href='${address}sensorstatus.php' onmouseover='return overlib(\"$popup_sensor\");' onmouseout='return nd();'>Sensor Status</a></li>\n";
        echo "<li><a href='${address}rank.php' onmouseover='return overlib(\"$popup_rank\");' onmouseout='return nd();'>Ranking</a></li>\n";
        echo "<li><a href='${address}search.php' onmouseover='return overlib(\"$popup_search\");' onmouseout='return nd();'>Search</a></li>\n";
        echo "<li><a href='${address}logindex.php' onmouseover='return overlib(\"$popup_logindex\");' onmouseout='return nd();'>Log Overview</a></li>\n";
    #    echo "<li><a href='${address}loghistory.php' onmouseover='return overlib(\"$popup_history\");' onmouseout='return nd();'>Log History</a></li>\n";
        echo "<li><a href='${address}logcheck.php' onmouseover='return overlib(\"$popup_check\");' onmouseout='return nd();'>Check</a></li>\n";
        echo "<li><a href='${address}traffic.php' onmouseover='return overlib(\"$popup_traffic\");' onmouseout='return nd();'>Traffic</a></li>\n";
        echo "<li><a href='${address}plotter.php' onmouseover='return overlib(\"$popup_plotter\");' onmouseout='return nd();'>Plotter</a></li>\n";
        echo "<li><a href='${address}googlemap.php' onmouseover='return overlib(\"$popup_googlemap\");' onmouseout='return nd();'>Map</a></li>\n";
        echo "<li><a href='${address}logout.php'>Logout</a></li>\n";
      echo "</ul>\n";
    echo "</div>\n";
    echo "<div class='filler'></div>\n";
    if ($file != "login.php") {
      $s_a_sensor = $s_access{0};
      $s_a_search = $s_access{1};
      $s_a_user = $s_access{2};

      $popup_useradmin = "Account management page. Add, change and delete user accounts!";
      $popup_useredit = "Account management page. Modify your account information here!";
      $popup_mail = "Mail report management. Manage your personal mail reports here!";
      $popup_org = "Organisation management page. Modify organisation info and add organisation identifiers!";
      $popup_server = "Tunnel server management page. Add or delete tunnel server machines!";
      $popup_stats = "Shows statistics about the tunnel server machine!";
      $popup_scanner = "Virus scanner management page. Add, change or delete virus scanners!";

      echo "<div class='nav-sub-menu'>\n";
        echo "<ul>\n";
          if ($s_a_user > 1) {
            echo "<li><a href='${address}useradmin.php' onmouseover='return overlib(\"$popup_useradmin\");' onmouseout='return nd();'>User Admin</a></li>\n";
          } elseif ($s_a_user > 0) {
            echo "<li><a href='${address}useredit.php' onmouseover='return overlib(\"$popup_useredit\");' onmouseout='return nd();'>User Admin</a></li>\n";
          }
          if ($s_a_user > 0) {
            echo "<li><a href='${address}mailadmin.php' onmouseover='return overlib(\"$popup_mail\");' onmouseout='return nd();'>Mail Admin</a></li>\n";
          }
          if ($s_admin == 1) {
            echo "<li><a href='${address}orgadmin.php' onmouseover='return overlib(\"$popup_org\");' onmouseout='return nd();'>Organisation Admin</a></li>\n";
          }
#          if ($s_admin == 1) {
#            echo "<li><a href='${address}serveradmin.php' onmouseover='return overlib(\"$popup_server\");' onmouseout='return nd();'>Server Admin</a></li>\n";
#          }
          if ($s_admin == 1) {
            echo "<li><a href='${address}serverstats.php' onmouseover='return overlib(\"$popup_stats\");' onmouseout='return nd();'>Server Info</a></li>\n";
          }
        echo "</ul>\n";
      echo "</div>\n";
      echo "<div class='filler'></div>\n";
    }
    echo "<div class='content'>\n";

function set_title($title) {
  echo "<h3>$title</h3>\n";
}

function footer() {
  if (isset($pgconn)) {
    pg_close($pgconn);
  }

  echo "</div>\n"; 
  echo "<div id='footer'><a href='http://validator.w3.org/'>Valid XHTML</a> - &copy; SURFnet</div>\n"; 
  echo "</body>\n";
  echo "</html>\n";
}
?>

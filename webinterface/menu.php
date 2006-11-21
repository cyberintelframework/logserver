<?php
####################################
# SURFnet IDS                      #
# Version 1.03.03                  #
# 17-11-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.03.03 Added mailadmin link
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
$address = getaddress($web_port);

if ($file != "login.php") {
  if (isset($_SESSION['s_admin'])) {
    $s_admin = intval($_SESSION['s_admin']);
    $s_user = stripinput($_SESSION['s_user']);
    $s_access = $_SESSION['s_access'];
    $s_access_user = intval($s_access{2});
    $chk_sid = checkSID();
    if ($chk_sid == 1) {
      $url = basename($_SERVER['SCRIPT_NAME']);
      header("location: ${address}login.php?url=$url");
      exit;
    }
  } else {
    $url = basename($_SERVER['SCRIPT_NAME']);
    header("location: ${address}login.php?url=$url");
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
    function updateThreshold() {
                var target = document.getElementById('threshold_user');
                target.innerHTML = 'Threshold: <br />\n';
                target.innerHTML += 'if [ ';
                target.innerHTML += document.getElementById('target')[document.getElementById('target').selectedIndex].innerHTML;
                target.innerHTML += ' ' + document.getElementById('operator')[document.getElementById('operator').selectedIndex].innerHTML + ' ';
                if (document.getElementById('value').selectedIndex == 0) {
                        target.innerHTML += 'Average';
                } else {
                        target.innerHTML += (document.getElementById('value_user').value * 1); // Numeric value
                }
                target.innerHTML += ' ] for ';
                target.innerHTML += document.getElementById('timespan')[document.getElementById('timespan').selectedIndex].innerHTML;
                target.innerHTML += ' with a deviation of ';
                target.innerHTML += (document.getElementById('deviation').value * 1) + ' %'; // Numeric value
                target.innerHTML += '<br />\n';
                target.innerHTML += '&nbsp;then send e-mail report with priority ';
                target.innerHTML += document.getElementById('priority')[document.getElementById('priority').selectedIndex].innerHTML + '';
    }

    function showTab(sel) {
      var x;
      var mytabs = new Array();
      mytabs[0] = "arp_stats";
      mytabs[1] = "arp_cache";
      mytabs[2] = "arp_logstats";
      mytabs[3] = "arp_poison";
      for (x in mytabs)
      {
        if (sel == mytabs[x]) {
          var but = 'button_' + sel;
          document.getElementById(sel).style.display='';
          document.getElementById(but).className='tabsel';
        } else {
          var but = 'button_' + mytabs[x];
          document.getElementById(mytabs[x]).style.display='none';
          document.getElementById(but).className='tab';
        }
        document.getElementById(but).blur();
      }
    }

<?php
    echo "</script>\n";
  echo "</head>\n";
  echo "<body>\n";
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
    echo "<div class='nav-menu'>\n";
      echo "<ul>\n";
        echo "<li><a href='${address}sensorstatus.php'>Sensor Status</a></li>\n";
        echo "<li><a href='${address}rank.php'>Ranking</a></li>\n";
        echo "<li><a href='${address}search.php'>Search</a></li>\n";
        echo "<li><a href='${address}logindex.php'>Log Overview</a></li>\n";
        echo "<li><a href='${address}loghistory.php'>Log History</a></li>\n";
        echo "<li><a href='${address}logcheck.php'>Check</a></li>\n";
        echo "<li><a href='${address}traffic.php'>Traffic</a></li>\n";
        echo "<li><a href='${address}logout.php'>Logout</a></li>\n";
      echo "</ul>\n";
    echo "</div>\n";
    echo "<div class='filler'></div>\n";
    if ($file != "login.php") {
      $s_a_sensor = $s_access{0};
      $s_a_search = $s_access{1};
      $s_a_user = $s_access{2};
      echo "<div class='nav-sub-menu'>\n";
        echo "<ul align='center'>\n";
          if ($s_a_user > 1) {
            echo "<li><a href='${address}useradmin.php'>User Admin</a></li>\n";
          } elseif ($s_a_user > 0) {
            echo "<li><a href='${address}useredit.php'>User Admin</a></li>\n";
          }
          if ($s_a_user > 0) {
            echo "<li><a href='${address}mailadmin.php'>Mail Admin</a></li>\n";
          }
          if ($s_admin == 1) {
            echo "<li><a href='${address}orgadmin.php'>Organisation Admin</a></li>\n";
          }
          if ($s_admin == 1) {
            echo "<li><a href='${address}serveradmin.php'>Server Admin</a></li>\n";
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

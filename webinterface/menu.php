<?php
####################################
# SURFnet IDS                      #
# Version 1.04.04                  #
# 16-11-2006                       #
# Jan van Lith & Kees Trippelvitz  #
# Modified by Peter Arts           #
####################################

#############################################
# Changelog:
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
$address = getaddress($web_port);

if ($file != "login.php") {
  if (isset($_SESSION['s_admin'])) {
    $s_admin = intval($_SESSION['s_admin']);
    $s_user = $_SESSION['s_user'];
    $s_access = $_SESSION['s_access'];
    $s_access_user = intval($s_access{2});
    $chk_sid = checkSID();
    if ($chk_sid == 1) {
      $url = basename($_SERVER['SCRIPT_NAME']);
      header("location: ${address}login.php?url=$url&m=1010");
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
    echo "<script type='text/javascript' src='${address}include/overlib/overlib.js'><!-- overLIB (c) Erik Bosrup --></script>\n";
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
    
    function submitSearchTemplate() {
    	var form = document.getElementById('searchform');
    	//searchtemplate_title
    	var title = prompt('Please submit a title for this searchtemplate');
    	if ((title == '') || (title == null) || (title == 'undefined')) {
    		alert('Invalid title.');
    		return false;
    	}
    	if (confirm('Would you like to use \'' + title + '\' as the title for this searchtemplate?')) {
    		document.getElementById('searchtemplate_title').value = title;
    		form.action = 'searchtemplate.php';
    		form.submit();
    	} else return false;
    }
    
    function submitSearchTemplateFromResults(url) {
    	//searchtemplate_title
    	var title = prompt('Please submit a title for this searchtemplate');
    	if ((title == '') || (title == null) || (title == 'undefined')) {
    		alert('Invalid title.');
    		return false;
    	}
    	if (confirm('Would you like to use \'' + title + '\' as the title for this searchtemplate?')) {
    		url = '/searchtemplate.php?' + url + '&searchtemplate_title=' + title;
    		url = URLDecode(url);
			window.location.href = url;
    		return true;
    	} else return false;
    }
    
    function URLDecode(encoded)
	{
	   // Replace + with ' '
	   // Replace %xx with equivalent character
	   // Put [ERROR] in output if %xx is invalid.
	   var HEXCHARS = "0123456789ABCDEFabcdef"; 
	   var plaintext = "";
	   var i = 0;
	   while (i < encoded.length) {
	       var ch = encoded.charAt(i);
		   if (ch == "+") {
		       plaintext += " ";
			   i++;
		   } else if (ch == "%") {
				if (i < (encoded.length-2) 
						&& HEXCHARS.indexOf(encoded.charAt(i+1)) != -1 
						&& HEXCHARS.indexOf(encoded.charAt(i+2)) != -1 ) {
					plaintext += unescape( encoded.substr(i,3) );
					i += 3;
				} else {
					alert( 'Bad escape combination near ...' + encoded.substr(i) );
					plaintext += "%[ERROR]";
					i++;
				}
			} else {
			   plaintext += ch;
			   i++;
			}
		} // while
	   return plaintext;
	}

        function show_hide_column(col_no) {
          var stl;

          var tbl  = document.getElementById('malwaretable');
          var rows = tbl.getElementsByTagName('tr');

          for (var row=0; row<rows.length;row++) {
            var cels = rows[row].getElementsByTagName('td');
            var status = cels[col_no].style.display;
            var but = 'scanner_' + col_no;
            if (status == '') {
              cels[col_no].style.display='none';
              document.getElementById(but).className='tab';
            } else {
              cels[col_no].style.display='';
              document.getElementById(but).className='tabsel';
            }
          }
        }

<?php
    echo "</script>\n";
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
    $popup_sensor = "Shows the status information of all your sensors!";
    $popup_rank = "Shows the current ranking of your sensors and organisation!";
    $popup_search = "Search engine for searching through the logging data!";
    $popup_logindex = "Summarized overview of the different attacks detected!";
    $popup_history = "Summarized data per month!";
    $popup_check = "Check for attacks originating from your own networks!";
    $popup_traffic = "Statistics about the traffic going through the sensors!";
    echo "<div class='nav-menu'>\n";
      echo "<ul>\n";
        echo "<li><a href='${address}sensorstatus.php' onmouseover='return overlib(\"$popup_sensor\");' onmouseout='return nd();'>Sensor Status</a></li>\n";
        echo "<li><a href='${address}rank.php' onmouseover='return overlib(\"$popup_rank\");' onmouseout='return nd();'>Ranking</a></li>\n";
        echo "<li><a href='${address}search.php' onmouseover='return overlib(\"$popup_search\");' onmouseout='return nd();'>Search</a></li>\n";
        echo "<li><a href='${address}logindex.php' onmouseover='return overlib(\"$popup_logindex\");' onmouseout='return nd();'>Log Overview</a></li>\n";
        echo "<li><a href='${address}loghistory.php' onmouseover='return overlib(\"$popup_history\");' onmouseout='return nd();'>Log History</a></li>\n";
        echo "<li><a href='${address}logcheck.php' onmouseover='return overlib(\"$popup_check\");' onmouseout='return nd();'>Check</a></li>\n";
#        if ($enable_arpwatch == 1) {
#          echo "<li><a href='${address}arpindex.php'>ARP Monitor</a></li>\n";
#        }
        echo "<li><a href='${address}traffic.php' onmouseover='return overlib(\"$popup_traffic\");' onmouseout='return nd();'>Traffic</a></li>\n";
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
          if ($s_admin == 1) {
            echo "<li><a href='${address}serveradmin.php' onmouseover='return overlib(\"$popup_server\");' onmouseout='return nd();'>Server Admin</a></li>\n";
          }
#          if ($s_a_sensor > 1) {
#            echo "<li><a href='${address}arpadmin.php' onmouseover='return overlib(\"$popup_arp\");' onmouseout='return nd();'>ARP Admin</a></li>\n";
#          }
          if ($s_admin == 1) {
            echo "<li><a href='${address}serverstats.php' onmouseover='return overlib(\"$popup_stats\");' onmouseout='return nd();'>Server Info</a></li>\n";
          }
          if ($s_admin == 1) {
            echo "<li><a href='${address}virusadmin.php' onmouseover='return overlib(\"$popup_scanner\");' onmouseout='return nd();'>Scanner Admin</a></li>\n";
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

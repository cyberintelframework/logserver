<?php
####################################
# SURFnet IDS                      #
# Version 2.10.01                  #
# 25-10-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################
# Contributors:                    #
# Peter Arts                       #
####################################

#############################################
# Changelog:
# 2.10.01 Added language support
# 2.00.09 Fixed bug with int_page
# 2.00.08 Not showing admin tab when contents are empty
# 2.00.07 Added contact mail
# 2.00.06 Added popout to overlay
# 2.00.05 Fixed typo
# 2.00.04 jQuery compatible
# 2.00.03 Fixed typo
# 2.00.02 Added arp_enable check for detectedprotos.php
# 2.00.01 version 2.00
# 1.04.11 Added ARGOS admin button
# 1.04.10 Added ARP admin button
# 1.04.09 Added IP exclusions button
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

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';
include '../include/variables.inc.php';

# Starting the session
session_start();

# Including language file
include "../lang/${c_language}.php";

$absfile = $_SERVER['SCRIPT_NAME'];
$file = basename($absfile);
$qs = $_SERVER['QUERY_STRING'];
$address = getaddress();
if ($qs == "") {
  $url = $file . "?";
} else {
  $url = $file . "?" . $qs . "&";

}

if ($file != "login.php") {
  if (isset($_SESSION['s_admin'])) {
    # Retrieving some session variables
    $s_admin = intval($_SESSION['s_admin']);
    $s_user = $_SESSION['s_user'];
    $s_userid = $_SESSION['s_userid'];
    $s_hash = md5($_SESSION['s_hash']);
    $s_access = $_SESSION['s_access'];
    $s_access_user = intval($s_access{2});
    $s_access_sensor = intval($s_access{0});
    $s_access_search = intval($s_access{1});

    # Validate the session_id() against the SID in the database
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
  $s_org = $_SESSION['s_org'];
  
  if ($s_admin == 1) {
    $sql_active = "SELECT COUNT(id) as total FROM sensors WHERE status = 1";
    $sql_sensors = "SELECT COUNT(id) as total FROM sensors WHERE status IN (0, 1)";
  } else {
    $sql_active = "SELECT COUNT(tapip) as total FROM sensors WHERE status = 1 AND organisation = " .$s_org;
    $sql_sensors = "SELECT COUNT(tapip) as total FROM sensors WHERE organisation = " .$s_org. "AND status IN (0, 1)";
  }
  $result_active = pg_query($pgconn, $sql_active);
  $row = pg_fetch_assoc($result_active);
  $total_active = $row['total'];

  $result_sensors = pg_query($pgconn, $sql_sensors);
  $row = pg_fetch_assoc($result_sensors);
  $total_sensors = $row['total'];
}

if (!$tab) {
  $tab = "0.0";
}
$tabar = explode(".", $tab);
$main_tab = $tabar[0];
$sub_tab = $tabar[1];

echo "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>\n";
echo "<html xmlns='http://www.w3.org/1999/xhtml' lang='en' xml:lang='en'>\n";
  echo "<head>\n";
    echo "<title>SURFids - $pagetitle</title>\n";
    echo "<link rel='stylesheet' href='${address}include/layout.css' />\n";
    echo "<link rel='stylesheet' href='${address}include/design.css' />\n";
    echo "<style type='text/css'>@import url('${address}include/calendar.css');</style>\n";
    echo "<link rel='stylesheet' href='${address}include/idsstyle.css' />\n";
    echo "<script type='text/javascript' src='${address}include/overlib/overlib.js'><!-- overLIB (c) Erik Bosrup --></script>\n";
    echo "<script type='text/javascript' src='${address}include/jquery-1.2.1.js'></script>\n";
    echo "<script type='text/javascript' src='${address}include/jquery.pstrength-min.1.2.js'></script>\n";
    echo "<script type='text/javascript' src='${address}include/jquery.selectboxes.js'></script>\n";
    echo "<script type='text/javascript' src='${address}include/surfnetids.js'></script>\n";
    echo "<script type='text/javascript' src='${address}include/calendar.js'></script>\n";
    echo "<script type='text/javascript' src='${address}include/calendar-en.js'></script>\n";
    echo "<script type='text/javascript' src='${address}include/calendar-setup.js'></script>\n";
  echo "</head>\n";
  echo "<body>\n";
  echo "<div id='page'>\n";
    echo "<div id='pageHeader'>\n";
      echo "<ul id='globalNav'>\n";
        echo "<li><a href='mailto:$c_contact_mail'>" .$l['me_contact']. "</a></li>\n";
        echo "<li><a href='logout.php'>" .$l['me_logout']. "</a></li>\n";
        echo "<li><a href='http://ids.surfnet.nl/'>" .$l['me_about']. " SURFids</a></li>\n";
      echo "</ul>\n";
      echo "<h1><a href=''>SURFids</a></h1>\n";
      echo "<div class='infoBar'>\n";
	if ($total_active != '') echo "<div id='headerSensors'>" .$l['me_active']. " $total_active " .$l['me_of']. " $total_sensors </div>";
        else echo "<div id='headerSensors'>&nbsp;</div>"; 
	echo "<div id='headerTime'> <span id=\"tP\">&nbsp;</span></div>\n";
          echo "<div id='headerUser'>\n";
            if ($file != "login.php") {
              echo $l['me_logged']. ": $s_user";
            }
          echo "</div>\n";
        echo "</div>";

        if ($file != "login.php") {
          echo "<div id='tab-menu'>\n";
            echo "<ul>\n";
              #################
              # HOME TAB
              ################
              echo "<li class='home'><a href='index.php'>" .$l['me_home']. "</a>\n";
                echo "<div id='tab1' class='tab'><ul></ul></div>\n";
              echo "</li>\n";

              #################
              # REPORT TAB
              #################
              echo printTabItem(2, "", $l['me_report'], $main_tab);
              if ($main_tab == 2) {
                echo "<div id='tab2' class='tab'>\n";
              } else {
                echo "<div id='tab2' class='tab' style='display: none;'>\n";
              }            
                  echo"<ul>\n";
                    echo printMenuitem(2.1, "rank.php", $l['me_rank'], $tab);
                    echo printMenuitem(2.2, "logcheck.php", $l['me_cross'], $tab);
                    echo printMenuitem(2.3, "googlemap.php", $l['me_google'], $tab);
                    echo printMenuitem(2.4, "traffic.php", $l['me_traffic'], $tab);
                    if ($s_admin == 1) { 
                      echo printMenuitem(2.5, "serverstats.php", $l['me_serverinfo'], $tab);
                    }
                    if ($c_enable_arp == 1 && $s_access_sensor > 1) {
                      echo printMenuitem(2.7, "detectedproto.php", $l['me_detprot'], $tab);
                    }
                    echo printMenuitem(2.8, "plotter.php", $l['me_graphs'], $tab);
                    echo printMenuitem(2.6, "myreports.php", $l['me_reports'], $tab);
                  echo "</ul>\n";
                echo "</div>\n";
              echo "</li>\n";
	
              #################
              # ANALYZE TAB
              #################
              echo printTabItem(3, "", $l['me_analyze'], $main_tab);
              if ($main_tab == 3) {
                echo "<div id='tab3' class='tab'>\n";
              } else {
                echo "<div id='tab3' class='tab' style='display: none;'>\n";
              }            
                  echo "<ul>\n";
                    echo printMenuitem(3.1, "logindex.php", $l['g_attacks'], $tab);	
                    echo printMenuitem(3.2, "exploits.php", $l['g_exploits'], $tab);	
                    echo printMenuitem(3.3, "maloffered.php", $l['me_maloff'], $tab);	
                    echo printMenuitem(3.4, "maldownloaded.php", $l['me_maldown'], $tab);
                    if ($c_enable_arp == 1 && $s_access_sensor > 1) {
                      echo printMenuitem(3.6, "arp_cache.php", $l['ah_arp_cache'], $tab);
                    }
                    echo printMenuitem(3.5, "search.php", $l['me_search'], $tab);
                  echo "</ul>\n";
                echo "</div>\n";
              echo "</li>";

              #################
              # MANAGEMENT TAB
              #################
              echo printTabItem(4, "", $l['me_config'], $main_tab);
              if ($main_tab == 4) {
                echo "<div id='tab4' class='tab'>\n";
              } else {
                echo "<div id='tab4' class='tab' style='display: none;'>\n";
              }            
                  echo "<ul>\n";
                    echo printMenuitem(4.1, "sensorstatus.php", $l['me_sensorstatus'], $tab);
                    if ($c_enable_arp == 1 && $s_access_sensor > 1) {
                      echo printMenuitem(4.2, "arp_static.php", $l['me_arp'], $tab);
                    }
                    if ($s_access_user > 1) {
                      echo printMenuitem(4.3, "orgipadmin.php", $l['me_ipex'], $tab);
                    }
                    if ($c_enable_argos == 1 && $s_access_sensor > 1) {
                      echo printMenuitem(4.4, "argosconfig.php", $l['me_argos'], $tab);
                    }
                    if ($s_admin == 1) {
                      if ($c_enable_argos == 1) {
                        echo printMenuitem(4.5, "argosadmin.php", $l['me_argostemp'], $tab);
                      }
                      echo printMenuitem(4.6, "serverconfig.php", $l['me_configinfo'], $tab);
                    }
                  echo "</ul>\n";
                echo "</div>\n";
              echo "</li>";
              if ($s_access_user > 1 || $s_admin == 1) {
                #################
                # ADMINISTRATION TAB
                #################
                echo printTabItem(5, "", $l['me_admin'], $main_tab);
                if ($main_tab == 5) {
                  echo "<div id='tab5' class='tab'>\n";
                } else {
                  echo "<div id='tab5' class='tab' style='display: none;'>\n";
                }            
                    echo "<ul>\n";
                      if ($s_access_user != 0) {
                        echo printMenuitem(5.1, "myaccount.php", $l['me_myaccount'], $tab);
                      }
                      if ($s_access_user > 1) {
                        echo printMenuitem(5.2, "useradmin.php", $l['me_users'], $tab);
                      }
                      if ($s_admin == 1) {
	                echo printMenuitem(5.3, "orgadmin.php", $l['me_domains'], $tab);
                      }
                      if ($s_access_user > 1) {
                        echo printMenuitem(5.4, "groupadmin.php", $l['me_groups'], $tab);
                      }
                    echo "</ul>\n";
                  echo "</div>\n";
                echo "</li>";
              }
            echo "</ul>\n";
          echo "</div>\n";
        } else {
          echo "<div id='tab-menu'></div>\n";
        }
      echo "</div>";
      echo "<div id='pageBody'>";

      echo "<div id='popup'>";
        echo "<div id='popupheader'>";
          echo "<div id='popupheaderleft'></div>\n";
          echo "<div id='popupheaderright'><a onclick='popout();'><img src='images/close.gif' /></a></div>\n";
        echo "</div>\n";
        echo "<div id='popupcontent'>" .$l['me_loading']. "</div>\n";
      echo "</div>\n";
      echo "<div id='error'></div>\n";
      echo "<div id='overlay' onclick='popout();'></div>\n";


function insert_selector($m_show = 1) {
  global $s_org, $pgconn, $s_access_search, $s_access_sensor, $s_access_user, $v_selector_period, $c_startdayofweek, $_GET, $_POST;
  global $clean, $tainted, $to, $from, $to_date, $from_date, $q_org, $q_org_name, $c_debug_sql, $c_debug_input, $c_allow_global_debug, $l;
  # Retrieving URL
  $url = $_SERVER['PHP_SELF'];
  $qs = $_SERVER['QUERY_STRING'];

  # Retrieving posted variables from $_GET
  $allowed_get = array(
                "int_selperiod",
                "int_from",
                "int_to",
		"int_org",
                "dir",
		"int_debug"
  );
  $check = extractvars($_GET, $allowed_get);
  debug_input();

  if ($c_allow_global_debug == 1) {
    if (isset($clean['debug'])) {
      $c_debug_sql = 1;
      $c_debug_input = 1;
    }
  }

  # Setting default values
  $from = date("U", mktime(0, 0, 0, date("n"), date("j"), date("Y")));
  $to = date("U", mktime(0, 0, 0, date("n"), date("j")+1, date("Y")));
  $selperiod = -1;

  # Checking access
  if ($s_access_search == 9) {
    if (isset($clean['org'])) {
      $q_org = $clean['org'];
    } elseif (isset($_SESSION['q_org'])) {
      $q_org = $_SESSION['q_org'];
    } else {
      $q_org = 0;
    }  
  } else {
    $q_org = $s_org;
  }

  if (isset($clean['selperiod'])) {
    $selperiod = $clean['selperiod'];
  }
  if ($clean['from']) {
    $from = $clean['from'];
  } elseif ($_SESSION['s_from']) {
    $from = $_SESSION['s_from'];
  }
  if ($clean['to']) {
    $to = $clean['to'];
  } elseif ($_SESSION['s_to']) {
    $to = $_SESSION['s_to'];
  }

  # Period stuff
  $per = $to - $from;
  if ($tainted['dir']) {
    if ($tainted['dir'] == "prev") {
      if ($selperiod == 6 || $selperiod == 7) {
        $to = mktime(0,0,0,date("n", $to)-1,date("j", $to),date("Y", $to));
        $from = mktime(0,0,0,date("n", $from)-1,date("j", $from),date("Y", $from));
      } else {
        $to = $to - $per;
        $from = $from - $per;
      }
    } else {
      if ($selperiod == 6 || $selperiod == 7) {
        $to = mktime(0,0,0,date("n", $to)+1,date("j", $to),date("Y", $to));
        $from = mktime(0,0,0,date("n", $from)+1,date("j", $from),date("Y", $from));
      } else {
        $to = $to + $per;
        $from = $from + $per;
      }
    }
  }
  $_SESSION['s_to'] = $to;
  $_SESSION['s_from'] = $from;
  $_SESSION['q_org'] = $q_org;
  $from_date = date("d-m-Y H:i", $from);
  $to_date = date("d-m-Y H:i", $to);

  if ($m_show == 1) {
  echo "<div id='selector'>\n";
    echo "<form id='fselector' name='fselector' action='$url' method='get'>\n";
      echo "<div id='orgsel'>";
        if ($s_access_search == 9) {
          $sql_orgs = "SELECT id, organisation FROM organisations WHERE NOT organisation = 'ADMIN' ORDER BY organisation";
          $debuginfo[] = $sql_orgs;
          $result_orgs = pg_query($pgconn, $sql_orgs);
          echo "<select name='int_org' class='smallselect' onChange='javascript: this.form.submit();'>\n";
            echo printOption(0, "All", $q_org) . "\n";
            while ($row = pg_fetch_assoc($result_orgs)) {
              $org_id = $row['id'];
              $organisation = $row['organisation'];
              echo printOption($org_id, $organisation, $q_org) . "\n";
            }
          echo "</select>\n";
        } else {
          $sql_orgs = "SELECT organisation FROM organisations WHERE id = '$s_org'";
          $debuginfo[] = $sql_orgs;
          $result_orgs = pg_query($pgconn, $sql_orgs);
          $row = pg_fetch_assoc($result_orgs);
          $q_org_name = $row['organisation'];
          echo "<font class='btext'>$q_org_name</font>\n";
          echo "<input type='hidden' name='int_org' value='$s_org' />\n";
        }
      echo "</div>\n";
      echo "<div id='border'></div>\n";
      echo "<div id='arrowleft'>\n";
        echo "<a onclick='browse(\"prev\");'><img src='images/selector_arrow_left.gif' /></a>\n";
      echo "</div>\n";

      echo "<div id='timesel'>\n";
        echo "<div id='timesel_top'>\n";
          echo "<font class='btext'>" .$l['me_period']. ":</font>\n";
          echo "<select name='int_selperiod' id='selperiod' class='smallselect' onchange='setperiod(\"$c_startdayofweek\");'>\n";
            if ($selperiod == -1) {
              $per = $to - $from;
              if ($per <= 3600) {
                $hours = floor($per / 3600);
                $per = $sec % 3600;
                $minutes = floor($per / 60);
                $str = "$hours hour(s)";
                if ($minutes != 0) {
                  $str = " $minutes minute(s)";
                }
              } else {
                $days = floor($per / 86400);
                $str = "$days day(s)";
              }
              echo printOption(-1, $str, -1);
            } else {
              echo printOption(-1, $v_selector_period[$selperiod], -1);
            }
            foreach ($v_selector_period as $key => $val) {
              echo printOption($key, $val, -1);
            }
          echo "</select>\n";
          echo "<a><img src='images/calendar.gif' id='trigger' onclick='shcals();' /></a>\n";
        echo "</div>\n";
        echo "<div id='timesel_bottom'>\n";
          echo "<div id='showstart'>\n";
            echo "<div class='showtext btext'>" .$l['me_from']. ":</div>\n";
            echo "<div id='showdate_start'>$from_date</div>\n";
            echo "<div id='fromcal' style='display: none;'></div>\n";
          echo "</div>\n";
          echo "<div id='showend'>\n";
            echo "<div class='showtext btext'>" .$l['me_until']. ":</div>\n";
            echo "<div id='showdate_end'>$to_date</div>\n";
            echo "<div id='tocal' style='display: none;'></div>\n";
          echo "</div>\n";
        echo "</div>\n";
      echo "</div>\n";

      echo "<div id='arrowright'>\n";
        echo "<a onclick='browse(\"next\");'><img src='images/selector_arrow_right.gif' /></a>\n";
      echo "</div>\n";
      echo "<input type='hidden' name='int_to' id='int_to' value='$to' />\n";
      echo "<input type='hidden' name='int_from' id='int_from' value='$from' />\n";
      echo "<input type='hidden' name='dir' id='selector_dir' value='$dir' />\n";
      $check_ar = array("int_to", "int_from", "dir", "int_org", "int_selperiod");
      if ($qs != "") {
        $qs_ar = split("&", $qs);
        foreach ($qs_ar as $pair) {
          $pair_ar = split("=", $pair);
          $key = $pair_ar[0];
          $val = $pair_ar[1];
          if (!in_array($key, $check_ar)) {
            $val = str_replace("%3A", ":", "$val");
            $key = str_replace("%5B", "[", "$key");
            $key = str_replace("%5D", "]", "$key");
	    if ($key != "int_page") echo "<input type='hidden' name='$key' value='$val' />\n";
          }
        }
      }
    echo "</form>\n";
  echo "</div>\n"; #</selector>
  ?>
  <script>
    ts_from = $('#int_from').val() * 1000;
    ts_to = $('#int_to').val() * 1000;
    from = new Date(ts_from);
    to = new Date(ts_to);

    function startcal(cal) {
      var date_from = cal.date;

      $('#showdate_start').html(date_from.print("%d-%m-%Y %H:%M"));
      $('#int_from').val(date_from.print("%s"));
    }

    function closecal(cal) {
      if (cal.dateClicked) {
        var date_to = cal.date;

        var ts_from = $('#field_from').val();
        var ts_to = date_to.print("%s");
        if (ts_to < ts_from) {
          newto = ts_from;
          newfrom = ts_to;
          $('#int_to').val(newto);
          $('#int_from').val(newfrom);
        } else {
          $('#showdate_end').html(date_to.print("%d-%m-%Y %H:%M"));
          $('#int_to').val(date_to.print("%s"));
        }
        $('#selperiod').selectedIndex = 0;
        $('#fromcal').hide();
        $('#tocal').hide();
        $('#fselector').submit();
      }
    }

    function shcals() {
      $('#fromcal').toggle();
      $('#tocal').toggle();
    }

/***********************************
 * Selector functions
 ***********************************/

function browse(dir) {
  $("#selector_dir").val(dir);
  document.fselector.submit();
}

function setperiod(startofweek) {
  var period = $("#selperiod").val();
  var start = new Date();
  var end = new Date();

  if (period == 1) {
    start.setHours(start.getHours()-1);
  } else if (period == 2) {
    start.setHours(0);
    start.setMinutes(0);
    start.setSeconds(0);
    end.setHours(0);
    end.setMinutes(0);
    end.setSeconds(0);
    end.setDate(end.getDate()+1);
  } else if (period == 3) {
    start.setDate(start.getDate()-7);
  } else if (period == 4) {
    start.setHours(0);
    start.setMinutes(0);
    start.setSeconds(0);
    end.setHours(0);
    end.setMinutes(0);
    end.setSeconds(0);
    var d = start.getDay();
    var d = d - startofweek;
    var d = start.getDate() - d;
    start.setDate(d);
    end.setDate(d + 7);
  } else if (period == 5) {
    start.setHours(0);
    start.setMinutes(0);
    start.setSeconds(0);
    end.setHours(0);
    end.setMinutes(0);
    end.setSeconds(0);
    var d = start.getDay();
    var d = d - startofweek;
    var d = start.getDate() - d;
    var d = d - 7;
    start.setDate(d);
    end.setDate(d + 7);
  } else if (period == 6) {
    start.setHours(0);
    start.setMinutes(0);
    start.setSeconds(0);
    start.setDate(1);
    end.setHours(0);
    end.setMinutes(0);
    end.setSeconds(0);
    end.setDate(1);
    end.setMonth(end.getMonth()+1);
  } else if (period == 7) {
    start.setHours(0);
    start.setMinutes(0);
    start.setSeconds(0);
    start.setMonth(start.getMonth()-1);
    start.setDate(1);
    end.setHours(0);
    end.setMinutes(0);
    end.setSeconds(0);
    end.setDate(1);
  } else if (period == 8) {
    start.setHours(0);
    start.setMinutes(0);
    start.setSeconds(0);
    start.setDate(1);
    start.setMonth(0);
    end.setHours(0);
    end.setMinutes(0);
    end.setSeconds(0);
    end.setDate(1);
    end.setMonth(0);
    end.setFullYear(end.getFullYear()+1);
  }

  $("#int_from").val(start.print("%s"));
  $("#int_to").val(end.print("%s"));
  $("#showdate_start").html(start.print("%d-%m-%Y %H:%M"));
  $("#showdate_end").html(end.print("%d-%m-%Y %H:%M"));
  $('#fselector').submit();
}

    Calendar.setup(
      {
        showsTime    : true,
        cache        : false,
        singleClick  : false,
        flat         : "fromcal",
        flatCallback : startcal,
        caltitle     : "Start date",
        date         : from,
        firstDay     : <?=$c_startdayofweek?>
      }
    );
    Calendar.setup(
      {
        showsTime    : true,
        cache        : false,
        singleClick  : false,
        flat         : "tocal",
        flatCallback : closecal,
        caltitle     : "End date",
        date         : to,
        firstDay     : <?=$c_startdayofweek?>
      }
    );
  </script>
  <?php
  }
}

function set_title($m_show = 1) {
  global $pagetitle;
  if ($m_show == 1) {
    echo "<div id='pagetitle'>$pagetitle</div>\n";
  } else {
    echo "<div class='all'>\n";
      echo "<div id='pagetitle'>$pagetitle</div>\n";
    echo "</div>\n";
  }
}

function contentHeader($m_show = 1) {
  echo "<div class='contentHeader'>\n";
    set_title($m_show);
    insert_selector($m_show);
  echo "</div>\n";
}

?>

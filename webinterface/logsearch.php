<?php
####################################
# SURFids 3.00                     #
# Changeset 014                    #
# 13-11-2009                       #
# Jan van Lith & Kees Trippelvitz  #
####################################
# Contributors:                    #
# Peter Arts                       #
####################################

#########################################################################
# Changelog:
# 014 Fixed sorting of attacks with the same timestmap (#178)
# 013 Removed unnecessary sql clause for f_attack
# 012 Changed default sorting order to timestampd
# 011 Fixed handling of Amun exploits
# 010 Fixed issue #140
# 009 Added link to sensordetails
# 008 Fixed a navigational bug
# 007 Added MAC exclusion stuff
# 005 Fixed BUG #59
# 004 Fixed BUGS #42 + #43 
# 003 Fixed a typo
# 002 Fixed bug with Criterea 
# 001 Added language support
#########################################################################

####################
# REPORT TYPE
####################
$valid_reptype = array("multi", "single");
if (in_array($_GET['reptype'], $valid_reptype)) {
  $rapport = pg_escape_string($_GET['reptype']);
} else {
  $rapport = "multi";
}

if ($rapport == "idmef") {
  $qs = $_SERVER['QUERY_STRING'];
  header("Location: logsearch_idmef.php?$qs");
  exit;
}
if ($rapport == "pdf") {
  $qs = $_SERVER['QUERY_STRING'];
  header("Location: logsearch_pdf.php?$qs");
  exit;
}

####################
# PAGE HEADER
####################
$tab = "3.7";
$pagetitle = "Search";
include 'menu.php';
contentHeader();

####################
# GEOIP MODULES
####################
if ($c_geoip_enable == 1) {
  include '../include/' .$c_geoip_module;
  $gi = geoip_open("../include/" .$c_geoip_data, GEOIP_STANDARD);
}

####################
# Default censor value
####################
# Retrieving cookie variables from $_COOKIE[SURFids]
$allowed_cookie = array(
            "int_dcensor" 
);
$check = extractvars($_COOKIE[SURFids], $allowed_cookie);

if (isset($clean['dcensor'])) {
    $d_censor = $clean['dcensor'];
} else {
    $sql = "SELECT d_censor FROM login WHERE id = '$s_userid'";
    $res = pg_query($pgconn, $sql);
    $row = pg_fetch_assoc($res);
    $d_censor = $row['d_censor'];
}

####################
# DATA INPUT
####################
# Retrieving posted variables from $_GET
$allowed_get = array(
		"reptype",
		"int_org",
		"sensorid",
		"mac_sourcemac",
		"ipv4v6_source",
		"inet_ownsource",
		"int_sport",
		"mac_destmac",
		"inet_dest",
		"int_dport",
		"int_sev",
		"int_sevtype",
		"strip_html_escape_binname",
		"int_attack",
		"strip_html_escape_virustxt",
		"strip_html_escape_filename",
		"int_from",
		"int_to",
		"int_charttype",
		"sort",
		"int_page",
		"int_binid",
		"int_sourcechoice",
		"int_destchoice",
		"int_interval",
		"int_gid",
		"int_macfilter",
		"int_ipfilter",
		"int_allexploits",
        "strip_html_escape_sshversion",
        "strip_html_escape_sshuser",
        "strip_html_escape_sshpass",
        "int_sshhascommand",
        "int_sshlogin",
        "strip_html_escape_sshcommand",
        "int_attackid",
        "int_sshversionid"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

echo "<div id=\"search_wait\">" .$l['ls_process']. "<br /><br />" .$l['gm_patient']. ".</div>\n";
if ($c_searchtime == 1) {
  $timestart = microtime_float();
}

# Setting up sorting stuff
if (isset($tainted['sort'])) {
  $pattern = '/^(timestampa|timestampd|severitya|severityb|sourcea|sourced|sporta|sportd|desta|destd|dporta|dportd|sensorida|sensoridd)$/';
  $sort = $tainted['sort'];
  $sql_sort = sorter($sort, $pattern);
} else {
  $sql_sort = " timestamp DESC";
  $sort = "timestampd";
}

####################
# INCLUDE SQL BUILDER
####################

include_once 'search_sqlbuilder.php';

####################
# BUILDING COUNT QUERY
####################
if (!isset($_SESSION["search_num_rows"]) || (intval($_SESSION["search_num_rows"]) == 0) || ($clean['page'] == 0) || $c_search_cache == 0) {
  ### Prepare count SQL query
  $sql_select = "COUNT(DISTINCT attacks.id) AS total";
  $sql_count = "SELECT $sql_select ";
  $sql_count .= " FROM $sql_from ";
  $sql_count .= " $sql_where ";
  $debuginfo[] = $sql_count;

  # SQL-count query
  $query_count = pg_query($sql_count);
  # Don't use pg_num_rows, slow's down factor 2-4!
  $num_rows = pg_result($query_count, 0);
  ### Check for config option.
  if ($c_search_cache == 1) {
    $_SESSION["search_num_rows"] = $num_rows;
  }
}
if ($c_search_cache == 1) {
  $num_rows = intval($_SESSION["search_num_rows"]);
}

####################
# HITS PER PAGE (SQL LIMIT)
####################
if ($rapport == "single") {
  $per_page = $num_rows;
} else {
  $per_page = 20;
}

####################
# CALC FIRST/LAST PAGE
####################
$last_page = ceil($num_rows / $per_page);
if (isset($clean['page'])) {
  $page_nr = $clean['page'];
  if ($page_nr <= $last_page) {
    $offset = ($page_nr - 1) * $per_page;
  } else {
    $page_nr = 1;
    $offset = 0;
  }
} else {
  $page_nr = 1;
  $offset = 0;
}
$sql_limit = "LIMIT $per_page OFFSET $offset";
$first_result = number_format($offset, 0, ".", ",");
if ($first_result == 0) $first_result++;
$last_result = ($offset + $per_page);
if ($last_result > $num_rows) $last_result = $num_rows;
$last_result = number_format($last_result, 0, ".", ",");

############################
# SEARCH CRITERIA INCLUDE
############################

# Setting up search include stuff
$search_dest = 'display: none;';    # If empty, show search input fields, else show search info summary
$search_src = 'display: none;';     # If empty, show search input fields, else show search info summary
$search_char = 'display: none;';    # If empty, show search input fields, else show search info summary
$info_dest = '';                    # If empty, show search info summary, else show search input fields
$info_src = '';                     # If empty, show search info summary, else show search input fields
$info_char = '';                    # If empty, show search info summary, else show search input fields
$show_change = 1;                   # Show the change links, used for the results page
$single_submit = 0;                 # Only show a single submit button in the bottom right corner of the criteria div

echo "<div class='leftmed'>\n";
include_once 'sinclude.php';
echo "</div>\n";


if (count($graph) != 0) {
  foreach ($graph as $key=>$val) {
    if ($key == 0) {
      $g = "?$val";
    } else {
      $g .= "&$val";
    }
  }
  if ($g != "") {
    $g .= "&int_type=1";
    $time = $to - $from;
    if ($time < 72001) {
      $g .= "&int_interval=3600";
    } elseif ($time < 1728001) {
      $g .= "&int_interval=86400";
    } else {
      $g .= "&int_interval=604800";
    }
  }
}

echo "<div class='leftmed'>\n";
  echo "<div class='block'>\n";
    echo "<div class='actionBlock'>\n";
      echo "<div class='blockHeader'>" .$l['g_actions']. "</div>\n";
      echo "<div class='blockContent'>\n";
        $qs = $_SERVER['QUERY_STRING'];
        echo "<a href='logsearch_pdf.php?$qs'>" .$l['ls_saveas']. " PDF</a><br />";
        echo "<a href='logsearch_idmef.php?$qs'>" .$l['ls_saveas']. " IDMEF</a><br />";

        echo "<a href='plotter.php$g'>" .$l['ls_graphit']. "</a><br />";


        echo "<a onclick='\$(\"#templates\").toggle();'>" .$l['ls_saveas']. " " .$l['ls_stemp']. "</a>";
        echo "<div id='templates' style='display: none;'>\n";
          echo "<form name='temp' action='template_add.php?$qs' method='post'>\n";
            echo "<table class='searchtable'>\n"; 
              echo "<tr>\n";
                echo "<td>" .$l['ls_temp_title']. "</td>\n";
                echo "<td><input type='text' name='strip_html_escape_temptitle' /></td>\n";
              echo "</tr>\n";
              echo "<tr>\n";
                echo "<td>" .$l['ls_time_options']. "</td>\n";
                echo "<td>";
                  $per = $to - $from;
                  if ($per <= 3600) {
                    $hours = floor($per / 3600);
                    $per = $sec % 3600;
                    $minutes = floor($per / 60);
                    $perstr = "$hours hour(s)";
                    if ($minutes != 0) {
                      $perstr = " $minutes minute(s)";
                    }
                  } else {
                    $days = floor($per / 86400);
                    $perstr = "$days day(s)";
                  }
                  $from_date = date($c_date_format, $from);
                  $to_date = date($c_date_format, $to);
                  echo printRadio("$perstr", int_timespan, 1, 0) . "<br />";
                  echo printRadio("$from_date - $to_date", int_timespan, 2, 0) . "<br />";
                  echo printRadio($l['ls_dontsave'], int_timespan, 3, 3) . "<br />";
                echo "</td>\n";
              echo "</tr>\n";
              echo "<tr>\n";
                echo "<td></td>\n";
                echo "<td><input type='submit' value='" .$l['g_submit']. "' class='sbutton' /></td>\n";
              echo "</tr>\n";
            echo "</table>\n";
            echo "<input type='hidden' value='$s_hash' name='md5_hash' />\n";
          echo "</form>\n";
        echo "</div>\n";
      echo "</div>\n"; 
      echo "<div class='blockFooter'></div>\n";
    echo "</div>\n"; 
  echo "</div>\n"; 
echo "</div>\n"; 

####################
# CHECKING RESULTS
####################
if ($num_rows == 0) {
  # If there are no search results
  echo "<div class='centerbig'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockHeader'>\n";
          echo "<div class='blockHeaderLeft'>$navheader</div>\n";
          echo "<div class='blockHeaderRight'>";
            echo "<div class='searchnav'>$nav</div>\n";
          echo "</div>\n";
        echo "</div>\n";
        echo "<div class='blockContent'>\n";
          echo "<div><h3>" .$l['ls_noresults']. "</h3></div>\n";
        echo "</div>\n";
      echo "</div>\n";
    echo "</div>\n";
  echo "</div>\n";
  debug_sql();
  echo "<script type='text/javascript' src='${address}include/surfids.search${min}.js'></script>\n";
  ?>
  <script language="javascript" type="text/javascript">
  document.getElementById('search_wait').style.display='none';
  </script>
  <?
  footer();
  exit;
}

####################
# NAVIGATION
####################
$url = $_SERVER['PHP_SELF'];
$qs = urldecode($_SERVER['QUERY_STRING']);
$url = $url . "?" . $qs;
$url = preg_replace('/&$/', '', $url);
$url = str_replace("&int_page=" . $clean["page"], "", $url);
$url = str_replace("?int_page=" . $clean["page"], "", $url);
$url = trim($url, "?");

$nav = printNav($page_nr, $last_page, $url);

if (strstr($url, "?")) {
    $oper = "&";
} else {
    $oper = "?";
}

$url = str_replace("reptype=single&", "", $url);
$url = str_replace("&reptype=single", "", $url);
$url = str_replace("reptype=&", "", $url);
$url = str_replace("&reptype=", "", $url);

# Handling report types
if ($rapport == "single") $nav .= "&nbsp;<a href='${url}${oper}reptype='>" .$l['ls_multi']. "</a>&nbsp;\n";
if ($rapport == "multi") $nav .= "&nbsp;<a href='${url}${oper}reptype=single'>" .$l['g_all']. "</a>&nbsp;\n";

####################
# BUILDING SEARCH QUERY
####################
#flush();
prepare_sql();

# Bugfix #178
$sql_sort .= ", severity ASC";

$sql =  "SELECT $sql_select";
$sql .= " FROM $sql_from ";
$sql .= " $sql_where ";
$sql .= " ORDER BY $sql_sort ";
if ($rapport == "multi") {
    $sql .= " $sql_limit ";
}
$debuginfo[] = $sql;
$result = pg_query($sql);

####################
# BUILDING TABLE HEADER
####################
if ($last_page > 1) $page_lbl = $l['ls_pages'];
else $page_lbl = $l['ls_page'];
$navheader = "<font class='btext'>" .$l['ls_results']. "</font> (page $page_nr:  $first_result - $last_result of " . number_format($num_rows, 0, ".", ",") . ")\n";

echo "<div class='centerbig'>\n";
  echo "<div class='block'>\n";
    echo "<div class='dataBlock'>\n";
      echo "<div class='blockHeader'>\n";
        echo "<div class='blockHeaderLeft'>$navheader</div>\n";
        echo "<div class='blockHeaderRight'>";
          echo "<div class='searchnav'>$nav</div>\n";
        echo "</div>\n";
      echo "</div>\n";
      echo "<div class='blockContent'>\n";

echo "<table class='datatable'>\n";
  echo "<tr>\n";
    echo "<th width='14%'>" .printsort($l['ls_timestamp'], "timestamp"). "</th>\n";
    echo "<th width='21%'>" .printsort($l['ls_sev'], "severity"). "</th>\n";
    echo "<th width='19%'>" .printsort($l['ls_source'], "source"). "</th>\n";
    echo "<th width='5%'>" .printsort($l['ls_port'], "sport"). "</th>\n";
    echo "<th width='12%'>" .printsort($l['ls_dest'], "dest"). "</th>\n";
    echo "<th width='5%'>" .printsort($l['ls_port'], "dport"). "</th>\n";
    echo "<th width='8%'>" .printsort($l['g_sensor'], "sensorid"). "</th>\n";
    echo "<th width='16%'>" .$l['ls_additional']. "</th>\n";
  echo "</tr>\n";

while ($row = pg_fetch_assoc($result)) {
  flush();
  $id = pg_escape_string($row['id']);
  $ts = date("d-m-Y H:i:s", $row['timestamp']);
  $sev = $row['severity'];
  $sevtype = $row['atype'];
  if ($sev == 1) {
    $sevtext = "$v_severity_ar[$sev] - $v_severity_atype_ar[$sevtype]";
  } else {
    $sevtext = "$v_severity_ar[$sev]";
  }
  $smac = $row['src_mac'];
  $source = $row['source'];
  $sport = $row['sport'];
  $dmac = $row['dst_mac'];
  $dest = $row['dest'];
  $dport = $row['dport'];
  $sensorid = $row['sensorid'];
  $vlanid = $row['vlanid'];
  $sensorname = $row['keyname'];
  $labelsensor = $row['label'];
  if ($vlanid != 0) { $sensorname = "$sensorname-$vlanid";}

  if ($sevtype == 7) {
    $sql_details = "SELECT attacks.id FROM attacks ";
	$sql_details .= " LEFT JOIN ssh_command ON attacks.id = ssh_command.attackid ";
	$sql_details .= " LEFT JOIN ssh_logins ON attacks.id = ssh_logins.attackid ";
	$sql_details .= " LEFT JOIN ssh_version ON attacks.id = ssh_version.attackid ";
	$sql_details .= " WHERE attacks.id = $id";
  } else {
    $sql_details = "SELECT id, text, type FROM details WHERE attackid = " . $id;
  }
  $result_details = pg_query($pgconn, $sql_details);
  $numrows_details = pg_num_rows($result_details);
  $debuginfo[] = $sql_details;

  if ($c_enable_pof == 1) {
    $sql_finger = "SELECT name FROM system WHERE ip_addr = '" .$source. "' ORDER BY last_tstamp DESC";
    $result_finger = pg_query($pgconn, $sql_finger);
    $numrows_finger = pg_num_rows($result_finger);
    $debuginfo[] = $sql_finger;

    $fingerprint = pg_result($result_finger, 0);
    $finger_ar = explode(" ", $fingerprint);
    $os = $finger_ar[0];
  } else {
    $numrows_finger = 0;
  }

  echo "<tr>\n";
    echo "<td>$ts</td>\n";
    if ($numrows_details != 0) {
    	echo "<td><a onclick=\"popit('" ."logdetail.php?int_id=$id&int_atype=$sevtype". "', 409, 700);\">$sevtext</a></td>\n";
    } else {
      echo "<td>$sevtext</td>\n";
    }
    echo "<td>";
    if ($numrows_finger != 0) {
      echo printosimg($os, $fingerprint);
    } else {
      echo printosimg("Blank", $l['ls_noinfo']);
    }

    if ($c_geoip_enable == 1) {
      $record = geoip_record_by_addr($gi, $source);
      $countrycode = strtolower($record->country_code);
      $cimg = "$c_surfidsdir/webinterface/images/worldflags/flag_" .$countrycode. ".gif";
      if (file_exists($cimg)) {
        $country = $record->country_name;
        echo printflagimg($country, $countrycode);
      } else {
        echo printflagimg("none", "");
      }
    }
    if ($sport == 0) {
      $sport = "";
    }
   
    if ($sevtype == 10 || $sevtype == 11) {
      echo "$smac</td>\n";
      echo "<td>$sport</td>\n";
    } elseif ($sevtype == 12) {
      echo "$source</td><td></td>\n";
    } else {
      if (matchCIDR($source, $ranges_ar)) {
        if ($d_censor == 2) {
          $t_source = censorip($source, $d_censor);
        } else {
          $t_source = $source;
        }
        echo "<a onclick=\"popUp('" ."whois.php?ip_ip=$source". "', 800, 500);\" class='warning' />$t_source</a>&nbsp;&nbsp;";
        echo "<img src='images/ownranges.jpg' ".printover("IP from your own ranges!") ."></td>\n";
      } else {
        if ($d_censor == 2) {
          $t_source = censorip($source, $d_censor);
        } else {
          $t_source = $source;
        }
        echo "<a onclick=\"popUp('" ."whois.php?ip_ip=$source". "', 800, 500);\" />$t_source</a>&nbsp;&nbsp;</td>";
      }
      echo "<td>$sport</td>\n";
    }
    $dest = censorip($dest, $d_censor);
    if ($dport == 0) {
      echo "<td>$dest</td><td></td>\n";
    } else {
      echo "<td>$dest</td><td>$dport</td>\n";
    }
    if ($labelsensor != "") {
      $name = $labelsensor;
    } else {
      $name = $sensorname;
    }
    echo "<td><a href='sensordetails.php?int_sid=$sensorid'>$name</a></td>\n";

    if ($numrows_details != 0) {
      if ($sev == 1 && ($sevtype == 0 || $sevtype == 5)) {
        if ($sevtype == 5) {
          $dia_ar = array('attackid' => $id, 'type' => 84);
          $dia_result_ar = pg_select($pgconn, 'details', $dia_ar);
          $text = $dia_result_ar[0]['text'];
        } else {
          $dia_ar = array('attackid' => $id, 'type' => 1);
          $dia_result_ar = pg_select($pgconn, 'details', $dia_ar);
          $text = $dia_result_ar[0]['text'];
        }
        if (strpos($text, "Vulnerability") == False) {
          # Handling Nepenthes detail records
          $attack = str_replace("Dialogue", "", $text);
        } else {
          # Handling Amun detail records
          $text = str_replace("Vulnerability", "", $text);
          $attack = trim($text);
        }
        echo "<td>";
        if ($attack != "") {
          echo "$attack<br />";
        }
        if ($smac != "") {
          echo "$smac";
        }
        echo "</td>\n";

      } elseif ( ( $sev == 1 || $sev == 0 ) && $sevtype == 2) {
        $dia_ar = array('attackid' => $id, 'type' => 40);
        $dia_result_ar = pg_select($pgconn, 'details', $dia_ar);
        $module = $dia_result_ar[0]['text'];

        echo "<td>$module</td>";
      } elseif ($sev == 1 && $sevtype == 1) {
        $dia_ar = array('attackid' => $id, 'type' => 20);
        $dia_result_ar = pg_select($pgconn, 'details', $dia_ar);
        $module = $dia_result_ar[0]['text'];

        echo "<td>$module";
		if ($smac != "") {
          echo "<br />$smac";
        }
        echo "</td>\n";
      } elseif ($sev == 1 && $sevtype == 11) {
        echo "<td>Source IP: $source</td>\n";
      } elseif ($sev == 16) {
        $row_details = pg_fetch_assoc($result_details);
        $text = $row_details['text'];
        $file = basename($text);
        if ($smac != "") {
          echo "<td>$file<br />$smac</td>\n";
        } else { 
          echo "<td>$file</td>\n";
        }
      } elseif ($sev == 32) {
        $dia_ar = array('attackid' => $id, 'type' => 8);
        $dia_result_ar = pg_select($pgconn, 'details', $dia_ar);
        $bin = $dia_result_ar[0]['text'];

        $sql_bin = "SELECT uniq_binaries.id, uniq_binaries.name FROM binaries, uniq_binaries WHERE uniq_binaries.name = '$bin' ";
        $sql_bin .= " AND binaries.bin = uniq_binaries.id ";
        $sql_bin .= " ORDER BY timestamp LIMIT 1";
        $result_bin = pg_query($pgconn, $sql_bin);
        $numrows_bin = pg_num_rows($result_bin);
        $row_bin = pg_fetch_assoc($result_bin);
    	$debuginfo[] = $sql_bin;

        echo "<td>";
        if ($numrows_bin != 0) {
          $binid = $row_bin['id'];
          echo "<a href='binaryhist.php?int_binid=$binid'>Info</a>";
        } else {
          echo "Suspicious";
        }
        if ($smac != "") {
          echo "<br />$smac";
        }
        echo "</td>\n";
      } else {
        if ($smac != "") {
          echo "<td>" .$l['ls_sourcemac']. ": $smac</td>\n";
        } else { 
          echo "<td></td>\n";
        }
      }
    } else {
      if ($smac == "") {
        echo "<td></td>\n";
      } elseif ($sevtype == 11) {
        echo "<td>" .$l['ls_sourceip']. ": $source</td>\n";
      } elseif ($sevtype == 10) {
        echo "<td></td>\n";
      } else {
        echo "<td>$smac</td>\n";
      }
    }
  echo "</tr>\n";
}
echo "</table>\n";

echo "</div>\n"; #</blockContent>
echo "<div class='blockFooter'></div>";
echo "</div>\n"; #</datablock>
echo "</div>\n"; #</block>
echo "</div>\n"; #</centerbig>

# Search time stuff
if ($c_searchtime == 1) {
  $timeend = microtime_float();
  $gen = $timeend - $timestart;
  $mili_gen = number_format(($gen * 1000), 0);
  echo "<div class='all'>\n";
  echo "<div class='textcenter'>\n";
    echo $l['ls_rendered']. " $mili_gen ms.";
  echo "</div>\n";
  echo "</div>\n";
}

debug_sql();
pg_close($pgconn);
echo "<script type='text/javascript' src='${address}include/surfids.search${min}.js'></script>\n";
?>
<script language="javascript" type="text/javascript">
document.getElementById('search_wait').style.display='none';
</script>

<?php footer(); ?>

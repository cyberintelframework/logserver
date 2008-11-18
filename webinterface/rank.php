<?php $tab="2.1"; $pagetitle="Ranking"; include("menu.php"); contentHeader(); ?>
<?php

####################################
# SURFids 2.04                     #
# Changeset 005                    #
# 8-11-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################
# Contributors:                    #
# Bjoern Weiland                   #
# David de Coster                  #
# Peter Arts                       #
####################################

####################################
# Changelog:
# 005 Fixed bug #46 & #47 
# 004 Fixed bug #45 
# 003 Fixed a bug where it would not show own organisation in top orgs list
# 002 Fixed a bug with tops source addresses.
# 001 version 2.00
####################################

### GEOIP STUFF
if ($c_geoip_enable == 1) {
  include '../include/' .$c_geoip_module;
  $gi = geoip_open("../include/" .$c_geoip_data, GEOIP_STANDARD);
}

# Retrieving posted variables from $_GET
$allowed_get = array(
                "int_org",
		"b",
		"i"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

$tsquery = " timestamp >= $from AND timestamp <= $to";

$sql_active = "SELECT count(id) as total FROM sensors WHERE tap != ''";
$result_active = pg_query($pgconn, $sql_active);
$row = pg_fetch_assoc($result_active);
$total_active = $row['total'];

$sql_sensors = "SELECT count(id) as total FROM sensors";
$result_sensors = pg_query($pgconn, $sql_sensors);
$row = pg_fetch_assoc($result_sensors);
$total_sensors = $row['total'];

$sql_getorg = "SELECT organisations.organisation FROM organisations, sensors WHERE sensors.organisation = organisations.id AND organisations.id = $q_org";
$result_getorg = pg_query($pgconn, $sql_getorg);
$row = pg_fetch_assoc($result_getorg);
$orgname = $row['organisation'];

$debuginfo[] = $sql_active;
$debuginfo[] = $sql_sensors;
$debuginfo[] = $sql_getorg;

#####################
# <DATA SQL>
# 1.01 Total Malicious attacks (all)
# 1.02 Total downloads (all)
# 1.03 Total Malicious attacks (org)
# 1.04 Total downloads (org)
# 2.01 Top exploits (all)
# 2.02 Top exploits (org)
# 3.01 Top sensors (all)
# 3.02 Top sensors (org)
# 4.01 Top destination ports (all)
# 4.02 Top destination ports (org)
# 5.01 Top source addresses (all)
# 5.02 Top source addresses (org)
# 6.01 Top filenames (all)
# 6.02 Top filenames (org)
# 7.01 Top protocols (all)
# 7.02 Top protocols (org)
# 8.01 Top OS (all)
# 8.02 Top OS (org)
# 9.01 Top domains (all)
#####################

#########
# 1.01 Total malicious attacks (all)
#########
add_to_sql("attacks", "table");
add_to_sql("attacks.severity = 1", "where");
add_to_sql("$tsquery", "where");
add_to_sql("DISTINCT COUNT(attacks.severity) as total", "select");
# IP Exclusion stuff
add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $q_org)", "where");
prepare_sql();
$sql_attacks = "SELECT $sql_select ";
$sql_attacks .= " FROM $sql_from ";
$sql_attacks .= " $sql_where ";
$debuginfo[] = $sql_attacks;
# Resetting the sql generation arrays
reset_sql();
#$where = array();
#$table = array();
#$select = array();

#########
# 1.02 Total downloads (all)
#########
add_to_sql("attacks", "table");
add_to_sql("attacks.severity = 32", "where");
add_to_sql("$tsquery", "where");
add_to_sql("DISTINCT COUNT(attacks.severity) as total", "select");
# IP Exclusion stuff
add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $q_org)", "where");
prepare_sql();
$sql_downloads = "SELECT $sql_select ";
$sql_downloads .= " FROM $sql_from ";
$sql_downloads .= " $sql_where ";
$debuginfo[] = $sql_downloads;
# Resetting the sql generation arrays
reset_sql();
#$where = array();
#$table = array();
#$select = array();

#########
# 1.03 Total malicious attacks (org)
#########
add_to_sql("attacks", "table");
add_to_sql("sensors", "table");
add_to_sql("attacks.severity = 1", "where");
add_to_sql(gen_org_sql(), "where");
add_to_sql("sensors.id = attacks.sensorid", "where");
add_to_sql("$tsquery", "where");
add_to_sql("DISTINCT COUNT(attacks.severity) as total", "select");
prepare_sql();
$sql_attacks_org = "SELECT $sql_select ";
$sql_attacks_org .= " FROM $sql_from ";
$sql_attacks_org .= " $sql_where ";
$debuginfo[] = $sql_attacks_org;
reset_sql();

#########
# 1.04 Total downloads (org)
#########
add_to_sql("attacks", "table");
add_to_sql("sensors", "table");
add_to_sql("attacks.severity = 32", "where");
add_to_sql(gen_org_sql(), "where");
add_to_sql("sensors.id = attacks.sensorid", "where");
add_to_sql("$tsquery", "where");
add_to_sql("DISTINCT COUNT(attacks.severity) as total", "select");
prepare_sql();
$sql_downloads_org = "SELECT $sql_select ";
$sql_downloads_org .= " FROM $sql_from ";
$sql_downloads_org .= " $sql_where ";
$debuginfo[] = $sql_downloads_org;
reset_sql();

#########
# 2.01 Top exploits (all)
#########

add_to_sql("attacks", "table");
add_to_sql("details", "table");
add_to_sql("stats_dialogue", "table");
add_to_sql("details.type = 1", "where");
add_to_sql("details.attackid = attacks.id", "where");
add_to_sql("details.text = stats_dialogue.name", "where");
add_to_sql("$tsquery", "where");
add_to_sql("DISTINCT details.text", "select");
add_to_sql("stats_dialogue.id", "select");
add_to_sql("COUNT(details.id) as total", "select");
add_to_sql("details.text", "group");
add_to_sql("stats_dialogue.id", "group");
add_to_sql("total DESC LIMIT $c_topexploits OFFSET 0", "order");
# IP Exclusion stuff
add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $q_org)", "where");
prepare_sql();
$sql_topexp = "SELECT $sql_select ";
$sql_topexp .= " FROM $sql_from ";
$sql_topexp .= " $sql_where ";
$sql_topexp .= " GROUP BY $sql_group ORDER BY $sql_order ";
$debuginfo[] = $sql_topexp;

#########
# 2.02 Top exploits (org)
#########

add_to_sql("sensors", "table");
add_to_sql("sensors.organisation = $q_org", "where");
add_to_sql("sensors.id = attacks.sensorid", "where");
prepare_sql();
$sql_topexp_org = "SELECT $sql_select ";
$sql_topexp_org .= " FROM $sql_from ";
$sql_topexp_org .= " $sql_where ";
$sql_topexp_org .= " GROUP BY $sql_group ORDER BY $sql_order ";
$debuginfo[] = $sql_topexp_org;
reset_sql();

#########
# 3.01 Top sensors (all)
#########

add_to_sql("DISTINCT sensors.organisation", "select");
add_to_sql("sensors.keyname", "select");
add_to_sql("sensors.vlanid", "select");
add_to_sql("sensors.label", "select");
add_to_sql("sensors.id as sensorid", "select");
add_to_sql("COUNT(details.id) as total", "select");
add_to_sql("attacks", "table");
add_to_sql("details", "table");
add_to_sql("sensors", "table");
add_to_sql("details.type = 1", "where");
add_to_sql("sensors.id = attacks.sensorid", "where");
add_to_sql("attacks.id = details.attackid", "where");
add_to_sql("$tsquery", "where");
add_to_sql("sensors.keyname", "group");
add_to_sql("sensors.vlanid", "group");
add_to_sql("sensors.organisation", "group");
add_to_sql("sensors.id", "group");
add_to_sql("sensors.label", "group");
add_to_sql("total DESC LIMIT $c_topsensors OFFSET 0", "order");
# IP Exclusion stuff
add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $q_org)", "where");
prepare_sql();
$sql_top = "SELECT $sql_select";
$sql_top .= " FROM $sql_from ";
$sql_top .= " $sql_where ";
$sql_top .= " GROUP BY $sql_group ORDER BY $sql_order";
$debuginfo[] = $sql_top;

#########
# 3.02 Top sensors (org)
#########

add_to_sql(gen_org_sql(), "where");
prepare_sql();
$sql_top_org = "SELECT $sql_select ";
$sql_top_org .= " FROM $sql_from ";
$sql_top_org .= " $sql_where ";
$sql_top_org .= " GROUP BY $sql_group ORDER BY $sql_order";
$debuginfo[] = $sql_top_org;
reset_sql();

#########
# 4.01 Top destination ports (all)
#########

add_to_sql("DISTINCT attacks.dport", "select");
add_to_sql("COUNT(attacks.dport) as total", "select");
add_to_sql("attacks", "table");
add_to_sql("$tsquery", "where");
add_to_sql("NOT attacks.dport = 0", "where");
add_to_sql("attacks.dport", "group");
add_to_sql("total DESC LIMIT $c_topports OFFSET 0", "order");
# IP Exclusion stuff
add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $q_org)", "where");
prepare_sql();
$sql_topports = "SELECT $sql_select ";
$sql_topports .= " FROM $sql_from ";
$sql_topports .= " $sql_where ";
$sql_topports .= " GROUP BY $sql_group ORDER BY $sql_order ";
$debuginfo[] = $sql_topports;

#########
# 4.02 Top destination ports (org)
#########

add_to_sql("sensors", "table");
add_to_sql("sensors.id = attacks.sensorid", "where");
add_to_sql(gen_org_sql(), "where");
prepare_sql();
$sql_topports_org = "SELECT $sql_select ";
$sql_topports_org .= " FROM $sql_from ";
$sql_topports_org .= " $sql_where ";
$sql_topports_org .= " GROUP BY $sql_group ORDER BY $sql_order";
$debuginfo[] = $sql_topports_org;
reset_sql();

#########
# 5.01 Top source addresses (all)
#########

add_to_sql("DISTINCT attacks.source", "select");
add_to_sql("COUNT(attacks.source) as total", "select");
add_to_sql("attacks", "table");
add_to_sql("$tsquery", "where");
add_to_sql("attacks.source", "group");
add_to_sql("total DESC LIMIT $c_topsourceips", "order");
# IP Exclusion stuff
add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $q_org)", "where");
prepare_sql();
$sql_topsource = "SELECT $sql_select ";
$sql_topsource .= " FROM $sql_from ";
$sql_topsource .= " $sql_where ";
$sql_topsource .= " GROUP BY $sql_group ORDER BY $sql_order ";
$debuginfo[] = $sql_topsource;

#########
# 5.02 Top source addresses (org)
#########

add_to_sql("sensors", "table");
add_to_sql("sensors.id = attacks.sensorid", "where");
add_to_sql(gen_org_sql(), "where");
prepare_sql();
$sql_topsource_org = "SELECT $sql_select ";
$sql_topsource_org .= " FROM $sql_from ";
$sql_topsource_org .= " $sql_where ";
$sql_topsource_org .= " GROUP BY $sql_group ORDER BY $sql_order";
$debuginfo[] = $sql_topsource_org;
reset_sql();

#########
# 6.01 Top filenames (all)
#########

$sql_topfiles = "SELECT DISTINCT sub.file, COUNT(sub.file) as total FROM ";
  $sql_topfiles .= "(SELECT split_part(details.text, '/', 4) as file ";
  $sql_topfiles .= "FROM details, attacks WHERE NOT split_part(details.text, '/', 4) = '' ";
  if ($tsquery != "") {
    $sql_topfiles .= " AND $tsquery ";
  }
  $sql_topfiles .= "AND type = 4  AND details.attackid = attacks.id ";
  $sql_topfiles .= "AND attacks.severity = 16 AND ";
  $sql_topfiles .= "NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $q_org)) as sub ";
$sql_topfiles .= "GROUP BY sub.file ORDER BY total DESC LIMIT $c_topfilenames";
$debuginfo[] = $sql_topfiles;

#########
# 6.02 Top filenames (org)
#########

$sql_topfiles_org = "SELECT DISTINCT sub.file, COUNT(sub.file) as total FROM ";
  $sql_topfiles_org .= "(SELECT split_part(details.text, '/', 4) as file ";
  $sql_topfiles_org .= "FROM details, sensors, attacks WHERE NOT split_part(details.text, '/', 4) = '' ";
  if ($tsquery != "") {
    $sql_topfiles_org .= " AND $tsquery ";
  }
  $sql_topfiles_org .= "AND sensors.id = details.sensorid ";
  $sql_topfiles_org .= "AND type = 4 AND details.attackid = attacks.id ";
  $sql_topfiles_org .= "AND attacks.severity = 16 ";
  $sql_topfiles_org .= "AND " .gen_org_sql();
  $sql_topfiles_org .= " AND NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $q_org)) as sub ";
$sql_topfiles_org .= "GROUP BY sub.file ORDER BY total DESC LIMIT $c_topfilenames";
$debuginfo[] = $sql_topfiles_org;

#########
# 7.01 Top protocols (all)
#########

$sql_topproto = "SELECT DISTINCT sub.proto, COUNT(sub.proto) as total FROM ";
  $sql_topproto .= "(SELECT split_part(details.text, '/', 1) as proto ";
  $sql_topproto .= "FROM details, attacks WHERE 1 = 1 ";
  if ($tsquery != "") {
    $sql_topproto .= " AND $tsquery ";
  }
  $sql_topproto .= "AND type = 4  AND details.attackid = attacks.id AND ";
  $sql_topproto .= "NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $q_org)) as sub ";
$sql_topproto .= "GROUP BY sub.proto ORDER BY total DESC LIMIT $c_topprotocols";
$debuginfo[] = $sql_topproto;

#########
# 7.02 Top protocols (org)
#########

$sql_topproto_org = "SELECT DISTINCT sub.proto, COUNT(sub.proto) as total FROM ";
  $sql_topproto_org .= "(SELECT split_part(details.text, '/', 1) as proto ";
  $sql_topproto_org .= "FROM details, attacks, sensors WHERE 1 = 1 ";
  if ($tsquery != "") {
    $sql_topproto_org .= " AND $tsquery ";
  }
  $sql_topproto_org .= "AND sensors.id = details.sensorid ";
  $sql_topproto_org .= "AND type = 4  AND details.attackid = attacks.id ";
  $sql_topproto_org .= " AND " .gen_org_sql();
  $sql_topproto_org .= " AND NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $q_org)) as sub ";
$sql_topproto_org .= "GROUP BY sub.proto ORDER BY total DESC LIMIT $c_topprotocols";
$debuginfo[] = $sql_topproto_org;

#########
# 8.01 Top OS (all)
#########

$sql_topos = "SELECT DISTINCT sub.os, COUNT(sub.os) as total FROM ";
  $sql_topos .= "(SELECT split_part(system.name, ' ', 1) as os ";
  $sql_topos .= "FROM system, attacks WHERE 1 = 1 ";
  if ($tsquery != "") {
    $sql_topos .= " AND $tsquery ";
  }
  $sql_topos .= " AND attacks.source = system.ip_addr AND ";
  $sql_topos .= "NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $q_org)) as sub ";
$sql_topos .= "GROUP BY sub.os ORDER BY total DESC LIMIT $c_topos";
$debuginfo[] = $sql_topos;

#########
# 8.02 Top OS (org)
#########

$sql_topos_org = "SELECT DISTINCT sub.os, COUNT(sub.os) as total FROM ";
  $sql_topos_org .= "(SELECT split_part(system.name, ' ', 1) as os ";
  $sql_topos_org .= "FROM system, attacks, sensors WHERE 1 = 1 ";
  if ($tsquery != "") {
    $sql_topos_org .= " AND $tsquery ";
  }
  $sql_topos_org .= "AND sensors.id = attacks.sensorid AND sensors.organisation = $q_org ";
  $sql_topos_org .= "AND attacks.source = system.ip_addr ";
  $sql_topos_org .= " AND " .gen_org_sql();
  $sql_topos_org .= " AND NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $q_org)) as sub ";
$sql_topos_org .= "GROUP BY sub.os ORDER BY total DESC LIMIT $c_topos";
$debuginfo[] = $sql_topos_org;

#########
# 9.01 Top domains (all)
#########

add_to_sql("organisations.organisation", "select");
add_to_sql("sensors.organisation as orgid", "select");
add_to_sql("COUNT(attacks.id) as total", "select");
add_to_sql("attacks", "table");
add_to_sql("sensors", "table");
add_to_sql("organisations", "table");
add_to_sql("attacks.severity = 1", "where");
add_to_sql("attacks.sensorid = sensors.id", "where");
add_to_sql("sensors.organisation = organisations.id", "where");
add_to_sql("$tsquery", "where");
add_to_sql("organisations.organisation", "group");
add_to_sql("sensors.organisation", "group");
add_to_sql("total DESC LIMIT $c_toporgs OFFSET 0", "order");
# IP Exclusion stuff
add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $q_org)", "where");
prepare_sql();
$sql_organisation = "SELECT $sql_select ";
$sql_organisation .= " FROM $sql_from ";
$sql_organisation .= " $sql_where ";
$sql_organisation .= " GROUP BY $sql_group ORDER BY $sql_order";
$debuginfo[] = $sql_organisation;
reset_sql();

#####################
# </DATA SQL>
#####################

echo "<div class='all'>\n";
  echo "<div class='leftmed'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockHeader'>Total</div>\n";
        echo "<div class='blockContent'>\n";
          $result_attacks = pg_query($pgconn, $sql_attacks);
          $row = pg_fetch_assoc($result_attacks);
          $total_attacks = $row['total'];

          $result_downloads = pg_query($pgconn, $sql_downloads);
          $row = pg_fetch_assoc($result_downloads);
          $total_downloads = $row['total'];

          if ($total_sensors != 0) {
            $avg_perc = floor(100 / $total_sensors);
          } else {
            $avg_perc = 0;
          }
          echo "<table class='datatable'>\n";
            echo "<tr><td class='title' colspan='2'>Totals</td></tr>\n";
            echo "<tr>\n";
              echo "<th width='80%'>Total malicious attacks of all sensors</th>\n";
              if ($s_access_search == 9) {
                echo "<th width='20%'>" .downlink("logsearch.php?int_sev=1&int_org=0", nf($total_attacks)). "</th>\n";
              } else {
                echo "<th width='20%'>" .nf($total_attacks). "</th>\n";
              }
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td>&nbsp;</td>\n";
              echo "<td></td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<th>Total downloaded malware of all sensors</th>\n";
              if ($s_access_search == 9) {
                echo "<th>" .downlink("logsearch.php?int_sev=32&int_org=0", nf($total_downloads)). "</th>\n";
              } else {
                echo "<th>" .nf($total_downloads). "</th>\n";
              }
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td>&nbsp;</td>\n";
              echo "<td></td>\n";
            echo "</tr>\n";
          echo "</table>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</dataBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</leftmed>

  if ($s_admin != 1 || ($s_admin == 1 && $q_org != 0) ) {
    echo "<div class='rightmed'>\n";
      echo "<div class='block'>\n";
        echo "<div class='dataBlock'>\n";
          echo "<div class='blockHeader'>Own domain</div>\n";
          echo "<div class='blockContent'>\n";
            $result_attacks = pg_query($pgconn, $sql_attacks_org);
            $row = pg_fetch_assoc($result_attacks);
            $org_attacks = $row['total'];
            if ($org_attacks == 0) {
              $org_attacks_perc = '0';
            } else {
              $org_attacks_perc = floor(($org_attacks / $total_attacks) * 100);
            }

            $result_downloads = pg_query($pgconn, $sql_downloads_org);
            $row = pg_fetch_assoc($result_downloads);
            $org_downloads = $row['total'];
            if ($org_downloads == 0) {
              $org_downloads_perc = '0';
            } else {
              $org_downloads_perc = floor(($org_downloads / $total_downloads) * 100);
            }

            echo "<table class='datatable'>\n";
              echo "<tr><td class='title' colspan='2'>Totals</td></tr>\n";
              echo "<tr>\n";
                echo "<th width='80%'>Total malicious attacks for $orgname</th>\n";
                echo "<th width='20%'>" .downlink("logsearch.php?int_sev=1", nf($org_attacks)). "</th>\n";
              echo "</tr>\n";
              echo "<tr>\n";
                echo "<td class='indented'>% of total malicious attacks</td>\n";
                echo "<td>$org_attacks_perc%</td>\n";
              echo "</tr>\n";
              echo "<tr>\n";
                echo "<th>Total downloaded malware by $orgname</th>\n";
                echo "<th>" .downlink("logsearch.php?int_sev=32", nf($org_downloads)). "</th>\n";
              echo "</tr>\n";
              echo "<tr>\n";
                echo "<td class='indented'>% of total collected malware</td>\n";
                echo "<td>$org_downloads_perc%</td>\n";
              echo "</tr>\n";
            echo "</table>\n";
          echo "</div>\n"; #</blockContent>
          echo "<div class='blockFooter'></div>\n";
        echo "</div>\n"; #</dataBlock>
      echo "</div>\n"; #</block>
    echo "</div>\n"; #</rightmed>
  }
echo "</div>\n"; #</all>

############## Top exploits
echo "<div class='all'>\n";
  echo "<div class='leftmed'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockContent'>\n";
          $result_topexp = pg_query($pgconn, $sql_topexp);
          echo "<table class='datatable'>\n";
            echo "<tr><td class='title' colspan='3'>Top $c_topexploits exploits of all sensors</td></tr>\n";
            echo "<tr>\n";
              echo "<th width='5%'>#</th>\n";
              echo "<th width='75%'>Exploit</th>\n";
              echo "<th width='20%'>Total</th>\n";
            echo "</tr>\n";
            $i=1;
            $grandtotal = 0;
            while ($row = pg_fetch_assoc($result_topexp)) {
              $exploit = $row['text'];
              $exploitid = $row['id'];
	      $total = $row['total'];
              $exploit_ar[$exploit] = $total;
              $exploitid_ar[$exploit] = $exploitid;
              $grandtotal = $grandtotal + $total;
            }
            if ($exploit_ar != "") {
              foreach ($exploit_ar as $key => $val) {
                $attack = $v_attacks_ar[$key]["Attack"];
                $attack_url = $v_attacks_ar[$key]["URL"];
                $exploitid  = $exploitid_ar[$key]; 
		echo "<tr>\n";
                  echo "<td>$i.</td>\n";
                  if ($attack_url != "") {
                    echo "<td><a href='$attack_url' target='new'>$attack</a></td>\n";
                  } else {
                    echo "<td>$attack</td>\n";
                  }
                  $perc = round($val / $grandtotal * 100);
                  if ($s_access_search == 9) {
                    echo "<td>" . downlink("logsearch.php?int_org=0&int_sev=1&int_sevtype=0&int_attack=$exploitid", nf($val)). " (${perc}%)</td>\n";
                  } else {
                    echo "<td>" . nf($val) . " (${perc}%)</td>\n";
                  }
                echo "</tr>\n";
                $i++;
              }
            }
          echo "</table>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</dataBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</leftmed>

  if ($s_admin != 1 || ($s_admin == 1 && $q_org != 0) ) {
    echo "<div class='rightmed'>\n";
      echo "<div class='block'>\n";
        echo "<div class='dataBlock'>\n";
          echo "<div class='blockContent'>\n";
            echo "<table class='datatable'>\n";
              echo "<tr><td class='title' colspan='3'>Top $c_topexploits exploits of your sensors</td></tr>\n";
              echo "<tr class='dataheader'>\n";
                echo "<th width='5%'>#</td>\n";
                echo "<th width='75%'>Exploit</td>\n";
                echo "<th width='20%'>Total</td>\n";
              echo "</tr>\n";
              $i = 1;
              $grandtotal = 0;
              $exploit_ar = "";
              $result_topexp_org = pg_query($pgconn, $sql_topexp_org);
              while ($row = pg_fetch_assoc($result_topexp_org)) {
                $exploit = $row['text'];
                $exploitid = $row['id'];
                $total = $row['total'];
                $exploit_ar[$exploit] = $total;
                $exploitid_ar[$exploit] = $exploitid;
                $grandtotal = $grandtotal + $total;
              }
              if ($exploit_ar != "") {
                foreach ($exploit_ar as $key => $val) {
                  $attack = $v_attacks_ar[$key]["Attack"];
                  $attack_url = $v_attacks_ar[$key]["URL"];
                  $exploitid  = $exploitid_ar[$key]; 
                  echo "<tr>\n";
                    echo "<td>$i.</td>\n";
                    if ($attack_url != "") {
                      echo "<td><a href='$attack_url' target='new'>$attack</a></td>\n";
                    } else {
                      echo "<td>$attack</td>\n";
                    }
                    $perc = round($val / $grandtotal * 100);
                    echo "<td>" . downlink("logsearch.php?int_sev=1&int_sevtype=0&int_attack=$exploitid", nf($val)). " (${perc}%)</td>\n";
                  echo "</tr>\n";
                  $i++;
                }
              }
            echo "</table>\n";
          echo "</div>\n"; #</blockContent>
          echo "<div class='blockFooter'></div>\n";
        echo "</div>\n"; #</dataBlock>
      echo "</div>\n"; #</block>
    echo "</div>\n"; #</rightmed>
  }
echo "</div>\n"; #</all>

########################## Top 10 sensors
echo "<div class='all'>\n";
  echo "<div class='leftmed'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockContent'>\n";
          $result_top = pg_query($pgconn, $sql_top);

          echo "<table class='datatable'>\n";
            echo "<tr><td class='title' colspan='3'>Top $c_topsensors sensors</td></tr>\n";
            echo "<tr>\n";
              echo "<th width='5%'>#</th>\n";
              echo "<th width='75%'>Sensor</th>\n";
              echo "<th width='20%'>Total exploits</th>\n";
            echo "</tr>\n";
            $i=1;
            $rank_ar = array();
            while ($row = pg_fetch_assoc($result_top)) {
              $db_org = $row['organisation'];

              $sql_getorg = "SELECT organisation FROM organisations WHERE id = $db_org";
              $result_getorg = pg_query($pgconn, $sql_getorg);
              $db_org_name = pg_result($result_getorg, 0);

              $debuginfo[] = $sql_getorg;

              $id = $row['sensorid'];
              $keyname = $row['keyname'];
              $vlanid = $row['vlanid'];
              $sensor = sensorname($keyname, $vlanid);
              $total = $row['total'];
              $label = $row['label'];
              if ($label != "") {
                $str = $label;
              } else {
                $str = $sensor;
              }
              $rank_ar["$keyname-$vlanid"] = $i;
              if ($i <= $c_topsensors) {
                echo "<tr>\n";
                  echo "<td>$i.</td>\n";
                  if ($s_access_search == 9) {
                    echo "<td>$db_org_name - $str</td>\n";
                  } elseif ($q_org == $db_org) {
                    echo "<td>$str</td>\n";
                  } else {
                    echo "<td></td>\n";
                  }
                  if ($s_access_search == 9) {
                    echo "<td>" . downlink("logsearch.php?int_org=0&int_sev=1&int_sevtype=0&sensorid[]=$id", nf($total)). "</td>\n";
                  } else {
                    echo "<td>" . nf($total) . "</td>\n";
                  }
                echo "</tr>\n";
              }
              $i++;
            }
          echo "</table>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</dataBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</leftmed>

  if ($s_admin != 1 || ($s_admin == 1 && $q_org != 0) ) {
    echo "<div class='rightmed'>\n";
      echo "<div class='block'>\n";
        echo "<div class='dataBlock'>\n";
          echo "<div class='blockContent'>\n";
            echo "<table class='datatable'>\n";
              echo "<tr><td class='title' colspan='4'>Top $c_topsensors sensors of $orgname</td></tr>\n";
              echo "<tr>\n";
                echo "<th width='5%'>#</th>\n";
                echo "<th width='25%'>Overall Rank</th>\n";
                echo "<th width='50%'>Sensor</th>\n";
                echo "<th width='20%'>Total exploits</th>\n";
              echo "</tr>\n";
              $i = 1;
              $result_top_org = pg_query($pgconn, $sql_top_org);
              while ($row_top_org = pg_fetch_assoc($result_top_org)) {
                echo "<tr>\n";
                  echo "<td>$i.</td>\n";
                  $id = $row_top_org['sensorid'];
                  $keyname = $row_top_org['keyname'];
                  $vlanid = $row_top_org['vlanid'];
                  $sensor = sensorname($keyname, $vlanid);
                  $total = $row_top_org['total'];
                  $label = $row_top_org['label'];
                  if ($label != "") {
                    $str = $label;
                  } else {
                    $str = $sensor;
                  }
                  $rank_all = $rank_ar["$keyname-$vlanid"];

                  echo "<td># $rank_all</td>\n";
                  echo "<td>$str</td>\n";
                  echo "<td>" .downlink("logsearch.php?int_sev=1&int_sevtype=0&sensorid[]=$id", nf($total)). "</td>\n";
                echo "</tr>\n";
                $i++;
              }
            echo "</table>\n";
          echo "</div>\n"; #</blockContent>
          echo "<div class='blockFooter'></div>\n";
        echo "</div>\n"; #</dataBlock>
      echo "</div>\n"; #</block>
    echo "</div>\n"; #</rightmed>
  }
echo "</div>\n"; #</all>

########################## Top 10 ports // Contribution by bjou
echo "<div class='all'>\n";
  echo "<div class='leftmed'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockContent'>\n";
          $result_topports = pg_query($pgconn, $sql_topports);

          echo "<table class='datatable'>\n";
            echo "<tr><td class='title' colspan='4'>Top $c_topports ports of all sensors</td></tr>\n"; // change this into variable to be read from conf
            echo "<tr>\n";
              echo "<th width='5%'>#</th>\n";
              echo "<th width='15%'>Port</th>\n";
              echo "<th width='60%'>Port Description</th>\n";
              echo "<th width='20%'>Total</th>\n";
            echo "</tr>\n";
            $i=1;
            $grandtotal = 0;
            while ($row = pg_fetch_assoc($result_topports)) {
              $port = $row['dport'];
              $total = $row['total'];
              $grandtotal = $grandtotal + $total;
              $port_ar[$port] = $total;
            }
            if ($port_ar != "") {
              foreach ($port_ar as $key => $val) {
                echo "<tr>\n";
                  echo "<td>$i</td>\n";
                  echo "<td>$key</td>\n";
                  echo "<td><a target='_blank' href='http://www.iss.net/security_center/advice/Exploits/Ports/$key'>".getPortDescr($key)."</a></td>\n";
                  $perc = round($val / $grandtotal * 100);
                  if ($s_access_search == 9) {
                    echo "<td>" .downlink("logsearch.php?int_dport=$key&int_org=0", $val). " (${perc}%)</td>\n";
                  } else {
                    echo "<td>$val (${perc}%)</td>\n";
                  }
                echo "</tr>\n";
                $i++;
              }
            }
          echo "</table>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</dataBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</leftmed>

  if ($s_admin != 1 || ($s_admin == 1 && $q_org != 0) ) {
    echo "<div class='rightmed'>\n";
      echo "<div class='block'>\n";
        echo "<div class='dataBlock'>\n";
          echo "<div class='blockContent'>\n";
            echo "<table class='datatable'>\n";
              echo "<tr><td class='title' colspan='4'>Top $c_topports ports of your sensors</td></tr>\n";
              echo "<tr>\n";
                echo "<th width='5%'>#</th>\n";
                echo "<th width='15%'>Port</th>\n";
                echo "<th width='60%'>Port Description</th>\n";
                echo "<th width='20%'>Total</th>\n";
              echo "</tr>\n";
              $i = 1;
              $grandtotal = 0;
              $port_ar = "";
              $result_topports_org = pg_query($pgconn, $sql_topports_org);
              while ($row = pg_fetch_assoc($result_topports_org)) {
                $port = $row['dport'];
                $total = $row['total'];
                $grandtotal = $grandtotal + $total;
                $port_ar[$port] = $total;
              }
              if ($port_ar != "") {
                foreach ($port_ar as $key => $val) {
                  echo "<tr>\n";
                    echo "<td>$i</td>\n";
                    echo "<td>$key</td>\n";
                    echo "<td><a target='_blank' href='http://www.iss.net/security_center/advice/Exploits/Ports/$key'>".getPortDescr($key)."</a></td>\n";
                    $perc = round($val / $grandtotal * 100);
                    echo "<td>" .downlink("logsearch.php?int_dport=$key", $val). " (${perc}%)</td>\n";
                  echo "</tr>\n";
                  $i++;
                }
              }
            echo "</table>\n";
          echo "</div>\n"; #</blockContent>
          echo "<div class='blockFooter'></div>\n";
        echo "</div>\n"; #</dataBlock>
      echo "</div>\n"; #</block>
    echo "</div>\n"; #</rightmed>
  }
echo "</div>\n"; #</all>

########################## Top 10 source addresses // Contribution by bjou
echo "<div class='all'>\n";
  echo "<div class='leftmed'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockContent'>\n";
          $result_topsource = pg_query($pgconn, $sql_topsource);

          echo "<table class='datatable'>\n";
            echo "<tr><td class='title' colspan='3'>Top $c_topsourceips source addresses of all sensors</span>\n";// change this into variable to be read from conf
            echo "<tr>\n";
              echo "<th width='5%'>#</th>\n";
              echo "<th width='75%'>Address</th>\n";
              echo "<th width='20%'>Total</th>\n";
            echo "</tr>\n";
            $i = 1;
            $grandtotal = 0;
            while ($row = pg_fetch_assoc($result_topsource)) {
              $source = $row['source'];
              $total = $row['total'];
              $grandtotal = $grandtotal + $total;
              $source_ar[$source] = $total;
            }
            if ($source_ar != "") {
              foreach ($source_ar as $key => $val) {
                echo "<tr>\n";
                  echo "<td>$i</td>\n";
                  echo "<td>";
                    if ($c_enable_pof == 1) {
                      $sql_finger = "SELECT name FROM system WHERE ip_addr = '" .$key. "' ORDER BY last_tstamp DESC";
                      $result_finger = pg_query($pgconn, $sql_finger);
                      $numrows_finger = pg_num_rows($result_finger);

                      $fingerprint = pg_result($result_finger, 0);
                      $finger_ar = explode(" ", $fingerprint);
                      $os = $finger_ar[0];
                    } else {
                      $numrows_finger = 0;
                    }
                    if ($numrows_finger != 0) {
                      echo printosimg($os, $fingerprint);
                    } else {
                      echo printosimg("Blank", "No info");
                    }
                    if ($c_geoip_enable == 1) {
                      $record = geoip_record_by_addr($gi, $key);
                      $countrycode = strtolower($record->country_code);
                      $cimg = "$c_surfidsdir/webinterface/images/worldflags/flag_" .$countrycode. ".gif";
                      if (file_exists($cimg)) {
                        $country = $record->country_name;
                        echo printflagimg($country, $countrycode);
                      } else {
                        echo printflagimg("none", "");
                      }
                    }
                    $sql_ranges = "SELECT ranges FROM organisations WHERE id = $q_org";
                    $debuginfo[] = $sql_ranges;
                    $result_ranges = pg_query($pgconn, $sql_ranges);
                    $rowrange = pg_fetch_assoc($result_ranges);
                    $ranges_ar = explode(";", $rowrange['ranges']);
                    if (matchCIDR($key, $ranges_ar)) {
                      echo "<a onclick=\"popUp('" ."whois.php?ip_ip=$key". "', 500, 500);\" class='warning' />$key</a>&nbsp;&nbsp;";
                      echo "<img src='images/ownranges.jpg' ".printover("IP from your own ranges!") ."></td>\n";
                    } else {
                      echo "<a onclick=\"popUp('" ."whois.php?ip_ip=$key". "', 500, 500);\" />$key</a>";
                    }
                  echo "</td>\n";
                  $perc = round($val / $grandtotal * 100);
                  if ($s_access_search == 9) {
                    echo "<td>" .downlink("logsearch.php?inet_source=$key&int_org=0", $val). " (${perc}%)</td>\n";
                  } else {
                    echo "<td>$val (${perc}%)</td>\n";
                  }
                echo "</tr>\n";
                $i++;
              }
            }
          echo "</table>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</dataBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</leftmed>

  if ($s_admin != 1 || ($s_admin == 1 && $q_org != 0) ) {
    echo "<div class='rightmed'>\n";
      echo "<div class='block'>\n";
        echo "<div class='dataBlock'>\n";
          echo "<div class='blockContent'>\n";
            echo "<table class='datatable'>\n";
              echo "<tr><td class='title' colspan='3'>Top $c_topsourceips source addresses of your sensors</td></tr>\n";  // change this into variable to be read from conf
              echo "<tr>\n";
                echo "<th width='5%'>#</th>\n";
                echo "<th width='75%'>Address</th>\n";
                echo "<th width='20%'>Total</th>\n";
              echo "</tr>\n";
              $i = 1;
              $grandtotal = 0;
              $source_ar = "";
              $result_topsource_org = pg_query($pgconn, $sql_topsource_org);
              while ($row = pg_fetch_assoc($result_topsource_org)) {
                $source = $row['source'];
                $total = $row['total'];
                $grandtotal = $grandtotal + $total;
                $source_ar[$source] = $total;
              }
              if ($source_ar != "") {
                foreach ($source_ar as $key => $val) {
                  echo "<tr>\n";
                    echo "<td>$i</td>\n";
                    echo "<td>";
                      if ($c_enable_pof == 1) {
                        $sql_finger = "SELECT name FROM system WHERE ip_addr = '" .$key. "' ORDER BY last_tstamp DESC";
                        $result_finger = pg_query($pgconn, $sql_finger);
                        $numrows_finger = pg_num_rows($result_finger);

                        $fingerprint = pg_result($result_finger, 0);
                        $finger_ar = explode(" ", $fingerprint);
                        $os = $finger_ar[0];
                      } else {
                        $numrows_finger = 0;
                      }
                      if ($numrows_finger != 0) {
                        echo printosimg($os, $fingerprint);
                      } else {
                        echo printosimg("Blank", "No info");
                      }
                      if ($c_geoip_enable == 1) {
                        $record = geoip_record_by_addr($gi, $key);
                        $countrycode = strtolower($record->country_code);
                        $cimg = "$c_surfidsdir/webinterface/images/worldflags/flag_" .$countrycode. ".gif";
                        if (file_exists($cimg)) {
                          $country = $record->country_name;
                          echo printflagimg($country, $countrycode);
                        } else {
                          echo printflagimg("none", "");
                        }
                      }
                      if (matchCIDR($key, $ranges_ar)) {
                        echo "<a onclick=\"popUp('" ."whois.php?ip_ip=$key". "', 500, 500);\" class='warning'>$key</a>&nbsp;&nbsp;";
                        echo "<img src='images/ownranges.jpg' ".printover("IP from your own ranges!") ."></td>\n";
                      } else {
                        echo "<a onclick=\"popUp('" ."whois.php?ip_ip=$key". "', 500, 500);\">$key </a></td>\n";
                      }
                    echo "</td>\n";
                    $perc = round($val / $grandtotal * 100);
                    echo "<td>" .downlink("logsearch.php?inet_source=$key", $val). " (${perc}%)</td>\n";
                  echo "</tr>\n";
                  $i++;
                }
              }
            echo "</table>\n";
          echo "</div>\n"; #</blockContent>
          echo "<div class='blockFooter'></div>\n";
        echo "</div>\n"; #</dataBlock>
      echo "</div>\n"; #</block>
    echo "</div>\n"; #</rightmed>
  }
echo "</div>\n"; #</all>

########################## Top 10 Filenames // Contribution by bjou
echo "<div class='all'>\n";
  echo "<div class='leftmed'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockContent'>\n";
          $result_topfiles = pg_query($pgconn, $sql_topfiles);
        
          echo "<table class='datatable'>\n";
            echo "<tr><td class='title' colspan='3'>Top $c_topfilenames filenames of all sensors</td></tr>\n"; // change this into variable to be read from conf
            echo "<tr>\n";
              echo "<th width='5%'>#</th>\n";
              echo "<th width='75%'>Filename</th>\n";
              echo "<th width='20%'>Total</th>\n";
            echo "</tr>\n";
            $i = 0;
            $grandtotal = 0;
            while ($row = pg_fetch_assoc($result_topfiles)) {
              if ($i == $c_topfilenames) {
                break;
              }
              $url = $row['file'];
              $total = $row['total'];
              $array = @parse_url($url);
              $filename = trim($array['path'],'/');
              $grandtotal = $grandtotal + $total;
              $file_ar[$filename] = $total;
            }
            if ($file_ar != "") {
              foreach ($file_ar as $key => $val) {
                $i++;
                echo "<tr>\n";
                  echo "<td>$i</td>\n";
                  echo "<td>$key</td>\n";
                  $perc = round($val / $grandtotal * 100);
                  if ($s_access_search == 9) {
                    echo "<td>" .downlink("logsearch.php?int_sev=16&strip_html_escape_filename=$key&int_org=0", $val). " (${perc}%)</td>\n";
                  } else {
                    echo "<td>$val (${perc}%)</td>\n";
                  }
                echo "</tr>\n";
              }
            }
          echo "</table>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</dataBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</leftmed>

  if ($s_admin != 1 || ($s_admin == 1 && $q_org != 0) ) {
    echo "<div class='rightmed'>\n";
      echo "<div class='block'>\n";
        echo "<div class='dataBlock'>\n";
          echo "<div class='blockContent'>\n";
            echo "<table class='datatable'>\n";
              echo "<tr><td class='title' colspan='3'>Top $c_topfilenames filenames of your sensors</td><tr>\n";// change this into variable to be read from conf
              echo "<tr>\n";
                echo "<th width='5%'>#</th>\n";
                echo "<th width='75%'>Filename</th>\n";
                echo "<th width='20%'>Total</th>\n";
              echo "</tr>\n";
              $filenameArray = array();
              $i = 0;
              $grandtotal = 0;
              $file_ar = "";
              $result_topfiles_org = pg_query($pgconn, $sql_topfiles_org);
              while ($row = pg_fetch_assoc($result_topfiles_org)) {
                if ($i == $c_topfilenames) {
                  break;
                }
                $url = $row['file'];
                $total = $row['total'];
                $array = @parse_url($url);
                $filename = trim($array['path'],'/');
                $grandtotal = $grandtotal + $total;
                $file_ar[$filename] = $total;
              }
              if ($file_ar != "") {
                foreach ($file_ar as $key => $val) {
                  $i ++;
                  echo "<tr>\n";
                    echo "<td>$i</td>\n";
                    echo "<td>$key</td>\n";
                    $perc = round($val / $grandtotal * 100);
                    echo "<td>" .downlink("logsearch.php?int_sev=16&strip_html_escape_filename=$key", $val). " (${perc}%)</td>\n";
                  echo "</tr>\n";
                }
              }
            echo "</table>\n";
          echo "</div>\n"; #</blockContent>
          echo "<div class='blockFooter'></div>\n";
        echo "</div>\n"; #</dataBlock>
      echo "</div>\n"; #</block>
    echo "</div>\n"; #</leftmed>
  }
echo "</div>\n"; #</all>

########################## Top 10 Protocols
echo "<div class='all'>\n";
  echo "<div class='leftmed'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockContent'>\n";
          $result_topproto = pg_query($pgconn, $sql_topproto);
        
          echo "<table class='datatable'>\n";
            echo "<tr><td class='title' colspan='3'>Top $c_topprotocols download protocols of all sensors</td></tr>\n";
            echo "<tr>\n";
              echo "<th width='5%'>#</th>\n";
              echo "<th width='75%'>Protocol</th>\n";
              echo "<th width='20%'>Total</th>\n";
            echo "</tr>\n";
            $i = 0;
            $grandtotal = 0;
            while ($row = pg_fetch_assoc($result_topproto)) {
              if ($i == $c_topprotocols) {
                break;
              }
              $tempproto = $row['proto'];
              $total = $row['total'];
              $proto = str_replace(":", "", $tempproto);
              $grandtotal = $grandtotal + $total;
              $proto_ar[$proto] = $total;
            }
            if ($proto_ar != "") {
              foreach ($proto_ar as $key => $val) {
                $i++;
                echo "<tr>\n";
                  echo "<td>$i</td>\n";
                  echo "<td>$key</td>\n";
                  $perc = round($val / $grandtotal * 100);
                  echo "<td>$val (${perc}%)</td>\n";
                echo "</tr>\n";
              }
            }
          echo "</table>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</dataBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</leftmed>

  if ($s_admin != 1 || ($s_admin == 1 && $q_org != 0) ) {
    echo "<div class='rightmed'>\n";
      echo "<div class='block'>\n";
        echo "<div class='dataBlock'>\n";
          echo "<div class='blockContent'>\n";
            echo "<table class='datatable'>\n";
              echo "<tr><td class='title' colspan='3'>Top $c_topprotocols download protocols of your sensors</td></tr>\n";
              echo "<tr>\n";
                echo "<th width='5%'>#</th>\n";
                echo "<th width='75%'>Protocol</th>\n";
                echo "<th width='20%'>Total</th>\n";
              echo "</tr>\n";
              $i = 0;
              $grandtotal = 0;
              $proto_ar = "";
              $result_topproto_org = pg_query($pgconn, $sql_topproto_org);
              while ($row = pg_fetch_assoc($result_topproto_org)) {
                if ($i == $c_topprotocols) {
                  break;
                }
                $tempproto = $row['proto'];
                $total = $row['total'];
                $proto = str_replace(":", "", $tempproto);
                $grandtotal = $grandtotal + $total;
                $proto_ar[$proto] = $total;
              }
              if ($proto_ar != "") {
                foreach ($proto_ar as $key => $val) {
                  $i ++;
                  echo "<tr>\n";
                    echo "<td>$i</td>\n";
                    echo "<td>$key</td>\n";
                    $perc = round($val / $grandtotal * 100);
                    echo "<td>$val (${perc}%)</td>\n";
                  echo "</tr>\n";
                }
              }
            echo "</table>\n";
          echo "</div>\n"; #</blockContent>
          echo "<div class='blockFooter'></div>\n";
        echo "</div>\n"; #</dataBlock>
      echo "</div>\n"; #</block>
    echo "</div>\n"; #</leftmed>
  }
echo "</div>\n"; #</all>

########################## Top 10 attacker OS's
echo "<div class='all'>\n";
  echo "<div class='leftmed'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockContent'>\n";
          $result_topos = pg_query($pgconn, $sql_topos);
        
          echo "<table class='datatable'>\n";
            echo "<tr><td class='title' colspan='3'>Top $c_topos attacker OS's of all sensors</td></tr>\n";
            echo "<tr>\n";
              echo "<th width='5%'>#</th>\n";
              echo "<th width='75%'>OS</th>\n";
              echo "<th width='20%'>Total</th>\n";
            echo "</tr>\n";
            $i = 0;
            $grandtotal = 0;
            while ($row = pg_fetch_assoc($result_topos)) {
              if ($i == $c_topos) {
                break;
              }
              $os = $row['os'];
              $total = $row['total'];
              $grandtotal = $grandtotal + $total;
              $os_ar[$os] = $total;
            }
            if ($os_ar != "") {
              foreach ($os_ar as $key => $val) {
                $i++;
                echo "<tr>\n";
                  echo "<td>$i</td>\n";
                  echo "<td>$key</td>\n";
                  $perc = round($val / $grandtotal * 100);
                  echo "<td>$val (${perc}%)</td>\n";
                echo "</tr>\n";
              }
            }
          echo "</table>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</dataBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</leftmed>

  if ($s_admin != 1 || ($s_admin == 1 && $q_org != 0) ) {
    echo "<div class='rightmed'>\n";
      echo "<div class='block'>\n";
        echo "<div class='dataBlock'>\n";
          echo "<div class='blockContent'>\n";
            echo "<table class='datatable'>\n";
              echo "<tr><td class='title' colspan='3'>Top $c_topos attacker OS's of your sensors</td></tr>\n";
              echo "<tr>\n";
                echo "<th width='5%'>#</th>\n";
                echo "<th width='75%'>OS</th>\n";
                echo "<th width='20%'>Total</th>\n";
              echo "</tr>\n";
              $i = 0;
              $grandtotal = 0;
              $os_ar = "";
              $result_topos_org = pg_query($pgconn, $sql_topos_org);
              while ($row = pg_fetch_assoc($result_topos_org)) {
                if ($i == $c_topos) {
                  break;
                }
                $os = $row['os'];
                $total = $row['total'];
                $grandtotal = $grandtotal + $total;
                $os_ar[$os] = $total;
              }
              if ($os_ar != "") {
                foreach ($os_ar as $key => $val) {
                  $i ++;
                  echo "<tr>\n";
                    echo "<td>$i</td>\n";
                    echo "<td>$key</td>\n";
                    $perc = round($val / $grandtotal * 100);
                    echo "<td>$val (${perc}%)</td>\n";
                  echo "</tr>\n";
                }
              }
            echo "</table>\n";
          echo "</div>\n"; #</blockContent>
          echo "<div class='blockFooter'></div>\n";
        echo "</div>\n"; #</dataBlock>
      echo "</div>\n"; #</block>
    echo "</div>\n"; #</rightmed>
  }
echo "</div>\n"; #</all>

########################## Top 5 Organisations
echo "<div class='all'>\n";
  echo "<div class='leftmed'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockContent'>\n";
          $result_organisation = pg_query($pgconn, $sql_organisation);

          echo "<table class='datatable' width='45%'>\n";
            echo "<tr><td class='title' colspan='3'>Top $c_toporgs domains</td></tr>\n";
            echo "<tr>\n";
              echo "<th width='5%'>#</th>\n";
              echo "<th width='75%'>Domain</th>\n";
              echo "<th width='20%'>Total exploits</th>\n";
            echo "</tr>\n";
            $i = 0;
            while ($row = pg_fetch_assoc($result_organisation)) {
              $i++;
              $db_org_name = $row['organisation'];
              $id = $row['orgid'];
              $count = $row['total'];
              echo "<tr>\n";
                echo "<td>$i</td>\n";
                if ($s_access_search == 9) {
                  echo "<td>$db_org_name</td>\n";
                } elseif ($q_org == $id) {
                  echo "<td>$db_org_name</td>\n";
                } else {
                  echo "<td></td>\n";
                }            
                echo "<td>" .downlink("logsearch.php?int_org=$id", nf($count)). "</td>\n";
              echo "</tr>\n";
            }
          echo "</table>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</dataBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</leftmed>
echo "</div>\n"; #</all>

debug_sql();
?>
<?php footer(); ?>

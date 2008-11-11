<?php $tab="2.9"; $pagetitle="Grouping"; include("menu.php"); contentHeader(); ?>
<?php

####################################
# SURFids 2.10                     #
# Changeset 002                    #
# 10-11-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

####################################
# Changelog:
# 002 Fixed bug for non-admin users
# 001 Initial release
####################################

### GEOIP STUFF
if ($c_geoip_enable == 1) {
  include '../include/' .$c_geoip_module;
  $gi = geoip_open("../include/" .$c_geoip_data, GEOIP_STANDARD);
}

$tsquery = " timestamp >= $from AND timestamp <= $to";

# Retrieving posted variables from $_GET
$allowed_get = array(
                "int_lgid",
                "int_rgid"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

if (isset($clean['lgid'])) {
  $lgid = $clean['lgid'];
}

if (isset($clean['rgid'])) {
  $rgid = $clean['rgid'];
}

echo "<div class='all'>\n";
echo "<div class='leftmed'>\n";
  echo "<div class='block'>\n";
    echo "<div class='actionBlock'>\n";
      echo "<div class='blockHeader'>" .$l['gr_select']. "</div>\n";
      echo "<div class='blockContent'>\n";
        echo "<form id='lgidsel' name='lgidsel' method='get'>\n";
        if ($s_access_user != 9) {
          $sql = "SELECT groups.id, name, organisation ";
          $sql .= " FROM groups, organisations WHERE owner = '$q_org' AND groups.owner = organisations.id";
        } else {
          $sql = "SELECT groups.id, name, organisation FROM groups, organisations WHERE groups.owner = organisations.id";
        }
        $result_groups = pg_query($pgconn, $sql);
        echo "<select name='int_lgid' onChange='javascript: this.form.submit();'>\n";
          echo printOption(0, "", $lgid);
          while ($row = pg_fetch_assoc($result_groups)) {
            $gid = $row['id'];
            $name = $row['name'];
            $org = $row['organisation'];
            if ($gid == $lgid) {
              $lname = $name;
            }
            echo printOption($gid, "$name - $org", $lgid);
          }
        echo "</select>\n";
        echo "<input type='hidden' name='int_rgid' value='$rgid' />\n";
        echo "</form>\n";
      echo "</div>\n"; #</blockContent>
      echo "<div class='blockFooter'></div>\n";
    echo "</div>\n"; #</actionBlock>
  echo "</div>\n"; #</block>
echo "</div>\n"; #</left>

echo "<div class='rightmed'>\n";
  echo "<div class='block'>\n";
    echo "<div class='actionBlock'>\n";
      echo "<div class='blockHeader'>" .$l['gr_select']. "</div>\n";
      echo "<div class='blockContent'>\n";
        pg_result_seek($result_groups, 0);
        echo "<form id='rgidsel' name='rgidsel' method='get'>\n";
        echo "<select name='int_rgid' onChange='javascript: this.form.submit();'>\n";
          echo printOption(0, "", $rgid);
          while ($row = pg_fetch_assoc($result_groups)) {
            $gid = $row['id'];
            $name = $row['name'];
            $org = $row['organisation'];
            if ($gid == $rgid) {
              $rname = $name;
            }
            echo printOption($gid, "$name - $org", $rgid);
          }
        echo "</select>\n";
        echo "<input type='hidden' name='int_lgid' value='$lgid' />\n";
        echo "</form>\n";
      echo "</div>\n"; #</blockContent>
      echo "<div class='blockFooter'></div>\n";
    echo "</div>\n"; #</actionBlock>
  echo "</div>\n"; #</block>
echo "</div>\n"; #</right>
echo "</div>\n"; #</all>

if ($lname != "" && $rname != "") {

  ###############################
  # Detected connections
  ###############################

  # Preparing general query stuff
  add_to_sql("attacks", "table");
  add_to_sql("$tsquery", "where");
  add_to_sql("DISTINCT COUNT(attacks.severity) as total, severity", "select");
  add_to_sql("severity", "group");
  add_to_sql("severity ASC", "order");
  prepare_sql();

  # Left group query (all attacks)
  $sql_attacks_l = "SELECT $sql_select ";
  $sql_attacks_l .= " FROM $sql_from ";
  $sql_attacks_l .= " $sql_where ";
  $sql_attacks_l .= " AND sensorid IN (SELECT sensorid FROM groupmembers WHERE groupid = '$lgid') ";
  $sql_attacks_l .= " GROUP BY $sql_group ";
  $sql_attacks_l .= " ORDER BY $sql_order ";
  $debuginfo[] = $sql_attacks_l;

  # Right group query (all attacks)
  $sql_attacks_r = "SELECT $sql_select ";
  $sql_attacks_r .= " FROM $sql_from ";
  $sql_attacks_r .= " $sql_where ";
  $sql_attacks_r .= " AND sensorid IN (SELECT sensorid FROM groupmembers WHERE groupid = '$rgid') ";
  $sql_attacks_r .= " GROUP BY $sql_group ";
  $sql_attacks_r .= " ORDER BY $sql_order ";
  $debuginfo[] = $sql_attacks_r;

  # Left group query (malicious attacks)
  $sql_atype_l = "SELECT DISTINCT attacks.atype, COUNT(attacks.atype) as total ";
  $sql_atype_l .= " FROM $sql_from ";
  $sql_atype_l .= " $sql_where AND severity = 1 ";
  $sql_atype_l .= " AND sensorid IN (SELECT sensorid FROM groupmembers WHERE groupid = '$lgid') ";
  $sql_atype_l .= " GROUP BY atype ";
#  $sql_atype_l .= " ORDER BY $sql_order ";
  $debuginfo[] = $sql_atype_l;

  # Right group query (malicious attacks)
  $sql_atype_r = "SELECT DISTINCT attacks.atype, COUNT(attacks.atype) as total ";
  $sql_atype_r .= " FROM $sql_from ";
  $sql_atype_r .= " $sql_where AND severity = 1 ";
  $sql_atype_r .= " AND sensorid IN (SELECT sensorid FROM groupmembers WHERE groupid = '$rgid') ";
  $sql_atype_r .= " GROUP BY atype ";
#  $sql_atype_r .= " ORDER BY $sql_order ";
  $debuginfo[] = $sql_atype_r;

  # Resetting the sql generation arrays
  reset_sql();

  ###############################
  # Exploits
  ###############################

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
  add_to_sql("stats_dialogue.id", "group");
  add_to_sql("details.text", "group");
  add_to_sql("total DESC LIMIT $c_topexploits", "order");
  prepare_sql();

  $sql_topexp_l = "SELECT $sql_select ";
  $sql_topexp_l .= " FROM $sql_from ";
  $sql_topexp_l .= " $sql_where ";
  $sql_topexp_l .= " AND attacks.sensorid IN (SELECT sensorid FROM groupmembers WHERE groupid = '$lgid') ";
  $sql_topexp_l .= " GROUP BY $sql_group ";
  $sql_topexp_l .= " ORDER BY $sql_order ";
  $debuginfo[] = $sql_topexp_l;

  $sql_topexp_r = "SELECT $sql_select ";
  $sql_topexp_r .= " FROM $sql_from ";
  $sql_topexp_r .= " $sql_where ";
  $sql_topexp_r .= " AND attacks.sensorid IN (SELECT sensorid FROM groupmembers WHERE groupid = '$rgid') ";
  $sql_topexp_r .= " GROUP BY $sql_group ";
  $sql_topexp_r .= " ORDER BY $sql_order ";
  $debuginfo[] = $sql_topexp_r;

  # Resetting the sql generation arrays
  reset_sql();

  ###############################
  # Destination ports
  ###############################

  add_to_sql("DISTINCT attacks.dport", "select");
  add_to_sql("COUNT(attacks.dport) as total", "select");
  add_to_sql("attacks", "table");
  add_to_sql("$tsquery", "where");
  add_to_sql("NOT attacks.dport = 0", "where");
  add_to_sql("attacks.dport", "group");
  add_to_sql("total DESC LIMIT $c_topports", "order");
  prepare_sql();

  $sql_topports_l = "SELECT $sql_select ";
  $sql_topports_l .= " FROM $sql_from ";
  $sql_topports_l .= " $sql_where ";
  $sql_topports_l .= " AND attacks.sensorid IN (SELECT sensorid FROM groupmembers WHERE groupid = '$lgid') ";
  $sql_topports_l .= " GROUP BY $sql_group ORDER BY $sql_order ";
  $debuginfo[] = $sql_topports_l;

  $sql_topports_r = "SELECT $sql_select ";
  $sql_topports_r .= " FROM $sql_from ";
  $sql_topports_r .= " $sql_where ";
  $sql_topports_r .= " AND attacks.sensorid IN (SELECT sensorid FROM groupmembers WHERE groupid = '$rgid') ";
  $sql_topports_r .= " GROUP BY $sql_group ORDER BY $sql_order ";
  $debuginfo[] = $sql_topports_r;

  # Resetting the sql generation arrays
  reset_sql();

  ###############################
  # Filenames
  ###############################

  $sql_topfiles_l = "SELECT DISTINCT sub.file, COUNT(sub.file) as total FROM ";
    $sql_topfiles_l .= "(SELECT split_part(details.text, '/', 4) as file ";
    $sql_topfiles_l .= "FROM details, attacks WHERE NOT split_part(details.text, '/', 4) = '' ";
    if ($tsquery != "") {
      $sql_topfiles_l .= " AND $tsquery ";
    }
    $sql_topfiles_l .= " AND attacks.sensorid IN (SELECT sensorid FROM groupmembers WHERE groupid = '$lgid') ";
    $sql_topfiles_l .= " AND type = 4  AND details.attackid = attacks.id ";
    $sql_topfiles_l .= " ) as sub ";
  $sql_topfiles_l .= "GROUP BY sub.file ORDER BY total DESC LIMIT $c_topfilenames";
  $debuginfo[] = $sql_topfiles_l;

  $sql_topfiles_r = "SELECT DISTINCT sub.file, COUNT(sub.file) as total FROM ";
    $sql_topfiles_r .= "(SELECT split_part(details.text, '/', 4) as file ";
    $sql_topfiles_r .= "FROM details, attacks WHERE NOT split_part(details.text, '/', 4) = '' ";
    if ($tsquery != "") {
      $sql_topfiles_r .= " AND $tsquery ";
    }
    $sql_topfiles_r .= " AND attacks.sensorid IN (SELECT sensorid FROM groupmembers WHERE groupid = '$rgid') ";
    $sql_topfiles_r .= " AND type = 4  AND details.attackid = attacks.id ";
    $sql_topfiles_r .= " ) as sub ";
  $sql_topfiles_r .= "GROUP BY sub.file ORDER BY total DESC LIMIT $c_topfilenames";
  $debuginfo[] = $sql_topfiles_r;

  ###############################
  # OS
  ###############################

  $sql_topos_l = "SELECT DISTINCT sub.os, COUNT(sub.os) as total FROM ";
    $sql_topos_l .= "(SELECT split_part(system.name, ' ', 1) as os ";
    $sql_topos_l .= "FROM system, attacks WHERE 1 = 1 ";
    if ($tsquery != "") {
      $sql_topos_l .= " AND $tsquery ";
    }
    $sql_topos_l.= " AND attacks.source = system.ip_addr ";
    $sql_topos_l .= " AND attacks.sensorid IN (SELECT sensorid FROM groupmembers WHERE groupid = '$lgid') ";
    $sql_topos_l .= " ) as sub ";
  $sql_topos_l .= "GROUP BY sub.os ORDER BY total DESC LIMIT $c_topos";
  $debuginfo[] = $sql_topos_l;

  $sql_topos_r = "SELECT DISTINCT sub.os, COUNT(sub.os) as total FROM ";
    $sql_topos_r .= "(SELECT split_part(system.name, ' ', 1) as os ";
    $sql_topos_r .= "FROM system, attacks WHERE 1 = 1 ";
    if ($tsquery != "") {
      $sql_topos_r .= " AND $tsquery ";
    }
    $sql_topos_r .= " AND attacks.source = system.ip_addr ";
    $sql_topos_r .= " AND attacks.sensorid IN (SELECT sensorid FROM groupmembers WHERE groupid = '$rgid') ";
    $sql_topos_r .= " ) as sub ";
  $sql_topos_r .= "GROUP BY sub.os ORDER BY total DESC LIMIT $c_topos";
  $debuginfo[] = $sql_topos_r;

  ###############################
  # HTML stuff
  ###############################

  echo "<div class='all'>\n";
    echo "<div class='leftmed'>\n";
      echo "<div class='block'>\n";
        echo "<div class='dataBlock'>\n";
          echo "<div class='blockHeader'>$lname</div>\n";
          echo "<div class='blockContent'>\n";
            echo "<table class='datatable'>\n";
              echo "<tr>\n";
                echo "<th>" .$l['g_detconn']. "</th>\n";
                echo "<th>" .$l['g_total']. "</th>\n";
              echo "</tr>\n";
              $result = pg_query($pgconn, $sql_attacks_l);
              while ($row = pg_fetch_assoc($result)) {
                $sev = $row['severity'];
                $total = $row['total'];

                echo "<tr>\n";
                  echo "<td>" .$v_severity_ar[$sev]. "</td>\n";
                  echo "<td>$total</td>\n";
                echo "</tr>\n";
                if ($sev == 1) {
                  $result_type = pg_query($pgconn, $sql_atype_l);
                  while ($row_type = pg_fetch_assoc($result_type)) {
                    $atype = $row_type['atype'];
                    $total = $row_type['total'];
                    $desc = $v_severity_atype_ar[$atype];

                    echo "<tr>\n";
                      echo "<td class='indented'>$desc</td>\n";
                        echo "<td>" .nf($total). "</td>\n";
                    echo "</tr>\n";
                  }
                }
              }
            echo "</table>\n";
          echo "</div>\n"; #</blockContent>
          echo "<div class='blockFooter'></div>\n";
        echo "</div>\n"; #</dataBlock>
      echo "</div>\n"; #</block>
    echo "</div\n"; #</leftmed>

    echo "<div class='rightmed'>\n";
      echo "<div class='block'>\n";
        echo "<div class='dataBlock'>\n";
          echo "<div class='blockHeader'>$rname</div>\n";
          echo "<div class='blockContent'>\n";
            echo "<table class='datatable'>\n";
              echo "<tr>\n";
                echo "<th>" .$l['g_detconn']. "</th>\n";
                echo "<th>" .$l['g_total']. "</th>\n";
              echo "</tr>\n";
              $result = pg_query($pgconn, $sql_attacks_r);
              while ($row = pg_fetch_assoc($result)) {
                $sev = $row['severity'];
                $total = $row['total'];

                echo "<tr>\n";
                  echo "<td>" .$v_severity_ar[$sev]. "</td>\n";
                  echo "<td>$total</td>\n";
                echo "</tr>\n";
                if ($sev == 1) {
                  $result_type = pg_query($pgconn, $sql_atype_r);
                  while ($row_type = pg_fetch_assoc($result_type)) {
                    $atype = $row_type['atype'];
                    $total = $row_type['total'];
                    $desc = $v_severity_atype_ar[$atype];

                    echo "<tr>\n";
                      echo "<td class='indented'>$desc</td>\n";
                        echo "<td>" .nf($total). "</td>\n";
                    echo "</tr>\n";
                  }
                }
              }
            echo "</table>\n";
          echo "</div>\n"; #</blockContent>
          echo "<div class='blockFooter'></div>\n";
        echo "</div>\n"; #</dataBlock>
      echo "</div>\n"; #</block>
    echo "</div\n"; #</rightmed>
  echo "</div>\n"; #</all>

  echo "<div class='all'>\n";
    echo "<div class='leftmed'>\n";
      echo "<div class='block'>\n";
        echo "<div class='dataBlock'>\n";
          echo "<div class='blockContent'>\n";
            $result_topexp = pg_query($pgconn, $sql_topexp_l);
            echo "<table class='datatable'>\n";
              echo "<tr><td class='title' colspan='3'>" .$l['ra_top']. " $c_topexploits " .$l['g_exploits_l']. "</td></tr>\n";
              echo "<tr>\n";
                echo "<th width='5%'>#</th>\n";
                echo "<th width='75%'>" .$l['ra_expl']. "</th>\n";
                echo "<th width='20%'>" .$l['ra_total']. "</th>\n";
              echo "</tr>\n";
              $i=1;
              $grandtotal = 0;
              $exploit_ar = array();
              $exploitid_ar = array();
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
                    echo "<td>" . nf($val) . " (${perc}%)</td>\n";
                  echo "</tr>\n";
                  $i++;
                }
              }
            echo "</table>\n";
          echo "</div>\n"; #</blockContent>
          echo "<div class='blockFooter'></div>\n";
        echo "</div>\n"; #</dataBlock>
      echo "</div>\n"; #</block>
    echo "</div\n"; #</leftmed>

    echo "<div class='rightmed'>\n";
      echo "<div class='block'>\n";
        echo "<div class='dataBlock'>\n";
          echo "<div class='blockContent'>\n";
            $result_topexp = pg_query($pgconn, $sql_topexp_r);
            echo "<table class='datatable'>\n";
              echo "<tr><td class='title' colspan='3'>" .$l['ra_top']. " $c_topexploits " .$l['g_exploits_l']. "</td></tr>\n";
              echo "<tr>\n";
                echo "<th width='5%'>#</th>\n";
                echo "<th width='75%'>" .$l['ra_expl']. "</th>\n";
                echo "<th width='20%'>" .$l['ra_total']. "</th>\n";
              echo "</tr>\n";
              $i=1;
              $grandtotal = 0;
              $exploit_ar = array();
              $exploitid_ar = array();
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
                    echo "<td>" . nf($val) . " (${perc}%)</td>\n";
                  echo "</tr>\n";
                  $i++;
                }
              }
            echo "</table>\n";
          echo "</div>\n"; #</blockContent>
          echo "<div class='blockFooter'></div>\n";
        echo "</div>\n"; #</dataBlock>
      echo "</div>\n"; #</block>
    echo "</div\n"; #</rightmed>
  echo "</div>\n"; #</all>

  echo "<div class='all'>\n";
    echo "<div class='leftmed'>\n";
      echo "<div class='block'>\n";
        echo "<div class='dataBlock'>\n";
          echo "<div class='blockContent'>\n";
            $result_topports = pg_query($pgconn, $sql_topports_l);
            echo "<table class='datatable'>\n";
              echo "<tr><td class='title' colspan='4'>" .$l['ra_top']. " $c_topports " .$l['g_ports_l']. "</td></tr>\n";
              echo "<tr>\n";
                echo "<th width='5%'>#</th>\n";
                echo "<th width='15%'>" .$l['ra_port']. "</th>\n";
                echo "<th width='60%'>" .$l['ra_portdesc']. "</th>\n";
                echo "<th width='20%'>" .$l['ra_total']. "</th>\n";
              echo "</tr>\n";
              $i=1;
              $grandtotal = 0;
              $port_ar = array();
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
                    echo "<td>$val (${perc}%)</td>\n";
                  echo "</tr>\n";
                  $i++;
                }
              }
            echo "</table>\n";
          echo "</div>\n"; #</blockContent>
          echo "<div class='blockFooter'></div>\n";
        echo "</div>\n"; #</dataBlock>
      echo "</div>\n"; #</block>
    echo "</div\n"; #</leftmed>

    echo "<div class='rightmed'>\n";
      echo "<div class='block'>\n";
        echo "<div class='dataBlock'>\n";
          echo "<div class='blockContent'>\n";
            $result_topports = pg_query($pgconn, $sql_topports_r);
            echo "<table class='datatable'>\n";
              echo "<tr><td class='title' colspan='4'>" .$l['ra_top']. " $c_topports " .$l['g_ports_l']. "</td></tr>\n";
              echo "<tr>\n";
                echo "<th width='5%'>#</th>\n";
                echo "<th width='15%'>" .$l['ra_port']. "</th>\n";
                echo "<th width='60%'>" .$l['ra_portdesc']. "</th>\n";
                echo "<th width='20%'>" .$l['ra_total']. "</th>\n";
              echo "</tr>\n";
              $i=1;
              $grandtotal = 0;
              $port_ar = array();
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
                    echo "<td>$val (${perc}%)</td>\n";
                  echo "</tr>\n";
                  $i++;
                }
              }
            echo "</table>\n";
          echo "</div>\n"; #</blockContent>
          echo "<div class='blockFooter'></div>\n";
        echo "</div>\n"; #</dataBlock>
      echo "</div>\n"; #</block>
    echo "</div\n"; #</rightmed>
  echo "</div>\n"; #</all>

  echo "<div class='all'>\n";
    echo "<div class='leftmed'>\n";
      echo "<div class='block'>\n";
        echo "<div class='dataBlock'>\n";
          echo "<div class='blockContent'>\n";
            $result_topfiles = pg_query($pgconn, $sql_topfiles_l);

            echo "<table class='datatable'>\n";
              echo "<tr><td class='title' colspan='3'>" .$l['ra_top']. " $c_topfilenames " .$l['g_files_l']. "</td></tr>\n";
              echo "<tr>\n";
                echo "<th width='5%'>#</th>\n";
                echo "<th width='75%'>" .$l['ra_filename']. "</th>\n";
                echo "<th width='20%'>" .$l['ra_total']. "</th>\n";
              echo "</tr>\n";
              $i = 0;
              $grandtotal = 0;
              $file_ar = array();
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
                    echo "<td>$val (${perc}%)</td>\n";
                  echo "</tr>\n";
                }
              }
            echo "</table>\n";
          echo "</div>\n"; #</blockContent>
          echo "<div class='blockFooter'></div>\n";
        echo "</div>\n"; #</dataBlock>
      echo "</div>\n"; #</block>
    echo "</div\n"; #</leftmed>

    echo "<div class='rightmed'>\n";
      echo "<div class='block'>\n";
        echo "<div class='dataBlock'>\n";
          echo "<div class='blockContent'>\n";
            $result_topfiles = pg_query($pgconn, $sql_topfiles_r);

            echo "<table class='datatable'>\n";
              echo "<tr><td class='title' colspan='3'>" .$l['ra_top']. " $c_topfilenames " .$l['g_files_l']. "</td></tr>\n";
              echo "<tr>\n";
                echo "<th width='5%'>#</th>\n";
                echo "<th width='75%'>" .$l['ra_filename']. "</th>\n";
                echo "<th width='20%'>" .$l['ra_total']. "</th>\n";
              echo "</tr>\n";
              $i = 0;
              $grandtotal = 0;
              $file_ar = array();
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
                    echo "<td>$val (${perc}%)</td>\n";
                  echo "</tr>\n";
                }
              }
            echo "</table>\n";
          echo "</div>\n"; #</blockContent>
          echo "<div class='blockFooter'></div>\n";
        echo "</div>\n"; #</dataBlock>
      echo "</div>\n"; #</block>
    echo "</div\n"; #</rightmed>
  echo "</div>\n"; #</all>

/*
  echo "<div class='all'>\n";
    echo "<div class='leftmed'>\n";
      echo "<div class='block'>\n";
        echo "<div class='dataBlock'>\n";
          echo "<div class='blockContent'>\n";

          echo "</div>\n"; #</blockContent>
          echo "<div class='blockFooter'></div>\n";
        echo "</div>\n"; #</dataBlock>
      echo "</div>\n"; #</block>
    echo "</div\n"; #</leftmed>

    echo "<div class='rightmed'>\n";
      echo "<div class='block'>\n";
        echo "<div class='dataBlock'>\n";
          echo "<div class='blockContent'>\n";

          echo "</div>\n"; #</blockContent>
          echo "<div class='blockFooter'></div>\n";
        echo "</div>\n"; #</dataBlock>
      echo "</div>\n"; #</block>
    echo "</div\n"; #</rightmed>
  echo "</div>\n"; #</all>


  echo "<div class='all'>\n";
    echo "<div class='leftmed'>\n";
      echo "<div class='block'>\n";
        echo "<div class='dataBlock'>\n";
          echo "<div class='blockContent'>\n";

          echo "</div>\n"; #</blockContent>
          echo "<div class='blockFooter'></div>\n";
        echo "</div>\n"; #</dataBlock>
      echo "</div>\n"; #</block>
    echo "</div\n"; #</leftmed>

    echo "<div class='rightmed'>\n";
      echo "<div class='block'>\n";
        echo "<div class='dataBlock'>\n";
          echo "<div class='blockContent'>\n";

          echo "</div>\n"; #</blockContent>
          echo "<div class='blockFooter'></div>\n";
        echo "</div>\n"; #</dataBlock>
      echo "</div>\n"; #</block>
    echo "</div\n"; #</rightmed>
  echo "</div>\n"; #</all>
*/
}

debug_sql();
?>
<?php footer(); ?>

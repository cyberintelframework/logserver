<?php

######################################
# Setting up criteria array
######################################

foreach ($clean as $search => $searchval) {
    if ($searchval != "-1") {
        $crit[$search] = $searchval;
    }
}

if (isset($tainted['sensorid'])) {
    if (is_array($tainted['sensorid'])) {
        $sensorid = -1;
    } elseif ($tainted['sensorid'] > 0) {
        $sensorid = $tainted['sensorid'];
    }
    $crit['sensorid'] = $tainted['sensorid'];
}

#echo "<pre>\n";
#print_r($crit);
#echo "</pre>\n";

####################
# Sensor ID's
####################
if ($sensorid > 0) {
  add_to_sql("sensors", "table");
  add_to_sql("sensors.id = '" .$crit['sensorid']. "'", "where");
} elseif ($sensorid == -1) {
  # multiple sensors
  add_to_sql("sensors", "table");

  # Removing 0 values
  $crit['sensorid'] = array_diff($crit['sensorid'], array(0));

  $count = count($crit['sensorid']);
  if ($count != 0) {
    $tmp_where = "sensors.id IN (";
    for ($i = 0; $i < $count; $i++) {
      if ($i != ($count - 1)) {
        $tmp_where .= $crit['sensorid'][$i]. ", ";
      } else {
        $tmp_where .= $crit['sensorid'][$i];
      }
    }
    $tmp_where .= ")";
    add_to_sql($tmp_where, "where");
  }
}

####################
# Group
####################
if (isset($crit['gid'])) {
  $gid = $crit['gid'];
  $sql_gid = "SELECT sensorid FROM groupmembers WHERE groupid = '$gid'";
  $result_gid = pg_query($pgconn, $sql_gid);
  $i = 0;
  $tmp_where = "sensors.id IN (";
  $num_gid = pg_num_rows($result_gid);
  while ($row_gid = pg_fetch_assoc($result_gid)) {
    $i++;
    $group_sid = $row_gid['sensorid'];
    if ($i != $num_gid) {
      $tmp_where .= "$group_sid, ";
    } else {
      $tmp_where .= "$group_sid";
    }
  }
  $tmp_where .= ")";
  add_to_sql($tmp_where, "where");
}

####################
# Source IP address
####################
if (isset($crit['sourcemac'])) {
  $source_mac = $crit['sourcemac'];
  add_to_sql("attacks", "table");
  add_to_sql("attacks.src_mac = '$source_mac'", "where");
}
if (isset($crit['source'])) {
  $source_ip = $crit['source'];
  add_to_sql("attacks", "table");
  add_to_sql("attacks.source <<= '$source_ip'", "where");
}
if (isset($crit['ownsource'])) {
  $ownsource = $crit['ownsource'];
  add_to_sql("attacks", "table");
  add_to_sql("attacks.source <<= '$ownsource'", "where");
}
if (isset($crit['sport'])) {
  $sport = $crit['sport'];
  if ($sport != 0) {
    add_to_sql("attacks", "table");
    add_to_sql("attacks.sport = '$sport'", "where");
  }
}

####################
# Destination IP address
####################
if (isset($crit['destmac'])) {
  $dest_mac = $crit['destmac'];
  add_to_sql("attacks", "table");
  add_to_sql("attacks.dst_mac = '$dest_mac'", "where");
}
if (isset($crit['dest'])) {
  $destination_ip = $crit['dest'];
  add_to_sql("attacks", "table");
  add_to_sql("attacks.dest <<= '$destination_ip'", "where");
}
if (isset($crit['dport'])) {
  $dport = $crit['dport'];
  if ($dport != 0) {
    add_to_sql("attacks", "table");
    add_to_sql("attacks.dport = '$dport'", "where");
  }
}

####################
# Start timestamp
####################
add_to_sql("attacks", "table");
if (!isset($crit['attackid'])) {
  add_to_sql("attacks.timestamp >= '$from'", "where");
}

####################
# End timestamp
####################
add_to_sql("attacks", "table");
if (!isset($crit['attackid'])) {
  add_to_sql("attacks.timestamp <= '$to'", "where");
}

####################
# Severity
####################
if (isset($crit['sev'])) {
  add_to_sql("attacks", "table");
  add_to_sql("attacks.severity = '" .$crit['sev']. "'", "where");
}

####################
# Severity type
####################
if (isset($crit['sevtype'])) {
  add_to_sql("attacks", "table");
  add_to_sql("attacks.atype = '" .$crit['sevtype']. "'", "where");
}

####################
# All exploits
####################
if (isset($crit['allexploits'])) {
  add_to_sql("attacks", "table");
  add_to_sql("details", "table");
  add_to_sql("attacks.id = details.attackid", "where");
  add_to_sql("details.type IN (1, 80)", "where");
}

####################
# Type of attack
####################
if ($crit['attack'] > 0) {
  add_to_sql("details", "table");
  add_to_sql("stats_dialogue", "table");
  add_to_sql("attacks.id = details.attackid", "where");
  add_to_sql("details.text = stats_dialogue.name", "where");
  add_to_sql("stats_dialogue.id = '" .$crit['attack']. "'", "where");
}

####################
# Type of virus
####################
if (isset($crit['virustxt'])) {
  add_to_sql("binaries", "table");
  add_to_sql("details", "table");
  add_to_sql("stats_virus", "table");
  add_to_sql("uniq_binaries", "table");
  add_to_sql("attacks.id = details.attackid", "where");
  add_to_sql("details.type = 8", "where");
  add_to_sql("details.text = uniq_binaries.name", "where");
  add_to_sql("uniq_binaries.id = binaries.bin", "where");
  add_to_sql("binaries.info = stats_virus.id", "where");
  add_to_sql("stats_virus.name LIKE '" .$crit['virustxt']. "'", "where");
  add_to_sql("details.text", "select");
}

####################
# Filename
####################
if (isset($crit['filename'])) {
  add_to_sql("details", "table");
  add_to_sql("attacks.id = details.attackid", "where");
  add_to_sql("details.type = 4", "where");
  add_to_sql("details.text LIKE '%" .$crit['filename']. "'", "where");
  add_to_sql("details.text", "select");
}

####################
# Binary Name
####################
if (isset($crit['binname'])) {
  add_to_sql("details", "table");
  add_to_sql("details.type = 8", "where");
  add_to_sql("attacks.id = details.attackid", "where");
  add_to_sql("details.text LIKE '" .$crit['binname']. "'", "where");
}

####################
# Binary ID
####################
if (isset($crit['binid'])) {
  add_to_sql("details", "table");
  add_to_sql("uniq_binaries", "table");
  add_to_sql("attacks.id = details.attackid", "where");
  add_to_sql("details.type = 8", "where");
  add_to_sql("details.text = uniq_binaries.name", "where");
  add_to_sql("uniq_binaries.id = " .$crit['binid'], "where");
}

####################
# Ranges
####################
if (!isset($crit['gid'])) {
  if ($crit['sourcechoice'] == 3 && $crit['ownsource'] == "") {
    add_to_sql(gen_org_sql(1), "where");
  } else {
    add_to_sql(gen_org_sql(), "where");
  }
}

####################
# SSH has command
####################
if (isset($crit['sshhascommand']) && $crit['sev'] == 1 && $crit['sevtype'] == 7) {
#  $crit['sev'] = 1;
#  $crit['sevtype'] = 7;

  # 2 = yes
  # 1 = no
  # 0 = both
  $subquery = "SELECT DISTINCT attacks.id FROM attacks, ssh_command WHERE attacks.timestamp < $to AND attacks.timestamp > $from AND atype = 7";
  $subquery .= " AND ssh_command.attackid = attacks.id";
  if ($crit['sshhascommand'] == 2) {
    add_to_sql("attacks.id IN ($subquery)", "where");
  } elseif ($crit['sshhascommand'] == 1) {
    add_to_sql("NOT attacks.id IN ($subquery)", "where");
  }
}

####################
# SSH successful login
####################
if (isset($crit['sshlogin']) && $crit['sev'] == 1 && $crit['sevtype'] == 7) {
  # 2 = yes
  # 1 = no
  # 0 = both
  if ($crit['sshlogin'] == 2) {
#    $crit['sev'] = 1;
#    $crit['sevtype'] = 7;

    add_to_sql("ssh_logins", "table");
    add_to_sql("attacks.atype = 7", "where");
    add_to_sql("attacks.id = ssh_logins.attackid", "where");
    add_to_sql("ssh_logins.type = TRUE", "where");
  } elseif ($crit['sshlogin'] == 1) {
#    $crit['sev'] = 1;
#    $crit['sevtype'] = 7;

    add_to_sql("ssh_logins", "table");
    add_to_sql("attacks.atype = 7", "where");
    add_to_sql("attacks.id = ssh_logins.attackid", "where");
    add_to_sql("ssh_logins.type = FALSE", "where");
  }
}

####################
# SSH version
####################
if (isset($crit['sshversion']) && $crit['sev'] == 1 && $crit['sevtype'] == 7) {
#  $crit['sev'] = 1;
#  $crit['sevtype'] = 7;

  add_to_sql("ssh_version", "table");
  add_to_sql("uniq_sshversion", "table");
  add_to_sql("attacks.atype = 7", "where");
  add_to_sql("attacks.id = ssh_version.attackid", "where");
  if (strpos($crit['sshversion'], "%") === true) {
      add_to_sql("uniq_sshversion.version LIKE '" .$crit['sshversion']. "'", "where");
  } else {
      add_to_sql("uniq_sshversion.version = '" .$crit['sshversion']. "'", "where");
  }
  add_to_sql("uniq_sshversion.id = ssh_version.version", "where");
} elseif (isset($crit['sshversionid'])) {
  $crit['sev'] = 1;
  $crit['sevtype'] = 7;

  add_to_sql("ssh_version", "table");
  add_to_sql("attacks.atype = 7", "where");
  add_to_sql("attacks.id = ssh_version.attackid", "where");
  add_to_sql("ssh_version.version = '" .$crit['sshversionid']. "'", "where");
}

####################
# SSH command
####################
if (isset($crit['sshcommand']) && $crit['sev'] == 1 && $crit['sevtype'] == 7) {
#  $crit['sev'] = 1;
#  $crit['sevtype'] = 7;

  add_to_sql("ssh_command", "table");
  add_to_sql("attacks.atype = 7", "where");
  add_to_sql("attacks.id = ssh_command.attackid", "where");
  if (strpos($crit['sshcommand'], "%") === true) {
      add_to_sql("ssh_command.command LIKE '" .$crit['sshcommand']. "'", "where");
  } else {
      add_to_sql("ssh_command.command = '" .$crit['sshcommand']. "'", "where");
  }
}

####################
# SSH user
####################
if (isset($crit['sshuser']) && $crit['sev'] == 1 && $crit['sevtype'] == 7) {
#  $crit['sev'] = 1;
#  $crit['sevtype'] = 7;

  add_to_sql("ssh_logins", "table");
  add_to_sql("attacks.atype = 7", "where");
  add_to_sql("attacks.id = ssh_logins.attackid", "where");
  add_to_sql("ssh_logins.sshuser = '" .$crit['sshuser']. "'", "where");
}

####################
# SSH pass
####################
if (isset($crit['sshpass']) && $crit['sev'] == 1 && $crit['sevtype'] == 7) {
#  $crit['sev'] = 1;
#  $crit['sevtype'] = 7;

  add_to_sql("ssh_logins", "table");
  add_to_sql("attacks.atype = 7", "where");
  add_to_sql("attacks.id = ssh_logins.attackid", "where");
  add_to_sql("ssh_logins.sshpass = '" .$crit['sshpass']. "'", "where");
}

####################
# Attack ID
####################
if (isset($crit['attackid'])) {
  add_to_sql("attacks.id = ". $crit['attackid'], "where");
}

####################
# General query stuff
####################
add_to_sql("attacks", "table");
add_to_sql("DISTINCT attacks.id as daid", "select");
add_to_sql("attacks.*", "select");
add_to_sql("sensors.keyname", "select");
add_to_sql("sensors.vlanid", "select");
add_to_sql("sensors.label", "select");
add_to_sql("sensors", "table");
add_to_sql("attacks.sensorid = sensors.id", "where");

if ($filter_ip == 1) {
  # IP Exclusion stuff
  add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $q_org)", "where");
}
if ($filter_mac == 1) {
  # MAC Exclusion stuff
  add_to_sql("(attacks.src_mac IS NULL OR NOT attacks.src_mac IN (SELECT mac FROM arp_excl))", "where");
}

prepare_sql();

?>


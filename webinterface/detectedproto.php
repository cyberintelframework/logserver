<?php $tab="2.7"; $pagetitle="Detected Protocols"; include("menu.php"); contentHeader(0,0); ?>
<?php

####################################
# SURFids 3.00                     #
# Changeset 001                    #
# 03-03-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 001 Added language support
#############################################

# Checking access
if ($s_access_sensor < 2) {
  $m = 101;
  geterror($m);
  footer();
  pg_close($pgconn);
  exit;
}

# Retrieving posted variables from $_GET
$allowed_get = array(
                "int_sid",
		"int_m"
);
$check = extractvars($_GET, $allowed_get);
debug_input();
$err = 0;

if (!isset($clean['sid'])) {
  $err = 1;
} else {
  $sid = $clean['sid'];
}

if (isset($clean['m'])) {
  $m = $clean['m'];
  geterror($m);
}

if ($err == 0) {
  echo "<div class='leftsmall'>\n";
    echo "<div class='block'>\n";
      echo "<div class='actionBlock'>\n";
        echo "<div class='blockHeader'>" .$l['g_actions']. "</div>\n";
        echo "<div class='blockContent'>\n";
          echo "<a href='detectedproto_clr.php?int_org=$q_org&md5_hash=$s_hash&int_sid=$sid' onclick=\"javascript: return confirm('" .$l['dp_confirm_del']. "');\">";
          echo $l['dp_clear_det_prot']. "</a>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</actionBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</leftsmall>
}

echo "<div class='all'>\n";
echo "<div class='left'>\n";
  echo "<div class='block'>\n";
    echo "<div class='dataBlock'>\n";
      echo "<div class='blockHeader'>";
        echo "<div class='blockHeaderLeft'>" .$l['dp_detected']. " " .printhelp(15). "</div>\n";
        echo "<div class='blockHeaderRight'>";
          echo "<form method='get'>\n";
            if ($q_org == 0) {
              $sql_sensors = "SELECT sensors.id, keyname, vlanid, arp, status, label, organisations.organisation ";
              $sql_sensors .= " FROM sensors, organisations WHERE sensors.organisation = organisations.id ORDER BY tapip, keyname";
            } else {
              $sql_sensors = "SELECT id, keyname, vlanid, arp, status, label FROM sensors WHERE organisation = $q_org ORDER BY tapip, keyname";
            }
            $debuginfo[] = $sql_sensors;
            $result_sensors = pg_query($pgconn, $sql_sensors);
            echo "<select class='smallselect' name='int_sid' onChange='javascript: this.form.submit();'>\n";
              echo printOption("", "", $sid);
              while ($row = pg_fetch_assoc($result_sensors)) {
                $id = $row['id'];
                $keyname = $row['keyname'];
                $label = $row['label'];
		$vlanid = $row['vlanid'];
                $sensor = sensorname($keyname, $vlanid);
                if ($label != "") $sensor = $label;
                $status = $row['status'];
                $org = $row['organisation'];
                if ($org != "") {
                  echo printOption($id, "$sensor - $org", $sid, $status);
                } else {
                  echo printOption($id, $sensor, $sid, $status);
                }
              }
            echo "</select>\n";
          echo "</form>\n";
        echo "</div>\n";
      echo "</div>\n";
      echo "<div class='blockContent'>\n";
        if ($err == 0) {
          echo "<table class='datatable'>\n";
            echo "<tr>\n";
              echo "<th width='150'>" .$l['dp_parent']. "</th>\n";
              echo "<th width='150'>" .$l['dp_type_number']. "</th>\n";
              echo "<th width='300'>" .$l['dp_type']. "</th>\n";
            echo "</tr>\n";

            $sql_protos = "SELECT parent, number, protocol FROM sniff_protos WHERE sensorid = '$sid' ORDER BY parent, number";
            $debuginfo[] = $sql_protos;
            $result_protos = pg_query($pgconn, $sql_protos);

            while ($row_protos = pg_fetch_assoc($result_protos)) {
              $head = $row_protos['parent'];
              $number = $row_protos['number'];
              $proto = $row_protos['protocol'];

              echo "<tr>\n";
                echo "<td>" .$v_proto_types[$head]. "</td>\n";
                echo "<td>$number</td>\n";
                echo "<td>$proto</td>\n";
              echo "</tr>\n";
            }
          echo "</table>\n";
        } else {
          echo "<form method='get'>\n";
          echo "<table>";
            echo "<tr>";
              echo "<td><span class='warning'>" .$l['g_select_sensor']. "</span></td>\n";
              echo "<td>\n";
                $select_size = 8;
                if ($q_org == 0) {
                  $sql_sensors = "SELECT sensors.id, keyname, vlanid, arp, status, label, organisations.organisation ";
                  $sql_sensors .= " FROM sensors, organisations WHERE sensors.organisation = organisations.id ORDER BY tapip, keyname";
                } else {
                  $sql_sensors = "SELECT id, keyname, vlanid, arp, status, label FROM sensors WHERE organisation = $q_org ORDER BY tapip, keyname";
                }
                $debuginfo[] = $sql_sensors;
                $result_sensors = pg_query($pgconn, $sql_sensors);
                echo "<select name='int_sid' size='$select_size' class='smallselect' onChange='javascript: this.form.submit();'>\n";
                  while ($row = pg_fetch_assoc($result_sensors)) {
                    $id = $row['id'];
                    $keyname = $row['keyname'];
                    $label = $row['label'];
                    $vlanid = $row['vlanid'];
                    $sensor = sensorname($keyname, $vlanid);
                    if ($label != "") $sensor = $label;
                    $status = $row['status'];
                    $org = $row['organisation'];
                    if ($org != "") {
                      echo printOption($id, "$sensor - $org", $sid, $status);
                    } else {
                      echo printOption($id, $sensor, $sid, $status);
                    }
                  }
                echo "</select>\n";
              echo "</td>\n";
            echo "</tr>\n";
          echo "</table>\n";
          echo "</form>\n";
        }
      echo "</div>\n"; #</blockContent>
      echo "<div class='blockFooter'></div>\n";
    echo "</div>\n"; #</dataBlock>
  echo "</div>\n"; #</block>
echo "</div>\n"; #</left>
echo "</div>\n"; #</all>

pg_close($pgconn);
debug_sql();
?>
<?php footer(); ?>

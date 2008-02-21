<?php

####################################
# SURFnet IDS                      #
# Version 2.10.01                  #
# 15-02-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 2.10.01 Initial release
#############################################

?>
<script type='text/javascript'>
function sh_sensorblock(dir) {
 si = $('#si').val();
 lastdiv = $('#lastdiv').val();
 if (dir == "n") {
    si++;
 } 
 if (dir == "p") {
    si--; 
 }
 if (si <= lastdiv && si >= 1) {
   $('#si').val(si);
   var i = 0;
   $('.blockContent').each(function(item){
    if (i == si) {
      $("#sensorstat" + i).show();
    } else {
      $("#sensorstat" + i).hide();
    }
    i++;
   })
 }
}
</script>
<?php

add_to_sql("sensors.*", "select");
add_to_sql("status ASC", "order");
add_to_sql("vlanid", "order");

if ($q_org != 0) {
  add_to_sql("organisation = '$q_org'", "where");
} else {
  add_to_sql("organisations", "table");
  add_to_sql("organisations.organisation as org", "select");
  add_to_sql("sensors.organisation = organisations.id", "where");
}

add_to_sql("sensors", "table");
prepare_sql();

$sql_sensors = "SELECT $sql_select ";
$sql_sensors .= " FROM $sql_from ";
$sql_sensors .= " $sql_where ";
$sql_sensors .= " ORDER BY $sql_order ";

$debuginfo[] = $sql_sensors;
$result_sensors = pg_query($pgconn, $sql_sensors);
$num_rows = pg_num_rows($result_sensors);

$nav = "";
$per_page = 5;
$divcount = 0;
$page = 1;
$lastdiv = ceil($num_rows / $per_page);

if ($num_rows > $per_page) {  
 $nav .= "<a onclick='javascript: sh_sensorblock(\"p\");'> <img src='images/selector_arrow_left.gif'></a>\n";
 $nav .= "<a onclick='javascript: sh_sensorblock(\"n\");'> <img src='images/selector_arrow_right.gif'></a>\n";
}
echo "<input type=hidden value=1 id=si />\n";
echo "<input type=hidden value='$lastdiv' id=lastdiv />\n";
echo "<div class='block'>\n";
  echo "<div class='dataBlock'>\n";
    echo "<div class='blockHeader'>";
      echo "<div class='blockHeaderLeft'>" .$l['g_sensor']. " " .$l['g_status']. "</div>\n";
      echo "<div class='blockHeaderRight'>\n";
        echo "<div class='searchnav'>$nav</div>\n";
      echo "</div>\n";
    echo "</div>\n";
    echo "<div class='blockContent' id='sensorstat$page'>\n";
      echo "<table class='datatable' width='100%'>\n";
        echo "<tr>\n";
          echo "<th>" .$l['g_sensor']. "</th>\n";
          echo "<th>" .$l['sd_devip']. "</th>\n";
          echo "<th>" .$l['sd_uptime']. "</th>\n";
          echo "<th>" .$l['g_status']. "</th>\n";
        echo "</tr>\n";

        while ($row = pg_fetch_assoc($result_sensors)) {
          $next_div = $divcount % $per_page;
          if ($divcount != 0 && $next_div == 0) { 
            $page++;
            echo "</table>\n";
              echo "</div>\n"; #</blockContent>
              echo "<div class='blockContent' id='sensorstat$page' style=\"display: none;\">\n";
                echo "<table class='datatable' width='100%'>\n";
                  echo "<tr>\n";
                    echo "<th>" .$l['g_sensor']. "</th>\n";
                    echo "<th>" .$l['sd_devip']. "</th>\n";
                    echo "<th>" .$l['sd_uptime']. "</th>\n";
                    echo "<th>" .$l['g_status']. "</th>\n";
                  echo "</tr>\n";
          }
          $now = time();
          $sid = $row['id'];
          $keyname = $row['keyname'];
          $label = $row['label'];
          $tap = $row['tap'];
          $tapip = censorip($row['tapip']);
          $start = $row['laststart'];
          $action = $row['action'];
          $ssh = $row['ssh'];
          $status = $row['status'];
          $uptime = date("U") - $start;
          $uptime_text = sec_to_string($uptime);
          $netconf = $row['netconf'];
          $vlanid = $row['vlanid'];
          $arp = $row['arp'];
          $sensor = sensorname($keyname, $vlanid);
          $lastupdate = $row['lastupdate'];
          $diffupdate = 0;
          if ($lastupdate != "") {
            $diffupdate = $now - $lastupdate;
            $lastupdate = date("d-m-Y H:i:s", $lastupdate);
          }
          if ($q_org == 0) {
            $org = $row['org'];
          }

          # Setting status correctly
          if (($netconf == "vlans" || $netconf == "static") && (empty($tapip) || $tapip == "")) {
            $status = 5;
          } elseif ($diffupdate <= 3600 && $status == 1 && !empty($tap)) {
            $status = 1;
          } elseif ($diffupdate > 3600 && $status == 1) {
            $status = 4;
          } elseif ($status == 1 && empty($tap)) {
            $status = 6;
          }

          echo "<tr>\n";
            if ($q_org != 0 ) {
              if ($label == "") $sensorlabel = $sensor; 
              else $sensorlabel = $label; 
            } else {        
              if ($label == "") $sensorlabel = "$sensor - $org"; 
              else $sensorlabel = "$label - $org";    
            }
            echo "<td><a href='sensordetails.php?int_sid=$sid'>$sensorlabel</a></td>\n";
            # Tap IP address
            if (empty($tapip)) {
                echo "<td></td>\n";
            } else {
                echo "<td>$tapip</td>\n";
            }
            echo "<td>$uptime_text<input type='hidden' name='js_uptime' value='$uptime' /></td>\n";
            echo "<td>";
              echo "<div class='sensorstatus'>";
                echo "<div class='" .$v_sensorstatus_ar[$status]["class"]. "'>";
                  echo "<div class='sensorstatustext'>" .$v_sensorstatus_ar[$status]["text"]. "</div>";
                echo "</div>";
              echo "</div>";
            echo "</td>\n";
          echo "</tr>\n";
          $divcount++;
        }
      echo "</table>\n";
     echo "</div>\n"; #</blockContent>
    echo "<div class='blockFooter'></div>\n";
  echo "</div>\n"; #</dataBlock>
echo "</div>\n"; #</block>

reset_sql();
?>

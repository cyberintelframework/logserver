<?php

####################################
# SURFids 3.00                     #
# Changeset 003                    #
# 20-10-2009                       #
# Kees Trippelvitz                 #
####################################

#############################################
# Changelog:
# 003 Fixed bug with multiple GeoIP includes
# 002 Fixed arsort with empty array
# 001 Initial release
#############################################

if ($c_geoip_enable == 1) {
  include_once '../include/' .$c_geoip_module;
  $gi = geoip_open("../include/" .$c_geoip_data, GEOIP_STANDARD);
  $geo_error = 0;

  $tsquery = "timestamp >= $from AND timestamp <= $to";

  add_to_sql("attacks", "table");
  add_to_sql("$tsquery", "where");
  add_to_sql("attacks.severity = 1", "where");
  if ($q_org != 0) {
    add_to_sql("sensors", "table");
    add_to_sql("sensors.id = attacks.sensorid", "where");
    add_to_sql(gen_org_sql(), "where");
  }
  add_to_sql("DISTINCT attacks.source", "select");

  # IP Exclusion stuff
  add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $q_org)", "where");
  # MAC Exclusion stuff
  add_to_sql("(attacks.src_mac IS NULL OR NOT attacks.src_mac IN (SELECT mac FROM arp_excl))", "where");

  prepare_sql();

  $sql = "SELECT $sql_select ";
  $sql .= " FROM $sql_from ";
  $sql .= " $sql_where ";
  $debuginfo[] = $sql;
  $result = pg_query($pgconn, $sql);
  $num = pg_num_rows($result);

  while ($row = pg_fetch_assoc($result)) {
    $source = $row['source'];

    # GEO IP stuff
    $record = geoip_record_by_addr($gi, $source);
    $countrycode = strtolower($record->country_code);

    $country_array[$countrycode]++;
    $name_array[$countrycode] = $record->country_name;
  }
} else {
  $geo_error = 1;
}
if (count($country_array) > 0) {
    arsort($country_array, SORT_NUMERIC);
}

echo "<div class='block'>\n";
  echo "<div class='dataBlock'>\n";
    echo "<div class='blockHeader'>" .$l['mod_countries']. "</div>\n";
    echo "<div class='blockContent'>\n";
      if ($geo_error == 1) {
        echo "<font class='warning'>" .$l['g_nofound']. "</font>\n";
      } else {
        if ($num > 0) {
          echo "<table class='datatable'>\n";
            echo "<tr>\n";
              echo "<th width='80%'>" .$l['g_country']. " " .printhelp(20,20). "</td>\n";
              echo "<th width='20%'>" .$l['g_stats']. "</td>\n";
            echo "</tr>\n";
            $i = 0;
            foreach ($country_array as $countrycode => $count) {
              if ($i == 10) break;
              if ($countrycode == "") {
                next;
              } else {
                echo "<tr>\n";
                  echo "<td>";
                    $cimg = "$c_surfidsdir/webinterface/images/worldflags/flag_" .$countrycode. ".gif";
                    $country = $name_array[$countrycode];
                    if (file_exists($cimg)) {
                      echo "<img class='flag' src='images/worldflags/flag_" .$countrycode. ".gif' />&nbsp; $country";
                    } else {
                      echo "<img class='flag' src='images/worldflags/flag.gif' />&nbsp; $country";
                    }
                  echo "</td>\n";
                  echo "<td>$count</td>\n";
                echo "</tr>\n";
                $i++;
              }
            } 
          echo "</table>\n";
        } else {
          echo "<font class='warning'>" .$l['g_nofound']. "</font>\n";
        }
      }
    echo "</div>\n"; #</blockContent>
    echo "<div class='blockFooter'></div>\n";
  echo "</div>\n"; #</dataBlock>
echo "</div>\n"; #</block>

reset_sql();
?>

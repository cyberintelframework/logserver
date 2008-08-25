<?php $tab="4.6"; $pagetitle="Config Info"; include("menu.php"); contentHeader(0,0); ?>
<?php

####################################
# SURFids 2.10.00                  #
# Changeset 003                    #
# 07-08-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 003 Added jquery_version + cookie stuff
# 002 Added utc_time
# 001 Added debug_sql_analyze
#############################################

# Checking access
if ($s_admin != 1) {
  geterror(101);
  footer();
  pg_close($pgconn);
  exit;
}

echo "<div class='leftmed'>\n";
  echo "<div class='block'>\n";
    echo "<div class='dataBlock'>\n";
      echo "<div class='blockHeader'>" .$l['sc_config']. "</div>\n";
      echo "<div class='blockContent'>\n";
        echo "<table class='multipletable'>\n";
          echo "<tr>\n";
            echo "<th colspan='2'>" .$l['sc_global']. "</th>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td width='30%'>c_surfidsdir</td>\n";
            echo "<td width='70%'>$c_surfidsdir</td>\n";
          echo "</tr>\n";
        echo "</table>\n";

        echo "<table class='multipletable'>\n";
          echo "<tr>\n";
            echo "<th colspan='2'>" .$l['sc_webconfig']. "</th>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td width='30%'>c_web_port</td>\n";
            echo "<td width='70%'>$c_web_port</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_showhelp</td>\n";
            echo "<td>" .printled($c_showhelp). "</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_download_binaries</td>\n";
            echo "<td>" .printled($c_download_binaries). "</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_surfnet_funcs</td>\n";
            echo "<td>" .printled($c_surfnet_funcs). "</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_startdayofweek</td>\n";
            echo "<td>$c_startdayofweek</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_selview</td>\n";
            echo "<td>$c_selview</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_footer_address</td>\n";
            echo "<td>$c_footer_address</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_language</td>\n";
            echo "<td>$c_language</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_minified_enable</td>\n";
            echo "<td>" .printled($c_minified_enable). "</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_jquery_version</td>\n";
            echo "<td>$c_jquery_version</td>\n";
          echo "</tr>\n";
        echo "</table>\n";

        echo "<table class='multipletable'>\n";
          echo "<tr>\n";
            echo "<th colspan='2'>" .$l['sc_session']. "</th>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td width='30%'>c_login_method</td>\n";
            echo "<td width='70%'>$c_login_method</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_chksession_ip</td>\n";
            echo "<td>" .printled($c_chksession_ip). "</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_chksession_ua</td>\n";
            echo "<td>" .printled($c_chksession_ua). "</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_session_timeout</td>\n";
            echo "<td>$c_session_timeout</td>\n";
          echo "</tr>\n";
        echo "</table>\n";

        echo "<table class='multipletable'>\n";
          echo "<tr>\n";
            echo "<th colspan='2'>" .$l['sc_debug']. "</th>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td width='30%'>c_debug_sql</td>\n";
            echo "<td width='70%'>" .printled($c_debug_sql). "</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_debug_sql_analyze</td>\n";
            echo "<td>" .printled($c_debug_sql_analyze). "</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_debug_input</td>\n";
            echo "<td>" .printled($c_debug_input). "</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_allow_global_debug</td>\n";
            echo "<td>" .printled($c_allow_global_debug). "</td>\n";
          echo "</tr>\n";
        echo "</table>\n";

        echo "<table class='multipletable'>\n";
          echo "<tr>\n";
            echo "<th colspan='2'>" .$l['sc_search']. "</th>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td width='30%'>c_censor_ip</td>\n";
            echo "<td width='70%'>$c_censor_ip</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_censor_word</td>\n";
            echo "<td>$c_censor_word</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_searchtime</td>\n";
            echo "<td>" .printled($c_searchtime). "</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_search_cache</td>\n";
            echo "<td>" .printled($c_search_cache). "</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_autocomplete</td>\n";
            echo "<td>" .printled($c_autocomplete). "</td>\n";
          echo "</tr>\n";
        echo "</table>\n";

        echo "<table class='multipletable'>\n";
          echo "<tr>\n";
            echo "<th colspan='2'>" .$l['sc_cookie']. "</th>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td width='30%'>c_cookie_name</td>\n";
            echo "<td width='70%'>$c_cookie_name</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_cookie_domain</td>\n";
            echo "<td>$c_cookie_domain</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_cookie_path</td>\n";
            echo "<td>$c_cookie_path</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_cookie_expiry</td>\n";
            echo "<td>$c_cookie_expiry</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_cookie_https</td>\n";
            echo "<td>" .printled($c_cookie_https) ."</td>\n";
          echo "</tr>\n";
        echo "</table>\n";

      echo "</div>\n"; #</blockContent>
      echo "<div class='blockFooter'></div>\n";
    echo "</div>\n"; #</dataBlock>
  echo "</div>\n"; #</block>
echo "</div>\n"; #</leftmed>


echo "<div class='rightmed'>\n";
  echo "<div class='block'>\n";
    echo "<div class='dataBlock'>\n";
      echo "<div class='blockHeader'>" .$l['sc_config']. "</div>\n";
      echo "<div class='blockContent'>\n";

        echo "<table class='multipletable'>\n";
          echo "<tr>\n";
            echo "<th colspan='2'>" .$l['sc_finger']. "</th>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td width='30%'>c_enable_pof</td>\n";
            echo "<td width='70%'>" .printled($c_enable_pof). "</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_phplot</td>\n";
            echo "<td>$c_phplot</td>\n";
          echo "</tr>\n";
        echo "</table>\n";

        echo "<table class='multipletable'>\n";
          echo "<tr>\n";
            echo "<th colspan='2'>" .$l['sc_geoip']. "</th>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td width='30%'>c_geoip_enable</td>\n";
            echo "<td width='70%'>" .printled($c_geoip_enable). "</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_geoip_module</td>\n";
            echo "<td>$c_geoip_module</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_geoip_data</td>\n";
            echo "<td>$c_geoip_data</td>\n";
          echo "</tr>\n";
        echo "</table>\n";

        echo "<table class='multipletable'>\n";
          echo "<tr>\n";
            echo "<th colspan='2'>" .$l['sc_rank']. "</th>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td width='30%'>c_topexploits</td>\n";
            echo "<td width='70%'>$c_topexploits</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_topsensors</td>\n";
            echo "<td>$c_topsensors</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_toporgs</td>\n";
            echo "<td>$c_toporgs</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_topports</td>\n";
            echo "<td>$c_topports</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_topfilenames</td>\n";
            echo "<td>$c_topfilenames</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_topsourceips</td>\n";
            echo "<td>$c_topsourceips</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_topprotocols</td>\n";
            echo "<td>$c_topprotocols</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_topos</td>\n";
            echo "<td>$c_topos</td>\n";
          echo "</tr>\n";
        echo "</table>\n";

        echo "<table class='multipletable'>\n";
          echo "<tr>\n";
            echo "<th colspan='2'>" .$l['sc_maillog']. "</th>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td width='30%'>c_from_address</td>\n";
            echo "<td width='70%'>$c_from_address</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_webinterface_prefix</td>\n";
            echo "<td>$c_webinterface_prefix</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_subject_prefix</td>\n";
            echo "<td>$c_subject_prefix</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_utc_time</td>\n";
            echo "<td>$c_utc_time</td>\n";
          echo "</tr>\n";
        echo "</table>\n";

        echo "<table class='multipletable'>\n";
          echo "<tr>\n";
            echo "<th colspan='2'>" .$l['sc_sandbox']. "</th>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td width='30%'>c_mail_mailhost</td>\n";
            echo "<td width='70%'>$c_mail_mailhost</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_mail_port</td>\n";
            echo "<td>$c_mail_port</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_mail_usessl</td>\n";
            echo "<td>" .printled($c_mail_usessl). "</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_cwtemp</td>\n";
            echo "<td>$c_cwtemp</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_cwmime</td>\n";
            echo "<td>$c_cwmime</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_cws</td>\n";
            echo "<td>" .printled($c_cws). "</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_xalanbin</td>\n";
            echo "<td>$c_xalanbin</td>\n";
          echo "</tr>\n";
        echo "</table>\n";

        echo "<table class='multipletable'>\n";
          echo "<tr>\n";
            echo "<th colspan='2'>" .$l['sc_module']. "</th>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td width='30%'>c_enable_arp</td>\n";
            echo "<td width='70%'>" .printled($c_enable_arp). "</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_enable_argos</td>\n";
            echo "<td>" .printled($c_enable_argos). "</td>\n";
          echo "</tr>\n";
        echo "</table>\n";

        echo "<table class='multipletable'>\n";
          echo "<tr>\n";
            echo "<th colspan='2'>" .$l['sc_perl']. "</th>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td width='30%'>c_logfile</td>\n";
            echo "<td width='70%'>$c_logfile</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>c_logstamp</td>\n";
            echo "<td>" .printled($c_logstamp). "</td>\n";
          echo "</tr>\n";
        echo "</table>\n";

      echo "</div>\n"; #</blockContent>
      echo "<div class='blockFooter'></div>\n";
    echo "</div>\n"; #</dataBlock>
  echo "</div>\n"; #</block>
echo "</div>\n"; #</rightmed>

echo "<div class='all'>\n";
  echo "<div class='centerbig'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockHeader'>" .$l['sc_virus']. "</div>\n";
        echo "<div class='blockContent'>\n";
          $sql = "SELECT name, version FROM scanners";
          $result = pg_query($pgconn, $sql);
          echo "<table class='datatable'>\n";
            while ($row = pg_fetch_assoc($result)) {
              $name = $row['name'];
              $ver = $row['version'];
              $ver = str_replace("Kaspersky Anti-Virus On-Demand Scanner for Linux. ", "", $ver);
              echo "<tr>\n";
                echo "<td><font class='btext'>$name</font></td>\n";
                echo "<td>$ver</td>\n";
              echo "</tr>\n";
            }
          echo "</table>\n";
        echo "</div>\n";
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</dataBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</centerbig>
echo "</div>\n"; #</all>

pg_close($pgconn);
debug_sql();
?>
<?php footer(); ?>

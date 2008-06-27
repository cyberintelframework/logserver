<?php $tab="4.5"; $pagetitle="Argos Templates"; include("menu.php"); contentHeader(0); ?>
<?php

####################################
# SURFids 2.00.03                  #
# Changeset 003                    #
# 10-10-2007                       #
# Kees Trippelvitz & Jan van Lith  #
####################################

####################################
# Changelog:
# 003 Added error message for unauthorized access
# 002 Added hash check stuff
# 001 Initial release
####################################

# Retrieving posted variables from $_GET
$allowed_get = array(
                "int_m",
);
$check = extractvars($_GET, $allowed_get);
debug_input();

# Showing info/error messages if any
if (isset($clean['m'])) {
  $m = $clean['m'];
  geterror($m);
}

if ($s_admin != 1) {
  geterror(101);
  footer();
  exit;
}

if ($s_admin == 1) {
  echo "<div class='centerbig'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockHeader'>Argos Images</div>\n";
        echo "<div class='blockContent'>\n";
          echo "<table class='datatable'>\n";
            echo "<tr>\n";
              echo "<th>Name</th>\n";
              echo "<th>Server IP</th>\n";
              echo "<th>Imagename on Server</th>\n";
              echo "<th>OS</th>\n";
              echo "<th>OS Language</th>\n";
              echo "<th>Mac address</th>\n";
              echo "<th>Organisation</th>\n";
              echo "<th></th>\n";
              echo "<th></th>\n";
            echo "</tr>\n";
            $sql_image = "SELECT * FROM argos_images ORDER BY id";
            $debuginfo[] = $sql_image;
            $query_image = pg_query($sql_image);
            while ($rowimage = pg_fetch_assoc($query_image)) {
              $imageid = $rowimage["id"];
              $name = $rowimage["name"];
              $serverip = $rowimage["serverip"];
              $macaddr = $rowimage["macaddr"];
              $imagename = $rowimage["imagename"];
              $osname = $rowimage["osname"];
              $oslang = $rowimage["oslang"];
              $organisationid = $rowimage["organisationid"];
              echo "<tr>\n";
                echo "<form name='argosadmin_updateimage' action='argosupdateimage.php' method='post'>\n";
                  echo "<td><input type='text' name='strip_html_escape_name' size='30' value='$name' /></td>";
                  echo "<td><input type='text' name='ip_serverip' size='13' value='$serverip' /></td>";
                  echo "<td><input type='text' name='strip_html_escape_imagename' size='20' value='$imagename' /></td>";
                  echo "<td>\n";
                    echo "<select name='strip_html_escape_osname'>\n";
                      echo printOption('win2k', 'win2k' , $osname); 
                      echo printOption('winxp', 'winxp' , $osname); 
                      echo printOption('linux', 'linux' , $osname); 
                    echo "</select>\n";
                  echo "</td>\n";
                  echo "<td>\n";
                    echo "<select name='strip_html_escape_oslang'>\n";
                      echo printOption('nl', 'Dutch' , $oslang);
                      echo printOption('en', 'English' , $oslang);
                    echo "</select>\n";
                  echo "</td>\n";
                  echo "<td><input type='text' name='mac_macaddr' size='15' value='$macaddr' /></td>";
                  echo "<td>\n";
                    echo "<select name='int_orgid'>\n";
                      echo printOption("0", "All Organisations" , "$organisationid");
                      $sql_org = "SELECT id, organisation FROM organisations ORDER BY id";
                      $debuginfo[] = $sql_org;
                      $query_org = pg_query($sql_org);
                      while ($roworg = pg_fetch_assoc($query_org)) {
                        $idorg = $roworg["id"];
                        $organisation = $roworg["organisation"];
                        $organisation = substr($organisation,0 ,11); 
                        if ($organisation != "ADMIN") {
                          echo printOption("$idorg", "$organisation" , "$organisationid");
                        }
                      }
                    echo "</select>\n";
                  echo "</td>\n";
                  echo "<td><input type='submit' class='button' value='Update' /></td>\n";
                  echo "<input type='hidden' name='int_imageid' value='$imageid'>\n";
                  echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
                echo "</form>\n";
                echo "<form name='argosadmin_delimage' action='argosdelimage.php' method='post'>\n";
                  echo "<input type='hidden' name='int_imageid' value='$imageid'>\n";
                  echo "<td><input type='submit' class='button' value='Delete' /></td>\n";
                  echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
                echo "</form>\n";
              echo "</tr>\n";
            }
            echo "<form name='argosadmin_addimage' action='argosaddimage.php' method='post'>\n";
              echo "<tr class='bottom'>\n";
                echo "<td><input type='text' name='strip_html_escape_name' size='30' /></td>";
                echo "<td><input type='text' name='ip_serverip' size='13' /></td>";
                echo "<td><input type='text' name='strip_html_escape_imagename' size='20' /></td>";
                echo "<td>\n";
                  echo "<select name='strip_html_escape_osname'>\n";
                    echo printOption('win2k', 'win2k', ""); 
                    echo printOption('winxp', 'winxp', ""); 
                    echo printOption('linux', 'linux', ""); 
                  echo "</select>\n";
                echo "</td>\n";
                echo "<td>\n";
                  echo "<select name='strip_html_escape_oslang'>\n";
                    echo printOption('nl', 'Dutch' , "");
                    echo printOption('en', 'English' , "");
                  echo "</select>\n";
                echo "</td>\n";
                echo "<td><input type='text' name='mac_macaddr' size='15' /></td>";
                echo "<td>\n";
                  echo "<select name='int_orgid'>\n";
                    echo printOption("0", "All Organisations" , "0");
                    $sql_org = "SELECT id, organisation FROM organisations ORDER BY id";
                    $debuginfo[] = $sql_org;
                    $query_org = pg_query($sql_org);
                    while ($roworg = pg_fetch_assoc($query_org)) {
                      $idorg = $roworg["id"];
                      $organisation = $roworg["organisation"];
                      $organisation = substr($organisation,0 ,11); 
                      if ($organisation != "ADMIN") {
                        echo printOption("$idorg", "$organisation" , "");
                      }
                    }
                  echo "</select>\n";
                echo "</td>\n";
                echo "<td colspan=2><input type='submit' class='button' value='Add' /></td>\n";
              echo "</tr>\n";
              echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
            echo "</form>\n";
          echo "</table>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</dataBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</centerbig>
}
debug_sql();
?>
<?php footer(); ?>

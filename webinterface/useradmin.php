<?php $tab="5.2"; $pagetitle="Users"; include("menu.php"); contentHeader(0); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 2.10.01                  #
# 26-10-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

####################################
# Changelog:
# 2.10.01 Added language support
# 2.00.03 Minor code change
# 2.00.02 Added All option for organisation selector
# 2.00.01 version 2.00
# 1.04.04 Minor bugfix + organisation name
# 1.04.03 Changed data input handling
# 1.04.02 Changed debug stuff
# 1.04.01 Code layout
# 1.03.02 Added sorting option and links to mailadmin
# 1.03.01 Released as part of the 1.03 package
# 1.02.05 Added some more input checks and removed includes
# 1.02.04 Enhanced debugging
# 1.02.03 Added admin_header
# 1.02.02 Changed the access for the admin pages
# 1.02.01 Initial release
####################################

# Retrieving posted variables from $_GET
$allowed_get = array(
		"int_m",
		"sort"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

if ($s_access_user < 2) {
  geterror(101);
  footer();
  pg_close($pgconn);
  exit;
}

# Showing info/error messages if any
if (isset($clean['m'])) {
  $m = $clean['m'];
  geterror($m);
}

# Setting up sorting stuff
if (isset($tainted['sort'])) {
  $pattern = '/^(usernamea|usernamed|organisationa|organisationd|lastlogina|lastlogind|accessa|accesesd)$/';
  $sort = $tainted['sort'];
  $sql_sort = sorter($sort, $pattern);
} else {
  $sql_sort = " username ASC";
  $sort = "usernamea";
}

echo "<div class='leftsmall'>\n";
  echo "<div class='block'>\n";
    echo "<div class='actionBlock'>\n";
      echo "<div class='blockHeader'>" .$l['g_actions']. "</div>\n";
      echo "<div class='blockContent'>\n";
        echo "<a href='usernew.php'>" .$l['ua_adduser']. "</a>\n";
      echo "</div>\n"; #</blockContent>
      echo "<div class='blockFooter'></div>\n";
    echo "</div>\n"; #</actionBlock>
  echo "</div>\n"; #</block>
echo "</div>\n"; #</leftsmall>

echo "<div class='centerbig'>\n";
  echo "<div class='block'>\n";
    echo "<div class='dataBlock'>\n";
     echo "<div class='blockHeader'>\n";
     echo "<div class='blockHeaderLeft'>" .$l['ua_users']. "</div>\n";
     echo "<div class='blockHeaderRight'>\n";
      echo "<form name='viewform' action='$url' method='GET'>\n";
        if ($s_access_search == 9) {
          $sql_orgs = "SELECT id, organisation FROM organisations ORDER BY organisation";
          $debuginfo[] = $sql_orgs;
          $result_orgs = pg_query($pgconn, $sql_orgs);
            echo "<select name='int_org' class='smallselect' onChange='javascript: this.form.submit();'>\n";
              echo printOption(0, $l['g_all'], $q_org);
              while ($row = pg_fetch_assoc($result_orgs)) {
                $org_id = $row['id'];
                $organisation = $row['organisation'];
                echo printOption($org_id, $organisation, $q_org);
              }
            echo "</select>\n";
          }
        echo "</form>\n";
      echo "</div>\n"; 
     echo "</div>\n"; 


      echo "<div class='blockContent'>\n";
        echo "<table class='datatable'>\n";
          echo "<tr>\n";
            echo "<th width='150'>" .printsort($l['ua_user'], "username"). "</th>\n";
            echo "<th width='150'>" .printsort($l['g_domain'], "organisation"). "</th>\n";
            echo "<th width='200'>" .printsort($l['ua_lastlogin'], "lastlogin"). "</th>\n";
            echo "<th width='100'>" .printsort($l['ua_access'], "access"). "</th>\n";
            echo "<th width='50'>" .$l['g_edit']. "</th>\n";
            echo "<th width='50'>" .$l['g_delete']. "</th>\n";
            echo "<th width='100'>" .$l['ua_reports']. "</th>\n";
          echo "</tr>\n";
          if ($s_access_user == 2) {
            $sql_user = "SELECT login.id, login.username, login.lastlogin, login.access, organisations.organisation ";
            $sql_user .= "FROM login, organisations WHERE login.organisation = $q_org AND login.organisation = organisations.id ";
            $sql_user .= "AND NOT login.access LIKE '%9%' ";
            if ($sql_sort != "") {
              $sql_user .= " ORDER BY $sql_sort";
            }
          } elseif ($s_access_user == 9) {
            $sql_user = "SELECT login.id, username, lastlogin, login.access, organisations.organisation ";
            $sql_user .= "FROM login, organisations WHERE login.organisation = organisations.id ";
            if ($q_org != 0) {
              $sql_user .= " AND login.organisation = $q_org ";
            }
            if ($sql_sort != "") {
              $sql_user .= " ORDER BY $sql_sort";
            }
          }
          $debuginfo[] = $sql_user;
          $result_user = pg_query($pgconn, $sql_user);

          while ($row = pg_fetch_assoc($result_user)) {
            $id = $row['id'];
            $username = $row['username'];
            $lastlogin = $row['lastlogin'];
            $access = $row['access'];
            $orgname = $row['organisation'];
            if ( $lastlogin ) {
              $lastlogin = date("d-m-Y H:i:s", $lastlogin);
            } else {
              $lastlogin = "";
            }
            echo "<tr>\n";
              echo "<td>$username</td>\n";
              echo "<td>$orgname</td>\n";
              echo "<td>$lastlogin</td>\n";
              echo "<td>$access</td>\n";
              echo "<td>[<a href='useredit.php?int_userid=$id'><font size=1>" .$l['g_edit']. "</font></a>]</td>\n";
              echo "<td>[<a href='userdel.php?int_userid=$id' onclick=\"javascript: return confirm('" .$l['ua_confirmdel']. "?');\"><font size=1>" .$l['g_delete']. "</font></a>]</td>\n";
              echo "<td>[<a href='myreports.php?int_userid=$id'><font size=1>" .$l['ua_er']. "</font></a>]</td>\n";
            echo "</tr>\n";
          }
        echo "</table>\n";
      echo "</div>\n"; #</blockContent>
      echo "<div class='blockFooter'></div>\n";
    echo "</div>\n"; #</dataBlock>
  echo "</div>\n"; #</block>
echo "</div>\n"; #</centerbig>

pg_close($pgconn);
debug_sql();
footer();
?>

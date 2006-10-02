<?php include("menu.php"); set_title("Organisation Admin"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.02.06                  #
# 09-08-2006                       #
# Kees Trippelvitz                 #
####################################

####################################
# Changelog:
# 1.02.06 Added intval() to session variables + pg_close
# 1.02.05 Added some more input checks and removed includes
# 1.02.04 Enhanced debugging
# 1.02.03 Added modifications for org_id table.
# 1.02.02 Added identifier column to table.
# 1.02.01 Initial release
####################################

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
# $s_access is obsolete again because $s_admin needs to be 1 to gain access
$s_access = $_SESSION['s_access'];
$s_access_user = intval($s_access{2});

if ( $s_admin != 1 ) {
  $err = 1;
}

if ( ! isset($_GET['orgid'] )) {
  $err = 1;
}
else {
  $orgid = intval($_GET['orgid']);
}

if (isset($_GET['m'])) {
  $m = intval($_GET['m']);

  # orgdel.php
  if ($m == 90) { $m = '<p>Admin rights needed to delete an organisation!</p>'; }
  elseif ($m == 91) { $m = '<p>Organisation ID was not set!</p>'; }
  elseif ($m == 92) { $m = '<p>Identifier ID was not set!</p>'; }
  elseif ($m == 93) { $m = '<p>There was no record with this ID!</p>'; }
  elseif ($m == 11) { $m = '<p>Succesfuly deleted this identifier!</p>'; }

  # orgsave.php
  elseif ($m == 81) { $m = '<p>User admin rights are required for this action!</p>'; }
  elseif ($m == 82) { $m = '<p>Organisation ID was not set!</p>'; }
  elseif ($m == 83) { $m = '<p>Organisation name was not set!</p>'; }
  elseif ($m == 12) { $m = '<p>Succesfuly saved the organisation details!</p>'; }

  # Unknown error
  else { $m = "<p><font color='red'>Unknown error!</font></p>"; }

  echo "<p><font color='red'>" .$m. "</font></p>";
}


if ($err != 1) {
  $sql_orgs = "SELECT * FROM organisations WHERE id = " .$orgid;
  $result_orgs = pg_query($pgconn, $sql_orgs);
  $row = pg_fetch_assoc($result_orgs);

  # Debug info
  if ($debug == 1) {
    echo "<pre>";
    echo "SQL_ORGS: $sql_orgs";
    echo "</pre>\n";
  }

  $org = $row['organisation'];
  $ident = $row['identifier'];
  $ranges = $row['ranges'];
  $ranges = str_replace(";", "\n", $ranges);

  echo "<form action='orgsave.php' method='POST'>\n";
    echo "<table class='datatable'>\n";
      echo "<tr class='datatr'>\n";
        echo "<td class='datatd' width='100'>ID</td>\n";
        echo "<td class='datatd' width='300'>$orgid<input type='hidden' name='f_orgid' value='$orgid' /></td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td class='datatd'>Organisation</td>\n";
        echo "<td class='datatd'><input type='text' name='f_org' value='$org' style='width: 99%;' /></td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td class='datatd' valign='top'>Ranges</td>\n";
        echo "<td class='datatd'><textarea name='f_ranges' cols='40' rows='10'>$ranges</textarea></td>\n";
      echo "</tr>\n";
    echo "</table>\n";
    echo "<br />\n";

    $sql_orgids = "SELECT * FROM org_id WHERE orgid = " .$orgid;
    $result_orgids = pg_query($pgconn, $sql_orgids);
    
    echo "<table class='datatable'>\n";
      echo "<tr class='datatr'>\n";
        echo "<td class='dataheader' width='100'>ID</td>\n";
        echo "<td class='dataheader' width='250'>Identifier</td>\n";
        echo "<td class='dataheader' width='50'>Action</td>\n";
      echo "</tr>\n";

      while ($row = pg_fetch_assoc($result_orgids)) {
        $id = $row['id'];
        $identifier = $row['identifier'];

        echo "<tr class='datatr'>\n";
          echo "<td class='datatd'>$id</td>\n";
          echo "<td class='datatd'>$identifier</td>\n";
          echo "<td class='datatd'><a href='orgdel.php?orgid=$orgid&ident=$id' onclick=\"javascript: return confirm('Are you sure you want to delete this identifier?');\">Delete</a></td>\n";
        echo "</tr>\n";
      }
      echo "<tr class='datatr'>\n";
        echo "<td class='datatd'>#</td>\n";
        echo "<td class='datatd' colspan='2'><input type='text' name='f_org_ident' style='width: 99%;' /></td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td class='datatd' colspan='3' align='right'><input type='submit' name='submit_org' value='Save' class='button' /></td>\n";
      echo "</tr>\n";
    echo "</table>\n";
  echo "</form>\n";
}
pg_close($pgconn);
?>
<?php footer(); ?>

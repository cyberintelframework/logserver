<?php include("menu.php"); set_title("Organisation Admin"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.03.01                  #
# 11-10-2006                       #
# Kees Trippelvitz                 #
####################################

####################################
# Changelog:
# 1.03.01 Released as part of the 1.03 package
# 1.02.06 Added intval() to session variables + pg_close
# 1.02.05 Added some more input checks and removed includes
# 1.02.04 Enhanced debugging
# 1.02.03 Added modifications for org_id table.
# 1.02.02 Added identifier column to table.
# 1.02.01 Initial release
####################################

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);

if ( $s_admin != 1 ) {
  $err = 1;
  $m = 91;
  pg_close($pgconn);
  header("location: orgadmin.php?m=$m");
  exit;
}

if ( ! isset($_GET['orgid'] )) {
  $err = 1;
  $m = 36;
  pg_close($pgconn);
  header("location: orgadmin.php?m=$m");
  exit;
} else {
  $orgid = intval($_GET['orgid']);
}

if (isset($_GET['m'])) {
  $m = intval($_GET['m']);
  $m = stripinput($errors[$m]);
  $m = "<p>$m</p>";
  echo "<font color='red'>" .$m. "</font>";
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

  echo "<form action='orgsave.php?type=ident' method='POST'>\n";
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

<?php $tab="3.7"; $pagetitle="Search"; include("menu.php"); contentHeader();

####################################
# SURFids 3.00                     #
# Changeset 001                    #
# 18-11-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################
# Contributors:                    #
# Peter Arts                       #
####################################

#############################################
# Changelog:
# 001 Added language support
#############################################

if (isset($_SESSION['s_total_search_records'])) {
  unset($_SESSION['s_total_search_records']);
}
$_SESSION["search_num_rows"] = 0;
unset($_SESSION["search_num_rows"]);

#$toggle_dest = 'display: none;';
#$toggle_src = 'display: none;';
#$toggle_char = 'display: none;';

# Setting up search include stuff
$search_dest = '';
$search_src = '';
$search_char = '';
$info_dest = 'display: none;';
$info_src = 'display: none;';
$info_char = 'display: none;';
$show_change = 0;
$single_submit = 1;

echo "<script type='text/javascript' src='${address}include/surfids_search${min}.js'></script>\n";
echo "<div class='left'>\n";
include_once 'sinclude.php';
echo "</div>\n";

debug_sql();
?>
<?php footer(); ?>

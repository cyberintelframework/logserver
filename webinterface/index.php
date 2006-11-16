<?php include("menu.php");?>
<?php
####################################
# SURFnet IDS                      #
# Version 1.04.01                  #
# 15-11-2006                       #
# Peter Arts                       #
####################################

#############################################
# Changelog:
# 1.04.01 Added changelog and GD check
#############################################

# GD check
$phpext = get_loaded_extensions();
if (!in_array ("gd", $phpext)){
  echo "<p>\n";
    echo "<font color='red'>\n";
      echo "Warning: GD was not loaded with FreeType support!<br />\n";
      echo "This means that chart generation will most likely fail!<br />\n";
      echo "For more information check out the documentation <a href='http://ids.surfnet.nl/documentation/faq_log.php#5'>here</a>.\n";
    echo "</font>\n";
  echo "</p>\n";
} else {
  $gdinfo = gd_info();
  if ($gdinfo["FreeType Support"] != 1) {
    echo "<p>\n";
      echo "<font color='red'>\n";
        echo "Warning: GD was not loaded with FreeType support!<br />\n";
        echo "This means that chart generation will most likely fail!<br />\n";
        echo "For more information check out the documentation <a href='http://ids.surfnet.nl/documentation/faq_log.php#5'>here</a>.\n";
      echo "</font>\n";
    echo "</p>\n";
  }
}
?>

<h4>SURFnet IDS 1.02</h4>
<ul>
  <li>Enhanced search engine</li>
  <li>Enhanced administration interface</li>
  <li>Increased binary information</li>
  <li>Support for BitDefender and Antivir virusscans</li>
  <li>Passive TCP fingerprinting support</li>
  <li>Added SSL security to the update mechanism</li>
  <li>Static IP configuration support</li>
  <li>Remote sensor control</li>
</ul>
<p>For more technical information you can surf to: <a href="http://ids.surfnet.nl/">http://ids.surfnet.nl/</a></p>
<?php footer(); ?>

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

echo "<h4>SURFnet IDS $c_version</h4>\n";
$day = date("d");
$year = date("Y");
$month = date("n");
$start = getStartWeek($day, $month, $year);
$end = getEndWeek($day, $month, $year);

$startqs = date("d-m-Y+H", $start);
$endqs = date("d-m-Y+H", $end);
$startqsmin = date("i", $start);
$endqsmin = date("i", $end);


echo "<img src='showplot.php?strip_html_escape_tsstart=$startqs%3A$startqsmin&strip_html_escape_tsend=$endqs%3A$endqsmin&sensorid%5B%5D=&severity%5B%5D=0&severity%5B%5D=1&int_interval=3600&int_type=2&int_width=990&int_heigth=400&submit=Show'>";
?>
<p>For more technical information you can surf to: <a href="http://ids.surfnet.nl/">http://ids.surfnet.nl/</a></p>

<?php footer(); ?>

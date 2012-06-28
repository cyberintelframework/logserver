<?php

include '../include/config.inc.php';
include '../include/functions.inc.php';
include '../include/variables.inc.php';

# Starting the session
session_start();

# Including language file
include "../lang/${c_language}.php";

echo "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>\n";
echo "<html xmlns='http://www.w3.org/1999/xhtml' lang='en' xml:lang='en'>\n";
  echo "<head>\n";
    echo "<title>SURFids - $pagetitle</title>\n";
    echo "<link rel='stylesheet' href='include/jquery.jgrowl.css' />\n";
    echo "<link rel='stylesheet' href='include/layout.css' />\n";
    echo "<link rel='stylesheet' href='include/design.css' />\n";
    echo "<link rel='stylesheet' href='include/jquery.jtip.css' />\n";
    echo "<style type='text/css'>@import url('include/calendar.css');</style>\n";
    echo "<link rel='stylesheet' href='include/idsstyle.css' />\n";
    echo "<script type='text/javascript' src='include/overlib/overlib${min}.js'><!-- overLIB (c) Erik Bosrup --></script>\n";
    echo "<script type='text/javascript' src='include/jquery-${c_jquery_version}${min}.js'></script>\n";
    echo "<script type='text/javascript' src='include/jquery.selectboxes${min}.js'></script>\n";
    echo "<script type='text/javascript' src='include/jquery.jgrowl${min}.js'></script>\n";
    echo "<script type='text/javascript' src='include/jquery.jtip${min}.js'></script>\n";
    echo "<script type='text/javascript' src='include/surfids${min}.js'></script>\n";
    echo "<script type='text/javascript' src='include/calendar${min}.js'></script>\n";
    echo "<script type='text/javascript' src='include/calendar-en${min}.js'></script>\n";
    echo "<script type='text/javascript' src='include/calendar-setup${min}.js'></script>\n";
  echo "</head>\n";
  echo "<body>\n";
  echo "<div id='page'>\n";
    echo "<div id='pageHeader'>\n";
      echo "<ul id='globalNav'>\n";
        echo "<li><a href='mailto:$c_contact_mail'>" .$l['me_contact']. "</a></li>\n";
        echo "<li><a href='logout.php'>" .$l['me_logout']. "</a></li>\n";
        echo "<li><a href='http://ids.surfnet.nl/'>" .$l['me_about']. "</a></li>\n";
        echo "<li><a href='http://www.surfnet.nl/Documents/Manual_IDS_v1.0.pdf'>" .$l['me_manual']. "</a></li>\n";
      echo "</ul>\n";
      echo "<h1><a href=''>SURFids</a></h1>\n";
      echo "<div class='infoBar'>\n";
          echo "<div id='headerSensors'>&nbsp;</div>";
          echo "<div id='headerTime'> <span id=\"tP\">&nbsp;</span></div>\n";
          echo "<div id='headerUser'>\n";
              echo $l['me_logged']. ": ".$_SESSION['email']."";
          echo "</div>\n";
      echo "</div>";
     echo "<div id='tab-menu'></div>\n";
     echo "</div>";

     echo "<div id='pageBody'>";
                echo "<div class='center'>\n";
                  echo "<div class='block'>\n";
                    echo "<div class='dataBlock'>\n";
                        echo "<div class='blockHeader'>Maintenance</div>\n";
                        echo "<div class='blockContent'>\n";
				echo "The SURFcert IDS webinterface is currently offline for maintenance. Apologies for the inconvenience.\n";
                        echo "</div>\n";
                        echo "<div class='blockFooter'></div>\n";
                    echo "</div>\n";
                  echo "</div>\n";
                echo "</div>\n";


?>
<?php footer(); ?>

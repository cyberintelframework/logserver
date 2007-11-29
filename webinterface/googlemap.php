<?php $tab="2.3"; $pagetitle="Google Map"; include("menu.php"); contentHeader(); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 2.00.01                  #
# 12-09-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 2.00.01 version 2.00
# 1.04.02 Added Legend 
# 1.04.01 Code layout 
# 1.04.00 initial release 
#############################################

# Retrieving some session variables
$s_org = intval($_SESSION['s_org']);
$s_access = $_SESSION['s_access'];
$s_access_search = intval($s_access{1});

$xmlquery="?int_from=$from&int_to=$to&int_org=$q_org";

### BROWSE MENU

echo "<div id=\"search_wait\"><center>Your request is being processed...<br /><br />Please be patient.<br /></center></div>\n";
#echo "<div id='content'>\n";
echo "<div class='center'>\n";
echo "<div class='block'>\n";
echo "<div class='dataBlock'>\n";
echo "<div class='blockHeader'>Google Map</div>\n";
echo "<div class='blockContent'>\n";
echo "<div id='map' style='width: 800px; height: 400px'></div>\n";
echo "<script src='http://maps.google.com/maps?file=api&amp;v=2&amp;key=$c_googlemap_key' type='text/javascript'></script>";
?>
<script type="text/javascript">
//<![CDATA[
 
var map = new GMap(document.getElementById("map"));
map.addControl(new GMapTypeControl());
map.addControl(new GLargeMapControl());
map.centerAndZoom(new GPoint(12.0,25.0),15);
//map.setMapType(G_SATELLITE_TYPE);
//]]>
 
var yellowicon = new GIcon();
yellowicon.image = "http://labs.google.com/ridefinder/images/mm_20_yellow.png";
yellowicon.shadow = "http://labs.google.com/ridefinder/images/mm_20_shadow.png";
yellowicon.iconSize = new GSize(12, 20);
yellowicon.shadowSize = new GSize(22, 20);
yellowicon.iconAnchor = new GPoint(6, 20);
yellowicon.infoWindowAnchor = new GPoint(5, 1);
 
var orangeicon = new GIcon();
orangeicon.image = "http://labs.google.com/ridefinder/images/mm_20_orange.png";
orangeicon.shadow = "http://labs.google.com/ridefinder/images/mm_20_shadow.png";
orangeicon.iconSize = new GSize(12, 20);
orangeicon.shadowSize = new GSize(22, 20);
orangeicon.iconAnchor = new GPoint(6, 20);
orangeicon.infoWindowAnchor = new GPoint(5, 1);
 
var redicon = new GIcon();
redicon.image = "http://labs.google.com/ridefinder/images/mm_20_red.png";
redicon.shadow = "http://labs.google.com/ridefinder/images/mm_20_shadow.png";
redicon.iconSize = new GSize(12, 20);
redicon.shadowSize = new GSize(22, 20);
redicon.iconAnchor = new GPoint(6, 20);
redicon.infoWindowAnchor = new GPoint(5, 1);
 
var blackicon = new GIcon();
blackicon.image = "http://labs.google.com/ridefinder/images/mm_20_black.png";
blackicon.shadow = "http://labs.google.com/ridefinder/images/mm_20_shadow.png";
blackicon.iconSize = new GSize(12, 20);
blackicon.shadowSize = new GSize(22, 20);
blackicon.iconAnchor = new GPoint(6, 20);
blackicon.infoWindowAnchor = new GPoint(5, 1);
 
 
function createMarker(point,country,city,count)
{
  var marker;
  if ( count <= 5){
   marker = new GMarker(point,yellowicon);
  }else
  if ( count <= 25){
   marker = new GMarker(point,orangeicon);
  }else
  if ( count <= 250){
   marker = new GMarker(point,redicon);
  }else
  {
   marker = new GMarker(point,blackicon);
  }
 
  var msg = "<small><b>Country:</b> " + country +"<br/>";
  msg = msg+"<b>City:</b> " + city +"<br/>";
  msg = msg+"<b>Count:</b> " + count +"<br/>";
  msg = msg+"</small>";
  GEvent.addListener(marker, "click", function(){marker.openInfoWindowHtml(msg);});
  return marker;
}
 
var request = GXmlHttp.create();
<?
echo "request.open('GET', 'googlemapdata.xml.php$xmlquery', true);";
?>

request.onreadystatechange = function() {
  if (request.readyState == 4) {
    if (request.status == 200) {
      document.getElementById('search_wait').style.display='none';
      var xmlDoc = request.responseXML;
      var markers = xmlDoc.documentElement.getElementsByTagName("marker");
 
      for (var i = 0; i < markers.length; i++) {
        var point = new GPoint(
          parseFloat(markers[i].getAttribute("lng")),
          parseFloat(markers[i].getAttribute("lat"))
        );
        var city = markers[i].getAttribute("city");
        var country = markers[i].getAttribute("country");
        var count = markers[i].getAttribute("count");
        var marker = new createMarker(point,country,city,count);
        map.addOverlay(marker);
      }
    }
  }
}
request.send(null);
</script>
<?php
echo "</div>\n";
echo "<div class='blockFooter'></div>\n";
echo "</div>\n"; #</dataBlock>
echo "</div>\n"; #</block>
echo "</div>\n"; #</center>

echo "<div class='center'>\n";
  echo "<div class='legend'>\n";
    echo "<div class='legendHeader'>Legend</div>\n";
    echo "<div class='legendContent'>\n";
      echo "<div class='legendContentLeft'>\n";
        echo "<div class='legendItem'>\n";
          echo "<img src='http://labs.google.com/ridefinder/images/mm_20_yellow.png'> &lt;= 5 Attacks<br />\n";
        echo "</div>\n";
        echo "<div class='legendItem'>\n";
          echo "<img src='http://labs.google.com/ridefinder/images/mm_20_orange.png'> &lt;= 25  Attacks<br />\n";
        echo "</div>\n";
        echo "<div class='legendItem'>\n";
          echo "<img src='http://labs.google.com/ridefinder/images/mm_20_red.png'> &lt;= 250  Attacks<br />\n";
        echo "</div>\n";
        echo "<div class='legendItem'>\n";
          echo "<img src='http://labs.google.com/ridefinder/images/mm_20_black.png'> &gt; 250 Attacks<br />\n";
        echo "</div>\n";
      echo "</div>\n";
    echo "</div>\n";
    echo "<div class='legendFooter'></div>\n";
  echo "</div>\n";
echo "</div>\n";

?>
<?php footer(); ?>

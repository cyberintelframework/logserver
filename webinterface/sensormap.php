<?php $tab="2.3"; include("menu.php"); $pagetitle=$l['gm_sensormap']; contentHeader(); ?>
<?php

####################################
# SURFnet IDS 2.10.00              #
# Changeset 001                    #
# 03-03-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 001 Initial release
#############################################

# Retrieving some session variables
$s_org = intval($_SESSION['s_org']);
$s_access = $_SESSION['s_access'];
$s_access_search = intval($s_access{1});

$xmlquery = "?int_from=$from&int_to=$to&int_org=$q_org";

echo "<div id='search_wait'><center>" .$l['gm_process']. "<br /><br />" .$l['gm_patient']. ".<br /></center></div>\n";
echo "<div class='center'>\n";
echo "<div class='block'>\n";
echo "<div class='dataBlock'>\n";
echo "<div class='blockHeader'>\n";
  echo "<div class='blockHeaderLeft'>" .$l['gm_sensormap']. "</div>\n";
  echo "<div class='blockHeaderRight'>\n";
    echo "<select class='smallselect' id='redirmapper' name='redir' onChange='redirmap();'>\n";
      echo printoption(1, "Attacks", 2);
      echo printoption(2, "Sensors", 2);
    echo "</select>\n";
  echo "</div>\n";
echo "</div>\n";
echo "<div class='blockContent'>\n";
echo "<div id='map' style='width: 890px; height: 400px'></div>\n";
echo "<script src='include/jquery.jmap2.js' type='text/javascript'></script>";
echo "<script src='http://maps.google.com/maps?file=api&amp;v=2&amp;key=$c_googlemap_key' type='text/javascript'></script>";
?>
<script type="text/javascript">
$('#search_wait').html("<?php echo "<center>" .$l['gm_setting']. "<br /><br />" .$l['gm_patient']. ".<br /></center>" ?>");
var yellowicon = $.jmap.createIcon({
        image: "http://labs.google.com/ridefinder/images/mm_20_yellow.png",
        shadow: "http://labs.google.com/ridefinder/images/mm_20_shadow.png",
        iconSize : new GSize(12, 20),
        shadowSize : new GSize(22, 20),
        iconAnchor : new GPoint(6, 20),
        infoWindowAnchor : new GPoint(5, 1),
        infoShadowAnchor : new GPoint(5, 1)
});

var orangeicon = $.jmap.createIcon({
        image: "http://labs.google.com/ridefinder/images/mm_20_orange.png",
        shadow: "http://labs.google.com/ridefinder/images/mm_20_shadow.png",
        iconSize : new GSize(12, 20),
        shadowSize : new GSize(22, 20),
        iconAnchor : new GPoint(6, 20),
        infoWindowAnchor : new GPoint(5, 1),
        infoShadowAnchor : new GPoint(5, 1)
});

var redicon = $.jmap.createIcon({
        image: "http://labs.google.com/ridefinder/images/mm_20_red.png",
        shadow: "http://labs.google.com/ridefinder/images/mm_20_shadow.png",
        iconSize : new GSize(12, 20),
        shadowSize : new GSize(22, 20),
        iconAnchor : new GPoint(6, 20),
        infoWindowAnchor : new GPoint(5, 1),
        infoShadowAnchor : new GPoint(5, 1)
});

var blackicon = $.jmap.createIcon({
        image: "http://labs.google.com/ridefinder/images/mm_20_black.png",
        shadow: "http://labs.google.com/ridefinder/images/mm_20_shadow.png",
        iconSize : new GSize(12, 20),
        shadowSize : new GSize(22, 20),
        iconAnchor : new GPoint(6, 20),
        infoWindowAnchor : new GPoint(5, 1),
        infoShadowAnchor : new GPoint(5, 1)
});

$('#map').jmap({
	mapZoom: 2,
	mapShowOverview: false,
	mapControlSize: "large",
	mapDimensions: [900,400]
});

</script>
<?php
flush();
echo "</div>\n";
echo "<div class='blockFooter'></div>\n";
echo "</div>\n"; #</dataBlock>
echo "</div>\n"; #</block>
echo "</div>\n"; #</center>

echo "<div class='center'>\n";
  echo "<div class='legend'>\n";
    echo "<div class='legendHeader'>" .$l['g_legend']. "</div>\n";
    echo "<div class='legendContent'>\n";
      echo "<div class='legendContentLeft'>\n";
        echo "<div class='legendItem'>\n";
          echo "<img src='http://labs.google.com/ridefinder/images/mm_20_yellow.png'> &lt;= 5 " .$l['g_sensors_l']. "<br />\n";
        echo "</div>\n";
        echo "<div class='legendItem'>\n";
          echo "<img src='http://labs.google.com/ridefinder/images/mm_20_orange.png'> &lt;= 25 " .$l['g_sensors_l']. "<br />\n";
        echo "</div>\n";
        echo "<div class='legendItem'>\n";
          echo "<img src='http://labs.google.com/ridefinder/images/mm_20_red.png'> &lt;= 250 " .$l['g_sensors_l']. "<br />\n";
        echo "</div>\n";
        echo "<div class='legendItem'>\n";
          echo "<img src='http://labs.google.com/ridefinder/images/mm_20_black.png'> &gt; 250 " .$l['g_sensors_l']. "<br />\n";
        echo "</div>\n";
      echo "</div>\n";
    echo "</div>\n";
    echo "<div class='legendFooter'></div>\n";
  echo "</div>\n";
echo "</div>\n";

?>
<script>
url = "sensormapdata.xml.php<?php echo $xmlquery ?>";
$('#search_wait').html("<?php echo "<center>" .$l['gm_loading']. "<br /><br />" .$l['gm_patient']. ".<br /></center>" ?>");

$.ajax({
  url: url,
  type: 'GET',
  dataType: 'xml',
  error: function(){
    alert('Error processing your request!');
  },
  success: function(xml){
    $("marker", xml).each(function() {
      country = $(this).attr("country");
      count = $(this).attr("count");
      city = $(this).attr("city");
      lat = $(this).attr("lat");
      lng = $(this).attr("lng");

      var msg = "<small><b>Country:</b> " + country +"<br/>";
      msg = msg+"<b>City:</b> " + city +"<br/>";
      msg = msg+"<b># of sensors:</b> " + count +"<br/>";
      msg = msg+"</small>";

      if (count <= 5) {
        var icon = yellowicon;
      } else if (count <= 25) {
        var icon = orangeicon;
      } else if (count <= 250) {
        var icon = redicon;
      } else {
        var icon = blackicon;
      }

      $('#map').addMarker({
        pointLat: lat,
        pointLng: lng,
        pointHTML: msg,
        icon: icon
      });
    });
    $('#search_wait').toggle();
  }
});
</script>
<?php flush(); ?>
<?php footer(); ?>

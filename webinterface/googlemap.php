<?php $tab="2.3"; include("menu.php"); $pagetitle=$l['gm_attackmap']; contentHeader(); ?>
<?php

####################################
# SURFids 2.10                     #
# Changeset 002                    #
# 14-04-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 002 Changed Count into Attacks
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
  echo "<div class='blockHeaderLeft'>" .$l['gm_attackmap']. "</div>\n";
  echo "<div class='blockHeaderRight'>\n";
    echo "<select class='smallselect' id='redirmapper' name='redir' onChange='redirmap();'>\n";
      echo printoption(1, "Attacks", 1);
      echo printoption(2, "Sensors", 1);
    echo "</select>\n";
  echo "</div>\n";
echo "</div>\n";
echo "<div class='blockContent'>\n";
echo "<div id='map' style='width: 890px; height: 400px'></div>\n";
echo "<script src='include/jquery.jmap.js' type='text/javascript'></script>";
echo "<script src='http://maps.google.com/maps?file=api&amp;v=2&amp;key=$c_googlemap_key' type='text/javascript'></script>";
?>
<script type="text/javascript">
$('#search_wait').html("<?php echo "<center>" .$l['gm_setting']. "<br /><br />" .$l['gm_patient']. ".<br /></center>" ?>");

$('#map').jmap('init', {
        'mapZoom': 2,
        'mapShowOverview': false,
        'mapControlSize': 'large',
        'mapDimensions': [900,400]
});

var yellowicon = Mapifies.createIcon ({
        iconImage: "http://labs.google.com/ridefinder/images/mm_20_yellow.png",
        iconShadow: "http://labs.google.com/ridefinder/images/mm_20_shadow.png",
        iconSize : new GSize(12, 20),
        iconShadowSize : new GSize(22, 20),
        iconAnchor : new GPoint(6, 20),
        iconInfoWindowAnchor : new GPoint(5, 1)
});

var orangeicon = Mapifies.createIcon ({
        iconImage: "http://labs.google.com/ridefinder/images/mm_20_orange.png",
        iconShadow: "http://labs.google.com/ridefinder/images/mm_20_shadow.png",
        iconSize : new GSize(12, 20),
        iconShadowSize : new GSize(22, 20),
        iconAnchor : new GPoint(6, 20),
        iconInfoWindowAnchor : new GPoint(5, 1)
});

var redicon = Mapifies.createIcon ({
        iconImage: "http://labs.google.com/ridefinder/images/mm_20_red.png",
        iconShadow: "http://labs.google.com/ridefinder/images/mm_20_shadow.png",
        iconSize : new GSize(12, 20),
        iconShadowSize : new GSize(22, 20),
        iconAnchor : new GPoint(6, 20),
        iconInfoWindowAnchor : new GPoint(5, 1)
});

var blackicon = Mapifies.createIcon ({
        iconImage: "http://labs.google.com/ridefinder/images/mm_20_black.png",
        iconShadow: "http://labs.google.com/ridefinder/images/mm_20_shadow.png",
        iconSize : new GSize(12, 20),
        iconShadowSize : new GSize(22, 20),
        iconAnchor : new GPoint(6, 20),
        iconInfoWindowAnchor : new GPoint(5, 1)
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
          echo "<img src='http://labs.google.com/ridefinder/images/mm_20_yellow.png'> &lt;= 5 " .$l['g_attacks']. "<br />\n";
        echo "</div>\n";
        echo "<div class='legendItem'>\n";
          echo "<img src='http://labs.google.com/ridefinder/images/mm_20_orange.png'> &lt;= 25 " .$l['g_attacks']. "<br />\n";
        echo "</div>\n";
        echo "<div class='legendItem'>\n";
          echo "<img src='http://labs.google.com/ridefinder/images/mm_20_red.png'> &lt;= 250 " .$l['g_attacks']. "<br />\n";
        echo "</div>\n";
        echo "<div class='legendItem'>\n";
          echo "<img src='http://labs.google.com/ridefinder/images/mm_20_black.png'> &gt; 250 " .$l['g_attacks']. "<br />\n";
        echo "</div>\n";
      echo "</div>\n";
    echo "</div>\n";
    echo "<div class='legendFooter'></div>\n";
  echo "</div>\n";
echo "</div>\n";

?>
<script>
url = "googlemapdata.xml.php<?php echo $xmlquery ?>";
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
      msg = msg+"<b>Attacks:</b> " + count +"<br/>";
      msg = msg+"</small>";

      if (count <= 5) {
        var picon = yellowicon;
      } else if (count <= 25) {
        var picon = orangeicon;
      } else if (count <= 250) {
        var picon = redicon;
      } else {
        var picon = blackicon;
      }

      $('#map').jmap('AddMarker', {
        pointLatLng: [lat, lng],
        pointHTML: msg,
        pointIcon: picon
      });
    });
    $('#search_wait').toggle();
  }
});
</script>
<?php flush(); ?>
<?php footer(); ?>

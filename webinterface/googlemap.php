<?php include("menu.php"); set_title("Google Map");?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.00                  #
# 05-01-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.04.00 
#############################################
$s_org = intval($_SESSION['s_org']);
$s_access = $_SESSION['s_access'];
$s_access_search = intval($s_access{1});

$allowed_get = array(
                "int_org",
                "b",
                "i",
		"int_to",
		"int_from"
);
$check = extractvars($_GET, $allowed_get);
debug_input();


if ($s_access_search == 9 && isset($clean['org'])) {
  $q_org = $clean['org'];
} elseif ($s_access_search == 9) {
  $q_org = 0;
} else {
  $q_org = intval($s_org);
}

$sql_getorg = "SELECT organisation FROM organisations WHERE id = $q_org";
$result_getorg = pg_query($pgconn, $sql_getorg);

$debuginfo[] = $sql_getorg;


### Default browse method is weekly.
if (isset($tainted['b'])) {
  $b = $tainted['b'];
  $pattern = '/^(weekly|daily|monthly|all)$/';
  if (!preg_match($pattern, $b)) {
    $b = "weekly";
  }
} else {
  $b = "weekly";
}

$year = date("Y");
if ($b == "monthly") {
  $month = $tainted['i'];
  if ($month == "") { $month = date("n"); }
  $month = intval($month);
  $next = $month + 1;
  $prev = $month - 1;
  $start = getStartMonth($month, $year);
  $end = getEndMonth($month, $year);
  $xmlquery="?b=$b&i=$month&int_org=$q_org";
} else {
  $month = date("n");
}
if ($b == "daily") {
  $day = $tainted['i'];
  if ($day == "") { $day = date("d"); }
  $day = intval($day);
  $prev = $day - 1;
  $next = $day + 1;
  $start = getStartDay($day, $month, $year);
  $end = getEndDay($day, $month, $year);
  $xmlquery="?b=$b&i=$day&int_org=$q_org";
} else {
  $day = date("d");
}
if ($b == "weekly") {
  $day = $tainted['i'];
  if ($day == "") { $day = date("d"); }
  $day = intval($day);
  $prev = $day - 7;
  $next = $day + 7;
  $start = getStartWeek($day, $month, $year);
  $end = getEndWeek($day, $month, $year);
  $xmlquery="?b=$b&i=$day&int_org=$q_org";
}

$tsquery = "timestamp >= $start AND timestamp <= $end";

### BROWSE MENU
$today = date("U");


echo "<form name='selectorg' method='get' action='googlemap.php'>\n";
  if ($b != "all") {
    echo "<input type='button' value='Prev' class='button' onClick=window.location='googlemap.php?b=$b&amp;i=$prev&amp;int_org=$q_org';>\n";
      
  } else {
    echo "<input type='button' value='Prev' class='button' disabled>\n";
  }
  echo "<select name='b' onChange='javascript: this.form.submit();'>\n";
    echo printOption("daily", "Daily", $b) . "\n";
    echo printOption("weekly", "Weekly", $b) . "\n";
    echo printOption("monthly", "Monthly", $b) . "\n";
  echo "</select>\n";

  if ($s_access_search == 9) {
    if (!isset($clean['org'])) {
      $err = 1;
    }
    $sql_orgs = "SELECT * FROM organisations WHERE NOT organisation = 'ADMIN'";
    $debuginfo[] = $sql_orgs;
    $result_orgs = pg_query($pgconn, $sql_orgs);
    echo "<select name='int_org' onChange='javascript: this.form.submit();'>\n";
      echo printOption(0, "All", $q_org) . "\n";
      while ($row = pg_fetch_assoc($result_orgs)) {
        $org_id = $row['id'];
        $organisation = $row['organisation'];
        echo printOption($org_id, $organisation, $q_org) . "\n";
      }
    echo "</select>&nbsp;\n";
  }

  if ($b != "all") {
    if ($end > $today) {
      echo "<input type='button' value='Next' class='button' disabled>\n";
    } else {
      echo "<input type='button' value='Next' class='button' onClick=window.location='googlemap.php?b=$b&amp;i=$next&amp;int_org=$q_org';>\n";
    }
  } else {
    echo "<input type='button' value='Next' class='button' disabled>\n";
  }
echo "</form>\n";
echo "<br />\n";
echo "Parsing could take some time. Please be patient<br />\n";
if ($b == "daily") {
      $datestart = date("d-m-Y", $start);
      echo "<h4>Results from $datestart</h4>\n";
} else {
      $datestart = date("d-m-Y", $start);
      $dateend = date("d-m-Y", $end);
      echo "<h4>Results from $datestart to $dateend</h4>\n";
}
?>


      



<div id="map" style="width: 800px; height: 400px">
</div>

</div>
<?php
echo "<script src='http://maps.google.com/maps?file=api&amp;v=2&amp;key=$c_googlemap_key' type='text/javascript'></script>";
?>
<script type="text/javascript">
//<![CDATA[
 
var map = new GMap(document.getElementById("map"));
//    map.addControl(new GSmallMapControl());
map.addControl(new GMapTypeControl());
map.addControl(new GLargeMapControl());
map.centerAndZoom(new GPoint(0.0, 18.0), 15);
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
	var xmlDoc = request.responseXML;
        var markers = xmlDoc.documentElement.getElementsByTagName("marker");
 
        for (var i = 0; i < markers.length; i++) {
            var point = new GPoint(parseFloat(markers[i].getAttribute("lng")),
                                   parseFloat(markers[i].getAttribute("lat")));
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





</body>


</html>


<?php include("menu.php"); set_title("Search");

####################################
# SURFnet IDS                      #
# Version 1.04.03                  #
# 16-11-2006                       #
# Peter Arts                       #
# Modified by Jan van Lith         #
####################################

#############################################
# Changelog:
# 1.04.03 Added Searchtemplates
# 1.04.02 Added VLAN support 
# 1.04.01 Rereleased as 1.04.01
# 1.03.01 Released as part of the 1.03 package
# 1.02.06 Removed includes
# 1.02.05 Added search templates
# 1.02.04 Added multiple sensor select
# 1.02.03 Added IDMEF to the report types
#############################################

if (isset($_SESSION['s_total_search_records'])) {
  unset($_SESSION['s_total_search_records']);
}
$_SESSION["search_num_rows"] = 0;
unset($_SESSION["search_num_rows"]);
?>

<style type="text/css">@import url('./calendar/css/calendar.css');</style>
<script type="text/javascript" src="./calendar/js/calendar.js"></script>
<script type="text/javascript" src="./calendar/js/calendar-en.js"></script>
<script type="text/javascript" src="./calendar/js/calendar-setup.js"></script>
<script type="text/javascript" language="javascript">
function check_byte(b_val,next_field) {
    if(isNaN(b_val.value) || (b_val.value).indexOf(".") >0 || b_val.value > 255){
        alert(b_val.value + " is not a valid number (0 - 254)");
        document.getElementById(b_val.id).value = '';
    } else {
        if ((b_val.value).length==b_val.maxLength) {
            if (next_field != '') {
                document.getElementById(next_field).focus();
            }
        }
    }
}
</script>

<form method="get" action="logsearch.php" id="searchform">

<table border='0'>
  <tr>
    <td>
<table class="datatable">
 <tr>
  <td colspan=2><h4>Who</h4></td>
 </tr>
    <?
    $select_size = 5;
    $s_org = intval($_SESSION['s_org']);
    $s_admin = intval($_SESSION['s_admin']);
	if ($s_admin == 1) $where = "";
	else $where = " WHERE organisation = '$s_org'";

    $sql = "SELECT COUNT(*) FROM sensors $where";
	$query = pg_query($sql);
	$nr_rows = intval(@pg_result($query, 0));
	if ($nr_rows < $select_size) $select_size = ($nr_rows + 1);
	if ($nr_rows > 1) {
		echo "<tr>\n";
  		echo "  <td class=\"datatd\" width=140>Sensor:</td>\n";
		echo "  <td class=\"datatd\">\n";
		echo "<select name=\"sensorid[]\" style=\"background-color:white;\" size=\"" . $select_size . "\" multiple=\"true\">\n";
	    echo printOption(0, "All sensors", $sensorid);
	    $sql = "SELECT * FROM sensors $where ORDER BY keyname";
		$query = pg_query($sql);
		while ($sensor_data = pg_fetch_assoc($query)) {
			$label = $sensor_data["keyname"];
			$vlanid = $sensor_data["vlanid"];
			if ($vlanid != 0 ) {
			  $label .=  "-" .$vlanid;
			}
			if ($s_admin == 1) {
				// get organisation name
				$query_org = pg_query("SELECT organisation FROM organisations WHERE id = '" . intval($sensor_data["organisation"]) . "'");
				$org = pg_result($query_org, 0);
				$label .= " (" . $org . ")";
			}
			
			  echo printOption($sensor_data["id"], $label, $sensorid);
		}
		echo "</select>\n";
		echo "  </td>\n";
		echo " </tr>\n";
	}
    ?>
 <tr>
  <td class="datatd" width=140>Source:</td>
  <td class="datatd">
    <input type="radio" name="s_radio" id="s_radioA" value="A" checked onclick="document.getElementById('source_A').style.display='';document.getElementById('source_N').style.display='none';"> 
     <label for="s_radioA" style="cursor:pointer;">Address</label> &nbsp; 
    <input type="radio" name="s_radio" id="s_radioN" value="N" onclick="document.getElementById('source_N').style.display='';document.getElementById('source_A').style.display='none';"> 
     <label for="s_radioN" style="cursor:pointer;">Network</label><br />
    <input type="text" name="source_ip[]" id="source_ip1" maxlength=3 style="width:30px;" onKeyUp="check_byte(this, 'source_ip2');" /> . 
    <input type="text" name="source_ip[]" id="source_ip2" maxlength=3 style="width:30px;" onKeyUp="check_byte(this, 'source_ip3');" /> . 
    <input type="text" name="source_ip[]" id="source_ip3" maxlength=3 style="width:30px;" onKeyUp="check_byte(this, 'source_ip4');" />. 
    <input type="text" name="source_ip[]" id="source_ip4" maxlength=3 style="width:30px;" onKeyUp="check_byte(this, '');" />
    <font id="source_A" name="source_A">
      : <input type="text" name="source_port" style="width:80px;" />
    </font>
    <font id="source_N" name="source_N" style="display:none;">
      / <input type="text" name="source_mask" maxlength=3 style="width:40px;">
    </font>
   </td>
 </tr>
 <tr>
  <td class="datatd">Destination:</td>
  <td class="datatd">
    <input type="radio" name="d_radio" id="d_radioA" value="A" checked onclick="document.getElementById('destination_A').style.display='';document.getElementById('destination_N').style.display='none';"> <label for="d_radioA" style="cursor:pointer;">Address</label> &nbsp; <input type="radio" name="d_radio" id="d_radioN" value="N" onclick="document.getElementById('destination_N').style.display='';document.getElementById('destination_A').style.display='none';"> <label for="d_radioN" style="cursor:pointer;">Network</label><br />
    <input type="text" name="destination_ip[]" id="destination_ip1" maxlength=3 style="width:30px;" onKeyUp="check_byte(this, 'destination_ip2');" /> . 
    <input type="text" name="destination_ip[]" id="destination_ip2" maxlength=3 style="width:30px;" onKeyUp="check_byte(this, 'destination_ip3');" /> . 
    <input type="text" name="destination_ip[]" id="destination_ip3" maxlength=3 style="width:30px;" onKeyUp="check_byte(this, 'destination_ip4');" />. 
    <input type="text" name="destination_ip[]" id="destination_ip4" maxlength=3 style="width:30px;" onKeyUp="check_byte(this, '');" />
    <font id="destination_A">
      : <input type="text" name="destination_port" style="width:80px;" />
    </font>
    <font id="destination_N" style="display:none;">
      / <input type="text" name="destination_mask" maxlength=3 style="width:40px;">
    </font>
   </td>
 </tr>
 <tr>
  <td colspan=2><h4>When</h4></td>
 </tr>
 <tr>
  <td class="datatd">Select:</td>
  <td class="datatd">
   <select name="ts_select" style="background-color:white;">
    <option value=""></option>
    <option value="H">Last hour</option>
    <option value="D">Last 24 hour</option>
    <option value="T">Today</option>
    <option value="W">Last week</option>
    <option value="M">Last month</option>
    <option value="Y">Last year</option>
   </select>
  </td>
 </tr>
 <tr>
  <td class="datatd">Between:</td>
  <td class="datatd"><input type="text" name="ts_start" id="ts_start" value="<?=$ts_start;?>" /> <input type="button" value="..." name="ts_start_trigger" id="ts_start_trigger" /></td>
 </tr>
 <tr>
  <td class="datatd">And: </td>
  <td class="datatd"><input type="text" name="ts_end" id="ts_end" value="<?=$ts_end;?>" /> <input type="button" value="..." name="ts_end_trigger" id="ts_end_trigger" /></td>
 </tr>
 <tr>
  <td colspan=2><h4>How</h4></td>
 </tr>
 <tr>
  <td class="datatd">Report type:</td>
  <td class="datatd">
   <select name="f_reptype" style="background-color:white;" onchange="change_form(this.selectedIndex);">
    <option value="multi">Multi page</option>
    <option value="single">Single page</option>
    <option value="chart_sensor">Chart for sensor</option>
    <option value="chart_attack">Chart for attack</option>
    <option value="idmef">IDMEF</option>
    <option value="pdf">PDF</option>
   </select>
  </td>
 </tr>
 <tr>
  <td colspan=2><h4>What</h4></td>
 </tr>
 <tr id="what_c1" name="what_c1" style="display:none;">
  <td class="datatd">Chart of:</td>
  <td class="datatd">
   <select name="f_chart_of" style="background-color:white;">
    <option value="attack">Attack</option>
    <option value="severity">Severity</option>
    <option value="virus">Virus top 15</option>
   </select>
  </td>
 </tr>
 <tr id="what_1" name="what_1">
  <td class="datatd">Severity: </td>
  <td class="datatd"><select name="f_sev" style="background-color:white;">
  <?
  $f_sev = -1;
  echo printOption(-1, "", $f_sev);
  foreach ( $severity_ar as $index=>$severity ) echo printOption($index, $severity, $f_sev);
  ?>
   </select></td>
 </tr>
 <tr id="what_2" name="what_2">
  <td class="datatd">Attack: </td>
  <td class="datatd"><select name="f_attack" style="background-color:white;">
  <?
  echo printOption(-1, "All attacks", $f_attack);
  $query = pg_query("SELECT * FROM stats_dialogue ORDER BY name");
  while ($row = pg_fetch_assoc($query)) {
  	$name = str_replace("Dialogue", "", $row["name"]);
  	echo printOption($row["id"], $name, $f_attack);
  }
  ?>
   </select></td>
 </tr>
 <tr id="what_3" name="what_3">
  <td class="datatd">Virus: </td>
  <td class="datatd"><select name="f_virus" style="background-color:white;">
  <?
  echo printOption(-1, "", $f_virus);
  $query = pg_query("SELECT * FROM stats_virus ORDER BY name");
  while ($row = pg_fetch_assoc($query)) {
  	echo printOption($row["id"], $row["name"], $f_virus);
  }
  ?>
   </select><br>
   <u><b>or</b></u> match: <input type="text" name="f_virus_txt" value="<?=$f_virus_txt;?>"> *
   </td>
 </tr>
 <tr id="what_4" name="what_4">
  <td class="datatd">Filename:</td>
  <td class="datatd"><input type="text" name="f_filename" value="<?=$f_filename;?>" /> *</td>
 </tr>
 <tr id="what_5" name="what_5">
  <td class="datatd">Binary:</td>
  <td class="datatd"><input type="text" name="f_bin" value="<?=$f_bin;?>" /> *</td>
 </tr>
 <tr id="what_c2" name="what_c2" style="display:none;">
  <td class="datatd">Chart type:</td>
  <td class="datatd">
   <select name="f_chart_type" style="background-color:white;">
    <option value="0">Pie</option>
    <option value="1">Horizontal bar</option>
    <option value="2">Vertical bar</option>
   </select>
  </td>
 </tr>
 </div>
 <tr>
   <td colspan=2 align="right"><br /><input type="hidden" name="c" value=0><input type="hidden" name="searchtemplate_title" id="searchtemplate_title" value=""><input type="button" name="f_submit_template" value="Save as template" class="button" style="cursor:pointer;" onclick="submitSearchTemplate();" /><input type="submit" name="f_submit" value="Search" class="button" style="cursor:pointer;" /></td>
 </tr>
</table>
</form>
<p>*) Wildcard is %</p>

<div id="searchtemplate">
<div id="searchtemplate_form">
  <?
	// get user searchtemplates
	$userid = intval($_SESSION["s_userid"]);
	//if ($userid == 49) $userid = 1;
	// userid 0 => default searchtemplates
	$sql = "SELECT * FROM searchtemplate WHERE userid = '" . intval($userid) . "' ORDER BY title";
	echo "<h3>Personal searchtemplates</h3>\n";
	showSearchTemplates($sql);
	echo "</div>\n";
	echo "<a href=\"/searchtemplate.php?action=admin\" style=\"margin-left:132px;\">Searchtemplate administration</a>\n";
	echo "<br /><br /><br /><br />\n";
	echo "<div id=\"searchtemplate_form\">\n";
	echo "<h3>Default searchtemplates</h3>\n";
	$sql = "SELECT * FROM searchtemplate WHERE userid = '0' ORDER BY title";
	showSearchTemplates($sql);
	  ?>
</div>
</div>
<?
   $sql_templates = "SELECT * FROM search_templates";
    $result_templates = pg_query($pgconn, $sql_templates);
    
    echo "</td>\n";
    echo "<td width='50'></td>\n";
    echo "<td valign='top'>\n";
#      echo "<table class='datatable'>\n";
#        echo "<tr>\n";
#          echo "<td width='450'><h4>Templates</h4></td>\n";
#        echo "</tr>\n";
#        echo "<tr>\n";
#          echo "<td>\n";
#            echo "<select name='templates' style='width: 300px;'>\n";
#              while ($row = pg_fetch_assoc($result_templates)) {
#                $id = $row['id'];
#                $name = $row['name'];
#                echo "<option value='$id'>$name</option>\n";
#              }
#            echo "</select>\n";
#          echo "</td>\n";
#        echo "</tr>\n";
#      echo "</table>\n";
    echo "</td>\n";
  echo "</tr>\n";
echo "</table>\n";
?>
<script type="text/javascript">
function change_form(index) {
	if (index == 0) {
		document.getElementById('what_c1').style.display='none';
		document.getElementById('what_c2').style.display='none';
		document.getElementById('what_1').style.display='';
		document.getElementById('what_2').style.display='';
		document.getElementById('what_3').style.display='';
		document.getElementById('what_4').style.display='';
		document.getElementById('what_5').style.display='';
	}
	if (index == 1) {
		document.getElementById('what_c1').style.display='none';
		document.getElementById('what_c2').style.display='none';
		document.getElementById('what_1').style.display='';
		document.getElementById('what_2').style.display='';
		document.getElementById('what_3').style.display='';
		document.getElementById('what_4').style.display='';
		document.getElementById('what_5').style.display='';
	}
	if (index == 2) {
		document.getElementById('what_c1').style.display='';
		document.getElementById('what_c2').style.display='';
		document.getElementById('what_1').style.display='none';
		document.getElementById('what_2').style.display='none';
		document.getElementById('what_3').style.display='none';
		document.getElementById('what_4').style.display='none';
		document.getElementById('what_5').style.display='none';
	}
	if (index == 3) {
		document.getElementById('what_c1').style.display='none';
		document.getElementById('what_c2').style.display='';
		document.getElementById('what_1').style.display='none';
		document.getElementById('what_2').style.display='';
		document.getElementById('what_3').style.display='none';
		document.getElementById('what_4').style.display='none';
	}
}
function catcalc(cal) {
    var date = cal.date;
    var time = date.getTime()
    // use the _other_ field
    var field = document.getElementById("ts_end");
    time += Date.WEEK; // add one week
    var date2 = new Date(time);
    field.value = date2.print("%d-%m-%Y %H:%M");
}

  Calendar.setup(
    {
      inputField  : "ts_start",
      ifFormat    : "%d-%m-%Y %H:%M",
      button      : "ts_start_trigger",
      showsTime   : true,
      singleClick : false,
      onUpdate    : catcalc
    }
  );
  Calendar.setup(
    {
      inputField  : "ts_end",
      ifFormat    : "%d-%m-%Y %H:%M",
      button      : "ts_end_trigger",
      showsTime   : true,
      singleClick : false
    }
  );
</script>

<?php footer(); ?>

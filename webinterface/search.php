<?php include("menu.php"); set_title("Search");

####################################
# SURFnet IDS                      #
# Version 1.04.10                  #
# 06-07-2007                       #
# Peter Arts                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.04.10 Removed PDF option
# 1.04.09 Removed chartof stuff
# 1.04.08 Removed libchart stuff & fixed sensors query
# 1.04.07 Fixed a bug with the destination radiobutton
# 1.04.06 Changed strip_html_escape_bin to strip_html_escape_binname
# 1.04.05 Changed data input handling
# 1.04.04 Query tuning
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

<form method="get" action="logsearch.php" name="searchform" id="searchform">

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
	if ($s_admin == 1) $where = " ";
	else $where = " AND sensors.organisation = '$s_org'";

    $sql = "SELECT COUNT(*) FROM sensors WHERE 1=1 $where";
    $debuginfo[] = $sql;
	$query = pg_query($sql);
	$nr_rows = intval(@pg_result($query, 0));
	if ($nr_rows < $select_size) $select_size = ($nr_rows + 1);
	if ($nr_rows > 1) {
		echo "<tr>\n";
  		echo "  <td class=\"datatd\" width=140>Sensor:</td>\n";
		echo "  <td class=\"datatd\">\n";
		echo "<select name=\"sensorid[]\" style=\"background-color:white;\" size=\"" . $select_size . "\" multiple=\"true\">\n";
                  echo printOption(0, "All sensors", $sensorid);
	    $sql = "SELECT sensors.id, sensors.keyname, sensors.vlanid, organisations.organisation FROM sensors, organisations ";
            $sql .= "WHERE organisations.id = sensors.organisation $where ORDER BY sensors.keyname";
            $debuginfo[] = $sql;
		$query = pg_query($sql);
		while ($sensor_data = pg_fetch_assoc($query)) {
			$sid = $sensor_data['id'];
			$label = $sensor_data["keyname"];
			$vlanid = $sensor_data["vlanid"];
			$org = $sensor_data["organisation"];
			if ($vlanid != 0 ) {
			  $label .=  "-" .$vlanid;
			}
			if ($s_admin == 1) {
	                        $label .= " (" .$org. ")";
				// get organisation name
#                                $sql = "SELECT organisation FROM organisation WHERE id = '" . intval($sensor_data["organisation"]) . "'";
#                                $debuginfo[] = $sql;
#				$query_org = pg_query($sql);
#				$org = pg_result($query_org, 0);
#				$label .= " (" . $org . ")";
			}
			
			  echo printOption($sid, $label, $sensorid);
		}
		echo "</select>\n";
		echo "  </td>\n";
		echo " </tr>\n";
	}
    ?>
 <tr>
  <td class="datatd" width=140>Source:</td>
  <td class="datatd">
    <input type="radio" name="sradio" id="s_radioA" value="A" checked onclick="document.getElementById('source_A').style.display='';document.getElementById('source_N').style.display='none';"> 
     <label for="s_radioA" style="cursor:pointer;">Address:Port</label> &nbsp; 
    <input type="radio" name="sradio" id="s_radioN" value="N" onclick="document.getElementById('source_N').style.display='';document.getElementById('source_A').style.display='none';"> 
     <label for="s_radioN" style="cursor:pointer;">Network</label><br />
    <input type="text" name="sourceip[]" id="source_ip1" maxlength=3 style="width:30px;" onKeyUp="check_byte(this, 'source_ip2');" /> . 
    <input type="text" name="sourceip[]" id="source_ip2" maxlength=3 style="width:30px;" onKeyUp="check_byte(this, 'source_ip3');" /> . 
    <input type="text" name="sourceip[]" id="source_ip3" maxlength=3 style="width:30px;" onKeyUp="check_byte(this, 'source_ip4');" />. 
    <input type="text" name="sourceip[]" id="source_ip4" maxlength=3 style="width:30px;" onKeyUp="check_byte(this, '');" />
    <font id="source_A" name="source_A">
      : <input type="text" name="int_sport" style="width:80px;" />
    </font>
    <font id="source_N" name="source_N" style="display:none;">
      / <input type="text" name="int_smask" maxlength=3 style="width:40px;">
    </font>
   </td>
 </tr>
 <tr>
  <td class="datatd">Destination:</td>
  <td class="datatd">
    <input type="radio" name="dradio" id="d_radioA" value="A" checked onclick="document.getElementById('destination_A').style.display='';document.getElementById('destination_N').style.display='none';">
      <label for="d_radioA" style="cursor:pointer;">Address:Port</label>&nbsp;
    <input type="radio" name="dradio" id="d_radioN" value="N" onclick="document.getElementById('destination_N').style.display='';document.getElementById('destination_A').style.display='none';">
      <label for="d_radioN" style="cursor:pointer;">Network</label><br />
    <input type="text" name="destip[]" id="destination_ip1" maxlength=3 style="width:30px;" onKeyUp="check_byte(this, 'destination_ip2');" /> . 
    <input type="text" name="destip[]" id="destination_ip2" maxlength=3 style="width:30px;" onKeyUp="check_byte(this, 'destination_ip3');" /> . 
    <input type="text" name="destip[]" id="destination_ip3" maxlength=3 style="width:30px;" onKeyUp="check_byte(this, 'destination_ip4');" />. 
    <input type="text" name="destip[]" id="destination_ip4" maxlength=3 style="width:30px;" onKeyUp="check_byte(this, '');" />
    <font id="destination_A">
      : <input type="text" name="int_dport" style="width:80px;" />
    </font>
    <font id="destination_N" style="display:none;">
      / <input type="text" name="int_dmask" maxlength=3 style="width:40px;">
    </font>
   </td>
 </tr>
 <tr>
  <td colspan=2><h4>When</h4></td>
 </tr>
 <tr>
  <td class="datatd">Select:</td>
  <td class="datatd">
   <select name="tsselect" style="background-color:white;">
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
  <td class="datatd"><input type="text" name="strip_html_escape_tsstart" id="ts_start" value="<?=$ts_start;?>" /> <input type="button" value="..." name="ts_start_trigger" id="ts_start_trigger" /></td>
 </tr>
 <tr>
  <td class="datatd">And: </td>
  <td class="datatd"><input type="text" name="strip_html_escape_tsend" id="ts_end" value="<?=$ts_end;?>" /> <input type="button" value="..." name="ts_end_trigger" id="ts_end_trigger" /></td>
 </tr>
 <tr>
  <td colspan=2><h4>How</h4></td>
 </tr>
 <tr>
  <td class="datatd">Report type:</td>
  <td class="datatd">
   <select name="reptype" style="background-color:white;" onchange="change_form(this.selectedIndex);">
    <option value="multi">Multi page</option>
    <option value="single">Single page</option>
    <option value="idmef">IDMEF</option>
   </select>
  </td>
 </tr>
 <tr>
  <td colspan=2><h4>What</h4></td>
 </tr>
 <tr id="what_1" name="what_1">
  <td class="datatd">Severity: </td>
  <td class="datatd"><select name="int_sev" style="background-color:white;">
  <?
  $f_sev = -1;
  echo printOption(-1, "", $f_sev);
  foreach ( $v_severity_ar as $index=>$severity ) echo printOption($index, $severity, $f_sev);
  ?>
   </select></td>
 </tr>
 <tr id="what_2" name="what_2">
  <td class="datatd">Attack: </td>
  <td class="datatd"><select name="int_attack" style="background-color:white;">
  <?
  echo printOption(-1, "All attacks", $f_attack);
  $sql = "SELECT * FROM stats_dialogue ORDER BY name";
  $debuginfo[] = $sql;
  $query = pg_query($sql);
  while ($row = pg_fetch_assoc($query)) {
  	$name = str_replace("Dialogue", "", $row["name"]);
  	echo printOption($row["id"], $name, $f_attack);
  }
  ?>
   </select></td>
 </tr>
 <tr id="what_3" name="what_3">
  <td class="datatd">Virus: </td>
  <td><input type="text" name="strip_html_escape_virustxt" value="<?=$f_virus_txt;?>"> *</td>
 </tr>
 <tr id="what_4" name="what_4">
  <td class="datatd">Filename:</td>
  <td class="datatd"><input type="text" name="strip_html_escape_filename" value="<?=$f_filename;?>" /> *</td>
 </tr>
 <tr id="what_5" name="what_5">
  <td class="datatd">Binary:</td>
  <td class="datatd"><input type="text" name="strip_html_escape_binname" value="<?=$f_bin;?>" /> *</td>
 </tr>
 <tr id="what_c2" name="what_c2" style="display:none;">
  <td class="datatd">Chart type:</td>
  <td class="datatd">
   <select name="int_charttype" style="background-color:white;">
    <option value="0">Pie</option>
    <option value="1">Horizontal bar</option>
    <option value="2">Vertical bar</option>
   </select>
  </td>
 </tr>
 </div>
 <tr>
   <td colspan=2 align="right"><br /><input type="hidden" name="int_c" value=0><input type="submit" name="submit" value="Show" class="button" style="cursor:pointer;" /></td>
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
        $debuginfo[] = $sql;
	echo "<h3>Personal searchtemplates</h3>\n";
	showSearchTemplates($sql);
	echo "</div><br />\n";
	echo "<input type='button' value='Searchtemplate administration' class='button' onClick=window.location='searchtemplate.php?action=admin';>\n";
	echo "<br /><br /><br /><br />\n";
	echo "<div id=\"searchtemplate_form\">\n";
	echo "<h3>Default searchtemplates</h3>\n";
	$sql = "SELECT * FROM searchtemplate WHERE userid = '0' ORDER BY title";
        $debuginfo[] = $sql;
	showSearchTemplates($sql);
	  ?>
</div>
</div>
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
<?php
debug_sql();
?>
<?php footer(); ?>

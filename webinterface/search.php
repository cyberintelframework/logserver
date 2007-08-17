<?php include("menu.php");

####################################
# SURFnet IDS                      #
# Version 1.04.13                  #
# 21-06-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################
# Contributors:                    #
# Peter Arts                       #
####################################

#############################################
# Changelog:
# 1.04.13 Fixed some autocomplete stuff
# 1.04.12 Changed source IP and destination IP address search fields
# 1.04.11 Removed PDF option (is being redone)
# 1.04.10 Added autocomplete function
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
<? if ($c_autocomplete == 1) { ?>
  <script type="text/javascript" src="maps.php?map=search"></script>
<? } ?>

<div id='normalsearch' style='display: inline;'>
<?php
  set_title("Search");
?>
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
			}
                        echo printOption($sid, $label, $sensorid);
		}
		echo "</select>\n";
		echo "  </td>\n";
		echo " </tr>\n";
	}
    ?>
 <tr><td>&nbsp;</td></tr>
 <tr style='height: 27px;'>
   <td class='datatd'>Source MAC:</td>
   <td class='datatd'>
   <? if ($c_autocomplete == 1) { ?>
     <span class='autocomplete_top'>
       <span class='autocomplete_dis'>
         <input type='text' id='mac_sourcemacdis' name='mac_sourcemacdis' value='' class='autocomplete_field_dis' disabled />
       </span>
       <span class='autocomplete'>
          <input type='text' id='mac_sourcemac' name='mac_sourcemac' autocomplete='off' class='autocomplete_field' value='' onfocus='own_autocomplete(this.id, smacmap);' onkeyup='own_autocomplete(this.id, smacmap);' onblur='fill_autocomplete(this.id);' />
       </span>
     </span>
     <input type='hidden' name='mac_sourcemachid' value='' id='mac_sourcemachid' />
   <? } else { ?>
     <input type='text' id='mac_sourcemac' name='mac_sourcemac' value='' />
   <? } ?>
   </td>
 </tr>
 <tr>
  <td class="datatd" width=140>Source IP:</td>
  <td class="datatd">
  <? if ($c_autocomplete == 1) { ?>
    <span class='autocomplete_top'>
      <span class='autocomplete_dis'>
        <input type='text' id='ip_sourceipdis' name='ip_sourceipdis' value='' class='autocomplete_field_dis' disabled />
      </span>
      <span class='autocomplete'>
        <input autocomplete='off' type='text' id='ip_sourceip' name='ip_sourceip' class='autocomplete_field' value='' onfocus='own_autocomplete(this.id, samap);' onkeyup='own_autocomplete(this.id, samap);' onblur='fill_autocomplete(this.id);' />
      </span>
    </span>
    <input type='hidden' name='ip_sourceiphid' id='ip_sourceiphid' value='' />
  <? } else { ?>
    <input type='text' id='ip_sourceip' name='ip_sourceip' maxlenght=18 />
  <? } ?>
  </td>
 </tr>
 <tr>
   <td class='datatd'>Source port:</td>
   <td class='datatd'><input type="text" name="int_sport" size='5' /></td>
 </tr>
 <tr><td>&nbsp;</td></tr>
 <tr style='height: 27px;'>
   <td class='datatd'>Destination MAC:</td>
   <td class='datatd'>
   <? if ($c_autocomplete == 1) { ?>
     <span class='autocomplete_top'>
       <span class='autocomplete_dis'>
         <input type='text' id='mac_destmacdis' name='mac_destmacdis' value='' style='background-color: #fff;' disabled />
       </span>
       <span class='autocomplete'>
          <input type='text' id='mac_destmac' name='mac_destmac' autocomplete='off' class='autocomplete_field' value='' onfocus='own_autocomplete(this.id, tmacmap);' onkeyup='own_autocomplete(this.id, tmacmap);' onblur='fill_autocomplete(this.id);' />
       </span>
     </span>
     <input type='hidden' name='mac_destmachid' value='' id='mac_destmachid' />
   <? } else { ?>
     <input type='text' id='mac_destmac' name='mac_destmac' value='' />
   <? } ?>
   </td>
 </tr>
 <tr>
  <td class="datatd">Destination IP:</td>
  <td class="datatd">
  <? if ($c_autocomplete == 1) { ?>
    <span class='autocomplete_top'>
      <span class='autocomplete_dis'>
        <input type='text' id='ip_destipdis' name='ip_destipdis' value='' class='autocomplete_field_dis' disabled />
      </span>
      <span class='autocomplete'>
        <input type='text' id='ip_destip' name='ip_destip' autocomplete='off' class='autocomplete_field' value='' onfocus='own_autocomplete(this.id, damap);' onkeyup='own_autocomplete(this.id, damap);' onblur='fill_autocomplete(this.id);' />
      </span>
    </span>
    <input type='hidden' name='ip_destiphid' id='ip_destiphid' value='' />
  <? } else { ?>
    <input type='text' id='ip_destip' name='ip_sourceip' maxlenght=18 />
  <? } ?>
  </td>
 </tr>
 <tr>
   <td class='datatd'>Destination port:</td>
   <td class='datatd'><input type="text" name="int_dport" size='5' /></td>
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
 <tr id="what_1" name="what_1">
  <td class="datatd">Attack type: </td>
  <td class="datatd"><select name="int_sevtype" style="background-color:white;">
  <?
  $f_sevtype = -1;
  echo printOption(-1, "", $f_sevtype);
  foreach ( $v_severity_atype_ar as $index=>$sevtype ) echo printOption($index, $sevtype, $f_sevtype);
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
  <td class='datatd'>
    <? if ($c_autocomplete == 1) { ?>
      <span class='autocomplete_top'>
        <span class='autocomplete_dis'>
          <input type='text' id='strip_html_escape_virustxtdis' name='strip_html_escape_virustxtdis' value='' class='autocomplete_field_dis' disabled />
        </span>
        <span class='autocomplete'>
          <input type='text' id='strip_html_escape_virustxt' name='strip_html_escape_virustxt' autocomplete='off' class='autocomplete_field' value='' onfocus='own_autocomplete(this.id, virusmap);' onkeyup='own_autocomplete(this.id, virusmap);' onblur='fill_autocomplete(this.id);' />
        </span>
      </span>
      <input type='hidden' name='strip_html_escape_virustxthid' value='' id='strip_html_escape_virustxthid' /> *
    <? } else { ?>
      <input type='text' name='strip_html_escape_virustxt' value='' /> *
    <? } ?>
  </td>
 </tr>
 <tr id="what_4" name="what_4">
  <td class="datatd">Filename:</td>
  <td class="datatd">
    <? if ($c_autocomplete == 1) { ?>
      <span class='autocomplete_top'>
        <span class='autocomplete_dis'>
          <input type='text' id='strip_html_escape_filenamedis' name='strip_html_escape_filenamedis' value='' class='autocomplete_field_dis' disabled />
        </span>
        <span class='autocomplete'>
          <input type='text' id='strip_html_escape_filename' name='strip_html_escape_filename' autocomplete='off' class='autocomplete_field' value='' onfocus='own_autocomplete(this.id, filemap);' onkeyup='own_autocomplete(this.id, filemap);' onblur='fill_autocomplete(this.id);' />
        </span>
      </span>
      <input type='hidden' name='strip_html_escape_filenamehid' value='' id='strip_html_escape_filenamehid' /> *
    <? } else { ?>
      <input type='text' name='strip_html_escape_filename' value='' /> *
    <? } ?>
  </td>
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
</div>

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

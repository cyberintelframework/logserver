/*
 * ####################################
 * # SURFids 3.00                     #
 * # Changeset 008                    #
 * # 10-07-2009                       #
 * # Jan van Lith & Kees Trippelvitz  #
 * ####################################
 *
 * #############################################
 * # Changelog:
 * # 008 Fxied bug #155
 * # 007 Fixed bug #152
 * # 006 Changed setperiod. Removed submit, added window.location
 * # 005 Added Last 24 Hours option for timeselector
 * # 004 Added support for Nepenthes mail markup
 * # 003 Added selector functions
 * # 002 Fixed bug with Critera field in logsearch
 * # 001 version 3.00
 * #############################################
 */

/***********************************
 * Trim functions
 ***********************************/

function trim(str, chars) {
  return ltrim(rtrim(str, chars), chars);
}

function ltrim(str, chars) {
  chars = chars || "\\s";
  return str.replace(new RegExp("^[" + chars + "]+", "g"), "");
}

function rtrim(str, chars) {
  chars = chars || "\\s";
  return str.replace(new RegExp("[" + chars + "]+$", "g"), "");
}

/***********************************
 * Timer functions
 ***********************************/

function tS() {
  var x=new Date();
  x.setTime(x.getTime());
  return x;
}
function lZ(x) {
  return (x>9)?x:'0'+x;
}
function dT() {
  $('#tP').html(oT);
  setTimeout('dT()',1000);
}
function y4(x) {
  return (x<500)?x+1900:x;
}

var dN = new Array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
var mN = new Array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');
var oT = dN[tS().getDay()]+' '+tS().getDate()+' '+mN[tS().getMonth()]+' '+y4(tS().getYear())+' '+lZ(tS().getHours())+':'+lZ(tS().getMinutes());
if (!document.all) {
  window.onload=dT;
} else {
  dT();
}

function startclock() {
  status = $('#sensor_status').val();
  if (status != 0 && status != 3) {
    var uptime = $("#js_hiduptime").val() - 0;
    uptime = uptime + 1;
    $("#js_hiduptime").val(uptime);
    uptime = sec_to_string(uptime);
    $("#js_uptime").html(uptime);
    setTimeout('startclock()',1000);
  }
}

/***********************************
 * Tab menu functions
 ***********************************/

function showtab(selected) {
  var i = 1;
  $('.tab').each(function(item){
    if (i == selected) {
      $("#tab" + i).show();
      $("#sel" + i).addClass("selected");
    } else {
      $("#tab" + i).hide();
      $("#sel" + i).removeClass("selected");
    }
    i++;
  });
}

/***********************************
 * Mail reporting functions
 ***********************************/

function sh_mailtemp(si) {
  $('#int_detail').selectOptions("0");
  $('#int_sdetail').selectOptions("0");
  $('#filter').hide();
  $('#timeandthresh').show();
  if (si == 4) {
    $('#attack_sev').hide();
    $('#attack_time').hide();
    $('#thresh_freq').hide();
    $('#timeoptions').hide();
    $('#repdetail').hide();
    $('#timestamps').hide();
    $('#srepdetail').show();
    $('#sensor_time').show();
    $('#sensor_sev').show();
  } else if (si == 5) {
    $('#attack_sev').hide();
    $('#sensor_sev').hide();
    $('#sensor_time').hide();
    $('#timeoptions').hide();
    $('#repdetail').hide();
    $('#srepdetail').hide();
    $('#timestamps').hide();
    $('#attack_time').show();
  } else if (si == 7) {
    $('#attack_sev').hide();
    $('#sensor_sev').hide();
    $('#timestamps').hide();
    $('#sensor_time').hide();
    $('#timeoptions').hide();
    $('#repdetail').hide();
    $('#srepdetail').hide();

    $('#attack_time').show();
  } else {
    $('#srepdetail').hide();
    $('#sensor_sev').hide();
    $('#sensor_time').hide();

    $('#attack_sev').show();
    $('#attack_time').show();
    $('#timeoptions').show();
    $('#repdetail').show();
    $('#timestamps').show();
  }
}

function sh_mailreptype(si) {
  if (si < 10) {
    if (si < 4) {
      $('#filter').hide();
      $('#timestamps').hide();
      $('#timeandthresh').show();
      $('#attack_sev').show();
    } else if (si == 4 || si == 5) {
      $('#attack_sev').hide();
      $('#timeandthresh').show();
      $('#int_template').selectOptions(1);
      $('#timestamps').show();
      rep = $('#int_template').val();
      if (rep == 2) {
        $('#filter').hide();
      } else {
        $('#filter').show();
      }
    }
  } else {
    $('#timeandthresh').hide();
    $('#filter').hide();
    $('#timestamps').hide();
    $('#attack_sev').show();
  }
}

function sh_mailfreq(si) {
  if (si == 1) {
    $('#daily_freq').hide();
    $('#weekly_freq').hide();
    $('#thresh_freq').hide();
    $('#always').show();
  } else if (si == 2) {
    $('#weekly_freq').hide();
    $('#thresh_freq').hide();
    $('#daily_freq').show();
    $('#always').show();
  } else if (si == 3) {
    $('#daily_freq').hide();
    $('#thresh_freq').hide();
    $('#weekly_freq').show();
    $('#always').show();
  } else if (si == 4) {
    $('#daily_freq').hide();
    $('#weekly_freq').hide();
    $('#always').hide();
    $('#thresh_freq').show();
  } 
}

/***********************************
 * Popup functions
 ***********************************/

function getScrollSize(){
  var xScroll, yScroll;
  if (window.innerHeight) {
    /* Firefox 5.00 tested & Opera */
    if (document.body.offsetHeight > window.innerHeight) {
      yScroll = document.body.offsetHeight + "px";
    } else if (window.scrollMaxY) {
      yScroll = window.innerHeight + window.scrollMaxY;
      yScroll = yScroll + "px";
    } else {
      yScroll = "100%";
    }
  } else {
    // Explorer Mac...would also work in Explorer 6 Strict, Mozilla and Safari
    if (document.body.offsetHeight < screen.availHeight) {
      yScroll = "100%";
    } else {
      yScroll = document.body.offsetHeight + (screen.height - screen.availHeight);
      yScroll = yScroll + "px";
    }
  }
  window.scrollTo(0,0);
  return yScroll;
}

function popit(url, h, w) {
  var wh = getScrollSize();
  $("#popupcontent").load(url);

  if (h !== null && w !== null) {
    $("#popup").height(h);
    $("#popup").width(w);
  }
  $("#popup").show();
  $("#overlay").show();
  return false;
}

function popout() {
  $("#popup").hide();
  $("#error").hide();
  $("#overlay").hide();
  $("#popupcontent").html('Loading...');
}

function poperr(str,h,w) {
  var wh = getScrollSize();
  $("#error").html(str);

  if (h !== null && w !== null) {
    $("#error").height(h);
    $("#error").width(w);
  }
  $("#error").show();
  $("#overlay").show();

  $("#overlay").height(wh);
  return false;
}

/***********************************
 * Password functions
 ***********************************/

function encrypt_pass() {
  var password1 = document.usermodify.elements[1].value;
  var password2 = document.usermodify.elements[3].value;
  if (password1 == password2) {
    if (password1 != "" || (password1 == "" && password2 == "")) {
      if (password1 != '') {
        var pass1 = document.usermodify.elements[2].value=hex_md5(document.usermodify.elements[1].value);
      }
      if (password2 != '') {
        var pass2 = document.usermodify.elements[4].value=hex_md5(document.usermodify.elements[3].value);
      }
      return true;
    } else {
      alert('Password field was empty!');
      document.usermodify.elements[1].value = "";
      document.usermodify.elements[2].value = "";
      document.usermodify.elements[3].value = "";
      document.usermodify.elements[4].value = "";
      return false;
    }
  } else {
    alert('Passwords did not match');
    document.usermodify.elements[1].value = "";
    document.usermodify.elements[2].value = "";
    document.usermodify.elements[3].value = "";
    document.usermodify.elements[4].value = "";
    return false;
  }
}

function generatep() {
  var pass = hex_md5(document.login.elements[0].value);
  var serverhash = document.login.elements[1].value;
  var check = pass + serverhash;

  document.login.md5_pass.value = hex_md5(check);
}

/***********************************
 * Selector functions
 ***********************************/

function browse(dir) {
  $("#selector_dir").val(dir);
  submitPeriod();
}

function setperiod(startofweek) {
  var period = $("#selperiod").val();
  var start = new Date();
  var end = new Date();

  if (period == 0) {
    start.setHours(start.getHours()-1);
  } else if (period == 1) {
    start.setHours(start.getHours()-24);
  } else if (period == 2) {
    start.setHours(0);
    start.setMinutes(0);
    start.setSeconds(0);
    end.setHours(0);
    end.setMinutes(0);
    end.setSeconds(0);
    end.setDate(end.getDate()+1);
  } else if (period == 3) {
    start.setDate(start.getDate()-7);
  } else if (period == 4) {
    start.setHours(0);
    start.setMinutes(0);
    start.setSeconds(0);
    end.setHours(0);
    end.setMinutes(0);
    end.setSeconds(0);
    var d = start.getDay();
    var d = d - startofweek;
    var d = start.getDate() - d;
    start.setDate(d);
    end.setDate(d + 7);
  } else if (period == 5) {
    start.setHours(0);
    start.setMinutes(0);
    start.setSeconds(0);
    end.setHours(0);
    end.setMinutes(0);
    end.setSeconds(0);
    var d = start.getDay();
    var d = d - startofweek;
    var d = start.getDate() - d;
    var d = d - 7;
    start.setDate(d);
    end.setDate(d + 7);
  } else if (period == 6) {
    start.setHours(0);
    start.setMinutes(0);
    start.setSeconds(0);
    start.setDate(1);
    end.setHours(0);
    end.setMinutes(0);
    end.setSeconds(0);
    end.setDate(1);
    end.setMonth(end.getMonth()+1);
  } else if (period == 7) {
    start.setHours(0);
    start.setMinutes(0);
    start.setSeconds(0);
    start.setMonth(start.getMonth()-1);
    start.setDate(1);
    end.setHours(0);
    end.setMinutes(0);
    end.setSeconds(0);
    end.setDate(1);
  } else if (period == 8) {
    start.setHours(0);
    start.setMinutes(0);
    start.setSeconds(0);
    start.setDate(1);
    start.setMonth(0);
    end.setHours(0);
    end.setMinutes(0);
    end.setSeconds(0);
    end.setDate(1);
    end.setMonth(0);
    end.setFullYear(end.getFullYear()+1);
  }

  $("#int_from").val(start.print("%s"));
  $("#int_to").val(end.print("%s"));
  $("#showdate_start").html(start.print("%d-%m-%Y %H:%M"));
  $("#showdate_end").html(end.print("%d-%m-%Y %H:%M"));

  submitPeriod();
}

function submitPeriod() {
  url = window.location + "";
  url = url.split("?", 1);
  qs = window.location.search.substring(1);
  tmp = qs.replace(/(int_selperiod|int_org|int_to|int_from)=[0-9]*&?/g, "");
  tmp = tmp.replace(/(dir=.*)&/, "");
  tmp = tmp.replace(/(dir=.*)$/, "");
  tmp = tmp.replace(/&$/, "");
  periodqs = $("#fselector").serialize();
  if (tmp != "") {
    newreq = url + "?" + tmp;
    if (periodqs != "") {
      newreq = newreq + "&" + periodqs;
    }
  } else {
    newreq = url;
    if (periodqs != "") {
      newreq = newreq + "?" + periodqs;
    }
  } 
  window.location = newreq;
}

function closecal(cal) {
  if (cal.dateClicked) {
    var date_to = cal.date;

    var ts_from = $('#field_from').val();
    var ts_to = date_to.print("%s");
    if (ts_to < ts_from) {
      newto = ts_from;
      newfrom = ts_to;
      $('#int_to').val(newto);
      $('#int_from').val(newfrom);
    } else {
      $('#showdate_end').html(date_to.print("%d-%m-%Y %H:%M"));
      $('#int_to').val(date_to.print("%s"));
    }
    $('#selperiod').selectedIndex = 0;
    $('#fromcal').hide();
    $('#tocal').hide();
    submitPeriod();
  }
}

function shcals() {
  $('#fromcal').toggle();
  $('#tocal').toggle();
}

/***********************************
 * Search page functions
 ***********************************/

function sh_search_dest(si) {
  if (si == 1) {
    $('#destmac').hide(0);
    $('#sensor').hide(0);
    $('#dest').show();
    $('#mac_destmac').val('');
    $('#sensorid')[0].selectedIndex = 0;
  } else if (si == 2) {
    $('#sensor').hide(0);
    $('#dest').hide(0);
    $('#destmac').show();
    $('#sensorid')[0].selectedIndex = 0;
    $('#inet_dest').val('');
  } else if (si == 3) {
    $('#destmac').hide(0);
    $('#dest').hide(0);
    $('#sensor').show();
    $('#mac_destmac').val('');
    $('#inet_dest').val('');
  }
}

function sh_search_src(si) {
  if (si == 1) {
    $('#source').show();
    $('#sourcemac').hide();
    $('#mac_sourcemac').val('');
    $('#ownrange').hide();
    $('#inet_ownsource').val('');
  } else if (si == 2) {
    $('#source').hide();
    $('#inet_source').val('');
    $('#sourcemac').show();
    $('#ownrange').hide();
    $('#inet_ownsource').val('');
  } else if (si == 3) {
    $('#source').hide();
    $('#inet_source').val('');
    $('#sourcemac').hide();
    $('#mac_sourcemac').val('');
    $('#ownrange').show();
  }
}

function sh_search_charac(si) {
    $('#int_sevtype')[0].selectedIndex = 0;
    $('#int_attack')[0].selectedIndex = 0;
    $('#strip_html_escape_virustxt').val('');
    $('#strip_html_escape_filename').val('');
    $('#strip_html_escape_binname').val('');
  if (si == 0) {
    $('#sevtype').hide(0);
    $('#attacktype').hide(0);
    $('#virus').hide(0);
    $('#filename').hide(0);
    $('#binary').hide(0);
    $('#charac_details').hide(0);
  } else if (si == 1) {
    $('#attacktype').hide(0);
    $('#virus').hide(0);
    $('#filename').hide(0);
    $('#binary').hide(0);
    $('#charac_details').show();
    $('#sevtype').show();
  } else if (si == 16) {
    $('#sevtype').hide(0);
    $('#attacktype').hide(0);
    $('#virus').hide(0);
    $('#binary').hide(0);
    $('#filename').hide(0);
    $('#charac_details').show();
    $('#filename').show();
  } else if (si == 32) {
    $('#sevtype').hide(0);
    $('#attacktype').hide(0);
    $('#charac_details').show();
    $('#virus').show();
    $('#filename').show();
    $('#binary').show();
  }
}

function sh_search_charac_sevtype(si) {
  if (si == 0) {
    $('#attacktype').show();
  } else {
    $('#attacktype').hide();
    $('#int_attack')[0].selectedIndex = 0;
  }
}

/***********************************
 * Autocomplete functions
 ***********************************/

function searchSuggest(typesel) {
  var strdest = $('#inet_dest').val();
  var strdmac = $('#mac_destmac').val();
  var strsource = $('#inet_source').val();
  var strsmac = $('#mac_sourcemac').val();
  var strvirus = $('#strip_html_escape_virustxt').val();
  var strfile = $('#strip_html_escape_filename').val();
  if (typesel == 1) { str = strdest; id = 'inet_dest'; }
  if (typesel == 2) { str = strdmac; id = 'mac_destmac'; }
  if (typesel == 3) { str = strsource; id = 'inet_source'; }
  if (typesel == 4) { str = strsmac; id = 'mac_sourcemac'; }
  if (typesel == 5) { str = strvirus; id = 'strip_html_escape_virustxt'; }
  if (typesel == 6) { str = strfile; id = 'strip_html_escape_filename'; }
  $('#search_suggest_'+typesel).html('<div class="suggest_link">Searching...</div>');
  $('#search_suggest_'+typesel).fadeIn("slow");

  $.get("searchSuggest.php?search="+str+"&int_type="+typesel, function(data){
    $('#search_suggest_'+typesel).html('');
    var str = data.split("\n");
    var a = 0;
    for(i=0; i < str.length - 1; i++) {
      if (str[i] != '') {
        var suggest = '<div onmouseover="javascript:suggestOver(this);" ';
        suggest += 'onmouseout="javascript:suggestOut(this);" ';
        suggest += 'onclick="javascript:setSearch(this.innerHTML, '+typesel+', \''+id+'\');" ';
        suggest += 'class="suggest_link">' + str[i] + '</div>';
        $('#search_suggest_'+typesel).append(suggest);
        a++;
      }
    }
    var suggest = '<div onmouseover="javascript:suggestOver(this);" ';
    suggest += 'onmouseout="javascript:suggestOut(this);" ';
    suggest += 'onclick="javascript:closeSuggest('+typesel+');" ';
    suggest += 'class="suggest_link">Close this!</div>';
    $('#search_suggest_'+typesel).append(suggest);
    if (a == 0) {
      $('#search_suggest_'+typesel).html('Searching...');
      $('#search_suggest_'+typesel).hide();
    }
  });
}

function suggestOver(div_value) {
  div_value.className = 'suggest_link_over';
}

function suggestOut(div_value) {
  div_value.className = 'suggest_link';
}

function setSearch(value, typesel, id) {
  $('#'+id).val(value);
  if (typesel) { $('#search_suggest_'+typesel).html('<div class="suggest_link">Searching...</div>'); }
  $('#search_suggest_'+typesel).hide();
}

function closeSuggest(typesel) {
  $('#search_suggest_'+typesel).hide();
}

/***********************************
 * Misc functions
 ***********************************/

function popUp(URL, w, h) {
  day = new Date();
  id = day.getTime();
  window.open(URL, '" + id + "', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width='+w+',height='+h+',left=50%,top=50%');
}

function basename(path) { return path.replace( /.*\//, "" ); }

function sortNumber(a,b) {
  return a - b;
}

function sec_to_string(sec) {
  var onehour, oneday, days, hours, minutes, seconds;
  onehour = 60 * 60;
  oneday = onehour * 24;
  days = Math.floor(sec / oneday);
  sec = sec % oneday;
  hours = Math.floor(sec / onehour);
  sec = sec % onehour;
  minutes = Math.floor(sec / 60);
  seconds = sec % 60;
  sec = days + "d " + hours + "h " + minutes + "m " + seconds + "s";
  return sec;
}

function switch_page_conf() {
  id = $('#page_select').val();
  if (id == 0) {
    $('#page_sensorstatus').hide();
    $('#page_indexmods').show();
  } else {
    $('#page_indexmods').hide();
    $('#page_sensorstatus').show();
  }
}

function redirmap() {
  var val = $('#redirmapper').val();
  if (val == 1) {
    window.location='googlemap.php';
  } else {
    window.location='sensormap.php';
  }
}

/***********************************
 * Plotter functions
 ***********************************/

var myplots = new Array();
myplots[1] = "severity";
myplots[2] = "attacks";
myplots[3] = "ports";
myplots[4] = "os";
myplots[5] = "virus";

function shlinks(id) {
  for (i=1;i<myplots.length;i++) {
    var tab = myplots[i];
    var but = 'button_' + tab;
    if (tab == id) {
      $('#switch').val(i);
      $('#'+but).addClass('tabsel');
      $('#'+id).show();
    } else {
      $('#'+but).removeClass('tabsel');
      $('#'+tab).hide();
    }
  }
  $('#'+id).blur();
}

function sh_plotsevtype(id) {
  status = $("#plotsev_"+id).val();
  found = 0;
  for (var i=0; i < status.length; i++) {
    val = status[i];
    if (val == 1) {
      found = 1;
    }
  }
  if (found == 1) {
    $(".plotsevtype_"+id).show();
  } else {
    $(".plotsevtype_"+id).hide();
  }
}

function popimg(url,h,w,x,y) {
  var wh = getScrollSize();
  $("#popupcontent").html("<center><img src='"+url+"' /></center>\n");

  if (h !== null) {
    $("#popup").height(h);
  }
  if (w !== null) {
    $("#popup").width(w);
  }
  if (x !== null) {
    $("#popup").css("left", x);
  }
  if (y !== null) {
    $("#popup").css("top", y);
  }
  $("#popup").show();
  $("#overlay").show();

  $("#overlay").height(wh);
}

function buildqs() {
  var str = "";
  var sw = $('#switch').val();
  $("form:eq("+sw+") input").each(function(item){
    var name = $("form:eq("+sw+") input:eq("+item+")").attr("name");
    var val = $("form:eq("+sw+") input:eq("+item+")").val();
    var type = $("form:eq("+sw+") input:eq("+item+")").attr("type");
    $("form:eq("+sw+") input:eq("+item+")").blur();
    if (type == "checkbox") {
      var chk = $("form:eq("+sw+") input:eq("+item+")").attr("checked");
      if (chk) {
        if (str == "") {
          str = str+"?"+name+"="+val;
        } else {
          str = str+"&"+name+"="+val;
        }
      }
    } else {
      if (str == "") {
        str = str+"?"+name+"="+val;
      } else {
        str = str+"&"+name+"="+val;
      }
    }
  })
  $("form:eq("+sw+") select").each(function(item){
    var name = $("form:eq("+sw+") select:eq("+item+")").attr("name");
    var val = $("form:eq("+sw+") select:eq("+item+")").val();
    if (str == "") {
      str = str+"?"+name+"="+val;
    } else {
      str = str+"&"+name+"="+val;
    }
  })
  method = $('#int_method').val();
  if (str == "") {
    str = "?int_method="+method;
  } else {
    str = str+"&int_method="+method;
  }
  if (method == 0) {
    location.href="plotter.php"+str;
  } else if (method == 1) {
    popimg("showphplot.php"+str, 600, 1000, "11%");
  }
  return false;
}

/***********************************
 * Test AJAX functions
 ***********************************/

/* Generic AJAX helper functions */
/*********************************/

$.fn.clearForm = function() {
  // iterate each matching form
  return this.each(function() {
	// iterate the elements within the form
	$(':input', this).each(function() {
	  var type = this.type, tag = this.tagName.toLowerCase();
	  if (type == 'text' || type == 'password' || tag == 'textarea')
		this.value = '';
	  else if (type == 'checkbox' || type == 'radio')
		this.checked = false;
	  else if (tag == 'select')
		this.selectedIndex = 0;
	});
  });
};

function expand_edit(id, title, divid_block, divid_title, url, type) {
  var status = $('#'+divid_block).css("display");
  var item = $('#'+divid_title).text();
  $('.edit_id').val(id);
  if (item == title || item == '') {
    if (status == "none" || status == "inline") {
      $('.groupmember').remove();
      var titlecontent = $('#'+divid_title).text() + title;
      $('#'+divid_title).text(titlecontent);
      $('#'+divid_block).show();
      arequest(url, type);
    } else if (status == "block") {
      $('#'+divid_block).hide();
      $('.groupmember').remove();
      $('#'+divid_title).text("");
    }
  } else {
    $('#'+divid_title).text("");
    $('.groupmember').remove();
    var titlecontent = $('#'+divid_title).text() + title;
    $('#'+divid_title).text(titlecontent);
    arequest(url, type);
  }
}

function db_add_record(url, type, formid) {
  var qs = $('#'+formid).serialize();
  var url = url+'?'+qs;
  $('#'+formid).clearForm();

  arequest(url, type);
}

function db_del_record(url, type) {
  arequest(url, type);
}

function db_del_selected_cb(url, type, formid, name) {
  var retstr = '';
  var len = $('#'+formid).length;
  $('#'+formid+' :checked').each(function() {
    var val = $(this).val();
    retstr += val + ',';
  });

  var chk = retstr.substring(retstr.length-1, retstr.length);
  if (chk == ',') {
    retstr = retstr.substring(0, retstr.length-1);
  }

  var qs = $('#'+formid).serialize();
  var url = url + '?' + name + '=' + retstr + '&' + qs;
  arequest(url, type);
}

function setdefault(formid, url) {
  var qs = $('#'+formid).serialize();
  var url = url+'?'+qs;
  var type = '';
  arequest(url, type);
}

function process_aresult(data, type) {
  var result = $("result", data);
  var ec = result.find("status").text();
  if (ec == "OK") {
    if (type == "groupdel") {
      groupdel(result);
    } else if (type == "groupadd") {
      groupadd(result);
    } else if (type == "getgroupmembers") {
      get_group_members(result);
    } else if (type == "groupaddorg") {
      get_group_members(result);
    } else if (type == "groupaddsensor") {
      get_group_members(result);
    } else if (type == "groupmdel") {
      groupmdel(result);
    } else if (type == "groupdelsensors") {
      groupmsdel(result);
    } else if (type == "logsys") {
      get_logsys(result);
    } else if (type == "getcontact") {
      getcontact(result);
    }

    var err = result.find("error").text();
    if (err != "") {
      $.jGrowl(err, { life: 500, header: "Success" });
    }
  } else {
    var err = result.find("error").text();
    $.jGrowl(err, { sticky: true, header: "Error" });
  }
}

function arequest(url, type) {
  $.ajax({
    url: url,
    type: 'GET',
    dataType: 'xml',
    error: function(xmlobj){
      var err = 'Could not retrieve data!';
      $.jGrowl(err, { sticky: true, header: "Error" });
      var url_ar = url.split("?");
      url = url_ar[0];
      var err = 'Request for ' +url+ ' returned: ' +xmlobj.status;
      $.jGrowl(err, { sticky: true, header: "Error" });
    },
    success: function(data){
      process_aresult(data, type);
    }
  });
}

/* Group member functions */
/**************************/

function groupmdel(result) {
  var members = result.find("members");
  $("memberid", members).each(function () {
    var id = $(this).text();
    $('#sensor'+id).remove();
  });

  var group = result.find("data").find("group");
  var gid = group.attr("gid");
  var name = group.find("name").text();
  var owner = group.find("owner").text();
  var members = group.find("members").text();
  var hash = $('#md5_globalhash').val();

  var html = '<tr id="group' +gid+ '">';
    html += '<td>' +name+ '</td>';
    html += '<td>' +owner+ '</td>';
    html += '<td>' +members+ '</td>';
    html += '<td>';
      html += '[<a onclick="javascript: expand_edit(\''+gid+'\', \''+name+'\', \'edit_block\', \'edit_title\', \'groupmget.php?int_gid='+gid+'&md5_hash='+hash+'\', \'getgroupmembers\');">edit</a>]\n';
      html += '[<a onclick="javascript: db_del_record(\'groupdel.php?int_gid='+gid+'&md5_hash='+hash+'\', \'groupdel\');\">delete</a>]';
    html += '</td>\n';
  html += '</tr>\n';

  $('#group'+gid).replaceWith(html);
}

function get_group_members(result) {
  var group = result.find("data").find("group");
  if (group) {
    var gid = group.attr("gid");
    var name = group.find("name").text();
    var owner = group.find("owner").text();
    var members = group.find("members").text();
    var hash = $('#md5_globalhash').val();

    var html = '<tr id="group' +gid+ '">';
      html += '<td>' +name+ '</td>';
      html += '<td>' +owner+ '</td>';
      html += '<td>' +members+ '</td>';
      html += '<td>';
        html += '[<a onclick="javascript: expand_edit(\''+name+'\', \'edit_block\', \'edit_title\', \'groupmget.php?int_gid='+gid+'&md5_hash='+hash+'\', \'getgroupmembers\');">edit</a>]\n';
        html += '[<a onclick="javascript: db_del_record(\'groupdel.php?int_gid='+gid+'&md5_hash='+hash+'\', \'groupdel\');\">delete</a>]';
      html += '</td>\n';
    html += '</tr>\n';
    $('#group'+gid).replaceWith(html);
  }

  $("sensor", result).each(function() {
    var sensor = $(this);

    var sid = sensor.attr("sid");
    var name = sensor.attr("name");
    var hash = $('#md5_globalhash').val();

    var html = '<tr id="sensor' +sid+ '" class="groupmember">';
      html += '<td>' +name+ '</td>';
      html += '<td><input type="checkbox" value="'+sid+'" />';
    html += '</tr>\n';

    $('#edit_row').before(html);
  });
}

/* Group functions */
/*******************/

function groupdel(result) {
  var gid = result.find("id").text();
  $('#group' +gid).remove();
  $('#edit_block').hide();
}

function groupadd(result) {
  var data = result.find("data");

  var gid = data.find("gid").text();
  var name = data.find("name").text();
  var owner = data.find("owner").text();
  var members = data.find("members").text();
  var hash = $('#md5_globalhash').val();

  var html = '<tr id="group' +gid+ '">';
    html += '<td>' +name+ '</td>';
    html += '<td>' +owner+ '</td>';
    html += '<td>' +members+ '</td>';
    html += '<td>';
      html += '[<a onclick="javascript: expand_edit(\''+gid+'\', \''+name+'\', \'edit_block\', \'edit_title\', \'groupmget.php?int_gid='+gid+'&md5_hash='+hash+'\', \'getgroupmembers\');">edit</a>]\n';
      html += '[<a onclick="javascript: db_del_record(\'groupdel.php?int_gid='+gid+'&md5_hash='+hash+'\', \'groupdel\');\">delete</a>]';
    html += '</td>\n';
  html += '</tr>\n';
  $("#inputrow").before(html);
}

/* Logsys functions */
/********************/

function browsedata(dir, formid, url, type) {
  var offset = $("#int_offset").val() - 0;
  var limit = $("#int_limit").val() - 0;
  var total = $("#int_total").val() - 0;
  if (dir == "start") {
    $("#int_offset").val("0");
    if (offset != 0) {
      var qs = $('#'+formid).serialize();
      var url = url+'?'+qs;
      arequest(url, type);
    }
  } else if (dir == "prev") {
    var new_offset = offset - limit;
    if (new_offset < 0) {
      new_offset = 0;
    }
    $("#int_offset").val(new_offset);
    if (offset != new_offset) {
      var qs = $('#'+formid).serialize();
      var url = url+'?'+qs;
      arequest(url, type);
    }
  } else if (dir == "next") {
    var new_offset = offset + limit;
    if ((new_offset >= total) === false) {
      $("#int_offset").val(new_offset);
      var qs = $('#'+formid).serialize();
      var url = url+'?'+qs;
      arequest(url, type);
    }
  } else if (dir == "end") {
    if (total > limit) {
      var new_offset = total - limit;
      $("#int_offset").val(new_offset);
      if (offset != new_offset) {
        var qs = $('#'+formid).serialize();
        var url = url+'?'+qs;
        arequest(url, type);
      }
    }
  } else if (dir == "filter") {
    $("#int_offset").val("0");
    var qs = $('#'+formid).serialize();
    var url = url+'?'+qs;
    arequest(url, type);
  }
}

function get_logsys(result) {
  var data = result.find("data");
  var pagecounter = data.find("pagecounter").text();
  pagecounter = '<span id="pagecounter">' + pagecounter + '</span>';
  var total = data.find("total").text();
  $("#pagecounter").replaceWith(pagecounter);
  $("#int_total").val(total);
  var html = '';
  $(".syslogrow").remove();
  $("message", data).each(function() {
    var message = $(this);

    var level = message.find("level").text();
    var ts = message.find("ts").text();
    var source = message.find("source").text();
    var pid = message.find("pid").text();
    var msg = message.find("msg").text();
    var args = message.find("args").text();
    var dev = message.find("device").text();
    var sid = message.find("sid").text();
    var sensor = message.find("sensor").text();

    html += '<tr class="syslogrow">';
      html += '<td class="syslog_'+level+'">'+level+'</td>\n';
      html += '<td>'+ts+'</td>\n';
      html += '<td>'+source+' (' + pid + ')</td>\n';
      html += '<td>'+msg+'</td>\n';
      html += '<td>'+args+'</td>\n';
      html += '<td><a href="sensordetails.php?int_sid='+sid+'">'+sensor+'</a></td>\n';
      html += '<td>'+dev+'</td>\n';
    html += '</tr>';

  });
  $('#edit_row').before(html);
}

/* Sensorstatus functions */
/**************************/

function getcontact(result) {
  var data = result.find("data");
  var sensor = data.find("sensor").text();
  var html = '<div class="center">';
  html += '<table class="datatable">';
  html += '<tr><th>'+sensor+'</th></tr>';
  
  $("email", data).each(function() {
    var email = $(this).text();
    html += '<tr><td>'+email+'</td></tr>';
  });
  html += '</table></div>';
  $("#popupcontent").html(html);
  $("#popup").show();
}

function sensorlink(choice, sid) {
  if (choice == "arpcache") {
    var choice = "arp_cache.php?int_sid=" + sid;
  } else if (choice == "arpstatic") {
    var choice = "arp_static.php?int_sid=" + sid;
  } else if (choice == "detproto") {
    var choice = "detectedproto.php?int_sid=" + sid;
  } else if (choice == "sdetails") {
    var choice = "sensordetails.php?int_sid=" + sid;
  }
  window.location = choice;  
}

/*      HeaderTabs        */
/**************************/

function showHeaderTab(nr) {
    $(".headerTab").removeClass("headerTabSel");
    $("#headerTab"+nr).addClass("headerTabSel");
    $(".subContent").hide();
    $("#sub"+nr).show();
}

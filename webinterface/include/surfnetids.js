/*
 * ####################################
 * # SURFnet IDS                      #
 * # Version 2.10.05                  #
 * # 12-02-2008                       #
 * # Jan van Lith & Kees Trippelvitz  #
 * ####################################
 *
 * #############################################
 * # Changelog:
 * # 2.10.05 Added Last 24 Hours option for timeselector
 * # 2.10.04 Added support for Nepenthes mail markup
 * # 2.10.03 Added selector functions
 * # 2.10.02 Fixed bug with Critera field in logsearch
 * # 2.10.01 version 2.10
 * # 2.00.01 version 2.00
 * # 1.04.06 Added selector functions
 * # 1.04.05 Added sh_mailreptype()
 * # 1.04.04 Removed submitSearchTemplate()
 * # 1.04.03 Added sh_mail functions
 * # 1.04.02 Fixed searchtemplate url bug
 * # 1.04.01 Initial release
 * #############################################
 */

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
  var uptime = $("#js_hiduptime").val() - 0;
  var total = $("#js_hidtotal").val() - 0;
  uptime = uptime + 1;
  total = total + 1;
  $("#js_hiduptime").val(uptime);
  $("#js_hidtotal").val(total);
  uptime = sec_to_string(uptime);
  total = sec_to_string(total);
  $("#js_uptime").html(uptime);
  $("#js_total").html(total);
  setTimeout('startclock()',1000);
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
  $('#timeandthresh').show();
  $('#filter').hide();
  if (si == 4) {
    $('#attack_sev').hide();
    $('#sensor_sev').show();

    $('#attack_time').hide();
    $('#sensor_time').show();
    $('#thresh_freq').hide();

    $('#timeoptions').hide();
    $('#repdetail').hide();
    $('#srepdetail').show();
  } else if (si == 5) {
    $('#attack_sev').hide();
    $('#sensor_sev').hide();

    $('#attack_time').show();
    $('#sensor_time').hide();

    $('#timeoptions').hide();
    $('#repdetail').hide();
    $('#srepdetail').hide();
  } else if (si == 7) {
    $('#attack_sev').hide();
    $('#sensor_sev').hide();

    $('#attack_time').show();
    $('#sensor_time').hide();

    $('#timeoptions').hide();
    $('#repdetail').hide();
    $('#srepdetail').hide();
  } else {
    $('#attack_sev').show();
    $('#sensor_sev').hide();

    $('#sensor_time').hide();
    $('#attack_time').show();

    $('#timeoptions').show();
    $('#repdetail').show();
    $('#srepdetail').hide();
  }
}

function sh_mailreptype(si) {
  if (si < 10) {
    if (si < 4) {
      $('#timeandthresh').show();
      $('#filter').hide();
      $('#attack_sev').show();
    } else if (si == 4 || si == 5) {
      $('#timeandthresh').show();
      $('#attack_sev').hide();
      $('#int_template').selectOptions(1);
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
    $('#daily_freq').show();
    $('#weekly_freq').hide();
    $('#thresh_freq').hide();
    $('#always').show();
  } else if (si == 3) {
    $('#daily_freq').hide();
    $('#weekly_freq').show();
    $('#thresh_freq').hide();
    $('#always').show();
  } else if (si == 4) {
    $('#daily_freq').hide();
    $('#weekly_freq').hide();
    $('#thresh_freq').show();
    $('#always').hide();
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

function popit(url,h,w) {
  var wh = getScrollSize();
  $("#popupcontent").load(url);

  if (h !== null && w !== null) {
    $("#popup").height(h);
    $("#popup").width(w);
  }
  $("#popup").show();
  $("#overlay").show();

  $("#overlay").height(wh);
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
  document.fselector.submit();
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
  $('#fselector').submit();
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
    $('#fselector').submit();
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

function submitform(formid, url, action, loc, str) {
  if (formid != '') {
    var qs = $('#'+formid).serialize();
    var url = url+'?'+qs;
  }
  if (action == 'd') {
    var chk = confirm(str);
  } else {
    chk = true;
  }
  if (chk) {
    $.ajax({
      url: url,
      type: 'GET',
      dataType: 'html',
      error: function(){
        alert('Error processing your request!');
      },
      success: function(data){
        var str = data;
        if (data.match(/ERROR.*/)) {
          poperr(str);
        } else {
          if (action == 'a') {
            $('#'+loc).before(data);
            $('#'+formid).clearForm();
          } else if (action == 'u') {
            $('#'+loc).replaceWith(data);
          } else if (action == 'd') {
            $('#'+loc).remove();
          }
        }
      }
    });
  }
}

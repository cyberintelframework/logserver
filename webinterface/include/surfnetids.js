/*
 * ####################################
 * # SURFnet IDS                      #
 * # Version 1.04.03                  #
 * # 11-06-2007                       #
 * # Jan van Lith & Kees Trippelvitz  #
 * # Modified by Peter Arts           #
 * ####################################
 *
 * #############################################
 * # Changelog:
 * # 1.04.03 Added sh_mail functions
 * # 1.04.02 Fixed searchtemplate url bug
 * # 1.04.01 Initial release
 * #############################################
 */

function resetmailform() {
  document.getElementById('attack_sev').style.display='';
  document.getElementById('sensor_sev').style.display='none';

  document.getElementById('sensor_time').style.display='none';
  document.getElementById('attack_time').style.display='';
  document.getElementById('thresh_freq').style.display='none';
}

function sh_mailtemp(si) {
  if (si == 4) {
    document.getElementById('attack_sev').style.display='none';
    document.getElementById('sensor_sev').style.display='';

    document.getElementById('attack_time').style.display='none';
    document.getElementById('sensor_time').style.display='';
    document.getElementById('thresh_freq').style.display='none';
  } else if (si == 5) {
    document.getElementById('attack_sev').style.display='none';
    document.getElementById('sensor_sev').style.display='none';

    document.getElementById('attack_time').style.display='';
    document.getElementById('sensor_time').style.display='none';
  } else {
    document.getElementById('attack_sev').style.display='';
    document.getElementById('sensor_sev').style.display='none';

    document.getElementById('sensor_time').style.display='none';
    document.getElementById('attack_time').style.display='';
  }
}

function sh_mailfreq(si) {
  if (si == 1) {
    document.getElementById('daily_freq').style.display='none';
    document.getElementById('weekly_freq').style.display='none';
    document.getElementById('thresh_freq').style.display='none';
  }
  if (si == 2) {
    document.getElementById('daily_freq').style.display='';
    document.getElementById('weekly_freq').style.display='none';
    document.getElementById('thresh_freq').style.display='none';
  }
  if (si == 3) {
    document.getElementById('daily_freqdaily').style.display='none';
    document.getElementById('weekly_freq').style.display='';
    document.getElementById('thresh_freq').style.display='none';
  }
  if (si == 4) {
    document.getElementById('daily_freq').style.display='none';
    document.getElementById('weekly_freq').style.display='none';
    document.getElementById('thresh_freq').style.display='';
  }
}

function fill_autocomplete(obj) {
  var disobj = obj + 'dis';
  var input = document.getElementById(disobj).value;
  document.getElementById(obj).value = input;
}

function own_autocomplete(obj, map) {
 var input = document.getElementById(obj).value;
 var disobj = obj + 'dis';
 var hidobj = obj + 'hid';
 var len = input.length;
 if (len!=0) {
   for(lname in map) {
     if(lname.substr(0,len).toLowerCase() == input.toLowerCase()) {
       document.getElementById(disobj).value = lname;
       document.getElementById(hidobj).value = lname;
       document.getElementById(obj).value = lname.substr(0,len);
       return false;
     }
   }
 }
 var sug_disp = input;
 while (sug_disp!="" && sug_disp.charAt(sug_disp.length-1)==' ') {
   sug_disp = sug_disp.substr(0,sug_disp.length-1);
 }
 document.getElementById(disobj).value = sug_disp;
 document.getElementById(hidobj).value = sug_disp;
 document.getElementById(obj).focus();
 return false;
}

function basename (path) { return path.replace( /.*\//, "" ); }

function changeId(id) {
  var docurl = window.location.href;
  var file = basename(docurl);
  var base = docurl.replace(file,"")

  if (document.getElementById(id).style.display == 'none') {
    // Make this element visible
    document.getElementById(id).style.display='';
    var imgurl = base + 'images/minus.gif';
  } else {
    // Make this element invisible
    document.getElementById(id).style.display='none';
    var imgurl = base + 'images/plus.gif';
  }
  document.getElementById(id+'_img').src=imgurl;
}

function updateThreshold() {
  var target = document.getElementById('threshold_user');
  target.innerHTML = 'Threshold: <br />\n';
  target.innerHTML += 'if [ ';
  target.innerHTML += document.getElementById('target')[document.getElementById('target').selectedIndex].innerHTML;
  target.innerHTML += ' ' + document.getElementById('operator')[document.getElementById('operator').selectedIndex].innerHTML + ' ';
  if (document.getElementById('value').selectedIndex == 0) {
    target.innerHTML += 'Average';
  } else {
    target.innerHTML += (document.getElementById('value_user').value * 1); // Numeric value
  }
  target.innerHTML += ' ] for ';
  target.innerHTML += document.getElementById('timespan')[document.getElementById('timespan').selectedIndex].innerHTML;
  target.innerHTML += ' with a deviation of ';
  target.innerHTML += (document.getElementById('deviation').value * 1) + ' %'; // Numeric value
  target.innerHTML += '<br />\n';
  target.innerHTML += '&nbsp;then send e-mail report with priority ';
  target.innerHTML += document.getElementById('priority')[document.getElementById('priority').selectedIndex].innerHTML + '';
}
/*
function submitSearchTemplate() {
  var sform = document.getElementById('searchform');
  //searchtemplate_title
  var title = prompt('Please submit a title for this searchtemplate');
  if ((title == '') || (title == null) || (title == 'undefined')) {
    alert('Invalid title.');
    return false;
  }
  if (confirm('Would you like to use \'' + title + '\' as the title for this searchtemplate?')) {
    document.getElementById('strip_html_escape_sttitle').value = title;
    sform.action = 'searchtemplate.php';
    sform.submit();
  } else return false;
}
*/
function submitSearchTemplateFromResults(url) {
  //searchtemplate_title
  var title = prompt('Please submit a title for this searchtemplate');
  if ((title == '') || (title == null) || (title == 'undefined')) {
    alert('Invalid title.');
    return false;
  }
  if (confirm('Would you like to use \'' + title + '\' as the title for this searchtemplate?')) {
    url = 'searchtemplate.php?' + url + '&strip_html_escape_sttitle=' + title;
    url = URLDecode(url);
    window.location.href = url;
    return true;
  } else return false;
}

function URLDecode(encoded) {
  // Replace + with ' '
  // Replace %xx with equivalent character
  // Put [ERROR] in output if %xx is invalid.
  var HEXCHARS = "0123456789ABCDEFabcdef";
  var plaintext = "";
  var i = 0;
  while (i < encoded.length) {
    var ch = encoded.charAt(i);
    if (ch == "+") {
      plaintext += " ";
      i++;
    } else if (ch == "%") {
      if (i < (encoded.length-2)
        && HEXCHARS.indexOf(encoded.charAt(i+1)) != -1
        && HEXCHARS.indexOf(encoded.charAt(i+2)) != -1 ) {
        plaintext += unescape( encoded.substr(i,3) );
        i += 3;
      } else {
        alert( 'Bad escape combination near ...' + encoded.substr(i) );
        plaintext += "%[ERROR]";
        i++;
      }
    } else {
      plaintext += ch;
      i++;
    }
  } // while
    return plaintext;
}

function show_hide_column(col_no) {
  var stl;
  var tbl  = document.getElementById('malwaretable');
  var rows = tbl.getElementsByTagName('tr');

  for (var row=0; row<rows.length;row++) {
    var cels = rows[row].getElementsByTagName('td');
    var status = cels[col_no].style.display;
    var but = 'scanner_' + col_no;
    if (status == '') {
      cels[col_no].style.display='none';
      document.getElementById(but).className='tab';
    } else {
      cels[col_no].style.display='';
      document.getElementById(but).className='tabsel';
    }
  }
}

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


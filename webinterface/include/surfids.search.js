function surfids_search_src(si) {
  if (si == 1) {
    $('#source').show();
    $('#sourcemac').hide();
    $('#mac_sourcemac').val('');
    $('#ownrange').hide();
    $('#inet_ownsource').val('');
  } else if (si == 2) {
    $('#source').hide();
    $('#ipv4v6_source').val('');
    $('#sourcemac').show();
    $('#ownrange').hide();
    $('#inet_ownsource').val('');
  } else if (si == 3) {
    $('#source').hide();
    $('#ipv4v6_source').val('');
    $('#sourcemac').hide();
    $('#mac_sourcemac').val('');
    $('#ownrange').show();
  }
}

function surfids_search_dest(si) {
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

function surfids_search_severity(si) {
  $('#sshversion').hide();
  $('#sshuser').hide();
  $('#sshpass').hide();
  $('#sshhascommand').hide();
  $('#sshlogin').hide();
  $('#sshcommand').hide();

  $('#sevtype').hide();
  $('#attacktype').hide();
  $('#virus').hide();
  $('#filename').hide();
  $('#binary').hide();
  $('#charac_details').hide();

  $('#int_sevtype')[0].selectedIndex = 0;
//  $('#int_attack')[0].selectedIndex = 0;
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

function surfids_search_sevtype(si) {
  if (si == 0 || si == 4 || si == 5) {
    $('#attacktype').show();
    $('#sshversion').hide();
    $('#sshuser').hide();
    $('#sshpass').hide();
    $('#sshhascommand').hide();
    $('#sshlogin').hide();
    $('#sshcommand').hide();
  } else if (si == 7) {
    $('#attacktype').hide();
    $('#sshversion').show();
    $('#sshuser').show();
    $('#sshpass').show();
    $('#sshhascommand').show();
    $('#sshlogin').show();
    $('#sshcommand').show();
  } else {
    $('#attacktype').hide();
    $('#int_attack')[0].selectedIndex = 0;
    $('#sshversion').hide();
    $('#sshuser').hide();
    $('#sshpass').hide();
    $('#sshhascommand').hide();
    $('#sshlogin').hide();
    $('#sshcommand').hide();
  }
}

function surfids_char_change() {
  if ($('.info_char').is(':visible')) {
    $('.info_char').hide();
    $('.search_char').show();

    sev = $('#int_sev').val();
    atype = $('#int_sevtype')[0].selectedIndex - 1;

    surfids_search_severity(sev);
    surfids_search_sevtype(atype);

    $('#int_sevtype')[0].selectedIndex = atype + 1;
  } else {
    $('.info_char').show();
    $('.search_char').hide();
  }
}

function surfids_dest_change() {
  if ($('.info_dest').is(':visible')) {
    $('.info_dest').hide();
    $('.search_dest').show();

    dest_type = $('#int_destchoice')[0].selectedIndex + 1;
    surfids_search_dest(dest_type);
  } else {
    $('.info_dest').show();
    $('.search_dest').hide();
  }
}

function surfids_source_change() {
  if ($('.info_source').is(':visible')) {
    $('.info_source').hide();
    $('.search_source').show();

    src_type = $('#int_sourcechoice')[0].selectedIndex + 1;
    surfids_search_src(src_type);
  } else {
    $('.info_source').show();
    $('.search_source').hide();
  }
}

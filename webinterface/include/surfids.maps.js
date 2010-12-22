function redirmap() {
  var val = $('#redirmapper').val();
  if (val == 1) {
    window.location='googlemap.php';
  } else {
    window.location='sensormap.php';
  }
}

function sevmap() {
  var sevmapper = $('#sevmapper').val();
  var ownmap = $('#int_own').val();
  if (sevmapper == 1) {
    window.location='googlemap.php?int_sev=0&int_own=' + ownmap;
  } else if (sevmapper == 2) {
    window.location='googlemap.php?int_sev=1&int_own=' + ownmap;
  } else if (sevmapper == 3) {
    window.location='googlemap.php?int_sev=1&int_atype=5&int_own=' + ownmap;
  } else if (sevmapper == 4) {
    window.location='googlemap.php?int_sev=1&int_atype=0&int_own=' + ownmap;
  } else if (sevmapper == 5) {
    window.location='googlemap.php?int_sev=1&int_atype=7&int_own=' + ownmap;
  }
}

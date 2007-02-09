#!/usr/bin/perl 

#######################################
# Function library for logging server #
# SURFnet IDS                         #
# Version 1.04.01                     #
# 07-11-2006                          #
# Jan van Lith & Kees Trippelvitz     #
#######################################

#####################
# Changelog:
# 1.04.01 Initial release
#####################

###############################################
# INDEX
###############################################
# 1		All CHK functions
# 2		All GET functions
# 2.01		getts
# 2.02		getec
# 3		ALL DB functions
# 3.01		dbremoteip
# 3.02		dbnetconf
# 3.03		dbmacaddr
# 4		ALL misc functions
# 4.01		printlog
# 4.05		printenv
# 4.06		connectdb
###############################################

# 2.01 getts
# Function to get the current date in a human readable format
# Returns date as "day-month-year hour:min:sec"
sub getts() {
  my ($ts, $year, $month, $day, $hour, $min, $sec, $timestamp);
  $ts = time();
  $year = localtime->year() + 1900;
  $month = localtime->mon() + 1;
  if ($month < 10) {
    $month = "0" . $month;
  }
  $day = localtime->mday();
  if ($day < 10) {
    $day = "0" . $day;
  }
  $hour = localtime->hour();
  if ($hour < 10) {
    $hour = "0" . $hour;
  }
  $min = localtime->min();
  if ($min < 10) {
    $min = "0" . $min;
  }
  $sec = localtime->sec();
  if ($sec < 10) {
    $sec = "0" . $sec;
  }

  $timestamp = "$day-$month-$year $hour:$min:$sec";
}

# 2.02 getec
# Function to get the error code of the last run command
# and translate it into something readable
sub getec() {
  my ($ec);
  if ($? == 0) {
    $ec = "Ok";
  } else {
    $ec = "Err - $?";
  }
}

# 3.01 dbremoteip
# Function that retrieves the remoteip given a tap device
# Returns remoteip on success
# Returns false on failure
sub dbremoteip() {
  my ($sth, $tap, @row, $remoteip);
  $tap = $_[0];
  chomp($tap);

  $sth = $dbh->prepare("SELECT remoteip FROM sensors WHERE tap = '$tap'");
  $execute_result = $sth->execute();
  @row = $sth->fetchrow_array;
  $remoteip = $row[0];
  if ("$remoteip" eq "") {
    return "false";
  } else {
    return $remoteip;
  }
  return "false";
}

# 3.02 dbnetconf
# Function that retrieves the netconf given a tap device
# Returns netconf on success
# Returns false on failure
sub dbnetconf() {
  my ($sth, $tap, @row, $remoteip, $er);
  $tap = $_[0];
  chomp($tap);

  $sth = $dbh->prepare("SELECT netconf FROM sensors WHERE tap = '$tap'");
  $er = $sth->execute();
  @row = $sth->fetchrow_array;
  $netconf = $row[0];
  if ("$netconf" eq "") {
    return "false";
  } else {
    return $netconf;
  }
  return "false";
}

# 3.03 dbmacaddr
# Function that retrieves the mac address for a specific sensor and remoteip
# Returns mac on success
# Returns false on failure
sub dbmacaddr() {
  my ($sth, $mac, $sensor, $remoteip, @row, $sql, $er);
  $sensor = $_[0];
  $remoteip = $_[1];
  chomp($sensor);
  chomp($remoteip);

  $sql = "SELECT mac FROM sensors WHERE keyname = '$sensor' AND remoteip = '$remoteip'";
  $sth = $dbh->prepare($sql);
  &printlog("Prepared query: $sql");
  $er = $sth->execute();
  &printlog("Executed query", "$er");
  @row = $sth->fetchrow_array;
  $mac = $row[0];
  if ("$mac" eq "") {
    return "false";
  } else {
    return $mac;
  }
  return "false";
}

# 4.01 printlog
# Function to print something to a logfile
# Returns 0 on success
# Returns 1 on failure
sub printlog() {
  my ($err, $ts, $msg, $logstring);
  $msg = $_[0];
  $err = $_[1];
  $ts = getts();
  $logstring = "[$ts";
  if ($tap) {
    if ($tap ne "") {
      $logstring .= " - $tap";
    }
  }
  if ($err) {
    if ($err ne "") {
      $logstring .= " - $err";
    }
  }
  $logstring .= "] $msg\n";
  if ($logfile) {
    open(LOG, ">> $logfile");
    print LOG $logstring;
    close(LOG);
  }
}

# 4.05 printenv
# Function to print all environment variables. Used for debugging purposes.
sub printenv() {
  my ($envlog, $key);
  $envlog = $_[0];

  open(ENVLOG, ">> $envlog");
  print ENVLOG "======================================================\n";
  foreach $key (sort keys(%ENV)) {
    print ENVLOG "$key = $ENV{$key}\n";
  }
  print ENVLOG "======================================================\n";
  close(ENVLOG);
}

# 4.06 connectdb
# Function to connect to the database
# Returns "true" on success
# Returns "false" on failure
sub connectdb() {
  my ($ts, $pgerr);
  $dbh = DBI->connect($c_dsn, $c_pgsql_user, $c_pgsql_pass);
  &printlog("Connecting to $c_pgsql_dbname with DSN: $c_dsn");
  if ($dbh ne "") {
    &printlog("Connect result: Ok");
    return "true";
  } else {
    &printlog("Connect result: failed");
    $pgerr = $DBI::errstr;
    chomp($pgerr);
    &printlog("Error message: $pgerr");
    return "false";
  }
}

return "true";

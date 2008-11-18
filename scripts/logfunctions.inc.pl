#!/usr/bin/perl 

#######################################
# Function library for logging server #
# SURFids 2.04                        #
# Changeset 001                       #
# 30-05-2008                          #
# Jan van Lith & Kees Trippelvitz     #
#######################################

#####################
# Changelog:
# 001 version 2.00
#####################

###############################################
# INDEX
###############################################
# 1		All CHK functions
# 2		All GET functions
# 2.01		getts
# 2.02		getec
# 2.03		getdatetime
# 2.04		getdate
# 2.05		gettime
# 4		ALL misc functions
# 4.01		printlog
# 4.02		printmail
# 4.03		printdebug
# 4.05		printenv
# 4.06		connectdb
# 4.07		printattach
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

# 2.03 getdatetime
# Function to get a human readable date/time string
sub getdatetime {
  my ($stamp, $ss, $mm, $hh, $dd, $mo, $yy, $datestring);
  $stamp = $_[0];
  chomp($stamp);
  $tm = localtime($stamp);
  $ss = $tm->sec;
  $mm = $tm->min;
  $hh = $tm->hour;
  $dd = $tm->mday;
  $mo = $tm->mon + 1;
  $yy = $tm->year + 1900;
  if ($ss < 10) { $ss = "0" .$ss; }
  if ($mm < 10) { $mm = "0" .$mm; }
  if ($hh < 10) { $hh = "0" .$hh; }
  if ($dd < 10) { $dd = "0" .$dd; }
  if ($mo < 10) { $mo = "0" .$mo; }
  $datestring = "$dd-$mo-$yy $hh:$mm:$ss";
  return $datestring;
}

# 2.04 getdate
# Function to get a human readable date string
sub getdate {
  my ($stamp, $dd, $mo, $yy, $datestring);
  $stamp = $_[0];
  chomp($stamp);
  $tm = localtime($stamp);
  $dd = $tm->mday;
  $mo = $tm->mon + 1;
  $yy = $tm->year + 1900;
  if ($dd < 10) { $dd = "0" .$dd; }
  if ($mo < 10) { $mo = "0" .$mo; }
  $datestring = "$dd-$mo-$yy";
  return $datestring;
}

# 2.05 gettime
# Function to get a human readable time string (without seconds)
sub gettime {
  my ($stamp, $mm, $hh, $datestring);
  $stamp = $_[0];
  chomp($stamp);
  $tm = localtime($stamp);
  $mm = $tm->min;
  $hh = $tm->hour;
  if ($mm < 10) { $mm = "0" .$mm; }
  if ($hh < 10) { $hh = "0" .$hh; }
  $datestring = "$hh:$mm";
  return $datestring;
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

# 4.02 printmail
# Function to print a line in a mail report
sub printmail() {
  my ($msg, $res, $len, $tabcount, $tabstring);
  $msg = $_[0];
  $res = $_[1];
  chomp($msg);

  open(MAIL, ">> $mailfile");
  if ($res) {
    chomp($res);
    $len = length($msg);
    $tabcount = ceil((48 - $len) / 8);
    $tabstring = "\t" x $tabcount;
    print MAIL $msg . $tabstring . $res . "\n";
  } else {
    print MAIL $msg . "\n";
  }
  close(MAIL);
}

# 4.03 printdebug
# Function to print debug messages to a file
sub printdebug() {
  my ($msg);
  $msg = $_[0];
  chomp($msg);
  open(DEBUG, ">> $debugfile");
  print DEBUG $msg . "\n";
  close(DEBUG);
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

# 4.07 printattach
# Function to print a line in a mail attachment
sub printattach() {
  my ($msg, $attachfile);
  $msg = $_[0];
  chomp($msg);
  $attachfile = "$mailfile" . "attach";

  open(ATTACH, ">> $attachfile");
  print ATTACH $msg . "\n";
  close(ATTACH);
}

return "true";

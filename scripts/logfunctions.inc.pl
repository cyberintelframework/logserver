#!/usr/bin/perl 

####################################
# Function library                 #
# SURFids 3.00                     #
# Changeset 003                    #
# 12-11-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#####################
# Changelog:
# 003 Added getifip, getifmac
# 002 Added utc, removed convert_to_utc
# 001 Added convert_to_utc
#####################

$f_log_debug = 0;
$f_log_info = 1;
$f_log_warn = 2;
$f_log_error = 3;
$f_log_crit = 4;

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
# 2.06      getifip
# 2.07      getifmac
# 3     ALL DB functions
# 3.01      dbconnect
# 3.02      dbquery
# 3.03      dbnumrows
# 4		ALL misc functions
# 4.02		printmail
# 4.03		printdebug
# 4.05		printenv
# 4.06		printattach
# 4.07		utc
# 4.08      prompt
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
  if ($enable_utc == 1) {
    $datestring = "$yy-$mo-$dd" ."T". "$hh:$mm:$ss" ."Z";
  } else {
    $datestring = "$dd-$mo-$yy $hh:$mm:$ss";
  }
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

# 2.06 getifip
# Function to retrieve the IP address from an interface
# Returns IP address on success
# Returns false on failure
sub getifip() {
  my ($if, $ip);
  $if = $_[0];
  $ip = `ifconfig $if | head -n2 | tail -n1 | grep -v MTU | awk '{print \$2}' | awk -F: '{print \$2}'`;
  chomp($ip);
  if ("$ip" ne "") {
    return $ip;
  } else {
    return "false";
  }
  return "false";
}

# 2.07 getifmac
# Function to retrieve the MAC address from a given interface
# Returns MAC address on success
# Returns false on failure
sub getifmac() {
  my ($if, $mac);
  $if = $_[0];
  $mac = `ifconfig $if | head -n1 | awk '{print \$NF}'`;
  chomp($mac);
  if ($mac ne "") {
    return $mac;
  } else {
    return "false";
  }
}

# 3.01 dbconnect
# Function to connect to the database
# Returns "true" on success
# Returns "false" on failure
sub dbconnect() {
  my ($ts, $pgerr, $args);
  $dbh = DBI->connect($c_dsn, $c_pgsql_user, $c_pgsql_pass)
    or die $DBI::errstr;
}

# 3.02 dbquery
# Performs a query to the database. If the query fails, log the query to the database
# and return false. Otherwise, return the data handle.
sub dbquery {
    my $sql = $_[0];

    if (!$dbh) {
        &logsys($f_log_error, "DB_ERROR", "No database handler!");
        return 'false';
    }
    $sth = $dbh->prepare($sql);
    $er = $sth->execute();
    $errstr = $sth->errstr;
    chomp($sql);
    if (!$er) {
        &logsys($f_log_error, "DB_QUERY_FAIL", $sql);
        &logsys($f_log_error, "DB_QUERY_FAIL", $errstr);
        return 'false';
    } else {
        if ($c_log_success_query == 1) {
            &logsys($f_log_debug, "DB_QUERY_OK", $sql);
        }
    }

    return $sth;
}

# 3.03 dbnumrows
# Performs a query to the database and return the amount of rows
sub dbnumrows() {
  my ($sql, $er, $sth);
  $sql = $_[0];

  if (!$dbh) {
    return 0;
  }
  $sth = $dbh->prepare($sql);
  $er = $sth->execute();
  if (!$er) {
    return 0;
  }
  return $sth->rows;
}

# 9.05 logsys
# Function to log messages to the syslog table
sub logsys() {
  my ($ts, $level, $msg, $sql, $er, $tsensor);    # local variables
  our $source;
  our $sensor;
  our $tap;
  our $pid;
  our $g_vlanid;

  if (!$source) { $source = "unknown"; }
  if (!$sensor) { $sensor = "unknown"; }
  if (!$tap)    { $tap    = "unknown"; }
  if (!$pid)    { $pid    = 0; }
  if (!$g_vlanid) { $g_vlanid = 0; }

  $level = $_[0];   # Loglegel (DEBUG, INFO, WARN, ERROR, CRIT)
  $msg = $_[1];     # Message (START_SCRIPT, STOP_SCRIPT, etc )
  chomp($msg);

  if ($level >= $c_log_level) {
    if (!$source) { $source = "unknown"; }
    if (!$sensor) { $sensor = "unknown"; }
    if (!$tap)    { $tap    = "unknown"; }
    if (!$pid)  { $pid    = 0; }
    if (!$g_vlanid) { $g_vlanid = 0; }

    if ($_[2]) {
      $args = $_[2];
      chomp($args);
    } else {
      $args = "";
    }

#    $ts = time();
    if ($c_log_method == 2 || $c_log_method == 3) {
      # We need to cleanup the $args and escape all ' and " characters
#      $args =~ s/\'/\\\'/g;
#      $args =~ s/\"/\\\"/g;
      $args =~ s/\'/\'\'/g;
#      $args =~ s/\"/\"\"/g;

      $sql = "INSERT INTO syslog (source, error, args, level, keyname, device, pid, vlanid) VALUES ";
      $sql .= " ('$source', '$msg', E'$args', $level, '$sensor', '$tap', $pid, $g_vlanid)";
      if ($dbh) {
       $er = $dbh->do($sql);
      }
    }
    if ($c_log_method == 1 || $c_log_method == 3) {
      $tsensor = $sensor;
      if ($g_vlanid != 0) {
        $tsensor = "$sensor-$g_vlanid";
      }
      $ts = &getts();
      open LOG,  ">>/var/log/surfids.log" || die ("cant open log: $!");
      print LOG "[$ts] $pid $source $tsensor $msg $args\n";
      close LOG;
    }
  }
  return "true";
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

# 4.06 printattach
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

# 4.07 utc
# Function to modify a timestamp into a UTC timestamp
# ie, it adds or substracts timezone difference and results in a UTC timestamp
sub utc() {
  my ($time, $utc);
  $time = $_[0];
  if ($enable_utc == 1) {
    chomp($time);
    $utc = (3600 * $c_utc_time) + $time;
    return $utc;
  } else {
    return $time;
  }
}

# 4.08 prompt
# Function to prompt the user for input
sub prompt() {
  my ($promptstring, $defaultvalue);
  ($promptstring,$defaultvalue) = @_;
  if ($defaultvalue) {
    #print $promptstring, "[", $defaultvalue, "]: ";
    print $promptstring;
  } else {
    $defaultvalue = "";
    print $promptstring;
  }
  $| = 1;       # force a flush after our print
  $_ = <STDIN>; # get the input from STDIN

  chomp;

  if ("$defaultvalue") {
    if ($_ eq "") {
      return $defaultvalue;
    } else {
      return $_;
    }
  } else {
    return $_;
  }
}

return "true";

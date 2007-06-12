#!/usr/bin/perl 

#######################################
# Function library for logging server #
# SURFnet IDS                         #
# Version 1.04.03                     #
# 17-04-2007                          #
# Jan van Lith & Kees Trippelvitz     #
#######################################

#####################
# Changelog:
# 1.04.03 Removed logging in connectdb
# 1.04.02 Removed DB functions
# 1.04.01 Initial release
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
# 4.05		printenv
# 4.06		connectdb
# 4.07		createmail
# 4.08		sendmail
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
  chomp($msg);

  open(MAIL, ">> $mailfile");
  if ($_[1]) {
    $res = $_[1];
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

# 4.07 createmail
# Function to create a mail for the mailreporter
# Returns 1 if mail needs to be sent
# Returns 0 if mail does not need to be sent
# Returns -1 on failure
sub createmail() {
  my ($org, $template, $timespan, $ts_now, $ts_start, $ts_end, $sensor_where, $sql, $andorg, $sensor, @row);
  my ($ip, $timestamp, $attacktype, $attack, $keyname, $vlanid, $ec, $ipview_query, $malattacks, $overview_query);
  my ($sql_ranges, $result_ranges, @rangerow, $count, $range, $i);
  $template = $_[0];
  $timespan = $_[1];
  $sensor = $_[2];
  $org = $_[3];
  chomp($template);
  chomp($timespan);
  chomp($sensor);
  chomp($org);

  if ("$template" eq "") {
    return -1;
  }

  if ("$timespan" eq "") {
    return -1;
  }

  if ("$sensor" eq "") {
    return -1;
  }

  if ("$org" eq "") {
    return -1;
  }

  $ts_now = time();
  $ts_start = $ts_now - $timespan - $week;
  $ts_end = $ts_now - $week;

  if ($sensor) {
    if ($sensor > -1) { $sensor_where = " AND sensors.id = '$sensor'"; }
    else { $sensor_where = ""; }
  } else {
    $sensor_where = "";
  }
  if ($aid == $org) {
    $andorg = "";
  } else {
    $andorg = "AND sensors.organisation = '$org'";
  }

  if ($template == 1) {
    ###########################
    # START ALL ATTACKS TEMPLATE
    ###########################

    # Get total of attacks and downloads and print to the mail
    $sql = "SELECT DISTINCT severity.txt, severity.val, COUNT(attacks.severity) as total ";
    $sql .= "FROM attacks, sensors, severity WHERE attacks.severity = severity.val ";
    $sql .= "AND attacks.timestamp >= '$ts_start' AND attacks.timestamp <= '$ts_end' ";
    $sql .= "AND NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $org) ";
    $sql .= "AND attacks.sensorid = sensors.id $andorg $sensor_where ";
    $sql .= "GROUP BY severity.txt, severity.val ORDER BY severity.val";
    print "SQL: $sql\n";

    $overview_query = $dbh->prepare($sql);
    $ec = $overview_query->execute();
    $malattacks = $overview_query->rows;
    if ($ec != 0) {
      &printmail("Results from " . getdatetime($ts_start) . " till " . getdatetime($ts_end));
      &printmail("");

      while (@row = $overview_query->fetchrow_array) {
        $severity = $row[0];
        $value = $row[1];
        $totalsev = $row[2];
        &printmail("$severity:", $totalsev);
      }
      &printmail("");

      $sql = "SELECT attacks.source, attacks.timestamp, details.text, sensors.keyname, sensors.vlanid ";
      $sql .= "FROM attacks, sensors, details WHERE attacks.severity = 1 AND details.type = 1 ";
      $sql .= "AND details.attackid = attacks.id AND attacks.timestamp >= '$ts_start' AND attacks.timestamp <= '$ts_end' ";
      $sql .= "AND NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $org) AND attacks.sensorid = sensors.id ";
      $sql .= " $andorg $sensor_where ";
      $sql .= "ORDER BY timestamp ASC";
      $ipview_query = $dbh->prepare($sql);
      $ec = $ipview_query->execute();

      &printmail("------ Malicious Attacks ------");
      &printmail("");
      &printmail("Sensor\t\tSource IP\t\tTimestamp\t\tAttack Type");

      while (@row = $ipview_query->fetchrow_array) {
        $ip = $row[0];
        $timestamp = $row[1];
        $time = getdatetime($timestamp);
        $attacktype = $row[2];
        $attacktype =~ s/Dialogue//;
        $keyname = $row[3];
        $vlanid = $row[4];
        if ($vlanid != 0) {
          $keyname = "$keyname-$vlanid";
        }
        &printmail("$keyname\t$ip\t\t$time\t$attacktype");
      }
      return 1;
    } else {
      return 0;
    }
    ###########################
    # END ALL ATTACKS TEMPLATE
    ###########################
  } elsif ($template == 2) {
    ###########################
    # START OWN RANGES TEMPLATE
    ###########################

    $sql = "SELECT DISTINCT ranges FROM organisations WHERE id = $org";
    $sql_ranges = $dbh->prepare($sql);
    $result_ranges = $sql_ranges->execute();
    @rangerow = $sql_ranges->fetchrow_array;
    @rangerow = split(/;/, "@rangerow");
    $count = @rangerow;
    $i = 0;
    if ($count > 0) {
      &printmail("Results from " . getdatetime($ts_start) . " till " . getdatetime($ts_end));
      &printmail("");

      foreach $range (@rangerow) {
        $sql = "SELECT DISTINCT source, timestamp, text FROM attacks, sensors, details ";
        $sql .= "WHERE attacks.source <<= '$range' AND details.attackid = attacks.id ";
        $sql .= "AND details.type = '1' AND attacks.severity = '1' AND attacks.timestamp >= '$ts_start' ";
        $sql .= "AND attacks.timestamp <= '$ts_end' AND attacks.sensorid = sensors.id ";
        $sql .= "AND NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $org) ";
        $sql .= "AND sensors.organisation = '$org' $sensor_where GROUP BY source, timestamp, text";
        $ipview_query = $dbh->prepare($sql);
        $ec = $ipview_query->execute();
        if ($ec != 0) {
          $i++;
          &printmail("$ec malicious attacks from range: $range");
          while (@row = $ipview_query->fetchrow_array) {
            $ip = "";
            $timestamp = "";
            $attacktype = "";
            $ip = $row[0];
            $timestamp = $row[1];
            $time = getdatetime($timestamp);
            $attacktype = $row[2];
            $attacktype =~ s/Dialogue//;
            &printmail("\t$ip\t$time\t$attacktype");
          }
          &printmail("");
        }
      }
    }
    if ($i > 0) {
      return 1;
    } else {
      return 0;
    }
    ###########################
    # END OWN RANGES TEMPLATE
    ###########################
  } elsif ($template == 3) {
    ###########################
    # START THRESHOLD TEMPLATE
    ###########################

    # Get detailed data for threshold
    $sql = "SELECT target, value, deviation, operator FROM report_template_threshold ";
    $sql .= "WHERE report_content_id = '$id'";
    $detail_query = $dbh->prepare($sql);
    $execute_result = $detail_query->execute();
    @detail = $detail_query->fetchrow_array;
    $target = $detail[0];
    $db_timespan = $frequency;
    $db_value = $detail[1];
    $deviation = $detail[2];
    $db_operator = $detail[3];

    printmail("Results from " . getdatetime($ts_start) . " till " . getdatetime($ts_end));
    printmail("");

    # Operator
    @ar_operator = ('', '<', '>', '<=', '>=', '=', '!=');
    $operator = $ar_operator[$db_operator];

    # Value
    # -1 = average, other value = user defined
    if ($db_value > -1) {
      $value = $db_value;
    } else {
      # Retrieving the total uptime for average calculation
      $sql = "SELECT (sum(uptime + up)) as total_uptime ";
      $sql .= "FROM sensors, ";
      # Start subquery
      $sql .= "(SELECT sum(floor(extract(epoch from now()) - laststart)) as up ";
      $sql .= " FROM sensors WHERE status = 1 $andorg $sensor_where) as current ";
      # End subquery
      $sql .= "WHERE sensors.id = sensors.id $andorg $sensor_where ";
      $first_query = $dbh->prepare($sql);
      $er = $first_query->execute();
      @first_result = $first_query->fetchrow_array;
      $uptime = $first_result[0];

      # Get the total amount of attacks
      $sql = "SELECT COUNT(attacks.id) as total FROM attacks, sensors ";
      $sql .= "WHERE severity = $target AND sensors.id = attacks.sensorid ";
      $sql .= "AND NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $org) ";
      $sql .= "$andorg $sensor_where";
      $total_query = $dbh->prepare($sql);
      $er = $total_query->execute();
      @total_result = $total_query->fetchrow_array;
      $total = $total_result[0];

      # Calculate the average number of attacks per timespan
      if ($uptime != 0) {
        $uptime_hours = floor($uptime / 60);
        $uptime_days = floor($uptime / 60 / 24);
        $uptime_weeks = floor($uptime / 60 / 24 / 7);
        $average_hours = floor($total / $uptime_hours);
        $average_days = floor($total / $uptime_days);
        $average_weeks = floor($total / $uptime_weeks);
      } else {
        $average_hours = 0;
        $average_days = 0;
        $average_weeks = 0;
      }

      if ($timespan == $hour) {
        $value = $average_hours;
      } elsif ($timespan == $day) {
        $value = $average_days;
      } elsif ($timespan == $week) {
        $value = $average_weeks;
      }
    }

    $i = 0;
    if ($operator eq "<") {
      if ($cur_value < $threshold) { $i = 1; }
    } elsif ($operator eq "<=") {
      if ($cur_value <= $threshold) { $i = 1; }
    } elsif ($operator eq ">") {
      if ($cur_value > $threshold) { $i = 1; }
    } elsif ($operator eq ">=") {
      if ($cur_value >= $threshold) { $i = 1; }
    } elsif ($operator eq "=") {
      if ($cur_value == $threshold) { $i = 1; }
    } elsif ($operator eq "!=") {
      if ($cur_value != $threshold) { $i = 1; }
    }
    $printcheck = "Measured attacks ($cur_value) < Allowed attacks ($threshold)";

    if ($i == 1) {
      printmail("Triggered threshold report: $subject");
      printmail("");
      printmail("Performed check:");
      printmail("$printcheck");
      printmail("");
      printmail("Use the next link to view related attacks:");
      printmail($c_webinterface_prefix . "/logsearch.php?int_from=$ts_start&int_to=$ts_end&reptype=multi&int_sev=$target");
      printmail("");
      return 1;
    } else {
      return 0;
    }
    ###########################
    # END THRESHOLD TEMPLATE
    ###########################
  } elsif ($template == 4) {
    ###########################
    # START SENSOR STATUS TEMPLATE
    ###########################
    &printmail("Sensor status overview for " .getdatetime($ts_now));
    &printmail("");

    if ($aid == $org) {
      $andorg = "";
    } else {
      $andorg = "AND sensors.organisation = '$org'";
    }

    $sql = "SELECT status, tap, tapip, keyname, vlanid FROM sensors ";
    $sql .= " WHERE sensors.id = sensors.id $andorg $sensor_where ";
    $sql .= " ORDER BY keyname ASC, vlanid ASC";
    $sensors_query = $dbh->prepare($sql);
    $sensors_result = $sensors_query->execute();
    $i = 0;
    if ($sensor_result > 0) {
      while (@sensors = $sensors_query->fetchrow_array) {
        $status = $sensors[0];
        $tap = $sensors[2];
        $tapip = $sensors[3];
        $keyname = $sensors[4];
        $vlanid = $sensors[5];
        if ($vlanid != 0) {
          $keyname = "$keyname-$vlanid";
        }

        if ($status == 0) {
          &printmail("$keyname is down!");
          $i++;
        } elsif ($status == 1 && "$tap" eq "" && "$tapip" eq "") {
          &printmail("$keyname tap/tapip error!");
          $i++;
        }
      }
      if ($i > 0) {
        return 1;
      } else {
        return 0;
      }
    } else {
      return 0;
    }
    ###########################
    # END SENSOR STATUS TEMPLATE
    ###########################
  } else {
    # Unknown template
    return -1;
  }
}

# 4.24 sendmail()
# Function to send a mail
# Returns 0 on success
# Dies on failure
sub sendmail() {
  my ($email, $mailfile, $sensorid, $subject, $gpg, $sigmailfile, $to_address, $mail_host, $final_mailfile, $msg, $chk, $priority, $header_priority);
  $mailfile = $_[0];
  $email = $_[1];
  $priority = $_[2];
  $gpg_enabled = $_[3];
  $subject = $_[4];
  chomp($email);
  chomp($mailfile);
  chomp($subject);
  chomp($priority);
  chomp($gpg_enabled);
  
  $sigmailfile = "$mailfile" . ".sig";
  $to_address = "$email";
  $mail_host = 'localhost';

  if ($gpg_enabled == 1) {
    # Encrypt the mail with gnupg 
    $gpgobj = new GnuPG();
    $gpgobj->clearsign(plaintext => "$mailfile", output => "$sigmailfile", armor => 1, passphrase => $c_passphrase);
  }
  
  #### Create the multipart container
  $msg = MIME::Lite->new (
    From => $c_from_address,
    To => $to_address,
    Subject => $subject,
    Type => 'multipart/mixed'
  ) or die "Error creating multipart container: $!\n";

  # Prepare priority (1 = low, 2 = normal, 3 = high)
  if ($priority == 1) { $header_priority = "5 (Lowest)"; }
  elsif ($priority == 3) { $header_priority = "1 (Highest)"; }
  else { $header_priority = "3 (Normal)"; }
  $msg->add('X-Priority' => $header_priority);

  if ($gpg_enabled == 1) { $final_mailfile  = $sigmailfile; }
  else { $final_mailfile = $mailfile; }

  ### Add the (signed) file
  $msg->attach (
    Type => 'text/plain; charset=ISO-8859-1',
    Path => $final_mailfile,
    Filename => $final_mailfile,
  ) or die "Error adding $final_mailfile: $!\n";
  
  ### Send the Message
  MIME::Lite->send('sendmail');
  $chk = $msg->send;
  
  # Delete the mail and signed mail
  if (-e "$sigmailfile") {
    system("rm $sigmailfile");
  }
  return 0;
}

return "true";

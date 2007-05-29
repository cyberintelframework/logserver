#!/usr/bin/perl
####################################
# Mail reporter                    #
# SURFnet IDS                      #
# Version 1.04.10                  #
# 29-05-2007                       #
# Jan van Lith & Kees Trippelvitz  #
# Modified by Peter Arts           #
####################################

#########################################################################################
# Changelog:
# 1.04.10 Fixed a bug with the ARP template
# 1.04.09 Added IP exclusion stuff
# 1.04.08 Fixed group by issue with all attacks reports
# 1.04.07 Fixed $logstamp variable
# 1.04.06 Fixed logsearch.php url
# 1.04.05 Added vlanid to sensor name
# 1.04.04 Removed out of date sensor message
# 1.04.03 Restructured the code
# 1.04.02 Fixed a bug with daily reports at a certain time and sensor specific reports
# 1.04.01 Rereleased as 1.04.01
# 1.03.07 Fixed a bug in the sensorstatus query
# 1.03.06 Fixed a send bug with template 4
# 1.03.05 Fixed bug when email address was empty
# 1.03.04 Updated with sensor status report
# 1.03.03 Fixed division by zero bug
# 1.03.02 Fixed average attack calculation
# 1.03.01 Released as part of the 1.03 package
# 1.02.09 Bugfixes
# 1.02.08 Bugfix
# 1.02.07 Fully addapted for new mail reporting.                                        
# 1.02.06 Fixed a bug in the timestamp of the logfiles.                                 
#########################################################################################

#########################################################################################
# Copyright (C) 2005-2006 SURFnet                                                       #
# Authors Jan van Lith & Kees Trippelvitz                                               #
#                                                                                       #
# This program is free software; you can redistribute it and/or                         #
# modify it under the terms of the GNU General Public License                           #
# as published by the Free Software Foundation; either version 2                        #
# of the License, or (at your option) any later version.                                #
#                                                                                       #
# This program is distributed in the hope that it will be useful,                       #
# but WITHOUT ANY WARRANTY; without even the implied warranty of                        #
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                         #
# GNU General Public License for more details.                                          #
#                                                                                       #
# You should have received a copy of the GNU General Public License                     #
# along with this program; if not, write to the Free Software                           #
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.       #
#                                                                                       #
# Contact ids@surfnet.nl                                                                #
#########################################################################################

# This script will send a mail clearsigned with gnupgp (if configured in webinterface) containing information
# about the amount of attacks and all the attacks detailed with ip, time of attack and type of attack.  

####################
# Modules used
####################
use DBI;
use Time::Local;
use Time::localtime;
use Net::SMTP;
use MIME::Lite;
use GnuPG qw( :algo );
use POSIX qw(floor);
use POSIX qw(ceil);

####################
# Variables used
####################
do '/etc/surfnetids/surfnetids-log.conf';

$logfile = $c_logfile;
$logfile =~ s|.*/||;
if ($c_logstamp == 1) {
  $day = localtime->mday();
  if ($day < 10) {
    $day = "0" . $day;
  }
  $month = localtime->mon() + 1;
  if ($month < 10) {
    $month = "0" . $month;
  }
  $year = localtime->year() + 1900;
  if ( ! -d "$c_surfidsdir/log/$day$month$year" ) {
    mkdir("$c_surfidsdir/log/$day$month$year");
  }
  $logfile = "$c_surfidsdir/log/$day$month$year/$logfile";
} else {
  $logfile = "$c_surfidsdir/log/$logfile";
}

##################
# Functions
##################
require "$c_surfidsdir/scripts/logfunctions.inc.pl";

####################
# Main script
####################

$localtime = time();
$localtime = getdatetime($localtime);

# Opening log file
open(LOG, ">> $logfile");

# Set now on top of this script
$ts_now = time;

# Connect to the database (dbh = DatabaseHandler or linkserver)
$check = connectdb();

# ts_ means timestamp
# dt_ means formatted datetime
# d_ means formatted date
# $ts_yesterday = (time - (24 * 60 * 60));
# $dt_yesterday = getdatetime($ts_yesterday);
# $d_yesterday = getdate($ts_yesterday);

# Declare timespans
$minute = 60;
$hour = (60 * $minute);
$day = (24 * $hour);
$week = (7 * $day);

# Get the organisation id for organisation ADMIN
$sql_aid = "SELECT id FROM organisations WHERE organisation = 'ADMIN'";
$aid_query = $dbh->prepare($sql_aid);
$er_aid = $aid_query->execute();
@row_aid = $aid_query->fetchrow_array;
$aid = $row_aid[0];

if ("$aid" eq "") {
  $aid = 0;
}

# Get organisation and email of all users with mailreporting enabled and status active
$sql_email = "SELECT login.email, login.organisation, report_content.id, report_content.template, ";
$sql_email .= "report_content.sensor_id, report_content.frequency, report_content.last_sent, report_content.interval, report_content.priority, ";
$sql_email .= "report_content.subject, login.gpg, report_content.title ";
$sql_email .= "FROM login, report_content ";
$sql_email .= "WHERE report_content.user_id = login.id AND report_content.active = TRUE AND NOT login.email = ''";
$email_query = $dbh->prepare($sql_email);
$execute_result = $email_query->execute();
while (@row = $email_query->fetchrow_array) {
  $email = $row[0];
  $org = $row[1];
  $id = $row[2];
  $template = $row[3];
  $sensor = $row[4];
  $frequency = $row[5];
  $last_sent = $row[6];
  $interval = $row[7];
  $priority = $row[8];
  $subject = $row[9];
  $gpg_enabled = $row[10];
  $title = $row[11];

  # The maill will be sent per default
  $sendit = 1;

  $ts_now = time;
  $lt = localtime(time);
  $curhour = $lt->hour;
  $curday = $lt->wday;

  print "SUBJECT: $subject\n";

  if ($frequency == 1) {
    $timespan = $hour;
  } elsif ($frequency == 2) {
    $timespan = $day;
  } elsif ($frequency == 3) {
    $timespan = $week;
  }

  if ($template != 3) {
    if ($last_sent ne "") {
      if ($frequency == 1) {
        $ts_check = $last_sent + $hour - (5 * $minute);
      } elsif ($frequency == 2) {
        $ts_check = $last_sent + $day - (5 * $minute);
        if ($interval != $curhour) {
          $sendit = 0;
        }
      } elsif ($frequency == 3) {
        $ts_check = $last_sent + $week - (5 * $minute);
        if ($interval != $curday) {
          $sendit = 0;
        }
      }
      if ($ts_now < $ts_check) {
        $sendit = 0;
      }
    } else {
      if ($frequency == 2) {
        if ($interval != $curhour) {
          $sendit = 0;
        }
      } elsif ($frequency == 3) {
        if ($interval != $curday) {
          $sendit = 0;
        }
      }
      $last_sent = $ts_now - $timespan;
    }
  }

  print "SENDIT: $sendit\n";
  print "\n";

  if ($template == 5) {
    $sendit = 0;
  }

  if ($sendit == 1) {
    # Set start and end timestamps
    $ts_start = $ts_now - $timespan;
    $ts_end = $ts_now;

    if ($sensor) {
      if ($sensor > -1) { $sensor_where = " AND sensors.id = '$sensor'"; }
      else { $sensor_where = ""; }
    } else {
      $sensor_where = "";
    }
    $mailfile = "/tmp/" .$id. ".mail";
    if (-e "$mailfile") {
      system("rm $mailfile");
    }
    
    # Date/time when report was generated
    printmail("Mailreport generated at " . getdatetime(time));
    
    # ALL ATTACKS:
    if ($template == 1) {
      printmail("Results from " . getdatetime($ts_start) . " till " . getdatetime($ts_end));
      printmail("");

      if ($aid == $org) {
        $andorg = "";
      } else {
        $andorg = "AND sensors.organisation = '$org'";
      }

      # Get total of attacks and downloads and print to the mail
      $sql = "SELECT DISTINCT severity.txt, severity.val, COUNT(attacks.severity) as total ";
      $sql .= "FROM attacks, sensors, severity WHERE attacks.severity = severity.val ";
      $sql .= "AND attacks.timestamp >= '$ts_start' AND attacks.timestamp <= '$ts_end' ";
      $sql .= "AND NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $org) ";
      $sql .= "AND attacks.sensorid = sensors.id $andorg $sensor_where ";
      $sql .= "GROUP BY severity.txt, severity.val ORDER BY severity.val";

      $overview_query = $dbh->prepare($sql);
      $execute_result = $overview_query->execute();
      $malattacks = $overview_query->rows;
      if ($execute_result == 0 ) {
        printmail("No malicious attacks detected for last timespan.");
      } else {
        while (@row = $overview_query->fetchrow_array) {
          $severity = $row[0];
          $value = $row[1];
          $totalsev = $row[2];
          printmail("$severity:", $totalsev);
        } 
        printmail("");
      
        # Get details about the attacks and print them to mail.   
        # Printed in format: ip address attacker, time of attack, type of attack.   
        $message = "";

        $sql = "SELECT attacks.id, attacks.source, attacks.timestamp, details.text, sensors.keyname, sensors.vlanid ";
        $sql .= "FROM attacks, sensors, details WHERE attacks.severity = 1 AND details.type = 1 ";
        $sql .= "AND details.attackid = attacks.id AND attacks.timestamp >= '$ts_start' AND attacks.timestamp <= '$ts_end' ";
        $sql .= "AND NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $org) AND attacks.sensorid = sensors.id ";
        $sql .= "AND sensors.organisation = '$org' $sensor_where ";
        $sql .= "ORDER BY timestamp ASC";
        print "SQL2: $sql\n\n";
        $ipview_query = $dbh->prepare($sql);
        $execute_result = $ipview_query->execute();
        
        printmail("------ Malicious Attacks ------");
        printmail("");
        printmail("Sensor\t\tSource IP\t\tTimestamp\t\tAttack Type");
        
        while (@row = $ipview_query->fetchrow_array) {
          $ip = "";
          $timestamp = "";
          $attacktype = "";
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
          printmail("$keyname\t$ip\t\t$time\t$attacktype");
        }
      }
    }
    # OWN RANGES:
    elsif ($template == 2) {  
      printmail("Results from " . getdatetime($ts_start) . " till " . getdatetime($ts_end));
      printmail("");

      $sql = "SELECT DISTINCT ranges FROM organisations WHERE id = $org";
      $sql_ranges = $dbh->prepare($sql);
      $result_ranges = $sql_ranges->execute();
      @rangerow = $sql_ranges->fetchrow_array;
      @rangerow = split(/;/, "@rangerow");
      $count = @rangerow;
      if ($count > 0) {
        foreach $range (@rangerow) {
          # Get details about the attacks and print them to mail.
          # Printed in format: ip address attacker, time of attack, type of attack.
          $message = "";
          $sql = "SELECT DISTINCT source, timestamp, text FROM attacks, sensors, details ";
          $sql .= "WHERE attacks.source <<= '$range' AND details.attackid = attacks.id ";
          $sql .= "AND details.type = '1' AND attacks.severity = '1' AND attacks.timestamp >= '$ts_start' ";
          $sql .= "AND attacks.timestamp <= '$ts_end' AND attacks.sensorid = sensors.id ";
          $sql .= "AND NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $org) ";
          $sql .= "AND sensors.organisation = '$org' $sensor_where GROUP BY source, timestamp, text";
          $ipview_query = $dbh->prepare($sql);
          $execute_result = $ipview_query->execute();
          if ($execute_result == 0 ) {
            printmail("No malicious attacks from range: $range");
          } else {
            printmail("$execute_result malicious attacks from range: $range"); 
            while (@row = $ipview_query->fetchrow_array) {
              $ip = "";
              $timestamp = "";
              $attacktype = "";
              $ip = $row[0];
              $timestamp = $row[1];
              $time = getdatetime($timestamp);
              $attacktype = $row[2];
              $attacktype =~ s/Dialogue//;
              printmail("\t$ip\t$time\t$attacktype");
            }
            printmail("");
          }
        }
      } else {
        printmail("No malicious attacks detected from your ranges for last timespan.");
      }
    }
    # THRESHOLD:
    elsif ($template == 3) {
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
        if ($aid == $org) {
          $andorg = "";
        } else {
          $andorg = "AND sensors.organisation = '$org'";
        }
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
      
      # Get current value for last timespan
      $sql = "SELECT COUNT(attacks.id) FROM attacks, sensors ";
      $sql .= "WHERE attacks.severity = $target AND sensors.id = attacks.sensorid ";
      $sql .= "AND NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $org) ";
      $sql .= " $andorg $sensor_where ";
      $sql .= "AND timestamp >= '$ts_start' AND timestamp <= '$ts_end'";

      $check_query = $dbh->prepare($sql);
      $execute_result = $check_query->execute();
      @db_row = $check_query->fetchrow_array;
      $cur_value = $db_row[0];
      
      # Use deviation to set db_upper and db_lower values
      $perc = ($deviation / 100);
      $cur_upper = ($cur_value + ($cur_value * $perc));
      $cur_lower = ($cur_value - ($cur_value * $perc));

      $sendit = 0;
      if ($operator eq "<") {
        if ($cur_lower < $value) { $sendit = 1; }
        $printcheck = "Measured attacks ($cur_lower) < Allowed attacks ($value)";
      } elsif ($operator eq "<=") {
        if ($cur_lower <= $value) { $sendit = 1; }
        $printcheck = "Measured attacks ($cur_lower) <= Allowed attacks ($value)";
      } elsif ($operator eq ">") {
        if ($cur_upper > $value) { $sendit = 1; }
        $printcheck = "Measured attacks ($cur_upper) > Allowed attacks ($value)";
      } elsif ($operator eq ">=") {
        if ($cur_upper >= $value) { $sendit = 1; }
        $printcheck = "Measured attacks ($cur_upper) >= Allowed attacks ($value)";
      } elsif ($operator eq "=") {
        if (($cur_lower >= $value) && ($cur_upper <= $value)) { $sendit = 1; }
        $printcheck = "Measured attacks ($cur_value) = $value with a deviation of $deviation";
      } elsif ($operator eq "!=") {
        if (($value < $cur_lower) || ($value > $cur_upper)) { $sendit = 1; }
        $printcheck = "Measured attacks ($cur_value) != Allowed attacks ($value)";
      }
      
      if ($sendit == 1) {
        # Send an e-mail
        printmail("Triggered threshold report: $title");
        printmail("");
        printmail("Performed check:");
        printmail("$printcheck");
        printmail("");
        printmail("Use next link to view related attacks:");
        printmail($c_webinterface_prefix . "/logsearch.php?int_from=$ts_start&int_to=$ts_end&reptype=multi&int_sev=$target");
        printmail("");
      }
    } elsif ($template == 4) {
      $sendit = 0;
      print MAIL "Sensor status overview for " .getdatetime($ts_now) . "\n";
      print MAIL "\n";

      if ($aid == $org) {
        $andorg = "";
      } else {
        $andorg = "AND sensors.organisation = '$org'";
      }

      $sql = "SELECT status, lastupdate, tap, tapip, keyname, vlanid FROM sensors ";
      $sql .= " WHERE sensors.id = sensors.id $andorg $sensor_where ";
      $sql .= " ORDER BY keyname";
      $sensors_query = $dbh->prepare($sql);
      $sensors_result = $sensors_query->execute();
      while (@sensors = $sensors_query->fetchrow_array) {
        $status = $sensors[0];
        $lastupdate = $sensors[1];
        $tap = $sensors[2];
        $tapip = $sensors[3];
        $keyname = $sensors[4];
        $vlanid = $sensors[5];

        if ($vlanid != 0) {
          $keyname = "$keyname-$vlanid";
        }

        if ($status == 0) {
          $sendit = 1;
          printmail("$keyname is down!");
        } elsif ($status == 1) {
          if ("$tap" eq "" && "$tapip" eq "") {
            $sendit = 1;
            printmail("$keyname tap/tapip error!");
          }
#          if ("$lastupdate" ne "") {
#            $ts_diff = $ts_now - $lastupdate;
#            if ($ts_diff > 3900) {
#              $sendit = 1;
#              printmail("$keyname missed an update!");
#            }
#          }
        }
      }
    } else {
      $sendit == 0;
    }
    
    if ($sendit == 1) {
      printmail("");
      if (MAIL) {
        close(MAIL);
      }
      &sendmail($email, $id, $subject, $priority, $gpg_enabled);
    } else {
      if (MAIL) {
        close(MAIL);
      }
      if ($mailfile) {
        if (-e "$mailfile") {
          system("rm -f $mailfile");
        }
      }
    }
  }
}

sub sendmail {
  # Get variables : mailaddress to send to, Date, sender, recipient, subject and your SMTP mailhost
  $email = $_[0];
  $id = $_[1];
  $subject = $_[2];
  $priority = $_[3];
  $gpg_enabled = $_[4];
  
  print "Sending mailreport to $email\n";
  
  $maildata = "/tmp/" .$id. ".mail";
  $sigmaildata = "$maildata" . ".sig";
  $replyto = "";
  $to_address = "$email";
  $mail_host = 'localhost';
  
  # Prepare subject
  # Ex. SURFnet IDS stats for %date%
  # %date%, %day%, %time%, %hour%
  $sub_date = getdate(time);
  $sub_time = gettime(time);
  $sub_tm = localtime(time);
  $sub_hour = $sub_tm->hour . "h";
  @ar_day = ('Sunday', 'Monday', 'Thuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
  $sub_day = $ar_day[$sub_tm->wday];
  $subject =~ s/%date%/$sub_date/;
  $subject =~ s/%time%/$sub_time/;
  $subject =~ s/%hour%/$sub_hour/;
  $subject =~ s/%day%/$sub_day/;
  $subject = $c_subject_prefix . $subject;

  if ($gpg_enabled == 1) {
    # Encrypt the mail with gnupg 
    $gpg = new GnuPG();
    $gpg->clearsign(plaintext => "$maildata", output => "$sigmaildata", armor => 1, passphrase => $c_passphrase);
  }
  
  #### Create the multipart container
  $msg = MIME::Lite->new (
    From => $c_from_address,
    To => $to_address,
\\    Reply-To => $replyto,
    Subject => $subject,
    Type => 'multipart/mixed'
  ) or die "Error creating multipart container: $!\n";
  
  # Prepare priority (1 = low, 2 = normal, 3 = high)
  if ($priority == 1) { $header_priority = "5 (Lowest)"; }
  elsif ($priority == 3) { $header_priority = "1 (Highest)"; }
  else { $header_priority = "3 (Normal)"; }
  $msg->add('X-Priority' => $header_priority);
  
  if ($gpg_enabled == 1) { $final_maildata  = $sigmaildata; }
  else { $final_maildata = $maildata; }
  ### Add the (signed) file
  $msg->attach (
    Type => 'text/plain; charset=ISO-8859-1',
    Path => $final_maildata,
    Filename => $final_maildata,
  ) or die "Error adding $final_maildata: $!\n";
  
  ### Send the Message
  # MIME::Lite->send('smtp', $mail_host, Timeout=>60, Hello=>"$mail_hello", From=>"$c_from_address");
  MIME::Lite->send('sendmail');
  $chk = $msg->send;
  
  # Update last_sent
  $last_sent = time;
  $sql = "UPDATE report_content SET last_sent = '$last_sent' WHERE id = '$id'";
  $execute_result = $dbh->do($sql);
  
  # Print info to a log file
  printlog("Mailed stats for $sub_date to: $email with organisation $org");
  
  # Delete the mail and signed mail
  if (-e "$maildata") {
    system("rm $maildata");
  }
  if (-e "$final_maildata") {
    system("rm $final_maildata");
  }
}

# Closing database connection.
close(LOG);

if (MAIL) {
  close(MAIL);
}

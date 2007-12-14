#!/usr/bin/perl
####################################
# Mail reporter                    #
# SURFnet IDS                      #
# Version 2.10.02                  #
# 13-12-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################
# Contributors:                    #
# Peter Arts                       #
####################################

#########################################################################################
# Changelog:
# 2.10.02 Normal text mails are now sent without attachment
# 2.10.01 Added Cymru mail report
# 2.00.01 version 2.00 - improved mailreporter
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
use Time::localtime qw(localtime);
use Net::SMTP;
use MIME::Lite;
use Net::Abuse::Utils qw( :all );
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

# Opening log file
open(LOG, ">> $logfile");

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
$sql_email = "SELECT login.email, login.organisation, report_content.id, report_content.user_id, ";
$sql_email .= " report_content.template, report_content.last_sent, report_content.sensor_id, ";
$sql_email .= " report_content.frequency, report_content.interval, report_content.priority, ";
$sql_email .= " report_content.subject, report_content.operator, report_content.threshold, ";
$sql_email .= " report_content.severity, report_content.detail, login.gpg ";
$sql_email .= " FROM login, report_content ";
$sql_email .= " WHERE report_content.user_id = login.id AND report_content.active = TRUE AND NOT login.email = ''";
$sql_email .= " AND report_content.detail < 10 ";

$email_query = $dbh->prepare($sql_email);
$ec = $email_query->execute();
while (@row = $email_query->fetchrow_array) {
  $email = $row[0];
  $org = $row[1];
  $id = $row[2];
  $userid = $row[3];
  $template = $row[4];
  $last_sent = $row[5];
  if (!$last_sent) {
    $last_sent = "";
  }
  $sensorid = $row[6];
  $frequency = $row[7];
  $interval = $row[8];
  $priority = $row[9];
  $subject = $row[10];
  $operator = $row[11];
  $threshold = $row[12];
  $severity = $row[13];
  $detail = $row[14];
  $gpg_enabled = $row[15];

  # The maill will be sent per default
  $sendit = 1;

  $ts_now = time;
  $lt = localtime(time);
  $curhour = $lt->hour;
  $curday = $lt->wday;

  if ($frequency == 1) {
    $timespan = $hour;
  } elsif ($frequency == 2) {
    $timespan = $day;
  } elsif ($frequency == 3) {
    $timespan = $week;
  } elsif ($frequency == 4) {
    if ($interval == 1) {
      $timespan = $hour;
      $tsstring = "last hour";
    } elsif ($interval == 2) {
      $timespan = $day;
      $tsstring = "last 24 hours";
    } elsif ($interval == 3) {
      $timespan = $week;
      $tsstring = "last 7 days";
    }
  }

  if ("$threshold" eq "-1") {
    # Not a threshold report, check for last_sent
    if ("$last_sent" eq "") {
      # Report has never been sent before
      if ($frequency == 1) {
        # Hourly report
        $ts_check = $ts_now - $hour + (5 * $minute);
      } elsif ($frequency == 2) {
        # Daily report
        $ts_check = $ts_now - $day + (5 * $minute);
        if ($interval != $curhour) {
          # Don't send report yet, wait for the set hour
          $sendit = 0;
        }
      } elsif ($frequency == 3) {
        # Weekly report
        $ts_check = $ts_now - $week + (5 * $minute);
        if ($interval != $curday) {
          # Don't send report yet, wait for the set day
          $sendit = 0;
        }
      }
    } else {
      $ts_check = $last_sent + $timespan - (5 * $minute);
      # Check against last_sent
      if ($frequency == 2) {
        # Daily report
        if ($interval != $curhour) {
          # Don't send report yet, wait for the set hour
          $sendit = 0;
        }
      } elsif ($frequency == 3) {
        # Weekly report
        if ($interval != $curday) {
          # Don't send report yet, wait for the set day
          $sendit = 0;
        }
      }
    }

    # Last_sent check
    if ($sendit == 1) {
      if ($ts_now < $ts_check) {
        $sendit = 0;
      }
    }
  }

  if ($template == 5) {
    # Ignore ARP reports, these are handled by detectarp.pl
    $sendit = 0;
  }

  if ($sendit == 1) {
    # Set start and end timestamps
    $ts_start = $ts_now - $timespan;
    $ts_end = $ts_now;

    # Setting up the sensor ID sql stuff
    if ($sensorid > -1) {
      $andsensor = " AND sensors.id = '$sensorid'";
    } else {
      $andsensor = "";
    }

    # Setting up the severity sql stuff
    if ($severity > -1) {
      $andsev = " AND attacks.severity = $severity";
    } else {
      $andsev = "";
    }

    # Setting up the organisation sql stuff
    if ($aid == $org) {
      $andorg = "";
    } else {
      $andorg = "AND sensors.organisation = '$org'";
    }

    if ($operator > -1) {
      # Setting up the correct operator
      @ar_operator = ('', '<', '>', '<=', '>=', '=', '!=');
      $oper = $ar_operator[$operator];
    }

    # Setting up the mail file
    $mailfile = "/tmp/" .$id. ".mail";
    if (-e "$mailfile") {
      system("rm $mailfile");
    }

    if ($detail == 3) {
      $attach = 1;
    } else {
      $attach = 0;
    }
    
    # Date/time when report was generated
    if ($detail != 4) {
      printmail("Mailreport generated at " . getdatetime(time));
    }
    
    if ($template == 1) {
      ################################
      # ALL ATTACKS TEMPLATE
      ################################
      if ($detail != 4) {
        printmail("Results from " . getdatetime($ts_start) . " till " . getdatetime($ts_end));
        printmail("");
      }

      $totalcount = 0;
      if ($detail =~ /^(0|2)$/) {

        # Summary
        ###############################################
        printmail("######### Summary #########");
        $sql = "SELECT DISTINCT severity.txt, severity.val, COUNT(attacks.severity) as total ";
        $sql .= " FROM attacks, sensors, severity WHERE attacks.severity = severity.val ";
        $sql .= " AND attacks.timestamp >= '$ts_start' AND attacks.timestamp <= '$ts_end' ";
        $sql .= " AND NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $org) ";
        $sql .= " AND attacks.sensorid = sensors.id $andorg $andsensor $andsev ";
        $sql .= " GROUP BY severity.txt, severity.val ORDER BY severity.val";

        $overview_query = $dbh->prepare($sql);
        $ec = $overview_query->execute();

        while (@row = $overview_query->fetchrow_array) {
          $severity = $row[0];
          $value = $row[1];
          $totalsev = $row[2];
          $totalcount = $totalcount + $totalsev;
          printmail("$severity:", $totalsev);
        } 
        ############# Summary
        printmail("");
      }

      if ($detail =~ /^(1|2)$/) {
        $totalcount = 0;

        # Detailed overview
        ###############################################
        printmail("######### Detail overview #########");
        $sql = "SELECT attacks.source, attacks.timestamp, details.text, sensors.keyname, sensors.vlanid ";
        $sql .= "FROM attacks ";
        $sql .= " INNER JOIN sensors ";
        $sql .= " ON attacks.sensorid = sensors.id ";
        $sql .= " LEFT JOIN details ";
        $sql .= " ON attacks.id = details.attackid ";
        $sql .= " WHERE (details.type IN (1,4,8) OR details.type IS NULL) ";
        $sql .= " AND attacks.timestamp >= '$ts_start' AND attacks.timestamp <= '$ts_end' ";
        $sql .= " AND NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $org) ";
        $sql .= " $andorg $andsensor $andsev";
        $sql .= " ORDER BY timestamp ASC";
        $ipview_query = $dbh->prepare($sql);
        $ec = $ipview_query->execute();

        printmail("Sensor\t\tSource IP\t\tTimestamp\t\tAdditional info");
        
        while (@row = $ipview_query->fetchrow_array) {
          $ip = "";
          $timestamp = "";
          $attacktype = "";
          $ip = $row[0];
          $timestamp = $row[1];
          $time = getdatetime($timestamp);
          if ($row[2]) {
            $attacktype = $row[2]; 
            $attacktype =~ s/Dialogue//;
          } else {
            $attacktype = "";
          }
          $keyname = $row[3];
          $vlanid = $row[4];
          if ($vlanid != 0) {
            $keyname = "$keyname-$vlanid";
          }
          $totalcount++;
          printmail("$keyname\t$ip\t\t$time\t$attacktype");
        }
        ############# Detail overview
      }
      if ($detail == 3) {
        # IDMEF
        ###############################################
        $sql = "SELECT attacks.id, sensors.keyname, sensors.vlanid, attacks.timestamp, attacks.severity, severity.txt, attacks.source, ";
        $sql .= " attacks.sport, attacks.dest, attacks.dport, details.text ";
        $sql .= " FROM attacks ";
        $sql .= " INNER JOIN sensors ";
        $sql .= " ON attacks.sensorid = sensors.id ";
        $sql .= " INNER JOIN severity ";
        $sql .= " ON severity.val = attacks.severity ";
        $sql .= " LEFT JOIN details ";
        $sql .= " ON attacks.id = details.attackid ";
        $sql .= " WHERE (details.type IN (1,4,8) OR details.type IS NULL) ";
        $sql .= " AND attacks.timestamp >= '$ts_start' AND attacks.timestamp <= '$ts_end' ";
        $sql .= " AND NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $org) ";
        $sql .= " $andorg $andsensor $andsev";
        $sql .= " ORDER BY timestamp ASC";
        $ipview_query = $dbh->prepare($sql);
        $ec = $ipview_query->execute();

        printattach("<?xml version=\"1.0\" encoding=\"UTF-8\"?>");
        printattach("<!DOCTYPE IDMEF-Message PUBLIC \"-//IETF//DTD RFC XXXX IDMEF v1.0//EN\" \"idmef-message.dtd\">");
        printattach("<idmef:IDMEF-Message version=\"1.0\" xmlns:idmef=\"http://iana.org/idmef\">");

        $totalcount = 0;
        while (@row = $ipview_query->fetchrow_array) {
          $totalcount++;
          $attackid = $row[0];
          $keyname = $row[1];
          $vlanid = $row[2];
          if ($vlanid != 0) {
            $keyname = "$keyname-$vlanid";
          }
          $timestamp = $row[3];
          $sev = $row[4];
          $sev_text = $row[5];
          $source = $row[6];
          $sport = $row[7];
          $dest = $row[8];
          $dport = $row[9];
          $dtext = $row[10];

          printattach("<idmef:Alert messageid=\"$attackid\">");
          printattach("<idmef:Analyzer analyzerid=\"$keyname\">");
          printattach("</idmef:Analyzer>");
          printattach("<idmef:CreateTime>$timestamp</idmef:CreateTime>");
          printattach("<idmef:Classification ident=\"$sev\" text=\"$sev_text\"></idmef:Classification>");
          printattach("<idmef:Source>");
          printattach("  <idmef:Node>");
          printattach("    <idmef:Address category=\"ipv4-addr\">");
          printattach("      <idmef:address>$source</idmef:address>");
          printattach("    </idmef:Address>");
          printattach("  </idmef:Node>");
          printattach("  <idmef:Service>");
          printattach("    <idmef:port>$sport</idmef:port>");
          printattach("  </idmef:Service>");
          printattach("</idmef:Source>");
          printattach("<idmef:Target>");
          printattach("  <idmef:Node>");
          printattach("    <idmef:Address category=\"ipv4-addr\">");
          printattach("      <idmef:address>$dest</idmef:address>");
          printattach("    </idmef:Address>");
          printattach("  </idmef:Node>");
          printattach("  <idmef:Service>");
          printattach("    <idmef:port>$dport</idmef:port>");
          printattach("  </idmef:Service>");
          printattach("</idmef:Target>");

          if ($sev == 1) {
            $dtext =~ s/Dialogue//;
            printattach("<idmef:AdditionalData type=\"string\" meaning=\"attack-type\">");
            printattach("  <idmef:string>$dtext</idmef:string>");
            printattach("</idmef:AdditionalData>");
          } elsif ($sev == 16) {
            printattach("<idmef:AdditionalData type=\"string\" meaning=\"file-offered\">");
            printattach("  <idmef:string>$dtext</idmef:string>");
            printattach("</idmef:AdditionalData>");
          } elsif ($sev == 32) {
            printattach("<idmef:AdditionalData type=\"string\" meaning=\"file-downloaded\">");
            printattach("  <idmef:string>$dtext</idmef:string>");
            printattach("</idmef:AdditionalData>");
          }
          printattach("</idmef:Alert>");
        }
        printattach("</idmef:IDMEF-Message>");
      }

      if ($detail == 4) {
        # CYMRU format
        ###############################################
        $sql = "SELECT attacks.source, attacks.timestamp, details.text ";
        $sql .= "FROM attacks ";
        $sql .= " INNER JOIN sensors ";
        $sql .= " ON attacks.sensorid = sensors.id ";
        $sql .= " LEFT JOIN details ";
        $sql .= " ON attacks.id = details.attackid ";
        $sql .= " WHERE (details.type IN (1,4,8) OR details.type IS NULL) ";
        $sql .= " AND attacks.severity = 1 ";
        $sql .= " AND attacks.timestamp >= '$ts_start' AND attacks.timestamp <= '$ts_end' ";
        $sql .= " AND NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $org) ";
        if ($severity == 0) {
          # Get the ranges of the organisation
          $sql_ranges = "SELECT DISTINCT ranges FROM organisations WHERE id = $org AND NOT ranges IS NULL";
          $sql_ranges = $dbh->prepare($sql_ranges);
          $result_ranges = $sql_ranges->execute();
          @rangerow = $sql_ranges->fetchrow_array;
          $count = @rangerow;

          if ($count > 0) {
            @rangerow = split(/;/, "@rangerow");
            foreach $range (@rangerow) {
              $sql .= " AND attacks.source !<< '$range' "
            }
          }        
        }
        $sql .= " $andorg $andsensor";
        $sql .= " ORDER BY timestamp ASC";
        $ipview_query = $dbh->prepare($sql);
        $ec = $ipview_query->execute();

        while (@row = $ipview_query->fetchrow_array) {
          $ip = $row[0];
          $timestamp = $row[1];
          $time = getdatetime($timestamp);
          if ($row[2]) {
            $attacktype = $row[2]; 
            $attacktype =~ s/Dialogue//;
          } else {
            $attacktype = "";
          }

          @asninfo = get_asn_info($ip);
          $asn = $asninfo[0];
          if ("$asn" ne "") {
            $desc = get_as_description($asn);
          } else {
            $desc = "";
          }

          $totalcount++;
          printmail("$asn | $ip | $time $attacktype | $desc");
        }
      }

      # Checking for threshold stuff
      ###############################################
      if ($threshold > -1) {
        $sendit = 0;
        $printcheck = "Measured attacks for the $tsstring ($totalcount) $oper Allowed attacks ($threshold)";
        if ($oper eq "<") {
          if ($totalcount < $threshold) { $sendit = 1; }
        } elsif ($oper eq "<=") {
          if ($totalcount <= $threshold) { $sendit = 1; }
        } elsif ($oper eq ">") {
          if ($totalcount > $threshold) { $sendit = 1; }
        } elsif ($oper eq ">=") {
          if ($totalcount >= $threshold) { $sendit = 1; }
        } elsif ($oper eq "=") {
          if ($totalcount == $threshold) { $sendit = 1; }
        } elsif ($oper eq "!=") {
          if ($totalcount != $threshold) { $sendit = 1; }
        }
        printmail("");
        printmail("######### Threshold rule #########");
        printmail($printcheck);
        printmail("");
      } else {
        if ($totalcount == 0) {
          $sendit = 0;
        }
      }
    } elsif ($template == 2) {  
      ################################
      # OWN RANGES TEMPLATE
      ################################
      printmail("Results from " . getdatetime($ts_start) . " till " . getdatetime($ts_end));
      printmail("");

      # Get the ranges of the organisation
      $sql = "SELECT DISTINCT ranges FROM organisations WHERE id = $org AND NOT ranges IS NULL";
      $sql_ranges = $dbh->prepare($sql);
      $result_ranges = $sql_ranges->execute();
      @rangerow = $sql_ranges->fetchrow_array;
      $count = @rangerow;

      if ($count > 0) {
        @rangerow = split(/;/, "@rangerow");
        if ($detail =~ /^(0|2)$/) {

          # Summary
          ###############################################
          printmail("######### Summary #########");

          %sevhash = ();
          $totalcount = 0;
          foreach $range (@rangerow) {
            $sql = "SELECT DISTINCT severity.txt, severity.val, COUNT(attacks.severity) as total ";
            $sql .= " FROM attacks, sensors, severity WHERE attacks.severity = severity.val ";
            $sql .= " AND attacks.timestamp >= '$ts_start' AND attacks.timestamp <= '$ts_end' ";
            $sql .= " AND NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $org) ";
            $sql .= " AND attacks.sensorid = sensors.id AND attacks.source <<= '$range' $andorg $andsensor $andsev ";
            $sql .= " GROUP BY severity.txt, severity.val ORDER BY severity.val";

            $overview_query = $dbh->prepare($sql);
            $ec = $overview_query->execute();
            while (@row = $overview_query->fetchrow_array) {
              $severity = $row[0];
              $totalsev = $row[2];
              $totalcount = $totalcount + $totalsev;
              if ($sevhash{$severity}) {
                $sevhash{$severity} = $sevhash{$severity} + $totalsev;
              } else {
                $sevhash{$severity} = $totalsev;
              }
            } 
          }
          for my $key (keys %sevhash) {
            my $value = $sevhash{$key};
            printmail("$key:", $value);
          }
          ############# Summary
          printmail("");
        }

        if ($detail =~ /^(1|2)$/) {

          # Detailed overview
          ###############################################
          $totalcount = 0;
          printmail("######### Detail overview #########");
          printmail("Source IP\t\tTimestamp\t\tAdditional info");

          foreach $range (@rangerow) {
            $sql = "SELECT attacks.source, attacks.timestamp, details.text ";
            $sql .= " FROM attacks ";
            $sql .= " INNER JOIN sensors ";
            $sql .= " ON attacks.sensorid = sensors.id ";
            $sql .= " INNER JOIN severity ";
            $sql .= " ON severity.val = attacks.severity ";
            $sql .= " LEFT JOIN details ";
            $sql .= " ON attacks.id = details.attackid ";
            $sql .= " WHERE (details.type IN (1,4,8) OR details.type IS NULL) ";
            $sql .= " AND attacks.timestamp >= '$ts_start' AND attacks.timestamp <= '$ts_end' ";
            $sql .= " AND NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $org) ";
            $sql .= " AND attacks.source <<= '$range' ";
            $sql .= " $andorg $andsensor $andsev";
            $sql .= " ORDER BY timestamp ASC";
            $ipview_query = $dbh->prepare($sql);
            $ec = $ipview_query->execute();

            while (@row = $ipview_query->fetchrow_array) {
              $totalcount++;
              $ip = "";
              $timestamp = "";
              $attacktype = "";
              $ip = $row[0];
              $timestamp = $row[1];
              $time = getdatetime($timestamp);
              if ($row[2]) {
                $attacktype = $row[2];
                $attacktype =~ s/Dialogue//;
              } else {
                $attacktype = "";
              }
              printmail("$ip\t\t$time\t$attacktype");
            }
          } #/foreach
          printmail("");
        } #/detail

        if ($detail == 3) {

          # IDMEF
          ###############################################
          $totalcount = 0;
          printattach("<?xml version=\"1.0\" encoding=\"UTF-8\"?>");
          printattach("<!DOCTYPE IDMEF-Message PUBLIC \"-//IETF//DTD RFC XXXX IDMEF v1.0//EN\" \"idmef-message.dtd\">");
          printattach("<idmef:IDMEF-Message version=\"1.0\" xmlns:idmef=\"http://iana.org/idmef\">");

          foreach $range (@rangerow) {
            $sql = "SELECT attacks.id, sensors.keyname, sensors.vlanid, attacks.timestamp, attacks.severity, severity.txt, attacks.source, ";
            $sql .= " attacks.sport, attacks.dest, attacks.dport, details.text ";
            $sql .= " FROM attacks ";
            $sql .= " INNER JOIN sensors ";
            $sql .= " ON attacks.sensorid = sensors.id ";
            $sql .= " INNER JOIN severity ";
            $sql .= " ON severity.val = attacks.severity ";
            $sql .= " LEFT JOIN details ";
            $sql .= " ON attacks.id = details.attackid ";
            $sql .= " WHERE (details.type IN (1,4,8) OR details.type IS NULL) ";
            $sql .= " AND attacks.timestamp >= '$ts_start' AND attacks.timestamp <= '$ts_end' ";
            $sql .= " AND NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $org) ";
            $sql .= " AND attacks.source <<= '$range' ";
            $sql .= " $andorg $andsensor $andsev";
            $sql .= " ORDER BY timestamp ASC";
            $ipview_query = $dbh->prepare($sql);
            $ec = $ipview_query->execute();

            while (@row = $ipview_query->fetchrow_array) {
              $totalcount++;
              $attackid = $row[0];
              $keyname = $row[1];
              $vlanid = $row[2];
              if ($vlanid != 0) {
                $keyname = "$keyname-$vlanid";
              }
              $timestamp = $row[3];
              $sev = $row[4];
              $sev_text = $row[5];
              $source = $row[6];
              $sport = $row[7];
              $dest = $row[8];
              $dport = $row[9];
              $dtext = $row[10];

              printattach("<idmef:Alert messageid=\"$attackid\">");
              printattach("<idmef:Analyzer analyzerid=\"$keyname\">");
              printattach("</idmef:Analyzer>");
              printattach("<idmef:CreateTime>$timestamp</idmef:CreateTime>");
              printattach("<idmef:Classification ident=\"$sev\" text=\"$sev_text\"></idmef:Classification>");
              printattach("<idmef:Source>");
              printattach("  <idmef:Node>");
              printattach("    <idmef:Address category=\"ipv4-addr\">");
              printattach("      <idmef:address>$source</idmef:address>");
              printattach("    </idmef:Address>");
              printattach("  </idmef:Node>");
              printattach("  <idmef:Service>");
              printattach("    <idmef:port>$sport</idmef:port>");
              printattach("  </idmef:Service>");
              printattach("</idmef:Source>");
              printattach("<idmef:Target>");
              printattach("  <idmef:Node>");
              printattach("    <idmef:Address category=\"ipv4-addr\">");
              printattach("      <idmef:address>$dest</idmef:address>");
              printattach("    </idmef:Address>");
              printattach("  </idmef:Node>");
              printattach("  <idmef:Service>");
              printattach("    <idmef:port>$dport</idmef:port>");
              printattach("  </idmef:Service>");
              printattach("</idmef:Target>");

              if ($sev == 1) {
                $dtext =~ s/Dialogue//;
                printattach("<idmef:AdditionalData type=\"string\" meaning=\"attack-type\">");
                printattach("  <idmef:string>$dtext</idmef:string>");
                printattach("</idmef:AdditionalData>");
              } elsif ($sev == 16) {
                printattach("<idmef:AdditionalData type=\"string\" meaning=\"file-offered\">");
                printattach("  <idmef:string>$dtext</idmef:string>");
                printattach("</idmef:AdditionalData>");
              } elsif ($sev == 32) {
                printattach("<idmef:AdditionalData type=\"string\" meaning=\"file-downloaded\">");
                printattach("  <idmef:string>$dtext</idmef:string>");
                printattach("</idmef:AdditionalData>");
              }
              printattach("</idmef:Alert>");
            }
          }
          printattach("</idmef:IDMEF-Message>");
        }

        # Checking for threshold stuff
        ###############################################
        if ($threshold > -1) {
          $sendit = 0;
          $printcheck = "Measured attacks for the $tsstring ($totalcount) $oper Allowed attacks ($threshold)";
          if ($oper eq "<") {
            if ($totalcount < $threshold) { $sendit = 1; }
          } elsif ($oper eq "<=") {
            if ($totalcount <= $threshold) { $sendit = 1; }
          } elsif ($oper eq ">") {
            if ($totalcount > $threshold) { $sendit = 1; }
          } elsif ($oper eq ">=") {
            if ($totalcount >= $threshold) { $sendit = 1; }
          } elsif ($oper eq "=") {
            if ($totalcount == $threshold) { $sendit = 1; }
          } elsif ($oper eq "!=") {
            if ($totalcount != $threshold) { $sendit = 1; }
          }
          printmail("");
          printmail("######### Threshold rule #########");
          printmail($printcheck);
          printmail("");
        } else {
          if ($totalcount == 0) {
            $sendit = 0;
          }
        }
      } else {
        $sendit = 0;
      } #/count
    } elsif ($template == 4) {
      ################################
      # SENSOR STATUS TEMPLATE
      ################################
      printmail("Sensor status overview for " . getdatetime($ts_now));
      printmail("");
      $sendit = 0;

      $sql = "SELECT status, tap, tapip, keyname, vlanid, laststart FROM sensors ";
      $sql .= " WHERE sensors.id = sensors.id $andorg $andsensor ";
      $sql .= " ORDER BY keyname";

      $sensors_query = $dbh->prepare($sql);
      $ec = $sensors_query->execute();
      while (@sensors = $sensors_query->fetchrow_array) {
        $status = $sensors[0];
        $tap = $sensors[1];
        $tapip = $sensors[2];
        $keyname = $sensors[3];
        $vlanid = $sensors[4];
        $laststart = $sensors[5];

        if ($vlanid != 0) {
          $keyname = "$keyname-$vlanid";
        }

        if ("$status" ne "") {
          if ("$severity" eq "-1") {

            # Checking for offline sensors
            ###############################################
            if ($status == 0) {
              $sendit = 1;
              printmail("$keyname is down!");
              printmail("");
            } elsif ($status == 1) {
              if ("$tap" eq "") {

                # Checking for failed startups (of sensors)
                ###############################################
                $check = $laststart + (10 * $minute);
                if ($ts_now > $check) {
                  # Sensor has been trying to start for 10 minutes now
                  $sendit = 1;
                  printmail("$keyname has been trying to start for 10 minutes now!");
                  printmail("");
                }
              } #/$tap
            } #/$status
          } elsif ($severity == 1) {
            if ($status == 1) {
              if ("$tap" eq "") {

                # Checking for failed startups (of sensors)
                ###############################################
                $check = $laststart + (10 * $minute);
                if ($ts_now > $check) {
                  # Sensor has been trying to start for 10 minutes now
                  $sendit = 1;
                  printmail("$keyname has been trying to start for 10 minutes now!");
                  printmail("");
                }
              } #/$tap
            } #/$status
          } elsif ($severity == 2) {

            # Checking for offline sensors
            ###############################################
            if ("$status" eq "0") {
              $sendit = 1;
              printmail("$keyname is down!");
              printmail("");
            }
          } #/$severity
        }
      }
    } else {
      $sendit = 0;
    }
    
    if ($sendit == 1) {
      &sendmail($email, $id, $subject, $priority, $gpg_enabled, $attach);
    } else {
      if ($mailfile) {
        if (-e "$mailfile") {
          system("rm -f $mailfile");
        }
        $att = "$mailfile" . "attach";
        if (-e "$att") {
          system("rm -f $att");
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
  $attach = $_[5];
  
  print "Sending mailreport to $email\n";
  
  $mailfile = "/tmp/" .$id. ".mail";
  $maildata = `cat $mailfile`;
  chomp($maildata);
  $attachfile = "$mailfile" . "attach";
  $sigfile = "$mailfile" . ".sig";
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
    $gpg->clearsign(plaintext => "$mailfile", output => "$sigfile", armor => 1, passphrase => $c_passphrase);
    $sigdata = `cat $sigfile`;
    chomp($sigdata);
  }
  
  #### Create the multipart container
  $msg = MIME::Lite->new (
    From => $c_from_address,
    To => "$email",
    Subject => $subject,
    Type => 'multipart/mixed'
  ) or die "Error creating multipart container: $!\n";
  
  # Prepare priority (1 = low, 2 = normal, 3 = high)
  if ($priority == 1) { $header_priority = "5 (Lowest)"; }
  elsif ($priority == 3) { $header_priority = "1 (Highest)"; }
  else { $header_priority = "3 (Normal)"; }
  $msg->add('X-Priority' => $header_priority);
  
#  if ($gpg_enabled == 1) { $final_maildata  = $sigmaildata; }
#  else { $final_maildata = $maildata; }
  if ($gpg_enabled == 1) { $maildata  = $sigdata; }
  ### Add the (signed) file
  $msg->attach (
    Type => 'text/plain; charset=ISO-8859-1',
    Data => $maildata
#    Filename => $final_maildata,
  ) or die "Error adding $maildata: $!\n";

  if ($attach == 1) {
    ### Add binary file as attachement
    $msg->attach (
      Type => 'text/xml',
      Path => $attachfile,
      Filename => "IDMEF-$id-$ts_now.xml",
      Disposition => 'attachment'
    ) or die "Error adding $attachfile: $!\n";
  }

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
  if (-e "$mailfile") {
    system("rm $mailfile");
  }
  if (-e "$sigfile") {
    system("rm $sigfile");
  }
  if (-e "$attachfile") {
    system("rm $attachfile");
  }
}

# Closing database connection.
close(LOG);

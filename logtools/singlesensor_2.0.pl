#!/usr/bin/perl

####################################
# Single sensor installer          #
# SURFnet IDS                      #
# Version 2.10.02                  #
# 09-11-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################
# Contributors:                    #
# Peter Arts                       #
####################################

#####################
# Changelog:
# 2.10.02 Fixed typo
# 2.10.01 Initial release
#####################

##################
# Modules used
##################
use POSIX;

##################
# Variables used
##################
do '/etc/surfnetids/surfnetids-log.conf';

# Color codes
$n = "\033[0;39m";
$y = "\033[1;33m";
$r = "\033[1;31m";
$g = "\033[1;32m";

##################
# Functions
##################
sub checkcron {
  my ($chk, $cronrule);
  $cronrule = $_[0];
  chomp($cronrule);
  $chk = `cat /etc/crontab | grep $cronrule | wc -l`;
  chomp($chk);
  return $chk;
}

sub printmsg {
  my ($msg, $ec, $len, $tabcount, $tabstring);
  $msg = $_[0];
  chomp($msg);
  $ec = $_[1];
  chomp($ec);
  $len = length($msg);
  $tabcount = ceil((64 - $len) / 8);
  $tabstring = "\t" x $tabcount;
  if ("$ec" eq "0") {
    print $msg . $tabstring . "[${g}OK${n}]\n";
  } elsif ($ec eq "false" || $ec eq "filtered") {
    print $msg . $tabstring . "[${r}Failed${n}]\n";
  } elsif ($ec eq "warning") {
    print $msg . $tabstring . "[${r}Warning${n}]\n";
  } elsif ($ec =~ /^([0-9]*)$/) {
    print $msg . $tabstring . "[${r}Failed (error: $ec)${n}]\n";
  } elsif ($ec eq "ignore") {
    print $msg . $tabstring . "[${y}ignore${n}]\n";
  } elsif ($ec eq "info") {
    print $msg . $tabstring . "[${y}info${n}]\n";
  } else {
    print $msg . $tabstring . "[${g}$ec${n}]\n";
  }
}

##################
# Main script
##################
printmsg("Downloading required tools:", "info");

if (! -e "/etc/surfnetids/surfnetids-tn.conf") {
  `svn export https://svn.ids.surfnet.nl/surfids/2.0/tunnel/branches/surfnetids-tn.conf /etc/surfnetids/surfnetids-tn.conf`;
  printmsg("Downloading surfnetids-tn.conf:", $?);
}

`svn export https://svn.ids.surfnet.nl/surfids/2.0/tunnel/branches/scripts/rrd_traffic.pl $c_surfidsdir/scripts/rrd_traffic.pl`;
printmsg("Downloading rrd_traffic.pl:", $?);

`svn export https://svn.ids.surfnet.nl/surfids/2.0/tunnel/branches/scripts/rrd_serverinfo.pl $c_surfidsdir/scripts/rrd_serverinfo.pl`;
printmsg("Downloading rrd_serverinfo.pl:", $?);

`svn export https://svn.ids.surfnet.nl/surfids/2.0/tunnel/branches/scripts/detectarp.pl $c_surfidsdir/scripts/detectarp.pl`;
printmsg("Downloading detectarp.pl:", $?);

`svn export https://svn.ids.surfnet.nl/surfids/2.0/tunnel/branches/scripts/scanbinaries.pl $c_surfidsdir/scripts/scanbinaries.pl`;
printmsg("Downloading scanbinaries.pl:", $?);

`svn export https://svn.ids.surfnet.nl/surfids/2.0/tunnel/branches/scripts/types_data.pl $c_surfidsdir/scripts/types_data.pl`;
printmsg("Downloading types_data.pl:", $?);

`svn export https://svn.ids.surfnet.nl/surfids/2.10/tunnel/branches/tntools/update_oui.pl $c_surfidsdir/logtools/update_oui.pl`;
printmsg("Downloading update_oui.pl:", $?);

`svn export https://svn.ids.surfnet.nl/surfids/2.10/tunnel/branches/tntools/localsensor.pl $c_surfidsdir/logtools/localsensor.pl`;
printmsg("Downloading localsensor.pl:", $?);

`svn export https://svn.ids.surfnet.nl/surfids/2.0/tunnel/branches/scripts/tnfunctions.inc.pl $c_surfidsdir/scripts/tnfunctions.inc.pl`;
printmsg("Downloading tnfunctions.inc.pl:", $?);

`svn export https://svn.ids.surfnet.nl/surfids/2.0/tunnel/branches/crontab.tn $c_surfidsdir/crontab.tn`;
printmsg("Downloading crontab.tn:", $?);

printmsg("Setting up the crontab:", "info");
open(CRONTAB, ">> $c_surfidsdir/crontab");
open(CRONLOG, "$c_surfidsdir/crontab.tn");
while (<CRONLOG>) {
  $line = $_;
  chomp($line);
  if ($line ne "") {
    @ar_line = split(/ /, $line);
    $check = $ar_line[6];
    chomp($check);
    @file_ar = split(/\//, $check);
    $count = scalar(@file_ar) - 1;
    $file = $file_ar[$count];
    $chk = checkcron($file);
    if ($chk == 0) {
      printmsg("Adding crontab rule for $file:", "info");
      print CRONTAB $line ."\n";
    }
  }
}
close(CRONTAB);
close(CRONLOG);

`$c_surfidsdir/logtools/update_oui.pl`;
printmsg("Installed oui.txt:", $?);

`rm $c_surfidsdir/crontab.tn`;
printmsg("Removed crontab template file:", $?);

`mv $c_surfidsdir/logtools/detectarp.init /etc/init.d/detectarp`;
printmsg("Moving detectarp init script:", $?);

`mv $c_surfidsdir/logtools/pof.init /etc/init.d/pof`;
printmsg("Moving pof init script:", $?);

`update-rc.d detectarp start 99 2 3 4 5 .`;
printmsg("Adding detectarp to the init:", $?);

`update-rc.d pof start 99 2 3 4 5 .`;
printmsg("Adding pof to the init:", $?);

print "\n";
print "Interesting configuration files:\n";
print "  ${g}/etc/crontab\n";
print "  /etc/surfnetids/surfnetids-tn.conf${n}\n";
print "\n";
print "Run $c_surfidsdir/logtools/localsensor.pl to create a sensor for your main interface!\n";

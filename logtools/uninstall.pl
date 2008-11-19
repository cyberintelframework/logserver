#!/usr/bin/perl

####################################
# Logserver uninstallation script  #
# SURFids 2.10                     #
# Changeset 001                    #
# 19-11-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#####################
# Changelog:
# 001 initial release
#####################

# Color codes
$n = "\033[0;39m";
$y = "\033[1;33m";
$r = "\033[1;31m";
$g = "\033[1;32m";

$targetdir = "/opt/surfnetids";
$configdir = "/etc/surfnetids";
$rundir = $0;
$rundir =~ s/uninstall.pl//g;
$logfile = "${rundir}uninstall.log";

##########################
# Includes
##########################

require "../functions_log.pl";

if (! $ARGV[0]) {
  print "-----------------------------------------------------------------------------------------------\n";
  print "This script will remove anything currently present in the logging server SURFids directories!\n";
  print "If you want to keep certain files for later use, make a backup of them and restart this script.\n";
  print "-----------------------------------------------------------------------------------------------\n";

  $confirm = "a";
  while ($confirm !~ /^(n|N|y|Y)$/) {
    $confirm = &prompt("Do you really want to uninstall the SURFids logging server installation? [y/n]: ");
  }
} else {
  $confirm = "y";
}
if ($confirm =~ /^(n|N)$/) {
  exit 1;
}

if (! $ARGV[0]) {
  print "-----------------------------------------------------------------------------------------------\n";
  print "This script can clean up the crontab, but it will remove all SURFids related entries!\n";
  print "This includes all SURFids logserver crontab entries!\n";
  print "If you choose not to let the uninstaller remove the entries, you will have to manually modify\n";
  print "the crontab later (/etc/crontab)\n";
  print "-----------------------------------------------------------------------------------------------\n";

  $confirm = "a";
  while ($confirm !~ /^(n|N|y|Y)$/) {
    $confirm = &prompt("Do you want to clear all SURFids crontab entries? [y/n]: ");
  }
} else {
  $confirm = "y";

  print "-----------------------------------------------------------------------------------------------\n";
  print "NOTICE: The crontab will be cleaned of all SURFids related entries!\n";
  print "-----------------------------------------------------------------------------------------------\n";
}
if ($confirm =~ /^(y|Y)$/) {
  printdelay("Cleaning up the crontab:");
  `cat /etc/crontab | grep -v "$targetdir" >> $rundir/crontab 2>>$logfile`;
  `mv $rundir/crontab /etc/crontab 2>>$logfile`;
  printresult($?);
}

@list = `cat $rundir/files.txt`;
foreach $file (@list) {
  chomp($file);
  printdelay("Removing $file:");
  if (-d "$targetdir/$file") {
    `rm -rf $targetdir/$file 2>>$logfile`;
  } elsif (-e "$targetdir/$file") {
    `rm -f $targetdir/$file 2>>$logfile`;
  }
  printresult($?);
}

if (! $ARGV[0]) {
  $confirm = "a";
  while ($confirm !~ /^(n|N|y|Y)$/) {
    $confirm = &prompt("Do you also want to uninstall the SURFids database? [y/n]: ");
  }
} else {
  $confirm = "y";
}

if ($confirm =~ /^(y|Y)$/) {
  `sudo -V >/dev/null 2>/dev/null`;
  if ($? == 0) {
    # getting info
    if (-e "/etc/surfnetids/surfnetids-log.conf") {
      $dbname = `grep ^\\\$c_pgsql_dbname /etc/surfnetids/surfnetids-log.conf | awk '{print \$NF}' | awk -F\\" '{print \$2}' 2>>$logfile`;
      $dbuser = `grep ^\\\$c_pgsql_user /etc/surfnetids/surfnetids-log.conf | awk '{print \$NF}' | awk -F\\" '{print \$2}' 2>>$logfile`;
      chomp($dbname);
      chomp($dbuser);
    } else {
      $dbname = "";
      $dbuser = "";
    }

    if ("$dbname" ne "") {
      printdelay("Removing database ($dbname):");
      `sudo -u postgres dropdb $dbname 2>>$logfile`;
      printresult($?);
    }

    if ("$dbuser" ne "") {
      printdelay("Removing user ($dbuser):");
      `sudo -u postgres dropuser $dbuser 2>>$logfile`;
      printresult($?);
    }

    printdelay("Removing user (nepenthes):");
    `sudo -u postgres dropuser nepenthes 2>>$logfile`;
    printresult($?);

    printdelay("Removing user (pofuser):");
    `sudo -u postgres dropuser pofuser 2>>$logfile`;
    printresult($?);

    printdelay("Removing user (argos):");
    `sudo -u postgres dropuser argos 2>>$logfile`;
    printresult($?);
  } else {
    printmsg("Skipping database removal:", "info");

    print "-----------------------------------------------------------------------------------------------\n";
    print "To be able to remove the database, this scripts needs to have the program 'sudo' installed!\n";
    print "-----------------------------------------------------------------------------------------------\n";
  }
} else {
  printmsg("Skipping database removal:", "info");
}

if (! -e "$targetdir/tntools/") {
  if (! -e "$targetdir/genkeys/") {
    # Tunnel server not installed
    if (-e "$targetdir/LICENSE") {
      printdelay("Removing license file:");
      `rm $targetdir/LICENSE 2>>$logfile`;
      printresult($?);
    }
    if (-e "$targetdir/CHANGELOG") {
      printdelay("Removing changelog file:");
      `rm $targetdir/CHANGELOG 2>>$logfile`;
      printresult($?);
    }
    if (-e "$targetdir/INSTALL") {
      printdelay("Removing install file:");
      `rm $targetdir/INSTALL 2>>$logfile`;
      printresult($?);
    }
  }
}

if (-e "/etc/apache2/sites-enabled/surfnetids-log-apache.conf") {
  printdelay("Disabling SURFids apache config:");
  `a2dissite surfnetids-log-apache.conf 2>>$logfile`;
  printresult($?);
}

if (-e "/etc/apache2/sites-available/surfnetids-log-apache.conf") {
  printdelay("Removing SURFids apache config:");
  `rm /etc/apache2/sites-available/surfnetids-log-apache.conf 2>>$logfile`;
  printresult($?);
}

if (-e "$targetdir/scripts/") {
  $chk = `ls $targetdir/scripts/ | wc -l`;
  chomp($chk);
  if ($chk == 0) {
    printdelay("Removing scripts directory:");
    `rm -r $targetdir/scripts/ 2>>$logfile`;
    printresult($?);
  }
}

if (-e "/etc/surfnetids/surfnetids-log.conf") {
  print "The SURFids logging server configuration file was not removed!\n";
}
print "Uninstallation complete!\n";

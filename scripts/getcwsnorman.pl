#!/usr/bin/perl
#################################################
# Norman/CWS Sandbox analysis retrieval script	#
# SURFnet IDS                      		#
# Version 1.04.01                  		#
# 02-05-2007                       		#
# Jan van Lith & Kees Trippelvitz  		#
################################################

###############################################
# Changelog:
# 1.04.01 Initial release
###############################################

####################
# Modules used
####################
use DBI;
use Mail::POP3Client;
use IO::Socket::SSL;

####################
# Variables used
####################
do '/etc/surfnetids/surfnetids-log.conf';

####################
# Main script
####################
$pop = new Mail::POP3Client(	USER     => $c_mail_username,
                               	PASSWORD => $c_mail_password,
				HOST     => $c_mail_mailhost,
				PORT	 => $c_mail_port,
				USESSL   => $c_mail_usessl,
				DEBUG	 => 0,
                           );


# if no msgs just exit
if (($pop->Count()) < 1) {
  print "No messages...\n";
  exit;
}

# if msgs, tell how many
print $pop->Count() . " messages found!\n";

# loop over msgs
for ($i = 1; $i <= $pop->Count(); $i++) {
  $mailfile="/var/tmp/mail$i";
  foreach ( $pop->Head($i) ) {
    if ($_ =~ /.*Subject:.*/) {
      @subject = split(/:/, $_);
      $subject = $subject[1];
    }
  }
  chomp($subject);
  print "Subject: $subject\n";
  if ($subject eq " [SANDBOX] Uploaded from web") {
    $body = $pop->Body($i) . "\n";
    open(LOG, "> $mailfile");
    print LOG "$body";
    close(LOG);	
    $count = `cat $mailfile | wc -l`;
    $count = $count - 27;
    $body = `tail -n $count $mailfile`;
    $body =~ s/'/ /g;	
    $body =~ s/\\/\\\\/g;	
    open(LOG, "> $mailfile");
    print LOG "$body";
    close(LOG);	
    $count2 = `cat $mailfile | wc -l`;
    $count2 = $count2 - 4;
    $body = `head -n $count2 $mailfile`;
	
    $md5 = `cat $mailfile |grep "MD5 hash:" | awk -F: '{print \$2}' |awk -F. '{print \$1}'`;
    $subject =~ s/^\s+//;
    $md5 =~ s/^\s+//;
    chomp($md5);
    $dbh = DBI->connect($c_dsn, $c_pgsql_user, $c_pgsql_pass)
        or die $DBI::errstr;
    $sth_md5 = $dbh->prepare("SELECT id FROM uniq_binaries WHERE name='$md5'");
    $execute_result = $sth_md5->execute();
    $numrows_md5 = $sth_md5->rows;
    @bin_id = $sth_md5->fetchrow_array; 
    $bin_id = $bin_id[0];
    if ($numrows_md5 == 0) {
      print "Adding md5: $md5 into uniq_binaries table\n";
      $sth_putmd5 = $dbh->prepare("INSERT INTO uniq_binaries (name) VALUES ('$md5')");
      $execute_result = $sth_putmd5->execute();
      $sth_md5 = $dbh->prepare("SELECT id FROM uniq_binaries WHERE name='$md5'");
      $execute_result = $sth_md5->execute();
      $numrows_md5 = $sth_md5->rows;
      @bin_id = $sth_md5->fetchrow_array; 
      $bin_id = $bin_id[0];
    }
    print "Adding new norman result info for binary ID: $bin_id\n";
    $sth_putnorman = $dbh->prepare("INSERT INTO norman (binid, result) VALUES ('$bin_id', '$body')");
    $execute_result = $sth_putnorman->execute();
  
    ##############
    # BINARIES_DETAIL
    ##############
    # Check if the binary was already in the binaries_detail table.
    $sql_checkbin = "SELECT bin FROM binaries_detail WHERE bin = $bin_id";
    $sth_checkbin = $dbh->prepare($sql_checkbin);
    $result_checkbin = $sth_checkbin->execute();
    $numrows_checkbin = $sth_checkbin->rows;
    if ($numrows_checkbin == 0) {
      # If not, we add the filesize and file info to the database. 
      # Getting the info from linux file command. 
    
      $filepath = "$c_surfidsdir/binaries/$md5";
      if (-e "$filepath") {
    	$fileinfo = `file $filepath`;
    	@fileinfo = split(/:/, $fileinfo);
    	$fileinfo = $fileinfo[1];
    	chomp($fileinfo);

    	# Getting the file size.
    	$filesize = (stat($filepath))[7];
     	
     	print "Adding new binary_detail info for binary ID: $bin_id\n";
    	$sql_checkbin = "INSERT INTO binaries_detail (bin, fileinfo, filesize) VALUES ($bin_id, '$fileinfo', $filesize)";
    	$sth_checkbin = $dbh->prepare($sql_checkbin);
    	$result_checkbin = $sth_checkbin->execute();
      } else { print "File does not exists"; }
    }
  }
  if ($subject =~ /.*CWSandbox-Analysis for ID.*/ ) {
    $body = $pop->Body($i) . "\n";
    open(LOG, "> $mailfile");
    print LOG "$body";
    close(LOG);	
    $count = `cat $mailfile | wc -l`;
    $counttail = $count - 9;
    $counthead = $counttail - 12;
    $body = `tail -n $counttail $mailfile| head -n $counthead`;
    open(LOG, "> $mailfile");
    print LOG "$body";
    close(LOG);
    $xmlfile = "$mailfile.xml"; 
    `openssl base64 -d -in $mailfile -out $xmlfile`;  
    $md5 = `cat $xmlfile |head -n3 |grep file | awk -F= '{print \$4}' | awk -F"\\"" '{print \$2}' | awk -F. '{print \$1}'`;
    $subject =~ s/^\s+//;
    $md5 =~ s/^\s+//;
    chomp($md5);
    `mv $xmlfile $c_surfidsdir/xml/$md5.xml`;
    $xmlfile = "$c_surfidsdir/xml/$md5.xml";
    print "Adding new CWS Sandbox XML result to $xmlfile\n";
    $dbh = DBI->connect($c_dsn, $c_pgsql_user, $c_pgsql_pass)
        or die $DBI::errstr;
    $sth_md5 = $dbh->prepare("SELECT id FROM uniq_binaries WHERE name='$md5'");
    $execute_result = $sth_md5->execute();
    $numrows_md5 = $sth_md5->rows;
    @bin_id = $sth_md5->fetchrow_array; 
    $bin_id = $bin_id[0];
    if ($numrows_md5 == 0) {
      print "Adding md5: $md5 into uniq_binaries table\n";
      $sth_putmd5 = $dbh->prepare("INSERT INTO uniq_binaries (name) VALUES ('$md5')");
      $execute_result = $sth_putmd5->execute();
      $sth_md5 = $dbh->prepare("SELECT id FROM uniq_binaries WHERE name='$md5'");
      $execute_result = $sth_md5->execute();
      $numrows_md5 = $sth_md5->rows;
      @bin_id = $sth_md5->fetchrow_array; 
      $bin_id = $bin_id[0];
    }

  
    ##############
    # BINARIES_DETAIL
    ##############
    # Check if the binary was already in the binaries_detail table.
    $sql_checkbin = "SELECT bin FROM binaries_detail WHERE bin = $bin_id";
    $sth_checkbin = $dbh->prepare($sql_checkbin);
    $result_checkbin = $sth_checkbin->execute();
    $numrows_checkbin = $sth_checkbin->rows;

  
    if ($numrows_checkbin == 0) {
      # If not, we add the filesize and file info to the database. 
      # Getting the info from linux file command. 
    
      $filepath = "$c_surfidsdir/binaries/$md5";
      if (-e "$filepath") {
    	$fileinfo = `file $filepath`;
    	@fileinfo = split(/:/, $fileinfo);
    	$fileinfo = $fileinfo[1];
    	chomp($fileinfo);

    	# Getting the file size.
    	$filesize = (stat($filepath))[7];
     	
     	print "Adding new binary_detail info for binary ID: $bin_id\n";
    	$sql_checkbin = "INSERT INTO binaries_detail (bin, fileinfo, filesize) VALUES ($bin_id, '$fileinfo', $filesize)";
    	$sth_checkbin = $dbh->prepare($sql_checkbin);
    	$result_checkbin = $sth_checkbin->execute();
      } 
      else { print "File does not exists\n"; 
      }
   } 
  }
}

# close connection
$pop->Close();
exit;

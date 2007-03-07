#!/usr/bin/perl
####################################
# Norman retrieval script          #
# SURFnet IDS                      #
# Version 1.04.02                  #
# 27-02-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

###############################################
# Changelog:
# 1.04.02 Fixed config file include
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
$pop = new Mail::POP3Client(	USER     => $c_norman_username,
                               	PASSWORD => $c_norman_password,
				HOST     => $c_norman_mailhost,
				PORT	 => $c_norman_port,
				USESSL   => $c_norman_usessl,
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
  print "subject: $subject\n";
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
    print "md5: $md5\n";
    $dbh = DBI->connect($c_dsn, $c_pgsql_user, $c_pgsql_pass)
        or die $DBI::errstr;
    $sth_md5 = $dbh->prepare("SELECT id FROM uniq_binaries WHERE name='$md5'");
    $execute_result = $sth_md5->execute();
    $numrows_md5 = $sth_md5->rows;
    @bin_id = $sth_md5->fetchrow_array; 
    $bin_id = $bin_id[0];
    if ($numrows_md5 == 0) {
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
    print "numrows in binaries_detail: $numrows_checkbin\n";
  
    if ($numrows_checkbin == 0) {
    
      # If not, we add the filesize and file info to the database. 
      # Getting the info from linux file command. 
    
      $filepath = "$bindir/$md5";
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
}

# close connection
$pop->Close();
exit;

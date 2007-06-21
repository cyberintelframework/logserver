#!/usr/bin/perl
####################################
# CWSandbox retrieval script       #
# SURFnet IDS                      #
# Version 1.04.03                  #
# 16-05-2007                       #
# Jan van Lith & Kees Trippelvitz  #
# Dave De Coster (Mods for CWS)    #
####################################

###############################################
# Changelog:
# 1.04.03 Added Norman sandbox support again
# 1.04.02 Skipping messages without md5
# 1.04.01 Initial release
###############################################

####################
# Modules used
####################
use DBI;
use Mail::POP3Client;
use IO::Socket::SSL;
use MIME::Parser;
use Encode;

####################
# Variables used
####################
do '/etc/surfnetids/surfnetids-log.conf';

####################
# Main script
####################
$pop = new Mail::POP3Client(    USER     => $c_norman_username,
                                PASSWORD => $c_norman_password,
                                HOST     => $c_norman_mailhost,
                                PORT     => $c_norman_port,
                                USESSL   => $c_norman_usessl,
                                DEBUG    => 0,
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
  $mailfile="$c_cwtemp/mail$i";
  $xml="$c_cwtemp/xml$i";
  foreach ( $pop->Head($i) ) {
    if ($_ =~ /.*Subject:.*/) {
      @subject = split(/:/, $_);
      $subject = $subject[1];
    }
  }
  chomp($subject);
  print "subject: $subject\n";

  if ($subject eq " [SANDBOX] Uploaded from web") {
    print "Found Norman sandbox report!\n";
    ################################
    # Norman Sandbox
    ################################
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
  }
  elsif ($subject =~ m/CWSandbox/) {
    print "Found CWSandbox report!\n";
    ################################
    # CWSandbox
    ################################
    $body = $pop->HeadAndBody($i) . "\n";
    open(LOG, "> $mailfile");
    print LOG "$body";
    close(LOG);

    # Rip the XML attachment out
    mimeextract($body);

    $md5 = `cat $xml | grep -m 1 md5 | cut -d \" \" -f 6 | egrep '^md5' | awk -F \"=\" '{print \$2}'`;
    $subject =~ s/^\s+//;
    $md5 =~ s/\"//g;
    chomp($md5);
    if ("$md5" eq "") {
      # Skip this one
      next;
    } else {
      print "md5: $md5\n";
    }
    $body = `$c_xalanbin $xml $c_surfidsdir/include/ViewAnalysis.xslt`;
    $body =~ s/'/ /g;
    $body =~ s/\\/\\\\/g;
    $body =~ s/\n+/\n/g;
    $xml2 = `cat $xml`;
    $xml2 =~ s/'/ /g;
    $xml2 =~ s/\\/\\\\/g;

    open(LOG, "> $mailfile");
    print LOG "$body";
    close(LOG);

    # This helps remove non-UTF8 characters that make postgres unhappy
    $body2 = encode("utf8", $body);

    $dbh = DBI->connect($c_dsn, $c_pgsql_user, $c_pgsql_pass)
        or die $DBI::errstr;
    $sth_md5 = $dbh->prepare("SELECT id FROM uniq_binaries WHERE name = '$md5'");
    $execute_result = $sth_md5->execute();
    $numrows_md5 = $sth_md5->rows;
    @bin_id = $sth_md5->fetchrow_array; 
    $bin_id = $bin_id[0];
    if ($numrows_md5 == 0) {
      $sth_putmd5 = $dbh->prepare("INSERT INTO uniq_binaries (name) VALUES ('$md5')");
      $execute_result = $sth_putmd5->execute();
      $sth_md5 = $dbh->prepare("SELECT id FROM uniq_binaries WHERE name = '$md5'");
      $execute_result = $sth_md5->execute();
      $numrows_md5 = $sth_md5->rows;
      @bin_id = $sth_md5->fetchrow_array; 
      $bin_id = $bin_id[0];
    }
    print "Adding new CWSandbox result info for binary ID: $bin_id\n";
    $sth_putcwsandbox = $dbh->prepare("INSERT INTO cwsandbox (binid, xml, result) VALUES ('$bin_id', '$xml2', '$body2')");
    $execute_result = $sth_putcwsandbox->execute();
  }
  if ("$md5" ne "") {
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
    
      $filepath = "$c_bindir/$md5";
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
    $md5 = "";
  }
}

# close connection
$pop->Close();

# Lets be nice and clean up our stuff
#my $dir = "/var/tmp/mimetemp";
my $word = "msg";
my $word2 = "analysis";

opendir (DIR,$c_cwmime);
@files = grep(/$word/, readdir (DIR));
closedir (DIR);

foreach $file (@files) {
  unlink "$c_cwmime/$file";
}

opendir (DIR,$c_cwmime);
@files2 = grep(/$word2/, readdir (DIR));
closedir (DIR);

foreach $file2 (@files2) {
  unlink "$c_cwmime/$file2";
}

rmdir($c_cwmime) || warn "Cannot rmdir mimetemp: $!";
exit;

sub dump_entity {
  my ($entity, $name) = @_;
  defined($name) or $name = "'anonymous'";
  my $IO;

  # Output the body:
  my @parts = $entity->parts;
  if (@parts) {
    # multipart...

    my $i;
    foreach $i (0 .. $#parts) {       # dump each part...
      dump_entity($parts[$i], ("$name, part ".(1+$i)));
    }
  } else { 
    # single part...

    # Get MIME type, and display accordingly...
    my ($type, $subtype) = split('/', $entity->head->mime_type);
    my $body = $entity->bodyhandle;
    if ($type =~ /^application$/) {
      if ($IO = $body->open("r")) {
        open(LOG, "> $xml");
        print LOG "$_" while (defined($_ = $IO->getline));
        close(LOG);
        $IO->close;
      } else {
        # d'oh!
        print "$0: couldn't find/open '$name': $!";
      }
    }
  }
}

sub mimeextract {
  # Create a new MIME parser:
  my $parser = new MIME::Parser;
    
  # Create and set the output directory:
  (-d "$c_cwmime") or mkdir "$c_cwmime",0755 or die "mkdir: $!";
  (-w "$c_cwmime") or die "can't write to directory";
  $parser->output_dir("$c_cwmime");
    
  # Read the MIME message:
  $entity = $parser->parse_data(@_) or die "couldn't parse MIME stream";

  # Dump it out:
  dump_entity($entity);
}

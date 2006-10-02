<?php
####################################
# SURFnet IDS                      #
# Version 1.02.02                  #
# 23-05-2006                       #
# Jan van Lith & Kees Trippelvitz  #
# Modified by Peter Arts           #
####################################

#############################################
# Changelog:			   	                #
# 1.02.02 Added new mailreporting arrays    #
#############################################

# The array with dialogue name, exploit name and additional info. Desc is not used at the moment.
$attacks_ar = array(
		'SMBDialogue'		=> array(
						'Attack'	=> 'ASN1',
						'Desc'		=> 'ASN1 exploit',
						'URL'		=> 'http://www.microsoft.com/technet/security/bulletin/ms04-007.mspx'),
		'BagleDialogue'		=> array(
						'Attack'	=> 'Bagle',
						'Desc'		=> 'Bagle worm',
						'URL'		=> 'http://www.f-secure.com/v-descs/bagle.shtml'),
		'DWDialogue'		=> array(
						'Attack'	=> 'Dameware',
						'Desc'		=> 'Dameware vulnerability',
						'URL'		=> ''),
		'DCOMDialogue'		=> array(
						'Attack'	=> 'DCOM',
						'Desc'		=> 'DCOM attack',
						'URL'		=> 'http://nepenthes.sourceforge.net/documentation:modules:vulnerability:vuln_dcom'),
		'FTPdDialogue'		=> array(
						'Attack'	=> 'FTPD',
						'Desc'		=> 'FTPD exploit',
						'URL'		=> ''),
		'IISDialogue'		=> array(
						'Attack'	=> 'IIS',
						'Desc'		=> 'IIS exploit',
						'URL'		=> 'http://nepenthes.sourceforge.net/documentation:modules:vulnerability:vuln_iis'),
		'Kuang2Dialogue'	=> array(
						'Attack'	=> 'Kuang2',
						'Desc'		=> 'Kuang2 exploit',
						'URL'		=> 'http://www3.ca.com/securityadvisor/pest/search.aspx?mode=scan&allwords=true&pst=KUANG'),
		'LSASSDialogue'		=> array(
						'Attack'	=> 'LSASS',
						'Desc'		=> 'LSASS vulnerability',
						'URL'		=> 'http://www.microsoft.com/technet/security/bulletin/MS04-011.mspx'),
		'MSDTCDialogue'		=> array(
						'Attack'	=> 'MSDTC/Dasher',
						'Desc'		=> 'Dasher worm',
						'URL'		=> 'http://www.microsoft.com/technet/security/Bulletin/MS05-051.mspx'),
		'MSMQDialogue'		=> array(
						'Attack'	=> 'MSMQ',
						'Desc'		=> 'MSMQ exploit',
						'URL'		=> 'http://www.microsoft.com/technet/security/bulletin/MS05-017.mspx'),
		'MSSQLDialogue'		=> array(
						'Attack'	=> 'MSSQL',
						'Desc'		=> 'MS02-061 exploit',
						'URL'		=> 'http://www.microsoft.com/technet/security/bulletin/MS02-039.mspx'),
		'MydoomDialogue'	=> array(
						'Attack'	=> 'MyDoom',
						'Desc'		=> 'MyDoom worm',
						'URL2'		=> 'http://vil.nai.com/vil/content/v_100983.htm'),
		'NETDDEDialogue'	=> array(
						'Attack'	=> 'NetDDE',
						'Desc'		=> 'NetDDE attack',
						'URL'		=> 'http://www.microsoft.com/technet/security/Bulletin/MS04-031.mspx'),
		'OPTIXBindDialogue'	=> array(
						'Attack'	=> 'OPTIX Bind',
						'Desc'		=> 'OPTIX Bind dialogue used for timeouts',
						'URL2'		=> 'http://www3.ca.com/securityadvisor/pest/search.aspx?mode=tmc&pst=optix&allwords=false&nameonly=false&type=6'),
		'OPTIXShellDialogue'	=> array(
						'Attack'	=> 'OPTIX Shell',
						'Desc'		=> 'OPTIX Shell Dialogue',
						'URL'		=> 'http://www3.ca.com/securityadvisor/pest/search.aspx?mode=tmc&pst=optix&allwords=false&nameonly=false&type=6'),
		'OPTIXDownloadDialogue'	=> array(
						'Attack'	=> 'OPTIX Download',
						'Desc'		=> 'OPTIX Download Dialogue',
						'URL'		=> 'http://www3.ca.com/securityadvisor/pest/search.aspx?mode=tmc&pst=optix&allwords=false&nameonly=false&type=6'),
		'PNPDialogue'		=> array(
						'Attack'	=> 'PNP',
						'Desc'		=> 'PNP Vulnerability',
						'URL'		=> 'http://www.microsoft.com/technet/security/Bulletin/MS05-039.mspx'),
		'SasserFTPDDialogue'	=> array(
						'Attack'	=> 'Sasser',
						'Desc'		=> 'Sasser exploit',
						'URL2'		=> 'http://www.microsoft.com/security/incident/sasser.mspx'),
		'SSHDialogue'		=> array(
						'Attack'	=> 'SSH vulnerability',
						'Desc'		=> 'SSH vulnerability simulation'),
		'SUB7Dialogue'		=> array(
						'Attack'	=> 'Sub7',
						'Desc'		=> 'Sub7 worm',
						'URL'		=> 'http://www3.ca.com/securityadvisor/pest/search.aspx?mode=tmc&pst=sub7&allwords=false&nameonly=false&type=6'),
		'UPNPDialogue'		=> array(
						'Attack'	=> 'UPNP',
						'Desc'		=> 'UPNP vulnerability',
						'URL'		=> 'http://www.microsoft.com/technet/security/bulletin/MS01-059.mspx'),
		'VERITASDialogue'	=> array(
						'Attack'	=> 'Veritas',
						'Desc'		=> 'Veritas vulnerability',
						'URL'		=> ''),
		'WINSDialogue'		=> array(
						'Attack'	=> 'WINS',
						'Desc'		=> 'WINS vulnerability',
						'URL'		=> 'http://nepenthes.sourceforge.net/documentation:modules:vulnerability:vuln_wins')
);

# Array with the severity as assigned by Nepenthes. The integers are set by Nepenthes, the text can be modified.
$severity_ar = array(
		'0'	=> 'Possible malicious attack',
		'1'	=> 'Malicious attack',
		'16'	=> 'Malware offered',
		'32'	=> 'Malware downloaded'
);

# Array with the type of attack as assigned by Nepenthes. The integers are set by Nepenthes, the text can be modified.
$attacktype_ar = array(
		'1'	=> 'Exploit dialogue',
		'2'	=> 'Shellcodehandler',
		'4'	=> 'Download url',
		'8'	=> 'Download hash'
);

# Array with the different types of access for the search engine.
$access_ar_search = array(
		'0'	=> 'No access',				# Disables search engine for that user.
                '1'	=> 'Organisation records',		# User can search all records of his organisation.
                '9'	=> 'All records (admin)'		# User can search all records.
);

# Array with the different types of access for the sensor remote control options.
$access_ar_sensor = array(
		'0'	=> 'Read only access',			# User can view all sensors of his organisation.
                '1'	=> 'Remote control access',		# User can view and control all sensors of his organisation.
                '2'	=> 'ARP Monitor access',		# User can add ARP monitoring entries.
                '9'	=> 'Total access (admin)'		# User can view and control all sensors.
);

# Array with the different types of access for the user administration.
$access_ar_user = array(
		'0'	=> 'No access',				# User cannot modify any user accounts.
                '1'	=> 'Own account',			# User can modify only his own account.
                '2'	=> 'Organisation accounts',		# User can modify and add user accounts for his organisation.
                '9'	=> 'All accounts (admin)'		# Total admin control for user and organisation administration.
);

# Array with the maillog options.
$maillog_ar = array (
		'0'	=> 'None',
		'1'	=> 'All attacks',
		'2'	=> 'Only from own ranges'
);

# Array with the mailreporting templates
$mail_template_ar = array(
		1 => "All attacks", 
		2 => "Own ranges", 
		3 => "Threshold"
);

# Array with the mailreporting priorities
$mail_priority_ar = array(
		1 => "Low",
		2 => "Normal",
		3 => "High"
);

# Array with the mailreporting targets
$mail_target_ar = array(
		1 => "Number malicious attacks"
);

# Array with the mailreporting timespans
$mail_timespan_ar = array(
		1 => "Last hour",
		2 => "Last day",
		3 => "Last week"
);

# Array with the mailreporting operators
$mail_operator_ar = array(
		1 => "<",
		2 => ">",
		3 => "<=",
		4 => ">=",
		5 => "=",
		6 => "!="
);

# Array with the mailreporting frequency
$mail_frequency_ar = array(
		1 => "Every hour",
		2 => "Every day",
		3 => "Every week"
);
?>

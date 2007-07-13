<?php
####################################
# SURFnet IDS                      #
# Version 1.04.22                  #
# 05-07-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.04.22 Added some more options to v_mail_detail array
# 1.04.21 Added IP address organisation identifier
# 1.04.20 Added detail array
# 1.04.19 Changed some variables values  
# 1.04.18 Removed some unused arrays
# 1.04.17 Added several ARGOS entries  
# 1.04.16 Added v_arp_alerts array
# 1.04.15 Added orgipadmin.php error messages
# 1.04.14 Changed help message for sensorstatus - action
# 1.04.13 Added error message for missing vlan id
# 1.04.12 Changed help messages
# 1.04.11 Added Argos attack
# 1.04.10 Added error messages for mailadmin
# 1.04.09 Added error messages
# 1.04.08 Added v_phplot_data_colors array
# 1.04.07 Added v_index_periods array
# 1.04.06 Added v_indexcolors array
# 1.04.05 Added error string for orgedit.php
# 1.04.04 Added v_ prefix to the variables
# 1.04.03 Added a few more error messages
# 1.04.02 Added org_ident_type_ar
# 1.04.01 Released as 1.04.01
# 1.03.02 Added sensor status to mail report templates
# 1.03.01 Released as part of the 1.03 package
# 1.02.02 Added new mailreporting arrays
#############################################

# The array with dialogue name, exploit name and additional info. Desc is not used at the moment.
$v_attacks_ar = array(
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
		'MS06070Dialogue'	=> array(
						'Attack'	=> 'Wkssvc',
						'Desc'		=> 'MS06070 vulnerability',
						'URL'		=> 'http://www.microsoft.com/technet/security/Bulletin/MS06-070.mspx'),
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
		'RealVNCDialogue'	=> array(
						'Attack'	=> 'RealVNC',
						'Desc'		=> 'RealVNC Vulnerability',
						'URL'		=> ''),
		'SasserFTPDDialogue'	=> array(
						'Attack'	=> 'Sasser',
						'Desc'		=> 'Sasser exploit',
						'URL2'		=> 'http://www.microsoft.com/security/incident/sasser.mspx'),
		'SAVDialogue'		=> array(
						'Attack'	=> 'Symantec AV',
						'Desc'		=> 'Symantec AntiVirus software bug',
						'URL'		=> 'http://www.symantec.com/avcenter/security/Content/2005.10.04.html'),
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
$v_severity_ar = array(
		'0'	=> 'Possible malicious attack',
		'1'	=> 'Malicious attack',
		'2'	=> 'Argos attack',
		'16'	=> 'Malware offered',
		'32'	=> 'Malware downloaded'
);

# Array with the type of attack as assigned by Nepenthes. The integers are set by Nepenthes, the text can be modified.
$v_attacktype_ar = array(
		'1'	=> 'Exploit dialogue',
		'2'	=> 'Shellcodehandler',
		'4'	=> 'Download url',
		'8'	=> 'Download hash',
		'10'	=> 'Argos ID',
		'12'	=> 'Process ID',
		'14'	=> 'OS',
		'16'	=> 'Imagename',
		'20'	=> 'Module',
		'22'	=> 'TCP Port',
		'24'	=> 'UDP Port'
);

# Array with the different types of access for the search engine.
$v_access_ar_search = array(
		'0'	=> 'No access',				# Disables search engine for that user.
                '1'	=> 'Organisation records',		# User can search all records of his organisation.
                '9'	=> 'All records (admin)'		# User can search all records.
);

# Array with the different types of access for the sensor remote control options.
$v_access_ar_sensor = array(
		'0'	=> 'Read only access',			# User can view all sensors of his organisation.
                '1'	=> 'Remote control access',		# User can view and control all sensors of his organisation.
                '2'	=> 'ARP & ARGOS access',		# User can add ARP monitoring + ARGOS redirecting.
                '9'	=> 'Total access (admin)'		# User can view and control all sensors.
);

# Array with the different types of access for the user administration.
$v_access_ar_user = array(
		'0'	=> 'No access',				# User cannot modify any user accounts.
                '1'	=> 'Own account',			# User can modify only his own account.
                '2'	=> 'Organisation accounts',		# User can modify and add user accounts for his organisation.
                '9'	=> 'All accounts (admin)'		# Total admin control for user and organisation administration.
);

# Array with the mailreporting templates
$v_mail_detail_ar = array(
		0 => "Mail - Summary", 
		1 => "Mail - Detail", 
		2 => "Mail - Summary + Detail",
		3 => "Mail - IDMEF Detail",
		10 => "RSS - Summary",
		11 => "RSS - Summary + Detail"
);

# Array with the mailreporting templates
$v_mail_sdetail_ar = array(
		2 => "Mail - Summary + Detail",
		11 => "RSS - Summary + Detail"
);

# Array with the mailreporting templates
$v_mail_template_ar = array(
		1 => "All attacks", 
		2 => "Own ranges", 
		4 => "Sensor status",
		5 => "ARP Alert"
);

# Array with the mailreporting priorities
$v_mail_priority_ar = array(
		1 => "Low",
		2 => "Normal",
		3 => "High"
);

$v_sensor_sev_ar = array(
		1 => "Sensor failed to start",
		2 => "Sensor down"
);

# Array with the mailreporting operators
$v_mail_operator_ar = array(
		1 => "<",
		2 => ">",
		3 => "<=",
		4 => ">=",
		5 => "=",
		6 => "!="
);

# Array with the mailreporting frequency
$v_mail_frequency_attacks_ar = array(
		1 => "Every hour",
		2 => "Every day",
		3 => "Every week",
		4 => "Threshold"
);

# Array with the mailreporting frequency
$v_mail_frequency_sensors_ar = array(
		1 => "Every hour",
		2 => "Every day",
		3 => "Every week"
);

# Array with the mailreporting frequency
$v_mail_timespan_ar = array(
		1 => "Last hour",
		2 => "Last 24 hours",
		3 => "Last 7 days"
);

# Array with the organisation identifier types
$v_org_ident_type_ar = array(
		0 => "IP address",
		1 => "Random Identifier String",
		2 => "WHOIS netname",
		3 => "Domain name",
		4 => "SURFnet SOAP"
);

$v_errors = array(
	arpadmin.php => array(
				1 => "Succesfully added a new static ARP entry!",
				2 => "Succesfully deleted a static ARP entry!",
				3 => "Succesfully cleared the ARP cache!",
				4 => "Enabled the ARP module for this sensor!",
				5 => "Disabled the ARP module for this sensor!",
				90 => "You don't have sufficient rights to perform the requested action!",
				91 => "Invalid hash!",
				92 => "Missing or invalid MAC address!",
				93 => "Missing or invalid IP address!",
				94 => "Missing or invalid sensor ID!",
				95 => "Missing or invalid ID!",
				96 => "This entry already exists!",
				97 => "Missing or invalid filter ID!",
				98 => "Cannot add this record. MAC/IP pair is possibly poisoned!"
			),
	binaryhist.php => array(
				91 => "No info could be found for the given binary!"
			),
	logdetail.php => array(
				91 => "Wrong or missing ID!"
			),
	login.php => array(
				91 => "Username or password was incorrect!"
			),
	loglist.php => array(
				91 => "No organisation was given!"
			),
	mailadmin.php => array(
				1 => "Updated mail settings!",
				2 => "Disabled all reports!",
				3 => "Enabled all reports!",
				4 => "Reset all timestamps!",
				5 => "Successfully added a new report!",
				6 => "Successfully deleted a report!",
				7 => "Successfully saved the changes!",
				86 => "Invalid or missing report ID!",
				90 => "You don't have sufficient rights to perform the requested action!",
				91 => "Invalid hash!",
				92 => "Invalid action method!",
				96 => "Invalid or missing report ID!"
			),
	orgadmin.php => array(
				1 => "Successfully added the organisation!",
				89 => "Invalid hash!",
				91 => "Admin rights are required to access this page!",
				95 => "Invalid update type!",
				96 => "Invalid or missing organisation name!",
				99 => "Organisation already exist!"
			),
	orgedit.php => array(
				1 => "Successfully saved the organisation details!",
				2 => "Successfully deleted the organisation identifier!",
				89 => "Invalid hash!",
				90 => "Invalid ranges string!",
				91 => "Admin rights are required to access this page!",
				92 => "Organisation ID was not set!",
				93 => "Identifier ID was not set!",
				94 => "The organisations supplied doesn't exist!",
				95 => "Invalid update type!",
				96 => "Invalid or missing organisation name!",
				97 => "Invalid or missing identifier!",
				98 => "Invalid or missing identifier type!",
				99 => "Organisation already exist!"
			),
	orgipadmin.php => array(
				1 => "Succesfully added a new exclusion!",
				2 => "Succesfully removed exclusion!",
				91 => "You don't have sufficient rights to perform the requested action!",
				92 => "Exclusion IP was not a valid IP address!",
				93 => "Invalid organisation ID!"
			),
	report_mod.php => array(
				91 => "You don't have sufficient rights to perform the requested action!",
			),
	report_new.php => array(
				90 => "Invalid or missing subject!",
				91 => "Invalid or missing priority!",
				92 => "Invalid or missing frequency!",
				93 => "Invalid or missing interval!",
				94 => "Invalid or missing operator and/or threshold value!",
				95 => "Invalid or missing sensor ID!",
				96 => "Invalid or missing template!",
				97 => "Invalid or missing severity!",
				98 => "Invalid hash!",
				99 => "You don't have sufficient rights to perform the requested action!"
			),
	report_edit.php => array(
				89 => "Invalid or missing status!",
				90 => "Invalid or missing subject!",
				91 => "Invalid or missing priority!",
				92 => "Invalid or missing frequency!",
				93 => "Invalid or missing interval!",
				94 => "Invalid or missing operator and/or threshold value!",
				95 => "Invalid or missing sensor ID!",
				96 => "Invalid or missing template!",
				97 => "Invalid or missing severity!",
				98 => "Invalid hash!",
				99 => "You don't have sufficient rights to perform the requested action!"
			),
	searchtemplate.php => array(
				1 => "Successfully saved searchtemplate!",
				92 => "Invalid user!",
				93 => "Invalid title!",
				94 => "Searchtemplate not saved!"
			),
	sensorstatus.php => array(
				1 => "Successfully saved the sensor status!",
				2 => "Successfully enabled ARP detection for this sensor!",
				3 => "Successfully disabled ARP detection for this sensor!",
				91 => "You don't have sufficient rights to perform the requested action!",
				92 => "Invalid or missing action!",
				93 => "Invalid or missing sensor ID!",
				94 => "Invalid or missing tap IP address!",
				95 => "Invalid or missing VLAN ID!"
			),
	serveradmin.php => array(
				1 => "Successfully added a new server!",
				91 => "You don't have sufficient rights to perform the requested action!",
				92 => "Invalid or missing server ID!",
				93 => "There has to be at least 1 server. Create one first before deleting this one!",
				94 => "Invalid or missing server!"
			),
	traffic.php => array(
				92 => "There are no active sensors!"
			),
	trafficview.php => array(
				92 => "Invalid or missing sensor ID!"
			),
	useradmin.php => array(
				1 => "Successfully added new user!",
				2 => "Successfully deleted the user!",
				3 => "Successfully saved the user details!",
				91 => "You don't have sufficient rights to perform the requested action!",
				92 => "Invalid or missing username!",
				93 => "Invalid or missing password or confirmation!",
				94 => "Supplied password did not match the confirmation password!",
				95 => "Invalid or missing organisation!",
				96 => "Invalid or missing user ID!"
			),
	useredit.php => array(
				96 => "Invalid or missing User ID!"
			),
	virusadmin.php => array(
				91 => "You don't have sufficient rights to perform the requested action!"
			),
	argosadmin.php => array(
				1 => "Successfully added new argos redirect!",
				2 => "Successfully deleted the redirect to argos!",
				3 => "Successfully updated the argos redirect!",
				11 => "Successfully added new image!",
				12 => "Successfully deleted image and corresponding linked sensors!",
				13 => "Successfully updated image!",
				21 => "Successfully added new range redirect!",
				22 => "Successfully deleted redirect of range!",
				23 => "Successfully updated redirect!",
				91 => "You don't have sufficient rights to perform the requested action!",
				92 => "Sensor already redirected!",
				93 => "Image name already exists!",
				94 => "MAC Address already exists!",
				95 => "You are not allowed to add a redirect to this image!",
				99 => "Invalid or missing Argos ID!"
			)
);

$v_plottertypes = array(
				1 => "bars",
				2 => "lines",
				3 => "linepoints",
				4 => "area",
				5 => "points",
				6 => "pie",
				7 => "thinbarline",
				8 => "squared"
);

$v_ipregexp = '^([0-9]|[1-9][0-9]|1([0-9][0-9])|2([0-4][0-9]|5[0-5]))';
$v_ipregexp .= '\.([0-9]|[1-9][0-9]|1([0-9][0-9])|2([0-4][0-9]|5[0-5]))';
$v_ipregexp .= '\.([0-9]|[1-9][0-9]|1([0-9][0-9])|2([0-4][0-9]|5[0-5]))';
$v_ipregexp .= '\.([0-9]|[1-9][0-9]|1([0-9][0-9])|2([0-4][0-9]|5[0-5]))$';

$v_indexcolors = array(
			0 => "Red",
			1 => "DarkOrange",
			2 => "Gold",
			3 => "Yellow",
			4 => "LawnGreen",
			5 => "LimeGreen",
			6 => "SeaGreen"
);

$v_index_periods = array(
			0 => "Today",
			1 => "Last 7 days"
);

$v_phplot_data_colors = array(
	"beige", "black", "blue", "brown", "cyan", "DarkGreen", "DimGrey", "gold", "green", "lavender", "magenta", "maroon", "navy", 
	"orange", "orchid", "PeachPuff", "peru", "pink", "plum", "purple", "red", "salmon", "SkyBlue", "SlateBlue", "tan", "violet", 
	"wheat", "yellow", "YellowGreen"
);

$v_help = array(
	arpadmin.php => array(
		"arpcache" => "This is the ARP cache that the ARP module keeps track of. This ARP cache gets filled based on ARP queries and replies that are detected by the sensor.",
		"arpmonitor" => "These are the MAC/IP pairs that are to be scanned by the ARP module. Whenever a change is detected that differs from these pairs, an alert is generated. Add the MAC/IP pair of your important servers in here. The scripts list is updated every $c_arp_static_refresh seconds."
	),
	logindex.php => array(
		0 => "This is a possible attack. This can be in fact any connection that is made to the sensor (portscans, random network traffic, etc).",
		1 => "At this point it&#39;s certain that the connection that was made to the sensor was a malicious connection.<br /> An exploit was used to try and gain entry to the system.",
		2 => "Argos detected a control flow diversion (e.g. caused by a buffer overflow or code injection).",
		16 => "A piece of malware is offered to the honeypot. The honeypot will try to download it.",
		32 => "The malware was succesfully downloaded to the honeypot."
	),
	orgedit.php => array(
		"ranges" => "The IP network ranges of the organisations networks. These ranges are used to check for attacks sourced from these ranges.",
		"ris" => "This is a unique string to identify the organisation.<br /> This can be placed on the sensor to make sure it will be placed in the correct organisation.",
	),
	sensorstatus.php => array(
		"sensor" => "The name of the sensor (VLAN number included if applicable).",
		"remote" => "The sensor IP address that&#39;s connecting to the tunnel server.",
		"local" => "The actual IP address of the sensor. This will differ from the remote IP address in case of NAT.",
		"tapmac" => "The MAC address of the virtual device on the server.",
		"tap" => "The virtual device on the tunnel server. This is in fact the tunnel endpoint on the server.",
		"tapip" => "The IP address of the virtual device on the server.",
		"status" => "The current status of the sensor.",
		"action" => "Possible actions that can be given to the sensor. The action will be executed once an hour along with the sensor updates.",
		"timestamps" => "Uptime and several other timestamps.",
		"static" => "This is the IP address on the virtual interface on the server.<br />This IP needs to be an IP from the same network range as the sensor.<br /> It cannot be the same IP address as the local or remote address!",
		"statusred" => "The sensor client was stopped by either a user or the server admin. The tunnel is not active at this moment.",
		"statusgreen" => "The sensor is active and correctly running.",
		"statusorange" => "The sensor missed a status update. These updates are used to synchronize data between the sensor and the server as well as to update the sensor scripts if needed.",
		"statusyellow" => "The sensor is trying to start up. This status is set when the sensor is started but a tap interface and tap IP address have not yet been acquired.",
		"statusblack" => "The sensor has been disabled by a server admin. This will mean the sensor cannot be started.",
		"statusblue" => "The sensor needs configuration. This is a status particularly for statically configured sensors. Configure the tap IP address to fix this.",
		"statusnone" => "The sensor is on ignore. This can be either because a different sensor of the same name is active or the sensor is manually ignored."
	),
	argosadmin.php => array(
		"sensor" => "The name of the sensor (VLAN number included if applicable).",
		"deviceip" => "The virtual device on the tunnel server. This IP address will be configured on the argos image",
		"imagename" => "The name of the Argos image.",
		"template" => "The template to be used. This will redirect IP addresses to the argos image you selected.<br /> The redirection is done by getting the possible malicious attacks that where not malicious after that.",
		"timespan" => "The timespan used to calculate the IP addresses that will be redirected.",
	)

);

$v_arp_alerts = array(
	1 => "ARP Poisoning"
);

$v_host_types = array(
	1 => "Router/Gateway",
	2 => "DHCP Server"
);

$v_proto_types = array(
	1 => "Ethernet",
	2 => "IP",
	3 => "ICMP"
);
?>

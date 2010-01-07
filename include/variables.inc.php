<?php
####################################
# SURFids 3.00                     #
# Changeset 005                    #
# 22-07-2009                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 005 Added some comments about detail records
# 004 Added Snort support
# 003 Added new error message
# 002 Added Last 24 Hours option for timeselector
# 001 Added selview array
#############################################

# Array with the severity of the attack.
$v_severity_ar = array(
        '0'	=> 'Possible malicious attack',
        '1'	=> 'Malicious attack',
        '16'	=> 'Malware offered',
        '32'	=> 'Malware downloaded'
);

# Array with the type of malicious attack.
$v_severity_atype_ar = array(
        0 => "Nepenthes",
        1 => "Argos",
        2 => "Snort",
        3 => "Glastopf",
        4 => "Amun",
        5 => "Dionaea",
        6 => "SMTP",
        10 => "ARP Poisoning",
        11 => "Rogue DHCP server"
);

# Array with the type of detail info record.
# These are the reserved numbers so far for the different detection methods
# 1-9 -> Nepenthes
# 10-29 -> Argos
# 30-39 -> detectarp.pl
# 40-59 -> Snort
# 60-69 -> Glastopf
# 70-79 -> SMTP
# 100-140 -> Dionaea
$v_attacktype_ar = array(
		'1'	=> 'Exploit dialogue',
		'2'	=> 'Shellcodehandler',
		'4'	=> 'Download url',
		'8'	=> 'Download hash',
		'10' => 'Argos ID',
		'12' => 'Process ID',
		'14' => 'OS',
		'16' => 'Imagename',
		'20' => 'Module',
		'22' => 'TCP Port',
		'24' => 'UDP Port',
		'30' => 'DHCP server identifier',
        '40' => 'Snort Message',
        '41' => 'Snort Sig Class Name',
        '42' => 'Snort Protocol',
        '60' => 'Request',
        '61' => 'Referer',
        '62' => 'User Agent',
        '70' => 'Sender address',
        '71' => 'Recipient address',
        '72' => 'Attachment filename',
        '73' => 'Attachment MD5',
        '74' => 'URL',
        '75' => 'Subject',
        '80' => 'Dialogue',
        '81' => 'Emulation Profile',
        '82' => 'Connectback',
);

# Array with the different types of access for the search engine.
$v_access_ar_search = array(
        		'0'	=> 'No access',		    		# Disables search engine for that user.
                '1'	=> 'Domain records',			# User can search all records of his domain.
                '9'	=> 'All records (admin)'		# User can search all records.
);

# Array with the different types of access for the sensor remote control options.
$v_access_ar_sensor = array(
		        '0'	=> 'Read only access',			# User can view all sensors of his domain.
                '1'	=> 'Remote control access',		# User can view and control all sensors of his domain.
                '2'	=> 'ARP & ARGOS access',		# User can add ARP monitoring + ARGOS redirecting.
                '9'	=> 'Total access (admin)'		# User can view and control all sensors.
);

# Array with the different types of access for the user administration.
$v_access_ar_user = array(
        		'0'	=> 'No access',				    # User cannot modify any user accounts.
                '1'	=> 'Own account',			    # User can modify only his own account.
                '2'	=> 'Domain accounts',			# User can modify and add user accounts for his domain.
                '9'	=> 'All accounts (admin)'		# Total admin control for user and domain administration.
);

# Array with the kind of report.
$v_mail_detail_ar = array(
		0 => "Mail - Summary", 
		1 => "Mail - Detail", 
		2 => "Mail - Summary/Detail",
		3 => "Mail - IDMEF Detail",
		4 => "Mail - Cymru markup",			# <ASN>  | <IP>  | <time> <info> | <ASN description>
		5 => "Mail - Nepenthes markup",			# [<time>] <source> -> <url> <md5>
		10 => "RSS - Summary",
		11 => "RSS - Summary/Detail"
);

# Array with the kind of report for sensor status.
$v_mail_sdetail_ar = array(
		2 => "Mail - Summary + Detail",
		11 => "RSS - Summary + Detail"
);

# Array with the mailreporting templates.
$v_mail_template_ar = array(
		1 => "All attacks", 
		2 => "Own ranges", 
		4 => "Sensor status",
		5 => "ARP Alert",
		6 => "Search",
		7 => "DHCP servers"
);

# Array with the mailreporting priorities.
$v_mail_priority_ar = array(
		1 => "Low",
		2 => "Normal",
		3 => "High"
);

# Array with the different severities for the sensor status report template.
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

# Array with the mailreporting frequency for the attacks templates.
$v_mail_frequency_attacks_ar = array(
		1 => "Every hour",
		2 => "Every day",
		3 => "Every week",
		4 => "Threshold"
);

# Array with the mailreporting frequency for the sensor status template.
$v_mail_frequency_sensors_ar = array(
		1 => "Every hour",
		2 => "Every day",
		3 => "Every week"
);

# Array with the timespan for threshold reports.
$v_mail_timespan_ar = array(
		1 => "Last hour",
		2 => "Last 24 hours",
		3 => "Last 7 days"
);

# Array with the organisation identifier types.
$v_org_ident_type_ar = array(
		0 => "IP address",
		1 => "OID",
		2 => "WHOIS netname",
		3 => "Domain name",
		4 => "SURFnet SOAP"
);

# Array with the different error messages
$v_errors = array(
		1 => "Successfully added a new record!",
		2 => "Successfully deleted a record!",
		3 => "Successfully altered a record!",
		4 => "Successfully cleared the ARP cache!",
		5 => "Successfully cleared the timestamps!",
		6 => "Disabled all reports!",
		7 => "Enabled all reports!",
		8 => "Successfully purged events!",
		9 => "Successfully retrieved data!",
		10 => "Successfully stored defaults!",
        11 => "Successfully added %1% %2%",
		100 => "Session expired, login again!",
		101 => "You don't have sufficient rights to perform the requested action!",
		102 => "Invalid or missing name!",
		103 => "Invalid or missing OS!",
		104 => "Invalid or missing OS language!",
		105 => "Invalid or missing image name!",
		106 => "Invalid or missing server IP!",
		107 => "Invalid or missing organisation ID!",
		108 => "Record already exists!",
		109 => "MAC address is already in use!",
		110 => "Invalid or missing sensor ID!",
		111 => "Invalid or missing template ID!",
		112 => "Invalid or missing image ID!",
		113 => "Invalid or missing timespan!",
		114 => "Invalid or missing range!",
		115 => "Invalid or missing Argos ID!",
		116 => "Invalid or missing hash checksum!",
		117 => "Invalid or missing ID!",
		118 => "Invalid or missing type!",
		119 => "Invalid or missing action!",
		120 => "Invalid or missing MAC address!",
		121 => "Invalid or missing IP address!",
		122 => "Cannot add this record. MAC/IP pair is possibly poisoned!",
		123 => "This record already exists!",
		124 => "Binary info could not be found!",
		125 => "Invalid username/password combination!",
		126 => "Invalid or missing identifier!",
		127 => "Invalid or missing VLAN ID!",
		128 => "Invalid or missing subject!",
		129 => "Invalid or missing priority!",
		130 => "Invalid or missing template!",
		131 => "Invalid or missing severity!",
		132 => "Invalid or missing frequency!",
		133 => "Invalid or missing interval!",
		134 => "Invalid or missing threshold!",
		135 => "Invalid or missing report ID!",
		136 => "Invalid or missing status!",
		137 => "Invalid or missing password!",
		138 => "Username already exists!",
		139 => "Invalid or missing user ID!",
		140 => "Invalid or missing network range!",
		141 => "Invalid or missing range ID!",
		142 => "Invalid or missing query string!",
		143 => "Invalid or missing feed ID!",
		144 => "Invalid or missing username!",
		145 => "Invalid or missing group name!",
		146 => "A public group needs to be approved by an admin. The admin has been notified!",
		147 => "Invalid or missing group type!",
		148 => "Invalid or missing group detail!",
		149 => "Invalid or missing status!",
		150 => "Invalid or missing group ID!",
		151 => "Sensor already a member of this group!",
		152 => "The group needs to be approved by an admin first!",
		153 => "Invalid or missing default graph!",
		154 => "Invalid or missing default graph type!",
		155 => "Invalid IP address. Choose a different IP address!",
		156 => "Invalid or missing page configuration array!",
		157 => "Invalid or missing page ID!",
        158 => "Invalid or missing note!",
        159 => "Invalid or missing VLAN specification!",
        160 => "Invalid or missing note ID!",
        161 => "Invalid or missing sensor details record!<br /><br />More info in the <a href='$c_faq_url'>FAQ L13</a>.",
        162 => "Invalid or missing module ID!",
        163 => "Invalid or missing module page!",
        164 => "Invalid or missing orgsavetype!",
        165 => "Invalid or missing organisation name!",
        166 => "Invalid or missing IP ranges!"
);

# Array for the different types of plots available.
/*
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
*/

$v_plottertypes = array(
			1 => "bars",
			2 => "lines",
			4 => "area",
			6 => "pie"
);

# Array with the colors used in the index page.
$v_indexcolors = array(
			0 => "Red",
			1 => "DarkOrange",
			2 => "Gold",
			3 => "Yellow",
			4 => "LawnGreen",
			5 => "LimeGreen",
			6 => "SeaGreen"
);

# Array with the different colors that are useable by phplot.
$v_phplot_data_colors = array(
	"black", "blue", "brown", "cyan", "DarkGreen", "DimGrey", "gold", "green", "lavender", "magenta", "maroon", "navy", 
	"orange", "orchid", "PeachPuff", "peru", "pink", "plum", "purple", "red", "salmon", "SkyBlue", "SlateBlue", "tan", "violet", 
	"wheat", "yellow", "YellowGreen"
);

# Array with the different host types.
$v_host_types = array(
	1 => "Router/Gateway",
	2 => "DHCP Server"
);

# Array with the different protocols detected by the sniffer.
$v_proto_types = array(
	0 => "Ethernet",
	1 => "IPv4",
	11 => "ICMP",
	12 => "IGMP",
	11768 => "DHCP"
);

# Array with the different timespans for the selector
$v_selector_period = array(
        0 => "Last Hour",
	1 => "Last 24 Hours",
	2 => "Today",
	3 => "Last 7 days",
	4 => "This Week",
	5 => "Last Week",
	6 => "This Month",
	7 => "Last Month",
	8 => "This Year"
);

# Array with the text messages and classes for the different status values of a sensor
$v_sensorstatus_ar = array(
	0 => array(
		'class' => "offline",
		'text' => "Offline"
	),
	1 => array(
		'class' => "online",
		'text' => "Online"
	),
	2 => array(
		'class' => "permanent",
		'text' => "Permanent"
	),
	3 => array(
		'class' => "ignored",
		'text' => "Ignored"
	),
	4 => array(
		'class' => "keepalive",
		'text' => "Missing keepalive"
	),
	5 => array(
		'class' => "config",
		'text' => "Configuration"
	),
	6 => array(
		'class' => "starting",
		'text' => "Starting up"
	),
	7 => array(
		'class' => "outdated",
		'text' => "Out of date"
	)
);

# Array with the textual representation of the different netconf values
$v_sensornetconf_ar = array(
    'vlan' => "VLAN",
    'normal' => "Normal",
	'dhcp' => "DHCP",
	'vland' => "VLAN DHCP",
	'static' => "Static",
	'vlans' => "VLAN Static"
);

# Array with the different available whois servers
$v_whois_servers = array(
	'ripe' => "RIPE",
	'arin' => "ARIN",
	'apnic' => "APNIC",
	'lacnic' => "LACNIC",
	'afrinic' => "AFRINIC",
	'krnic' => "KRNIC",
	'jpnic' => "JPNIC",
    'cymru' => "CYMRU"
);

# Array with the different destination search option
$v_search_dest_ar = array (
	'1' => "IP or Range",
	'2' => "MAC Address",
	'3' => "Sensor"
);

# Array with the different source search option
$v_search_src_ar = array (
	'1' => "IP or Range",
	'2' => "MAC Address",
	'3' => "Own Ranges"
);

$v_weekdays = array("", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday");

$v_os_languages = array(
	'nl' => "Dutch",
	'en' => "English"
);

$v_selview_ar = array(
	0 => "View all sensors",
	1 => "View offline sensors",
	2 => "View online sensors",
	9 => "View deactivated sensors"
);

$v_group_type_ar = array(
	0 => "Sub-Domain",
	1 => "Global"
);

$v_plotters_ar = array(
	0 => "Open Flash Chart",
	1 => "PHPlot"
);

$v_timestamp_format_ar = array(
	0 => "Local time",
	1 => "UTC time"
);

# Array with configurable pages
$v_page_select_ar = array(
	0 => "Home",
	1 => "Sensor Status"
);

# Array with the different syslog levels
$v_syslog_levels_ar = array(
	0 => "Debug",
	1 => "Info",
	2 => "Warning",
	3 => "Error",
	4 => "Critical"
);

$v_note_types_ar = array(
    1 => "Status",
    2 => "Other",
    3 => "Contact"
);

$v_note_all_ar = array(
    1 => "All Vlans",
    2 => "Current VLAN only"
);

$v_indexmod_ar = array(
    1 => "Attacks",
    2 => "Exploits",
    3 => "Search",
    4 => "Top 10 attackers",
    5 => "Top 10 protocols",
    6 => "Top 5 virusses",
    7 => "Cross domain",
    8 => "Malware offered",
    9 => "Sensor status",
    10 => "Ports",
    11 => "Top 10 sensors",
    12 => "Top 10 malicious countries"
);

?>

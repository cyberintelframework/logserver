<?php
####################################
# Protocol Types library           #
# SURFids 3.00                     #
# Changeset 002                    #
# 15-10-2009                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#####################
# Changelog:
# 002 Added Mobility Header and Shim6
# 001 Initial release
#####################

$v_protos_main_ar = array(
    0 => "Ethernet",
    1 => "IPv4",
    6 => "TCP",
    11 => "ICMP",
    12 => "IGMP",
    17 => "UDP",
    11768 => "DHCP",
);

$v_protos_dhcp_ar = array(
	1 => "DHCPDISCOVER",
	2 => "DHCPOFFER",
	3 => "DHCPREQUEST",
	4 => "DHCPDECLINE",
	5 => "DHCPACK",
	6 => "DHCPNAK",
	7 => "DHCPRELEASE",
	8 => "DHCPINFORM",
	9 => "DHCPFORCERENEW",
	13 => "DHCPLEASEQUERY",
);

$v_protos_icmp_ar = array(
	"0" => "Echo Reply",
	"1" => "Unassigned",
	"2" => "Unassigned",
	"3" => "Destination Unreachable",
	"4" => "Source Quench",
	"5" => "Redirect",
	"6" => "Alternate Host Address",
	"7" => "Unassigned",
	"8" => "Echo",
	"9" => "Router Advertisement",
	"10" => "Router Solicitation",
	"11" => "Time Exceeded",
	"12" => "Parameter Problem",
	"13" => "Timestamp",
	"14" => "Timestamp Reply",
	"15" => "Information Request",
	"16" => "Information Reply",
	"17" => "Address Mask Request",
	"18" => "Address Mask Reply",
	"19" => "Reserved (for Security)",
	"20" => "Reserved (for Robustness Experiment)",
	"21" => "Reserved (for Robustness Experiment)",
	"22" => "Reserved (for Robustness Experiment)",
	"23" => "Reserved (for Robustness Experiment)",
	"24" => "Reserved (for Robustness Experiment)",
	"25" => "Reserved (for Robustness Experiment)",
	"26" => "Reserved (for Robustness Experiment)",
	"27" => "Reserved (for Robustness Experiment)",
	"28" => "Reserved (for Robustness Experiment)",
	"29" => "Reserved (for Robustness Experiment)",
	"30" => "Traceroute",
	"31" => "Datagram Conversion Error",
	"32" => "Mobile Host Redirect",
	"33" => "IPv6 Where-Are-You",
	"34" => "IPv6 I-Am-Here",
	"35" => "Mobile Registration Request",
	"36" => "Mobile Registration Reply",
	"37" => "Domain Name Request",
	"38" => "Domain Name Reply",
	"39" => "SKIP",
	"40" => "Photuris",
);

$v_protos_igmp_ar = array(
        0 => array(
            1 => "Create Group Request",
            2 => "Create Group Reply",
            3 => "Join Group Request",
            4 => "Join Group Reply",
            5 => "Leave Group Request",
            6 => "Leave Group Reply",
            7 => "Confirm Group Request",
            8 => "Confirm Group Reply",
        ),
        1 => array(
            1 => "Host Membership Query",
            2 => "Host Membership Report",
            3 => "DVMRP"
        ),
        2 => array(
            17 => "Group Membership Query",
            18 => "IGMPv1 Membership Report",
            19 => "DVMRP",
            20 => "PIMv1",
            21 => "Cisco Trace Messages",
            22 => "IGMPv2 Membership Report",
            23 => "IGMPv2 Leave Group",
            30 => "Multicast Traceroute Response",
            31 => "Multicast Traceroute",
            34 => "IGMPv3 Membership Report",
            48 => "MRD, Multicast Router Advertisement",
            49 => "MRD, Multicast Router Solicitation",
            50 => "MRD, Multicast Router Termination" 
        ),
);

$v_protos_ipv4_ar = array(
	"0" => "HOPOPT",
	"1" => "ICMP",
	"2" => "IGMP",
	"3" => "GGP",
	"4" => "IP",
	"5" => "ST",
	"6" => "TCP",
	"7" => "CBT",
	"8" => "EGP",
	"9" => "IGP",
	"10" => "BBN-RCC-MON",
	"11" => "NVP-II",
	"12" => "PUP",
	"13" => "ARGUS",
	"14" => "EMCON",
	"15" => "XNET",
	"16" => "CHAOS",
	"17" => "UDP",
	"18" => "MUX",
	"19" => "DCN-MEAS",
	"20" => "HMP",
	"21" => "PRM",
	"22" => "XNS-IDP",
	"23" => "TRUNK-1",
	"24" => "TRUNK-2",
	"25" => "LEAF-1",
	"26" => "LEAF-2",
	"27" => "RDP",
	"28" => "IRTP",
	"29" => "ISO-TP4",
	"30" => "NETBLT",
	"31" => "MFE-NSP",
	"32" => "MERIT-INP",
	"33" => "DCCP",
	"34" => "3PC",
	"35" => "IDPR",
	"36" => "XTP",
	"37" => "DDP",
	"38" => "IDPR-CMTP",
	"39" => "TP++",
	"40" => "IL",
	"41" => "IPv6",
	"42" => "SDRP",
	"43" => "IPv6-Route",
	"44" => "IPv6-Frag",
	"45" => "IDRP",
	"46" => "RSVP",
	"47" => "GRE",
	"48" => "DSR",
	"49" => "BNA",
	"50" => "ESP",
	"51" => "AH",
	"52" => "I-NLSP",
	"53" => "SWIPE",
	"54" => "NARP",
	"55" => "MOBILE",
	"56" => "TLSP",
	"57" => "SKIP",
	"58" => "IPv6-ICMP",
	"59" => "IPv6-NoNxt",
	"60" => "IPv6-Opts",
	"62" => "CFTP",
	"64" => "SAT-EXPAK",
	"65" => "KRYPTOLAN",
	"66" => "RVD",
	"67" => "IPPC",
	"69" => "SAT-MON",
	"70" => "VISA",
	"71" => "IPCV",
	"72" => "CPNX",
	"73" => "CPHB",
	"74" => "WSN",
	"75" => "PVP",
	"76" => "BR-SAT-MON",
	"77" => "SUN-ND",
	"78" => "WB-MON",
	"79" => "WB-EXPAK",
	"80" => "ISO-IP",
	"81" => "VMTP",
	"82" => "SECURE-VMTP",
	"83" => "VINES",
	"84" => "TTP",
	"85" => "NSFNET-IGP",
	"86" => "DGP",
	"87" => "TCF",
	"88" => "EIGRP",
	"89" => "OSPFIGP",
	"90" => "Sprite-RPC",
	"91" => "LARP",
	"92" => "MTP",
	"93" => "AX.25",
	"94" => "IPIP",
	"95" => "MICP",
	"96" => "SCC-SP",
	"97" => "ETHERIP",
	"98" => "ENCAP",
	"100" => "GMTP",
	"101" => "IFMP",
	"102" => "PNNI",
	"103" => "PIM",
	"104" => "ARIS",
	"105" => "SCPS",
	"106" => "QNX",
	"107" => "A/N",
	"108" => "IPComp",
	"109" => "SNP",
	"110" => "Compaq-Peer",
	"111" => "IPX-in-IP",
	"112" => "VRRP",
	"113" => "PGM",
	"115" => "L2TP",
	"116" => "DDX",
	"117" => "IATP",
	"118" => "STP",
	"119" => "SRP",
	"120" => "UTI",
	"121" => "SMP",
	"122" => "SM",
	"123" => "PTP",
	"124" => "ISIS-over-IPv4",
	"125" => "FIRE",
	"126" => "CRTP",
	"127" => "CRUDP",
	"128" => "SSCOPMCE",
	"129" => "IPLT",
	"130" => "SPS",
	"131" => "PIPE",
	"132" => "SCTP",
	"133" => "FC",
	"134" => "RSVP-E2E-IGNORE",
    "135" => "Mobility Header",
	"136" => "UDPLite",
	"137" => "MPLS-in-IP",
	"138" => "MANET",
    "139" => "HIP",
    "140" => "Shim6",
);

$v_protos_ethernet_ar = array(
    "512" => "XEROX PUP",
    "513" => "PUP Addr Trans",
    "1024" => "Nixdorf",
    "1536" => "XEROX NS IDP",
    "1632" => "DLOG",
    "1633" => "DLOG",
    "2048" => "Internet IP (IPv4)",
    "2049" => "X.75 Internet",
    "2050" => "NBS Internet",
    "2051" => "ECMA Internet",
    "2052" => "Chaosnet",
    "2053" => "X.25 Level 3",
    "2054" => "ARP",
    "2055" => "XNS Compatability",
    "2056" => "Frame Relay ARP",
    "2076" => "Symbolics Private",
    "2184" => "Xyplex",
    "2185" => "Xyplex",
    "2186" => "Xyplex",
    "2304" => "Ungermann-Bass net debugr",
    "2560" => "Xerox IEEE802.3 PUP",
    "2561" => "PUP Addr Trans",
    "2989" => "Banyan VINES",
    "2990" => "VINES Loopback",
    "2991" => "VINES Echo",
    "4096" => "Berkeley Trailer nego",
    "4097" => "Berkeley Trailer encap/IP",
    "4098" => "Berkeley Trailer encap/IP",
    "4099" => "Berkeley Trailer encap/IP",
    "4100" => "Berkeley Trailer encap/IP",
    "4101" => "Berkeley Trailer encap/IP",
    "4102" => "Berkeley Trailer encap/IP",
    "4103" => "Berkeley Trailer encap/IP",
    "4104" => "Berkeley Trailer encap/IP",
    "4105" => "Berkeley Trailer encap/IP",
    "4106" => "Berkeley Trailer encap/IP",
    "4107" => "Berkeley Trailer encap/IP",
    "4108" => "Berkeley Trailer encap/IP",
    "4109" => "Berkeley Trailer encap/IP",
    "4110" => "Berkeley Trailer encap/IP",
    "4111" => "Berkeley Trailer encap/IP",
    "5632" => "Valid Systems",
    "16962" => "PCS Basic Block Protocol",
    "21000" => "BBN Simnet",
    "24576" => "DEC Unassigned (Exp.)",
    "24577" => "DEC MOP Dump/Load",
    "24578" => "DEC MOP Remote Console",
    "24579" => "DEC DECNET Phase IV Route",
    "24580" => "DEC LAT",
    "24581" => "DEC Diagnostic Protocol",
    "24582" => "DEC Customer Protocol",
    "24583" => "DEC LAVC, SCA",
    "24584" => "DEC Unassigned",
    "24585" => "DEC Unassigned",
    "24592" => "3Com Corporation",
    "24593" => "3Com Corporation",
    "24594" => "3Com Corporation",
    "24595" => "3Com Corporation",
    "24596" => "3Com Corporation",
    "25944" => "Trans Ether Bridging",
    "25945" => "Raw Frame Relay",
    "28672" => "Ungermann-Bass download",
    "28674" => "Ungermann-Bass dia/loop",
    "28704" => "LRT",
    "28705" => "LRT",
    "28706" => "LRT",
    "28707" => "LRT",
    "28708" => "LRT",
    "28709" => "LRT",
    "28710" => "LRT",
    "28711" => "LRT",
    "28712" => "LRT",
    "28713" => "LRT",
    "28720" => "Proteon",
    "28724" => "Cabletron",
    "32771" => "Cronus VLN",
    "32772" => "Cronus Direct",
    "32773" => "HP Probe",
    "32774" => "Nestar",
    "32776" => "AT&T",
    "32784" => "Excelan",
    "32787" => "SGI diagnostics",
    "32788" => "SGI network games",
    "32789" => "SGI reserved",
    "32790" => "SGI bounce server",
    "32793" => "Apollo Domain",
    "32814" => "Tymshare",
    "32815" => "Tigan, Inc.",
    "32821" => "Reverse ARP",
    "32822" => "Aeonic Systems",
    "32824" => "DEC LANBridge",
    "32825" => "DEC Unassigned",
    "32826" => "DEC Unassigned",
    "32827" => "DEC Unassigned",
    "32828" => "DEC Unassigned",
    "32829" => "DEC Ethernet Encryption",
    "32830" => "DEC Unassigned",
    "32831" => "DEC LAN Traffic Monitor",
    "32832" => "DEC Unassigned",
    "32833" => "DEC Unassigned",
    "32834" => "DEC Unassigned",
    "32836" => "Planning Research Corp.",
    "32838" => "AT&T",
    "32839" => "AT&T",
    "32841" => "ExperData",
    "32859" => "Stanford V Kernel exp.",
    "32860" => "Stanford V Kernel prod.",
    "32861" => "Evans & Sutherland",
    "32864" => "Little Machines",
    "32866" => "Counterpoint Computers",
    "32869" => "Univ. of Mass. @ Amherst",
    "32870" => "Univ. of Mass. @ Amherst",
    "32871" => "Veeco Integrated Auto.",
    "32872" => "General Dynamics",
    "32873" => "AT&T",
    "32874" => "Autophon",
    "32876" => "ComDesign",
    "32877" => "Computgraphic Corp.",
    "32878" => "Landmark Graphics Corp.",
    "32879" => "Landmark Graphics Corp.",
    "32880" => "Landmark Graphics Corp.",
    "32881" => "Landmark Graphics Corp.",
    "32882" => "Landmark Graphics Corp.",
    "32883" => "Landmark Graphics Corp.",
    "32884" => "Landmark Graphics Corp.",
    "32885" => "Landmark Graphics Corp.",
    "32886" => "Landmark Graphics Corp.",
    "32887" => "Landmark Graphics Corp.",
    "32890" => "Matra",
    "32891" => "Dansk Data Elektronik",
    "32892" => "Merit Internodal",
    "32893" => "Vitalink Communications",
    "32894" => "Vitalink Communications",
    "32895" => "Vitalink Communications",
    "32896" => "Vitalink TransLAN III",
    "32897" => "Counterpoint Computers",
    "32898" => "Counterpoint Computers",
    "32899" => "Counterpoint Computers",
    "32923" => "Appletalk",
    "32924" => "Datability",
    "32925" => "Datability",
    "32926" => "Datability",
    "32927" => "Spider Systems Ltd.",
    "32931" => "Nixdorf Computers",
    "32932" => "Siemens Gammasonics Inc.",
    "32933" => "Siemens Gammasonics Inc.",
    "32934" => "Siemens Gammasonics Inc.",
    "32935" => "Siemens Gammasonics Inc.",
    "32936" => "Siemens Gammasonics Inc.",
    "32937" => "Siemens Gammasonics Inc.",
    "32938" => "Siemens Gammasonics Inc.",
    "32939" => "Siemens Gammasonics Inc.",
    "32940" => "Siemens Gammasonics Inc.",
    "32941" => "Siemens Gammasonics Inc.",
    "32942" => "Siemens Gammasonics Inc.",
    "32943" => "Siemens Gammasonics Inc.",
    "32944" => "Siemens Gammasonics Inc.",
    "32945" => "Siemens Gammasonics Inc.",
    "32946" => "Siemens Gammasonics Inc.",
    "32947" => "Siemens Gammasonics Inc.",
    "32960" => "DCA Data Exchange Cluster",
    "32961" => "DCA Data Exchange Cluster",
    "32962" => "DCA Data Exchange Cluster",
    "32963" => "DCA Data Exchange Cluster",
    "32964" => "Banyan Systems",
    "32965" => "Banyan Systems",
    "32966" => "Pacer Software",
    "32967" => "Applitek Corporation",
    "32968" => "Intergraph Corporation",
    "32969" => "Intergraph Corporation",
    "32970" => "Intergraph Corporation",
    "32971" => "Intergraph Corporation",
    "32972" => "Intergraph Corporation",
    "32973" => "Harris Corporation",
    "32974" => "Harris Corporation",
    "32975" => "Taylor Instrument",
    "32976" => "Taylor Instrument",
    "32977" => "Taylor Instrument",
    "32978" => "Taylor Instrument",
    "32979" => "Rosemount Corporation",
    "32980" => "Rosemount Corporation",
    "32981" => "IBM SNA Service on Ether",
    "32989" => "Varian Associates",
    "32990" => "Integrated Solutions TRFS",
    "32991" => "Integrated Solutions TRFS",
    "32992" => "Allen-Bradley",
    "32993" => "Allen-Bradley",
    "32994" => "Allen-Bradley",
    "32995" => "Allen-Bradley",
    "32996" => "Datability",
    "32997" => "Datability",
    "32998" => "Datability",
    "32999" => "Datability",
    "33000" => "Datability",
    "33001" => "Datability",
    "33002" => "Datability",
    "33003" => "Datability",
    "33004" => "Datability",
    "33005" => "Datability",
    "33006" => "Datability",
    "33007" => "Datability",
    "33008" => "Datability",
    "33010" => "Retix",
    "33011" => "AppleTalk AARP (Kinetics)",
    "33012" => "Kinetics",
    "33013" => "Kinetics",
    "33015" => "Apollo Computer",
    "33023" => "Wellfleet Communications",
    "33024" => "Wellfleet Communications",
    "33025" => "Wellfleet Communications",
    "33026" => "Wellfleet Communications",
    "33027" => "Wellfleet Communications",
    "33031" => "Symbolics Private",
    "33032" => "Symbolics Private",
    "33033" => "Symbolics Private",
    "33072" => "Hayes Microcomputers",
    "33073" => "VG Laboratory Systems",
    "33074" => "Bridge Communications",
    "33075" => "Bridge Communications",
    "33076" => "Bridge Communications",
    "33077" => "Bridge Communications",
    "33078" => "Bridge Communications",
    "33079" => "Novell, Inc.",
    "33080" => "Novell, Inc.",
    "33081" => "KTI",
    "33082" => "KTI",
    "33083" => "KTI",
    "33084" => "KTI",
    "33085" => "KTI",
    "33096" => "Logicraft",
    "33097" => "Network Computing Devices",
    "33098" => "Alpha Micro",
    "33100" => "SNMP",
    "33101" => "BIIN",
    "33102" => "BIIN",
    "33103" => "Technically Elite Concept",
    "33104" => "Rational Corp",
    "33105" => "Qualcomm",
    "33106" => "Qualcomm",
    "33107" => "Qualcomm",
    "33116" => "Computer Protocol Pty Ltd",
    "33117" => "Computer Protocol Pty Ltd",
    "33118" => "Computer Protocol Pty Ltd",
    "33124" => "Charles River Data System",
    "33125" => "Charles River Data System",
    "33126" => "Charles River Data System",
    "33149" => "Protocol Engines",
    "33150" => "Protocol Engines",
    "33151" => "Protocol Engines",
    "33152" => "Protocol Engines",
    "33153" => "Protocol Engines",
    "33154" => "Protocol Engines",
    "33155" => "Protocol Engines",
    "33156" => "Protocol Engines",
    "33157" => "Protocol Engines",
    "33158" => "Protocol Engines",
    "33159" => "Protocol Engines",
    "33160" => "Protocol Engines",
    "33161" => "Protocol Engines",
    "33162" => "Protocol Engines",
    "33163" => "Protocol Engines",
    "33164" => "Protocol Engines",
    "33165" => "Motorola Computer",
    "33178" => "Qualcomm",
    "33179" => "Qualcomm",
    "33180" => "Qualcomm",
    "33181" => "Qualcomm",
    "33182" => "Qualcomm",
    "33183" => "Qualcomm",
    "33184" => "Qualcomm",
    "33185" => "Qualcomm",
    "33186" => "Qualcomm",
    "33187" => "Qualcomm",
    "33188" => "ARAI Bunkichi",
    "33189" => "RAD Network Devices",
    "33190" => "RAD Network Devices",
    "33191" => "RAD Network Devices",
    "33192" => "RAD Network Devices",
    "33193" => "RAD Network Devices",
    "33194" => "RAD Network Devices",
    "33195" => "RAD Network Devices",
    "33196" => "RAD Network Devices",
    "33197" => "RAD Network Devices",
    "33198" => "RAD Network Devices",
    "33207" => "Xyplex",
    "33208" => "Xyplex",
    "33209" => "Xyplex",
    "33228" => "Apricot Computers",
    "33229" => "Apricot Computers",
    "33230" => "Apricot Computers",
    "33231" => "Apricot Computers",
    "33232" => "Apricot Computers",
    "33233" => "Apricot Computers",
    "33234" => "Apricot Computers",
    "33235" => "Apricot Computers",
    "33236" => "Apricot Computers",
    "33237" => "Apricot Computers",
    "33238" => "Artisoft",
    "33239" => "Artisoft",
    "33240" => "Artisoft",
    "33241" => "Artisoft",
    "33242" => "Artisoft",
    "33243" => "Artisoft",
    "33244" => "Artisoft",
    "33245" => "Artisoft",
    "33254" => "Polygon",
    "33255" => "Polygon",
    "33256" => "Polygon",
    "33257" => "Polygon",
    "33258" => "Polygon",
    "33259" => "Polygon",
    "33260" => "Polygon",
    "33261" => "Polygon",
    "33262" => "Polygon",
    "33263" => "Polygon",
    "33264" => "Comsat Labs",
    "33265" => "Comsat Labs",
    "33266" => "Comsat Labs",
    "33267" => "SAIC",
    "33268" => "SAIC",
    "33269" => "SAIC",
    "33270" => "VG Analytical",
    "33271" => "VG Analytical",
    "33272" => "VG Analytical",
    "33283" => "Quantum Software",
    "33284" => "Quantum Software",
    "33285" => "Quantum Software",
    "33313" => "Ascom Banking Systems",
    "33314" => "Ascom Banking Systems",
    "33342" => "Advanced Encryption Syste",
    "33343" => "Advanced Encryption Syste",
    "33344" => "Advanced Encryption Syste",
    "33407" => "Athena Programming",
    "33408" => "Athena Programming",
    "33409" => "Athena Programming",
    "33410" => "Athena Programming",
    "33379" => "Charles River Data System",
    "33380" => "Charles River Data System",
    "33381" => "Charles River Data System",
    "33382" => "Charles River Data System",
    "33383" => "Charles River Data System",
    "33384" => "Charles River Data System",
    "33385" => "Charles River Data System",
    "33386" => "Charles River Data System",
    "33434" => "Inst Ind Info Tech",
    "33435" => "Inst Ind Info Tech",
    "33436" => "Taurus Controls",
    "33437" => "Taurus Controls",
    "33438" => "Taurus Controls",
    "33439" => "Taurus Controls",
    "33440" => "Taurus Controls",
    "33441" => "Taurus Controls",
    "33442" => "Taurus Controls",
    "33443" => "Taurus Controls",
    "33444" => "Taurus Controls",
    "33445" => "Taurus Controls",
    "33446" => "Taurus Controls",
    "33447" => "Taurus Controls",
    "33448" => "Taurus Controls",
    "33449" => "Taurus Controls",
    "33450" => "Taurus Controls",
    "33451" => "Taurus Controls",
    "34452" => "Idea Courier",
    "34453" => "Idea Courier",
    "34454" => "Idea Courier",
    "34455" => "Idea Courier",
    "34456" => "Idea Courier",
    "34457" => "Idea Courier",
    "34458" => "Idea Courier",
    "34459" => "Idea Courier",
    "34460" => "Idea Courier",
    "34461" => "Idea Courier",
    "34462" => "Computer Network Tech",
    "34463" => "Computer Network Tech",
    "34464" => "Computer Network Tech",
    "34465" => "Computer Network Tech",
    "34467" => "Gateway Communications",
    "34468" => "Gateway Communications",
    "34469" => "Gateway Communications",
    "34470" => "Gateway Communications",
    "34471" => "Gateway Communications",
    "34472" => "Gateway Communications",
    "34473" => "Gateway Communications",
    "34474" => "Gateway Communications",
    "34475" => "Gateway Communications",
    "34476" => "Gateway Communications",
    "34523" => "SECTRA",
    "34526" => "Delta Controls",
    "34527" => "ATOMIC",
    "34528" => "Landis & Gyr Powers",
    "34529" => "Landis & Gyr Powers",
    "34530" => "Landis & Gyr Powers",
    "34531" => "Landis & Gyr Powers",
    "34532" => "Landis & Gyr Powers",
    "34533" => "Landis & Gyr Powers",
    "34534" => "Landis & Gyr Powers",
    "34535" => "Landis & Gyr Powers",
    "34536" => "Landis & Gyr Powers",
    "34537" => "Landis & Gyr Powers",
    "34538" => "Landis & Gyr Powers",
    "34539" => "Landis & Gyr Powers",
    "34540" => "Landis & Gyr Powers",
    "34541" => "Landis & Gyr Powers",
    "34542" => "Landis & Gyr Powers",
    "34543" => "Landis & Gyr Powers",
    "34560" => "Motorola",
    "34561" => "Motorola",
    "34562" => "Motorola",
    "34563" => "Motorola",
    "34564" => "Motorola",
    "34565" => "Motorola",
    "34566" => "Motorola",
    "34567" => "Motorola",
    "34568" => "Motorola",
    "34569" => "Motorola",
    "34570" => "Motorola",
    "34571" => "Motorola",
    "34572" => "Motorola",
    "34573" => "Motorola",
    "34574" => "Motorola",
    "34575" => "Motorola",
    "34576" => "Motorola",
    "34667" => "TCP/IP Compression",
    "34668" => "IP Autonomous Systems",
    "34669" => "Secure Data",
    "35478" => "Invisible Software",
    "35479" => "Invisible Software",
    "36864" => "Loopback",
    "36865" => "3Com(Bridge) XNS Sys Mgmt",
    "36866" => "3Com(Bridge) TCP-IP Sys",
    "36867" => "3Com(Bridge) loop detect",
    "65280" => "BBN VITAL-LanBridge cache",
    "65280" => "ISC Bunker Ramo",
    "65281" => "ISC Bunker Ramo",
    "65282" => "ISC Bunker Ramo",
    "65283" => "ISC Bunker Ramo",
    "65284" => "ISC Bunker Ramo",
    "65285" => "ISC Bunker Ramo",
    "65286" => "ISC Bunker Ramo",
    "65287" => "ISC Bunker Ramo",
    "65288" => "ISC Bunker Ramo",
    "65289" => "ISC Bunker Ramo",
    "65290" => "ISC Bunker Ramo",
    "65291" => "ISC Bunker Ramo",
    "65292" => "ISC Bunker Ramo",
    "65293" => "ISC Bunker Ramo",
    "65294" => "ISC Bunker Ramo",
    "65295" => "ISC Bunker Ramo",
    "65535" => "Reserved",
);

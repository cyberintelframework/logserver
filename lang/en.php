<?php

####################################
# SURFnet IDS                      #
# Version 2.10.03                  #
# 06-11-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################
# Contributors:                    #
# Peter Arts                       #
####################################

#############################################
# Changelog:
# 2.10.03 Added group pages
# 2.10.02 Added missing timestamp text for search page
# 2.10.01 Initial release
#############################################

##########################
# Global (Uppercase first letter)
##########################
$l['g_add'] 			= "Add";
$l['g_update'] 			= "Update";
$l['g_delete']			= "Delete";
$l['g_edit']			= "Edit";
$l['g_insert']			= "Insert";
$l['g_sensor'] 			= "Sensor";
$l['g_select_sensor']		= "Select a sensor";
$l['g_type']			= "Type";
$l['g_mac']			= "MAC address";
$l['g_ip']			= "IP address";
$l['g_action']			= "Action";
$l['g_actions']			= "Actions";
$l['g_legend']			= "Legend";
$l['g_attacks']			= "Attacks";
$l['g_exploits']		= "Exploits";
$l['g_mal']			= "Malicious attacks";
$l['g_pos']			= "Possible Malicious Attacks";
$l['g_stats']			= "Statistics";
$l['g_nofound']			= "No records found!";
$l['g_info']			= "Info";
$l['g_detconn']			= "Detected connections";
$l['g_submit']			= "Submit";
$l['g_all']			= "All";
$l['g_id']			= "ID";
$l['g_domain']			= "Domain";
$l['g_status']			= "Status";
$l['g_modify']			= "Modify";
$l['g_total']			= "Total";

##########################
# Global (Lowercase first letter)
##########################
$l['g_hour_l']			= "hour";
$l['g_remove_l']		= "remove";
$l['g_approve_l']		= "approve";
$l['g_disapprove_l']		= "disapprove";
$l['g_deny_l']			= "deny";
$l['g_delete_l']		= "delete";
$l['g_edit_l']			= "edit";
$l['g_exploits_l']		= "exploits";
$l['g_ports_l']			= "ports";
$l['g_files_l']			= "files";

##########################
# argosadmin.php
##########################
$l['aa_allorg']			= "All domains";
$l['aa_argosimages'] 		= "Argos Images";
$l['aa_name'] 			= "Name";
$l['aa_serverip'] 		= "Server IP";
$l['aa_imagename'] 		= "Image name on server";
$l['aa_os'] 			= "OS";
$l['aa_oslang']			= "OS language";
$l['aa_mac'] 			= "MAC address";
$l['aa_org'] 			= "Domain";

##########################
# argosconfig.php
##########################
$l['ac_deviceip']		= "Device IP";
$l['ac_imagename'] 		= "Image name";
$l['ac_template'] 		= "Template";
$l['ac_timespan'] 		= "Timespan";
$l['ac_last24'] 		= "Last 24 hours";
$l['ac_lastweek'] 		= "Last week";
$l['ac_lastmonth']		= "Last month";
$l['ac_lastyear']		= "Last year";
$l['ac_notime']			= "No timespan";
$l['ac_confirm'] 		= "This will also delete your range redirections.\\nAre you sure?";
$l['ac_redirectto']		= "Redirect to ranges";
$l['ac_range_or_ip']		= "Range or IP";

##########################
# arp_cache.php
##########################
$l['ah_confirm']		= "Are you sure you want to clear the ARP cache?";
$l['ah_clear_arp']		= "Clear ARP cache";
$l['ah_arp_cache']		= "ARP Cache";
$l['ah_nic_man'] 		= "NIC manufacturer";
$l['ah_last_changed']		= "Last changed";
$l['ah_status']			= "Status";
$l['ah_ok']			= "OK";
$l['ah_poisoned']		= "Poisoned";
$l['ah_add_to_static']		= "Add to static list";

##########################
# arp_static.php
##########################
$l['as_actions_for']		= "Actions for ";
$l['as_disabled']		= "Disabled";
$l['as_enabled']		= "Enabled";
$l['as_arp_mod']		= "ARP module configuration";
$l['as_delconfirm']		= "Are you sure you want to delete this entry?";
$l['as_del_router']		= "Del router";
$l['as_add_router']		= "Add router";
$l['as_del_dhcp']		= "Del DHCP";
$l['as_add_dhcp']		= "Add DHCP";

##########################
# binaryhist.php
##########################
$l['bh_binary_info']		= "Binary info";
$l['bh_binary']			= "Binary";
$l['bh_download']		= "Download";
$l['bh_size']			= "Size";
$l['bh_last_seen']		= "Last seen";
$l['bh_first_seen']		= "First seen";
$l['bh_norman']			= "Norman result";
$l['bh_cws']			= "CWSandbox result";
$l['bh_binaryhist']		= "Binary history";
$l['bh_filenames']		= "Filenames used";
$l['bh_full']			= "Show full list";
$l['bh_top10']			= "Show top 10";
$l['bh_last_scanned']		= "Last scanned";

##########################
# detectedproto.php
##########################
$l['dp_confirm_del']		= "Are you sure you want to clear the Detected Protocols?";
$l['dp_clear_det_prot']		= "Clear detected protocols";
$l['dp_detected']		= "Detected protocols";
$l['dp_parent']			= "Parent protocol";
$l['dp_type_number']		= "Type number";

##########################
# exploits.php
##########################

##########################
# groupadmin.php
##########################
$l['ga_confirmdel']		= "Are you sure you want to delete this group?";
$l['ga_group']			= "Group";
$l['ga_groups']			= "Groups";
$l['ga_name']			= "Name";
$l['ga_type']			= "Type";
$l['ga_detail']			= "Detail";
$l['ga_owner']			= "Owner";
$l['ga_status']			= "Status";
$l['ga_pending']		= "Pending";
$l['ga_active']			= "Active";

##########################
# groupedit.php
##########################
$l['ge_edit']			= "Edit Group";
$l['ge_members']		= "Group members";

##########################
# grouping.php
##########################
$l['gr_select']			= "Select group";

##########################
# googlemap.php
##########################
$l['gm_process']		= "Your request is being processed...";
$l['gm_patient']		= "Please be patient";
$l['gm_gmap']			= "Google Map";

##########################
# index.php
##########################
$l['in_attackers']		= "Attackers";
$l['in_lastseen']		= "Last seen";
$l['in_totalhits']		= "Total hits";
$l['in_today']			= "Today";
$l['in_7']			= "7 days ago";
$l['in_ports']			= "Ports";
$l['in_desc']			= "Description";
$l['in_destports']		= "Destination ports";

##########################
# logcheck.php
##########################
$l['lc_cross']			= "Cross domain";
$l['lc_noranges']		= "No ranges present for this domain.";
$l['lc_range']			= "Range";
$l['lc_uniqsource']		= "Unique source addresses";

##########################
# logdetail.php
##########################
$l['ld_aid_details']		= "Details of attack ID";
$l['ld_popout']			= "Close this popup";

##########################
# login.php
##########################
$l['lo_error']			= "Username or password was incorrect!";
$l['lo_username']		= "Username";
$l['lo_login']			= "Login";
$l['lo_pass']			= "Password";

##########################
# logsearch.php
##########################
$l['ls_search']			= "Search";
$l['ls_clear']			= "Clear";
$l['ls_allsensors']		= "All sensors";
$l['ls_process']		= "Your search is being processed...";
$l['ls_crit']			= "Criteria";
$l['ls_clear']			= "clear";
$l['ls_dest']			= "Destination";
$l['ls_all']			= "ALL";
$l['ls_change']			= "change";
$l['ls_destip']			= "Destination IP";
$l['ls_desstmac']		= "Destination MAC";
$l['ls_port']			= "Port";
$l['ls_source']			= "Source";
$l['ls_ipex_on']		= "IP exclusion ON";
$l['ls_ipex_off']		= "IP exclusion OFF";
$l['ls_own']			= "Own ranges";
$l['ls_address']		= "Address";
$l['ls_sourceip']		= "Source IP";
$l['ls_sourcemac']		= "Source MAC";
$l['ls_noranges']		= "No ranges present";
$l['ls_allranges']		= "All ranges";
$l['ls_chars']			= "Characteristics";
$l['ls_sev']			= "Severity";
$l['ls_sevtype']		= "Severity type";
$l['ls_exp']			= "Exploit";
$l['ls_binname']		= "Binary name";
$l['ls_virus']			= "Virus";
$l['ls_filename']		= "Filename";
$l['ls_att_type']		= "Attack type";
$l['ls_wildcard']		= "Wildcard is";
$l['ls_saveas']			= "Save as";
$l['ls_stemp']			= "searchtemplate";
$l['ls_temp_title']		= "Template title";
$l['ls_time_options']		= "Timespan options";
$l['ls_dontsave']		= "Don't save timespan info";
$l['ls_noresults']		= "No matching results found!";
$l['ls_multi']			= "Multi";
$l['ls_pages']			= "pages";
$l['ls_page']			= "page";
$l['ls_results']		= "Results";
$l['ls_additional']		= "Additional info";
$l['ls_noinfo']			= "No Info";
$l['ls_rendered']		= "Page rendered in";
$l['ls_search_crit']		= "Search Criteria";
$l['ls_fo']			= "file-offered";
$l['ls_at']			= "attack-type";
$l['ls_timestamp']		= "Timestamp";

##########################
# maldownloaded.php
##########################
$l['md_title']			= "Malware downloaded";
$l['md_malware']		= "Malware";
$l['md_stats']			= "Stats";
$l['md_notscanned']		= "Not scanned";
$l['md_scanned']		= "scanned";

##########################
# maloffered.php
##########################
$l['mo_offered']		= "Malware offered";
$l['mo_top10']			= "Top 10";

##########################
# menu.php
##########################
$l['me_contact']		= "Contact";
$l['me_logout']			= "Logout";
$l['me_about']			= "About";
$l['me_active']			= "Active sensors";
$l['me_of']			= "of";
$l['me_logged']			= "Logged in as";
$l['me_home']			= "Home";
$l['me_report']			= "Report";
$l['me_rank']			= "Ranking";
$l['me_cross']			= "Cross Domain";
$l['me_google']			= "Google Map";
$l['me_traffic']		= "Traffic";
$l['me_serverinfo']		= "Server Info";
$l['me_detprot']		= "Detected Protocols";
$l['me_graphs']			= "Graphs";
$l['me_reports']		= "My Reports";
$l['me_analyze']		= "Analyze";
$l['me_maloff']			= "Malware Offered";
$l['me_maldown']		= "Malware Downloaded";
$l['me_search']			= "Search";
$l['me_config']			= "Configuration";
$l['me_sensorstatus']		= "Sensor Status";
$l['me_arp']			= "ARP";
$l['me_ipex']			= "IP Exclusions";
$l['me_argos']			= "Argos";
$l['me_argostemp']		= "Argos Templates";
$l['me_configinfo']		= "Config Info";
$l['me_admin']			= "Administration";
$l['me_myaccount']		= "My Account";
$l['me_users']			= "Users";
$l['me_domains']		= "Domains";
$l['me_loading']		= "Loading page...";
$l['me_period']			= "Period";
$l['me_from']			= "From";
$l['me_until']			= "Until";
$l['me_groups']			= "Groups";
$l['me_grouping']		= "Group compare";

##########################
# myaccount.php
##########################
$l['ma_edit']			= "Edit";
$l['ma_confirmp']		= "Confirm password";
$l['ma_domain']			= "Domain";
$l['ma_email']			= "Email address";
$l['ma_signing']		= "Email signing";
$l['ma_enable_gpg']		= "Enable GPG signing";
$l['ma_disable_gpg']		= "Disable GPG signing";
$l['ma_asensor']		= "Access: Sensor";
$l['ma_asearch']		= "Access: Search";
$l['ma_auseradmin']		= "Access: User Admin";
$l['ma_arpac']			= "ARP access";
$l['ma_argosac']		= "ARGOS access";
$l['ma_modules']		= "Modules";

##########################
# myreports.php
##########################
$l['mr_addreport']		= "My Report";
$l['mr_disableall']		= "Disable all reports";
$l['mr_enableall']		= "Enable all reports";
$l['mr_resetall']		= "Reset all timestamps";
$l['mr_reportsof']		= "Reports of";
$l['mr_title']			= "Title";
$l['mr_lastsent']		= "Last sent";
$l['mr_temp']			= "Template";
$l['mr_timeopts']		= "Time options";
$l['mr_active']			= "Active";
$l['mr_inactive']		= "Inactive";
$l['mr_never']			= "never";
$l['mr_result']			= "Search result";
$l['mr_confirmdel']		= "Are you sure you want to delete this report";

##########################
# orgadmin.php
##########################
$l['oa_identifiers']		= "of identifiers";
$l['oa_editdomain']		= "Edit this domain";

##########################
# orgedit.php
##########################
$l['oe_generate']		= "Generate Random Identifier String";
$l['oe_editdomain']		= "Edit domain";
$l['oe_idents']			= "identifiers";
$l['oe_ranges']			= "Ranges";
$l['oe_ident']			= "Identifier";
$l['oe_confirmdel']		= "Are you sure you want to delete this identifier";

##########################
# orgipadmin.php
##########################
$l['oi_excls']			= "Exclusions";
$l['oi_excl']			= "Exclusion";
$l['oi_confirmdel']		= "Are you sure you want to delete this record";

##########################
# plotter.php
##########################
$l['pl_sev']			= "Severity";
$l['pl_attack']			= "Attack";
$l['pl_port']			= "Port";
$l['pl_os']			= "OS";
$l['pl_virus']			= "Virus";
$l['pl_graphs']			= "Graphs";
$l['pl_sensors']		= "Sensors";
$l['pl_int']			= "Interval";
$l['pl_hour']			= "Hour";
$l['pl_day']			= "Day";
$l['pl_week']			= "Week";
$l['pl_show']			= "Show";
$l['pl_dports']			= "Destination ports/ranges";
$l['pl_example']		= "example";
$l['pl_all']			= "all";
$l['pl_ostype']			= "OS type";
$l['pl_plottype']		= "Plot type";
$l['pl_virusinfo']		= "Virus info";
$l['pl_allvirii']		= "All viruses";
$l['pl_top10virii']		= "Top 10 viruses";
$l['pl_scanner']		= "Scanner";
$l['pl_allattacks']		= "All attacks";
$l['pl_graph']			= "Graph";

##########################
# rank.php
##########################
$l['ra_total']			= "Total";
$l['ra_totals']			= "Totals";
$l['ra_totalmal_all']		= "Total malicious attacks of all sensors";
$l['ra_totaldown_all']		= "Total downloaded malware of all sensors";
$l['ra_owndomain']		= "Own domain";
$l['ra_totalmal_org']		= "Total malicious attacks for";
$l['ra_totalmal_perc']		= "of total malicious attacks";
$l['ra_totaldown_org']		= "Total downloaded malware by";
$l['ra_totaldown_perc']		= "of total collected malware";
$l['ra_top']			= "Top";
$l['ra_exploits_all']		= "exploits of all sensors";
$l['ra_exploits_org']		= "exploits of your sensors";
$l['ra_expl']			= "Exploit";
$l['ra_sensors']		= "sensors";
$l['ra_totalexpl']		= "Total exploits";
$l['ra_sensorsof']		= "sensors of";
$l['ra_overallrank']		= "Overall rank";
$l['ra_port']			= "Port";
$l['ra_portdesc']		= "Port Description";
$l['ra_ports_org']		= "ports of your sensors";
$l['ra_ports_all']		= "ports of all sensors";
$l['ra_source_all']		= "source addresses of all sensors";
$l['ra_source_org']		= "source addresses of your sensors";
$l['ra_address']		= "Address";
$l['ra_ipownranges']		= "IP from your own ranges!";
$l['ra_files_all']		= "filenames of all sensors";
$l['ra_files_org']		= "filenames of your sensors";
$l['ra_filename']		= "Filename";
$l['ra_proto_all']		= "download protocols of all sensors";
$l['ra_proto_org']		= "download protocols of your sensors";
$l['ra_proto']			= "Protocol";
$l['ra_os_all']			= "attacker OS of all sensors";
$l['ra_os_org']			= "attacker OS of your sensors";
$l['ra_os']			= "OS";
$l['ra_domains']		= "domains";

##########################
# report_edit.php
##########################
$l['re_mailopts']		= "Mail options";
$l['re_subject']		= "Subject";
$l['re_mailprio']		= "Mail priority";
$l['re_reportpopts']		= "Report options";
$l['re_allsensors']		= "All sensors";
$l['re_reptemp']		= "Report template";
$l['re_reptype']		= "Report type";
$l['re_filter']			= "Filter";
$l['re_exown']			= "Exclude own IP ranges";
$l['re_incown']			= "Include own IP ranges";
$l['re_allsev']			= "All severities";
$l['re_timeopts']		= "Time options";
$l['re_freq']			= "Frequency";
$l['re_time']			= "Time";
$l['re_day']			= "Day";
$l['re_threshopts']		= "Threshold options";
$l['re_op']			= "Operator";
$l['re_thresh_amount']		= "Threshold amount";
$l['re_timespan']		= "Timespan";

##########################
# rssfeed.php
##########################
$l['rf_generator']		= "Generator";
$l['rf_new']			= "New";
$l['rf_detected']		= "detected (attack ID";
$l['rf_noranges']		= "Error: No ranges present for this domain!";

##########################
# sensordetails.php
##########################
$l['sd_purge']			= "Purge all events older than";
$l['sd_name']			= "Name";
$l['sd_sensorname']		= "Sensor name";
$l['sd_label']			= "Sensor label";
$l['sd_clear']			= "Clear";
$l['sd_sensorside']		= "Sensor side";
$l['sd_rip']			= "Remote IP";
$l['sd_lip']			= "Local IP";
$l['sd_serverside']		= "Server side";
$l['sd_device']			= "Device";
$l['sd_devmac']			= "Device MAC";
$l['sd_devip']			= "Device IP";
$l['sd_status']			= "Status";
$l['sd_started']		= "Last started";
$l['sd_stopped']		= "Last stopped";
$l['sd_updated']		= "Last updated";
$l['sd_sensorlog']		= "Sensor log";
$l['sd_uptime']			= "Uptime";
$l['sd_since']			= "Logging since";
$l['sd_total']			= "Total log time";
$l['sd_events']			= "Events";
$l['sd_totalevents']		= "Total number of events";
$l['sd_members']		= "Member of groups";

##########################
# sensorstatus.php
##########################
$l['ss_label']			= "Label";
$l['ss_config']			= "Config method";
$l['ss_none']			= "None";
$l['ss_reboot']			= "Reboot";
$l['ss_sshoff']			= "SSH off";
$l['ss_sshon']			= "SSH on";
$l['ss_stop']			= "Stop";
$l['ss_start']			= "Start";
$l['ss_disable']		= "Disable";
$l['ss_enable']			= "Enable";
$l['ss_ignore']			= "Ignore";
$l['ss_unignore']		= "Unignore";
$l['ss_disable_arp']		= "Disable ARP";
$l['ss_enable_arp']		= "Enable ARP";
$l['ss_legend']			= "Legend";

##########################
# serverconfig.php
##########################
$l['sc_config']			= "Logging server config";
$l['sc_global']			= "Global config options";
$l['sc_webconfig']		= "Webinterface config options";
$l['sc_session']		= "Login and session options";
$l['sc_debug']			= "Debug options";
$l['sc_search']			= "Search page options";
$l['sc_perl']			= "Perl script options";
$l['sc_finger']			= "Fingerprinting options";
$l['sc_geoip']			= "GeoIP options";
$l['sc_rank']			= "Ranking page options";
$l['sc_maillog']		= "Maillogging script options";
$l['sc_sandbox']		= "Sandbox script options";
$l['sc_module']			= "Module options";
$l['sc_virus']			= "Virus scanner info";

##########################
# serverstats.php
##########################
$l['ss_info']			= "Server Info";
$l['ss_daily']			= "Daily Graph (5 minute averages)";
$l['ss_day']			= "Daily";

##########################
# serverstatsview.php
##########################
$l['sv_dg']			= "Daily Graph (5 minute averages)";
$l['sv_wg']			= "Weekly Graph (30 minute averages)";
$l['sv_mg']			= "Monthly Graph (2 hour averages)";
$l['sv_yg']			= "Yearly Graph (12 hour averages)";
$l['sv_daily']			= "Daily";
$l['sv_weekly']			= "Weekly";
$l['sv_monthly']		= "Monthly";
$l['sv_yearly']			= "Yearly";

##########################
# traffic.php
##########################
$l['tr_traffic']		= "Traffic";
$l['tr_allsensors']		= "All sensors";

##########################
# trafficview.php
##########################
$l['tv_header']			= "Traffic analysis for";

##########################
# useradmin.php
##########################
$l['ua_adduser']		= "Add User";
$l['ua_users']			= "Users";
$l['ua_user']			= "User";
$l['ua_lastlogin']		= "Last login";
$l['ua_access']			= "Access";
$l['ua_reports']		= "Reports";
$l['ua_confirmdel']		= "Are you sure you want to delete this user";
$l['ua_er']			= "Edit reports";

##########################
# usernew.php
##########################
$l['un_new']			= "New User";

##########################
# whois.php
##########################
$l['wh_select']			= "Select server";
$l['wh_query']			= "Whois query";
$l['wh_enterip']		= "Enter whois IP";
$l['wh_q']			= "Query";
$l['wh_wquery']			= "WHOIS Query at";
$l['wh_for']			= "for";
$l['wh_connect']		= "Connecting to";
$l['wh_connto']			= "Connection to";
$l['wh_connected']		= "Connected to";
$l['wh_sending']		= "sending request...";
$l['wh_couldnot']		= "coult not be made";
$l['wh_connclosed']		= "Connection closed";

##########################
# mods 
##########################
$l['mod_virusscanners']			= "Virus Scanners";
$l['mod_virusscan']			= "Virus scanner";
$l['mod_version']			= "Version";
?>

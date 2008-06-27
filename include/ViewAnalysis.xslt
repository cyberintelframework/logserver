<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
<xsl:output method="html" doctype-public="-//W3C//DTD HTML 4.01 Transitional//EN" indent="yes" encoding="iso-8859-1"/>

<!-- 

changes:
2007-05-02: update for new XML structure (DLL handling)
2006-12-06: Thorsten Holz : fixed bug in IRC parsing 
2006-07-25: Eric Sites : modified to support new xml results and to cleanup the formatting
2006-05-25: Eric Sites : modified to support new xml results names, plus added a few minor changes to the formating
2006-05-07: initial xsl file

description: template for reports by CWSandbox

-->

<xsl:template match="analysis">

<html>
<head>
		
<style type="text/css">

.sectitle{
font-family:Tahoma; 
font-size:11px;
color:#FF0000;
text-align:left;
font-weight:bold;
}


.mainpoints{
background-color:#ECECFF;
color:#000000;
text-align:left;
font-weight:bold;
border:1px solid #000000;
}

.notes{
color:#000000;
text-align:left;
font-weight:normal;
font-family:Tahoma; 
font-size:12px;
}

table.details {
	border-width: 1px;
	border-spacing: 1px;
	padding: 1px;
	border-style: solid;
	border-color: gray;
	border-collapse: collapse;
	background-color: white;
	width:100%;
	font-family:Tahoma; 
	font-size:12px;
	width: 640;
}
table.details th {
	border-width: 1px;
	padding: 1px;
	border-style: solid;
	border-color: lightgray;
	background-color: white;
	text-align:left;
	font-size:12px;
	color:gray;
	width:160;
	font-weight:bold;
}
table.details td {
	border-width: 1px;
	padding: 1px;
	border-style: solid;
	border-color: lightgray;
	background-color: white;
	text-align:left;
	font-size:12px;
}

</style>


</head>

<body>

<p class="sectitle">Analysis Summary:</p>
<table class="details" width="640">
   <tr><th>Analysis Date</th> <td><xsl:value-of select="@time"/></td></tr>
   <tr><th>Sandbox Version</th> <td><xsl:value-of select="@cwsversion"/></td></tr>
   <tr><th>Filename</th><td><xsl:value-of select="@file"/></td></tr>
</table>

<p class="sectitle">Technical Details:</p>

<xsl:apply-templates select="processes/process"/>


<p style="font-family:Tahoma; font-size:10px;">Report generated at <xsl:value-of select="@time"/> with CWSandbox Version <xsl:value-of select="@cwsversion"/><br />
This analysis was created by the CWSandbox Copyright © 2006 Carsten Willems<br />
Copyright © 1996-2006 Sunbelt Software. All rights reserved.
</p>

</body>

</html>

</xsl:template>

<xsl:template match="process">

<xsl:if test="not(contains(string(@parentindex), '0'))">
<span class="notes">The following process was started by process: <xsl:value-of select="@parentindex"/></span><br />
</xsl:if>

<table class="details" width="640">
   <tr><th>Analysis Number</th> <td><xsl:value-of select="position()"/></td></tr>
   <tr><th>Parent ID</th><td><xsl:value-of select="@parentindex"/></td></tr>
   <tr><th>Process ID</th><td><xsl:value-of select="@pid"/></td></tr>
   <tr><th>Filename</th> <td><xsl:value-of select="@filename"/></td></tr>
   <tr><th>Filesize</th><td><xsl:value-of select="@filesize"/> bytes</td></tr>
   <tr><th>MD5</th><td><xsl:value-of select="@md5"/></td></tr>
   <tr><th>Start Reason</th><td><xsl:value-of select="@startreason"/></td></tr>
   <tr><th>Termination Reason</th><td><xsl:value-of select="@terminationreason"/></td></tr>
   <tr><th>Start Time</th><td><xsl:value-of select="@starttime"/></td></tr>
   <tr><th>Stop Time</th><td><xsl:value-of select="@terminationtime"/></td></tr>
	
	<xsl:apply-templates />

   </table>
   <br />
</xsl:template>


<xsl:template match="virusscan_section">
		<xsl:variable name="number" select="count(../preceding-sibling::*)+1"/>
		<tr>
		<th>Detection</th>
		<td>
		<xsl:for-each select="scanner"> 
			<xsl:for-each select="classification">
				<xsl:value-of select="."/>
			</xsl:for-each>
			(<xsl:value-of select="@name"/>)<br />
		</xsl:for-each>
		</td></tr>
</xsl:template>


<xsl:template match="system_section">
		<xsl:variable name="number" select="count(../preceding-sibling::*)+1"/>
		<tr>
		<th>System</th>
		<td>
		<xsl:for-each select="exit_windows">
			Exit Windows - Exit Flags (<xsl:value-of select="@exitflags"/>)<br />
		</xsl:for-each>
		<xsl:for-each select="sleep">
			Sleep - Milliseconds (<xsl:value-of select="@milliseconds"/>)<br />
		</xsl:for-each>
		</td>
		</tr>
	<xsl:apply-templates />
</xsl:template>

<xsl:template match="system_info_section">
		<xsl:variable name="number" select="count(../preceding-sibling::*)+1"/>
		<tr>
		<th>System Info</th>
		<td>
		<xsl:for-each select="get_system_directory">
			Get System Directory<br />
		</xsl:for-each>
		<xsl:for-each select="get_windows_directory">
			Get Windows Directory<br />
		</xsl:for-each>
		<xsl:for-each select="get_computer_name">
			Get Computer Name<br />
		</xsl:for-each>
		<xsl:for-each select="get_environment_strings">
			Get Environment Strings<br />
		</xsl:for-each>
		<xsl:for-each select="get_system_time">
			Get System Time<br />
		</xsl:for-each>
		<xsl:for-each select="enum_handles">
			Enum Handles<br />
		</xsl:for-each>
		</td>
		</tr>
	<xsl:apply-templates />
</xsl:template>

<xsl:template match="window_section">
		<xsl:variable name="number" select="count(../preceding-sibling::*)+1"/>
		<tr>
		<th>Window</th>
		<td>
		<xsl:for-each select="find_window">
			Find Window - Class Name (<xsl:value-of select="@classname"/>) Window Name (<xsl:value-of select="@windowname"/>)<br />
		</xsl:for-each>
		<xsl:for-each select="enum_window">
			Enum Windows<br />
		</xsl:for-each>
		<xsl:for-each select="destroy_window">
			Destroy Window - Class Name (<xsl:value-of select="@classname"/>) Window Name (<xsl:value-of select="@windowname"/>)<br />
		</xsl:for-each>
		</td>
		</tr>
	<xsl:apply-templates />
</xsl:template>

<xsl:template match="dll_handling_section">
		<xsl:variable name="number" select="count(../preceding-sibling::*)+1"/>
		<tr>
		<th>DLL-Handling</th>
		<td>
		
		<table id="details" width="100%">
						
		<xsl:if test="count(load_dll) > 0">
		<tr><th>Loaded DLLs</th></tr>
		<tr><td>
		<xsl:for-each select="load_dll">
			<xsl:value-of select="@dll"/><xsl:value-of select="@filename"/><br />
		</xsl:for-each>
		</td></tr>
		</xsl:if>
		
		<xsl:if test="count(load_image) > 0">
		<tr><th>Loaded Image</th></tr>
		<tr><td>
		<xsl:for-each select="load_image">
			<xsl:value-of select="@filename"/><br />
		</xsl:for-each>
		</td></tr>
		</xsl:if>
		
		<xsl:if test="count(get_proc_address) > 0">
		<tr><th>Get Proc Address</th></tr>
		<tr><td>
		<xsl:for-each select="get_proc_address">
			<xsl:value-of select="@dll"/> Method: <xsl:value-of select="@method"/> Successful: <xsl:value-of select="@successful"/><br />
		</xsl:for-each>
		</td></tr>
		</xsl:if>
		
		</table>
		
		
		</td></tr>
		
		<xsl:apply-templates />
</xsl:template>

<xsl:template match="filesystem_section">
		<xsl:variable name="number" select="count(../preceding-sibling::*)+1"/>
		<tr>
		<th>Filesystem</th>
		<td>
		
		<table id="details" width="100%">
				
		<xsl:if test="count(copy_file|move_file|create_file|create_open_file|create_namedpipe) > 0">
		<tr><th>New Files</th></tr>
		<tr><td>
			<xsl:for-each select="copy_file|move_file|create_file|create_open_file|create_namedpipe">
				<xsl:if test="name() = 'copy_file'">
					<xsl:value-of select="@dstfile"/><br />
				</xsl:if> 

				<xsl:if test="name() = 'move_file'">
					<xsl:value-of select="@dstfile"/><br />
				</xsl:if>

				<xsl:if test="name() = 'create_file'">
					<xsl:value-of select="@srcfile"/><br />
				</xsl:if>

				<xsl:if test="name() = 'create_open_file'">
					<xsl:value-of select="@srcfile"/><br />
				</xsl:if>

				<xsl:if test="name() = 'create_namedpipe'">
					<xsl:value-of select="@srcfile"/><br />
				</xsl:if>
			</xsl:for-each>
		</td></tr>		
		</xsl:if>
		
		<xsl:if test="count(open_file) > 0">
		<tr><th>Opened Files</th></tr>
		<tr><td>
		<xsl:for-each select="open_file">
			<xsl:if test="name() = 'open_file'">
			<xsl:value-of select="@srcfile"/><br />
			</xsl:if>
		</xsl:for-each>
		</td></tr>
		</xsl:if>
		
		<xsl:if test="count(delete_file) > 0">
		<tr><th>Deleted Files</th></tr>
		<tr><td>
		<xsl:for-each select="delete_file">			
			<xsl:value-of select="@srcfile"/><br />
		</xsl:for-each>
		</td></tr>
		</xsl:if>
		
		<tr>
		<th>Chronological order</th>
		</tr>
		<tr><td>
		
		
		<xsl:for-each select="copy_file|find_file|move_file|create_file|delete_file|open_file|create_open_file|create_namedpipe|set_file_attributes|get_file_attributes|set_file_time">
			<xsl:if test="name() = 'copy_file'">
				Copy File: <xsl:value-of select="@srcfile"/> to <xsl:value-of select="@dstfile"/><br />
			</xsl:if> 
			
			<xsl:if test="name() = 'find_file'">
				Find File: <xsl:value-of select="@srcfile"/><br />
			</xsl:if>
			
			<xsl:if test="name() = 'move_file'">
				Move File: <xsl:value-of select="@srcfile"/> to <xsl:value-of select="@dstfile"/><br />
			</xsl:if>
			
			<xsl:if test="name() = 'create_file'">
				Create File: <xsl:value-of select="@srcfile"/><br />
			</xsl:if>
			
			<xsl:if test="name() = 'delete_file'">
				Delete File: <xsl:value-of select="@srcfile"/><br />
			</xsl:if>
			
			<xsl:if test="name() = 'open_file'">
				Open File: <xsl:value-of select="@srcfile"/> (<xsl:value-of select="@creationdistribution"/>)<br />
			</xsl:if>
			
			<xsl:if test="name() = 'create_open_file'">
				Create/Open File: <xsl:value-of select="@srcfile"/> (<xsl:value-of select="@creationdistribution"/>)<br />
			</xsl:if>
			
			<xsl:if test="name() = 'create_namedpipe'">
				Create NamedPipe: <xsl:value-of select="@srcfile"/><br />
			</xsl:if>
			
			<xsl:if test="name() = 'set_file_attributes'">
				Set File Attributes: <xsl:value-of select="@srcfile"/> <xsl:value-of select="@dstfile"/> Flags: (<xsl:value-of select="@flags"/>)<br />
			</xsl:if>
			
			<xsl:if test="name() = 'get_file_attributes'">
				Get File Attributes: <xsl:value-of select="@srcfile"/> <xsl:value-of select="@dstfile"/> Flags: (<xsl:value-of select="@flags"/>)<br />
			</xsl:if>
			
			<xsl:if test="name() = 'set_file_time'">
				Set File Time: <xsl:value-of select="@srcfile"/> <xsl:value-of select="@dstfile"/><br />
			</xsl:if>
			
		</xsl:for-each>
		</td></tr></table>
		
		</td>
		</tr>
	<xsl:apply-templates />
</xsl:template>

<xsl:template match="ini_file_section">
		<xsl:variable name="number" select="count(../preceding-sibling::*)+1"/>
		<tr>
		<th>INI Files</th>
		<td>
		
		<table id="details">
								
		<xsl:if test="count(read_value) > 0">
		<tr><th>Read INI File</th></tr>
		<tr><td>
		<xsl:for-each select="read_value">
			<xsl:value-of select="@file"/> [<xsl:value-of select="@section"/>] <xsl:value-of select="@value"/> = <xsl:value-of select="@data"/><br />
		</xsl:for-each>
		</td></tr>
		</xsl:if>
		
		
		<xsl:if test="count(write_value) > 0">
		<tr><th>Read INI File</th></tr>
		<tr><td>
		<xsl:for-each select="write_value">
			<xsl:value-of select="@file"/> [<xsl:value-of select="@section"/>] <xsl:value-of select="@value"/> = <xsl:value-of select="@data"/><br />
		</xsl:for-each>
		</td></tr>
		</xsl:if>
		</table>
		
		</td>
		</tr>
	<xsl:apply-templates />
</xsl:template>

<xsl:template match="process_section">
		<xsl:variable name="number" select="count(../preceding-sibling::*)+1"/>
		<tr>
		<th>Process Management</th>
		<td>
		<xsl:for-each select="create_process">
			Creates Process - Filename (<xsl:value-of select="@filename"/>) CommandLine: (<xsl:value-of select="@commandline"/>) As User: (<xsl:value-of select="@asuser"/>) Creation Flags: (<xsl:value-of select="@creationflags"/>)<br />
		</xsl:for-each>
		<xsl:for-each select="create_process_nt">
			Creates Process NT - Filename (<xsl:value-of select="@filename"/>) CommandLine: (<xsl:value-of select="@commandline"/>) Target PID: (<xsl:value-of select="@targetpid"/>) As User: (<xsl:value-of select="@asuser"/>) Creation Flags: (<xsl:value-of select="@creationflags"/>)<br />
		</xsl:for-each>
		<xsl:for-each select="create_process_as_user">
			Create Process As User - Filename (<xsl:value-of select="@filename"/>) CommandLine: (<xsl:value-of select="@commandline"/>) Target PID: (<xsl:value-of select="@targetpid"/>) As User: (<xsl:value-of select="@asuser"/>) Creation Flags: (<xsl:value-of select="@creationflags"/>)<br />
		</xsl:for-each>
		<xsl:for-each select="kill_process">
			Kill Process - Filename (<xsl:value-of select="@filename"/>) CommandLine: (<xsl:value-of select="@commandline"/>) Target PID: (<xsl:value-of select="@targetpid"/>) As User: (<xsl:value-of select="@asuser"/>) Creation Flags: (<xsl:value-of select="@creationflags"/>)<br />
		</xsl:for-each>
		<xsl:for-each select="enum_processes">
			Enum Processes<br />
		</xsl:for-each>
		<xsl:for-each select="enum_modules">
			Enum Modules - Target PID: (<xsl:value-of select="@targetpid"/>)<br />
		</xsl:for-each>
		<xsl:for-each select="open_process">
			Open Process - Filename (<xsl:value-of select="@filename"/>) Target PID: (<xsl:value-of select="@targetpid"/>)<br />
		</xsl:for-each>
		</td>
		</tr>
	<xsl:apply-templates />
</xsl:template>

<xsl:template match="thread_section">
		<xsl:variable name="number" select="count(../preceding-sibling::*)+1"/>
		<tr>
		<th>Threads</th>
		<td>
		<xsl:for-each select="create_thread">
			Create Thread - Target PID (<xsl:value-of select="@targetpid"/>) Thread ID (<xsl:value-of select="@threadid"/>) Thread ID (<xsl:value-of select="@address"/>) Parameter Address (<xsl:value-of select="@parameteraddress"/>) Creation Flags (<xsl:value-of select="@creationflags"/>)<br />
		</xsl:for-each>
		<xsl:for-each select="create_thread_remote">
			Create Remote Thread - Target PID (<xsl:value-of select="@targetpid"/>) Thread ID (<xsl:value-of select="@threadid"/>) Thread ID (<xsl:value-of select="@address"/>) Parameter Address (<xsl:value-of select="@parameteraddress"/>) Creation Flags (<xsl:value-of select="@creationflags"/>)<br />
		</xsl:for-each>
		<xsl:for-each select="kill_thread">
			Kill Thread - Target PID (<xsl:value-of select="@targetpid"/>) Thread ID (<xsl:value-of select="@threadid"/>) Thread ID (<xsl:value-of select="@address"/>) Parameter Address (<xsl:value-of select="@parameteraddress"/>) Creation Flags (<xsl:value-of select="@creationflags"/>)<br />
		</xsl:for-each>
		</td>
		</tr>
	<xsl:apply-templates />
</xsl:template>

<xsl:template match="service_section">
		<xsl:variable name="number" select="count(../preceding-sibling::*)+1"/>
		<tr>
		<th>Service Management</th>
		<td>
		<xsl:for-each select="open_scmanager">
			Open Service Manager - Name: "<xsl:value-of select="@servicename"/>"<br />
		</xsl:for-each>
		<xsl:for-each select="open_service">
			Open Service - Name: "<xsl:value-of select="@servicename"/>"<br />
		</xsl:for-each>
		<xsl:for-each select="enum_service">
			Enum Services<br />
		</xsl:for-each>
		<xsl:for-each select="create_service">
			Create Service - Name: (<xsl:value-of select="@servicename"/>) Display Name: (<xsl:value-of select="@displayname"/>) File Name: (<xsl:value-of select="@filename"/>) Control: (<xsl:value-of select="@control"/>) Start Type: (<xsl:value-of select="@starttype"/>)<br />
		</xsl:for-each>
		<xsl:for-each select="delete_service">
			Delete Service - Name: (<xsl:value-of select="@servicename"/>) Display Name: (<xsl:value-of select="@displayname"/>) File Name: (<xsl:value-of select="@filename"/>) Control: (<xsl:value-of select="@control"/>) Start Type: (<xsl:value-of select="@starttype"/>)<br />
		</xsl:for-each>
		<xsl:for-each select="start_service">
			Start Service - Name: (<xsl:value-of select="@servicename"/>) Display Name: (<xsl:value-of select="@displayname"/>) File Name: (<xsl:value-of select="@filename"/>) Control: (<xsl:value-of select="@control"/>) Start Type: (<xsl:value-of select="@starttype"/>)<br />
		</xsl:for-each>
		<xsl:for-each select="stop_service">
			Stop Service - Name: (<xsl:value-of select="@servicename"/>) Display Name: (<xsl:value-of select="@displayname"/>) File Name: (<xsl:value-of select="@filename"/>) Control: (<xsl:value-of select="@control"/>) Start Type: (<xsl:value-of select="@starttype"/>)<br />
		</xsl:for-each>
		<xsl:for-each select="control_service">
			Control Service - Name: (<xsl:value-of select="@servicename"/>) Display Name: (<xsl:value-of select="@displayname"/>) File Name: (<xsl:value-of select="@filename"/>) Control: (<xsl:value-of select="@control"/>) Start Type: (<xsl:value-of select="@starttype"/>)<br />
		</xsl:for-each>
		<xsl:for-each select="change_service_config">
			Change Service Configuration - Name: (<xsl:value-of select="@servicename"/>) Display Name: (<xsl:value-of select="@displayname"/>) File Name: (<xsl:value-of select="@filename"/>) Control: (<xsl:value-of select="@control"/>) Start Type: (<xsl:value-of select="@starttype"/>)<br />
		</xsl:for-each>
		<xsl:for-each select="register_service_process">
			Register Service - Name: (<xsl:value-of select="@servicename"/>) Display Name: (<xsl:value-of select="@displayname"/>) File Name: (<xsl:value-of select="@filename"/>) Control: (<xsl:value-of select="@control"/>) Start Type: (<xsl:value-of select="@starttype"/>)<br />
		</xsl:for-each>
		<xsl:for-each select="unload_driver">
			Unload Driver - Name: (<xsl:value-of select="@servicename"/>) Display Name: (<xsl:value-of select="@displayname"/>) File Name: (<xsl:value-of select="@filename"/>) Control: (<xsl:value-of select="@control"/>) Start Type: (<xsl:value-of select="@starttype"/>)<br />
		</xsl:for-each>
		<xsl:for-each select="load_driver">
			Load Driver - Name: (<xsl:value-of select="@servicename"/>) File Name: (<xsl:value-of select="@filename"/>)<br />
		</xsl:for-each>
		</td>
		</tr>
	<xsl:apply-templates />
</xsl:template>

<xsl:template match="mutex_section">
		<xsl:variable name="number" select="count(../preceding-sibling::*)+1"/>
		<tr>
		<th>Mutexes</th>
		<td>
		<xsl:for-each select="create_mutex">
			Creates Mutex: <xsl:value-of select="@name"/><br />
		</xsl:for-each>
		<xsl:for-each select="open_mutex">
			Opens Mutex: <xsl:value-of select="@name"/><br />
		</xsl:for-each>
		</td>
		</tr>
	<xsl:apply-templates />
</xsl:template>

<xsl:template match="virtual_memory_section">
		<xsl:variable name="number" select="count(../preceding-sibling::*)+1"/>
		<tr>
		<th>Virtual Memory</th>
		<td>
		<xsl:for-each select="vm_allocate">
			VM Allocate - Target: (<xsl:value-of select="@targetpid"/>) Address: (<xsl:value-of select="@address"/>) Size: (<xsl:value-of select="@size"/>) Protect: (<xsl:value-of select="@protect"/>) Allocation Type: (<xsl:value-of select="@allocationtype"/>)<br />
		</xsl:for-each>
		<xsl:for-each select="vm_protect">
			VM Protect - Target: (<xsl:value-of select="@targetpid"/>) Address: (<xsl:value-of select="@address"/>) Size: (<xsl:value-of select="@size"/>) Protect: (<xsl:value-of select="@protect"/>)<br />
		</xsl:for-each>
		<xsl:for-each select="vm_read">
			VM Read - Target: (<xsl:value-of select="@targetpid"/>) Address: (<xsl:value-of select="@address"/>) Size: (<xsl:value-of select="@size"/>)<br />
		</xsl:for-each>
		<xsl:for-each select="vm_write">
			VM Write - Target: (<xsl:value-of select="@targetpid"/>) Address: (<xsl:value-of select="@address"/>) Size: (<xsl:value-of select="@size"/>)<br />
		</xsl:for-each>
		</td>
		</tr>
	<xsl:apply-templates />
</xsl:template>

<xsl:template match="user_section">
		<xsl:variable name="number" select="count(../preceding-sibling::*)+1"/>
		<tr>
		<th>User Management</th>
		<td>
		<xsl:for-each select="add_user">
			Add User - Domain: (<xsl:value-of select="@domain"/>) User: (<xsl:value-of select="@user"/>) Host: (<xsl:value-of select="@host"/>)<br />
		</xsl:for-each>
		<xsl:for-each select="delete_user">
			Delete User - Domain: (<xsl:value-of select="@domain"/>) User: (<xsl:value-of select="@user"/>) Host: (<xsl:value-of select="@host"/>)<br />
		</xsl:for-each>
		<xsl:for-each select="enum_user">
			Enum User - Domain: (<xsl:value-of select="@domain"/>) User: (<xsl:value-of select="@user"/>) Host: (<xsl:value-of select="@host"/>)<br />
		</xsl:for-each>
		<xsl:for-each select="logon_as_user">
			Login As User - Domain: (<xsl:value-of select="@domain"/>) User: (<xsl:value-of select="@user"/>) Host: (<xsl:value-of select="@host"/>)<br />
		</xsl:for-each>
		<xsl:for-each select="impersonate_user">
			Impersonate User - Domain: (<xsl:value-of select="@domain"/>) User: (<xsl:value-of select="@user"/>)<br />
		</xsl:for-each>
		<xsl:for-each select="revert_to_self">
			Revert To Self<br />
		</xsl:for-each>
		<xsl:for-each select="get_username">
			Get User Name<br />
		</xsl:for-each>
		<xsl:for-each select="get_userinfo">
			Get User Info - Domain: (<xsl:value-of select="@domain"/>) User: (<xsl:value-of select="@user"/>) Host: (<xsl:value-of select="@host"/>)<br />
		</xsl:for-each>
		</td>
		</tr>
	<xsl:apply-templates />
</xsl:template>

<xsl:template match="registry_section">
		<xsl:variable name="number" select="count(../preceding-sibling::*)+1"/>
		
		<tr>
		<th>Registry</th>
		<td>
		
		<table id="details" width="100%">
		
		
		<xsl:if test="count(create_open_key) > 0">
		<tr><th>Create or Open</th></tr>
		<tr><td>
		<xsl:for-each select="create_open_key">
			<xsl:value-of select="@key"/><br />
		</xsl:for-each>
		</td></tr>
		</xsl:if>
		
		<xsl:if test="count(set_value) > 0">
		<tr><th>Changes</th></tr>
		<tr><td>
		<xsl:for-each select="set_value">
			<xsl:value-of select="@key"/> "<xsl:value-of select="@subkey_or_value"/>" = <xsl:value-of select="@data"/><br />
		</xsl:for-each>
		</td></tr>
		</xsl:if>
		
		<xsl:if test="count(query_value) > 0">
		<tr><th>Reads</th></tr>
		<tr><td>
		<xsl:for-each select="query_value">
			<xsl:value-of select="@key"/> "<xsl:value-of select="@subkey_or_value"/>"<br />
		</xsl:for-each>
		</td></tr>
		</xsl:if>
		
		<xsl:if test="count(enum_keys) > 0">
		<tr><th>Enums</th></tr>
		<tr><td>
		<xsl:for-each select="enum_keys">
			<xsl:value-of select="@key"/><br />
		</xsl:for-each>
		</td></tr>
		</xsl:if>
		</table>
		
		</td>
		</tr>
	<xsl:apply-templates />
</xsl:template>

<xsl:template match="com_section">
		<xsl:variable name="number" select="count(../preceding-sibling::*)+1"/>
		<tr>
		<th>COM</th>
		<td>
		<xsl:for-each select="com_create_instance">
			COM Create Instance: <xsl:value-of select="@inprocserver32"/>, ProgID: (<xsl:value-of select="@progid"/>), Interface ID: (<xsl:value-of select="@interfaceid"/>)<br />
		</xsl:for-each>
		<xsl:for-each select="com_get_class_object">
			COM Get Class Object: <xsl:value-of select="@inprocserver32"/>, Interface ID: (<xsl:value-of select="@interfaceid"/>)<br />
		</xsl:for-each>
		</td>
		</tr>
	<xsl:apply-templates />
</xsl:template>

<xsl:template match="icmp_section">
		<xsl:variable name="number" select="count(../preceding-sibling::*)+1"/>
		<tr>
		<th>ICMP - Ping</th>
		<td>
		<xsl:for-each select="ping">
			Ping Host: <xsl:value-of select="@host"/><br />
		</xsl:for-each>
		</td>
		</tr>
	<xsl:apply-templates />
</xsl:template>

<xsl:template match="network_section">
		<xsl:variable name="number" select="count(../preceding-sibling::*)+1"/>
		<tr>
		<th>Network Shares</th>
		<td>
		<xsl:for-each select="add_share">
			Add Share - Host: (<xsl:value-of select="@host"/>) Network Ressource: (<xsl:value-of select="@networkressource"/>) Filename: (<xsl:value-of select="@filename"/>) As User: (<xsl:value-of select="@asuser"/>)<br />
		</xsl:for-each>
		<xsl:for-each select="delete_share">
			Delete Share - Host: (<xsl:value-of select="@host"/>) Network Ressource: (<xsl:value-of select="@networkressource"/>) Filename: (<xsl:value-of select="@filename"/>) As User: (<xsl:value-of select="@asuser"/>)<br />
		</xsl:for-each>
		<xsl:for-each select="enum_share">
			Enum Network Shares - Network Ressource: (<xsl:value-of select="@networkressource"/>) Host: (<xsl:value-of select="@host"/>)<br />
		</xsl:for-each>
		<xsl:for-each select="add_netjob">
			Add Net Job - Host: (<xsl:value-of select="@host"/>) Network Ressource: (<xsl:value-of select="@networkressource"/>) Filename: (<xsl:value-of select="@filename"/>) As User: (<xsl:value-of select="@asuser"/>)<br />
		</xsl:for-each>
		<xsl:for-each select="connect_share">
			Connect Share - Host: (<xsl:value-of select="@host"/>) Network Ressource: (<xsl:value-of select="@networkressource"/>) Filename: (<xsl:value-of select="@filename"/>) As User: (<xsl:value-of select="@asuser"/>)<br />
		</xsl:for-each>
		<xsl:for-each select="disconnect_share">
			Disconnect Share - Host: (<xsl:value-of select="@host"/>) Network Ressource: (<xsl:value-of select="@networkressource"/>) Filename: (<xsl:value-of select="@filename"/>) As User: (<xsl:value-of select="@asuser"/>)<br />
		</xsl:for-each>
		</td>
		</tr>
	<xsl:apply-templates />
</xsl:template>

<xsl:template match="winsock_section">
		<xsl:variable name="number" select="count(../preceding-sibling::*)+1"/>
		<tr>
		<th>Network Activity</th>
		<td>
		<xsl:for-each select="connections_unknown/connection/action">
			<xsl:value-of select="."/><br />
		</xsl:for-each>
		
		<!-- Get Host By Name -->
		<xsl:if test="count(connections_unknown/connection/gethostbyname) != 0">
		<table id="details" width="100%">
		<tr><th colspan="2">DNS Lookup</th></tr>
		<tr><th>Host Name</th><th>IP Address</th></tr>
		  <xsl:for-each select="connections_unknown/connection/gethostbyname">
		  	<tr>
		  	<td><xsl:value-of select="@requested_host"/></td>
			<td><xsl:value-of select="@resulting_addr"/></td>
			</tr>
		  </xsl:for-each>
		</table>
		</xsl:if>
		
		<!-- UDP Connections -->
		<xsl:if test="count(connections_udp/connection) != 0">
		<table id="details" width="100%">
		<tr><th>UDP Connections</th></tr>
		  <xsl:for-each select="connections_udp/connection">
			<xsl:if test="number(@connectionestablished) != 0">
			<tr>
			<td>
			<b>Remote IP Address: <xsl:value-of select="@remoteaddr"/> Port: <xsl:value-of select="@remoteport"/></b><br />

			<xsl:for-each select="send_datagram|recv_datagram">
				<xsl:if test="name() = 'send_datagram'">
				Send Datagram: <xsl:value-of select="@quantity"/> packet(s) of size <xsl:value-of select="@size"/><br />
				</xsl:if> 
				<xsl:if test="name() = 'recv_datagram'">
				Recv Datagram: <xsl:value-of select="@quantity"/> packet(s) of size <xsl:value-of select="@size"/><br />
				</xsl:if> 
			</xsl:for-each>	
			</td>
			</tr>
			
			</xsl:if> 
			
		  </xsl:for-each>
		  </table>
		</xsl:if> 
		
		
		
		<xsl:for-each select="connections_listening/connection">
			<xsl:choose>
				<xsl:when test="@transportprotocol='TCP'">
						Opened listening TCP connection on port: <xsl:value-of select="@localport"/><br />
				</xsl:when>
				<xsl:when test="@transportprotocol='UDP'">
						Opened listening UDP connection on port: <xsl:value-of select="@localport"/><br />
				</xsl:when>
			</xsl:choose>
		</xsl:for-each>
		
		
		<!-- HTTP data recv -->
		<xsl:if test="count(connections_outgoing/connection/http_data/http_cmd[@method = 'GET']) != 0">
			<table id="details" width="100%">
			<tr><th>Download URLs</th></tr>		
			<xsl:for-each select="connections_outgoing/connection/http_data/http_cmd[@method = 'GET']">
				 <xsl:variable name="addr" select="../../@remoteaddr"/>
				 <tr><td>
				 <xsl:if test="string(../../../../connections_unknown/connection/gethostbyname[@resulting_addr = $addr]/@requested_host) != ''"> 
				 	http://<xsl:value-of select="../../../../connections_unknown/connection/gethostbyname[@resulting_addr = $addr]/@requested_host"/><xsl:value-of select="@url"/>
				 </xsl:if>
				 <xsl:if test="string(../../../../connections_unknown/connection/gethostbyname[@resulting_addr = $addr]/@requested_host) = ''"> 
				 	http://<xsl:value-of select="$addr"/><xsl:value-of select="@url"/>
				 </xsl:if>
				 </td></tr>
			</xsl:for-each>
			</table>
		</xsl:if>
		
		<!-- HTTP data sent -->
		<xsl:if test="count(connections_outgoing/connection/http_data/http_cmd[@method = 'POST']) != 0">
			<table id="details" width="100%">
			<tr><th>Data posted to URLs</th></tr>
			<xsl:for-each select="connections_outgoing/connection/http_data/http_cmd[@method = 'POST']">
				 <xsl:variable name="addr" select="../../@remoteaddr"/>
				 <tr><td>
				 <xsl:if test="string(../../../../connections_unknown/connection/gethostbyname[@resulting_addr = $addr]/@requested_host) != ''"> 
				 http://<xsl:value-of select="../../../../connections_unknown/connection/gethostbyname[@resulting_addr = $addr]/@requested_host"/><xsl:value-of select="@url"/>
				 </xsl:if>
				 <xsl:if test="string(../../../../connections_unknown/connection/gethostbyname[@resulting_addr = $addr]/@requested_host) = ''"> 
				 http://<xsl:value-of select="$addr"/><xsl:value-of select="@url"/>
				 </xsl:if>
				 </td></tr>
			</xsl:for-each>
			</table>
		</xsl:if>
		
		<!-- Out going connections -->
		<xsl:for-each select="connections_outgoing/connection">
			<xsl:choose>
				<xsl:when test="@protocol='IRC'">
					<ul>
						<li>C&amp;C Server: <xsl:value-of select="@remoteaddr"/>:<xsl:value-of select="@remoteport"/></li>
						<li>Server Password: <xsl:value-of select="irc_data/@password"/></li>
						<li>Username: <xsl:value-of select="irc_data/@username"/></li>
						<li>Nickname: <xsl:value-of select="irc_data/@nick"/></li>
						<xsl:for-each select="action">
							<li><xsl:value-of select="."/></li>
						</xsl:for-each>
					</ul>
				</xsl:when>
				
				<xsl:when test="@protocol='SMTP'">
					<ul>
						<li>SMTP: <xsl:value-of select="@remoteaddr"/>:<xsl:value-of select="@remoteport"/></li>
						<xsl:if test="not(contains(string(.), '  '))">
							<li>Username / Password: <xsl:value-of select="@username"/> / <xsl:value-of select="password"/></li>
						</xsl:if>
						<xsl:for-each select="action">
							<li><xsl:value-of select="."/></li>
						</xsl:for-each>
					</ul>
				</xsl:when>
				<xsl:when test="@remoteport='25'">
						SMTP: <xsl:value-of select="@remoteaddr"/>:<xsl:value-of select="@remoteport"/><br />
				</xsl:when>
				<xsl:when test="@protocol='Unknown'">
					<xsl:variable name="addr" select="@remoteaddr"/>					
					<xsl:if test="string(../../connections_unknown/connection/gethostbyname[@resulting_addr = $addr]/@requested_host) != ''"> 
					Outgoing connection to remote server: <xsl:value-of select="../../connections_unknown/connection/gethostbyname[@resulting_addr = $addr]/@requested_host"/> port <xsl:value-of select="@remoteport"/><br />
					</xsl:if>
					<xsl:if test="string(../../connections_unknown/connection/gethostbyname[@resulting_addr = $addr]/@requested_host) = ''"> 
					Outgoing connection to remote server: <xsl:value-of select="$addr"/> port <xsl:value-of select="@remoteport"/><br />
				 	</xsl:if>
				</xsl:when>
				<xsl:when test="@transportprotocol='UDP'">
					<xsl:variable name="addr" select="@remoteaddr"/>
					<xsl:if test="string(../../connections_unknown/connection/gethostbyname[@resulting_addr = $addr]/@requested_host) != ''"> 
					Outgoing connection to remote server: <xsl:value-of select="../../connections_unknown/connection/gethostbyname[@resulting_addr = $addr]/@requested_host"/> UDP port <xsl:value-of select="@remoteport"/><br />
					 </xsl:if>
					 <xsl:if test="string(../../connections_unknown/connection/gethostbyname[@resulting_addr = $addr]/@requested_host) = ''"> 
					 Outgoing connection to remote server: <xsl:value-of select="$addr"/> UDP port <xsl:value-of select="@remoteport"/><br />
				 	</xsl:if>
				</xsl:when>
				<xsl:when test="@transportprotocol='TCP'">
					<xsl:variable name="addr" select="@remoteaddr"/>					
					<xsl:if test="string(../../connections_unknown/connection/gethostbyname[@resulting_addr = $addr]/@requested_host) != ''"> 
					Outgoing connection to remote server: <xsl:value-of select="../../connections_unknown/connection/gethostbyname[@resulting_addr = $addr]/@requested_host"/> TCP port <xsl:value-of select="@remoteport"/><br />
					 </xsl:if>
					 <xsl:if test="string(../../connections_unknown/connection/gethostbyname[@resulting_addr = $addr]/@requested_host) = ''"> 
					 Outgoing connection to remote server: <xsl:value-of select="$addr"/> TCP port <xsl:value-of select="@remoteport"/><br />
				 	</xsl:if>
				</xsl:when>
			</xsl:choose>
		</xsl:for-each>
		</td>
		</tr>
</xsl:template>
		

</xsl:stylesheet>


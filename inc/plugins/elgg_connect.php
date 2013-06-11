<?php
/**
 * Elgg Connect Plugin
 * Copyright 2013 Thang Nguyen, All Rights Reserved
 *
 * Website: http://atemix.com
 * License: LGPL
 *
 * 
 */
 
// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook("global_start", "elgg_connect_global_start");

function elgg_connect_info()
{
	/**
	 * Array of information about the plugin.
	 * name: The name of the plugin
	 * description: Description of what the plugin does
	 * website: The website the plugin is maintained at (Optional)
	 * author: The name of the author of the plugin
	 * authorsite: The URL to the website of the author (Optional)
	 * version: The version number of the plugin
	 * guid: Unique ID issued by the MyBB Mods site for version checking
	 * compatibility: A CSV list of MyBB versions supported. Ex, "121,123", "12*". Wildcards supported.
	 */
	return array(
		"name"			=> "Elgg Connect!",
		"description"	=> "Connect to Elgg elgg.org",
		"website"		=> "http://mybb.com",
		"author"		=> "Thang Nguyen",
		"authorsite"	=> "http://mybb.com",
		"version"		=> "1.0",
		"guid" 			=> "",
		"compatibility" => "16*",
	);
}

function elgg_connect_install()
{
global $db;
$db->write_query("
	CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."elggconnect_users
	(`id` int(10) NOT NULL auto_increment,
	`mbb_uid` int(10) NOT NULL default '0',
	`mybb_username` varchar(120) NOT NULL DEFAULT '',
	`elgg_guid` bigint(20) unsigned NOT NULL,
	`elgg_password` varchar(32) NOT NULL DEFAULT '',
	`elgg_salt` varchar(8) NOT NULL DEFAULT '',
	PRIMARY KEY  (id))");
}


function elgg_connect_is_installed()
{
global $db;
if($db->table_exists("elggconnect_users"))
{
	return true;
}
return false;
}

function elgg_connect_uninstall()
{
global $db;

// Drop the Table
$db->drop_table("elggconnect_users");
}

function elgg_connect_activate()
{
}

function elgg_connect_deactivate()
{
}

function elgg_connect_global_start()
{
	global $mybb, $db;
	
	//exit if user not logged in
	if($mybb->user['uid'] == 0)
		exit;
	
	//check if user already migrated to Elgg
	$query = $db->simple_select("elggconnect_users", "*", "mbb_uid = '{$mybb->user['uid']}'", array("limit" => 1));
	$elgg = $db->fetch_field($query);
	
	require_once(MYBB_ROOT.'KLogger.php');
	$log = new KLogger(dirname(__FILE__) , KLogger::DEBUG );
	
	if ($elgg['id'])
	{
		$log->LogDebug("Found Elgg id");
	}
	else
	{
		//migrate to elgg and update db
		$log->LogDebug("Not Found Elgg id");
	}
}
?>
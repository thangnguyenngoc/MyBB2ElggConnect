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

$plugins->add_hook(" global_end", "elggconnect_global_end");

function elggconnect_info()
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
		"compatibility" => "16*"
	);
}

/**
 * ADDITIONAL PLUGIN INSTALL/UNINSTALL ROUTINES
 *
 * _install():
 *   Called whenever a plugin is installed by clicking the "Install" button in the plugin manager.
 *   If no install routine exists, the install button is not shown and it assumed any work will be
 *   performed in the _activate() routine.
 **/
 function elggconnect_install()
 {
	$db->write_query("
		CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."elggconnect_users
		(`id` int(10) NOT NULL auto_increment,
		`mbb_uid` int(10) NOT NULL default '0',
		`mybb_username` varchar(120) NOT NULL DEFAULT '',
		`elgg_guid` bigint(20) unsigned NOT NULL,
		`elgg_username` varchar(128) NOT NULL DEFAULT '',
		`elgg_password` varchar(32) NOT NULL DEFAULT '',
		`elgg_salt` varchar(8) NOT NULL DEFAULT '',
		PRIMARY KEY  (id))");
 }
 
/* * _is_installed():
 *   Called on the plugin management page to establish if a plugin is already installed or not.
 *   This should return TRUE if the plugin is installed (by checking tables, fields etc) or FALSE
 *   if the plugin is not installed.
 **/
 function elggconnect_is_installed()
 {
 	global $db;
 	if($db->table_exists("elggconnect_users"))
  	{
  		return true;
 	}
 	return false;
 }
 
 /** _uninstall():
 *    Called whenever a plugin is to be uninstalled. This should remove ALL traces of the plugin
 *    from the installation (tables etc). If it does not exist, uninstall button is not shown.
 **/
 function elggconnect_uninstall()
 {
	global $db;

	// Drop the Table
	$db->drop_table("elggconnect_users");
 }
 /**
 * _activate():
 *    Called whenever a plugin is activated via the Admin CP. This should essentially make a plugin
 *    "visible" by adding templates/template changes, language changes etc.
 *
 * function elggconnect_activate()
 * {
 * }
 *
 * _deactivate():
 *    Called whenever a plugin is deactivated. This should essentially "hide" the plugin from view
 *    by removing templates/template changes etc. It should not, however, remove any information
 *    such as tables, fields etc - that should be handled by an _uninstall routine. When a plugin is
 *    uninstalled, this routine will also be called before _uninstall() if the plugin is active.
 *
 * function elggconnect_deactivate()
 * {
 * }
 */


function elggconnect_global_end()
{
	//exit if user not logged in
	if($mybb->user['uid'] == 0)
		exit;
	
	//check if user already migrated to Elgg
	$query = $db->simple_select("elggconnect_users", "*", "mbb_uid = '{$mybb->user['uid']}'", array("limit" => 1));
	$elgg = $db->fetch_field($query);
	if ($elgg['id'])
	{
		//todo: call elgg api to log in
	}
	else
	{
		//migrate to elgg and update db
	}
}

?>
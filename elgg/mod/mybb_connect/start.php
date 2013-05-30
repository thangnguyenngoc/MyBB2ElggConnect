<?php
/**
 * Describe plugin here
 */

elgg_register_event_handler('init', 'system', 'mybb_connect_init');

function mybb_connect_init() {
	// Rename this function based on the name of your plugin and update the
	// elgg_register_event_handler() call accordingly

	// Register a script to handle (usually) a POST request (an action)
	$base_dir = elgg_get_plugins_path() . 'my_plugin/actions/my_plugin';
	//elgg_register_action('my_plugin', "$base_dir/my_action.php");

	// Extend the main CSS file
	//elgg_extend_view('css/elgg', 'my_plugin/css');

	// Add a menu item to the main site menu
	//$item = new ElggMenuItem('my_plugin', elgg_echo('my_plugin:menu'), 'my_url');
	//elgg_register_menu_item('site', $item);
	
	expose_function("mybb_connect.registeruser", 
                "mybb_connect_register_user", 
                 array( 'username' => array ('type' => 'string'),
                       'password' => array ('type' => 'string'),
					    'salt' => array ('type' => 'string'),
                       'email' => array ('type' => 'string'),
                     ),
                 'MyBB connect - register new user to Elgg',
                 'GET',
                 false,	//no user authentication key as the api only allow calls from the same server
                 false
                );
}

function mybb_connect_register_user($username, $password, $salt, $email) 
	//todo: check if the request comes from the same server
	//todo: register user
	require_once dirname(__FILE__) . '/lib/users.php';
	
	$elgg_user = get_user_by_username($username);
	if ($elgg_user)	//user already exist
		return false;
	
	//return user guid to MyBB
	return register_user($username, $salt, $username, $email);
}

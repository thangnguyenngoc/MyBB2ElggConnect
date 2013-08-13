<?php
/**
 * Save the mybbconnect settings
 */

$site = elgg_get_site_entity();

$simple_settings = array('enable_authenticationkey', 'authentication_key', );
foreach ($simple_settings as $setting) {
	elgg_set_plugin_setting($setting, get_input($setting), 'mybbconnect');
}

system_message(elgg_echo('mybbconnect:settings:success'));

forward(REFERER);

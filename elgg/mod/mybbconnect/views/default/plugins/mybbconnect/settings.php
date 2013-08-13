<?php
/**
 * Mybb Connect settings
 */

$data = array(
	'enable_authenticationkey' => array(
		'type' => 'checkbox',
		'value' => 1,
		'checked' => elgg_get_plugin_setting('enable_authenticationkey', 'mybbconnect') == 1,
	),

	'authentication_key' => array(
		'type' => 'text',
		'value' => elgg_get_plugin_setting('authentication_key', 'mybbconnect'),
	),
);

$form_vars = array('id' => 'mybbconnect-settings-form', 'class' => 'elgg-form-settings');
$body_vars = array('data' => $data);
echo elgg_view_form('mybbconnect/settings', $form_vars, $body_vars);
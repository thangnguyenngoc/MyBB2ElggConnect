<?php
/**
 * Settings form body
 *
 * @uses $vars['values']
 */

echo '<p>' . elgg_echo('mybbconnect:settings:explanation') . '</p>';

foreach ($vars['data'] as $name => $info) {
	echo '<div>';
	if ($info['type'] == 'checkbox') {
		echo '<label>';
		echo elgg_view("input/checkbox", array(
			'name' => $name,
			'value' => $info['value'],
			'checked' => $info['checked'],
		));
		echo elgg_echo("mybbconnect:label:$name") . '</label>';
		echo '<span class="elgg-text-help">' . elgg_echo("mybbconnect:help:$name") . '</span>';
	} else {
		echo '<label>' . elgg_echo("mybbconnect:label:$name");
		echo elgg_view("input/{$info['type']}", array(
			'name' => $name,
			'value' => $info['value'],
			'options_values' => $info['options_values'],
		));
		echo '</label>';
		echo '<span class="elgg-text-help">' . elgg_echo("mybbconnect:help:$name") . '</span>';
	}
	echo '</div>';
}
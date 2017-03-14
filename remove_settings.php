<?php

/**
 * This file is a simplified database uninstaller. It does what it is suppoed to.
 */

// If we have found SSI.php and we are outside of ElkArte, then we are running standalone.
if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('ELK'))
	require_once(dirname(__FILE__) . '/SSI.php');
elseif (!defined('ELK')) // If we are outside ElkArte and can't find SSI.php, then throw an error
	die('<b>Error:</b> Cannot uninstall - please verify you put this file in the same place as ElkArte\'s SSI.php.');

global $modSettings;

$db = database();

// Only do database changes on uninstall if requested.
if (!empty($_POST['do_db_changes']))
{
	// List all mod settingss here to Remove
	$mod_settings_to_remove = array(
		'geoIP_enablemap',
		'geoIP_Key',
		'geoIP_enablepinid',
		'geoIP_enablereg',
		'geoIP_cc_block',
		'geoIPSidebar',
		'geoIPType',
		'geoIPNavType',
		'geoIPDefaultLat',
		'geoIPDefaultLong',
		'geoIPDefaultZoom',
		'geoIPPinBackground',
		'geoIPPinForeground',
		'geoIPPinStyle',
		'geoIPPinShadow',
		'geoIPPinSize',
		'geoIPPinText',
		'geoIPPinIcon',
		'geoIP_enableflags',
	);

	// Remove the modsettings from the settings table
	if (count($mod_settings_to_remove) > 0)
	{
		// Remove the mod_settings if applicable, first the session
		foreach ($mod_settings_to_remove as $setting)
			if (isset($modSettings[$setting]))
				unset($modSettings[$setting]);

		// And now the database values
		$db->query('', '
			DELETE FROM {db_prefix}settings
			WHERE variable IN ({array_string:settings})',
			array(
				'settings' => $mod_settings_to_remove,
			)
		);

		// Make sure the cache is reset as well
		updateSettings(array(
			'settings_updated' => time(),
		));
	}

	if (ELK == 'SSI')
	   echo 'Congratulations! You have successfully removed this addon!';
}

<?php

/**
 *
 * @package "geopIP" Mod for ElkArte
 * @author Spuds
 * @copyright (c) 2011 Spuds
 * @license Mozilla Public License version 1.1 http://www.mozilla.org/MPL/1.1/.
 *
 * @version 1.0
 *
 */

if (!defined('ELK'))
	die('No access...');

/**
 * Returns the users geoIP values as set by nginx/fpm params
 */
function geo_logon()
{
	return array(
		'latitude' => getenv('GEOIP_LATITUDE'),
		'longitude' => getenv('GEOIP_LONGITUDE'),
		'country' => getenv('GEOIP_CITY_COUNTRY_NAME'),
		'city' => getenv('GEOIP_CITY'),
		'cc' => getenv('GEOIP_CITY_COUNTRY_CODE')
	);
}

/**
 * geo_search()
 *
 * Takes an array of ip address and determines the geo location
 * uses network lookups to find the location
 * returns the information in an array
 *
 * @param mixed[] $ip_input
 * @param boolean $search
 */
function geo_search($ip_input, $search = true)
{
	require_once (SUBSDIR . '/Package.subs.php');
	$memberIPData = array();
	$ips = array();

	// It must be an array, even if we are only looking up one IP
	if (!is_array($ip_input))
		$ips = array($ip_input);
	else
	{
		// Passed an array from the log_online, this should contain all geoip info established at logon
		foreach ($ip_input as $member => $data)
		{
			// already have the data?
			if (!empty($data['latitude']) && !empty($data['longitude']) && !empty($data['country']) && !empty($data['cc']))
			{
				// data is available, use it and save a lookup
				$memberIPData[$member]['country'] = $data['country'];
				$memberIPData[$member]['city'] = isset($data['city']) ? $data['city'] : '';
				$memberIPData[$member]['latitude'] = $data['latitude'];
				$memberIPData[$member]['longitude'] = $data['longitude'];
				$memberIPData[$member]['cc'] = $data['cc'];
				$memberIPData[$member]['session'] = $data['session'];
			}
			// Or look it up instead :\
			else
				$ips[$member] = $data['ip'];
		}
	}

	// Look up what we don't know
	foreach ($ips as $member => $ip)
	{
		// Look up some geo info, lets try telize first
		$geo_data = fetch_web_data('http://www.telize.com/geoip/' . $ip);
		if (!empty($geo_data))
		{
			$memberIPData[$member] = json_decode($geo_data, true);
			$memberIPData[$member]['cc'] = !empty($memberIPData[$member]['country_code']) ? $memberIPData[$member]['country_code'] : '';
		}

		// Missing anything?
		if (empty($geo_data['city']) && !empty($geo_data['country_code']))
		{
			$data = fetch_web_data('http://api.hostip.info/get_html.php?ip=' . $ip . '&position=true');
			if (preg_match('~Country: (.*(?:\((.*)\)))\n?City: (.*)\n?Latitude: (.*)\nLongitude: (.*)\n~isU', $data, $match))
			{
				// We trust the data from geo just a bit more
				if (!empty($match[2]) && $match[2] == $geo_data['country_code'])
				{
					// Place this result into our result for this user
					$memberIPData[$member]['country'] = empty($geo_data['country']) ? $match[1] : $geo_data['country'];
					$memberIPData[$member]['city'] = $match[3];
					$memberIPData[$member]['latitude'] = empty($geo_data['latitude']) ? $match[4] : $geo_data['latitude'];
					$memberIPData[$member]['longitude'] = empty($geo_data['longitude']) ? $match[5] : $geo_data['longitude'];

					// Update the online log so we don't do this again if it was for an online user ofcourse
					if (!empty($memberIPData[$member]['session']))
						geo_save_data($memberIPData[$member]);
				}
			}
		}
	}

	return $memberIPData;
}

/**
 * Updates the online log with the geoip information
 *
 * @param array $data
 */
function geo_save_data($data = array())
{
	$db = database();

	// Simply update this session with the newly found data
	$db->query('', '
		UPDATE {db_prefix}log_online
		SET latitude = {float:latitude}, longitude = {float:longitude}, country = {string:country}, city = {string:city}, cc = {string:cc}
		WHERE session = {string:session}',
		array(
			'latitude' => $data['latitude'],
			'longitude' => $data['longitude'],
			'country' => $data['country'],
			'city' => $data['city'],
			'cc' => $data['cc'],
			'session' => $data['session'],
		)
	);
}

/**
 * geo_dot2long()
 *
 * - takes a 123.456.789.012 ip address are returns it as a long int
 * - take a long int and converts it back to a dot ip address
 *
 * @param mixed $ip_addr
 */
function geo_dot2long($ip_addr)
{
	// We could use built in functions but why when math is fun!
	if (empty($ip_addr))
		return 0;
	elseif (strpos($ip_addr, '.') === false)
		return (int) ($ip_addr / (256 * 256 * 256) % 256) . '.' . (int) ($ip_addr / (256 * 256) % 256) . '.' . (int) (($ip_addr / 256) % 256) . '.' . (int) (($ip_addr) % 256);
	elseif (preg_match('~\d{1,3}.\d{1,3}.\d{1,3}.\d{1,3}~', $ip_addr, $dummy))
	{
		$ips = explode('.', $ip_addr);
		return ($ips[3] + $ips[2] * 256 + $ips[1] * 256 * 256 + $ips[0] * 256 * 256 * 256);
	}
	else
		return 0;
}
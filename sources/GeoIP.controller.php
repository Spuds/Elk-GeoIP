<?php

/**
 *
 * @package "geopIP" Addon for ElkArte
 * @author Spuds
 * @copyright (c) 2011 Spuds
 * @license Mozilla Public License version 1.1 http://www.mozilla.org/MPL/1.1/.
 *
 * @version 1.4
 *
 */

if (!defined('ELK'))
{
	die('No access...');
}

class GeoIP_Controller extends Action_Controller
{
	/**
	 * Entry point function for GeoIP, permission checks, makes sure its on
	 */
	public function pre_dispatch()
	{
		global $modSettings;

		// Whos online enabled and geoip enabled are required
		if (empty($modSettings['who_enabled']) || empty($modSettings['geoIP_enablemap']))
		{
			fatal_lang_error('feature_disabled', true);
		}

		// First are they allowed to view whos online and the online map?
		isallowedTo(array('geoIP_view'));
		isallowedTo(array('who_view'));

		// Some things we will need
		loadLanguage('geoIP');
		require_once(SUBSDIR . '/GeoIP.subs.php');
	}

	/**
	 * Default action method, if a specific method wasn't
	 * directly called already. Simply forwards to main.
	 */
	public function action_index()
	{
		$this->action_geoip_main();
	}

	/**
	 * geoIP()
	 *
	 * Traffic cop, checks permissions
	 * Calls the template which in turn calls this to request the xml file or js file to template inclusion
	 */
	public function action_geoip_main()
	{
		global $context, $txt;

		// Create the pins for use, do it now so its available everywhere
		$this->_geo_buildpins();

		// Requesting the XML details or the JS file?
		if (isset($_GET['sa']) && $_GET['sa'] === '.xml')
		{
			return $this->action_geoMapsXML();
		}

		if (isset($_GET['sa']) && $_GET['sa'] === '.js')
		{
			return $this->action_geoMapsJS();
		}

		// load up our template and style sheet
		loadTemplate('geoIP');
		loadCSSFile('geoIP.css');
		$context['sub_template'] = 'geoIP';
		$context['page_title'] = $txt['geoIP'];
	}

	/**
	 * geoMapsJS()
	 *
	 * Creates the javascript file based on the admin settings
	 * Called from the map template file via map sa = js
	 */
	public function action_geoMapsJS()
	{
		global $scripturl, $txt, $modSettings;

		// Lets dump everything in the buffer so we can return nice clean javascript to the template
		ob_end_clean();

		// Compressed or not
		if (!empty($modSettings['enableCompressedOutput']))
		{
			@ob_start('ob_gzhandler');
		}
		else
		{
			ob_start();
		}

		// Start up the session URL fixer.
		ob_start('ob_sessrewrite');

		// Let them know what they are about to get
		header('Content-Type: application/javascript');

		// What type of pin are we using?
		$this->_npin = $modSettings['npin'];
		$this->_mshd = (!empty($modSettings['geoIPPinShadow'])) ? '_withshadow' : '';

		// Validate the icon size to keep from breaking
		$m_iconsize = (isset($modSettings['geoIPPinSize']) && $modSettings['geoIPPinSize'] > 19) ? $modSettings['geoIPPinSize'] : 20;

		// Set our member and pin sizes the image sizes are 21 X 34 for standard 40 X 37 with a shadow
		// We need to tweak the sizes based on these W/H ratios to maintain aspect ratio
		// and overall size so that a mixed shawdow/none appear the same size
		$m_icon_w = ($this->_mshd != '') ? $m_iconsize * 1.08 : $m_iconsize * .62;
		$m_icon_h = $m_iconsize;

		// Now set all those anchor points based on the icon size, icon at pin mid bottom, info mid top(ish)....
		$m_iconanchor_w = ($this->_mshd != '') ? $m_icon_w / 3.0 : $m_icon_w / 2.0;
		$m_iconanchor_h = $m_icon_h;

		// Lets start making some javascript
		echo '// Globals
	var xhr = false;

	// Arrays to hold copies of the markers and html used by the sidebar
	var gmarkers = [],
		htmls = [],
		sidebar_html = "";

	// Icon locations
	var chartbase = "//chart.apis.google.com/chart";

	// Our pin to show on the map ....
	var pic = {
		url: chartbase + "' . $this->_npin . '",
		size: null,
		origin: null,
		anchor: new google.maps.Point(' . $m_iconanchor_w . ', ' . $m_iconanchor_h . '),
		scaledSize: new google.maps.Size(' . $m_icon_w . ', ' . $m_icon_h . ')
	};

	// Map and info bubble
	var map = null,
		infowindow = null;

	// Read the xml data
	function makeRequest(url) {
		if (window.XMLHttpRequest)
		{
			xhr = new XMLHttpRequest();
		}
		else
		{
			if (window.ActiveXObject)
			{
				try {
					xhr = new ActiveXObject("Microsoft.XMLHTTP");
				} catch (e) { }
			}
		}

		if (xhr)
		{
			xhr.onreadystatechange = showContents;
			xhr.open("GET", url, true);
			xhr.send(null);
		}
		else
		{
			document.write("' . $txt['geoIP_xmlerror'] . '");
		}
	}

	function showContents() {
		var xmldoc = \'\';

		if (xhr.readyState == 4)
		{
			// Run on server (200) or local machine (0)
			if (xhr.status == 200 || xhr.status == 0)
			{
				xmldoc = xhr.responseXML;
				makeMarkers(xmldoc);
			}
			else
			{
				document.write("' . $txt['geoIP_error'] . ' - " + xhr.status);
			}
		}
	}

	// Create the map and load our data
	function initialize() {
		// Create the map
		var latlng = new google.maps.LatLng(' . (!empty($modSettings['geoIPDefaultLat']) ? $modSettings['geoIPDefaultLat'] : 0) . ', ' . (!empty($modSettings['geoIPDefaultLong']) ? $modSettings['geoIPDefaultLong'] : 0) . '),
			options = {
				zoom: ' . $modSettings['geoIPDefaultZoom'] . ',
				center: latlng,
				scrollwheel: false,
				mapTypeId: google.maps.MapTypeId.' . $modSettings['geoIPType'] . ',
				mapTypeControlOptions: {
					style: google.maps.MapTypeControlStyle.DROPDOWN_MENU
				},
				zoomControl: true,
				zoomControlOptions: {
					style: google.maps.ZoomControlStyle.' . $modSettings['geoIPNavType'] . '
				},
			};

		map = new google.maps.Map(document.getElementById("map"), options);

		// Load the data
		makeRequest("' . $scripturl . '?action=geoIP;sa=.xml");
	}

	// Function to read the output of the marker xml
	function makeMarkers(xmldoc) {
		var markers = xmldoc.documentElement.getElementsByTagName("marker"),
			point = null,
			html = null,
			label = null,
			marker = null;

		for (var i = 0; i < markers.length; i++) {
			point = new google.maps.LatLng(parseFloat(markers[i].getAttribute("lat")),parseFloat(markers[i].getAttribute("lng")));
			html = markers[i].childNodes[0].nodeValue;
			label = markers[i].getAttribute("label");
			marker = createMarker(point, pic, label, html, i);
		}

		// Put the assembled sidebar_html contents into the sidebar div
		document.getElementById("gooSidebar").innerHTML = sidebar_html;
	}

	// Create the marker and set up the event window
	function createMarker(point, pic, name, html, i) {
		// Map marker
		var marker = new google.maps.Marker({
			position: point,
			map: map,
			icon: pic,
			clickable: true,
			title: name
		});

		// Listen for a marker click
		google.maps.event.addListener(marker,"click", function() {
			if (infowindow)
				infowindow.close();

			infowindow = new google.maps.InfoWindow({content: html, maxWidth:280});
			infowindow.open(map, marker);
		});

		// Save the info used to populate the sidebar
		gmarkers.push(marker);
		htmls.push(html);
		name = name.replace(/\[b\](.*)\[\/b\]/gi, "<strong>$1</strong>");

		// Add a line to the sidebar html';
		if ($modSettings['googleMap_Sidebar'] !== 'none')
		{
			echo '
		sidebar_html += \'<a href="javascript:finduser(\' + i + \')">\' + name + \'</a><br /> \';';
		}

		echo '
	}

	// This function picks up the click and opens the corresponding info window
	function finduser(i) {
		// Close any open info boxes
		if (infowindow)
			infowindow.close();

		var marker = gmarkers[i]["position"];

		infowindow = new google.maps.InfoWindow({content: htmls[i], maxWidth:280});
		infowindow.setPosition(marker);
		infowindow.open(map);
	}

	google.maps.event.addDomListener(window, "load", initialize);';

		obExit(false);
	}

	/**
	 * geoMapsXML()
	 *
	 * - creates the xml data for use on the map
	 * - pin info window content
	 * - map sidebar layout
	 */
	public function action_geoMapsXML()
	{
		global $settings, $options, $txt, $modSettings, $memberContext, $user_info;

		$db = database();

		// Lets dump everything in the buffer and start clean for this xml result
		ob_end_clean();

		// Start a new clean buffer, compressed or not
		if (!empty($modSettings['enableCompressedOutput']))
		{
			@ob_start('ob_gzhandler');
		}
		else
		{
			ob_start();
		}

		// Start up the session URL fixer.
		ob_start('ob_sessrewrite');

		// XML Header
		header('Content-Type: application/xml; charset=UTF-8');

		// Lets find the online members and thier ip's
		$guests = array();
		$temp = array();
		$ips = array();
		$spider = false;

		// Can they see spiders ... ewww stomp em :P
		if (!empty($modSettings['show_spider_online']) && ($modSettings['show_spider_online'] == 2 || allowedTo('admin_forum')) && !empty($modSettings['spider_name_cache']))
		{
			$spider = '(lo.id_member = 0 AND lo.id_spider > 0)';
		}

		// Look for people online
		$request = $db->query('', '
			SELECT
				lo.session, lo.id_member, lo.latitude, lo.longitude, lo.country, lo.city, lo.id_spider, lo.cc, INET_NTOA(lo.ip) AS ip, IFNULL(mem.show_online, 1) AS show_online
			FROM {db_prefix}log_online AS lo
				LEFT JOIN {db_prefix}members AS mem ON (lo.id_member = mem.id_member)
			WHERE (lo.id_member >= 0 AND lo.id_spider = 0)' . (!empty($spider) ? '
				OR {raw:spider}' : '') . '
			ORDER BY lo.log_time DESC',
			array(
				'spider' => $spider,
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			// Don't load blank locations or hidden members to non moderators.
			if ((!empty($row['show_online']) || allowedTo('moderate_forum')) && (!empty($row['latitude'])) && (!empty($row['longitude'])))
			{
				// load the information for map use.
				$ips[$row['id_member']] = array(
					'ip' => $row['ip'],
					'session' => $row['session'],
					'is_hidden' => $row['show_online'] == 0,
					'id_spider' => $row['id_spider'],
					'latitude' => $row['latitude'],
					'longitude' => $row['longitude'],
					'country' => $row['country'],
					'city' => $row['city'],
					'cc' => $row['cc'],
				);

				// keep track of the members vs guests/spiders
				if (!empty($row['id_member']))
				{
					$temp[] = $row['id_member'];
				}
				else
				{
					$guests[] = $ips[$row['id_member']];
				}
			}
		}
		$db->free_result($request);

		// Get the geoIP information for these members
		$memberIPData = geo_search($ips);

		// Load all of the data for these online members
		loadMemberData($temp);
		foreach ($temp as $v)
		{
			loadMemberContext($v);
		}

		// Let's actually start making the XML
		echo '<?xml version="1.0" encoding="UTF-8"?', '>
<markers>';

		if (isset($memberContext))
		{
			// To prevent the avatar being outside the popup info window we set a max div height
			$div_height = max(isset($modSettings['avatar_max_height_external']) ? $modSettings['avatar_max_height_external'] : 0, isset($modSettings['avatar_max_height_upload']) ? $modSettings['avatar_max_height_upload'] : 0);

			// Assuming we have data to work with ... build the info bubble
			foreach ($memberContext as $marker)
			{
				// No location ... no pin ;)
				if (empty($memberIPData[$marker['id']]['latitude']) && empty($memberIPData[$marker['id']]['longitude']))
				{
					continue;
				}

				// If they are allowed to see the user info to pin, build the blurb.
				if (!empty($modSettings['geoIP_enablepinid']) || allowedTo('moderate_forum'))
				{
					$datablurb = '
		<div class="googleMap">
				<h4>
					<a href="' . $marker['href'] . '">' . $marker['name'] . '</a>
				</h4>';

					// Avatar
					if (!empty($settings['show_user_images']) && empty($options['show_no_avatars']) && !empty($marker['avatar']['image']))
					{
						$datablurb .= '
				<div class="gmm_avatar" style="height:' . $div_height . 'px">' . $marker['avatar']['image'] . '<br /></div>';
					}

					// User info section
					$datablurb .= '
				<div class="gmm_poster">
					<ul class="reset">';

					// Show the member's primary group (like 'Administrator') if they have one.
					if (!empty($marker['group']))
					{
						$datablurb .= '
						<li class="membergroup">' . $marker['group'] . '</li>';
					}

					// Show the post group if and only if they have no other group or the option is on, and they are in a post group.
					if ((empty($settings['hide_post_group']) || $marker['group'] === '') && $marker['post_group'] !== '')
					{
						$datablurb .= '
						<li class="postgroup">' . $marker['post_group'] . '</li>';
					}

					// groups icons
					$datablurb .= '
						<li class="icons">' . $marker['group_icons'] . '</li>';

					// show the title, if they have one
					if (!empty($marker['title']) && !$user_info['is_guest'])
					{
						$datablurb .= '
						<li class="title">' . $marker['title'] . '</li>';
					}

					// Show some geo id info
					if (!empty($memberIPData[$marker['id']]['city']))
					{
						$datablurb .= '
						<li class="title">' . $memberIPData[$marker['id']]['city'];
					}

					if (!empty($memberIPData[$marker['id']]['region']))
					{
						$datablurb .= ', ' . $memberIPData[$marker['id']]['region'];
					}

					$datablurb .= '</li>';

					if (!empty($memberIPData[$marker['id']]['country']))
					{
						$datablurb .= '
						<li class="icons">
							<img src="' . $settings['default_images_url'] . '/ISO_3166_Flags/' . $memberIPData[$marker['id']]['cc'] . '.gif"  height="12" width="18" border="0" alt="[ * ]" title="' . $memberIPData[$marker['id']]['country'] . '"/>
						</li>';
					}

					$datablurb .= '
					</ul>
				</div>';
				}
				else
				{
					$datablurb = $txt['who_member'];
				}

				// Let's bring it all together...
				$markers = '<marker lat="' . round($memberIPData[$marker['id']]['latitude'], 6) . '" lng="' . round($memberIPData[$marker['id']]['longitude'], 6) . '" ';
				$markers .= 'label="' . $marker['name'] . '"><![CDATA[' . $datablurb . ']]></marker>';

				echo $markers;
			}
		}

		// Now those lovely little guests and spiders as well
		if (!empty($modSettings['show_spider_online']) && ($modSettings['show_spider_online'] < 3 || allowedTo('admin_forum')) && !empty($modSettings['spider_name_cache']))
		{
			$spidernames = unserialize($modSettings['spider_name_cache']);
		}

		foreach ($guests as $marker)
		{
			if (!empty($marker['id_spider']) && empty($modSettings['show_spider_online']))
			{
				continue;
			}

			$marker['name'] = empty($marker['id_spider']) ? $txt['guest'] : (isset($spidernames[$marker['id_spider']]) ? $spidernames[$marker['id_spider']] : $txt['spider']);
			$markers = '<marker lat="' . round($marker['latitude'], 6) . '" lng="' . round($marker['longitude'], 6) . '" ';
			$markers .= 'label="' . $marker['name'] . '"><![CDATA[' . $marker['name'] . ']]></marker>';

			echo $markers;
		}

		echo '
</markers>';

		// Ok we should be done with output, dump it to user...
		obExit(false);
	}

	/**
	 * geo_buildpins()
	 *
	 * Does the majority of work in determining how the map pin should look based on admin settings
	 *
	 */
	private function _geo_buildpins()
	{
		global $modSettings;

		// Lets work out all those options so this works
		$modSettings['geoIPPinBackground'] = $this->_geo_validate_color('geoIPPinBackground', '66FF66');
		$modSettings['geoIPPinForeground'] = $this->_geo_validate_color('geoIPPinForeground', '202020');

		// What kind of pins have been chosen
		$this->_mpin = $this->_geo_validate_pin('geoIPPinStyle', 'd_map_pin_icon');

		// Shall we add in shadows
		$this->_mshd = (isset($modSettings['geoIPPinShadow']) && $modSettings['geoIPPinShadow']) ? '_withshadow' : '';

		// set the member and cluster pin styles, icon or text
		if ($this->_mpin === 'd_map_pin_icon')
		{
			$this->_mchld = ((isset($modSettings['geoIPPinIcon']) && trim($modSettings['geoIPPinIcon']) != '') ? $modSettings['geoIPPinIcon'] : 'info');
		}
		elseif ($this->_mpin === 'd_map_pin_letter')
		{
			$this->_mchld = (isset($modSettings['geoIPPinText']) && trim($modSettings['geoIPPinText']) != '') ? $modSettings['geoIPPinText'] : '';
		}
		else
		{
			$this->_mpin = 'd_map_pin_letter';
			$this->_mchld = '';
		}

		// Now the colors
		$this->_mchld .= '|' . $modSettings['geoIPPinBackground'] . '|' . $modSettings['geoIPPinForeground'];

		// Build those pins
		$modSettings['npin'] = '?chst=' . $this->_mpin . $this->_mshd . '&chld=' . $this->_mchld;
		if ($this->_mpin === 'd_map_pin_icon')
		{
			$modSettings['mpin'] = '?chst=d_map_pin_icon' . $this->_mshd . '&chld=WCmale|0066FF';
		}
		else
		{
			$modSettings['mpin'] = '?chst=d_map_pin_letter' . $this->_mshd . '&chld=|0066FF|' . $modSettings['geoIPPinForeground'];
		}

		return;
	}

	/**
	 * geo_validate_color()
	 *
	 * Makes sure we have a 6digit hex for the color definitions or sets a default value
	 *
	 * @param string $color
	 * @param string $default
	 *
	 * @return string
	 */
	private function _geo_validate_color($color, $default)
	{
		global $modSettings;

		// no leading #'s please
		if (substr($modSettings[$color], 0, 1) === '#')
		{
			$modSettings[$color] = substr($modSettings[$color], 1);
		}

		// is it a hex
		if (!preg_match('/^[a-f0-9]{6}$/i', $modSettings[$color]))
		{
			$modSettings[$color] = $default;
		}

		return strtoupper($modSettings[$color]);
	}

	/**
	 * geo_validate_pin()
	 *
	 * outputs the correct goggle chart pin type based on selection
	 *
	 * @param string $area
	 * @param string $default
	 *
	 * @return string
	 */
	private function _geo_validate_pin($area, $default)
	{
		global $modSettings;

		if (isset($modSettings[$area]))
		{
			switch ($modSettings[$area])
			{
				case 'plainpin':
					$pin = 'd_map_pin';
					break;
				case 'textpin':
					$pin = 'd_map_pin_letter';
					break;
				case 'iconpin':
					$pin = 'd_map_pin_icon';
					break;
				default:
					$pin = 'd_map_pin_icon';
			}
		}
		else
		{
			$pin = $default;
		}

		return $pin;
	}
}
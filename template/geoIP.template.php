<?php

// GeoIP templates

function template_geoIP()
{
	global $modSettings, $scripturl, $txt;

	if (!empty($modSettings['geoIP_enablemap']))
	{
		echo '
					<div id="geoip">
						<div class="content">
							<table>
								<tr>
									<td>
										<h2 class="category_header">'
											, $txt['geoIP'], '
										</h2>
										<div id="map" style="height: 515px;"></div>
									</td>';

		// Show a right sidebar?
		if ((!empty($modSettings['geoIPSidebar'])) && $modSettings['geoIPSidebar'] == 'right')
		{
			echo '
									<td class="sidebarright">
										<h2 class="category_header">
											', $txt['online_users'], '
										</h2>
										<div id="gooSidebar"></div>
									</td>';
		}

		// Close this table
		echo '
								</tr>
							</table>';

		// Load the scripts so google starts to render this page
		echo '
							<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false" ></script>
							<script type="text/javascript" src="', $scripturl, '?action=geoIP;sa=.js"></script>';

		// Close it up jim
		echo '
						</div>
					</div>';
	}
}

function template_geoIPreg()
{
	global $context, $txt, $settings, $modSettings;

	echo '
		<form action="', $context['post_url'], '" method="post" name="geoIP" id="geoIP" accept-charset="UTF-8" enctype="multipart/form-data">
			<h3 class="category_header">', $txt['geoIPRegistration'], '</h3>
			<div class="windowbg">
				<div class="content">
						<div class="roundframe">
							<dl class="settings">
								<dt>
									<label for="geoIP_cc_block">', $txt['geoIP_cc_block'], '</label>:<br />
									<span class="smalltext">', $txt['geoIP_cc_block_desc'], '</span>
								</dt>
								<dd>
									<input type="checkbox" name="geoIP_cc_block" id="geoIP_cc_block" ', empty($modSettings['geoIP_cc_block']) ? '' : 'checked="checked"', ' />
								</dd>
							</dl>
						</div>';

	// all the countries and the flags ....
	echo '
				<fieldset id="countrycode">
					<legend>', $txt['geoIPCCToUse_select'], '</legend>
						<ul class="reset">';

	// for each column
	foreach ($context['geoCC'] as $cc)
	{
		echo '
							<li class="windowbg">
								<input type="checkbox" name="geoIPCC[]" id="geoIPCC_', $cc['cc'], '" value="', $cc['cc'], '"',  'class="input_check"', ($cc['checked'] ? 'checked="checked"' : '') ,' />
								<label for="geoIPCC_', $cc['cc'], '">
									<img src="' , $settings['default_images_url'] , '/ISO_3166_Flags/' , $cc['cc'] . '.gif"  height="12" width="18" border="0" alt="[ * ]" title="' . $cc['cn'] . '"/>&nbsp;', $cc['cn'], '
								</label>
							</li>';
	}

	echo '				</ul>
				</fieldset>
				<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
				<div class="righttext">
					<input type="submit" class="button_submit" name="save" value="', $txt['save'], '" tabindex="', $context['tabindex']++, '" />
				</div>';

	// Done
	echo '
			</div>
		</div>
		</form>';
}

/**
 * Show the geoIP info for a given IP
 */
function template_geotrackIP_above()
{
	global $settings, $txt, $context;

	if (empty($context['geoIP']['country']))
		return;

	// This shows the geoIP information for this IP.
	echo '
	<h3 class="category_header">', $txt['geoIP_info'], ': ', $context['ip'], '</h3>
	<div class="roundframe">
		<div class="content">',
			$context['geoIP']['city'], (!empty($context['geoIP']['city']) ? '<br />' : ''),
			$context['geoIP']['country'], (!empty($context['geoIP']['country']) ? '<br />' : ''),
			'<img src="' , $settings['default_images_url'] , '/ISO_3166_Flags/' , $context['geoIP']['cc'] . '.gif"  height="12" width="18" border="0" alt="[ * ]" title="' . $context['geoIP']['country'] . '"/>&nbsp;', $context['geoIP']['cc'], '
		</div>
	</div>
	<br />';
}

/**
 * Show the member map button on the whos online list
 */
function template_who_geomap_below()
{
	global $txt, $scripturl;

	// This shows the geoIP information for this IP.
	echo '
		<a class="linkbutton_right" style="margin-top: 4px" href="' . $scripturl . '?action=geoIP">' . $txt['geoIPOnlineMap'] . '</a>';
}
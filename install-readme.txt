[center][size=16pt][b]geoIP Location[/b][/size]
[/center]
[hr]
[color=blue][b][size=12pt][u]Introduction[/u][/size][/b][/color]
This modification adds the ability to use the latitude & longitude of a member given their IP address. This is commonly referred to as geolocation. The mod makes use of the geoip capabilities in Nginx or Apache web servers.  [u][b]You must have this capability enabled in your web server[/b][/u] and they must make the following enviroment variables available to PHP ('GEOIP_LATITUDE', 'GEOIP_LONGITUDE' 'GEOIP_CITY_COUNTRY_NAME', 'GEOIP_CITY', 'GEOIP_CITY_COUNTRY_CODE')

[color=blue][b][size=12pt][u]License[/u][/size][/b][/color]
o This modification is released under a MPL V1.1 license, a copy of it with its provisions is included with the package.

[b]A note on accuracy:[/b]
Maxmind shows that this database (which is updated on a monthly basis) is over 99.5% accurate on a country level making it a viable source for registration blocking.

When it comes to the city level accuracy this number is and 79% for the US (within a 25 mile radius).  That is the best accuracy, and other countries city/region location accuracy tapper off from that.  Even with that level of accuracy, it still makes for an entertaining online member map.

In some instances the IP address will not be found, or will have incomplete data.  In this case the mod will make use of secondary sources to gather information.

[color=blue][b][size=12pt][u]Features[/u][/size][/b][/color]
o Adds an on-line member map which will show a map pin for each IP currently on your forum
o Adds the ability to block or allow member registrations on a per country basis
o Adds in the geoIP information under the track IP sections, allows you to see city / region (state) / country of the IP address
o No source edits
o @todo add country flag to online member list

There are admin settings available with this mod, go to admin - configuration - addon settings - geoIP.

[color=blue][b][size=12pt][u]Installation[/u][/size][/b][/color]
[b][color=red]IMPORTANT NOTES:[/color][/b]
o The package will install on all systems, however you [b][u]must[/u][/b] have geoip capabilities enabled in the web server and the environment variables made available to PHP.  If you are on shared hosting, check with your host, or if you are on a VPS you can read how to do it, for example with Nginx : http://www.howtoforge.com/using-geoip-with-nginx-on-ubuntu-12.04 
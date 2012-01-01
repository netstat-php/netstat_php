<?php

/**
 * netstat.php - Show online status of hosts and services
 *
 * This script is intended to provide a simplified, easily comprehensible
 * and aesthetically pleasing overview of the online status of hosts and
 * services. Checks are done in real-time but they only check whether
 * a port is open (which might be sufficient if your hosts are monitored
 * by full-blown monitoring tools anyway and all you want is a simple
 * interface for e.g. users or clients).
 *
 * Requirements: fsockopen(), for ICMP pings also exec()
 *
 * (License + History: see also end of file)
 *
 * @author     Andreas Schamanek <http://andreas.schamanek.net>
 * @license    GPL <http://www.gnu.org/licenses/gpl.html>
 * @copyright  (c) 2009 Andreas Schamanek
 *
 */
$version = 
'<a href="http://www.fam.tuwien.ac.at/~schamane/sysadmin/netstat/">netstat.php</a> '
. 'by <a href="http://www.fam.tuwien.ac.at/~schamane/">Andreas Schamanek</a> '
. '~ v0.14 ~ 2009-12-05 ~ '
. '<a href="http://www.gnu.org/licenses/gpl.html">GPL licensed</a>';

// below we set up some silly defaults; it is recommended to save your
// own settings in $configfile; if readable it will override our defaults;
// for a list of all configuration variables see http://wox.at/as/_/netstat_php
$configfile = 'netstat.conf.php';

// my network, title and headline of the page
$title = "Our Network Status";
$headline = $title;

// including $configfile if available
if (file_exists($configfile) && is_readable($configfile))
	@include($configfile);

// all configuration variables are set only if not already set
function defaults(&$var, $value) {
	isset($var) || $var = $value;
}

// Report no PHP errors (to be safe we include this very early)
defaults($error_reporting, 0); error_reporting($error_reporting);

// if $alertfile exists the contents will be included()/shown (use HTML!)
defaults($alertfile, 'netstat.txt');

// checks (use pipes (|) with care ;)
//   syntax: host or IP to check | port | description
//     if $port = 'ping' an ICMP ping will be executed
//     if $port = 'headline' $host is printed as a headline
defaults($checks, array(

     'Examples testing localhost |headline',
 'localhost | ping| ICMP ping (ping)',
 'localhost |  80 | WWW server (port 80)',
 '127.0.0.2 | 443 | WWW server (SSL, port 443)',
 '127.0.0.1 |  22 | SSH server (port 22)',
 '127.0.0.3 |  21 | FTP server (port 21)',
 '-------------------------------------------------',
    'Some more examples with errors or not :)|headline',
 'www.hostveryunknown.com| 21|www.hostveryunknown.com',
 'example.com| 23|example.com:23 (<a href="http://en.wikipedia.org/wiki/Telnet">telnet</a> is dead)',
 'Empty and negative ports are ignored||',
 'So are lines without pipe delimiter',
 'www.google.com  |  80 | WWW server @ google.com',
 'localhost       |-ping| Disabled ping',
 'www.example.com | -80 | WWW server @ www.example.com'  // no colon here!

));

// exec command for ping: -l3 (preload) is recommended but
//defaults($ping_command, 'ping -l3 -c3 -w1 -q'); // might not work everywhere
defaults($ping_command, 'ping -c3 -w1 -q');

// fsockopen timeout; might need adjustment depending on network
defaults($timeout, 4);

// show a very simple progress indicator (requires Javascript)
// may be disabled also by adding '?noprogress' to the script's URL
defaults($progressindicator, TRUE);

// strings for online and offline (by default these are used for CSS, too)
defaults($online, 'Online');
defaults($offline, 'Offline');

// print date and/or time (leave empty to show no timestamp)
defaults($datetime, 'l, F j, Y, H:i:s T');

// RSS alert feed
defaults($rssfeed, TRUE); // use to enable or disable RSS feeds
// URL of RSS feed
defaults($rssfeedurl, 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'].'?rss');
// RSS feed title
defaults($rsstitle, "RSS alert feed of $title");
// RSS header e.g. to include in $htmlheader; set to '' to offer no RSS 
defaults($rssheader, '<link rel="alternate" type="application/rss+xml" '."title=\"$rsstitle\" href=\"$rssfeedurl\" />");
// RSS alert link (might point e.g. to your network status homepage)
defaults($rsslink, 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'].'?noprogress');
// RSS date and/or time format (here we use a ISO 8601 format)
defaults($rssdatetime, 'o-m-d H:i:s T');

// HTML/page header
$htmlheader_tmp = <<<EOH
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<title>$title</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-15" />
<meta http-equiv="Refresh" content="399">
<meta name="description" content="Online status of hosts and services provided by netstat.php (c) Andreas Schamanek * http://andreas.schamanek.net" />
$rssheader
<style type="text/css">
<!--
body { font-family: Verdana, "Lucida Sans", Arial, Helvetica, sans-serif; font-size: 87%; }
html>body { font-size: 14px; /* for FF */ }
div#container { width: 37em; margin: 0 auto; position: relative; }
.datetime { font-size: 87%; font-weight: bolder; text-align: center; margin-bottom: 2em; }
.version { font-size: 73%; text-align: center; color: black; background: white; }
.version a { font-weight: bolder; color: black; text-decoration: none; }
h1 { color: #500000; border-bottom: 1px solid #999999; text-align: center; margin-bottom: 1em; margin-top: 2em; }
div#alert { border: 1px solid red; padding: 0.2em 1.5em; margin: 1em 0; }
div#progress { position: fixed; top: 0; left: 0; background: orange; color: black; padding: 0.2em 1em 0.2em 1em; }
.status_table {	border: 1px solid #333333; border-collapse: collapse; width: 100%; }
.status_table td { color: #333333; border: 1px solid #444444; }
.status_table td.headline { font-weight: bolder; background-color: #CFCCCC; padding: 0.4em 0.4em 0.3em 1.5em; }
.hidden { display: none !important; }
.$online { background-color: #D9FFB3; padding-left: 0.8em; }
.$offline { background-color: #FFB6B6; padding-left: 0.8em; }
-->
</style>
</head>

<body>
<div id="container">
EOH;
// end of $htmlheader.tmp
defaults($htmlheader, $htmlheader_tmp);

// HTML/page footer
defaults($htmlfooter, "</div>\n</body>\n</html>");

// ------------------------------------------------- main part of script

// if RSS feed is requested send $alertfile and quit
if ((($_REQUEST['rss'] !== NULL) and ($rssfeed)) or ($argv[1] == 'rss'))
{
	header("Content-Type: application/rss+xml");
	echo "<?xml version=\"1.0\"?><rss version=\"2.0\">\n<channel>\n";
	echo "<title>$rsstitle</title>\n<link>$rsslink</link>\n";
	echo "<description>$rsstitle</description>\n";
	echo "<language>en</language>\n";
	if (file_exists($alertfile) && is_readable($alertfile))
	{
		echo "<item>\n<title>Alert ".date($rssdatetime, filemtime($alertfile))
			. " for $rsstitle</title>\n";
		echo '<pubDate>'.date("r", filemtime($alertfile))."</pubDate>\n";
		echo "<link>$rsslink</link>\n";
		echo '<description><![CDATA[';
		@include($alertfile);
		echo "]]></description>\n</item>\n";
	}
	echo "</channel>\n</rss>\n";
	exit;
} else if ($_REQUEST['rss'] !== NULL) {
	// if RSS was requested even though it was disabled
	exit;
}

// output HTML/page header
echo $htmlheader;

// headline, date and time and start of table
echo "<h1>$headline</h1>\n";
if ($datetime) echo '<p class="datetime">as of ' . date($datetime) . "</p>\n";

// show the contents of $alertfile if it is readable and larger than 2 bytes
if (file_exists($alertfile) && is_readable($alertfile))
{
	clearstatcache();
	if (filesize($alertfile) > 2)
	{
		echo "<div id=\"alert\">\n";
		@include($alertfile);
		echo "</div>\n";
	}
}

// show a simple progress indicator
if (($_REQUEST['noprogress'] !== NULL) || ($argv[1] == 'noprogress'))
{
	$progressindicator = $FALSE;
}
if ($progressindicator)
{
	echo "<div id=\"progress\">Checks in progress ...</div>\n";
}

echo "<table class=\"status_table\" cellpadding=\"4\">\n";

// main loop of checks
foreach ($checks as $check)
{
	$status = $offline;  // default state
	$diagnostics = '';   // mouse-over for tooltips
	$output = TRUE;      // print a line or print no line
	list($host,$port,$description) = explode('|',"$check||"); // the 2 extra '|'s are to avoid notices about undefined offsets
	$host = trim($host);
	$port = trim($port);

	switch ($port)
	{
		case '': // ignore lines with empty port
			$output = FALSE; break;
		case (substr($port,0,1)=='-'): // negative ports, '-ping', and
		    // any "port" starting with '-' is considered a disabled check
			$output = FALSE; break;
		case 'headline': // print a headline within the status table
			// we enclose it with invisible <br>== == for nicer text output
			echo '<tr><td class="headline" colspan="2">'
				. '<span class="hidden">&nbsp;<br />==&nbsp;</span>'
				. $host
				. '<span class="hidden">&nbsp;==</span>'
				. "</td></tr>\n";
			$output = FALSE; break;
		case 'ping': // do an ICMP ping
			unset($pingoutput);
			$ping=exec("$ping_command $host",$pingoutput,$pingreturn);
			if(strlen($ping)>10) 
			{
				// strlen($ping)>10 works around a bug in Debian ping (", pipe 3")
				// http://bugs.debian.org/cgi-bin/bugreport.cgi?bug=456192
				$status = $online; $diagnostics = "$ping :: $pingreturn"; 
			}
			else $diagnostics = "$ping :: $pingreturn";
			// uncomment this if you want the full output as HTML comment
			//echo "\n<!-- "; print_r($pingoutput); echo "-->\n";
			// *nix ping command's return value meanings:
			// 0: all OK; 1: an error occured; 2: host unknown
			break;
		default: // look if a TCP connection to port can be opened
			$time_start = microtime(true);
			$fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
			$time_end = microtime(true);
			$time = number_format(($time_end - $time_start)*1000,1);
  
			if ($fp)
			{
				// fsockopen worked, service is online
				$status = $online;
				$diagnostics = "$time ms";
				fclose($fp);
			}
			else if ($errno<0) { $diagnostics = "errno=$errno; Host unknown?"; }
			else { $diagnostics = $errstr; }
	}
	
	// output results
	if ($output)
	echo "<tr><td>$description</td><td class=\"$status\" title=\"$diagnostics\">$status</td></tr>\n";
	flush();
}

echo "</table>\n";

// make progress indicator disappear by means of Javascript
if ($progressindicator) {
echo <<<EOT
<script>
progressindicator = document.getElementById("progress");
progressindicator.innerHTML = "asdf";
progressindicator.style.visibility = 'hidden';
</script>
EOT;
}

// output $version and HTML/page footer
if (!empty($version)) echo "<p class=\"version\">$version</p>\n";
echo "$htmlfooter\n";

/*
 * License
 *
 * This script is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this script; if not, write to the
 * Free Software Foundation, Inc., 59 Temple Place, Suite 330,
 * Boston, MA  02111-1307  USA
 
 * History
 *
 * 0.09 * 2009-10-26  first buggy "punish me" alpha release candidate
 * 0.10 * 2009-11-07  added stopwatch (time diagnostics) for online services
 * 0.11 * 2009-11-09  added RSS feed option for alerts
 * 0.12 * 2009-11-12  cleaned up and simplified settings mechanism
 * 0.13 * 2009-11-18  $alertfile is only included if larger than 2 bytes
 * 0.14 * 2009-12-05  Default CSS code change to improve font size scaling
 *
 */

?>

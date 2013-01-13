<?php
require_once './Mustache.php';

$msTemplate   = file_get_contents('index.mustache');
$data         = array();
$debug        = false;

if (isset($_GET['debug'])) {
	$debug = true;

	/* debug in the data object is used to make display of debug data sticky across requests */
	$data['debug'] = 'yes';
}

if (file_exists('analytics.txt')) {
	$analyticsCode = file_get_contents('analytics.txt');
	$data['analyticsCode']   = $analyticsCode;

}

$ipnumber = $_SERVER['REMOTE_ADDR'];
if (!empty($_GET['ipnumber'])) {
	$tmpIp = $_GET['ipnumber'];
	// Make sure it's an IP(v4) number...
	if (validateIP($tmpIp) && ip2long($tmpIp)) {
		$ipnumber = $tmpIp;
	} else {
		$ipnumber = null;
	}
} else if ($ipnumber == '::1') {
	// Testing on localhost. Please set $ipnumber to a valid number of your choosing.
	$ipnumber = null;
} else {
	// Do we have an (as of yet unsupported) IPv6 number?
	$ipnumber = null;
}
if ($ipnumber) {
	$data['ipv4Number']       = $ipnumber;
	$data['ipv4singleNumber'] = ip2long($ipnumber);
} else {
	$data['noIP'] = 1;
}

if ($ipnumber) {
	$data['hostname'] = gethostbyaddr($ipnumber);

	$geoData = fetchUrl('http://freegeoip.net/xml/' . $ipnumber);
	$geoXml  = new SimpleXMLElement($geoData);

	$data['country_name'] = $geoXml->CountryName;
	$data['region_name']  = $geoXml->RegionName;
	$data['city_name']    = $geoXml->City;

	$countryCode = $geoXml->CountryCode;
	$countryCode = strtolower($countryCode);

	$data['country_code'] = $countryCode;

	$data['country_flag'] = '//raw.github.com/markjames/famfamfam-flag-icons/master/icons/png/'. $countryCode . '.png';
}

$mustache = new Mustache($msTemplate);
echo $mustache->render($msTemplate, $data, array(), array('charset' => 'UTF8'));

if ($debug) {
	echo "<hr><h2>Template data</h2>\n<pre>";
	var_dump($data);
	echo "</pre>";
}

function validateIP($ip = '') {
	$regexp   = "([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})";
	preg_match($regexp, $ip, $matches);

	if ($matches) {
		return true;
	} else {
		return false;
	}
}

function fetchUrl($url = null) {
	$cUrl = curl_init();
	curl_setopt($cUrl, CURLOPT_URL, $url);
	curl_setopt($cUrl, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt($cUrl, CURLOPT_RETURNTRANSFER, 1);

	$curlData = curl_exec($cUrl);
	curl_close($cUrl);

	return $curlData;
}
?>

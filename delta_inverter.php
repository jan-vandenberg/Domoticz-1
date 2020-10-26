#!/usr/bin/php
<?PHP
//https://mydeltasolar.deltaww.com
// https://mydeltasolar.deltaww.com/includes/process_gtop_plot.php

/**
 * Pass2PHP
 * php version 7.3 (Debian Buster 26-1-2020)
 *
 * @category Home_Automation
 * @package  Pass2PHP FULL script for Delta Inverter
 * @author   Sándor Incze
 * @license  GNU GPLv3
 * @link     https://github.com/sincze/Domoticz
 **/

// $ sudo apt-get update && sudo apt-get upgrade
// $ sudo apt-get install php php-curl php-gd php-imap php-json php- php-cli
// $ cd /home/pi/domoticz/scripts
// $ mkdir pass2php
// $ cd pass2php
// $ Add the downloaded RAW file here
// In Domoticz:
// Create Virtual Sensor -> Electric (instant+Counter)
// Check device idx (Setup-> Devices) for created Virtual Sensor
// Check Setup -> Settings -> Local Networks if '127.0.0.*' is present.
// In delta_inverter.php 
// Modify **** to your username  line 47
// Modify **** to your password  line 48
// Modify DOMOTICZDEVICE **** to your IDX  line 52
// In terminal execute 'php /home/pi/domoticz/scripts/pass2php/delta_inverter.php'
// No errors should be seen, check domoticz created virtual sensor device & log for errors.
// If device is updated continue.
// $ sudo nano /etc/crontab
// add line: */5 * * * *   root    php /home/pi/domoticz/scripts/pass2php/delta_inverter.php >/dev/null 2>&1  

error_reporting(E_ALL);						// 14-04-2019 Pass2PHP 3.0
ini_set("display_errors","on");				// 14-04-2019 Pass2PHP 3.0
date_default_timezone_set('Europe/Amsterdam');
define('time',$_SERVER['REQUEST_TIME']);	// Time is here
define('domoticz','http://127.0.0.1:8080/');

retrieve_Delta_data('test');

function retrieve_Delta_data($command)
{
	define('USERNAME', '****');
	define('PASSWORD', '****');
	define('USER_AGENT', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36');		//Set a user agent. 
	define('COOKIE_FILE','/var/tmp/snapshots/Delta.cookie');													//Where our cookie information will be stored (need to be writable!)
		
	define('DOMOTICZDEVICE', '****');																														
	// URLS
	define('DELTA_LOGIN_URL', 'https://mydeltasolar.deltaww.com/includes/process_login.php');
	define('DELTA_DATA_URL', 'https://mydeltasolar.deltaww.com/includes/process_gtop.php?_=');
		
	// REFERER
	define('LOGIN_REFERER', 'https://mydeltasolar.deltaww.com/index.php?lang=en-us');
	define('DATA_REFERER', 'https://mydeltasolar.deltaww.com/index.php');
	
	// HEADERS
	define('LOGINHEADER',array( 'Accept: application/json, text/javascript, */*; q=0.01',
								'Accept-Encoding: gzip, deflate, br',
								'Accept-Language: nl-NL,nl;q=0.9,en-US;q=0.8,en;q=0.7',
								'X-Requested-With: XMLHttpRequest'
								)
			);


	$continue=true;
	$debug=true;
    header('Content-Type: application/json');

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, DELTA_LOGIN_URL);
	curl_setopt($curl, CURLOPT_POST, true);									
	curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query( postvalues_login() ));	
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);						//We don't want any HTTPS errors.
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);						//We don't want any HTTPS errors.
	curl_setopt($curl, CURLOPT_COOKIEFILE, COOKIE_FILE); 
	curl_setopt($curl, CURLOPT_COOKIEJAR, COOKIE_FILE);						//Where our cookie details are saved.
	curl_setopt($curl, CURLOPT_HTTPHEADER, LOGINHEADER);
	curl_setopt($curl, CURLOPT_USERAGENT, USER_AGENT);	
	curl_setopt($curl, CURLOPT_REFERER, LOGIN_REFERER); 					
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);						
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);						//Do we want to follow any redirects?
	$result=curl_exec($curl);												//Execute the login request.
	//print_r($result);

	if(curl_errno($curl)){
		switch ($http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE)) {
 			case 200:   $continue=true;# OK
 	    				lg('Delta: Login: Expected HTTP code: ', $http_code);
        				break;
			case 302:   $continue=true;# OK
 	    				lg('Delta: Login: Expected HTTP code: ', $http_code);
        				break;        				
        	default:    $continue=false;
        				lg('Delta: Login: Unexpected HTTP code: ', $http_code);
		}
	}

	curl_close($curl);

 	
	if ($debug) {
		if (file_exists(COOKIE_FILE)) lg ('Cookie File: '.COOKIE_FILE.' exists!'); else lg ('Cookie File: '.COOKIE_FILE.' does NOT exist!');	
	}
	
	if (is_writable(COOKIE_FILE)) {
			if ($debug) lg('Cookie File: '.COOKIE_FILE.' is writable!'); 			
			$continue=true; 
	} else { 
			if ($debug) lg('Cookie File: '.COOKIE_FILE.' NOT writable!');
			$continue=false;
	}

	if ($result=='{"errmsg":"","sucmsg":""}')
	{
		lg("Login OK to proceed -> ");
		//echo("Login OK to proceed -> ");
	}
	else 
	{
		//echo("Login NOT OK");
		lg("Login NOT OK");
		$continue=false;
	}

	if ($continue) {

		// Retrieve DATA
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, DELTA_DATA_URL.time());
		curl_setopt($curl, CURLOPT_POST, true);				
		curl_setopt($curl, CURLOPT_HTTPHEADER, LOGINHEADER);
		curl_setopt($curl, CURLOPT_COOKIEJAR, COOKIE_FILE);	
		curl_setopt($curl, CURLOPT_COOKIEFILE, COOKIE_FILE);
		curl_setopt($curl, CURLOPT_REFERER, DATA_REFERER); 
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);	
		curl_setopt($curl, CURLOPT_USERAGENT, USER_AGENT);			//Sets the user agennt		
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);				//We don't want any HTTPS / SSL errors.
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);				//We don't want any HTTPS / SSL errors.
		curl_setopt($curl, CURLOPT_SSLVERSION, 1);
		curl_setopt($curl, CURLOPT_ENCODING, '');	
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);						
		$result = curl_exec($curl);							
//		print_r($result);

		if(curl_errno($curl)){
			switch ($http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE)) {
 				case 200:   $continue=true;# OK
 	    					lg('Growatt inverter: Data: Expected HTTP code: ', $http_code);
        					break;
				case 302:   $continue=true;# OK
 	    					lg('Growatt inverter: Data: Expected HTTP code: ', $http_code);
        					break;        				
        		default:    $continue=false;
        					lg('Growatt inverter: Data: Unexpected HTTP code: ', $http_code);
			}
		}	
		curl_close($curl);

		if ($continue) {

			$data=extract_data($result);
			if ($result!=false) {
				$str=( $data['current_watt'].';'. $data['today_power']*1000 );
				lg('Delta Inverter: '. $data['current_watt'].' for domoticz: '.$str);
				print('Delta Inverter: '. $data['current_watt'].' for domoticz: '.$str);
				ud(DOMOTICZDEVICE,0,$str);	
			}
		}		
	}
}

function postvalues_login()
{
	$postValues = array(
		'email'		=> USERNAME,
		'password'	=> PASSWORD
	);
	return $postValues;
}

function extract_data($queryresult)
{
	$result=false;
	$queryresult = json_decode($queryresult, JSON_PRETTY_PRINT);
//	print_r($queryresult);
	if(isset($queryresult['te']['0']) && isset($queryresult['cur']['0'])) {
		$result = array('today_power' => $queryresult['te']['0'], 'current_watt' => $queryresult['cur']['0']);	
	}
	return $result;
}


function lg($msg)   // Can be used to write loglines to separate file or to internal domoticz log. Check settings.php for value.
{
	curl(domoticz.'json.htm?type=command&param=addlogmessage&message='.urlencode('--->> '.$msg));		
}

function ud($idx,$nvalue,$svalue,$check=false)
{	
	curl(domoticz.'json.htm?type=command&param=udevice&idx='.$idx.'&nvalue='.$nvalue.'&svalue='.$svalue);
	lg(' (udevice) | '.$idx.' => '.$nvalue.','.$svalue);
}

function curl($url){
	$headers=array('Content-Type: application/json');
	$ch=curl_init();
	curl_setopt($ch,CURLOPT_URL,$url);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
 	curl_setopt($ch,CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch,CURLOPT_TIMEOUT, 3);
	curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
	curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
	$data=curl_exec($ch);
	curl_close($ch);
	return $data;
}

?>

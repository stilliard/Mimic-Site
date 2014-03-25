<?php
/**
 * MIMIC SITE 
 * Script to bring in another site into this one. 
 *  and make it all look and run asif the entrie site is here 
 */
session_start();
error_reporting(E_ALL ^ E_NOTICE);
ini_set("display_errors", true);

// inc casche class
include '../cache.class.php'; 

function encodeName($name) {
	
	return md5($name);
}

function isImageType($file_type) {
	
	return (($file_type=='jpg') || ($file_type=='jpeg') || ($file_type=='png') || ($file_type=='gif'));
}
function isOtherCachableFileType($file_type) {
	
	return (($file_type=='css') || ($file_type=='js'));
}

// Current path/url info 
$request = $_SERVER['REQUEST_URI'];
$request_cache_name = encodeName(($request!='') ? $request : 'Home');
// add serialized post values to the end of the cache file, to make sure it doesnt bring up incorrect data 
if(!empty($_POST)) $request_cache_name .= encodeName(serialize($_POST)); 

// The following will get the current url from the 
if($request[0]=='/') $request = substr($request, 1);
$currentFile = $_SERVER["SCRIPT_NAME"];
$parts = explode('/', $currentFile);
$file_name = $parts[count($parts) - 1];
$protocol = $_SERVER['HTTPS'] == 'on' ? 'https' : 'http';
$domain = $protocol . '://' . $_SERVER['HTTP_HOST'] . str_replace('/' . $file_name, '', $currentFile) . '/';

// Get the parts of the url 
$url_parts = parse_url($domain); 
$sub_directory_path = substr($url_parts['path'], 1);

// remove the sub directorys string fromt the request 
$request = str_replace($sub_directory_path, '', $request);

// the image url, is really the url without any url (GET) params 
$image_url = explode('?', $request);
$image_url = $image_url[0];

if(($image_url=='') || !stristr($image_url,'.')) $file_type = 'html';
else $file_type = end(explode(".", stristr(str_replace(".php", '', $image_url),'.') ? str_replace(".php", '', $image_url) : $image_url )); 

// Re cache time 
$re_cache_time = 0;//720; // minutes to re-cache after 

$file_name = '';
$file = 'cache/'.urlencode($request_cache_name).'.'.($file_type ? $file_type : 'cache' );

// Set headers 
if(isImageType($file_type)) {
	
	if($file_type=='jpg') $file_type = 'jpeg';
	header("Content-type: image/$file_type");
	
	$cache = new CacheOutput($file, $re_cache_time, false); 
}
else {
	if($file_type!='php') header("Content-type: text/$file_type");
	else header("Content-type: text/html");
	
	// check cached credentials and reprocess accordingly
	header("cache-control: must-revalidate");
	// set variable for duration of cached content
	$offset = 60 * 60;
	// set variable specifying format of expiration header
	$expire = "expires: " . gmdate ("D, d M Y H:i:s", time() + $offset) . " GMT";
	// send cache expiration header to the client broswer
	header($expire);
	
	if(isOtherCachableFileType($file_type)) $cache = new CacheOutput($file, $re_cache_time, true); 
}


// Check now, if this site has been cached in the past day, in which case use that instead. 
if ( $cache && is_object($cache) && !$cache->needToReCache() ) {
	
	echo $cache->getFile();
	
} else { // not yet chaced, generate and save 

	$xml = simplexml_load_file('../settings.xml');
	
	// Config 
	$site 			= (string) $xml->main->url; // the site you want to mimic (with trailing slash!)
	$this_site 		= (string) $xml->main->this_url; // this site (with trailing slash!)
	$default_page 	= (string) $xml->main->default_page;//"index.html"; // Default landing page 
	
	if($request=='') $request = $default_page;
	
	$end_url = $site . $request;
	$image_url = $site . $image_url;
	
	// If this is an image...
	if(isImageType($file_type)) {
		
		ob_start();
		
		$image_url = str_replace(' ', '%20', $image_url);
			
		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Set curl to return the data instead of printing it to the browser.
		curl_setopt($ch, CURLOPT_URL, $image_url);
		$data = curl_exec($ch);
		curl_close($ch); 
		
		echo $data;
		
		// Save current content to a new file 
		$cache->writeFile();
		exit();
	}
	
	// Begin object buffering
	if(!ob_start("ob_gzhandler")) ob_start();
	
	// Replace Array (any keywords you want to replace) (Replaces from source code so be carefull with links to pages and images) 
	// Tip #1: Add spaces before or after words to help make sure the words dont appear in links 
	// Tip #2: UPPERCASE/lowercase will matter! 
	$additionalreplace = array();
	$replaces = $xml->replaces->replace;
	$tempvars = array('{site}'=>$site, '{request}'=>$request);
	foreach($replaces as $replace) {
		
		$key = (string) str_replace(array_keys($tempvars), array_values($tempvars), $replace->a);
		$val = (string) str_replace(array_keys($tempvars), array_values($tempvars), $replace->b);
		
		$additionalreplace[$key] = $val;
	}
	
	// Redirects (If a redirect is found, auto foward them
	$redirects = array();
	$reds = $xml->redirects->redirect;
	foreach($reds as $r) {
		$redirects[] = (string) $r;
	}
	
	// Base replaces, for images src's and links href etc (folders etc)
	$basereplace = array(	
				
		// replace any occurences of the old site url with the new one		
		$site => $this_site, 
		// the following allows the site to work on a sub domain of another 
		"src=\"/" => "src=\"".$this_site,
		"src='/" => "src='".$this_site,
		"href=\"/" => "href=\"".$this_site,
		"href='/" => "href='".$this_site, 
		"url(\"/" => "url(\"".$this_site, 
		"url('/" => "url('".$this_site, 
		"url(/" => "url(".$this_site
	); 
	
	foreach($redirects as $goto) {
		$gotoNostars = str_replace('*', '', $goto);
		if($goto[0]=='*' && $request==$gotoNostars) header("Location: {$site}{$goto}"); 
		elseif(stristr($request, $gotoNostars)) 	header("Location: {$site}{$goto}"); 
	}
	
	// Function to get content 
	function file_get_contents_curl($url, $site_referer) {	
		
		$user_agent = ($_SERVER['HTTP_USER_AGENT']!='') 
			? $_SERVER['HTTP_USER_AGENT'] : 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.15) Gecko/20080623 Firefox/2.0.0.15';
		
		$header[] = "Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
		$header[] = "Cache-Control: max-age=0";
		$header[] = "Connection: keep-alive";
		$header[] = "Keep-Alive: 300";
		$header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
		$header[] = "Accept-Language: en-us,en;q=0.5";
		$header[] = "Expect: "; 
		$header[] = "Pragma: "; // browsers keep this blank.
		
		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		#curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
		#curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_REFERER, $site_referer);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 120); 
		
		if(!empty($_POST)) { 
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $_POST);
		}
		
		curl_setopt($ch, CURLOPT_COOKIE, session_name().'='.session_id()); 
		curl_setopt($ch, CURLOPT_COOKIEJAR, realpath('../tmp/cookie.txt'));
		curl_setopt($ch, CURLOPT_COOKIEFILE, realpath('../tmp/cookie.txt'));
		
		$data = curl_exec($ch); 
		curl_close($ch); 
		return $data; 
	}
	
	// Get content 
	$file = file_get_contents_curl($end_url, $this_site);
	
	// Replace some values 
	$replace = array_merge($basereplace, $additionalreplace); 
	$file = str_replace(array_keys($replace), array_values($replace), $file); 
	
	// finaly print/echo out the webpage 
	echo $file;
	
	// Save current content to a new file 
	if ( $cache && is_object($cache) ) $cache->writeFile();
	exit();
}


?>

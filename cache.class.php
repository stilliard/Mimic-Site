<?php
/**
 * Cache Output Class File 
 * 
 * Input a file name/location to cache to, 
 * and this file handles prity much all the rest, 
 */
class CacheOutput {
	
	public $filename;
	public $timeperiod;
	public $content;
	public $useFileGetContents;
	public $cacheResetCode = 'true';
	
	public function __construct($filename, $timeperiod=60, $useFileGetContents=true) { 
	
		$this->filename = $filename;
		$this->timeperiod = $timeperiod;
		$this->useFileGetContents = $useFileGetContents;
	}
	
	public function needToReCache() { 
		
		if(isset($_GET['force_cache']) && ($_GET['force_cache']==$this->cacheResetCode)) return true;
		if(!file_exists($this->filename)) return true;
		
		$time = time();
		$filemtime = filemtime($this->filename);
		$timeperiod = ($this->timeperiod * 60);
		
		if(($time - $filemtime) >= $timeperiod) return true;
		
		return false;
	}
	
	public function beginOutput() { 
		ob_start();
	}
	
	public function getFile() { 
		
		if($this->useFileGetContents) {
			$content = file_get_contents($this->filename);
			return $content;
		}
		else readfile($this->filename);
		#else include($this->filename);
	}
	
	public function writeFile() { 
		#return file_put_contents($this->filename, $this->content);
		#@flush();
		$content = ob_get_contents();
		ob_end_flush();
		$fp = fopen($this->filename, 'w'); 
		fwrite($fp, $content); 
		fclose($fp); 
		#echo "\n<!-- ". ("Last modified " . date("l, dS F, Y @ h:ia", filemtime())) ." -->";
	}
}
?>
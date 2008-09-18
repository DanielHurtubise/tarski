<?php

/**
 * class TarskiVersion
 * 
 * The Version class is the foundation of Tarski's update notifier.
 * A TarskiVersion object can have several properties: the current theme
 * version (i.e. the currently installed version), the latest theme
 * version (i.e. the most recently released version, as specified by
 * the Tarski version feed {@link http://tarskitheme.com/version.atom}),
 * the link to the release post on the Tarski website of the latest
 * version, and the version status, i.e. whether the currently
 * installed version equal to the latest version, and hence whether
 * the theme is in need of updating.
 * @package Tarski
 * @since 2.0
 */
class TarskiVersion {
	
	/**
	 * The version number of the currently installed theme.
	 * @var string
	 */
	var $current;
	
	/**
	 * The version number of the latest Tarski release.
	 * @var string
	 */
	var $latest;
	
	/**
	 * Link to the latest Tarski release post.
	 * @var string
	 */
	var $latest_link;
	
	/**
	 * Summary text of the latest Tarski release.
	 * @var string
	 */
	var $latest_summary;
	
	/**
	 * The status of the currently installed version.
	 * @var string
	 */
	var $status;
	
	/**
	 * current_version_number() - Returns current version number.
	 * 
	 * @since 2.0
	 */
	function current_version_number() {
		$themedata = get_theme_data(TEMPLATEPATH . '/style.css');
		$installed_version = trim($themedata['Version']);
		if(strlen($installed_version) < 1) {
			$this->current = 'unknown';
		} else {
			$this->current = $installed_version;
		}
	}
	
	/**
	 * version_feed_data() - Returns latest version feed data.
	 * 
	 * @link http://tarskitheme.com/version.atom
	 * @since 2.0
	 * @return string $atomdata
	 */
	function version_feed_data() {
		ob_start();
		
		// Thanks to Simon Willison for the inspiration
		$cachefile = TARSKICACHE . "/version.atom";
		$cachetime = 60 * 60;

		// Serve from the cache if it is younger than $cachetime
		if(file_exists($cachefile) && (time() - $cachetime < filemtime($cachefile)) && file_get_contents($cachefile)) {
			$atomdata = file_get_contents($cachefile);
		} else {
			$atomdata = wp_remote_get(TARSKIVERSIONFILE);
			
			if(!empty($atomdata) && cache_is_writable("version.atom")) {
				$fp = fopen($cachefile, "w");
				if($fp) {
					fwrite($fp, $atomdata);
					fclose($fp);
				}
			}
		}
		
		return $atomdata;
		
		$atomdata = ob_get_contents();
		ob_end_clean();

		return $atomdata;
	}
	
	/**
	 * latest_version_number() - Returns latest version number.
	 * 
	 * @since 2.0
	 * @return string
	 */
	function latest_version_number() {
		if(preg_match('/<entry>.*?<title>(.+?)<\/title>.*?<\/entry>/is', TarskiVersion::version_feed_data(), $matches)) {
			$this->latest = wp_specialchars($matches[1]);
		}
	}
	
	/**
	 * latest_version_link() - Returns link to latest version release post.
	 * 
	 * The link should be the release post on the Tarski website
	 * for the latest version of Tarski, which will include a link
	 * to download the .zip file of that latest version.
 	 * @since 2.0
	 * @return string
	 */
	function latest_version_link() {
		if(preg_match('/<entry>.*?<id>(.+?)<\/id>.*?<\/entry>/is', TarskiVersion::version_feed_data(), $matches)) {
			$this->latest_link = wp_specialchars($matches[1]);
		}
	}
	
	/**
	 * latest_version_summary() - Returns the summary text of the the latest version release post.
	 * 
	 * @since 2.4
	 * @return string
	 */
	function latest_version_summary() {
		if(preg_match('/<entry>.*?<summary>(.+?)<\/summary>.*?<\/entry>/is', TarskiVersion::version_feed_data(), $matches)) {
			$this->latest_summary = wp_specialchars($matches[1]);
		}
	}
	
	/**
	 * version_status() - Returns the status of the current version.
	 * 
	 * This lets Tarski know whether there is a connection to the version
	 * feed {@link http://tarskitheme.com/version.atom} and if so, whether
	 * the current version is equal to the latest version.
	 * @since 2.0
	 * @return string
	 */
	function version_status() {
		$this->current_version_number();
		$this->latest_version_number();
		
		$status = version_compare($this->latest, $this->current);
		
		if ($this->latest) {
			if ($status === 0) {
				$this->status = 'current';
			} elseif ($status === 1) {
				$this->status = 'older';
			} elseif ($status === -1) {
				$this->status = 'newer';
			} else {
				$this->status = 'error';
			}
		} else {
			$this->status = 'no_connection';
		}
	}
	
}

?>
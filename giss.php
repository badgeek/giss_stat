<?

	/*
		Giss(is not a)TV
		Stats parser *2009
		iyok@deadmediafm.org
	*/

	class GissStats
	{
		//variables
	    var $mp_mountpoint;
	    var $mp_info;
	    var $mp_is_onair = false;
	    
	    var $icecast_file	= 'icecast.html';
	    var $icecast_url	= 'http://giss.tv:8000/';
	
		var $cache_file		= './giss_cache.cache';
		var $cache_age		= 2;
	    
	    var $is_parsed;
	    var $is_debug 	 = false;

		function GissStats()
		{
			$this->is_parsed = false;
		}

		function setGissMountpoint($mountpoint)
		{
			$this->mp_mountpoint = $mountpoint;
			$this->cache_file = './' . 'GISS' . md5($mountpoint) . '.cache';
		}

		function setGissStatsUrl($url)
		{
			$this->icecast_file = $url;
		}


		function doParse()
		{
			if (file_exists($this->cache_file))
			{
				if ($this->_file_age_in_minutes($this->cache_file) > $this->cache_age)
				{
					$this->_doRealParse();
					//echo "real parse";
				}else{
					$this->_read_cache();
					//echo "using cache";
				}
			}else{
				$this->_doRealParse();
			}
		}


		function _doRealParse()
		{
			$icecast_res	= fopen($this->icecast_file, 'r');
			
			if (!$this->is_debug)
			{
				$icecast_html	= $this->_getUrl($this->icecast_url);
			}else{
				$icecast_html	= fread($icecast_res, filesize($this->icecast_file));
			}
		
			//parse current mountpoint
			$mp_parse_top_offset = strpos($icecast_html, 'Mount Point ' . $this->mp_mountpoint );
			
			
			if($mp_parse_top_offset > 0){
			
				$mp_parse_bottom_offset 	= strpos($icecast_html, '</tr></table></div><div class="roundbottom">', $mp_parse_top_offset);
				$mp_parse_length			= $mp_parse_bottom_offset - $mp_parse_top_offset;
				$mp_html 					= substr($icecast_html,$mp_parse_top_offset,$mp_parse_length);
				$mp_parse_regex_streamdata 	= '|<td>(?P<type>.*):</td><td class=\"streamdata\">(?P<data>.*)</td>|U';
				preg_match_all($mp_parse_regex_streamdata, $mp_html, $matches);

				//debug print
				
				if ($this->is_debug)
				{
					print_r($matches);
				}
				
				$this->mp_info["OnAir"] =  true;
				
				$x=0;
				foreach ($matches['type'] as $type)
				{
					//echo $x;
					$this->mp_info[$type] =  $matches['data'][$x];
					$x++;
				}
				
				$this->is_parsed 	= true;
				
				$this->_write_cache($this->mp_info);
				
			}else{
				
				$this->mp_is_onair = false;
				
			}
			
			//print_r($this->mp_info);
		}
		
		
		// INTERNAL FUNCTION
		
		function _getUrl($url, $return = true)
		{
			//if curl then
		
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL,$url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, $return);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				
				if ($return = true)
				{
						return curl_exec($ch);
				}else{
						curl_exec($ch);
				}

				curl_close($ch);	
			
			//if no curl then
			
			//return html
		}
		
		
		function _file_age_in_minutes($file_name)
		{
			$time_file_born = filemtime($file_name);
			$current_time = time();
			return ($current_time - $time_file_born)/60; 
		}
		
		function _write_cache($mp_info_array)
		{
			$cache_data 		= serialize($mp_info_array);
			$cache_file_handle	= fopen($this->cache_file,'w');
			$success = fwrite($cache_file_handle, $cache_data);
			return $success;
		}
		
		function _read_cache()
		{
			$cache_file_handle	= fopen($this->cache_file,'r');
			$cache_data = fread($cache_file_handle, filesize($this->cache_file));
			//echo $cache_data;
			$this->mp_info = unserialize($cache_data);
			$this->mp_info['cache'] = 1;
			//print_r($this->mp_info);
			//return $mp_info_array;
		}		
		
		/*
			[Stream Title] => My station description
		    [Stream Description] => audio/mpeg
		    [Content Type] => Sun, 27 Sep 2009 02:21:10 +0200
		    [Mount Start] => 16
		    [Bitrate] => 0
		    [Current Listeners] => 1
		    [Peak Listeners] => Various
		    [Stream Genre] => <a target="_blank" href="http://www.audiorealm.com">http://www.audiorealm.com</a>
		    [Stream URL] => 
		    [Current Song] =>
		
		*/
		
		function getIsOnAir()
		{
			if ($this->is_parsed == false) {
				$this->doParse();
			}
			return $this->mp_info['OnAir'];
		}
		
		function getMpInfo($type)
		{
			if ($this->is_parsed == false) {
				$this->doParse();
			}
			return $this->mp_info[$type];
		}
		
		function setDebug($debug)
		{
			$this->is_debug = $debug;
		}
		
		function debugDump()
		{
			if ($this->is_parsed == false) {
				$this->doParse();
			}
			print_r($this->mp_info);
		}
		
	}
	
?>

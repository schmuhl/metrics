<?php

/**
 * Class MetricRecording
 * @since
 * @copyright Ben Schmuhl
 * @version
 * @package Metrics
 */
class MetricRecording {

	var $recorded;
	var $value;
	var $metricID;
	
	
	
	/** Set the members of this object by an array
	 *@param array info the array to set from (like a mysql_fetch_array or _POST)
	 */
	function setByArray ( $info ) {
		$vars = get_object_vars($this);
		foreach ( $vars as $var=>$val ) {
			if ( isset($info[$var]) ) $this->$var = $info[$var];
		}
	}
	
	
	/**
	 * Save a new metric recording
	 * @return boolean 
	 */
	public function save ( $frequency = null ) {
		if ( !is_numeric($this->value) || !is_numeric($this->metricID) ) return false;
		if ( !is_numeric($this->recorded) ) $this->recorded = strtotime($this->recorded);
		if ( $this->recorded == 0 ) return false;  // don't record no date
		// there are limits to what we will save
        if ( $frequency == 'monthly' ) $sql = "DELETE FROM metricRecordings WHERE metricID=$this->metricID AND year(recorded) = '".date("Y",$this->recorded)."' AND month(recorded) = '".date("m",$this->recorded)."';";
        else if ( $frequency == 'daily' ) $sql = "DELETE FROM metricRecordings WHERE metricID=$this->metricID AND date(recorded) = '".date("Y-m-d",$this->recorded)."';";
		else if ( $frequency == 'hourly' ) $sql = "DELETE FROM metricRecordings WHERE metricID=$this->metricID AND date(recorded) = '".date("Y-m-d",$this->recorded)."' AND hour(recorded) = '".date("H",$this->recorded)."';";
		else if ( $frequency == 'minutely' ) $sql = "DELETE FROM metricRecordings WHERE metricID=$this->metricID AND date(recorded) = '".date("Y-m-d",$this->recorded)."' AND hour(recorded) = '".date("H",$this->recorded)."' AND minute(recorded) = '".date("i",$this->recorded)."';";
		else $sql = null;
		if ( !empty($sql) ) {
            $result = dbQuery($sql);  //echo $sql;
            if ( !$result ) return false;
        }
		
		// record the metric
		$sql = "INSERT INTO metricRecordings (recorded,value,metricID) VALUES ('".date("Y-m-d H:i:s",$this->recorded)."',$this->value,$this->metricID);";
		//echo $sql;
		$result = dbQuery($sql);
		if ( !$result ) return false;
		
		return true;
	}
	
	
	/**
	 * Delete a record with the specified values, but just one
	 * @return boolean Success
	 */
	public function delete () {
		//print_r($this);
		if ( !is_numeric($this->metricID) || !is_numeric($this->value) ) return false;
		if ( !is_numeric($this->recorded) ) $this->recorded = strtotime($this->recorded);
		$sql = "DELETE FROM metricRecordings WHERE metricID=$this->metricID AND value=$this->value AND recorded='".date("Y-m-d G:i:s",$this->recorded)."' LIMIT 1;";
		//echo $sql;
		$result = dbQuery($sql);// or die(mysql_error()." $sql");
		if ( !$result ) return false;
		if ( mysql_affected_rows() == 1 ) return true;
		return false;
	}
	
	
	/**
	 * Get the recordings for a given metric
	 * @param int $metricID
	 * @param int $count The number of recent recordings to get
     * @param string $frequency Daily, hourly, etc.
	 * @return array An array of MetricRecording objects 
	 */
	public static function getRecordings ( $metricID, $count=null, $frequency=null ) {
		if ( !is_numeric($metricID) ) return null;
        if ( isset($frequency) && !in_array($frequency, Metric::$frequencies)) return null;  // must be a valid frequency

        $sql = "SELECT value, sum(value) as v, recorded FROM metricRecordings WHERE metricID=$metricID";
        if ( $frequency == 'monthly' ) {
            $sql .= ' GROUP BY year(recorded), month(recorded)';
        } else if ( $frequency == 'daily' ) {
            $sql .= ' GROUP BY date(recorded)';
        } else if ( $frequency == 'hourly' ) {
            $sql .= ' GROUP BY date(recorded), hour(recorded)';
        } else if ( $frequency == 'minutely' ) {
            $sql .= ' GROUP BY date(recorded), hour(recorded), minute(recorded)';
        }
        $sql .= ' ORDER BY recorded DESC';
		if ( is_numeric($count) ) $sql .= " LIMIT $count;";
        //die($sql);
		$result = dbQuery($sql);
		$recordings = array();
		while ( $row = $result->fetch_array() ) {
			$recording = new MetricRecording ();
			$recording->recorded = $row['recorded'];
            $recording->value = $row['v'];
            //$recording->value = $row['value'];
			$recordings []= $recording;
		}
		return $recordings;
	}


	public static function sortByDate ( $a, $b ) {
	    return $a->recorded > $b->recorded;
	}
}
?>
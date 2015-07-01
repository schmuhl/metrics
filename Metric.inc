<?php
/* 
 * The Metric object
 * @copyright Ben Schmuhl
 * @since
 * @version
 * @package Metrics
 */

require 'MetricRecording.inc';


class Metric {
	
	var $metricID;
	var $name;
	var $description;
	var $type;
	var $frequency;
	var $allowZero;
	
	var $recordings;

    var $precision = 3;
	static $types = array('decimal','integer','percentage');
	static $frequencies = array('monthly','daily','hourly','minutely');
	
	
	/**
	 * Create a metric
	 * @param type $metricID
	 * @return type 
	 */
	public function __construct ( $metricID=null ) {
		$this->recordings = array();
		if ( !is_numeric($metricID) ) return;
		$result = dbQuery("SELECT * FROM metrics WHERE metricID = $metricID;");
		if ( dbRows($result) < 1 ) return;
		$row = dbFetch($result);
		$this->setByArray($row);
	}
	
	
	/** Set the members of this object by an array
	 *@param array info the array to set from (like a mysql_fetch_array or _POST)
	 */
	function setByArray ( $info ) {
		$vars = get_object_vars($this);
		foreach ( $vars as $var=>$val ) {
		    if ( $var == "allowZero" ) $this->$var = ( isset($info[$var]) && $info[$var] ) ? TRUE : FALSE;  // special handling
		    else if ( isset($info[$var]) ) $this->$var = $info[$var];
		}
	}
	
	
	/**
	 * Save a new Metric, or update a new one
	 * @return boolean 
	 */
	public function save () {
		$name = addslashes($this->name);
		$description = addslashes($this->description);
		$type = ( in_array($this->type,Metric::$types) ) ? "'$this->type'" : 'NULL';
		$frequency = ( in_array($this->frequency,Metric::$frequencies) ) ? "'$this->frequency'" : 'NULL';
		$allowZero = ( isset($this->allowZero) && ( $this->allowZero || $this->allowZero == "TRUE" || $this->allowZero == "true" ) ) ? 1 : 0;
		if ( is_numeric($this->metricID) ) $sql = "UPDATE metrics SET name='$name', description='$description', type=$type, frequency=$frequency, allowZero=$allowZero WHERE metricID=$this->metricID;";
		else $sql = "INSERT INTO metrics (name,description,type,frequency,allowZero) VALUES ('$name','$description',$type,$frequency,$allowZero);";
		$result = dbQuery($sql);
		$this->metridID = dbInsertID();
		//addMessage($sql);
		if ( !$result ) return false;
		return true;
	}
	
	
	/**
	 * Get the recordings for this given metric
     * @param int $count The number or results to return, or null for all
     * @param string $frequency The frequency to report on, or null for default
	 * @return array An array of MetricRecording objects 
	 */
	public function getRecordings ( $count=null, $frequency=null ) {
        if ( isset($frequency) && !in_array($frequency,Metric::$frequencies) ) return null;  // illegal frequency
        if ( !isset($frequency) ) $frequency = $this->frequency; // default to the metric's frequency
		$this->recordings = MetricRecording::getRecordings($this->metricID,$count,$frequency);
		usort($this->recordings,"MetricRecording::sortByDate");
		return $this->recordings;
	}
	
	
	/**
	 * Just grab the most recent recording 
	 */
	public function getLastRecording () {
		if ( count($this->recordings) < 1 ) $this->getRecordings();
		//print_r($this);
		if ( isset($this->recordings[count($this->recordings)-1]) ) return $this->recordings[count($this->recordings)-1];
		else return null;
	}
	
	
	/**
	 * Make a new recording
	 * @param mixed $date
	 * @param float $value 
	 */
	public function saveRecording ( $date, $value ) {
		$recording = new MetricRecording();
		$value = preg_replace("~[\%\,]{1,}~","",$value);  // clean illegal characters
		$recording->value = (float)$value;
		if ( !$this->allowZero && 0 == $recording->value ) return null;
		$recording->recorded = $date;
		$recording->metricID = $this->metricID;
		if ( $recording->save($this->frequency) ) $this->recordings []= $recording;
		return $recording;
	}
	
	
	/**
	 * Delete a single recording with the spencifed data
	 * @param mixed $date
	 * @param float $value
	 * @return boolean success 
	 */
	public function deleteRecording ( $date, $value ) {
		$recording = new MetricRecording();
		$recording->metricID = $this->metricID;
		$recording->recorded = $date;
		$recording->value = $value;
		return $recording->delete();
	}
	
	
	/**
	 * Format the date in a way that will make sense, given this metric's frequency
	 * @param mixed $date
	 * @return string 
	 */
	public function toDate ( $date, $showFullAnyway=false ) {
		if ( !is_numeric($date) ) $date = strtotime($date);
		if ( !$showFullAnyway && $this->frequency == 'daily' ) return date("n/d/Y",$date);
		if ( !$showFullAnyway && $this->frequency == 'hourly' ) return date("n/d ga",$date);
		else return date("n/d/Y g:ia",$date);
	}
	
	
	/** 
	 * Show the value, given the type of metric
	 * @param float $value
	 * @return string 
	 */
	public function value ( $value=null, $justNumber=false ) {
        if ( !isset($value) ) $value = $this->value;
        if ( $this->type == 'integer' ) return round($value);
		if ( $this->type == 'percentage' ) {
            if ( isset($this->precision) ) {
                if ( $justNumber ) return number_format($value,$this->precision);
                else return number_format($value,$this->precision).'%';
            } else {
                if ( $justNumber ) return $value;
                else return $value."%";
            }
        }
        else if ( isset($this->precision) ) return number_format($value,$this->precision);
		else if ( $this->type == 'decimal' ) {
		    if ( $value < 0.1 ) return number_format($value,4);
		    if ( $value < 1 ) return number_format($value,3);
		    else return number_format($value,2);
		}
		else return number_format($value);
	}
	
	/**
	 * Get all of the metrics
	 * @return array An array of Metric objects
	 */
	public static function getAllMetrics () {
		$metrics = array();
		$result = dbQuery("SELECT * FROM metrics ORDER BY name;");// or die(mysql_error());
		if ( !$result ) {
			Metric::createTable();
			return array();
		}
		while ( $row = $result->fetch_assoc() ) {
			$metric = new Metric ();
			$metric->setByArray($row);
			$metrics []= $metric;
		}
		return $metrics;
	}
	
	
	
	/**
	 * Create the needed table
	 * @param boolean $dropFirst Drop the table (reset) before trying to create
	 * @return boolean 
	 */
	public static function createTable ( $dropFirst = false ) {
		if ( $dropFirst ) {
			dbQuery("DROP TABLE metrics;");// or die(mysql_error());
			dbQuery("DROP TABLE metricRecordings;");// or die(mysql_error());
		}
		$sql = "CREATE TABLE IF NOT EXISTS metrics ( 
			metricID INT PRIMARY KEY AUTO_INCREMENT,
			name VARCHAR(50),
			description VARCHAR(255),
			allowZero BOOLEAN,
			type ENUM(";
		foreach ( Metric::$types as $type ) $sql .= "'$type',";
		$sql .= "'other'),
			frequency ENUM(";
		foreach ( Metric::$frequencies as $frequency ) $sql .= "'$frequency',";
		$sql .= "'other') );";
		$result = dbQuery($sql);// or die(mysql_error());
		$sql = "CREATE TABLE IF NOT EXISTS metricRecordings ( 
			metricID INT,
			recorded DATETIME,
			value FLOAT
		);";
		$result = dbQuery($sql);// or die(mysql_error());
		if ( !$result ) return false;
		return true;
	}
}


?>

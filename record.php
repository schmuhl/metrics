<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

error_reporting(0);



require "includes/template.inc";



if ( isset($_GET['metric']) ) $metric = new Metric ( $_GET['metric'] );
if ( !isset($metric) || !is_numeric($metric->metricID) ) {
	die('{ "code": 1, "message": "Sorry, the specified metric does not exist." }');
}

if ( !isset($_GET['date']) || !isset($_GET['value']) ) die('{ "code": 2, "message": "Sorry, you must specify a timestamp and a value to be recorded." }');

if ( isset($_GET['action']) && $_GET['action'] == 'delete' ) {  // delete?
	if ( $metric->deleteRecording($_GET['date'],$_GET['value']) ) die('{ "code": 0, "message": "The specified metric recording was deleted." }');
	else die('{ "code": 4, "message": "Sorry, we could not remove the specified record data." }');
} else {  // or save ?

	$date = ( is_numeric($_GET['date']) ) ? $_GET['date'] : strtotime($_GET['date']);
	//if ( $date == 0 || date("N",$date) >= 6 || date("H",$date) > 17 || date("H",$date) < 8 ) // don't record on no date or a weekend or after work hours
	//	die('{ "code": 5, "message": "Sorry, metrics are only recorded during business hours.  You asked to record something for '.date("g:ia n/d/Y",$date).'" }');

	$recording = $metric->saveRecording($_GET['date'],$_GET['value']);
	if ( !$recording ) die('{ "code": 3, "message": "Sorry, we could not record the provided data." }');
}

?>
{
	"code": 0,
	"message": "The new value '<?php echo $recording->value; ?>' was recorded for metric '<?php echo $metric->name; ?>' on <?php echo date("n/d/Y g:ia",$recording->recorded); ?>."
}

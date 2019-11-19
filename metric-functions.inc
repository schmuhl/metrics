<?php


// Create a new metric
if ( isset($_GET['metric']) && 'new' == $_GET['metric'] ) {
	$metric = new Metric ();
	$metric->name = "New metric";
	if ( $metric->save() ) addMessage('New metric created successfully.');
	else addMessage("Sorry, we could not create a new metric.");
	$_GET['metric'] = $metric->metricID;
}


// which metric are we working with here?
if ( isset($_POST['metricID']) ) $metric = new Metric ( $_POST['metricID'] );
else if ( isset($_GET['metric']) ) $metric = new Metric ( $_GET['metric'] );
if ( !isset($metric) || !is_numeric($metric->metricID) ) {
	addMessage('Sorry, the metric you asked for does not exist.');
	header('Location: index.php');
	exit();
}


// hande an edit form submit
if ( isset($_POST['metricID']) ) {
	$metric->setByArray($_POST);
	if ( $metric->save() ) addMessage("Metric '$metric->name' has been updated.");
	else addMessage("Sorry, metric '$metric->name' could not be updated.");
	header("Location: metric.php?metric=$metric->metricID");
	exit();
}


// what frequency to report on?
if ( isset($_GET['frequency']) && in_array($_GET['frequency'],Metric::$frequencies) ) {
    $frequency = $_GET['frequency'];
} else $frequency = $metric->frequency;
$frequency="daily";


// what timeframes to look at?
$from = isset($_GET['from']) ? $_GET['from'] : ( isset($_SESSION['from']) ? $_SESSION['from'] : null );
if ( isset($from) ) {
    if ( is_numeric($from) ) $from = date('Y-m-d H:i:s',$from);
    else $from = date('Y-m-d H:i:s',strtotime($from));
    $_SESSION['from'] = $from;
}
$to = isset($_GET['to']) ? $_GET['to'] : ( isset($_SESSION['to']) ? $_SESSION['to'] : null );
if ( isset($to) ) {
    if ( is_numeric($to) ) $to = date('Y-m-d H:i:s',$to);
    else $to = date('Y-m-d H:i:s',strtotime($to));
    $_SESSION['to'] = $to;
}


// Are we comparing two time frames?
$compare = isset($_GET['compare']) ? $_GET['compare'] : ( isset($_SESSION['compare']) ? $_SESSION['compare'] : null );
if ( isset($compare) ) $_SESSION['compare'] = $compare;


// what to show?
if ( isset($_GET['show']) ) {
    if ( $_GET['show'] == 'graph' ) {
        $showHeading = false;
        $showRecordings = false;
    } else if ( $_GET['show'] == 'json' ) {
        $metric->getRecordings(null,$frequency);
        header('Content-Type: application/json');
        print_r(json_encode($metric));
        exit(0);
    } else if ( $_GET['show'] == 'csv' ) {
        $metric->getRecordings(null,$frequency);
        if ( $metric->allowZero ) {
            if ( $frequency == 'daily' ) {
                // find the start and end times
                $start = null;
                $end = null;
                $recordings = array();
                foreach ( $metric->recordings as $recording ) {
                    if ( !isset($start) || $recording->recorded < $start ) $start = $recording->recorded;
                    if ( !isset($end) || $recording->recorded > $end ) $end = $recording->recorded;
                    $recordings[date("Y-m-d",strtotime($recording->recorded))] = $recording;
                }
                // loop through the times and make sure that there is a recording for each day
                $i = date("Y-m-d",strtotime($start));
                while ( $i < $end ) {
                    if ( !isset($recordings[$i]) ) {
                        $emptyRecording = new MetricRecording();
                        $emptyRecording->value = 0;
                        $emptyRecording->recorded = $i;
                        $recordings[$i] = $emptyRecording;
                        //echo "Added a recording on $i<br/>";
                    }
                    $i = date("Y-m-d", strtotime($i) + 86401);  // move to tomorrow
                }
            }
        }
        usort($recordings,'MetricRecording::sortByDate');
        //die("<pre>".print_r($recordings,true)."</pre>");
        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename=$metric->name.csv");
        echo "\"Date\",\"$metric->name\"\n";
        foreach ( $recordings as $recording ) echo "\"$recording->recorded\",\"".$metric->value($recording->value)."\"\n";
        exit(0);
    }
} else {
    $showHeading = true;
    $showRecordings = true;
}


// Get all of the needed recordings
if ( isset($compare) ) {
    $from2 = date('Y-m-d H:i:s',strtotime("$from $compare"));
    $to2 = date('Y-m-d H:i:s',strtotime("$to $compare"));
    //echo "$from $compare = from is $from2 and to is $to2";
    if ( $from2 != $from && $to2 != $to ) $recordings2 = $metric->getRecordings(null,$frequency,$from2,$to2);  // get the comparison date range
    $metric->getRecordings(null,$frequency,$from,$to);  // get the date range

    if ( count($metric->recordings) < count($recordings2) ) {  // more data points in the comparison, swap them
        $temp = $metric->recordings;
        $metric->recordings = $recordings2;
        $recordings2 = $temp;
        $temp = null;
    }
} else {
    $count = ( isset($from) && isset($to) ) ? null : 50;  // default to 50
    $metric->getRecordings(50,$frequency,$from,$to);
}
//print_r($metric);

?>

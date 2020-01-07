<?php


require "template.inc";

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

    if ( isset($recordings2) && count($metric->recordings) < count($recordings2) ) {  // more data points in the comparison, swap them
        $temp = $metric->recordings;
        $metric->recordings = $recordings2;
        $recordings2 = $temp;
        $temp = null;
    }
} else {
    $count = ( isset($from) && isset($to) ) ? null : 50;  // default to 50
    $metric->getRecordings(50,$frequency,$from,$to);
		//print_r($metric->recordings);
		//die('poo');
}
//print_r($metric);


/*
$count = 200;
$hourly = $metric->getRecordings($count,'hourly');
$daily = $metric->getRecordings($count,'daily');
?>
<table>
    <?php for ( $i=0; $i<count($hourly); $i++ ) {
        $hour = $hourly[$i];
        $day = isset($daily[$i]) ? $daily[$i] : new MetricRecording();
        ?>
    <tr>
        <td><?php echo $hour->recorded; ?></td>
        <td><?php echo $hour->value; ?></td>
        <td></td>
        <td><?php echo $day->recorded; ?></td>
        <td><?php echo $day->value; ?></td>
    </tr>
        <?php
    } ?>
</table>

<?php
die();

*/


// the width and height may be specified for the chart, grab it
$chartWidth = ( isset($_GET['width']) ) ? $_GET['width'] : 1000;
$chartHeight = ( isset($_GET['height']) ) ? $_GET['height'] : 400;



if ( $showHeading ) showHeader("Metrics: $metric->name");
?>

<?php if ( $showHeading ) { ?>
<div id="breadcrumb">
    <a href="index.php"><< all metrics</a>
</div>
<?php } ?>

<?php if ( count($metric->recordings) > 0 ) { ?>
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript">
	google.load("visualization", "1", {packages:["corechart"]});
	google.setOnLoadCallback(drawChart);
	function drawChart() {
	var data = google.visualization.arrayToDataTable([
		['Date', 'Value' <?php if ( isset($recordings2) ) echo ", 'Comparison'"; ?> ],
        <?php $max = isset($recordings2) ? max(count($recordings2),count($metric->recordings)) : count($metric->recordings); // how many data points to show ?>
		<?php for ( $i = 0; $i < $max; $i++ ) {
		     $recording = $metric->recordings[$i];
		     ?>
		['<?php echo $metric->toDate($recording->recorded); ?>',  <?php echo $metric->value($recording->value,true); ?> <?php
			if ( isset($recordings2) ) {
				echo ", ";
				if ( isset($recordings2[$i]) ) {
					echo $metric->value($recordings2[$i]->value,true);
				} else {
					echo '0';
				}
			}
			?>],
		<?php } ?>
	]);

	var options = {
		title: '<?php echo $metric->name; ?>',
        <?php if ( !isset($recordings2) ) { ?>'legend':'none',<?php } ?>
		animation: {
			duration: 1000,
			easing: 'out'
		}//,
		//curveType: 'function',
		//pointSize: 5
	};

	var chart = new google.visualization.AreaChart(document.getElementById('chart_div'));
	chart.draw(data, options);
	}
</script>
<?php } ?>

<?php if ( $showHeading ) { ?>

<div id="metricLegend">
    <form action="metric.php">
        From: <input type="text" name="from" value="<?php echo $from; ?>" />
        To: <input type="text" name="to" value="<?php echo $to; ?>" />
        <input type="submit" value="Set" />
        <input type="hidden" name="metric" value="<?php echo $metric->metricID; ?>" />
        <input type="hidden" name="compare" value="<?php echo $compare; ?>" />
        <input type="hidden" name="frequency" value="<?php echo $frequency; ?>" />
    </form>
    <!--Frequency:
    <a href="metric.php?metric=<?php echo $metric->metricID; ?>&frequency=hourly">hourly</a>
    <a href="metric.php?metric=<?php echo $metric->metricID; ?>&frequency=daily">daily</a>
    <br/>-->
    (beta) Compare to last:
    <a href="metric.php?metric=<?php echo $metric->metricID; ?>&frequency=daily&compare=-1year">year</a>
    <a href="metric.php?metric=<?php echo $metric->metricID; ?>&frequency=daily&compare=-4months">trimester</a>
    <a href="metric.php?metric=<?php echo $metric->metricID; ?>&frequency=daily&compare=-1month">month</a>
    <a href="metric.php?metric=<?php echo $metric->metricID; ?>&frequency=daily&compare=-7days">week</a>
    <a href="metric.php?metric=<?php echo $metric->metricID; ?>&frequency=daily&compare=">NONE</a>
    <br/>
    View as:
    <a href="metric.php?metric=<?php echo $metric->metricID; ?>&frequency=<?php echo $frequency; ?>&show=csv">CSV/Excel</a>
    <!-- <a href="metric.php?metric=<?php echo $metric->metricID; ?>&frequency=<?php echo $frequency; ?>">HTML</a> -->
    <a href="metric.php?metric=<?php echo $metric->metricID; ?>&frequency=<?php echo $frequency; ?>&show=json">JSON</a>
    <a href="metric.php?metric=<?php echo $metric->metricID; ?>&frequency=<?php echo $frequency; ?>&show=graph">Chart</a>
</div>

<h1><?php echo $metric->name; ?></h1>
<p>
	<?php echo $metric->description; ?>
	(Recorded <?php echo ($metric->frequency); ?> as <?php echo ($metric->type); ?>)
    <?php if ($_SESSION['admin']) { ?>
	<img src="images/metricEdit.png" alt="Edit metric" title="Edit metric" onclick="$('#editMetricForm').show()" style="cursor: pointer;" />
    <?php } ?>
</p>
<div class="stats">
    <?php
    // Calculate the statistics on this set of numbers
    $total = 0;
    $count = 0;
    $values = array();
    foreach ( $metric->recordings as $recording ) {
        $total += $recording->value;
        $count ++;
        $values []= $recording->value;
    }
    $average = ($count>0)?$total/$count:null;
    $stdDev = standard_deviation($values);
    $moe = margin_of_error($stdDev,$count);
    ?>
    Average: <?php echo $metric->value($average); ?>
    Standard Deviation: <?php echo $metric->value($stdDev,true); ?>
    Margin of Error: &plusmn;<?php echo $metric->value($moe,true); ?> or <?php echo $metric->value($average-$moe); ?> to <?php echo $metric->value($average+$moe); ?>
</div>
<div>
	View as:
	<a href="hero.php?metric=<?php echo $metric->metricID; ?>">Wallboard</a>
	| <a href="barometer.php?metric=<?php echo $metric->metricID; ?>">Barometer</a>
</div>


<form action="metric.php" method="post" style="display: none;" id="editMetricForm">
	<h2>Edit Metric</h2>
	<div>
		<label for="newMetricName">Name</label>
		<input type="text" id="newMetricName" name="name" value="<?php echo $metric->name; ?>" />
	</div>
	<div>
		<label for="newMetricDescription">Description</label>
		<textarea id="newMetricDescription" name="description"><?php echo $metric->description; ?></textarea>
	</div>
	<div>
		<label for="newMetricType">Type</label>
		<select id="newMetricName" name="type">
			<?php foreach ( Metric::$types as $type ) { ?>
			<option value="<?php echo $type; ?>" <?php if ( $metric->type == $type ) echo 'selected'; ?>><?php echo ucfirst($type); ?></option>
			<?php } ?>
		</select>
	</div>
	<div>
		<label for="newMetricFrequency">Frequency</label>
		<select id="newMetricFrequency" name="frequency">
			<?php foreach ( Metric::$frequencies as $frequency ) { ?>
			<option value="<?php echo $frequency; ?>" <?php if ( $metric->frequency == $frequency ) echo 'selected'; ?>><?php echo ucfirst($frequency); ?></option>
			<?php } ?>
		</select>
	</div>
	<div>
		<label for="newMetricAllowZero">Allow Zero</label>
		<input type="checkbox" name="allowZero" id="newMetricAllowZero" value="TRUE" <?php if ( $metric->allowZero ) echo ' checked="checked"'; ?> />
	</div>
	<div>
		<input type="submit" value="Save" />
		<input type="button" value="Cancel" onclick="$('#editMetricForm').hide()" />
		<input type="hidden" name="metricID" value="<?php echo $metric->metricID; ?>" />
	</div>
</form>

<script>
	function saveRecording ( metricID, recorded, value ) {
		//alert('record.php?metric='+metricID+"&date="+recorded+"&value="+value);
		$.getJSON('record.php?metric='+metricID+"&date="+recorded+"&value="+value, function(data) {
			//alert(data.message);
		});
		return true;
	}

	function deleteRecording  ( metricID, recorded, value ) {
		$.getJSON('record.php?action=delete&metric='+metricID+"&date="+recorded+"&value="+value, function(data) {
			//alert(data.message);
		});
		return true;
	}
	setTimeout('location.reload(true);',<?php if ( $metric->frequency == 'hourly' ) echo 60*60*1000; else if ( $metric->frequency == 'minutely' ) echo 60*1000; else echo '60*60*24*1000'; ?>)
</script>
<?php }  // if showHeading ?>


<?php if ( count($metric->recordings) > 0 ) { ?>
<style>#chart_div { margin: 30px auto 0 auto; width: <?php echo $width; ?>px; height: <?php echo $height; ?>px; }</style>
<div id="chart_div" style=""></div>
<?php } ?>


<?php if ( $showRecordings ) { ?>
<h2>Data</h2>
<table>
	<tr>
		<th>Recorded</th>
		<th>Value</th>
        <?php if ( isset($recordings2) ) { ?>
        <td> &nbsp; </td>
        <th>Comparison</th>
        <th>Value</th>
        <?php } ?>
	</tr>
	<?php
	$i = 0;
    $max = isset($recordings2) ? max(count($recordings2),count($metric->recordings)) : count($metric->recordings); // how many data points to show?
    for ( $i = 0; $i < $max; $i++ ) {
        $recording = isset($metric->recordings[$i]) ? $metric->recordings[$i] : null; ?>
	<tr id="recording<?php echo $i; ?>">
        <?php if ( isset($recording) ) { ?>
		<td><?php echo date("n/d/Y g:ia",strtotime($recording->recorded)); ?></td>
		<td align="right" id="recording<?php echo $i; ?>ValueColumn">
			<span id="recording<?php echo $i; ?>Value"><?php echo $metric->value($recording->value); ?></span>
            <?php if ($_SESSION['admin']) { ?>
			<img src="images/recordingEdit.png" alt="Edit recording" title="Edit recording" style="cursor: pointer;" onclick="$('#recording<?php echo $i; ?>Editable').show(); $('#recording<?php echo $i; ?>ValueColumn').hide();" />
            <?php } ?>
		</td>
		<td id="recording<?php echo $i; ?>Editable" style="display: none;">
			<input id="recording<?php echo $i; ?>NewValue" type="text" value="<?php echo $metric->value($recording->value); ?>" />
			<input type="button" value="Save" onclick="deleteRecording('<?php echo $metric->metricID; ?>','<?php echo strtotime($recording->recorded); ?>','<?php echo $metric->value($recording->value); ?>'); if ( saveRecording('<?php echo $metric->metricID; ?>','<?php echo strtotime($recording->recorded); ?>',$('#recording<?php echo $i; ?>NewValue').val()) ) { $('#recording<?php echo $i; ?>Value').html($('#recording<?php echo $i; ?>NewValue').val()); $('#recording<?php echo $i; ?>Editable').hide(); $('#recording<?php echo $i; ?>ValueColumn').show(); }" />
			<input type="button" value="Delete" onclick="if ( confirm('Are you sure that you want to delete this?') && deleteRecording('<?php echo $metric->metricID; ?>','<?php echo strtotime($recording->recorded); ?>','<?php echo $metric->value($recording->value); ?>') ) $('#recording<?php echo $i; ?>').hide();" />
		</td>
        <?php } else { ?>
        <td></td>
        <td></td>
        <?php } ?>


        <?php if ( isset($recordings2[$i]) ) { ?>
            <td> &nbsp; </td>
            <td><?php echo date("n/d/Y g:ia",strtotime($recordings2[$i]->recorded)); ?></td>
            <td align="right"><?php echo $metric->value($recordings2[$i]->value); ?></td>
        <?php } ?>
	</tr>
	<?php } ?>
</table>
<?php  } // if showRecordings ?>



<?php if ( $showHeading ) showFooter(); ?>

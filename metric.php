<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


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
        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename=$metric->name.csv");
        echo "\"Date\",\"$metric->name\"\n";
        foreach ( $metric->recordings as $recording ) echo "\"$recording->recorded\",\"$recording->value\"\n";
        exit(0);
    }
} else {
    $showHeading = true;
    $showRecordings = true;
}


// get the recordings
$metric->getRecordings(50,$frequency);
//print_r($metric);


if ( $showHeading ) showHeader("Metrics: $metric->name");
?>

<?php if ( count($metric->recordings) > 0 ) { ?>
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript">
	google.load("visualization", "1", {packages:["corechart"]});
	google.setOnLoadCallback(drawChart);
	function drawChart() {
	var data = google.visualization.arrayToDataTable([
		['Date', 'Value'],
		<?php foreach ( $metric->recordings as $recording ) { ?>
		['<?php echo $metric->toDate($recording->recorded); ?>',  <?php echo $recording->value; ?>],
		<?php } ?>
	]);

	var options = {
		title: '<?php echo $metric->name; ?>',
		animation: {
			duration: 1000,
			easing: 'out'
		},
		curveType: 'function',
		pointSize: 5
	};

	var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
	chart.draw(data, options);
	}
</script>
<?php } ?>
    
<?php if ( $showHeading ) { ?>
<h1><?php echo $metric->name; ?></h1>
<p>
	<?php echo $metric->description; ?> 
	(<?php echo ucwords($metric->type); ?>, <?php echo ucwords($metric->frequency); ?>)
	<img src="images/metricEdit.png" alt="Edit metric" title="Edit metric" onclick="$('#editMetricForm').show()" style="cursor: pointer;" />
</p>


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
<div id="chart_div" style="width: 900px; height: 400px;"></div>
<?php } ?>


<?php if ( $showRecordings ) { ?>
<h2>Recordings</h2>
<table>
	<tr>
		<th>Recorded</th>
		<th>Value</th>
	</tr>
	<?php 
	$i = 0;
	foreach ( $metric->recordings as $recording ) { ?>
	<tr id="recording<?php echo ++$i; ?>">
		<td><?php echo date("n/d/Y g:ia",strtotime($recording->recorded)); ?></td>
		<td align="right" id="recording<?php echo $i; ?>ValueColumn">
			<span id="recording<?php echo $i; ?>Value"><?php echo $metric->value($recording->value); ?></span>
			<img src="images/recordingEdit.png" alt="Edit recording" titl="Edit recording" style="cursor: pointer;" onclick="$('#recording<?php echo $i; ?>Editable').show(); $('#recording<?php echo $i; ?>ValueColumn').hide();" />
		</td>
		<td id="recording<?php echo $i; ?>Editable" style="display: none;">
			<input id="recording<?php echo $i; ?>NewValue" type="text" value="<?php echo $recording->value; ?>" />
			<input type="button" value="Save" onclick="deleteRecording('<?php echo $metric->metricID; ?>','<?php echo strtotime($recording->recorded); ?>','<?php echo $recording->value; ?>'); if ( saveRecording('<?php echo $metric->metricID; ?>','<?php echo strtotime($recording->recorded); ?>',$('#recording<?php echo $i; ?>NewValue').val()) ) { $('#recording<?php echo $i; ?>Value').html($('#recording<?php echo $i; ?>NewValue').val()); $('#recording<?php echo $i; ?>Editable').hide(); $('#recording<?php echo $i; ?>ValueColumn').show(); }" />
			<input type="button" value="Delete" onclick="if ( confirm('Are you sure that you want to delete this?') && deleteRecording('<?php echo $metric->metricID; ?>','<?php echo strtotime($recording->recorded); ?>','<?php echo $recording->value; ?>') ) $('#recording<?php echo $i; ?>').hide();" />
		</td>
	</tr>
	<?php } ?>
</table>
<?php  } // if showRecordings ?>



<?php if ( $showHeading ) showFooter(); ?>
<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


require "template.inc";

$metrics = Metric::getAllMetrics();
//print_r($metrics);
?>

<h1>Metrics</h1>
<table>
	<tr>
		<th colspan="2">Metric</th>
		<th>Value</th>
		<th>Since</th>
	</tr>
	<?php foreach ( $metrics as $metric ) { 
		$recording = $metric->getLastRecording();
		$recorded = @strtotime($recording->recorded);
		if ( $recorded > time()-(60*60*23) ) {
		    $status = "current";
		    $statusDescription = "This metric is being recorded properly";
		} else if ( $recorded > time()-(60*60*24*3) ) {
		    $status = "delayed";
		    $statusDescription = "This metric has not been recorded recently";
		} else if ( $recorded > time()-(60*60*24*7) ) {
		    $status = "warning";
		    $statusDescription = "This metric has not been recorded recently";
		} else {
		    $status = "error";
		    @$statusDescription = "This metrics is no longer being recorded - $recording->recorded";
		}
		?>
	<tr>
	    <td><img src="images/<?php echo $status; ?>.png" alt="<?php echo $statusDescription; ?>" title="<?php echo $statusDescription; ?>" /></td>
		<td><a href="metric.php?metric=<?php echo $metric->metricID; ?>"><?php echo $metric->name; ?></a></td>
		<td align="right"><?php if ( $recording instanceOf MetricRecording ) echo $metric->value($recording->value); ?></td>
		<td><?php if ( $recording instanceOf MetricRecording ) echo $metric->toDate($recording->recorded,true); ?></td>
	</tr>
	<?php } ?>
</table>

<p>
    <a href="metric.php?metric=new"><img src="images/add.png" style="margin-right: 5px;"/>New metric...</a>
</p>


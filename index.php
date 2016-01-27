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
      <th>Last Recorded</th>
      <th>Average</th>
      <th>MOE</th>
	</tr>
	<?php foreach ( $metrics as $metric ) {
        if ( empty($metric->name) ) continue;
		$recording = $metric->getLastRecording();
        $metric->getRecordings(100);
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

       // Calculate the statistics on this set of numbers
       $total = 0;
       $count = 0;
       $values = array();
       foreach ( $metric->recordings as $recording ) {
          $total += $recording->value;
          $count ++;
          $values []= $recording->value;
       }
       $average = $total/$count;
       $stdDev = standard_deviation($values);
       $moe = margin_of_error($stdDev,$count);
		?>
	<tr>
	    <td><img src="images/<?php echo $status; ?>.png" alt="<?php echo $statusDescription; ?>" title="<?php echo $statusDescription; ?>" /></td>
		<td><a href="metric.php?metric=<?php echo $metric->metricID; ?>"><?php echo $metric->name; ?></a></td>
		<td align="right"><?php if ( $recording instanceOf MetricRecording ) echo $metric->value($recording->value); ?></td>
       <td><?php if ( $recording instanceOf MetricRecording ) echo $metric->toDate($recording->recorded,true); ?></td>
       <td><?php if ( $recording instanceOf MetricRecording ) echo $metric->value($average); ?></td>
       <td><?php if ( $recording instanceOf MetricRecording ) echo '&plusmn;'.$metric->value($moe,true); ?></td>
	</tr>
	<?php } ?>
</table>

<p>
    <a href="metric.php?metric=new"><img src="images/add.png" style="margin-right: 5px;"/>New metric...</a>
</p>


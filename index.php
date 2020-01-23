<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


require "includes/template.inc";
$metrics = Metric::getAllMetrics();
//print_r($metrics);
$lastMonth = date('Y-m',strtotime('last month'));
$thisMonth = date('Y-m');



showHeader($config->name);
?>

<h1>Metrics</h1>
<table>
	<tr>
      <th colspan="2">Metric</th>
      <th>Last Value</th>
      <th>Last Recorded</th>
      <th>Average</th>
      <th>MOE</th>
			<th><?php echo $lastMonth; ?></th>
			<th><?php echo $thisMonth; ?></th>
	</tr>
	<?php foreach ( $metrics as $metric ) {
    if ( empty($metric->name) ) continue;
		$recording = $metric->getLastRecording();
        $metric->getRecordings(1000);
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
			 $lastMonths = array();
			 $thisMonths = array();
       foreach ( $metric->recordings as $recording ) {
          $total += $recording->value;
          $count ++;
          $values []= $recording->value;
					if ( date('Y-m',strtotime($recording->recorded)) == $lastMonth ) $lastMonths []= $recording->value;
					if ( date('Y-m',strtotime($recording->recorded)) == $thisMonth ) $thisMonths []= $recording->value;
       }
			 //print_r($thisMonths); die();
       $average = ($count==0) ? 0 : $total/$count;
       $stdDev = standard_deviation($values);
       $moe = margin_of_error($stdDev,$count);
		?>
	<tr>
	    <td><img src="images/<?php echo $status; ?>.png" alt="<?php echo $statusDescription; ?>" title="<?php echo $statusDescription; ?>" /></td>
			<td><a href="metric.php?metric=<?php echo $metric->metricID; ?>"><?php echo $metric->name; ?></a></td>
			<td align="right"><?php if ( $recording instanceOf MetricRecording ) echo $metric->value($recording->value); ?></td>
       <td align="right"><?php if ( $recording instanceOf MetricRecording ) echo $metric->toDate($recording->recorded,true); ?></td>
       <td align="right"><?php if ( $recording instanceOf MetricRecording ) echo $metric->value($average); ?></td>
       <td align="right"><?php if ( $recording instanceOf MetricRecording ) echo '&plusmn;'.$metric->value($moe,true); ?></td>
			 <td align="right"><?php echo @$metric->value(array_sum($lastMonths)/count($lastMonths)); ?></td>
			 <td align="right"><?php echo @$metric->value(array_sum($thisMonths)/count($thisMonths)); ?></td>
	</tr>
	<?php } ?>
</table>


<?php if ( $_SESSION['admin'] ) { ?>
<p>
    <a href="metric.php?metric=new"><img src="images/add.png" style="margin-right: 5px;"/>New metric...</a>
</p>
<?php } ?>


<?php showFooter(); ?>

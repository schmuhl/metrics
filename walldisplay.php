<?php


require "template.inc";

$metrics = array();


if ( isset($_GET['metrics']) ) {
    $_GET['metrics'] = explode(',',$_GET['metrics']);
    foreach ( $_GET['metrics'] as $m ) {
        //if ( isset($metric[$m]) ) continue;  // no duplicates
        $metrics[$m] = new Metric ( $m );
        if ( !isset($metrics[$m]->metricID) ) unset($metrics[$m]);
    }
}
if ( count($metrics) == 0 ) die('Please specify up to four valid metrics to display (e.g. http://example.com/walldisplay.php?metrics=1,2,3).');



?>

<style>
    iframe { margin: 10px 0 0 10px; border: 0; padding: 0; width: 500px; height: 300px; }
</style>

<div style="margin: 0 auto; width: 1030px; text-align: center;">

    <?php
    foreach ( $metrics as $metric ) { ?>
        <iframe src="metric.php?metric=<?php echo $metric->metricID; ?>&show=graph&width=500&height=500"></iframe>
    <?php } ?>

</div>
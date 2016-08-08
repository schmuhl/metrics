<?php
/**
 * Show a hero-shot of a given metric, comparing it to last year
 * @package metrics
 * @author Ben Schmuhl
 * @since 201-08-05
 */


require "template.inc";

// load the given metric and related info
require 'metric-functions.inc';
//print_r($metric);

// Grab since the last Fall semester
$metric->getRecordings(null,'daily',"2015-07-01","now");

// get this year's and the last year comparison
$r1 = array();
$r2 = array();
$runningTotal = 0;
foreach ( $metric->recordings as $recording ) {
  $date = date("Y-m",strtotime($recording->recorded));  
  if ( $recording->recorded >= '2015-08-01' && $recording->recorded < '2016-01-01' ) $r1[]= $recording;
  else if ( $recording->recorded >= '2016-08-01' ) {
      $r2[]= $recording;
      $runningTotal += $recording->value;
  }
}
if ( count($r2) == 0 ) {  // what? nothing in this year?
    $value = number_format($r1[count($r1)-1]->value);
    $caption = date("F jS",strtotime($r1[count($r1)-1]->recorded));
  unset($r2);
} else {  // this is the expected case
    $value = number_format($r2[count($r2)-1]->value);
    $caption = date("F jS",strtotime($r2[count($r2)-1]->recorded));
}
$runningTotal = number_format($runningTotal);



if ( $showHeading ) showHeader($metric->name);
?>


<style>
  DIV#body { width: 100%; height: 100%; }
  DIV#body HEADER { width: auto; }
  DIV#body DIV.content { width: 100%; }

  DIV.hero { margin: 0 -20px; padding: 0; width: 100%; display: block; overflow: hidden; }
  DIV.hero H1 { margin: 0 0 20px 0; padding: 0; font-size: 80px; text-align: center; line-height: 120px; }
  DIV.hero DIV.today { float: left; margin: 50px 0 0 20px; border: 1px solid gray; width: 460px; height: 400px; text-align: center; }
  DIV.hero DIV.today DIV.value { height: 300px; font-size: 170px; line-height: 300px; }
  DIV.hero DIV.today DIV.caption { hight: 100px; background: gray; color: white; font-size: 60px; line-height: 100px; }
  DIV.hero DIV#chart_div { float: right; margin: -10px 0 0 0 !important; padding: 0 !important; width: 800px !important; height: 500px !important; over1flow: hidden; }
  DIV.hero DIV.total { clear: both; margin: 0 0 15px 0; color: #dc3912; border: 0px solid #dc3912; background: #f5c4b8; font-size: 60px; line-height: 90px; text-align: center;  }
</style>


<div class="hero">

  <h1><?php echo $metric->name; ?></h1>

  <div class="today">
    <div class="value"><?php echo $value; ?></div>
    <div class="caption"><?php echo $caption; ?></div>
  </div>

  <div id="chart_div"></div>

  <div class="total"><?php echo $runningTotal; ?> and counting</div>

</div>



<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript">
  google.load("visualization", "1", {packages:["corechart"]});
  google.setOnLoadCallback(drawChart);
  function drawChart() {
  var data = google.visualization.arrayToDataTable([
    ['Date', 'Last Year' <?php if ( isset($r2) ) echo ", 'This Year'"; ?> ],
        <?php $max = isset($r2) ? max(count($r2),count($r1)) : count($r1); // how many data points to show ?>
    <?php for ( $i = 0; $i < $max; $i++ ) { ?>
    ['<?php echo date("j-M",strtotime($r1[$i]->recorded)); ?>',  <?php echo $metric->value($r1[$i]->value,true); ?> <?php if ( isset($r2) ) echo ", ".$metric->value($r2[$i]->value,true); ?>],
    <?php } ?>
  ]);

  var options = {
    title: '<?php echo $metric->name; ?>',
    <?php if ( !isset($r2) ) { ?>'legend':'none',<?php } ?>
    height: 500,
    animation: {
      duration: 1000,
      easing: 'out'
    }
  };

  var chart = new google.visualization.AreaChart(document.getElementById('chart_div'));
  chart.draw(data, options);
  }
</script>


<?php if ( $showHeading ) showFooter(); ?>

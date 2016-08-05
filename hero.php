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
$value = number_format($metric->recordings[0]->value);
$caption = date("F jS",strtotime($metric->recordings[0]->recorded));

// get this year's and the last year comparison
$r1 = array();
$r2 = array();
foreach ( $metric->recordings as $recording ) {
  $date = date("Y-m",strtotime($recording->recorded));
  if ( $recording->recorded >= '2015-07-15' && $recording->recorded < '2016-01-01' ) $r1[]= $recording;
  else if ( $recording->recorded >= '2016-07-15' ) $r2[]= $recording;
}
if ( count($r2) == 0 ) unset($r2);


if ( $showHeading ) showHeader($metric->name);
?>


<style>
  DIV#body { width: 100%; height: 100%; }
  DIV#body HEADER { width: auto; }
  DIV#body DIV.content { width: 100%; }

  DIV.hero { width: 100%; display: block; overflow: hidden; }
  DIV.hero H1 { font-size: 100px; text-align: center; }
  DIV.hero DIV.today { float: left; margin: 50px 0; border: 1px solid gray; width: 460px; height: 400px; text-align: center; }
  DIV.hero DIV.today DIV.value { height: 300px; font-size: 170px; line-height: 300px; }
  DIV.hero DIV.today DIV.caption { hight: 100px; background: gray; color: white; font-size: 60px; line-height: 100px; }
  DIV.hero DIV#chart_div { float: right; display: block; margin: 30px auto 0 auto; width: 800px !important; height: 400px !important; overflow: hidden; }
</style>


<div class="hero">

  <h1><?php echo $metric->name; ?></h1>

  <div class="today">
    <div class="value"><?php echo $value; ?></div>
    <div class="caption"><?php echo $caption; ?></div>
  </div>

  <div id="chart_div"></div>

</div>



<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript">
  google.load("visualization", "1", {packages:["corechart"]});
  google.setOnLoadCallback(drawChart);
  function drawChart() {
  var data = google.visualization.arrayToDataTable([
    ['Date', 'Value' <?php if ( isset($recordings2) ) echo ", 'Comparison'"; ?> ],
        <?php $max = isset($r2) ? max(count($r2),count($r1)) : count($r1); // how many data points to show ?>
    <?php for ( $i = 0; $i < $max; $i++ ) { ?>
    ['<?php echo date("j-M",strtotime($r1[$i]->recorded)); ?>',  <?php echo $metric->value($r1[$i]->value,true); ?> <?php if ( isset($r2) ) echo ", ".$metric->value($r2[$i]->value,true); ?>],
    <?php } ?>
  ]);

  var options = {
    title: '<?php echo $metric->name; ?>',
        <?php if ( !isset($recordings2) ) { ?>'legend':'none',<?php } ?>
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

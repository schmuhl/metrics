<?php
/**
 * Show a special barometer view
 * @package metrics
 * @author Ben Schmuhl
 * @since 2020-01-04
 */


require "template.inc";

// load the given metric and related info
require 'metric-functions.inc';
//print_r($metric);

// Grab since the last Fall semester
$metric->getRecordings(null,$metric->frequency,"-7days","tomorrow");
//print_r($metric);
$min = null;
$max = null;
$sum = 0;
foreach ( $metric->recordings as $recording ) {
  if ( $recording->value < $min || !isset($min) ) $min = $recording->value;
  if ( $recording->value > $max || !isset($max) ) $max = $recording->value;
  $sum += $recording->value;
}
$average = $sum/count($metric->recordings);

$pressure = $metric->getLastRecording();
//print_r($pressure);
$pressure=$pressure->value;




if ( $showHeading ) showHeader($metric->name);
?>


<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
   <script type="text/javascript">
      google.charts.load('current', {'packages':['gauge']});
      google.charts.setOnLoadCallback(drawChart);

      function drawChart() {

        var data = google.visualization.arrayToDataTable([
          ['Label', 'Value'],
          ['hPA', <?php echo $metric->value($pressure); ?>]
        ]);

        var options = {
          //animation.easing: 'inAndOut',
          width: 400, height: 400,
          min: <?php echo $min; ?>, max: <?php echo $max; ?>,
          yellowFrom:  <?php echo $min; ?>, yellowTo: <?php echo ($min+$average)/2; ?>,
          greenFrom: <?php echo ($min+$average)/2; ?>, greenTo: <?php echo ($max+$average)/2; ?>,
          redFrom: <?php echo ($max+$average)/2; ?>, redTo: <?php echo $max; ?>,
          minorTicks: 5
        };

        var chart = new google.visualization.Gauge(document.getElementById('chart_div'));

        chart.draw(data, options);

        /*
        setInterval(function() {
          data.setValue(0, 1, 40 + Math.round(60 * Math.random()));
          chart.draw(data, options);
        }, 13000);
        */
      }
    </script>


<h1><?php echo $metric->name; ?></h1>
<p><?php echo $metric->description; ?></p>
<div id="chart_div" style="width: 400px; height: 400px;"></div>


<?php
exit();
?>


<style>
  DIV#body { width: auto; background: white; }
  DIV#body HEADER { width: auto; }
  DIV#body DIV.content { margin: 0; padding: 0; width: auto; }

  DIV.hero { margin: 0 auto; width: 1900px; height: auto; padding: 0 0 20px 0; background: white; display: block; overflow: hidden; }
  DIV.hero H1 { margin: 20px 0 50px 0; padding: 0; font-size: 90px; text-align: center; line-height: 120px; }
  DIV.hero DIV.today { float: left; margin: 50px 0 0 20px; border: 2px solid black; width: 600px; height: 350px; text-align: center; overflow: hidden; }
  DIV.hero DIV.today DIV.value { height: 250px; font-size: 170px; line-height: 250px; }
  DIV.hero DIV.today DIV.caption { hight: 100px; background: gray; color: white; font-size: 60px; line-height: 100px; }
  DIV.hero DIV#chart_div { float: right; margin: -50px 0 50px 0 !important; border: none; padding: 0 !important; width: 1200px !important; height: 600px !important; overflow: hidden; }
  DIV.hero DIV.total { clear: both; float: none; display: block; margin: 15px 0 40px 0; color: #dc3912; border: 0px solid #dc3912; background: #f5c4b8; font-size: 60px; line-height: 100px; text-align: center;  }
</style>


<div class="hero">

  <h1><?php echo $metric->name; ?></h1>

  <div class="today">
    <div class="value"><?php echo $metric->value($value); ?></div>
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
    ['<?php echo @date("j-M",strtotime($r1[$i]->recorded)); ?>',  <?php echo @$metric->value($r1[$i]->value,true); ?> <?php if ( isset($r2) ) echo ", ".@$metric->value($r2[$i]->value,true); ?>],
    <?php } ?>
  ]);

  var options = {
    title: '<?php echo $metric->name; ?>',
    <?php if ( !isset($r2) ) { ?>'legend':'none',<?php } ?>
    height: 630,
    width: 1200,
    animation: {
      duration: 1000,
      easing: 'out'
    }
  };

  var chart = new google.visualization.AreaChart(document.getElementById('chart_div'));
  chart.draw(data, options);
  }
</script>


<script>setTimeout(function(){ location.reload(); },3600000)</script>


<?php if ( $showHeading ) showFooter(); ?>

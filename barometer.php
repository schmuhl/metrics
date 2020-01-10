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
//echo '<pre>'.print_r($metric,true).'</pre>';
$min = null;
$max = null;
$sum = 0;
foreach ( $metric->recordings as $recording ) {
  if ( $recording->value < $min || !isset($min) ) $min = $recording->value;
  if ( $recording->value > $max || !isset($max) ) $max = $recording->value;
  $sum += $recording->value;
}
$average = $sum/count($metric->recordings);
$yellow = 1009;
$red = 1022;

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
          ['hPa', <?php echo $metric->value($pressure); ?>]
        ]);

        var options = {
          //animation.easing: 'inAndOut',
          width: 400, height: 400,
          min: <?php echo $min; ?>, max: <?php echo $max; ?>,
          yellowFrom:  <?php echo $min; ?>, yellowTo: <?php echo max($min,$yellow); ?>,
          greenFrom: <?php echo max($min,$yellow); ?>, greenTo: <?php echo min($max,$red); ?>,
          redFrom: <?php echo min($max,$red); ?>, redTo: <?php echo $max; ?>,
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



<script type="text/javascript">
  google.charts.load('current', {'packages':['corechart']});
  google.charts.setOnLoadCallback(drawChart);

  function drawChart() {
    var data = google.visualization.arrayToDataTable([
      ['Recorded', 'hPa'],
      <?php
      foreach ( $metric->recordings as $recording ) {
        echo "['".date("D ga",strtotime($recording->recorded))."',$recording->value],";
      }
      ?>
    ]);

    var options = {
      title: 'Over the past week',
      legend: { position: 'none' },
      curveType: 'function'
    };

    var chart = new google.visualization.LineChart(document.getElementById('curve_chart'));

    chart.draw(data, options);
  }
</script>

<div id="curve_chart" style="width: 100%; height: 200px;"></div>







<style>
  BODY DIV#body { width: auto; background: #fff; margin: 0; }
  DIV#body HEADER { width: auto; }
  DIV#body DIV.content { margin: 0; padding: 10px; width: auto; }
</style>

<?php
exit();
?>




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

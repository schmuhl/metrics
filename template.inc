<?php


// read the configuration
if ( $config = file_get_contents("configuration.json") ) {
    $config = json_decode($config);
    // @todo check to make sure all the config is there
    if ( !isset($config->timezone) ) $config->timezone = 'UTC';
    if ( !isset($config->database) ) die("Database settings are required.");
} else {
    // create a dummy config file
    $json = new stdClass();
    $json->timezone = 'UTC';
    $json->database = new stdClass();
    $json->database->host = '127.0.0.1';
    $json->database->database = 'metrics';
    $json->database->username = 'root';
    $json->database->password = 'root';

    // attempt to write the configuration file
    if ( !file_put_contents('configuration.json',json_encode($json)) ) {
        die('Cannot create configuration file (which is probably good) so you will need to make it yourself or give more access to this directory (scary).');
    }

    // reload and try again
    header("Refresh:0");
    exit();
}


session_start();
require "../projects/common/database.inc"; /* @todo include these functions into the repo or don't use them */
require "Metric.inc";


// use the settings that should be there, hopefully
$mysqli = dbConnect($config->database->database,$config->database->host,$config->database->username,$config->database->password); /* @todo Move these settings into a config file */
date_default_timezone_set($config->timezone); // Note that PHP and MySQL must be running on the same time zone!


/**
 * Show the HTML page header
 * @param string $title The page title
 * @todo Do a better job of displaying the messages in the header
 */
function showHeader ( $title=null ) {
    if ( !isset($_SESSION['messages']) || !is_array($_SESSION['messages']) ) $_SESSION['messages'] = array();
    $messages = $_SESSION['messages'];
    ?>
<!DOCTYPE html>
<html>
    <head>
	<title><?php echo $title; ?></title>
	<script type="text/javascript" src="//code.jquery.com/jquery-1.11.3.min.js"></script>
    </head>
    <header>
        <div>
            <a href="index.php">Home</a>
        </div>
        <div id="messages">
            <?php
            $messages = getMessages();
            print_r($messages);
            ?>
        </div>
    </header>
    <?php
}


function showFooter () {
    ?>
    </body>
</html>
    <?php
}


/**
 * Return the margin of error for a statistical sample
 * @param $std_dev
 * @param $sample_size
 * @param float $z defaults to 95%
 * @return float
 */
function margin_of_error ( $std_dev, $sample_size, $z=1.96 ) {
    return $z*$std_dev/sqrt($sample_size);
}

function standard_deviation(array $a, $sample = false) {
    $n = count($a);
    if ($n === 0) {
        trigger_error("The array has zero elements", E_USER_WARNING);
        return false;
    }
    if ($sample && $n === 1) {
        trigger_error("The array has only 1 element", E_USER_WARNING);
        return false;
    }
    $mean = array_sum($a) / $n;
    $carry = 0.0;
    foreach ($a as $val) {
        $d = ((double) $val) - $mean;
        $carry += $d * $d;
    };
    if ($sample) {
        --$n;
    }
    return sqrt($carry / $n);
}
?>

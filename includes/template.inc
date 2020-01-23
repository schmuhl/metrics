<?php

session_start();


// are you an admin?
if ( isset($_GET['admin']) ) $_SESSION['admin'] = true;
if ( !isset($_SESSION['admin']) ) $_SESSION['admin'] = false;



// read the configuration
require_once 'config.inc';
if ( $config = file_get_contents($configPath) ) {
    $config = json_decode($config);
    // @todo check to make sure all the config is there
    if ( !isset($config->timezone) ) $config->timezone = 'UTC';
    if ( !isset($config->errors) ) $config->errors = false;
    if ( !isset($config->database) ) die("Database settings are required.");
    date_default_timezone_set($config->timezone);
} else {
    // create a dummy config file
    $json = new stdClass();
    $json->name = 'Metrics';
    $json->timezone = 'UTC';
    $json->errors = 1;
    $json->database = new stdClass();
    $json->database->host = '127.0.0.1';
    $json->database->database = 'metrics';
    $json->database->username = 'root';
    $json->database->password = 'root';

    // attempt to write the configuration file
    if ( !file_put_contents($configPath,json_encode($json)) ) {
        die('Cannot create configuration file (which is probably good) so you will need to make it yourself or give more access to this directory (scary).');
    }

    // reload and try again
    header("Refresh:0");
    exit();
}

// show errors?
if ( $config->errors ) error_reporting(-1); else error_reporting(0);

// date ranges for reporting
if ( !isset($config->timeframes) || !is_array($config->timeframes) || count($config->timeframes) < 2 ) { // default to quarters
  $config->timeframes = array ( '1/1', '5/1', '8/1' );
}
$config->startTime = null;
$config->endTime = null;
$min = strtotime('+1year');
$max = null;
foreach ( $config->timeframes as $date ) {
  $date = strtotime($date);
  if ( $date > time() || (isset($config->endTime) && $date < $config->endTime) ) $config->endTime = $date; // set the end time
  if ( time() > $date || (isset($config->startTime) && $config->startTime > $date) ) $config->startTime = $date;  // set the start time
  if ( $date > $max ) $max = $date;
  if ( $date < $min ) $min = $date;
}
if ( !isset($config->endTime) || empty($config->endTime) ) $config->endTime = $min+(365*24*60*60); // set it to the min for next year
//print_r($config); print_r("The max is ".date('Y-m-d',$max)." and the min is ".date('Y-m-d',$min).'. But the start time is '.date('Y-m-d',$config->startTime).' and the end time is '.date('Y-m-d',$config->endTime).', OK?'); die();



require "Metric.inc";


// use the settings that should be there, hopefully
$mysqli = dbConnect($config->database->database,$config->database->host,$config->database->username,$config->database->password);
date_default_timezone_set($config->timezone); // Note that PHP and MySQL must be running on the same time zone!


/**
 * Show the HTML page header
 * @param string $title The page title
 * @todo Do a better job of displaying the messages in the header
 */
function showHeader ( $title=null ) {
    global $config;
    if ( !isset($_SESSION['messages']) || !is_array($_SESSION['messages']) ) $_SESSION['messages'] = array();
    $messages = $_SESSION['messages'];
    ?>
<!DOCTYPE html>
<html>
    <head>
        <title><?php echo $title; ?></title>
        <script type="text/javascript" src="//code.jquery.com/jquery-1.11.3.min.js"></script>
        <script type="text/javascript" src="script.js"></script>
        <link href="style.css" rel="stylesheet" />
        <meta name="viewport" content ="width=device-width,initial-scale=1,user-scalable=yes" />
    </head>
    <body>
        <div id="body">
            <header>
                <div id="title">
                    <a href="index.php"><?php echo $config->name; ?></a>
                </div>
                <div id="messagesBox">
                    <?php
                    $messages = getMessages();
                    if ( count($messages) > 0 ) {
                        ?>
                    <ul id="messages">
                        <?php foreach ( $messages as $message ) {
                            ?>
                        <li><?php echo $message; ?></li>
                            <?php
                        }
                        ?>
                    </ul>
                        <?php
                    }
                    ?>
                </div>
            </header>
            <div class="content">
    <?php
}


function showFooter () {
    ?>
            </div>
            <footer>
                &nbsp;
            </footer>
        </div>
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
    if ( $sample_size > 0 ) return $z*$std_dev/sqrt($sample_size);
    return null;
}

function standard_deviation(array $a, $sample = false) {
    $n = count($a);
    if ($n === 0) {
        //trigger_error("The array has zero elements", E_USER_WARNING);
        return false;
    }
    if ($sample && $n === 1) {
        //trigger_error("The array has only 1 element", E_USER_WARNING);
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




/**
 * load this array up if you want to show error messages to the user
 * @param mixed $message
 * @return boolean
 */
function addMessage ( $message ) {
    if ( !isset($_SESSION['messages']) ) {  // set the session message variable
        $_SESSION['messages'] = array();
    }
    if ( is_array($message) ) {
        foreach ( $message as $m ) {
            if ( !empty($m) ) {
                $_SESSION['messages'] []= $m;
            }
        }
    } else if ( !empty($message) ) {
        $_SESSION['messages'] []= $message;
    } else {
        return false;
    }
    return true;
}


/**
 * Get the messages
 * @return array An array of strings
 */
function getMessages ( $clear=true ) {
    // messages to show to the user
    if ( !isset($_SESSION['messages']) || !is_array($_SESSION['messages']) ) $_SESSION['messages'] = array();
    $messages = $_SESSION['messages'];
    $_SESSION['messages'] = array(); // clear it
    return $messages;
}



/**
 * Make a connection to the database
 * @global string $MYSQL_USER
 * @global string $MYSQL_PASSWORD
 * @param string $database
 * @return boolean
 */
function dbConnect ( $database, $host, $user, $password ) {
    if ( empty($database) || empty($host) || empty($user) ) return false;

    $mysqli = new mysqli($host,$user,$password,$database);
    if ($mysqli->connect_errno) {
        trigger_error("Failed to connect to MySQL database '$database' on host '$host' : (" . $mysqli->connect_errno . " " . $mysqli->connect_error.")");
        return false;
    }

    return $mysqli;
}


/**
 * Wrapper function for queries
 * @global type $mysqli
 * @param string $sql
 * @return type result
 */
function dbQuery ( $sql ) {
    global $mysqli;
    if ( !$mysqli ) return false;
    $result = $mysqli->query($sql);
    if ( !$result && isset($DEVELOPMENT_ENVIRONMENT) && $DEVELOPMENT_ENVIRONMENT ) trigger_error(mysql_error()." on ($sql)");
    return $result;
}

function dbFetch ( $results ) {
    global $mysqli;
    return $results->fetch_assoc();

}

/**
 * Wrapper function for knowing how many rows were affected by the last query
 * @global type $mysqli
 * @return int
 */
function dbRows () {
    global $mysqli;
    return $mysqli->affected_rows;
}

/**
 * Wrapper function for knowing the ID for a newly inserted row
 * @global type $mysqli
 * @return int
 */
function dbInsertID () {
    global $mysqli;
    return $mysqli->insert_id;
}

/**
 * Wrapper function for knowing if there is a database error
 * @global type $mysqli
 * @return string
 */
function dbError () {
    global $mysqli;
    return $mysqli->error;
}


?>

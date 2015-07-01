<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

session_start();
require "../projects/common/database.inc"; /* @todo include these functions into the repo or don't use them */
require "Metric.inc";
$mysqli = dbConnect("metrics","127.0.0.1","root","root"); /* @todo Move these settings into a config file */
date_default_timezone_set("UTC"); // Note that PHP and MySQL must be running on the same time zone!


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
?>

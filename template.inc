<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

session_start();
require "../projects/common/database.inc";
require "Metric.inc";
$mysqli = dbConnect("metrics","127.0.0.1","root","root");
date_default_timezone_set("UTC");



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

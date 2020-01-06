<?php
/**
 * Created by PhpStorm.
 * User: schmuhl
 * Date: 3/23/15
 * Time: 10:25 AM
 */

require 'template.inc';

showHeader("Setup");


if ( Metric::createTable() ) echo "Tables have been created.";
else echo "Tables could not be created.";
?>


<?php
showFooter();
?>

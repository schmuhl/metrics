<?php
/**
 * Created by PhpStorm.
 * User: schmuhl
 * Date: 3/23/15
 * Time: 10:25 AM
 */

require "includes/template.inc";

showHeader("Setup");


echo '<h1>Database Setup</h1>';
if ( Metric::createTable() ) echo "<p>Tables have been created.</p>";
else echo "<p>Tables could not be created.</p>";


// check for "manual" db improvements that need to be made
$result = dbQuery("DESC metrics;");
while ( $row = $result->fetch_assoc() ) {
  //print_r($row);
  // look for the original schema that used restrictive enums
  if ( $row['Field'] == 'type' && is_numeric(strpos($row['Type'],'enum')) ) {
    echo '<p>Need to apply "restrictive enum" database upgrade...';
    if ( dbQuery("ALTER TABLE metrics CHANGE COLUMN `type` `type` VARCHAR(255);") &&
      dbQuery("ALTER TABLE metrics CHANGE COLUMN `frequency` `frequency` VARCHAR(255);") ) {
          echo ' upgrade successful!</p>';
    } else echo ' failed to upgrade: '.dbError().'</p>';
  }
}
?>


<?php
showFooter();
?>

<?php

require "includes/template.inc";
if ( !isset($_SESSION['admin']) || !$_SESSION['admin'] ) {
    $_SESSION['admin'] = true;
    addMessage("You have been logged in as an admin.");
} else {
    $_SESSION['admin'] = false;
    addMessage("You have been logged out.");
}

?>

<script>
    location.href='index.php';
</script>

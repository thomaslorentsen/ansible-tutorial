<h1>Pearls Cat Food Website</h1>
<?php
if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) { ?>
    <p>Our website will never go down, we are powered by HA Proxy</p>
    <p>Your request was made on <?php echo $_SERVER['HTTP_X_FORWARDED_FOR'] ?> frontend server.</p>
<?php } else { ?>
    <p>Sorry, our website is only powered by a single server. Only one cat at a time please!</p>
<?php } ?>

<pre>
<?php
var_dump($_SERVER);
?>
</pre>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/css/bootstrap.min.css" integrity="sha384-rwoIResjU2yc3z8GV/NPeZWAv56rSmLldC3R/AZzGRnGxQQKnKkoFVhFQhNUwEyJ" crossorigin="anonymous">
    <script src="https://code.jquery.com/jquery-3.1.1.slim.min.js" integrity="sha384-A7FZj7v+d/sdmMqp/nOQwliLvUsJfDHW+k9Omg/a/EheAdgtzNs3hpfag6Ed950n" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tether/1.4.0/js/tether.min.js" integrity="sha384-DztdAPBWPRXSA/3eYEEUWrWCy7G5KFbe8fFjk5JAIxUYHKkDx6Qin1DkWx51bBrb" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/js/bootstrap.min.js" integrity="sha384-vBWWzlZJ8ea9aCX4pEW3rVHjgjt7zpkNpZk+02D9phzyeVkE+jo0ieGizqPLForn" crossorigin="anonymous"></script>
</head>
<body>
<div class="container">


<h1 class="display-6 text-center">Pearl's Cat Food Treats</h1>
<?php
if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) { ?>
    <div class="alert alert-info" role="alert">
        You will always be able to make an order because our website is powered by a load balancer!
    </div>
    <div class="alert alert-info" role="alert">
        This page was generated from the <strong>{{ server_name }}</strong> frontend server.
    </div>
<?php } else { ?>
    <div class="alert alert-danger" role="alert">
        Sorry, our website is only powered by a single server. Only one visitor at a time please!
    </div>
<?php } ?>

<?php

try {
    include "predis-1.1.1/src/Autoloader.php";
    Predis\Autoloader::register();
    $redis = new Predis\Client('tcp://192.168.33.35:6379');
    if (!$redis->exists('counter')) {
        $redis->set("counter", "0");
    }
    $redis->incr("counter");
    $counter = $redis->get("counter");
?>
    <h1 class="display-4">
        You are visitor <?php echo $counter ?>! Please place your order.
    </h1>
    <form>
        <div class="form-group">
            <label for="exampleInputName">Pet's Name</label>
            <input type="text" class="form-control" id="exampleInputName" placeholder="">
        </div>
        <div class="form-group">
            <label for="exampleTextarea">Pet's Address</label>
            <textarea class="form-control" id="exampleTextarea" rows="3"></textarea>
        </div>
        <div class="form-check">
            <label class="form-check-label">
                <input type="checkbox" class="form-check-input">
                Pearl's Fish Bites
            </label>
        </div>
        <div class="form-check">
            <label class="form-check-label">
                <input type="checkbox" class="form-check-input">
                Pearl's Vegan Selection
            </label>
        </div>
        <div class="form-check">
            <label class="form-check-label">
                <input type="checkbox" class="form-check-input">
                Pearl's Chocolate Dessert
            </label>
        </div>
        <button type="submit" class="btn btn-primary">Order</button>
    </form>

<?php } catch (Exception $exception) { ?>
    <div class="alert alert-danger" role="alert">
        We will be taking orders once we have Micro Services!
    </div>
<?php } ?>
</div>
</body>
</html>

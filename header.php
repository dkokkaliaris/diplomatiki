<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
	<base href="<?php echo BASE_URL ?>">

    <!-- Bootstrap CSS -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="<?php echo BASE_URL ?>/css/bootstrap.min.css" rel="stylesheet" />

    <!-- CSS DateTimePicker -->
    <link href="<?php echo BASE_URL ?>/css/jquery.datetimepicker.css" rel="stylesheet" type="text/css" />
    <!-- CSS Font Awesome -->
    <link href="<?php echo BASE_URL ?>/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
    <!-- Custom CSS -->
    <link href="<?php echo BASE_URL ?>/css/custom.css" rel="stylesheet" type="text/css" />

    <!-- jQuery -->
    <script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
    <!-- Tether -->
    <script src="//www.atlasestateagents.co.uk/javascript/tether.min.js"></script>
    <!-- Bootstrap -->
    <script src="<?php echo BASE_URL ?>/js/bootstrap.min.js"></script>
    <!-- JS DateTimePicker -->
    <script src="<?php echo BASE_URL ?>/js/jquery.datetimepicker.js"></script>
    <!-- jQuery UI -->
    <script src="<?php echo BASE_URL ?>/js/jquery-ui.min.js"></script>
    <title>Questionnaire</title>
</head>
<body>
    <header>
        <div class="container">
            <div class="row">
                <div class="cold-md-12">
                    <?php if(isset($_SESSION['userid']) && $_SESSION['userid']){?>
                        <a href="<?php echo BASE_URL; ?>logout.php">Έξοδος</a>
                    <?php }else{?>
                        <a href="<?php echo BASE_URL; ?>login.php">Είσοδος</a>
                    <?php }?>
                </div>
            </div>
        </div>
    </header>
    <div class="logo-container">
        <div class="container">
            <a class="logo" href="<?php echo BASE_URL; ?>index.php"><img class="logo" src= "<?php echo BASE_URL; ?>assets/img/admin.png"></a>
        </div>
    </div>
    <section class="menu-section">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <nav class="navbar navbar-dark">
                        <ul class="nav navbar-nav pull-xs-right">
                            <li class="nav-item active"><a class="nav-link" href="<?php echo BASE_URL; ?>index.php">Αρχική Σελίδα</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </section>

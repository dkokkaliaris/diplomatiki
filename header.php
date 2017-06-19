<?php

error_reporting(E_ALL);
ini_set("display_errors",1);

echo '<!DOCTYPE HTML>
<html lang="el">
<html>
<head>
    <meta charset="utf-8" />
	<base href="'.BASE_URL.'">

    <!-- Bootstrap CSS -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="'.BASE_URL.'css/bootstrap.min.css" rel="stylesheet" />

    <!-- CSS DateTimePicker -->
    <link href="'.BASE_URL.'css/jquery.datetimepicker.css" rel="stylesheet" type="text/css" />
    <!-- CSS Font Awesome -->
    <link href="'.BASE_URL.'css/font-awesome.min.css" rel="stylesheet" type="text/css" />
    <!-- Custom CSS -->
    <link href="'.BASE_URL.'css/custom.css" rel="stylesheet" type="text/css" />

    <!-- jQuery -->
    <script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
    <!-- jQuery UI -->
    <script src="'.BASE_URL.'js/jquery-ui.min.js"></script>
    <!-- Tether -->
    <script src="//www.atlasestateagents.co.uk/javascript/tether.min.js"></script>
    <!-- Bootstrap -->
    <script src="'.BASE_URL.'js/bootstrap.min.js"></script>
    <!-- JS DateTimePicker -->
    <script src="'.BASE_URL.'js/jquery.datetimepicker.js"></script>
    <!-- ChartJS -->
    <script src="'.BASE_URL.'js/Chart.min.js"></script>
    <!-- jQuery Validator -->
    <script src="'.BASE_URL.'js/jquery.validate.min.js"></script>
    <script src="'.BASE_URL.'js/custom.js"></script>
    <title>Questionnaire</title>
</head>
<body>
    <header>
        <div class="container">
            <div class="row">
                <div class="cold-md-12">
                    '.(is_logged_in()
                        ?'<a href="'.BASE_URL.'logout.php" style="color: #FFFFFF">Έξοδος </a>('.$_SESSION['username'].')'
                        :'<a href="'.BASE_URL.'login.php" style="color: #FFFFFF">Είσοδος</a>').'
                </div>
            </div>
        </div>
    </header>
    <div class="logo-container">
        <div class="container">
            <a class="logo" href="'.BASE_URL.'index.php"><img alt="i-evaluiation" class="logo" src= "'.BASE_URL.'img/admin.png"></a>

        </div>
    </div>
    <section class="menu-section">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <nav class="navbar navbar-dark header-nav">
                        <ul class="nav navbar-nav pull-xs-right">';
                            $page = basename($_SERVER['REQUEST_URI'], '.php');
                            echo '<li class="nav-item '.($page=='index'?' active':'').'"><a class="nav-link" href="'.BASE_URL.'index.php">Αρχική Σελίδα</a></li>';
                            if ($_SESSION) { include "sidebar.php";}

                        echo '
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </section>';

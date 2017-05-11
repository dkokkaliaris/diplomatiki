<?php
session_start();
include_once('sso/CAS.php');
include_once('sso/cas_config.php');
if ($_SESSION['sso_login']) {
    phpCAS::client($cas_protocol, $cas_sso_server, $cas_port, '');
    phpCAS::setCasServerCACert($cas_cert);
    phpCAS::handleLogoutRequests(true ,array($cas_sso_server));
    phpCAS::forceAuthentication();
    phpCAS::logout(array("service"=>$cas_logout_app_redirect_url));
}
session_destroy();
header("Location: login.php");
exit();
?>

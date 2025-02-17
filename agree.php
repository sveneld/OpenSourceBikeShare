<?php

require_once 'vendor/autoload.php';
require_once 'actions-web.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title><? echo $configuration->get('systemname'); ?> <?= _('account activation'); ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
<script type="text/javascript" src="js/bootstrap.min.js"></script>
<script type="text/javascript" src="js/bootstrapValidator.min.js"></script>
<script type="text/javascript" src="js/register.js"></script>
<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css" />
<link rel="stylesheet" type="text/css" href="css/bootstrap-theme.min.css" />
<link rel="stylesheet" type="text/css" href="css/bootstrapValidator.min.css" />
<link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
<link rel="icon" type="image/svg+xml" href="/favicon.svg" />
<link rel="shortcut icon" href="/favicon.ico" />
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
<meta name="apple-mobile-web-app-title" content="Whitebikes" />
<link rel="manifest" href="/site.webmanifest" />
<?php require("analytics.php"); ?>
</head>
<body>
<!-- Fixed navbar -->
    <div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="sr-only"><?= _('Toggle navigation'); ?></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="<?= $configuration->get('systemURL'); ?>"><?= $configuration->get('systemname'); ?></a>
        </div>
        <div class="collapse navbar-collapse">
          <ul class="nav navbar-nav">
            <li><a href="<?= $configuration->get('systemURL'); ?>"><?= _('Map'); ?></a></li>
          </ul>
        </div><!--/.nav-collapse -->
      </div>
    </div>
<br />
<div class="container">
   <div class="page-header">
   <h1><?= _('Account activation'); ?></h1>
   </div>
<?php
$userkey="";
if (isset($_GET["key"])) $userkey=$_GET["key"];
confirmUser($userkey);
?>
<div class="alert alert-warning" role="alert">
<p><?= _('By registering I confirm that I have read:'); ?> <a href="<?= $configuration->get('systemrules'); ?>"><?= _('User Guide'); ?></a></p>
</div>
   <div class="panel panel-default">
  <div class="panel-body">
    <i class="glyphicon glyphicon-copyright-mark"></i> <? echo date("Y"); ?> <a href="<?= $configuration->get('systemURL'); ?>"><?= $configuration->get('systemname'); ?></a>
  </div>
  <div class="panel-footer"><strong><?= _('Privacy policy:'); ?></strong> <?= _('We will use your details for'); echo $configuration->get('systemname'),'-'; echo _('related activities only'); ?>.</div>
   </div>

    </div><!-- /.container -->
</body>
</html>
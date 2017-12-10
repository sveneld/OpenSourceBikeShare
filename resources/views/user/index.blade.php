<?php

$systemURL="https://github.com/mmmaly/OpenSourceBikeShare"; // needs trailing slash!
$systemlang="en_EN"; // language code such as en_EN, de_DE etc. - translation must be in languages/ directory, defaults to English if non-existent

$systememail="email@example.com"; // system From: email address for sending emails

$forcestack=0; // 0 = allow renting any bike at stand, 1 = allow renting last bicycle returned only (top of stack)
$watches["email"]="email@example.com"; // notification email for notifications such as notes etc., blank if notifications not required
$watches["stack"]=1;  // 0 - do not watch stack, 1 - notify if other than the top of the stack bike is rented from a stand (independent from forcestack)
$watches["longrental"]=24; // in hours (bike rented for more than X h)
$watches["timetoomany"]=1; // in hours (high number of rentals by one person in a short time)
$watches["numbertoomany"]=1; // if userlimit+numbertooomany reached in timetoomany, then notify
$watches["freetime"]=30; // in minutes (rental changes from free to paid after this time and $credit["rent"] is deducted)
$watches["flatpricecycle"]=60; // in minutes (uses flat price $credit["rent"] every $watches["flatpricecycle"] minutes after first paid period, i.e. $watches["freetime"]*2)
$watches["doublepricecycle"]=60; // in minutes (doubles the rental price $credit["rent"] every $watches["doublepricecycle"] minutes after first paid period, i.e. $watches["freetime"]*2)
$watches["doublepricecyclecap"]=3; // number of cycles after doubling of rental price $credit["rent"] is capped and stays flat (but reached cycle multiplier still applies)

$limits["registration"]=0; // number of bikes user can rent after he registered: 0 = no bike, 1 = 1 bike etc.
$limits["increase"]=0; // allow more bike rentals in addition to user limit: 0 = not allowed, otherwise: temporary limit increase - number of bikes

$credit["enabled"]=1; // 0 = no credit system, 1 = apply credit system rules and deductions
$credit["currency"]="€"; // currency used for credit system
$credit["min"]=2; // minimum credit required to allow any bike operations
$credit["rent"]=2; // rental fee (after $watches["freetime"])
$credit["pricecycle"]=0; // 0 = disabled, 1 = charge flat price $credit["rent"] every $watches["flatpricecycle"] minutes, 2 = charge doubled price $credit["rent"] every $watches["doublepricecycle"] minutes
$credit["longrental"]=5; // long rental fee ($watches["longrental"] time)
$credit["limitincrease"]=10; // credit needed to temporarily increase limit, applicable only when $limits["increase"]>0
$credit["violation"]=5; // credit deduction for rule violations (applied by admins)

$notifyuser=0; // 0 = no notication send to users (when admins get notified), 1 = notification messages sent to users as well

/*** Database ***/
$dbserver="localhost";
$dbuser="root";
$dbpassword="root";
$dbname="";

/*** Email ***/
$email["smtp"]="mail.example.com"; // SMTP mail server for notifications
$email["user"]="user"; // mail server username
$email["pass"]="pass"; // mail server password

/*** SMS related ***/
$connectors["sms"]=""; // API connector used for SMS operations (connectors/ directory); empty to disable SMS system, "loopback" to simulate dummy gateway API for testing
$countrycode=""; // international dialing code (country code prefix), no plus sign

/*** geoJSON files - uncomment line below to use, any number of geoJSON files can be included ***/
// $geojson[]="http://example.com/poi.json"; // example geojson file with points of interests to be displayed on the map


//require("config.php");
//require("db.class.php");
//require("actions-web.php");

//$db=new Database($dbserver,$dbuser,$dbpassword,$dbname);
//$db->connect();
?>
        <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $systemname }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
    <script type="text/javascript" src="js/old/bootstrap.min.js"></script>
    <script type="text/javascript" src="js/old/viewportDetect.js"></script>
    <script type="text/javascript" src="js/old/leaflet.js"></script>
    <script type="text/javascript" src="js/old/L.Control.Sidebar.js"></script>
    <script type="text/javascript" src="js/old/translations.php"></script>
    <script type="text/javascript" src="js/old/functions.js"></script>
    <?php
    //if (isset($geojson))
    //   {
    //   foreach($geojson as $url)
    //      {
    //      echo '<link rel="points" type="application/json" href="',$url,'">'."\n";
    //      }
    //   }
    //?>
    <?php //if (date("m-d")=="04-01") echo '<script type="text/javascript" src="http://maps.stamen.com/js/tile.stamen.js?v1.3.0"></script>'; ?>
    <link rel="stylesheet" type="text/css" href="css/old/bootstrap.min.css"/>
    <link rel="stylesheet" type="text/css" href="css/old/bootstrap-theme.min.css"/>
    <link rel="stylesheet" type="text/css" href="css/old/leaflet.css"/>
    <link rel="stylesheet" type="text/css" href="css/old/L.Control.Sidebar.css"/>
    <link rel="stylesheet" type="text/css" href="css/old/map.css"/>
    <script>
        var maplat ={{ $systemlat }};
        var maplon ={{ $systemlong }};
        var mapzoom ={{  $systemzoom }};
        var standselected = 0;

        @if($isloggedin)
            var loggedin=1;
            var priv=0;//            var priv=', getprivileges($_COOKIE["loguserid"]), ";\n";
        @else
            var loggedin=0;
            var priv=0;
        @endif

        @if($iscreditenabled)
            var creditsystem=1;
        @else
            var creditsystem=0;
        @endif

        @if($issmssystemenabled)
            var sms=1;
        @else
            var sms=0;
        @endif
    </script>
   <?php //if (file_exists("analytics.php")) require("analytics.php"); ?>
</head>
<body>
<div id="map"></div>
<div id="sidebar">
    <div id="overlay"></div>
    <div class="row">
        <div class="col-xs-11 col-sm-11 col-md-11 col-lg-11">
            <ul class="list-inline">
                <li><a href="{{ $systemrules }}">
                        <span
                                class="glyphicon glyphicon-question-sign"></span>
                        {{ __('Help') }}></a>
                </li>

                @if($isloggedin && $isadmin)
                    <li>
                        {{-- TODO: link --}}
                        <a href="admin.php"><span class="glyphicon glyphicon-cog"></span>{{  __('Admin') }}</a>
                    </li>
                @endif

                @if($isloggedin)
                    <li>
                        <span class="glyphicon glyphicon-user"></span>
                        <small>{{ $user->name }}</small>
                    </li>
                @endif

                @if($iscreditenabled)
                    <li>
                        (<span id="usercredit" title="{{  __('Remaining credit') }}">{{ $user->credit }}</span> {{ $currency }}
                        <button type="button" class="btn btn-success btn-xs" id="opencredit" title="{{  __('Add credit') }}">
                            <span class="glyphicon glyphicon-plus"></span></button>)
                    </li>
                @endif

                <li><a href="command.php?action=logout" id="logout">
                        <span class="glyphicon glyphicon-log-out"></span> {{ __('Log out')}} </a></li>
            </ul>
        </div>
        <div class="col-xs-1 col-sm-1 col-md-1 col-lg-1">
        </div>
    </div>
    <div class="row">
        <div class="col-xs-11 col-sm-11 col-md-11 col-lg-11">
            <h1 class="pull-left"><{{ $systemname }}</h1>
        </div>
        <div class="col-xs-1 col-sm-1 col-md-1 col-lg-1">
        </div>
    </div>

    @if($isloggedin)
    <div id="loginform">
        <h1>Log in</h1>

        @if ($error == 1)
            <div class="alert alert-danger" role="alert">
                <h3>
                    {{__('User / phone number or password incorrect! Please, try again.')}}
                </h3>
            </div>
        @elseif($error == 2)
            <div class="alert alert-danger" role="alert">
                <h3>
                    {{  __('Session timed out! Please, log in again.') }}
                </h3>
            </div>
        @endif
        <form method="POST" action="command.php?action=login">
            <div class="row">
                <div class="col-lg-12">
                    <label for="number" class="control-label">
                        @if($issmssystemenabled)
                            {{__('Phone number:') }}
                        @else
                            {{ __('User number:') }}
                        @endif
                        </label> <input type="text" name="number" id="number" class="form-control"/>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <label for="password">{{  __('Password:') }}
                        <small id="passwordresetblock">(<a
                                    id="resetpassword">{{__('Forgotten? Reset password')}}</a>)
                        </small>
                    </label> <input type="password" name="password" id="password" class="form-control"/>
                </div>
            </div>
            <br/>
            <div class="row">
                <div class="col-lg-12">
                    <button type="submit" id="register"
                            class="btn btn-lg btn-block btn-primary">{{ __('Log in') }}</button>
                </div>
            </div>
        </form>
    </div>
    @endif


    <h2 id="standname"><select id="stands"></select><span id="standcount"></span></h2>
    <div id="standinfo"></div>
    <div id="standphoto"></div>
    <div id="standbikes"></div>
    <div class="row">
        <div class="col-lg-12">
            <div id="console">
            </div>
        </div>
    </div>
    <div class="row">
        <div id="standactions" class="btn-group">
            <div class="col-lg-12">
                <button class="btn btn-primary btn-large col-lg-12" type="button" id="rent"
                        title="{{ __('Choose bike number and rent bicycle. You will receive a code to unlock the bike and the new code to set.')}}">
                    <span class="glyphicon glyphicon-log-out"></span> {{  __('Rent') }}<span
                            class="bikenumber"></span></button>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <br/></div>
    </div>
    <div id="rentedbikes"></div>
    <div class="row">
        <div class="input-group">
            <div class="col-lg-12">
                <input type="text" name="notetext" id="notetext" class="form-control"
                       placeholder="{{ __('Describe problem') }}">
            </div>
        </div>
    </div>
    <div class="row">
        <div class="btn-group bicycleactions">
            <div class="col-lg-12">
                <button type="button" class="btn btn-primary" id="return"
                        title="{{ __('Return this bicycle to the selected stand.') }}"><span
                            class="glyphicon glyphicon-log-in"></span> {{ __('Return bicycle') }} <span
                            class="bikenumber"></span></button>
                ({{ __('and')  }}<a href="#" id="note"
                                            title="{{ __('Use this link to open a text field to write in any issues with the bicycle you are returning (flat tire, chain stuck etc.).') }} ">
                    {{__('report problem')}}
                    <span class="glyphicon glyphicon-exclamation-sign"></span></a>)
            </div>
        </div>
    </div>

</div>
</body>
</html>

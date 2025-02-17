<?php
error_reporting(0);
define('DEBUG',FALSE); // debug mode, TRUE to turn on
define('ERROR',0);

$systemname="OpenSourceBikeShare";
$systemURL="https://github.com/mmmaly/OpenSourceBikeShare"; // needs trailing slash!
$systemlang="en_EN"; // language code such as en_EN, de_DE etc. - translation must be in languages/ directory, defaults to English if non-existent
$systemlat="48.148154"; // default map center point - latitude
$systemlong="17.117232"; // default map center point - longitude
$systemzoom="15"; // default map zoom
$systemrules="http://example.com/rules.htm"; // system rules / help URL
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

/*** Database ***/
$dbserver="localhost";
$dbuser="bikeshare";
$dbpassword="YourPassword";
$dbname="WB";

/*** Email ***/
$email["smtp"]="mail.example.com"; // SMTP mail server for notifications
$email["user"]="user"; // mail server username
$email["pass"]="pass"; // mail server password

/*** SMS related ***/
$connectors["sms"]=""; // API connector used for SMS operations (connectors/ directory); empty to disable SMS system, "loopback" to simulate dummy gateway API for testing
$connectors["config"]["disabled"]="{}"; //json string for configuration of sms service
$countrycode=""; // international dialing code (country code prefix), no plus sign

$cities = ['Bratislava']; //avalible in cities
$citiesGPS = [
    'Bratislava' => ['48.148154', '17.117232']
];

/*** geoJSON files - uncomment line below to use, any number of geoJSON files can be included ***/
// $geojson[]="http://example.com/poi.json"; // example geojson file with points of interests to be displayed on the map

?>
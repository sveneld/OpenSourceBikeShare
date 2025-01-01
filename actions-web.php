<?php
require("common.php");

function response($message, $error = 0, $additional = '', $log = 1)
{
    global $db, $user, $auth;
    $json = array('error' => $error, 'content' => $message);
    if (is_array($additional)) {
        foreach ($additional as $key => $value) {
            $json[$key] = $value;
        }
    }
    $json = json_encode($json);
    if ($log == 1 and $message) {
        $userid = $auth->getUserId();

        $number = $user->findPhoneNumber($userid);
        logresult($number, $message);
    }
    $db->commit();
    echo $json;
    exit;
}

function where($userId, $bike)
{
    global $db;
    $bikeNum = $bike;

    $result = $db->query("SELECT number,userName,stands.standName FROM bikes LEFT JOIN users on bikes.currentUser=users.userID LEFT JOIN stands on bikes.currentStand=stands.standId where bikeNum=$bikeNum");
    $row = $result->fetch_assoc();
    $phone = $row['number'];
    $userName = $row['userName'];
    $standName = $row['standName'];
    $result = $db->query("SELECT note FROM notes WHERE bikeNum='$bikeNum' AND deleted IS NULL ORDER BY time DESC");
    $note = '';
    while ($row = $result->fetch_assoc()) {
        $note .= $row['note'] . '; ';
    }
    $note = substr($note, 0, strlen($note) - 2); // remove last two chars - comma and space
    if ($note) {
        $note = _('Bike note:') . ' ' . $note;
    }

    if ($standName) {
        response('<h3>' . _('Bike') . ' ' . $bikeNum . ' ' . _('at') . ' <span class="label label-primary">' . $standName . '</span>.</h3>' . $note);
    } else {
        response('<h3>' . _('Bike') . ' ' . $bikeNum . ' ' . _('rented by') . ' <span class="label label-primary">' . $userName . '</span>.</h3>' . _('Phone') . ': <a href="tel:+' . $phone . '">+' . $phone . '</a>. ' . $note);
    }
}

function listbikes($stand)
{
    global $db, $configuration;

    $stacktopbike = false;
    $stand = $db->escape($stand);
    if ($configuration->get('forcestack')) {
        $result = $db->query("SELECT standId FROM stands WHERE standName='$stand'");
        $row = $result->fetch_assoc();
        $stacktopbike = checktopofstack($row['standId']);
    }
    $result = $db->query("SELECT bikeNum FROM bikes LEFT JOIN stands ON bikes.currentStand=stands.standId WHERE standName='$stand'");
    while ($row = $result->fetch_assoc()) {
        $bikenum = $row['bikeNum'];
        $result2 = $db->query("SELECT note FROM notes WHERE bikeNum='$bikenum' AND deleted IS NULL ORDER BY time DESC");
        $note = '';
        while ($row = $result2->fetch_assoc()) {
            $note .= $row['note'] . '; ';
        }
        $note = substr($note, 0, strlen($note) - 2); // remove last two chars - comma and space
        if ($note) {
            $bicycles[] = '*' . $bikenum; // bike with note / issue
            $notes[] = $note;
        } else {
            $bicycles[] = $bikenum;
            $notes[] = '';
        }
    }
    if (!$result->num_rows) {
        $bicycles = '';
        $notes = '';
    }
    response($bicycles, 0, array('notes' => $notes, 'stacktopbike' => $stacktopbike), 0);
}

function removenote($userId, $bikeNum)
{
    global $db;

    $result = $db->query("DELETE FROM notes WHERE bikeNum=$bikeNum LIMIT XXXX");
    response(_('Note for bike') . ' ' . $bikeNum . ' ' . _('deleted') . '.');
}

function userbikes($userId)
{
    global $db, $auth;
    if (!$auth->isLoggedIn()) {
        response('');
    }

    $result = $db->query("SELECT bikeNum,currentCode FROM bikes WHERE currentUser=$userId ORDER BY bikeNum");
    while ($row = $result->fetch_assoc()) {
        $bikenum = $row['bikeNum'];
        $bicycles[] = $bikenum;
        $codes[] = str_pad($row['currentCode'], 4, '0', STR_PAD_LEFT);
        // get rented seconds and the old code
        $result2 = $db->query("SELECT TIMESTAMPDIFF(SECOND, time, NOW()) as rentedSeconds, parameter FROM history WHERE bikeNum=$bikenum AND action IN ('RENT','FORCERENT') ORDER BY time DESC LIMIT 2");

        $row2 = $result2->fetchAssoc();
        $rentedseconds[] = $row2['rentedSeconds'];

        $row2 = $result2->fetchAssoc();
        $oldcodes[] = str_pad($row2['parameter'], 4, '0', STR_PAD_LEFT);
    }

    if (!$result->num_rows) {
        $bicycles = '';
    }

    if (!isset($codes)) {
        $codes = '';
    } else {
        $codes = array('codes' => $codes, 'oldcodes' => $oldcodes, 'rentedseconds' => $rentedseconds);
    }

    response($bicycles, 0, $codes, 0);
}

function revert($userId, $bikeNum)
{
    global $db, $smsSender, $user;

    $standId = 0;
    $result = $db->query("SELECT currentUser FROM bikes WHERE bikeNum=$bikeNum AND currentUser IS NOT NULL");
    if (!$result->num_rows) {
        response(_('Bicycle') . ' ' . $bikeNum . ' ' . _('is not rented right now. Revert not successful!'), ERROR);
        return;
    } else {
        $row = $result->fetch_assoc();
        $revertusernumber = $user->findPhoneNumber($row['currentUser']);
    }
    $result = $db->query("SELECT parameter,standName FROM stands LEFT JOIN history ON stands.standId=parameter WHERE bikeNum=$bikeNum AND action IN ('RETURN','FORCERETURN') ORDER BY time DESC LIMIT 1");
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $standId = $row['parameter'];
        $stand = $row['standName'];
    }
    $result = $db->query("SELECT parameter FROM history WHERE bikeNum=$bikeNum AND action IN ('RENT','FORCERENT') ORDER BY time DESC LIMIT 1,1");
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $code = str_pad($row['parameter'], 4, '0', STR_PAD_LEFT);
    }
    if ($standId and $code) {
        $result = $db->query("UPDATE bikes SET currentUser=NULL,currentStand=$standId,currentCode=$code WHERE bikeNum=$bikeNum");
        $result = $db->query("INSERT INTO history SET userId=$userId,bikeNum=$bikeNum,action='REVERT',parameter='$standId|$code'");
        $result = $db->query("INSERT INTO history SET userId=0,bikeNum=$bikeNum,action='RENT',parameter=$code");
        $result = $db->query("INSERT INTO history SET userId=0,bikeNum=$bikeNum,action='RETURN',parameter=$standId");
        response('<h3>' . _('Bicycle') . ' ' . $bikeNum . ' ' . _('reverted to') . ' <span class="label label-primary">' . $stand . '</span> ' . _('with code') . ' <span class="label label-primary">' . $code . '</span>.</h3>');
        $smsSender->send($revertusernumber, _('Bike') . ' ' . $bikeNum . ' ' . _('has been returned. You can now rent a new bicycle.'));
    } else {
        response(_('No last stand or code for bicycle') . ' ' . $bikeNum . ' ' . _('found. Revert not successful!'), ERROR);
    }
}

function register($number, $code, $checkcode, $fullname, $email, $password, $password2, $existing)
{
    global $db, $configuration, $user;

    $number = $db->escape(trim($number));
    $code = $db->escape(trim($code));
    $checkcode = $db->escape(trim($checkcode));
    $fullname = $db->escape(trim($fullname));
    $email = $db->escape(trim($email));
    $password = $db->escape(trim($password));
    $password2 = $db->escape(trim($password2));
    $existing = $db->escape(trim($existing));
    $parametercheck = $number . ';' . str_replace(' ', '', $code) . ';' . $checkcode;
    if ($password != $password2) {
        response(_('Password do not match. Please correct and try again.'), ERROR);
    }
    if (issmssystemenabled() == true) {
        $result = $db->query("SELECT parameter FROM history WHERE userId=0 AND bikeNum=0 AND action='REGISTER' AND parameter='$parametercheck' ORDER BY time DESC LIMIT 1");
        if ($result->num_rows == 1) {
            if (!$existing) { // new user registration
                $result = $db->query("INSERT INTO users SET userName='$fullname',password=SHA2('$password',512),mail='$email',number='$number',privileges=0");
                $userId = $db->getLastInsertId();
                sendConfirmationEmail($email);
                response(_('You have been successfully registered. Please, check your email and read the instructions to finish your registration.'));
            } else { // existing user, password change
                $userId = $user->findUserIdByNumber($number);
                $result = $db->query("UPDATE users SET password=SHA2('$password',512) WHERE userId='$userId'");
                response(_('Password successfully changed. Your username is your phone number. Continue to') . ' <a href="' . $configuration->get('systemURL') . '">' . _('login') . '</a>.');
            }
        } else {
            response(_('Problem with the SMS code entered. Please check and try again.'), ERROR);
        }
    } else { // SMS system disabled
        $result = $db->query("INSERT INTO users SET userName='$fullname',password=SHA2('$password',512),mail='$email',number='',privileges=0");
        $userId = $db->getLastInsertId();
        $result = $db->query("UPDATE users SET number='$userId' WHERE userId='$userId'");
        sendConfirmationEmail($email);
        response(_('You have been successfully registered. Please, check your email and read the instructions to finish your registration. Your number for login is:') . ' ' . $userId);
    }
}

function checkprivileges($userid)
{
    global $db, $user;
    $privileges = $user->findPrivileges($userid);
    if ($privileges < 1) {
        response(_('Sorry, this command is only available for the privileged users.'), ERROR);
        exit;
    }
}

function smscode($number)
{
    global $db, $smsSender, $user, $phonePurifier;
    srand();

    $number = $phonePurifier->purify($number);
    $number = $db->escape($number);
    $userexists = 0;
    if ($user->findUserIdByNumber($number)) {
        $userexists = 1;
    }

    $smscode = chr(rand(65, 90)) . chr(rand(65, 90)) . ' ' . rand(100000, 999999);
    $smscodenormalized = str_replace(' ', '', $smscode);
    $checkcode = md5('WB' . $number . $smscodenormalized);
    if (!$userexists) {
        $text = _('Enter this code to register:') . ' ' . $smscode;
    } else {
        $text = _('Enter this code to change password:') . ' ' . $smscode;
    }

    $text = $db->escape($text);

    if (!issmssystemenabled()) {
        $result = $db->query("INSERT INTO sent SET number='$number',text='$text'");
    }

    $result = $db->query("INSERT INTO history SET userId=0,bikeNum=0,action='REGISTER',parameter='$number;$smscodenormalized;$checkcode'");

    if (DEBUG === true) {
        response($number, 0, array('checkcode' => $checkcode, 'smscode' => $smscode, 'existing' => $userexists));
    } else {
        $smsSender->send($number, $text);
        if (issmssystemenabled() == true) {
            response($number, 0, array('checkcode' => $checkcode, 'existing' => $userexists));
        } else {
            response($number, 0, array('checkcode' => $checkcode, 'existing' => $userexists));
        }
    }
}

function trips($userId, $bike = 0)
{
    global $db;
    $bikeNum = intval($bike);
    if ($bikeNum) {
        $result = $db->query("SELECT longitude,latitude FROM `history` LEFT JOIN stands ON stands.standid=history.parameter WHERE bikenum=$bikeNum AND action='RETURN' ORDER BY time DESC");
        while ($row = $result->fetch_assoc()) {
            $jsoncontent[] = array('longitude' => $row['longitude'], 'latitude' => $row['latitude']);
        }
    } else {
        $result = $db->query("SELECT bikeNum,longitude,latitude FROM `history` LEFT JOIN stands ON stands.standid=history.parameter WHERE action='RETURN' ORDER BY bikeNum,time DESC");
        $i = 0;
        while ($row = $result->fetch_assoc()) {
            $bikenum = $row['bikeNum'];
            $jsoncontent[$bikenum][] = array('longitude' => $row['longitude'], 'latitude' => $row['latitude']);
        }
    }
    echo json_encode($jsoncontent); // TODO change to response function
}

function validatecoupon($userid, $coupon)
{
    global $db, $creditSystem;
    if ($creditSystem->isEnabled() == false) {
        return;
    }
    // if credit system disabled, exit
    $result = $db->query("SELECT coupon,value FROM coupons WHERE coupon='" . $coupon . "' AND status<'2'");
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $value = $row['value'];
        $result = $db->query("UPDATE credit SET credit=credit+'" . $value . "' WHERE userId='" . $userid . "'");
        $result = $db->query("INSERT INTO history SET userId=$userid,bikeNum=0,action='CREDITCHANGE',parameter='" . $value . '|add+' . $value . '|' . $coupon . "'");
        $result = $db->query("UPDATE coupons SET status='2' WHERE coupon='" . $coupon . "'");
        response('+' . $value . ' ' . $creditSystem->getCreditCurrency() . '. ' . _('Coupon') . ' ' . $coupon . ' ' . _('has been redeemed') . '.');
    }
    response(_('Invalid coupon, try again.'), 1);
}

function changecity($userid, $city)
{
    global $db, $configuration;

    if (in_array($city, $configuration->get('cities'))) {
        $result = $db->query("UPDATE users SET city='$city' WHERE userId=" . $userid);
        response('City changed');
    }
    response(_('Invalid City.'), 1);
}


function mapgetmarkers($userId)
{
    global $db, $configuration, $user;
	$filtercity = '';
    if ($configuration->get('cities')) {
        if ($userId != 0) {
            $filtercity = ' AND city = "' . $user->findCity($userId) . '" ';
        } else {
            $filtercity = "";
        }
    }
    $jsoncontent = array();
    $result = $db->query('SELECT standId,count(bikeNum) AS bikecount,standDescription,standName,standPhoto,longitude AS lon, latitude AS lat FROM stands LEFT JOIN bikes on bikes.currentStand=stands.standId WHERE stands.serviceTag=0 '.$filtercity.' GROUP BY standName ORDER BY standName');
    while ($row = $result->fetch_assoc()) {
        $jsoncontent[] = $row;
    }
    echo json_encode($jsoncontent); // TODO proper response function
}

function mapgetlimit($userId)
{
    global $db, $auth, $creditSystem;

    if (!$auth->isLoggedIn()) {
        response('');
    }

    $result = $db->query("SELECT count(*) as countRented FROM bikes where currentUser=$userId");
    $row = $result->fetch_assoc();
    $rented = $row['countRented'];

    $result = $db->query("SELECT userLimit FROM limits where userId=$userId");
    $row = $result->fetch_assoc();
    $limit = $row['userLimit'];

    $currentlimit = $limit - $rented;

    $userCredit = $creditSystem->getUserCredit($userId);

    echo json_encode(array('limit' => $currentlimit, 'rented' => $rented, 'usercredit' => $userCredit));
}

function mapgeolocation($userid, $lat, $long)
{
    global $db;

    $result = $db->query("INSERT INTO geolocation SET userId='$userid',latitude='$lat',longitude='$long'");

    response('');
}; // TODO for admins: show bikes position on map depending on the user (allowed) geolocation, do not display user bikes without geoloc

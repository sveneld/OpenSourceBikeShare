<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="sk" lang="sk">
    <head><title>Report</title>
	<meta http-equiv="Expire" content="now" />
	<meta http-equiv="Pragma" content="no-cache" />
	<meta name="description" content="DATANEST na mape"/>
	<meta name="copyright" content="Copyright (c) 2011 Michal Maly"/>
	<meta name="author" content="Michal Maly"/>
        <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
	<link href="style.css" type="text/css" rel="stylesheet" />
    </head>
<body>
<h2>Stands Statistics Report</h2>

<?php

error_reporting(-1);

require("config.php");

function report()
{
    global $dbServer, $dbUser, $dbPassword, $dbName;
    
    $mysqli = new mysqli($dbServer, $dbUser, $dbPassword, $dbName);

    if ($result = $mysqli->query("select max(c) as 'users', year(d) as 'year',month(d) as 'month' from (select count(A.userid) as c,B.t as d from (SELECT min(time) as t, userid
FROM `history` where action=\"RENT\" group by userid ) as A, ( SELECT min(time) as t, userid
FROM `history` where action=\"RENT\" group by userid ) as B where A.t<=B.t group by B.t) as S group by year(d),month(d)")) {
        $data = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        echo "problem s sql dotazom";
        error("select not fetched");
    }
    
    echo '<table style="width:50%">';
#	echo '<caption>last usage</caption>';
        
    echo "<tr>";
    $cols = array("year","month", "users");
    foreach ($cols as $col) {
        echo "<th>$col</th>";
    }
    echo "</tr>";
     
    for ($i=0; $i<count($data); $i++) {
        echo "<tr>";
        foreach ($cols as $col) {
            echo "<td>";
            echo $data[$i][$col];
            echo "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
}

report();

?>


</body></html>


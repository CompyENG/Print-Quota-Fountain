<?php
// Check to make sure the user hasn't requested in the last 24 hours, and send it off!
include("mysql.inc.php");

$sql = "SELECT * FROM papercutRequests WHERE username='".mysql_real_escape_string($_POST['username'])."' AND time >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
$sqlr = mysql_query($sql);
if(mysql_num_rows($sqlr) > 0) {
    echo "<p>Error: You have requested quota in the last 24 hours.  You can only request quota from this system once per day.</p>";
    die();
}

if($_POST['amount'] > 1) {
    echo "<p>Error: Sorry, you can only request up to $1 from this system.</p>";
    die();
}

if($_POST['amount'] < 0) {
    echo "<p>Error: Did you put in a negative amount...? Who does that?!?</p>";
    die();
}

if($_POST['username'] == "") {
    die("<p>Error: Looks like username is blank! Please put in a username!</p>");
}

$amount = sprintf("$%01.2f", $_POST['amount']);

// Should be all good... send it off!
include("http.php");
$http=new http_class;
$http->debug=0;
$http->html_debug=1;
$http->follow_redirect=1;

$url=$pcServer."/app";
$error=$http->GetRequestArguments($url,$arguments);

$arguments["RequestMethod"]="POST";
$arguments["PostValues"]=array(
    "inputUsername"=>$pcUsername,
    "inputPassword"=>$pcPassword,
    "service"=>'direct/0/Home/$Form',
    '$PropertySelection'=>"en",
    '$Submit'=>"Log in",
    'sp'=>"S0",
    "Form0"=>'$Hidden,inputUsername,inputPassword,$PropertySelection,$Submit',
    '$Hidden'=>"F"
);

$error=$http->Open($arguments);
$error=$http->SendRequest($arguments);
$error=$http->ReadWholeReplyBody($body1);
$http->Close();

// Find the link to the transfer page
// Looks like: <a href="/app;jsessionid=1343y1z9dc0j7?service=page/UserTransfer" id="linkUserTransfer">
//echo $body1;
preg_match("/\\/app;jsessionid=(.*?)\\?service=/", $body1, $matches);
$jsessionid = $matches[1];

$url = $pcServer."/app;jsessionid=".$jsessionid."?service=page/UserTransfer";
$arguments = array(); // Clear arguments array
$error = $http->GetRequestArguments($url, $arguments);
$error = $http->Open($arguments);
$error=$http->SendRequest($arguments);
$error=$http->ReadWholeReplyBody($body2);
$http->Close();

preg_match('/name="\\$Hidden" value="(.*?)"\\/>/', $body2, $matches2);
$hiddenValue = $matches2[1];

// Now, send it!
$url = $pcServer."/app;jsessionid=".$jsessionid;
$arguments = array(); // Clear arguments array
$error = $http->GetRequestArguments($url, $arguments);
$arguments["RequestMethod"]="POST";
$arguments["PostValues"]=array(
    "service"=>"direct/1/UserTransfer/transferForm",
	"sp"=>"S0",
	"Form0"=>'$Hidden,inputAmount,inputToUsername,inputComment,$Submit',
	'$Hidden'=>$hiddenValue,
	'inputAmount'=>$amount,
	'inputToUsername'=>$_POST['username'],
	'inputComment'=>"Transfer from Print Fountain",
	'$Submit'=>"Transfer"	
);
$error = $http->Open($arguments);
$error=$http->SendRequest($arguments);
$error=$http->ReadWholeReplyBody($body1);
$http->Close();

// Let's check!
if(!strpos($body1, "The user ".$_POST['username']." can not be found.")) {
    if(strpos($body1, "The transfer has been successfully applied.") > 0) {
        $sql = "INSERT INTO papercutRequests (time,username,amount) VALUES (NOW(), '".mysql_real_escape_string($_POST['username'])."', ".str_replace("$", "", $amount).")";
        //echo $sql."<br>";
        $sqlr = mysql_query($sql);
        echo "<p>Quota sent to ".$_POST['username']."!</p>";
    } else {
        echo "<p>Error: Some error occured in the transfer.  Please try again later.</p>";
    }
} else {
    echo "<p>Error: There doesn't seem to be a user with username \"".$_POST['username']."\". Please try again.</p>";
}

// Do a logout :/
$url = $pcServer.'/app;jsessionid='.$jsessionid.'?service=direct/1/UserTransactions/$UserBorder.logoutLink';
$arguments = array();
$error = $http->GetRequestArguments($url, $arguments);
$error = $http->Open($arguments);
$error = $http->SendRequest($arguments);
$error = $http->ReadWholeReplyBody($body3);
$http->Close();

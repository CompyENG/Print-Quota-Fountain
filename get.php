<?php
include("mysql.inc.php");
// This will handle checking PaperCut to verify that we received the funds

$amount = sprintf("$%01.2f", $_POST['amount']);
$amountRegEx = str_replace('.', '\.', str_replace('$', '\$', $amount));
$from = $_POST['username'];

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

// Intermediate step -- get the table, even though we don't use it:
$url = $pcServer."/app;jsessionid=".$jsessionid."?service=page/UserTransactions";
$arguments = array();
$error = $http->GetRequestArguments($url, $arguments);
$error = $http->Open($arguments);
$error = $http->SendRequest($arguments);
$error = $http->ReadWholeReplyBody($body3);
$http->Close();

$url=$pcServer."/app;jsessionid=".$jsessionid."?service=direct/1/UserTransactions/accTrans.exportLogs.csv&sp=SCSV&sp=F";
$arguments = array();
$error = $http->GetRequestArguments($url, $arguments);
$error = $http->Open($arguments);
$error = $http->SendRequest($arguments);
$error = $http->ReadWholeReplyBody($body2);
$http->Close();

// body2 has a good CSV file.  Just need to figure out how to parse it correctly -- but I need someone to send me some funds in order to do that
//  I'll make a simple regex that should be able to find if the funds were sent and when
//
// Line I'm looking for:
//  2012-01-18 19:52:49,USER,rgraese,$0.01,$17.83,TRANSFER,"Transfer from user ""ksmith7"" to user ""rgraese"" - No comment",ksmith7
//$amount = "0.01";
//$amountRegEx = '\$0\.01';
//$from = "ksmith7";
$pattern = '/^([0-9\-: ]*?),USER,'.$pcUsername.','.$amountRegEx.',\$[0-9]+\.[0-9]{2},TRANSFER,"Transfer from user ""'.$from.'"" to user ""'.$pcUsername.'"" \- (.*?)",'.$from.'/im';
preg_match($pattern, $body2, $matches);
if(count($matches) > 0) { // Found a match!
	// $matches[1] contains time and date
	// $matches[2] contains comment
	// Verify that this isn't in the DB already
	$sql = "SELECT * FROM papercutDonations WHERE time='".$matches[1]."' AND username='".$from."' AND amount='".str_replace('$', '', $amount)."'";
	$sqlr = mysql_query($sql);
	if(mysql_num_rows($sqlr) == 0) {
		// Run the insert!
		$sql = "INSERT INTO papercutDonations (time, username, amount, verified, comment) VALUES ('".$matches[1]."', '".mysql_real_escape_string($from)."', ".str_replace('$', '', $amount).", 1, '".mysql_real_escape_string($matches[2])."')";
		mysql_query($sql) or die(mysql_error());
		echo "It worked!";
	} else {
		echo "Sorry, it looks like this donation has already been entered!";
	}
} else {
	echo "No match found.";
}
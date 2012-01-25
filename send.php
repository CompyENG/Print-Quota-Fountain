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

// Make sure they aren't requesting more than is in the fountain
$sql = "SELECT SUM(amount) AS s FROM papercutDonations";
$sqlr = mysql_query($sql);
$sr = mysql_fetch_array($sqlr);
$donated = $sr['s'];

$sql2 = "SELECT SUM(amount) AS s FROM papercutRequests";
$sqlr2 = mysql_query($sql2);
$sr2 = mysql_fetch_array($sqlr2);
$requested = $sr2['s'];
if(($donated-$requested) > $_POST['amount']) {
    echo "<p>Error: Sorry, there are insufficient funds in the fountain to fulfill that request.</p>";
}

if($_POST['username'] == "") {
    die("<p>Error: Looks like username is blank! Please put in a username!</p>");
}

$amount = sprintf("$%01.2f", $_POST['amount']);

// Should be all good... send it off!
include("papercut.class.php");
$pc = new PaperCut($pcServer);
$pc->setComment("Transfer from Print Fountain");
if(!$pc->login($pcUsername, $pcPassword)) {
	die("<p>Error: Could not login to PaperCut.  Contact <a href='mailto:bobby.graese@valpo.edu'>Bobby</a> to fix this.</p>");
}

if($pc->transfer($amount, $_POST['username'])) {
	$sql = "INSERT INTO papercutRequests (time,username,amount) VALUES (NOW(), '".mysql_real_escape_string($_POST['username'])."', ".str_replace("$", "", $amount).")";
    mysql_query($sql);
	echo "<p>Quota sent to ".$_POST['username']."!</p>";
} else {
	echo "<p>An error occured in the transfer: <strong>".$pc->getError()."</strong>.  Please try again later.</p>";
}

// Do a logout :/
$pc->logout();

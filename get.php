<?php
include("mysql.inc.php");
// This will handle checking PaperCut to verify that we received the funds

$amount = sprintf("$%01.2f", $_POST['amount']);
$amountRegEx = str_replace('.', '\.', str_replace('$', '\$', $amount));
$from = $_POST['username'];

include("papercut.class.php");
$pc = new PaperCut($pcServer);
$pc->login($pcUsername, $pcPassword);

$check = $pc->verifyReceive($amountRegEx, $from);
if($check['success']) { // Found a match!
	// Verify that this isn't in the DB already
	$sql = "SELECT * FROM papercutDonations WHERE time='".$check['time']."' AND username='".$from."' AND amount='".str_replace('$', '', $amount)."'";
	$sqlr = mysql_query($sql);
	if(mysql_num_rows($sqlr) == 0) {
		// Run the insert!
		$sql = "INSERT INTO papercutDonations (time, username, amount, verified, comment) VALUES ('".$check['time']."', '".mysql_real_escape_string($from)."', ".str_replace('$', '', $amount).", 1, '".mysql_real_escape_string($check['comment'])."')";
		mysql_query($sql) or die(mysql_error());
		echo "Added donation to database! Thank you for your contribution!";
	} else {
		echo "Sorry, it looks like this donation has already been entered!";
	}
} else {
	echo "<p>Error: ".$pc->getError().".  Please try again later.  If you are sure the transfer went through correctly, contact <a href='mailto:bobby.graese@valpo.edu'>Bobby</a> and he can manually check the transaction.</p>";
}

$pc->logout();
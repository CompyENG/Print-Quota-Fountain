<?php
// Since I'm starting with this in a very modular fashion,
//  this file will simply display the form where you can request more quota.
//  All checks on this data are done in send.php
?>
<form action="send.php" method="POST">
<label for="username" style="width:250px;float:left;">Your username:</label><input type="text" id="username" name="username" maxlength="8"><br>
<label for="amount" style="width:250px;float:left;">Amount you would like (max $1.00):<span style="float:right">$</span></label><input type="text" id="amount" name="amount" value="0.05"><br>
<input type="submit"> (<strong>Please</strong> only click once!)</form>
<p>Available quota in the fountain: <?php
include("mysql.inc.php");
$sql = "SELECT SUM(amount) AS s FROM papercutDonations";
$sqlr = mysql_query($sql);
$sr = mysql_fetch_array($sqlr);
$donated = $sr['s'];

$sql2 = "SELECT SUM(amount) AS s FROM papercutRequests";
$sqlr2 = mysql_query($sql2);
$sr2 = mysql_fetch_array($sqlr2);
$requested = $sr2['s'];

echo "$".($donated-$requested);
?>.  If you would like to check how much you have in your account, <a href="<?=$pcServer?>">click here</a>.</p>
<p>Please note that to avoid abuse of this system, there are some restrictions in place.  First, you can only requests $1 at a time.  Sorry if this is an issue, but it's to avoid people stealing all the quota from the fountain.  Second, you can only request funds from the fountain once every 24 hours.  Again, it's just to avoid abuse.</p>
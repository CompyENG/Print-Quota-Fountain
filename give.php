<?php
// This'll just be a form -- fill out your username and how much you gave and we'll add it to the DB
?>
<form action="get.php" method="POST">
<label for="username" style="width:200px;float:left">Your username:</label><input type="text" id="username" name="username" maxlength="8"><br>
<label for="amount" style="width:200px;float:left">Amount donated:<span style="float:right">$</span></label><input type="text" id="amount" name="amount" value="0.00"><br>
<input type="submit"></form>
<p>Directions:
<ol><li>Go to <a href="http://labprinters.valpo.edu:9191">the papercut interface</a> and login.</li>
<li>Click on "Transfers" on the left.</li>
<li>Send however much you want/can to ______. Feel free to leave a comment, too!</li>
<li>Come back to this page, put in your username and the amount you donated.</li></ol>
That's it! Please note that if you don't come back and fill out this form, you'll simply be giving me print quota. Sorry :/ If you have any questions, there's a link to e-mail me below.
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

printf("$%01.2f", ($donated-$requested));
?></p>
<p>Disclaimer: This money gets donated to _______'s print account.  I needed an account to collect money, and mine was convenient to use.  However, I do keep track of how much money is donated, and I won't use donated money for my personal use (unless I decide I need to use the print fountain a bit, in which case I'll go through the normal request process).</p>
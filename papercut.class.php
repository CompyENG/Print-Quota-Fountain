<?php
require_once("http.php");

class PaperCut {
	// TODO: Define all errors as constants. Just kinda good practice -- that way the UI can define readable errors
	/* We must be able to:
	 *  1. Login
	 *  2. Transfer quota
	 *  3. Logout
	 *  4. Verify a received amount
	 * */
	 
	// Keep track of these temporarily, just in case!
	var $username;
	var $password;
	var $comment;
	var $error;
	
	var $sessionId;
	var $loggedIn;
	var $server;
	
	// So we can reuse our http class
	var $http;
	
	function PaperCut($server) {
		// Make sure we don't accidentally put a '/' at the end of $server
		if($server[strlen($server)-1] == '/') {
			$this->server = substr($server, 0, -1);
		} else {
			$this->server = $server;
		}
		
		$this->loggedIn = false;
		$this->comment = "";
		$this->error = "";
		
		$this->http = new http_class;
		$this->http->debug=0;
		$this->http->html_debug=0;
		$this->http->follow_redirect=1;
	}
	
	function setComment($comment) {
		$this->comment = $comment;
	}
	
	function getComment() {
		return $this->comment;
	}
	
	function getError() {
		return $this->error;
	}
	
	// Returns true on success, false on failure.
	function login($username, $password) {
		$this->username = $username;
		$this->password = $password;
		
		// TODO: Add error checking from http class.
		$url=$this->server."/app";
		$req=$this->http->GetRequestArguments($url,$arguments);

		$arguments["RequestMethod"]="POST";
		$arguments["PostValues"]=array(
			"inputUsername"=>$this->username,
			"inputPassword"=>$this->password,
			"service"=>'direct/0/Home/$Form',
			'$PropertySelection'=>"en",
			'$Submit'=>"Log in",
			'sp'=>"S0",
			"Form0"=>'$Hidden,inputUsername,inputPassword,$PropertySelection,$Submit',
			'$Hidden'=>"F"
		);

		$req=$this->http->Open($arguments);
		$req=$this->http->SendRequest($arguments);
		$req=$this->http->ReadWholeReplyBody($body1);
		$this->http->Close();

		// Find the session id
		if(preg_match("/\\/app;jsessionid=(.*?)\\?service=/", $body1, $matches)) {
			$this->sessionId = $matches[1];
			$this->loggedIn = true;
			$this->error = "";
			return true;
		} else {
			// If there's no session id, then our login failed.
			$this->loggedIn = false;
			$this->error = "Login failed.";
			return false;
		}
	}
	
	// We should be logged in already, and the class should be tracking any intermediate
	//  values needed. So, we just need an amount and the user to send to.  If a "comment"
	//  field is allowed, the child class can implement a "setComment" function, and the
	//  actual implementation of the transfer function can handle that.
	// To avoid uneeded complexity, let's just expect that $amount and $to are well-formatted.
	//  This will return a bool -- true if the transfer succeeds, false otherwise.
	function transfer($amount, $to) {
		// TODO: http class error checking
		if(!$this->loggedIn) {
			$this->error = "Not logged in.";
			return false;
		}
		
		// Grab the transfer page.
		$url = $this->server."/app;jsessionid=".$this->sessionId."?service=page/UserTransfer";
		$req = $this->http->GetRequestArguments($url, $arguments);
		$req = $this->http->Open($arguments);
		$req = $this->http->SendRequest($arguments);
		$req = $this->http->ReadWholeReplyBody($body2);
		$this->http->Close();

		// Get hidden form value from the transfer page
		if(preg_match('/name="\\$Hidden" value="(.*?)"\\/>/', $body2, $matches2)) {
			$hiddenValue = $matches2[1];
		} else {
			$this->error = "Couldn't grab transfer page.";
			return false;
		}

		// Now, send it!
		$url = $this->server."/app;jsessionid=".$this->sessionId;
		$arguments = array(); // Clear arguments array
		$req = $this->http->GetRequestArguments($url, $arguments);
		$arguments["RequestMethod"]="POST";
		$arguments["PostValues"]=array(
			"service"=>"direct/1/UserTransfer/transferForm",
			"sp"=>"S0",
			"Form0"=>'$Hidden,inputAmount,inputToUsername,inputComment,$Submit',
			'$Hidden'=>$hiddenValue,
			'inputAmount'=>$amount,
			'inputToUsername'=>$to,
			'inputComment'=>$this->comment,
			'$Submit'=>"Transfer"	
		);
		$req = $this->http->Open($arguments);
		$req = $this->http->SendRequest($arguments);
		$req = $this->http->ReadWholeReplyBody($body1);
		$this->http->Close();
		
		if(!strpos($body1, "The user ".$to." can not be found.")) {
			if(strpos($body1, "The transfer has been successfully applied.") > 0) {
				$this->error = "";
				return true;
			} else {
				$this->error = "Transfer error";
				return false;
			}
		} else {
			$this->error = "No such user";
			return false;
		}
	}
	
	// So we don't leave lingering sessions on the server, we should provide a way to logout.
	//  If a particular implementation doesn't require this, it can leave the method body blank,
	//  or just "return true" or something.
	function logout() {
		$url = $this->server.'/app;jsessionid='.$this->sessionId.'?service=direct/1/UserTransactions/$UserBorder.logoutLink';
		$req = $this->http->GetRequestArguments($url, $arguments);
		$req = $this->http->Open($arguments);
		$req = $this->http->SendRequest($arguments);
		$req = $this->http->ReadWholeReplyBody($body3);
		$this->http->Close();
		
		// Ideally, I should verify this logout :/
		$this->error = "";
		return true;
	}
	
	// Verify that we recently received $amount from $user.  This will be a bit trickier.  It should
	//  return an array detailed below.
	//  We can then verify that against the DB elsewhere to make sure we haven't already logged
	//  this entry.  This can return false if it can't find the entry specified.
	// Again, assume that $amount and $user are well-formatted.
	//
	// Return: array( success => bool,
	//				  time => string time of transfer,
	// 				  comment => string comment in transfer )
	// Note: time and comment may not be defined if success is false
	function verifyReceive($amount, $user) {
		if(!$this->loggedIn) {
			$this->error = "Not logged in";
			return array('success' => false);
		}
		
		// Intermediate step -- get the table, even though we don't use it:
		$url = $this->server."/app;jsessionid=".$this->sessionId."?service=page/UserTransactions";
		$req = $this->http->GetRequestArguments($url, $arguments);
		$req = $this->http->Open($arguments);
		$req = $this->http->SendRequest($arguments);
		$req = $this->http->ReadWholeReplyBody($body3);
		$this->http->Close();

		$url = $this->server."/app;jsessionid=".$this->sessionId."?service=direct/1/UserTransactions/accTrans.exportLogs.csv&sp=SCSV&sp=F";
		$arguments = array();
		$req = $this->http->GetRequestArguments($url, $arguments);
		$req = $this->http->Open($arguments);
		$req = $this->http->SendRequest($arguments);
		$req = $this->http->ReadWholeReplyBody($body2);
		$this->http->Close();
		
		// body2 has a good CSV file.  Just need to figure out how to parse it correctly -- but I need someone to send me some funds in order to do that
		//  I'll make a simple regex that should be able to find if the funds were sent and when
		//
		// Line I'm looking for:
		//  2012-01-18 19:52:49,USER,rgraese,$0.01,$17.83,TRANSFER,"Transfer from user ""ksmith7"" to user ""rgraese"" - No comment",ksmith7
		//$amount = "0.01";
		//$amountRegEx = '\$0\.01';
		//$from = "ksmith7";
		$pattern = '/^([0-9\-: ]*?),USER,'.$this->username.','.$amount.',\$[0-9]+\.[0-9]{2},TRANSFER,"Transfer from user ""'.$user.'"" to user ""'.$this->username.'"" \- (.*?)",'.$user.'/im';
		if(preg_match($pattern, $body2, $matches)) {
			// We found it!
			// $matches[1] contains time and date
			// $matches[2] contains comment
			$this->error = "";
			return array('success' => true,
						 'time'    => $matches[1],
						 'comment' => $matches[2]);
		} else {
			$this->error = "Transaction not found";
			return array('success' => false);
		}		
	}
}	

<?php

    // oAuth 2.0 Configuration
	$OAUTH_SERVER = "https://auth.tfoundry.com/oauth";
    // ***********************************
    // Put your developer credentials here
    // ***********************************
	$CLIENT_ID = "******************************";
	$CLIENT_SECRET = "****************";

	// If we don't have a known access token already
	if (!isset($_SESSION['access_token']))
	{
		
		// Redirect back url
		$redirect = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
		
		// If we don't have a code, redirect to AT&T to get a new code.
		if (!isset($_GET['code']))
		{
			// Redirect
			$url = $OAUTH_SERVER . "/authorize?response_type=code&client_id=" . urlencode($CLIENT_ID) .
							"&scope=" . urlencode("webrtc") . "&redirect_uri=" . urlencode($redirect);
			Header("Location: " . $url);
			return;
		}
		
		// We have a code; we can convert it into an access token by doing a CURL
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $OAUTH_SERVER . "/token");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($ch, CURLOPT_POST, 1);
		
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			"Authorization: Basic " . base64_encode($CLIENT_ID .":". $CLIENT_SECRET),
			"Content-Type: application/x-www-form-urlencoded"
		));
		
		$bodystr = "grant_type=authorization_code&code=" . $_GET['code'] . "&redirect_uri=" . urlencode($redirect) .
									"&client_id=" . $CLIENT_ID . "&client_secret=".$CLIENT_SECRET;
		
		curl_setopt($ch, CURLOPT_POSTFIELDS, $bodystr);
		
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		
		$output = curl_exec($ch); 
		curl_close($ch);
		
		$json = json_decode($output);
		
		$_SESSION['access_token'] = $json->access_token;
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://auth.tfoundry.com/me.json?access_token=".$json->access_token);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		
		$userout = curl_exec($ch); 
		curl_close($ch);
		
		$user = json_decode($userout);
		

	}
	
	// Now output our nice little app <script src="https://c9.io/kormart/demo-project/workspace/html/wcg_2-1-25_phono.js"></script>

?>

<!DOCTYPE html> 
<html> 
 
<head>
	<meta charset="utf-8"> 
	<meta name="viewport" content="width=device-width, initial-scale=1"> 
	<title>eWeb</title> 
	<link rel="stylesheet" href="http://d2dx.com/scaffold1020/jquery-mobile.css" />
	<script src="http://d2dx.com/scaffold1020/jquery-164.js"></script>
	<script src="http://d2dx.com/scaffold1020/jquery-mobile.js"></script>
    <script src="https://c9.io/kormart/demo-project/workspace/html/wcg_2-1-26_gh_phono.js"></script> 
<script>
		
	sipdomain = "vims1.com";
	server = "https://api.foundry.att.com/a1/webrtc";
	
	function formatPhone(phonenum) {
		var regexObj = /^(?:\+?1[-. ]?)?(?:\(?([0-9]{3})\)?[-. ]?)?([0-9]{3})[-. ]?([0-9]{4})$/;
		if (regexObj.test(phonenum)) {
			var parts = phonenum.match(regexObj);
			var phone = "";
			if (parts[1]) { phone += "+1 (" + parts[1] + ") "; }
			phone += parts[2] + "-" + parts[3];
			return phone;
		}
		else {
			//invalid phone number
			return phonenum;
		}
	}
	
	function login(num)
	{
		self.num = num;
		self.phono = $.phono({
					server: server,
					apiKey: "oauth <?php echo $_SESSION['access_token']; ?>" , 
					video: false,
					
					onReady: function() {
						$.mobile.changePage($("#make-call"));
					},
					  
					phone: {
					
					  	onIncomingCall: function(evt)
					  	{
					  		self.call = evt.call;
					  		var rNum = evt.call.from;
					  		
							self.call.onHangup = function() {
								$.mobile.changePage($("#make-call"));
							};
					  		
					  		var match = rNum.match(/[0-9]+/);
					  		if (match.length > 0)
					  			rNum = match[0];
					  		rNum = formatPhone(rNum);
					  		$(".remote-user").text(rNum);
					  		$.mobile.changePage($("#incoming-call"));
					  	}
					}

				});
				
		$.mobile.changePage($("#logging-in"));
		
	}
	
	$(login);
	
	$(".incoming-call-answer").live("click", function() {
		self.call.answer();
		$.mobile.changePage($("#call"));
		self.call.onHangup = function() {
			$.mobile.changePage($("#make-call"));
		};
		self.call.onError = function() {
			$.mobile.changePage($("#make-call"));
		};
		
		self.call.onAddStream = function(e) {
			console.log("Onaddstream");
			remoteVideo.style.display = "block"
			remoteVideo.src = webkitURL.createObjectURL(e.stream);
								
			//localVideo.style.display = "block"
			//localVideo.src = webkitURL.createObjectURL(this.localStreams[0]);
		};
	});
	
	$(".incoming-call-reject").live("click", function() {
		self.call.hangup();
		$.mobile.changePage($("#make-call"));
	});
	
	$(".call-hangup").live("click", function() {
		self.call.hangup();
		$.mobile.changePage($("#make-call"));
	});
	
	function do_login()
	{
		login(username.value);
	}
	
	function do_call()
	{
		make_call(remote_number.value);
	}
	
	function make_call(num)
	{
	
		$(".remote-user").text(formatPhone(num));
		self.call = phono.phone.dial("sip:"+num + "@" + sipdomain, {
			onRing: function() {
				
			},
			onAnswer: function() {
				this.onHangup = function() {
					$.mobile.changePage($("#make-call"));
				};
				$.mobile.changePage($("#call"));
			},
			onHangup: function() {
				setTimeout(function() { if ($.mobile.activePage.attr("id") == "outgoing-call-rejected") $.mobile.changePage($("#make-call"), {reverse: true});}, 2000);
				$.mobile.changePage($("#outgoing-call-rejected"), {transition: "fade"});
			},
			onError: function() {
				setTimeout(function() { if ($.mobile.activePage.attr("id") == "outgoing-call-rejected") $.mobile.changePage($("#make-call"), {reverse: true});}, 2000);
				$.mobile.changePage($("#outgoing-call-rejected"), {transition: "fade"});
			},
			onAddStream: function(e) {
				console.log("Onaddstream");
				//remoteVideo.style.display = "block"
				//remoteVideo.src = webkitURL.createObjectURL(e.stream);
								
				//localVideo.style.display = "block"
				//localVideo.src = webkitURL.createObjectURL(this.localStreams[0]);
			},
		});
		
		$.mobile.changePage($("#outgoing-call"));
	
	}
	
	</script>
</head> 

	
<body> 

<!-- Start of first page: #one -->
<div data-role="page" id="main" data-theme="a">

	<div data-role="header">
		<h1>Web Phone</h1>
	</div><!-- /header -->

	<div data-role="content" id="one">	
		<h2>Welcome to webphone!</h2>
		
		<p>This is a softphone using the WebRTC API from Foundry. Enter your access token below.</p>

		<p>Access Token: <input type="text" id="username"/></p>
		<button onclick="do_login();">Log In!</button> 
	</div><!-- /content -->
	
	<div data-role="footer">
		<h5>wTime 2.0</h5>
	</div><!-- /footer -->
</div><!-- /page one -->

<!-- Start of first page: #one -->
<div data-role="page" id="logging-in" data-theme="a">

	<div data-role="header">
		<h1>Web Phone</h1>
	</div><!-- /header -->

	<div data-role="content" id="one">	
		<h2>Welcome to webphone!</h2>
		
		<p>Logging in. Please wait!</p>

	</div><!-- /content -->
	
	<div data-role="footer">
		<h5>wTime 2.0</h5>
	</div><!-- /footer -->
</div><!-- /page one -->

<!-- Start of first page: #one -->
<div data-role="page" id="make-call" data-theme="a">

	<div data-role="header">
		<h1>Web Phone</h1>
	</div><!-- /header -->

	<div data-role="content" id="one">	
		<h3>Webphone for <span id="gui_user"><?php echo $user->info->first_name; ?></span> with number <span id="selfnumber"><?php echo $user->virtual_identifiers->mobile[0]; ?></span> </h3>
		
		<p><input type="text" placeholder="Type number to call, format: 1..." id="remote_number"/></p>
		<button onclick="do_call();" >Call!</button> 
	</div><!-- /content -->
	
	<div data-role="footer">
		<h5>wTime 2.0</h5>
	</div><!-- /footer -->
</div><!-- /page one -->

<!-- Start of third page: #popup -->
<div data-role="page" id="incoming-call" data-theme="b">

	<div data-role="header">
		<h1>Incoming Call!</h1>
	</div><!-- /header -->

	<div data-role="content" data-theme="d">	
		<span style="display: block; width: 100%; text-align: center"><h2><span class="remote-user">User</span> is calling!</h2></span>
    <div class="ui-grid-a">
        <div class="ui-block-a"><a class="incoming-call-answer" href="" data-role="button" data-inline="false" data-icon="check" data-iconpos="right" data-theme="b"><strong>Answer</strong></a></div>
    	<div class="ui-block-b"><a class="incoming-call-reject" href="" data-role="button" data-inline="false" data-icon="check" data-iconpos="right" data-theme="e"><strong>Reject</strong></a></div>
    </div><!-- /grid-a -->

</div><!-- /content -->
	
	<div data-role="footer">
		<h5>eTime 2.0</h5>
	</div><!-- /footer -->
</div><!-- /page popup -->


<div data-role="page" id="outgoing-call" data-theme="a">

	<div data-role="header">
		<h1>Calling <span class="outgoing-call-user">User</span></h1>
	</div><!-- /header -->

	<div data-role="content" data-theme="a">
	<br/><br/>
	<br/><br/><span style="display: block; width: 100%; text-align: center"><h2>Waiting for <span class="remote-user">User</span> to answer...</h2></span>
	<br/><br/>
		<p><a class="call-hangup" href="" data-role="button" data-inline="false" data-icon="check" data-iconpos="right" data-theme="e">Hang up</a></p>	
	</div><!-- /content -->
	
	<div data-role="footer">
		<h5>eTime 2.0</h5>
	</div><!-- /footer -->
</div><!-- /page popup -->


<div data-role="page" id="outgoing-call-rejected" data-theme="a">

	<div data-role="header">
		<h1>Calling <span class="outgoing-call-user">User</span></h1>
	</div><!-- /header -->

	<div data-role="content" data-theme="a">
	<br/><br/>
	<br/><br/><span style="display: block; width: 100%; text-align: center"><h2><span class="remote-user">User</span> rejected the call, or failed to answer, or something else happened.</h2></span>
	<br/><br/>
	</div><!-- /content -->
	
	<div data-role="footer">
		<h5>eTime 2.0</h5>
	</div><!-- /footer -->
</div><!-- /page popup -->


<div data-role="page" id="call" data-theme="a">

	<div data-role="header">
		<h1>Call with <span class="remote-user">User</span></h1>
	</div><!-- /header -->

	<div data-role="content" data-theme="a">
		<div id="call-audio" style="display: inline;"><br/><br/>
			<br/><br/><span style="display: block; width: 100%; text-align: center"><h2><span class="call-time">Ongoing call...</span></h2></span>
			<p><a class="call-hangup" href="" data-role="button" data-inline="false" data-icon="check" data-iconpos="right" data-theme="e">Hang up</a></p>	
        	<br/><br/>
      		<video width="0px" height="0px" style="display: none; position: absolute; top: 0px; left: 0px" id="remoteVideo" autoplay="autoplay"></video>
		</div>
	</div><!-- /content -->
	
	<div data-role="footer">
		<h5>eTime 2.0</h5>
	</div><!-- /footer -->
</div><!-- /page popup -->


</body>
</html>
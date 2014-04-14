<?php
//login.php - interface for logging into the social networking site

//If member is already logged in, redirect to the profile page
session_start();
if ($_SESSION['memberID']) {
   //if already logged in, redirect
   header("Location: /social/profile.php?profileID=" . $_SESSION['memberID']);
}

/*Connect using the username "social_login" on the database "social_network" */
include($_SERVER['DOCUMENT_ROOT']."/social/social_login_login.php");

$db = new mysqli($host,$user,$pw,$database,$port,$socket)
      or die("Cannot connect to mySQL.");

/*Mary's test==================================/
$command = "select * from member_login";
$r = $db->query($command);
if (!$r) {
throw new Exception("Database Error [{$db->errno}] {$db->error}");
}
while ($d = $r->fetch_assoc()){
	foreach($d as $key=>$val){
		echo "$key => $val<br>";
	}
}
End of Mary's test===========================*/

$email = $_POST['email'];
$password = $_POST['password'];

//Only check if someone has submitted an email or a password
if ($email || $password) {
   
   //Make sure both exist and the email is valid 
   if (!($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
       $error_message = "Please enter the email address you used to join.  ";
   }
   else if (!($password)) {
       $error_message = "Please enter your password.  ";
   }
   else {
      //Now, check the database for a correct login
      $command = "SELECT memberID FROM member_login WHERE email = '".$db->real_escape_string($email)."' ". 
                 "AND password = password('".$db->real_escape_string($password)."');";
                 
      $result = $db->query($command);
      
echo "Command: " . $command;
      
      if ($data = $result->fetch_object()) {
         //correct login! Set session and redirect
         $_SESSION['memberID'] = $data->memberID;
         header("Location: /social/profile.php?profileID=" . $_SESSION['memberID']);
      }
      else {
         $error_message = "Sorry, your login was incorrect.  Please contact us if you've forgotten your password.";
      }
   }
}



//Include header
include($_SERVER['DOCUMENT_ROOT']."/.php_files/social_header.inc");
?>

<h4>Log in to access your profile and connect to others!</h4>
<span style="color:red;font-size:12px;">
<?php
if ($error_message) {
   echo $error_message;
}
?>
</span>

<form method="POST" action="/social/login.php">
<table>
<tr>
<td align="right">
Email address:
</td>
<td align="left">
<input type="text" size="24" maxlength="50" name="email" value="<? echo htmlentities($email); ?>">
</td>
</tr>
<tr>
<td align="right">
Password:
</td><td align="left">
<input type="password" size="24" maxlength="12" name="password" value="">
</td>
</tr>

<tr>
<td colspn="2">&nbsp;</td>
</tr>


<tr>
<td>&nbsp;</td>
<td align="center">
<input type="submit" value="Login">
</td></tr>
</table><br>
Not a member?  <a href="join.php">Click here</a> to join!
</form>
 
<br>
<br>
<?
//Include footer 
include($_SERVER['DOCUMENT_ROOT']."/.php_files/social_footer.inc");
?> 

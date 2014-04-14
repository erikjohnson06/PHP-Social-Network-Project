<?php
/*join.php -- interface for joining the social networking site*/

/*First, if a member's already logged in, redirect them to the profile page*/
session_start();
if ($_SESSION['memberID']) {
    /*If already logged in, redirect them:*/
    header("Location: /social/profile.php");
}

/*Here's a function to check inputs against a regular expression representing the proper input format.*/
function valid_input($myinput, $good_input) {
   if (preg_match("/$good_input/", $myinput, $regs)) {
      return true;
   }
   else {
      return false;
   }
}

if (count($_POST) > 0) {

   /*Connect using the username "social_login" on the database "social_network" */
   include($_SERVER['DOCUMENT_ROOT']."/social/social_login_login.php");

    $db = new mysqli($host,$user,$pw,$database,$port,$socket)
	   or die("Cannot connect to mySQL.");
	   
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $verify_password = trim($_POST['password2']);
    
    /*Regular expressions for emails and password. Password must includ 6-12 characters, 
    must have both alphanumberic characters AND numbers, and may allow a few special characters
    as well. Email must conform to a standard email format, and any name may contain dashes 
    and apostrophes, but must contain at least some letters. */
    $valid_pass = "^(?=.{6,12})(?=.*[A-Za-z])(?=.*\d)[a-zA-Z\d_!]+$";
    $valid_email = "[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}";
    $valid_name = "^[A-Za-z][A-Za-z-']+$";
    $letter_filter = "^[a-zA-Z]+$";
    
    /*First, ensure that all field are completed*/
    if (!($first_name && $last_name && $email && $password && $verify_password)) {
        $error_message = "Please make sure you've filled in all the form fields. ";
    }
    
    /*Check for proper formats for all inputs*/
    else if (strlen($first_name) > 25 || strlen($last_name) > 25) {
        $error_message = "Please make sure both your first and last names are fewer than 25 characters each.  ";
    }
    
    else if (!(valid_input($first_name, $valid_name)) || !(valid_input($last_name, $valid_name)) ||
             !(valid_input($last_name, $letter_filter)) || !(valid_input($last_name, $letter_filter))) {
        $error_message = "Please make sure you enter a valid name, which contains letters, hyphens or apostrophes only.  ";
    }
    
    else if (strlen($email) > 50 || !(valid_input($email, $valid_email))) {
        $error_message = "Please make sure you enter a valid email address, which is less than 50 characters.  ";
    }
    
    else if (strlen($password) > 12 || strlen($password) < 6 || !(valid_input($password, $valid_pass))) {
       $error_message = "Please make sure your password is between 6 and 12 characters, and is a combination of 
                         contains letters and numbers. Special characters allowed include underscores (_) and exclamation marks (!).  ";
    }
    
    /*Makes sure the both passwords match each other*/
    else if (!($password == $verify_password)) {
       $error_message = "Please make sure to type the same password in twice, to make sure it's what you want.";
    }
    
    else {
       /*Check the database for an existing member with this email*/
       $command = "SELECT memberID FROM member_login WHERE email = '". $db->real_escape_string($email)."';";
       $result = $db->query($command);
       
       if ($data = $result->fetch_object()) {
          $error_message = "We have found an existing member with that email address.  Please contact us if you 
                            have forgotten your password.";
       }
       else  {
         //Process the membership once all checks have passed
         $success = true;
         $memberID = '';
         
         //Start the transaction
          $command = "SET AUTOCOMMIT=0";
          $result = $db->query($command);
          $command = "BEGIN";
          $result = $db->query($command);
          
          //First, member login
          $command = "INSERT INTO member_login (memberID, email, password) " . 
                      "VALUES ('', '". $db->real_escape_string($email)."', password('".$db->real_escape_string($password)."'));";
          $result = $db->query($command);
          if ($result == false) {
              $success = false;
          }
          else {
              //Now, member info
             $memberID  = $db->insert_id;
             $command = "INSERT INTO member_info (memberID, first_name, last_name, date_enrolled) VALUES 
                        ('".$db->real_escape_string($memberID)."','".$db->real_escape_string($first_name)."','".$db->real_escape_string($last_name)."', now());";
             $result = $db->query($command);
             if ($result == false) {
                $success = false;
             }  
          }
          
          if (!$success) {
             $command = "ROLLBACK";
             $result = $db->query($command);
             $error_message = "We're sorry, there has been an error on our end.  Please contact us to report this bug.  ";
          }
          else {
             $command = "COMMIT";
             $result = $db->query($command);
            
             //Set session variable
             $_SESSION['memberID'] = $memberID;
          }
          $command = "SET AUTOCOMMIT=1";  //Return to autocommit
          $result = $db->query($command);
          
          //If successful, then redirect
          if ($success) {
            header("Location: /social/profile.php?profileID=" . $_SESSION['memberID']);
          }
       }
    }
}

/*A nice looking header*/
include($_SERVER['DOCUMENT_ROOT']."/.php_files/social_header.inc");
?>
 
<h4>Join now and start connecting.  Its easy and free!</h4>
<span style="color:red;font-size:12px;">
<?
if ($error_message) {
   echo $error_message;
}
?>
</span>
 
 
<form method="POST" action="">
<table>
<tr>
<td align="right">
Your First Name:
</td>
<td align="left">
<input type="text" size="25" maxlength="25" name="first_name" value="<? echo $_POST['first_name'] ?>">
</td>
</tr>
<tr>
<td align="right">
Your Last Name:
</td>
<td align="left">
<input type="text" size="25" maxlength="25" name="last_name" value="<? echo $_POST['last_name'] ?>">
</td>
</tr>
<tr>
<td align="right">
Your Email address:
</td>
<td align="left">
<input type="text" size="25" maxlength="50" name="email" value="<? echo $_POST['email'] ?>">
</td>
</tr>
<tr>
<td align="right">
Choose a Password:
</td><td align="left">
<input type="password" size="12" maxlength="12" name="password" value="">
</td>
</tr>
<tr>
<td align="right">
Please retype your Password:
</td><td align="left">
<input type="password" size="12" maxlength="12" name="password2" value="">
</td>
</tr>

<tr>
<td colspn="2">&nbsp;</td>
</tr>

<tr>
<td>&nbsp;</td>
<td align="left">
<input type="submit" value="Submit">
</td></tr>
</table><br><br>
Already a member?  <a href="/social/login.php">Click here</a> to log in!
</form>

<br>
<br>

<?
 /*a nice-looking footer */
 include($_SERVER['DOCUMENT_ROOT']."/.php_files/social_footer.inc");
?> 
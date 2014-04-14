<?php
/*password_reset.php -- interface for resetting your password*/

/*If a member's not logged in, redirect them to the home page*/
session_start();
if (!$_SESSION['memberID']) {
    header("Location: /social/index.php");
}

//Set a variable for the member ID based on the session variable
$memberID = $_SESSION['memberID'];

/*Here's a function to check inputs against a regular expression representing the proper input format.*/
function valid_input($myinput, $good_input) {
   if (preg_match("/$good_input/", $myinput, $regs)) {
      return true;
   }
   else {
      return false;
   }
}

/*This function can be called upon to verify that the password being entered matches 
the user's current password*/
function verify_password($memberID, $current_password, $db) {
      
   $command = "SELECT * FROM member_login WHERE memberID = '".$db->real_escape_string($memberID)."' ". 
              "AND password = password('".$db->real_escape_string($current_password)."');";
   $result = $db->query($command);
   
   if ($data = $result->fetch_object()) {
      return true;

   }
   else {
      return false;
   }
}

/*If the form has been submitted, proceed with the validation checks */
if ($_POST) {

   /*Connect using the username "social_login" on the database "social_network" */
   include($_SERVER['DOCUMENT_ROOT']."/social/social_login_login.php");

    $db = new mysqli($host,$user,$pw,$database,$port,$socket)
	   or die("Cannot connect to mySQL.");
	   
    $current_password = trim($_POST['current_password']);
    $new_password1 = trim($_POST['new_password1']);
    $new_password2 = trim($_POST['new_password2']);
    
    /*Regular expressions for new password. Password must include 6-12 characters, 
    must have both alphanumberic characters AND numbers, and may allow a few special characters
    as well. */
    $valid_pass = "^(?=.{6,12})(?=.*[A-Za-z])(?=.*\d)[a-zA-Z\d_!]+$";
    
    /*Ensure that all field are completed*/
    if (!($current_password && $new_password1 && $new_password2)) {
        $error_message = "Please complete all fields. ";
    }
    
    
    else if (strlen($new_password1) > 12 || strlen($new_password1) < 6 || !(valid_input($new_password1, $valid_pass))) {
       $error_message = "Please make sure your new password is between 6 and 12 characters, and is a combination of 
                         contains letters and numbers. Special characters allowed include underscores (_) and exclamation marks (!).  ";
    }
    
    /*Makes sure the both passwords match each other*/
    else if (!($new_password1 == $new_password2)) {
       $error_message = "New passwords do not match.";
    }
    
    else {
       /*Now, check the database to make sure the current password is correct*/
       
       if (!(verify_password($memberID, $current_password, $db))) {
           $error_message = "Invalid current password. Please make sure you are entering your password correctly.";
       }
 
       else  {
          
          //Update password in database, display a success message, and close the connection.
          $command = "UPDATE member_login SET password = password('". $db->real_escape_string($new_password1) ."') WHERE " . 
                     " memberID = '" . $db->real_escape_string($memberID) ."' ;";
          $result = $db->query($command);

          $success_message = "Your password has been successfully updated.";
          
          $db->close();
          }
       }
}

/*A nice looking header*/
include($_SERVER['DOCUMENT_ROOT']."/.php_files/social_header.inc");
?>

<span class="signed_in">Signed in as <? echo $_SESSION['user_email']?> </span><br>
<span class="signed_in">Not <a href="profile.php?logout=1"><? echo $_SESSION['first_name']?>? </a></span>
<br>


<h2>Reset Your Password</h2>
<span style="color:red;font-size:12px;">
<?
if ($error_message) {
   echo $error_message;
}
?>
</span>
<span style="color:blue;font-size:12px;">
<?
if ($success_message) {
   echo $success_message;
}
?>
</span>
 
<form method="POST" action="">
<table>

<tr>
  <td align="right">
  Current Password:
  </td><td align="left">
  <input type="password" size="24" maxlength="12" name="current_password" value="<? echo $_POST['current_password']; ?>">
  </td>
</tr>

<tr>
  <td align="right">
  New Password:
  </td><td align="left">
  <input type="password" size="24" maxlength="12" name="new_password1" value="<? echo $_POST['new_password1']; ?>">
  </td>
</tr>

<tr>
  <td align="right">
  Confirm New Password:
  </td><td align="left">
  <input type="password" size="24" maxlength="12" name="new_password2" value="<? echo $_POST['new_password2']; ?>">
  </td>
</tr>

<tr>
<td colspan="2" align="center">
<input type="submit" value="SUBMIT">
</td></tr>
</table><br>
</form>

<br>
<h4><a href="profile.php?profileID=<?echo $_SESSION['memberID']; ?>">Return to My Profile ></a></h4>


<?
 /*a nice-looking footer */
 include($_SERVER['DOCUMENT_ROOT']."/.php_files/social_footer.inc");
 

?> 
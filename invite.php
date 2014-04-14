<?php
/*invite.php -- interface for inviting others to the social networking site*/

/*Connect using the username "social_login" on the database "social_network" */
include($_SERVER['DOCUMENT_ROOT']."/social/social_login_login.php");

$db = new mysqli($host,$user,$pw,$database,$port,$socket)
      or die("Cannot connect to mySQL.");


/*First, make sure the member is logged in.*/
session_start();
if (!$_SESSION['memberID']) {
   
    //If the user is not logged in, redirect them to the home page:
    header("Location: http://". $_SERVER['HTTP_HOST'] ."/social/index.php");
}


/*This function will check inputs against a regular expression representing the proper input format.*/
function valid_input($myinput, $good_input) {
   if (preg_match("/$good_input/", $myinput, $regs)) {
      return true;
   }
   else {
      return false;
   }
}

/*This function is calle upon when all validation checks are complete, and the invitation
email is finally ready to be processed. It accepts the POST data array, an email template file, 
and a date as parameters. */
function send_invitation($form_data, $template_file, $date) {

   /*Use the email template and replace the strings located within
   with the values from the form_data array */  
   $email_message = file_get_contents($template_file);
   $email_message = str_replace("#DATE#", $date, $email_message);
   $email_message = str_replace("#NAME#", $form_data['first_name'] . " " . $form_data['last_name'], $email_message);
   $email_message = str_replace("#MESSAGE#", $form_data['message'], $email_message);
   $email_message = str_replace("#FROM_EMAIL#", $form_data['user_email'], $email_message);
   $email_message = str_replace("#FROM_MEMBER#", $form_data['from_member'], $email_message);
 
   //Construct the email headers
   $to = $form_data['email'];
   $from = $form_data['user_email'];
   $email_subject = $form_data['subject'];

   $headers  = "From: " . $from . "\r\n";
   $headers .= 'MIME-Version: 1.0' . "\n"; 
   $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";   
   
   //Send an invitation email message
   mail($to, $email_subject, $email_message, $headers);
}


/*If the form has been submitted, proceed with checking the input for errors*/
if ($_POST) {

    /*Set post variables here. Use the trim() function to remove any white spaces
    on either end of the input.*/	   
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    
    /*Regular expressions for names and email. Email must conform to a standard email format, 
    and names may contain dashes and apostrophes, but must contain at least some letters. */
    $valid_name = "^[A-Za-z][A-Za-z-']+$";
    $letter_filter = "^[a-zA-Z]+$";
    
    /*Ensure that all field are completed*/
    if (!($first_name && $last_name && $email && $subject)) {
        $error_message = "Please make sure you've filled in all the form fields. ";
    }
    
    /*Check inputs for length and validity*/
    else if (strlen($first_name) > 25 || strlen($last_name) > 25) {
        $error_message = "Please make sure both your first and last names are fewer than 25 characters each.  ";
    }
    
    else if (!(valid_input($first_name, $valid_name)) || !(valid_input($last_name, $valid_name)) ||
             !(valid_input($last_name, $letter_filter)) || !(valid_input($last_name, $letter_filter))) {
        $error_message = "Please make sure you enter a valid name, which contains letters, hyphens or apostrophes only.  ";
    }
    
    else if (strlen($email) > 50 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please make sure you enter a valid email address, which is less than 50 characters.  ";
    }
    
    else if (strlen($subject) > 50) {
        $error_message = "Please limit your subject to 50 characters or less.  ";
    }
    
    else if (strlen($message) > 250) {
        $error_message = "Please limit your message to 250 characters or less.  ";
    }
    
    /*If user input validation passes, proceed with database checks */
    else {
    
  
       /*Check the database for an existing member with this email*/
       $command = "SELECT memberID FROM member_login WHERE email = '". $db->real_escape_string($email)."';";
       $result = $db->query($command);
       
       /*If a match is found, display an error message to the user. Otherwise, proceed with sending 
       and invitation. */
       if ($data = $result->fetch_object()) {
          $error_message = "We have found an existing member with that email address.";
       }
       
       else  {
          
          /* Use the getdate() and mktime() functions to calculate a timestamp for the invitation. 
          Use the date as an argument passed to the send_invitation function */
          $date = getdate();
          $invite_date = mktime($date['hours'],$date['minutes'],$date['seconds'],$date['mon'],$date['mday'],$date['year']);
          $date = date("F d, Y   g:h a", $invite_date);
          
          /*Call the function "send_invitation", passing the array of POST data, the location 
          of the email template, and the date of the invitation. Display a message to the user
          that an invitation has been sent. */
          send_invitation($_POST, $_SERVER['DOCUMENT_ROOT']."/social/invite_template.txt", $date);

          $success_message = "An invitation has been successfully sent to " . $email;

       }
       //Close the database connection
       $db->close();
    } 
}

/*Include a header*/
include($_SERVER['DOCUMENT_ROOT']."/.php_files/social_header.inc");
?>
 
<span class="signed_in">Signed in as <? echo $_SESSION['user_email']?> </span><br>
<span class="signed_in">Not <a href="profile.php?logout=1"><? echo $_SESSION['first_name']?>? </a></span>
<br>
<br>

<h4>Invite others to join! Send an email invitation to a friend to join your personal network.</h4>

<span style="color:red;font-size:12px;">
<?
if ($error_message) {
   echo $error_message . "<br>";
}
?>
</span>

<span style="color:blue;font-size:12px;">
<?
if ($success_message) {
   echo $success_message . "<br>";
}
?>
</span>

<form method="POST" action="">
<table>
<tr>
<td align="right">
From:
</td>
<td align="left">
<? echo $_SESSION['user_email'] ?>
</td>
</tr>
<tr>
<td align="right">
First Name:
</td>
<td align="left">
<input type="text" size="25" maxlength="25" name="first_name" value="<? echo $_POST['first_name']; ?>">
</td>
</tr>
<tr>
<td align="right">
Last Name:
</td>
<td align="left">
<input type="text" size="25" maxlength="25" name="last_name" value="<? echo $_POST['last_name']; ?>">
</td>
</tr>
<tr>
<td align="right">
Email address:
</td>
<td align="left">
<input type="text" size="25" maxlength="50" name="email" value="<? echo $_POST['email']; ?>">
</td>
</tr>
<tr>
<td align="right">
Email subject:
</td>
<td align="left">
<input type="text" size="25" maxlength="50" name="subject" value="<? echo $_POST['subject']; ?>">
</td>
</tr>
<tr>
<td align="right" valign="top">
Message: <br> <span style="color:grey">(optional)</span>
</td>
<td align="left">
<textarea rows="8" cols="30" maxlength="250" name="message" value="<? echo $_POST['message']; ?>" style="resize:none"><? echo $_POST['message']; ?></textarea>
</td>
</tr>

<tr>
<td colspan="2">
&nbsp;
</td>
</tr>


<tr>
<td colspan="2" align="center">
<input type="submit" value="Send Invitation">
<input type="hidden" name="user_email" value="<? echo $_SESSION['user_email']; ?>">
<input type="hidden" name="from_member" value="<? echo $_SESSION['first_name'] . " " . $_SESSION['last_name']; ?>">
</td></tr>
</table>
</form>

<br>
<h4><a href="profile.php?profileID=<?echo $_SESSION['memberID']; ?>">Return to My Profile ></a></h4>



<?
 /*Include a footer */
 include($_SERVER['DOCUMENT_ROOT']."/.php_files/social_footer.inc");
 
?> 
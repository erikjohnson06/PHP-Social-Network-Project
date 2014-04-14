<?php
/*group_create.php -- interface for creating a new group*/

//Start the session and set the member ID to the current session's memberID variable
session_start();
$memberID = $_SESSION['memberID'];

/*Ensure that the user is logged in*/
if (!$memberID) {
   
    //If the user is not logged in, redirect them to the home page:
    header("Location: http://". $_SERVER['HTTP_HOST'] ."/social/index.php");
}

/*Connect using the username "group_login" on the database "social_network" */
include($_SERVER['DOCUMENT_ROOT']."/social/groups/group_login_login.php");

$db = new mysqli($host,$user,$pw,$database,$port,$socket) 
      or die("Cannot connect to mySQL.");

################
#              #
#  FUNCTIONS   #
#              #
################

/*This function will check inputs against a regular expression representing the proper input format.*/
function valid_input($myinput, $good_input) {
   if (preg_match("/$good_input/i", $myinput, $regs)) {
      return true;
   }
   else {
      return false;
   }
}


/*Use this function to ensure database integrity. If a group's name is already
existing elsewhere, return a false value. Otherwise, it is safe to proceed. */
function check_group_name($name, $db) {
    
   //Search for any group names matching the user's input
   $command = "SELECT * from groups where name = '" . $db->real_escape_string($name) . "';"; 
  
   $result = $db->query($command); 
                        
   if ($result->num_rows > 0) {
       return false;
   }
   else {
       return true;
   }
}

################
#              #
#  REQUEST     #
#  HANDLING    #
#              #
################

/*If the form has been submitted, proceed with checking the input for errors. */
if ($_POST) {

    /*Set post variables here. Use the trim() function to remove any white spaces
    on either end of the input.*/	   
    $name = trim($_POST['name']);
    $category = $_POST['category'];
    $description = trim($_POST['description']);
    $created_by = $_POST['created_by'];
    $create_date = date('Y-m-d H:i:s');
    
    /*Regular expressions for all text input. Names must contain dashes and apostrophes, 
    but must contain at least some letters. Descriptions may contain alphanumeric characters, 
    as well as simple punctuation. */
    $valid_name = "^[A-Za-z][A-Za-z-'\s]+$";
    $letter_filter = "[a-zA-Z]";
    $valid_description = "^[A-Za-z0-9-'\?\!\.\:\;\,\@\s]+$";
    
    /*Ensure that all fields are completed upon submission*/
    if (!($name && $category && $description)) {
        $error_message = "Please make sure you've filled in all the form fields. <br>";
    }
    
    /*Check all input for length and validity based on the regular expressions above*/
    else if (strlen($name) > 50) {
        $error_message  .=  "Please make sure the group name does not exceed 50 characters.  <br>";
    }
    
    else if (!(valid_input($name, $valid_name)) || !(valid_input($name, $letter_filter))) {
        $error_message .=  "Please enter a valid group name, which contains letters, hyphens or apostrophes only. <br> ";
    }
    
    else if (strlen($description) > 250) {
        $error_message .= "Please limit your description to 250 characters. <br>";
    }
    else if (!(valid_input($description, $letter_filter))  || !(valid_input($description, $valid_description))) {
        $error_message .= "Please limit your message characters to letters, digits, and punctuation. <br>";
    }
    
    
/*If any error messages occurred as a result of not passing validation, display them as messages 
to the user and do not proceed further. If the data passed the validation, perform database constraint
checks, and then insert the data into the database. */
if (!$error_message) {
    
   /*Check the group name to make sure it remains unique in the database. */
   if (check_group_name($name, $db) != true) {
       $error_message  .= "Sorry. A group by this name already exists. <br>"; 
   } 
   
   /*If the database validation has passed, begin a transaction to update the groups and group_requests tables. */
   else {
       
       //Use this flag to determine the success of the transaction   
       $success = true; 
       $group_id = '';
       
       //Set autocommit to "0", and begin the transaction.
       $command = "SET AUTOCOMMIT=0";
       $result = $db->query($command);
       $command = "BEGIN";
       $result = $db->query($command);
       
       //Insert the new group into the table
       $command = "INSERT INTO groups (group_id, name, description, category, created_by, create_date) " . 
                  "VALUES ('', '" . $db->real_escape_string($name) . 
                   "', '" . $db->real_escape_string($description) . 
                   "', '" . $db->real_escape_string($category) . 
                   "', '" . $db->real_escape_string($created_by) . 
                   "',  '" . $db->real_escape_string($create_date) . "');"; 
       
       $result = $db->query($command);
       
       /*Record the success/failure of the query for the transaction*/
       if ($result == false) {
           $success = false;
       }
       
       /*If successful, proceed to inserting a new member into the group_requests table. 
       Because the creator of the group needs no approval, they will automatically be
       approved as a member. */
       else {
       
       $group_id = $db->insert_id;
       
       //Insert the creator of the group into the group_requests table
       $command = "INSERT INTO group_requests (requestID, req_member, req_date, group_id, app_member, app_date) " . 
                  "VALUES ('', '" .$db->real_escape_string($memberID) . 
                   "', '" . $db->real_escape_string($create_date) . 
                   "', '" . $db->real_escape_string($group_id) .        
                   "', '" . $db->real_escape_string($created_by) .                          
                   "',  '" . $db->real_escape_string($create_date) . "');"; 

       $result = $db->query($command);
       
           /*Record the success/failure of the query for the transaction*/
           if ($result == false) {
           $success = false;
           }
       
       }
   
       /*If the transaction was successful, save the change to the database and display a 
       success message. If not, rollback the changes.*/
       if ($success) {
          $command = "COMMIT";
          $result = $db->query($command);
          $success_message = "<br>Group was successfully created! Visit the <a href='group_profile.php?group_id=" . $group_id . "'>" . $name  . "</a> page.<br>" ;
          
       }
       
       else {
          $command = "ROLLBACK";
          $result = $db->query($command);
          $error_message = $error_message . "<br>An error has occurred. This group could not be created.<br>";
       }
       
       //Return to autocommit
       $command = "SET AUTOCOMMIT=1"; 
       $result = $db->query($command);

       $db->close();
   }
 } 
}



//Include header
include($_SERVER['DOCUMENT_ROOT']."/.php_files/social_header.inc");
?>

<span class="signed_in">Signed in as <? echo $_SESSION['user_email']?> </span><br>
<span class="signed_in">Not <a href="../profile.php?logout=1"><? echo $_SESSION['first_name']?>? </a></span>
<br>


<h2>Create a New Group</h2>

<span style="color:red;font-size:12px;">
<?
//Display error / success messages here
if ($error_message) {
   echo $error_message . "<br>";
}
?>
</span>

<span style="color:black;font-size:12px;">
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
    Group Name:
   </td>
   <td align="left">
     <input type="text" size="25" maxlength="50" name="name" value="<? echo $_POST['name']; ?>">
   </td>
</tr>


<tr>
  <td align="right">
  Moderator:
  </td>
  <td align="left">
    <input type="text" size="25" value="<? echo $_SESSION['first_name'] . " " . $_SESSION['last_name']; ?>" disabled>
  </td>
</tr>

<tr>
  <td align="right">
  Category:
  </td>
  <td align="left">
   <select name="category">
    <option value="">--Select---</option>
    <option value="People">People</option>
    <option value="Places">Places</option>
    <option value="Interests">Interests</option>
   </select>
  </td>
</tr>

<tr>
  <td align="right" valign="top">
    Description:
  </td>
  <td align="left">
   <textarea rows="8" cols="25" maxlength='250' name="description" style="resize:none" placeholder="Please limit description to 250 characters."><? echo $_POST['description']; ?></textarea>
  </td>
</tr>

<tr>
  <td colspan="2">
    <input type="hidden" name="created_by" value="<? echo $_SESSION['memberID']; ?>">
  </td>
</tr>


<tr>
  <td colspan="2" align="center">
   &nbsp;
  </td>
</tr>

<tr>
  <td colspan="2" align="center">
   <input type="submit" value="Submit">
  </td>
</tr>

</table>
</form>

<br>

<h4><a href="group_profile.php">Browse for Groups ></a></h4>
<h4><a href="../profile.php?profileID=<?echo $_SESSION['memberID']; ?>">Return to My Profile ></a></h4>




<?php

//Include footer 
include($_SERVER['DOCUMENT_ROOT']."/.php_files/social_footer.inc");
/*
echo "Session array: <br>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "Profile array: <br>";
echo "<pre>";
print_r($profile_array);
echo "</pre>";

echo "My Profile array: <br>";
echo "<pre>";
print_r($myprofile_array);
echo "</pre>";

echo "Friends array: <br>";
echo "<pre>";
print_r($friends_array);
echo "</pre>";
*/

?> 
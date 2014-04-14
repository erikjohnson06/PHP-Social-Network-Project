<?php
/*group_edit.php -- interface for editing a new group*/

//Start the session and set the member ID to the current session's memberID variable
session_start();
$memberID = $_SESSION['memberID'];
$groupID = $_GET['group_id'];


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

/*This function checks where the user visiting this page is a moderator. The visitor should 
not have access to make changes to the group profile if they are not the moderator. */
function is_moderator($groupID, $memberID, $db) {
     //returns a boolean value
     $is_moderator = false;
     if ($groupID && $memberID) {
        $command = "SELECT * FROM groups " .
                   "WHERE created_by ='" . $db->real_escape_string($memberID) .
                   "' AND group_id ='".$db->real_escape_string($groupID) .
                   "' AND date_deactivated <= 0;";
        
        $result = $db->query($command);
        
        if ($result->num_rows > 0) {
           $is_moderator = true;
        }
     }
     return $is_moderator;
}

/*This function will retrieve the moderator of the group. */
function fetch_moderator($groupID, $db) {
  
   
    //group_moderator array is a two dimensional array that holds the name of the group's moderator
    $group_moderator_array = array();
    
    if ($groupID) {
      $command = "SELECT g.group_id, g.created_by, mi.first_name, mi.last_name FROM groups g, member_info mi " . 
                 "WHERE g.created_by = mi.memberID AND g.group_id = '" . $db->real_escape_string($groupID) . "' " .
                 "AND g.date_deactivated <= 0 ;";
                                
      $result = $db->query($command);
      
      if ($result->num_rows > 0) {
                 
         while ($data_array = $result->fetch_assoc()) {
            array_push($group_moderator_array, $data_array);
         }
      }
     }

    return $group_moderator_array;
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

/*This function will return an array of information about the group that can be used
to populate the text fields */
function fetch_group($groupID, $db) {

    $group_array = array();
    
    if ($groupID) {

       $command = "SELECT * FROM groups WHERE group_id = '". $db->real_escape_string($groupID) ."';";
                  
       $result = $db->query($command);
       
       if ($result->num_rows > 0) {
          $group_array = $result->fetch_assoc();
       }
    }
    return $group_array;    
}

/*This function is currently not is use here, however, it can be used to list the 
members of the group. */
function fetch_members($groupID, $db) {
    
    //group_members array is a two dimensional array that holds the names of the group's members
    $group_members_array = array();
    
    //Find all the members of the group that are currently active
    if ($groupID) {
      $command = "SELECT gr.group_id, gr.req_member, mi.first_name, mi.last_name FROM  group_requests gr, member_info mi " . 
                 "WHERE gr.req_member = mi.memberID AND gr.group_id = '" . $db->real_escape_string($groupID) . "' " .
                 "AND gr.app_date > 0 AND gr.date_deactivated <= 0 ;";
                                  
      $result = $db->query($command);
      
      if ($result->num_rows > 0) {
                 
         while ($thismember_array = $result->fetch_assoc()) {
            array_push($group_members_array, $thismember_array);
         }
      }
     }

    return $group_members_array;
}

################
#              #
#  REQUEST     #
#  HANDLING    #
#              #
################

/*If the form has been submitted, proceed with checking the input for errors*/
if ($_POST) {

    /*Set post variables here. Use the trim() function to remove any white spaces
    on either end of the input.*/	   
    $name = trim($_POST['name']);
    $category = $_POST['category'];
    $description = trim($_POST['description']);
    $created_by = $_POST['created_by'];
    $group_id = $_POST['group_id'];
        
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
       
      else {
       //Update the group's profile
       $command = "UPDATE groups SET name = '" . $db->real_escape_string($name) . 
                   "', description = '" . $db->real_escape_string($description) . 
                   "', category = '" . $db->real_escape_string($category) . 
                   "' WHERE group_id = '" . $db->real_escape_string($group_id) . "';"; 
             
       $result = $db->query($command);
       
       $success_message = "<br>Group was successfully updated! <br>" ;
      }   
  }  
} 



//Include header
include($_SERVER['DOCUMENT_ROOT']."/.php_files/social_header.inc");
?>

<span class="signed_in">Signed in as <? echo $_SESSION['user_email']?> </span><br>
<span class="signed_in">Not <a href="../profile.php?logout=1"><? echo $_SESSION['first_name']?>? </a></span>
<br>


<h2>Edit Group Profile</h2>

<span style="color:red;font-size:12px;">
<?
//Display error / success messages here
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

<?

//Set variables for us below. Variables contain arrays from the function defined above. 
$is_moderator = is_moderator($groupID, $memberID, $db);
$group_array = fetch_group($groupID, $db); 
$group_members_array = fetch_members($groupID, $db);
$moderator_array = fetch_moderator($groupID, $db);
$moderator = $moderator_array['0']['first_name'] . " " . $moderator_array['0']['last_name'];

/*Only display the page if the visiting member is the moderator. If not, then display a message
saying that only the moderator is allowed to edit group profiles. */
if ($is_moderator) {

?>

<form method="POST" action="">
 <table>

  <tr>
   <td align="right">
    Group Name:
   </td>
   <td align="left">
     <input type="text" size="25" maxlength="50" name="name" value="<? echo $group_array['name']; ?>">
   </td>
</tr>


<tr>
  <td align="right">
  Moderator:
  </td>
  <td align="left">
    <input type="text" size="25" value="<? echo $moderator; ?>" disabled>
  </td>
</tr>

<tr>
  <td align="right">
  Category:
  </td>
  <td align="left">
   <select name="category">
    <option value="">--Select---
    <option value="People" <? if ($group_array['category'] == 'People') { echo "selected";}?> >People
    <option value="Places" <? if ($group_array['category'] == 'Places') { echo "selected";}?> >Places
    <option value="Interests" <? if ($group_array['category'] == 'Interests') { echo "selected";}?> >Interests
   </select>
  </td>
</tr>

<tr>
  <td align="right" valign="top">
    Description:
  </td>
  <td align="left">
   <textarea rows="8" cols="25" maxlength='250' name="description" style="resize:none" placeholder="Please limit description to 250 characters."><? if ($_POST) { echo $_POST['description'];} else {echo $group_array['description'];} ?>
   </textarea>
  </td>
</tr>

<tr>
  <td colspan="2">
    <input type="hidden" name="created_by" value="<? echo $group_array['created_by']; ?>">
    <input type="hidden" name="group_id" value="<? echo $group_array['group_id']; ?>">
  </td>
</tr>

<tr>
  <td colspan="2" align="center">
   &nbsp;
  </td>
</tr>

<tr>
  <td colspan="2" align="center">
   <input type="submit" value="Update">
  </td>
</tr>

</table>
</form>


<br>
<h4><a href="group_profile.php?group_id=<? echo $group_array['group_id']; ?>">Return to Group Profile ></a></h4>

<?


}

else {

echo "<span style='color:red;font-size:12px;'>You are not allowed to make changes to this group. Please contact the moderator: " . $moderator . "</span>";

}

//Include footer 
include($_SERVER['DOCUMENT_ROOT']."/.php_files/social_footer.inc");
/*
echo "Session array: <br>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "POST array: <br>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

echo "Moderator array: <br>";
echo "<pre>";
print_r($moderator_array);
echo "</pre>";

echo "Group array: <br>";
echo "<pre>";
print_r($group_array);
echo "</pre>";

echo "Group array: <br>";
echo "<pre>";
print_r($group_members_array);
echo "</pre>";
*/

//Close the connection
$db->close();
?> 
<?php
/*request.php -- interface for managing friend requests*/

/*If a member's not logged in, redirect them to the home page*/
session_start();
if (!$_SESSION['memberID']) {
    header("Location: /social/index.php");
}

//Set variable for memberID who is currently logged in
$memberID = $_SESSION['memberID'];

/*Connect using the username "social_member" on the database "social_network" */
include($_SERVER['DOCUMENT_ROOT']."/social/social_member_login.php");

$db = new mysqli($host,$user,$pw,$database,$port,$socket) 
      or die("Cannot connect to mySQL.");


################
#              #
#  FUNCTIONS   #
#              #
################

//This function will find out if there are any pending friend requests, and return a boolean value
function my_approval_pending($memberID, $db) {

     $pending = false;
     if ($memberID) {
        $command = "SELECT * FROM member_friends WHERE " .
                   "app_member='" . $db->real_escape_string($memberID) . "' ".
                   "AND app_date <= 0  AND date_deactivated <= 0;";
                   
        $result = $db->query($command);
        
        if ($result->num_rows > 0) {
           $pending = true;
        }
     }
     return $pending;
}

//This function will return an array of pending friend request information to display
function fetch_pending_requests($memberID, $db) {

       $pending_array = array();

        $command = "SELECT mf.friendID,mf.req_member,date_format(mf.req_date, '%M %D, %Y') as req_date,mf.message,mf.app_member,mi.first_name,mi.last_name " . 
                   "FROM member_friends mf, member_info mi " .
                   "WHERE mf.app_member='" . $db->real_escape_string($memberID) . "' ".
                   "AND mf.req_member = mi.memberID " .
                   "AND mf.app_date <= 0  AND mf.date_deactivated <= 0;";
                   
        $result = $db->query($command);
        
       if ($result->num_rows > 0) {
                 
           while ($data_array = $result->fetch_assoc()) {
            array_push($pending_array, $data_array);
         }
       }            

     return $pending_array;
}

/*This function takes input from an originating link and returns and array of data based on that
 member's profileID. This info is then displayed on this page as part of a form to request that 
 member's friendship. */
function fetch_member_info($profileID, $db) {

    $friend_info_array = array();
    
    if ($profileID) {
      $command = "SELECT mi.memberID, mi.first_name, mi.last_name, ml.email FROM  member_info mi, member_login ml " . 
                 "WHERE mi.memberID = ml.memberID AND mi.memberID = '". $db->real_escape_string($profileID)."';";
                 
      $result = $db->query($command);
      
      if ($result->num_rows > 0) {
                 
         if ($request_data = $result->fetch_object()) {
            
            $friend_info_array = array("memberID" => $request_data->memberID, 
                                       "first_name" => $request_data->first_name, 
                                       "last_name" => $request_data->last_name, 
                                       "email" => $request_data->email);
         }
      }
     }
    return $friend_info_array;
}

/*This function will check inputs against a regular expression representing the proper input format.*/
function valid_input($myinput, $good_input) {
   if (preg_match("/$good_input/i", $myinput, $regs)) {
      return true;
   }
   else {
      return false;
   }
}

/*If a friendship request was confirmed, this function will be called, taking the necessary input
and updating the approval date column. */
function confirm_friendship($req_member, $app_member, $app_date, $db) {

   $command = "UPDATE member_friends SET app_date = '". $db->real_escape_string($app_date) ."' ". 
              "WHERE app_member='" . $db->real_escape_string($app_member) . "' " .
              "AND req_member = '" . $db->real_escape_string($req_member) . "';";

   $result = $db->query($command);
   
   if ($result == true) {
      return true; 
   }
   else {
      return false;
   }
}

/*If a friendship request was denied, this function will be called, taking the necessary input
and updating the approval date column AND date_deactivated column. These two columns can
later be reset if the friendship request is made again. */
function deny_friendship($req_member, $app_member, $app_date, $db) {

   $command = "UPDATE member_friends SET app_date = '". $db->real_escape_string($app_date) ."', ". 
              "date_deactivated = '". $db->real_escape_string($app_date) ."' " .
              "WHERE app_member='" . $db->real_escape_string($app_member) . "' " .
              "AND req_member = '" . $db->real_escape_string($req_member) . "';";

   $result = $db->query($command);
   
   if ($result == true) {
      return true; 
   }
   else {
      return false;
   }

}

################
#              #
#  REQUEST     #
#  HANDLING    #
#              #
################

//If a friend request for another member has been submitted, update the member_friends table
if ($_POST['submit_request']) {

  /*First, check to make sure that the message contains no more than 150 characters. If so, 
  display a message to user. Otherwise, insert the request into the member_friends table.*/
 $message = trim($_POST['message']);
 $req_member = $_POST['req_member'];
 $app_member = $_POST['app_member'];
 
 /*Although it is difficult to enforce character rules on a text message, we do not want to simply 
 trust that all users have good intentions. We need to at least limit the message to alphanumeric
 characters, digits, and simple punctuation. */
 $valid_message = "^[A-Za-z0-9-'\?\!\.\:\;\,\@\s]+$";
 
  if ($message && strlen($message) > 150) {
      $error_message = "Please limit your message to 150 characters. <br>";
  }
  else if ($message && !(valid_input($message, $valid_message))) {
      $error_message .= "Please limit your message characters to letters, digits, and punctuation. <br>";
  }
  /*If no message was entered, that is acceptable. However, in order to keep the database
  from having null fields, we will set the default message to "none" and check for this later.*/
  if (!$message) {
      $message = "none";
  }

  //If no error message is encountered, proceed with database checks
  if (!$error_message) {
  
     //Sanitize any characters that slipped past our regular expression
     $message = htmlentities($message); 
     
     //Get the current datetime 
     $req_date = date('Y-m-d H:i:s');
  
     
     //If no error messages were received, proceed with checking for an entry in the database
     $command = "SELECT * from member_friends WHERE req_member = '" . $db->real_escape_string($req_member) . 
                "' AND app_member = '" . $db->real_escape_string($app_member) . "';";
  
     $result = $db->query($command);
     
     /*If an existing entry was found, use this row rather than inserting a new row of data. 
     This will make for a more efficient and cleaner database. */
     if ($result->num_rows > 0) {
     
     //Reset the date_deactivated and app_date back to "0"
     $command = "UPDATE member_friends SET app_date = '0000-00-00 00:00:00', date_deactivated = '0000-00-00 00:00:00', " . 
                "message = '". $db->real_escape_string($message) ."' " . 
                "WHERE req_member = '" . $db->real_escape_string($req_member) . 
                "' AND app_member = '" . $db->real_escape_string($app_member) . "';";
     
     $result = $db->query($command);
     }
  
     //If no existing entries were found, proceed with creating a new entry in the database
     else {

     //Insert the request for friendship into member_friends
     $command = "INSERT INTO member_friends (friendID, req_member, req_date, message, app_member) " . 
                " VALUES('', '" . $db->real_escape_string($req_member) . 
                   "', '" . $db->real_escape_string($req_date) . 
                   "', '" . $db->real_escape_string($message) . 
                   "', '" . $db->real_escape_string($app_member) . "');"; 

     $result = $db->query($command);
     }
     //Close the connection
     $db->close();
    
  //Redirect back to member's profile page after requesting friendship
  header("Location: http://". $_SERVER['HTTP_HOST'] . "/social/profile.php?profileID=" . $app_member);
  }
}

//if a friend request is accepted, confirm the friendship and update the member_friends table 
if ($_POST['confirm']) {

   //Get the current datetime, and POST variables needed to update the member_friends table
   $app_date = date('Y-m-d H:i:s');
   $req_member = $_POST['req_member'];
   $app_member = $_POST['app_member'];

  /*Call the function defined to execute the update query with the appropriate information. 
  Display a success/error message, depending on the outcome*/
  if (confirm_friendship($req_member, $app_member, $app_date, $db) == true) {
     $confirm_message = "Successfully added friend.";
  }
  else {
     $confirm_message = "An error was encountered adding this member to your network.";
  }
}

/*if a friend request is not accepted, update the member_friends table with the current 
approval date, but also update the date_deactivated date */
if ($_POST['deny']) {

   //Get the current datetime, and POST variables needed to update the member_friends table
   $app_date = date('Y-m-d H:i:s');
   $req_member = $_POST['req_member'];
   $app_member = $_POST['app_member'];

   /*Call the function defined to execute the update query with the appropriate information.
   Display a success/error message, depending on the outcome*/
  if (deny_friendship($req_member, $app_member, $app_date, $db) == true) {
     $deny_message = "Member was not added to your network.";
  }
  else {
     $deny_message = "An error was encountered in removing this member from your network.";
  }
}

//Include header
include($_SERVER['DOCUMENT_ROOT']."/.php_files/social_header.inc");
?>

<span class="signed_in">Signed in as <? echo $_SESSION['user_email']?> </span><br>
<span class="signed_in">Not <a href="profile.php?logout=1"><? echo $_SESSION['first_name']?>? </a></span>
<br>

<h2>Friendship Requests</h2>

<div class="left_col">
 <div class="box">
  <div class="top">
	<div class="inside">
	 <div class="title">
	  <div class="left">
	   Pending Requests
	  </div>
	  <div class="right">
	  </div>
	  <div class="clear_both"></div>
	 </div>
	</div>
   </div>
   <div class="bot">
	<div class="inside">
	  <? 
	      //If there are requests that are currently approval, display them here    
	      if (my_approval_pending($memberID, $db)) {
	      
	         $pending = fetch_pending_requests($memberID, $db);
	      
	         $count = count($pending); 
	      
	         /*Display each pending request using a loop function. Each request will be a form
	         that will allow the user to either confirm or deny the request*/
	         for ($i = 0; $i < $count; $i++) {
	         
	           echo "<form method='post' action=''><table>";
	           echo "<tr><td>Name: <a href='profile.php?profileID=". $pending[$i]['req_member'] ."'>" . 
	                $pending[$i]['first_name'] . " " . $pending[$i]['last_name'] . "</a></td></tr>";
         
	           echo "<tr><td><span style='color:#777;'>Request Date: " . $pending[$i]['req_date'] . "</span></td></tr>";
	          
	           //If no message was included in the request, do not display a blank message
	           if (!($pending[$i]['message'] == "none")) {
	              echo "<tr><td><span style='color:#777;'>Message: " . $pending[$i]['message'] . "</span></td></tr>";
	            }
	           echo "<tr><td><input type='hidden' name='req_member' value=". $pending[$i]['req_member'] ." /></td>";
	           echo "<td><input type='hidden' name='app_member' value=". $pending[$i]['app_member'] ." /></td></tr>";
	           echo "<tr><td colspan='2' align='left'><input name='confirm' type='submit' value='Confirm' />&nbsp;&nbsp;";
	           echo "<input name='deny' type='submit' value='Not Now' /></td></tr>";
	           echo "<tr><td colspan='2'>&nbsp;</td></tr>";
	           echo "</table></form>";
	         }
	     } 
	     
	     else {
	       echo "<p>You have no friendship requests awaiting approval.</p>";
	     }
	  ?>
	</div>
   </div>
  </div>		
</div>


<div class="right_col">
 <div class="box">
  <div class="top">
   <div class="inside">
	<div class="title">
	 <div class="left">
	  Make a Request
	 </div>
	 <div class="right">

	 </div>	
	 <div class="clear_both"></div>
	</div>
   </div>
  </div>
  <div class="bot">
   <div class="inside">
	<?
	   /*variable for the approval member (who is receiving request). 
	   Retrieve this from the URL (which originates from the link on each members
	   profile page */
	   if ($_GET['friend_request']) {
	   $friend_request = $_GET['friend_request'];
	   $member_info_array = fetch_member_info($friend_request, $db);
	   
	   /*Display a form that will create a friend request when submitted. A brief message up to 
	   150 characters is allowed. The approving member will be determined by using the fetch_member_info
	   function in conjunction with the friend_request id. The requester will, of course, be the 
	   user who is logged in. */
           echo "<form method='post' action=''><table><tr>";
	   echo "<td colspan='2'>Request Friendship for:</td></tr>";
	   echo "<tr><td colspan='2'>&nbsp;</td></tr>";
	   echo "<tr><td>Name: </td><td>" . $member_info_array['first_name'] . " " . $member_info_array['last_name'] . "</td></tr>";
	   echo "<tr><td>Email: </td><td>" . $member_info_array['email'] . "</td></tr>";
	   echo "<tr><td colspan='2'>&nbsp;</td></tr>";
	   echo "<tr><td colspan='2'>Include a brief message: <span style='color:#777;'>(Optional)</span></td></tr>";
	   echo "<tr><td colspan='2'><textarea rows='8' cols='29' maxlength='150' name='message' style='resize:none'" . 
	        " placeholder='Please limit message to 150 characters.'>" . $_POST['message'] . "</textarea></td></tr>";
	   echo "<tr><td colspan='2'>&nbsp;</td></tr>";
	   echo "<tr><td colspan='2'><input type='hidden' name='req_member' value=". $_SESSION['memberID'] ." /></td></tr>";
	   echo "<tr><td colspan='2'><input type='hidden' name='app_member' value=". $member_info_array['memberID'] ." /></td></tr>";
	   echo "<tr><td colspan='2' align='center'><input name='submit_request' type='submit' value='Request' /></td></tr>";
	   echo "</table></form>";
	   if ($error_message) { echo "<span style='color:red;'>" . $error_message . "</span>";}
	   
	   
	  }
	  else {
	  ?>
	    <p>Expand your personal network! Search for other members to connect with:</p>
	   
	    <br>
            <a href="search.php"><b>Search ></b></a>
          <?
	  }

	?>
   </div>
  </div>
 </div>
</div>
<div class="clear_both"></div>

<?

   if ($confirm_message) { echo "<span style='color:#777;margin-left:20px;'>" . $confirm_message . "</span><br>";}
   if ($deny_message) { echo "<span style='color:#777;margin-left:20px;'>" . $deny_message . "</span><br>";}
?>
<br>
<h4><a href="profile.php?profileID=<?echo $_SESSION['memberID']; ?>">Return to My Profile ></a></h4>


<?php

//Include footer 
include($_SERVER['DOCUMENT_ROOT']."/.php_files/social_footer.inc");

/*
echo "Session array: <br>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "Member Info array: <br>";
echo "<pre>";
print_r($member_info_array);
echo "</pre>";

echo "Post array: <br>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

echo "Pending array: <br>";
echo "<pre>";
print_r($pending);
echo "</pre>";
*/

?> 
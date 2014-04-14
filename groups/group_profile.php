<?php
/*group_profile.php -- interface for browsing and joining groups*/

//Start the session and set the member ID to the current session's memberID variable
session_start();
$memberID = $_SESSION['memberID'];

//Find out which group profile to display
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

/*Retrieve information regarding the group currently displayed. */
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
  
/*Create an array of all existing, active groups with the most recent group displayed first*/
  function fetch_all_groups($db) {
    $all_groups_array = array();
 
    $command = "SELECT group_id, name, description FROM groups WHERE date_deactivated <= 0 ORDER BY create_date DESC;";
                  
    $result = $db->query($command);
       
    if ($result->num_rows > 0) {
          
       while ($data_array = $result->fetch_assoc()) {
            array_push($all_groups_array, $data_array);
         }
    }
    
    return  $all_groups_array;    
 } 
 
/*Retrieve the group members for the group currently displayed */
  function fetch_members($groupID, $db) {
    
    //group_members array is a two dimensional array that holds the names of the group's members
    $group_members_array = array();
    
    //Find all the members the of the group that are currently active
    if ($groupID) {
      $command = "SELECT gr.group_id, gr.req_member, mi.first_name, mi.last_name FROM group_requests gr, member_info mi " . 
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
  
/*Find out who the moderator of the group is.*/
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
  
/*Query the database to find out if the visitor of the page is a member*/
  function is_member($groupID, $memberID, $db) {

     $active_member = false;
     if ($groupID && $memberID) {
        $command = "SELECT * FROM group_requests " .
                   "WHERE req_member ='" . $db->real_escape_string($memberID) .
                   "' AND group_id ='".$db->real_escape_string($groupID) .
                   "' AND app_date > 0  AND date_deactivated <= 0;";
        
        $result = $db->query($command);
        
        if ($result->num_rows > 0) {
           $active_member = true;
        }
     }
     return $active_member;
  }

/*Query the database to find out if the visitor of the page is the moderator*/
  function is_moderator($groupID, $memberID, $db) {

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

/*Search for any open approvals for the group moderator.*/
  function approval_pending($app_member, $groupID, $db) {

     $pending = false;
     if ($app_member) {
        $command = "SELECT * FROM group_requests WHERE " .
                   "app_member='".$db->real_escape_string($app_member)."' ".
                   "AND group_id = '".$db->real_escape_string($groupID)."' AND app_date <= 0  AND date_deactivated <= 0;";
                   
        $result = $db->query($command);
        
       if ($result->num_rows > 0) {
           $pending = true;
        }
     }
     return $pending;
  }
  
  //This function will return an array of pending group join requests for the moderator
  function fetch_pending_approvals($app_member, $groupID, $db) {

       $pending_array = array();

        $command = "SELECT gr.requestID,gr.req_member,date_format(gr.req_date, '%M %D, %Y') as req_date,gr.group_id,gr.app_member,mi.first_name,mi.last_name " . 
                   "FROM group_requests gr, member_info mi " .
                   "WHERE gr.app_member='" . $db->real_escape_string($app_member) . "' ".
                   "AND gr.req_member = mi.memberID " .
                   "AND gr.group_id = '" . $db->real_escape_string($groupID) . "' ".
                   "AND gr.app_date <= 0  AND gr.date_deactivated <= 0;";
                   
        $result = $db->query($command);
        
       if ($result->num_rows > 0) {
                 
           while ($data_array = $result->fetch_assoc()) {
            array_push($pending_array, $data_array);
         }
       }            

     return $pending_array;
  }
  
  //This function will find out if the visiting member of the page has requested to join already
  function request_pending($memberID, $groupID, $db) {
     //returns a boolean value
     $pending = false;
     if ($memberID) {
        $command = "SELECT * FROM group_requests WHERE " .
                   "req_member='". $db->real_escape_string($memberID)."' and group_id = '". $db->real_escape_string($groupID) .
                   "' AND app_date <= 0  AND date_deactivated <= 0;";
                        
        $result = $db->query($command);
        
        if ($result->num_rows > 0) {
           $pending = true;
        }
     }
     return $pending;
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

/*If a membership request was confirmed, this function will be called, taking the necessary input
and updating the approval date column. */
function confirm_membership($req_member, $group_id, $app_member, $app_date, $db) {


   $command = "UPDATE group_requests SET app_date = '". $db->real_escape_string($app_date) ."' ". 
              "WHERE app_member='" . $db->real_escape_string($app_member) . "' " .
              "AND group_id='" . $db->real_escape_string($group_id) . "' " .
              "AND req_member = '" . $db->real_escape_string($req_member) . "';";

   $result = $db->query($command);
   
   if ($result == true) {
      return true; 
   }
   else {
      return false;
   }
}

/*If a membership request was denied, this function will be called, taking the necessary input
and updating the approval date column AND date_deactivated column. These two columns can
later be reset if the membership request is made again. */
function deny_membership($req_member, $group_id, $app_member, $app_date, $db) {

   $command = "UPDATE group_requests SET app_date = '". $db->real_escape_string($app_date) ."', ". 
              "date_deactivated = '". $db->real_escape_string($app_date) ."' " .
              "WHERE app_member='" . $db->real_escape_string($app_member) . "' " .
              "AND group_id='" . $db->real_escape_string($group_id) . "' " .
              "AND req_member = '" . $db->real_escape_string($req_member) . "';";

   $result = $db->query($command);
   
   if ($result == true) {
      return true; 
   }
   else {
      return false;
   }

}

 /*This function runs a query to retrieve all of the group's wall posts*/
  function fetch_wall_posts($groupID, $db) {

    $wall_post_array = array();
    
    if ($groupID) {
      $command = "SELECT gwp.post_id, gwp.author_id, gwp.group_id, gwp.post_entry, date_format(gwp.post_date, '%M %D, %Y %h:%i %p') as post_date, " . 
                 "mi.memberID, mi.first_name, mi.last_name " . 
                 "FROM group_wall_posts gwp, member_info mi where mi.memberID = gwp.author_id " . 
                 "AND gwp.group_id = '" . $db->real_escape_string($groupID) . "' " . 
                 "AND gwp.date_deactivated <= 0 ORDER BY gwp.post_date DESC;";
                                                 
      $result = $db->query($command);
      
      if ($result->num_rows > 0) {
                 
         while ($data_array = $result->fetch_assoc()) {
            array_push($wall_post_array, $data_array);
         }
      }
     }
    return $wall_post_array;
  }
  
   /*This function runs a query to retrieve all of the group's wall posts comments */
  function fetch_wall_comments($post_id, $db) {

    $wall_comments_array = array();
    
    if ($post_id) {
      $command = "SELECT gwp.post_entry, gwc.comment_id, gwc.comment_entry, gwc.post_id, gwc.author_id, " . 
                 "date_format(gwc.comment_date, '%M %D, %Y %h:%i %p') as comment_date, mi.memberID, mi.first_name, mi.last_name " . 
                 "FROM group_wall_posts gwp, member_info mi, group_wall_comments gwc WHERE mi.memberID = gwc.author_id " . 
                 "AND gwc.post_id = gwp.post_id " .  
                 "AND gwc.post_id = '" . $db->real_escape_string($post_id) . "' " . 
                 "AND gwc.date_deactivated <= 0 ORDER BY gwc.comment_date ASC;";
                      

      $result = $db->query($command);
           
      if ($result->num_rows > 0) {
                 
         while ($data_array = $result->fetch_assoc()) {
            array_push($wall_comments_array, $data_array);
         }
      }
     }
    return $wall_comments_array;
  }


//If a specific group_id is present, retreive the information about the group, members, and moderator
if ($groupID) {
   $group_array = fetch_group($groupID, $db);
   $group_members_array = fetch_members($groupID, $db);
   $group_moderator_array = fetch_moderator($groupID, $db);
}



################
#              #
#  REQUEST     #
#  HANDLING    #
#              #
################

/*If the link is clicked to join a group, submit a request to the moderator. */
if ($groupID && $_GET['join_request']) {
     
     /*First and foremost, we never trust the input data, especially from a GET request. 
     In order to prevent anybody with mischievious or malicious intentions from hacking
     into our database, first verify the join group_id (the join_request number) is valid, 
     and that it only consists of numbers. */
     $join_request = trim($_GET['join_request']);
     $join_request = htmlentities($join_request);
     $valid_join_request = "^[0-9]+$";
     
     if (!(valid_input($join_request, $valid_join_request))) {
      $error_message = "An error has occurred in joining this group. <br>";
     }
     else {
       
     //Get the current datetime and set the requesting member as the user logged in
     $req_date = date('Y-m-d H:i:s');
     $req_member = $memberID;
     
     //If no error messages were received, proceed with checking for an entry in the database
     $command = "SELECT * from group_requests WHERE req_member = '" . $db->real_escape_string($req_member) . 
                "' AND group_id = '" . $db->real_escape_string($groupID) . "';";
  
     $result = $db->query($command);
          
     /*If an existing request entry was found, update this row rather than inserting a new row of data. 
     This will make for a more efficient and cleaner database. */
     if ($result->num_rows > 0) {
     
     //Reset the date_deactivated and app_date back to "0"
     $command = "UPDATE group_requests SET app_date = '0000-00-00 00:00:00', date_deactivated = '0000-00-00 00:00:00' " . 
                "WHERE req_member = '" . $db->real_escape_string($req_member) . 
                "' AND group_id = '" . $db->real_escape_string($groupID) . "';";
     
     $result = $db->query($command);
     }
  
     //If no existing entries were found, proceed with creating a new entry in the database
     else {
     
     //Find the moderator of the group, and set the moderator as the approving member
     $moderator_array = fetch_moderator($groupID, $db);
     $moderator = $moderator_array['0']['created_by'];
     
     //Insert the request for friendship into group_requests
     $command = "INSERT INTO group_requests (requestID, req_member, req_date, group_id, app_member) " . 
                " VALUES('', '" . $db->real_escape_string($req_member) . 
                   "', '" . $db->real_escape_string($req_date) . 
                   "', '" . $db->real_escape_string($groupID) . 
                   "', '" . $db->real_escape_string($moderator) . "');"; 
                   

     $result = $db->query($command);
     
     }

  /*Reload the page after making the request, as this allow the 
  message "You have requested to join.." to be displayed. */
  header("Location: http://". $_SERVER['HTTP_HOST'] . "/social/groups/group_profile.php?group_id=" . $groupID);
  }
}

/*If the link is clicked to leave a group, mark the member as "inactive" in the database. */
if ($groupID && $_GET['remove_request']) {
     
     /*In order to prevent anybody with mischievious or malicious intentions from hacking
     into our database, first verify the remove_request id (the member ID) is valid, 
     and that it only consists of numbers. */
     $remove_request = trim($_GET['remove_request']);
     $remove_request = htmlentities($remove_request);
     $valid_remove_request = "^[0-9]+$";
     
     if (!(valid_input($remove_request, $valid_remove_request))) {
      $error_message = "An error has occurred in leaving this group. <br>";
     }
     else {
       
     //Get the current datetime 
     $date_deactivated = date('Y-m-d H:i:s');

  
     //Update the date_deactivated field with the current date. This will effectively delete the member
     $command = "UPDATE group_requests SET date_deactivated = '". $db->real_escape_string($date_deactivated) .
                "' WHERE req_member = '" . $db->real_escape_string($remove_request) . 
                "' AND group_id = '" . $db->real_escape_string($groupID) . "';";
          
     $result = $db->query($command);
     }

  //Reload the group profile page again to ensure that all information (e.g., number of members) is updated seamlessly
  header("Location: http://". $_SERVER['HTTP_HOST'] . "/social/groups/group_profile.php?group_id=" . $groupID);
}


//if a group join request is accepted by the moderator, confirm the membership and update the group_requests table 
if ($_POST['confirm']) {

   //Get the current datetime, and POST variables needed to update the group_requests table
   $app_date = date('Y-m-d H:i:s');
   $req_member = $_POST['req_member'];
   $app_member = $_POST['app_member'];
   $group_id = $_POST['group_id'];

  /*Call the function defined to execute the update query with the appropriate information. 
  Display a success/error message, depending on the outcome*/
  if (confirm_membership($req_member, $group_id, $app_member, $app_date, $db) == true) {
     $confirm_message = "Successfully added member.";
     
     //Reload the page back to group's profile page after confirming membership to display new member
     header("Location: http://". $_SERVER['HTTP_HOST'] . "/social/groups/group_profile.php?group_id=" . $groupID);
  }
  else {
     $confirm_message = "An error was encountered adding this member to the group.";
  }

  
}

/*if a group membership request is not accepted, update the group_requests table with the current 
approval date, but also update the date_deactivated date */
if ($_POST['deny']) {

   //Get the current datetime, and POST variables needed to update the group_requests table
   $app_date = date('Y-m-d H:i:s');
   $req_member = $_POST['req_member'];
   $app_member = $_POST['app_member'];
   $group_id = $_POST['group_id'];

   /*Call the function defined to execute the update query with the appropriate information.
   Display a success/error message, depending on the outcome*/
  if (deny_membership($req_member, $group_id, $app_member, $app_date, $db) == true) {
     $deny_message = "Member was not added to the group.";
  }
  else {
     $deny_message = "An error was encountered in removing this member from the group.";
  }
}

/*Search the database for the searchstring specified if the search has been submitted*/
if ($_POST['search']) {

    /*Set post variable for the search string here. Use the trim() function to remove any white spaces
    on either end of the input. Also, make the searchstring all upper case to make it easier to find 
    more consistent matches in the database (all of which will also be converted to upper case for 
    the search)*/	   
    $search = trim($_POST['searchstring']);
    $search = strtoupper($search); 

    
    /*Regular expression for search input. We want to at least limit the search to alphanumeric characters
    and common symbols. */
    $valid_search = "^[A-Za-z0-9-'\?\!\.\:\;\,\@\s]+$";

    /*Ensure that the search field is completed upon submission*/
    if (!($search)) {
        $error_message = "Please enter a keyword. <br>";
    }
    
    /*Search input should not exceed 50 characters*/
    else if (strlen($search) > 50) {
        $error_message  .=  "Search may not exceed 50 characters.  <br>";
    }
    
    else if (!(valid_input($search, $valid_search))) {
        $error_message .=  "Please enter a valid keyword. <br> ";
    }
    
    else {
              
        //Search the groups table for matching names
        $command = "SELECT group_id, name FROM groups " . 
                   "WHERE upper(name) LIKE '%" . $db->real_escape_string($search) ."%';"; 
    
        $result = $db->query($command);
    
        $search_array = array(); 
        
        if ($result->num_rows > 0) {
          
           while ($data_array = $result->fetch_assoc()) {
              array_push($search_array, $data_array);
           }
           
           $search_count = count($search_array);
           
           if ($search_count == 1) {
              $matches_found = $search_count . " match was found.<br>";
           }
           else {
              $matches_found = $search_count . " matches were found.<br>";
           }
        }
        
        else {
           $matches_found = "No matches were found.<br>";
        }
    }
}

//Process new wall posts here
if ($_POST['create_wall_post']) {

  /*First, check to make sure that the post exists and contains no more than 150 characters. If not, 
  display a message to user. Otherwise, insert the post into the member_wall_posts table.*/
 $post_entry = trim($_POST['post_entry']);
 $author_id = $_POST['author_id'];
 $group_wall_id = $_POST['group_id'];

 
 /* Limit the post to alphanumeric characters, digits, and simple punctuation. */
 $valid_post_entry = "^[A-Za-z0-9-'\"\=\+\?\!\(\)\.\:\;\,\/\@\s]+$";
 
  if (!$post_entry) {
      $error_message = "Please enter a post to share. <br>";
  }
  else if (strlen($post_entry) > 150) {
      $error_message .= "Please limit your post to 150 characters. <br>";
  }
  else if (!(valid_input($post_entry, $valid_post_entry))) {
      $error_message .= "Please limit your post to letter, number, and punctuation characters. <br>";
  }
  
  if (!$error_message) {
  
    //Insert the post into the group_wall_posts table
    $command = "INSERT INTO group_wall_posts (post_id, group_id, author_id, post_entry, post_date) " . 
               "VALUES('', '" . $db->real_escape_string($group_wall_id) .       
                       "', '" . $db->real_escape_string($author_id) . 
                       "', '" . $db->real_escape_string($post_entry). "', " .
                       "now());"; 
                           
   $result = $db->query($command);   
 
  }
  
}

//Process comments here 
if ($_POST['create_wall_comment']) {

  /*First, check to make sure that the comment exists and contains no more than 150 characters. If not, 
  display a message to user. Otherwise, insert the commentinto the group_wall_comments table.*/
 $comment_entry = trim($_POST['comment_entry']);
 $author_id = $_POST['author_id'];
 $post_id = $_POST['post_id'];

 
 /* Limit the comments to alphanumeric characters, digits, and simple punctuation. */
 $valid_comment_entry = "^[A-Za-z0-9-'\"\=\+\?\!\(\)\.\:\;\,\/\@\s]+$";
 
  if (!$comment_entry) {
      $error_message = "Please enter a comment to share. <br>";
  }
  else if (strlen($comment_entry) > 150) {
      $error_message .= "Please limit your comment to 150 characters. <br>";
  }
  else if (!(valid_input($comment_entry, $valid_comment_entry))) {
      $error_message .= "Please limit your comment to letter, number, and punctuation characters. <br>";
  }
  
  if (!$error_message) {
  
    //Insert the comment into the group_wall_comments table
    $command = "INSERT INTO group_wall_comments (comment_id, post_id, author_id, comment_entry, comment_date) " . 
               "VALUES('', '" . $db->real_escape_string($post_id) .       
                       "', '" . $db->real_escape_string($author_id) . 
                       "', '" . $db->real_escape_string($comment_entry). "', " .
                       "now());"; 
                           
   $result = $db->query($command);   
  }
}

/*If the link is clicked to delete a post, mark the post as "inactive" in the database. */
if ($groupID && $_GET['delete_post']) {
     
     /*Verify the delete_post & post_id are valid, and that it only consists of numbers. */
     $delete_post = trim($_GET['delete_post']);
     $delete_post = htmlentities($delete_post);
     $post_id = trim($_GET['post_id']);
     $post_id = htmlentities($post_id);
     $valid_delete_post = "^[0-9]+$";
     
     if (!(valid_input($delete_post, $valid_delete_post))) {
      $error_message = "An error has occurred in deleting this post. <br>";
     }
     else if (!(valid_input($post_id, $valid_delete_post))) {
      $error_message = "An error has occurred in deleting this post. <br>";
     }
     
     else {
       
     //Get the current datetime 
     $date_deactivated = date('Y-m-d H:i:s');

  
     /*Update the date_deactivated field with the current date. This will effectively delete the post */
     $command = "UPDATE group_wall_posts SET date_deactivated = '". $db->real_escape_string($date_deactivated) .
                "' WHERE post_id = '" . $db->real_escape_string($post_id) . "';";
          
     $result = $db->query($command);
     }

  //Reload the group profile page again to ensure that all information (e.g., number of members) is updated seamlessly
  header("Location: http://". $_SERVER['HTTP_HOST'] . "/social/groups/group_profile.php?group_id=" . $groupID);
}

/*If the link is clicked to delete a comment, mark the post as "inactive" in the database. */
if ($groupID && $_GET['delete_comment']) {
     
     /*Verify the delete_comment & comment_id are valid, and that it only consists of numbers. */
     $delete_comment = trim($_GET['delete_comment']);
     $delete_comment = htmlentities($delete_comment);
     $comment_id = trim($_GET['comment_id']);
     $comment_id = htmlentities($comment_id);
     $valid_delete_comment= "^[0-9]+$";
     
     if (!(valid_input($delete_comment, $valid_delete_comment))) {
      $error_message = "An error has occurred in deleting this post. <br>";
     }
     else if (!(valid_input($comment_id, $valid_delete_comment))) {
      $error_message = "An error has occurred in deleting this post. <br>";
     }
     
     else {
       
     //Get the current datetime 
     $date_deactivated = date('Y-m-d H:i:s');

  
     /*Update the date_deactivated field with the current date. This will effectively delete the post */
     $command = "UPDATE group_wall_comments SET date_deactivated = '". $db->real_escape_string($date_deactivated) .
                "' WHERE comment_id = '" . $db->real_escape_string($comment_id) . "';";
          
     $result = $db->query($command);
     }

  //Reload the group profile page again to ensure that all information (e.g., number of members) is updated seamlessly
  header("Location: http://". $_SERVER['HTTP_HOST'] . "/social/groups/group_profile.php?group_id=" . $groupID);
}


//Include header
include($_SERVER['DOCUMENT_ROOT']."/.php_files/social_header.inc");
?>

<span class="signed_in">Signed in as <? echo $_SESSION['user_email']?> </span><br>
<span class="signed_in">Not <a href="../profile.php?logout=1"><? echo $_SESSION['first_name']?>? </a></span>
<br>


<?

/* If no group profile is specified, we will consider this group_profile page a "home" page
which will allow for browsing the various groups. */
if (!$groupID) {
?>
<h2>Network Groups</h2>
<h4>Make more connections by joining network groups!</h4>

<div class="full_col">
 <div class="box">
  <div class="top">
	<div class="inside">
	 <div class="title">
	  <div class="left">
	   Browse for Groups
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
      /*Here, we want to display all groups available. Initially, only ten of the most recent groups 
      will be displayed with a link to view all groups (we want to prevent from information overload 
      if there are many many groups. */
      $all_groups_array = fetch_all_groups($db);
      $array_count = count($all_groups_array);
	 
      /*If the link to view all was clicked, display the entire array of groups. */
      if ($_GET['view_all'] == '1') {
	 
	  for ($i = 0; $i <= $array_count; $i++) {
	     ?>
	          <a href="group_profile.php?group_id=<? echo $all_groups_array[$i]['group_id']; ?>">
	     <?
	           echo $all_groups_array[$i]['name'] . "</a><br> " . 
	                "<span style='color:#777;'>" . $all_groups_array[$i]['description'] . "</span><br><br>";
	      ?>
	         
	     <?
	   }
      }
      
      //Otherwise, just display the most recent ten groups (or, however many there are)
      else {
	 	 
	 if ($array_count < 10) {
	 
	   for ($i = 0; $i < $array_count; $i++) {
	     ?>
	          <a href="group_profile.php?group_id=<? echo $all_groups_array[$i]['group_id']; ?>">
	     <?
	           echo $all_groups_array[$i]['name'] . "</a><br> " . 
	                "<span style='color:#777;'>" . $all_groups_array[$i]['description'] . "</span><br><br>";
	      ?>
	         
	     <?
	   }
	 }
	 
	 else {
	   	   for ($i = 0; $i <= 10; $i++) {
	     ?>
	          <a href="group_profile.php?group_id=<? echo $all_groups_array[$i]['group_id']; ?>">
	     <?
	           echo $all_groups_array[$i]['name'] . "</a><br> " . 
	                "<span style='color:#777;'>" . $all_groups_array[$i]['description'] . "</span><br><br>";
	      ?>
	         
	     <?
	   }
	     ?>
	          <a href="group_profile.php?view_all=1">View all groups ></a><br>
	     <?
	 }
       }
       ?>

	</div>
   </div>
  </div>		
</div>
<div class="clear_both"></div>

<div class="full_col">
 <div class="box">
  <div class="top">
	<div class="inside">
	 <div class="title">
	  <div class="left">
	   Search
	  </div>
	  <div class="right">
	     
	  </div>
	  <div class="clear_both"></div>
	 </div>
	</div>
   </div>
   <div class="bot">
	<div class="inside">

        <form method="POST" action="">
        <table>
	
        <tr>
          <td align="right">
             Enter keywords:
          </td>
          <td align="left">
            <input type="text" size="25" maxlength="50" name="searchstring" value="<? echo $_POST['searchstring']; ?>">
          </td>

         <td colspan="2" align="center">
           <input type="submit" name="search" value="Search">
         </td>
       </tr>

       </table>
       </form>
       
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
      if ($matches_found) {
         echo $matches_found . "<br>";
      }
      ?>
      </span>
      	
	<?
	//Display group search results here, with links to the group
	for ($i = 0; $i < $search_count; $i++) {
	
	    ?>
	       <a href="group_profile.php?group_id=<? echo $search_array[$i]['group_id']; ?>">
	    <?
	       echo $search_array[$i]['name'] . "</a><br>";
	}
	?>

	</div>
   </div>
  </div>		
</div>
<div class="clear_both"></div>

<h4><a href="group_create.php">Create a New Group ></a></h4>

<?
}


/*If the group_id is available, we will display some information depending on several 
variables - whether the viewer of the group is a moderator, a member, a non-member 
who has requested to join, or a non-member. If a groupID is available, then display 
all information pertaining to that group here. Note: This continues for roughly the 
next 200 lines of code.*/
else if ($groupID && $memberID) {

   ?>
   <h2><? echo $group_array['name']; ?></h2>
   <?
   
   //Retrieve the status of the member signed in and find out whether they are members or moderators
   $is_member = is_member($groupID, $memberID, $db);
   $is_moderator = is_moderator($groupID, $memberID, $db);
   
   /*If the viewer is a moderator, display a message that tells them this, and display and 
   pending approvals of non-members wanting to join the group */
   if ($is_moderator) {
      ?>
        <h4>You are the moderator of this group.</h4>     
      <?
      
      $approval_pending = approval_pending($memberID, $groupID, $db);
       
       if ($approval_pending) {
       
          //If there are pending approvals, display them in a box below the general group information
          $display_approvals = true;
       
       }
   }
   
   /* If the viewer is a member of the group already, display the private profile. Also, give them 
   the option to leave the group. */
   else if ($is_member) {
      ?>
        <h4>You are a member of this group. <a href="group_profile.php?group_id=<? echo $groupID; ?>&remove_request=<? echo $memberID; ?>">Leave group</a></h4>     
      <?
   }
   

   /*If the viewer is not a member of the group, first check if they have requested membership.
    If not, give them the opportunity to do so.*/
   else {
       
       $request_pending = request_pending($memberID, $groupID, $db);
       
       if ($request_pending) {
       
          ?>
           <h4>You have requested to join this group.</h4>
          <?
       
       }       
 
       else {
       
      ?>
        <h4><a href="group_profile.php?group_id=<? echo $groupID; ?>&join_request=<? echo $groupID; ?>">Click here</a> to request membership to this group.</h4>
      <?
      
      }
   }


?>

<div class="left_col">
 <div class="box">
  <div class="top">
	<div class="inside">
	 <div class="title">
	  <div class="left">
	   Group Members <? echo "(" . count($group_members_array) . ")"; ?>
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
	   //If the view is member, display the "private profile" (other group members) 
	   if ($is_member) {
	   
	      $array_count = count($group_members_array);
	      for ($i = 0; $i < $array_count; $i++) {
	         ?>
	           <a href="../profile.php?profileID=<? echo $group_members_array[$i]['req_member']; ?>">
	         <?
	           echo $group_members_array[$i]['first_name'] . " " . $group_members_array[$i]['last_name'] . "</a><br>";
	      }
	     
	      if ($array_count <= 0) {
	        echo "No members yet.";
	      }
	   }
	  
	  //If not a member, advise them to join in order to see the others
	   else {
	     echo "You must join this group to view the list of members";
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
	  Group Profile
	 </div>
	 <div class="right">
	 <?  if ($is_moderator) { 
	      ?>
	       <a href="group_edit.php?group_id=<? echo $group_array['group_id'] ?>">Edit ></a>
	      <? 
	     } 
	 ?>
	 </div>	
	 <div class="clear_both"></div>
	</div>
   </div>
  </div>
  <div class="bot">
   <div class="inside">
	
	<?
            //Show public profile regardless of whether viewer is a member or not
		   
	    $group_size = count($group_members_array);
	    
	    echo "<b>Name:</b> " . $group_array['name'] . "<br>";
	    echo "<b>Description:</b> " . $group_array['description'] . "<br>";
	    echo "<b>Category:</b> " . $group_array['category'] . "<br>";
	    echo "<b>Members:</b> " . $group_size . "<br>";
	    echo "<b>Moderator:</b> " . $group_moderator_array['0']['first_name'] . " " . $group_moderator_array['0']['last_name'] . "<br>";
	  
	?>
	
	
	
   </div>
  </div>
 </div>
</div>
<div class="clear_both"></div>

<?

//If there are pending approvals, display them here in the page
if ($display_approvals == true) {

?>

<div class="full_col">
 <div class="box">
  <div class="top">
	<div class="inside">
	 <div class="title">
	  <div class="left">
	   Pending Join Requests
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
	      //Fetch an array of pending requests information 
              $pending_approvals_array = fetch_pending_approvals($memberID, $groupID, $db);
	     
	      if ($pending_approvals_array) {
	      
	         $count = count($pending_approvals_array); 
	      
	         /*Display each pending request using a loop function. Each request will be a form
	         that will allow the user to either confirm or deny the request*/
	         for ($i = 0; $i < $count; $i++) {
	         
	           echo "<form method='post' action=''><table>";
	           echo "<tr><td>Name: <a href='../profile.php?profileID=". $pending_approvals_array[$i]['req_member'] ."'>" . 
	                $pending_approvals_array[$i]['first_name'] . " " . $pending_approvals_array[$i]['last_name'] . "</a></td></tr>";        
	           echo "<tr><td><span style='color:#777;'>Request Date: " . $pending_approvals_array[$i]['req_date'] . "</span></td></tr>";       
	           echo "<tr><td><input type='hidden' name='req_member' value=". $pending_approvals_array[$i]['req_member'] ." /></td>";
	           echo "<td><input type='hidden' name='app_member' value=". $pending_approvals_array[$i]['app_member'] ." /></td></tr>";
	           echo "<tr><td colspan='2'><input type='hidden' name='group_id' value=". $groupID ." /></td></tr>";
	           echo "<tr><td colspan='2' align='left'><input name='confirm' type='submit' value='Confirm' />&nbsp;&nbsp;";
	           echo "<input name='deny' type='submit' value='Not Now' /></td></tr>";
	           echo "<tr><td colspan='2'>&nbsp;</td></tr>";
	           echo "</table></form>";
	         }
	     } 
	  ?>
	</div>
   </div>
  </div>		
</div>
<div class="clear_both"></div>

<?
}
?>

<div class="full_col">
 <div class="box">
  <div class="top">
	<div class="inside">
	 <div class="title">
	  <div class="left">
	   Group Forum
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
       /*A confirmed friendship is required to view the member's wall posts. If no friendship
       is established, display a message. Otherwise, display a form to create wall posts, and
       query the database for existing posts and comments. */
        if ($is_member) {
        
        ?>
        <form method="POST" action="">
        <table>
	
        <tr>
          <td colspan="2" align="left">
           <textarea rows="4" cols="70" maxlength="150" name="post_entry"  placeholder="Share your thoughts.." style="resize:none"><? if ($error_message) {echo $_POST['post_entry'];} ?></textarea>
          </td>
        </tr>
       
        <tr>
         <td colspan="2" align="right">
           <input type="hidden" name="author_id" value="<? echo $memberID; ?>">
           <input type="hidden" name="group_id" value="<? echo $groupID; ?>">
           <input type="submit" name="create_wall_post" value="Post">
         </td>
       </tr>

       </table>
       </form>
       <?
       
         //Display any error messages here  regarding posted to a members wall
         if ($error_message) { echo "<span style='color:red;'>" . $error_message . "</span><br>";}
       
         /*Retrieve the wall posts for a member, based on their profile ID. Get a count of the 
         array to be used in the loop. We want to limit the number of post displayed (for instance, 
         if the member had 1000 posts, we do notwant to display them all at once. Limit the wall posts
         to 20 of the most recent posts. */
         $wall_post_array = fetch_wall_posts($groupID, $db);
         $post_count = count($wall_post_array);
         
         if ($post_count > 20) {
             $post_count = 20;
         }
         
         /*If there are wall posts found for the member, cycle through each of them and display the 
         name of the poster, date of the post, and option to delete the post (if the post is on the 
         member's page), the post itself, and an option to make a new comment. */                
         if ($wall_post_array) {
            
            echo "<div>";
	    for ($i = 0; $i < $post_count; $i++) {
             
              echo "<div class='wall_posts'>";
	        echo "<a href='../profile.php?profileID=" .  $wall_post_array[$i]['memberID'] .  "'>". 
	        $wall_post_array[$i]['first_name'] . " " . $wall_post_array[$i]['last_name'] . "</a>";
	        
                echo "  <span style='color:#777;'> at " . $wall_post_array[$i]['post_date'] . ", wrote: </span> "; 
                 
                     if ($is_moderator){
                   
                     echo " <a class='delete_post' href='group_profile.php?group_id=" . $groupID .  
                     "&delete_post=1&post_id=" . $wall_post_array[$i]['post_id']. "'>&#10007;</a>";
                     
                     }
                     
                echo "<br>" . $wall_post_array[$i]['post_entry'] . "<br><br>";
                
                /*For each post, check for any related comments (these are related by post_id's 
                in the member_wall_comments and member_wall_comments tables) using a sub-loop. If
                comments are found for the post, display them, along with a link to delete them. */
                $wall_comments_array = fetch_wall_comments($wall_post_array[$i]['post_id'], $db);
                $comments_count = count($wall_comments_array);
                
                if ($wall_comments_array) {
                   echo "<div class='wall_comments'>";
                   
                   for ($j = 0; $j < $comments_count; $j++) {
                     
                     echo "<a href='../profile.php?profileID=" .  $wall_comments_array[$j]['memberID'] .  "'>". 
	             $wall_comments_array[$j]['first_name'] . " " . $wall_comments_array[$j]['last_name'] . "</a>";
                     echo "<span style='color:#777;'> at " . $wall_comments_array[$j]['comment_date'] . " commented: </span>";
                     
                       if ($is_moderator) {
                   
                       echo "  <a class='delete_post' href='group_profile.php?group_id=" . $groupID .  
                       "&delete_comment=1&comment_id=" . $wall_comments_array[$j]['comment_id']. "'>&#10007;</a> ";
                     
                       }
                     
                     echo "<br>" . $wall_comments_array[$j]['comment_entry'] . "<br><br>";
                   
                   }
                   echo "</div>";
                }

        ?>
        <form method="POST" action="">
        <table style="margin-left:10px;">
	
        <tr>
          <td colspan="2" align="left">
           <textarea rows="1" cols="60" maxlength="150" name="comment_entry" placeholder="Write a comment.." style="resize:none"><? if ($error_message) {echo $_POST['comment_entry'];} ?></textarea>
          </td>
        </tr>
       
        <tr>
         <td colspan="2" align="right">
           <input type="hidden" name="author_id" value="<? echo $memberID; ?>">
           <input type="hidden" name="post_id" value="<? echo $wall_post_array[$i]['post_id']; ?>">
           <input type="submit" name="create_wall_comment" value="Comment">
         </td>
       </tr>

       </table>
       </form>
       <?


              echo "</div>";
            }
            echo "</div>";
         }
       }
       else {
       echo "You must be a member of this group to view their discussion activity.";
       
       }
       ?>
	</div>
   </div>
  </div>		
</div>
<div class="clear_both"></div>


<h4><a href="group_profile.php">Browse Groups ></a></h4>
<?
}
?>


<h4><a href="../profile.php?profileID=<?echo $_SESSION['memberID']; ?>">Return to My Profile ></a></h4>


<?

//Include footer 
include($_SERVER['DOCUMENT_ROOT']."/.php_files/social_footer.inc");

/*
echo "Session array: <br>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "Group Members array: <br>";
echo "<pre>";
print_r($group_members_array);
echo "</pre>";

echo "Group array: <br>";
echo "<pre>";
print_r($group_array);
echo "</pre>";

echo "Group Moderator array: <br>";
echo "<pre>";
print_r($group_moderator_array);
echo "</pre>";

echo "Pending approvals array: <br>";
echo "<pre>";
print_r(fetch_pending_approvals($memberID, $groupID, $db));
echo "</pre>";



echo "Is a member?: " . is_member($groupID, $memberID, $db) . "<br>";
echo "Is a moderator?: " . is_moderator($groupID, $memberID, $db) . "<br>";
echo "Pending approvals?: " . approval_pending($memberID, $groupID, $db) . "<br>";
echo "Pending requests?: " . request_pending($memberID, $groupID, $db) . "<br>";

echo "All groups array: <br>";
echo "<pre>";
print_r(fetch_all_groups($db));
echo "</pre>";

echo "POST array: <br>";
echo "<pre>";
print_r($_POST);
echo "</pre>";
*/

//Close the connection
$db->close();
?> 
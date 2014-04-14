<?php

/*Connect using the username "social_member" on the database "social_network" */
include($_SERVER['DOCUMENT_ROOT']."/social/social_member_login.php");

$db = new mysqli($host,$user,$pw,$database,$port,$socket) 
      or die("Cannot connect to mySQL.");

################
#              #
#  FUNCTIONS   #
#              #
################

  function fetch_profile($profileID, $db) {
    $profile_array = array();
    if ($profileID) {
       //make sure that the profile exists
       $command = "SELECT mi.first_name, mi.last_name, mi.gender, mi.relationship, mi.birthdate, " . 
                  "mi.address, mi.city, mi.state, mi.zip, mi.country, mi.date_enrolled, ml.email FROM " . 
                  "member_info as mi, member_login as ml WHERE mi.memberID = ml.memberID AND " . 
                  "ml.date_deactivated <= 0 AND mi.memberID = '". $db->real_escape_string($profileID) ."';";
                  
       $result = $db->query($command);
       
       if ($result->num_rows > 0) {
          $profile_array = $result->fetch_assoc();
       }
    }
    return $profile_array;    
  }
  
  function fetch_friends($profileID, $db) {
    //friends array is a two dimensional array
    //it holds arrays of friend's information
    $friends_array = array();
    
    if ($profileID) {
      $command = "SELECT mi.memberID, mi.first_name, mi.last_name FROM  member_info mi, member_login ml, member_friends mf " . 
                 "WHERE mi.memberID = ml.memberID AND ((mi.memberID = mf.req_member AND mf.app_member='". $db->real_escape_string($profileID)."') " .
                 "OR (mi.memberID = mf.app_member AND mf.req_member = '". $db->real_escape_string($profileID)."')) " .
                 "AND mf.date_deactivated <= 0 AND mf.app_date > 0;";
                 
      $result = $db->query($command);
      
      if ($result->num_rows > 0) {
                 
         while ($thisfriend_array = $result->fetch_assoc()) {
            array_push($friends_array, $thisfriend_array);
         }
      }
     }
    return $friends_array;
  }
  
  function are_friends($memberID_1, $memberID_2, $db) {
     //returns a boolean value
     $friends = false;
     if ($memberID_1 && $memberID_2) {
        $command = "SELECT friendID FROM member_friends WHERE " .
                   "((req_member='".$db->real_escape_string($memberID_1)."' AND app_member='".$db->real_escape_string($memberID_2)."') OR " .
                   "(req_member='".$db->real_escape_string($memberID_2)."' AND app_member='".$db->real_escape_string($memberID_1)."')) " . 
                   "AND app_date > 0 AND date_deactivated <= 0;";
        $result = $db->query($command);
        
        if ($data = $result->fetch_object()) {
           $friends = true;
        }
     }
     return $friends;
  }

  function approval_pending($memberID_1, $memberID_2, $db) {
     //returns a boolean value
     $pending = false;
     if ($memberID_1 && $memberID_2) {
        $command = "SELECT friendID FROM member_friends WHERE " .
                   "req_member='".$db->real_escape_string($memberID_2)."' AND app_member='" . $db->real_escape_string($memberID_1) . "' ".
                   "AND app_date <= 0  AND date_deactivated <= 0;";
                   
        $result = $db->query($command);
        
       if ($result->num_rows > 0) {
           $pending = true;
        }
     }
     return $pending;
  }
  
  function request_pending($memberID_1, $memberID_2, $db) {
     //returns a boolean value
     $pending = false;
     if ($memberID_1 && $memberID_2) {
        $command = "SELECT friendID FROM member_friends WHERE " .
                   "req_member='".$db->real_escape_string($memberID_1)."' AND app_member='".$db->real_escape_string($memberID_2)."' ".
                   "AND app_date <= 0  AND date_deactivated <= 0;";
                        
        $result = $db->query($command);
        
        if ($result->num_rows > 0) {
           $pending = true;
        }
     }
     return $pending;
  }
  
  function fetch_my_groups_members($profileID) {
  
    /*Connect using the username "group_login" on the database "social_network". */
    include($_SERVER['DOCUMENT_ROOT']."/social/groups/group_login_login.php");

    $db = new mysqli($host,$user,$pw,$database,$port,$socket) 
      or die("Cannot connect to mySQL.");
    
    //group_members array is a two dimensional array that holds the names of the group's members
    $my_group_membership_array = array();
    
    //Find all the members the of the group that are currently active
    if ($profileID) {
      $command = "SELECT g.name, gr.group_id, gr.req_member FROM groups g, group_requests gr " . 
                 "WHERE gr.group_id = g.group_id AND gr.req_member = '" . $db->real_escape_string($profileID) . "' " .
                 "AND g.date_deactivated <= 0 AND gr.date_deactivated <= 0 AND gr.app_date > 0;";
                 

      $result = $db->query($command);
      
      if ($result->num_rows > 0) {
                 
         while ($data_array = $result->fetch_assoc()) {
            array_push($my_group_membership_array, $data_array);
         }
      }
     $db->close();
     }

    return $my_group_membership_array;
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
  
 /*This function runs a query to retrieve all of the member's wall posts*/
  function fetch_wall_posts($profileID, $db) {

    $wall_post_array = array();
    
    if ($profileID) {
      $command = "SELECT mwp.post_id, mwp.author_id, mwp.wall_id, mwp.post_entry, date_format(mwp.post_date, '%M %D, %Y %h:%i %p') as post_date, " . 
                 "mi.memberID, mi.first_name, mi.last_name " . 
                 "FROM member_wall_posts mwp, member_info mi where mi.memberID = mwp.author_id " . 
                 "AND mwp.wall_id = '" . $db->real_escape_string($profileID) . "' " . 
                 "AND mwp.date_deactivated <= 0 ORDER BY mwp.post_date DESC;";
                                                 
      $result = $db->query($command);
      
      if ($result->num_rows > 0) {
                 
         while ($data_array = $result->fetch_assoc()) {
            array_push($wall_post_array, $data_array);
         }
      }
     }
    return $wall_post_array;
  }
  
   /*This function runs a query to retrieve all of the member's wall posts comments */
  function fetch_wall_comments($post_id, $db) {

    $wall_comments_array = array();
    
    if ($post_id) {
      $command = "SELECT mwp.post_entry, mwc.comment_id, mwc.comment_entry, mwc.post_id, mwc.author_id, " . 
                 "date_format(mwc.comment_date, '%M %D, %Y %h:%i %p') as comment_date, mi.memberID, mi.first_name, mi.last_name " . 
                 "FROM member_wall_posts mwp, member_info mi, member_wall_comments mwc WHERE mi.memberID = mwc.author_id " . 
                 "AND mwc.post_id = mwp.post_id " .  
                 "AND mwc.post_id = '" . $db->real_escape_string($post_id) . "' " . 
                 "AND mwc.date_deactivated <= 0 ORDER BY mwc.comment_date ASC;";
                      

      $result = $db->query($command);
           
      if ($result->num_rows > 0) {
                 
         while ($data_array = $result->fetch_assoc()) {
            array_push($wall_comments_array, $data_array);
         }
      }
     }
    return $wall_comments_array;
  }
  


################
#              #
#  REQUEST     #
#  HANDLING    #
#              #
################

//find out who's logged in
session_start();
$memberID = $_SESSION['memberID'];

if ($_GET['logout'] == '1') {
session_destroy();
header("Location: /social/index.php");
}

//now find out which member's profile to display
//profileID is this member's memberID
$profileID = $_GET['profileID'];

if (!($memberID || $profileID)) {
   //we've got nothing to display, so redirect to the home page
   header("Location: /social/index.php");
}


$myprofile_array = array();

if ($profileID) {
   $myprofile_array = fetch_profile($profileID, $db);
}

if ((count($myprofile_array) <= 0) && $memberID) {
   //check for this profile
   $myprofile_array = fetch_profile($memberID, $db);
   $profileID = $memberID;
}

if (count($myprofile_array) <= 0) {
   //cannot find profile! Redirect
   header("Location: /social/index.php");
}

/*Create session variables that will persist after leaving the page is the profile was fetched successfully*/
if (isset($myprofile_array) && $memberID == $profileID) {
$_SESSION['user_email'] = $myprofile_array['email'];
$_SESSION['first_name'] = $myprofile_array['first_name'];
$_SESSION['last_name'] = $myprofile_array['last_name'];
$_SESSION['gender'] = $myprofile_array['gender'];
$_SESSION['relationship'] = $myprofile_array['relationship'];
$_SESSION['birthdate'] = $myprofile_array['birthdate'];
$_SESSION['address'] = $myprofile_array['address'];
$_SESSION['city'] = $myprofile_array['city'];
$_SESSION['state'] = $myprofile_array['state'];
$_SESSION['zip'] = $myprofile_array['zip'];
$_SESSION['country'] = $myprofile_array['country'];
$_SESSION['date_enrolled'] = $myprofile_array['date_enrolled'];
$_SESSION['country'] = $myprofile_array['country'];
}

/*If the link is clicked to de-friend somebody, mark the friendship as "inactive" in the database. */
if ($profileID && $_GET['remove_request']) {
     
     /*In order to prevent anybody with mischievious or malicious intentions from hacking
     into our database, first verify the remove_request id (the member ID) is valid, 
     and that it only consists of numbers. */
     $remove_request = trim($_GET['remove_request']);
     $remove_request = htmlentities($remove_request);
     $valid_remove_request = "^[0-9]+$";
     
     if (!(valid_input($remove_request, $valid_remove_request))) {
      $error_message = "An error has occurred in removing this member. <br>";
     }
     else {
       
     //Get the current datetime 
     $date_deactivated = date('Y-m-d H:i:s');

  
     /*Update the date_deactivated field with the current date. This will effectively delete the member
     from the user's network. Because of the structure of the table, we will not know who originally
     requested whose friendship. Therefore, we must use an UPDATE statement with OR. */
     $command = "UPDATE member_friends SET date_deactivated = '". $db->real_escape_string($date_deactivated) .
                "' WHERE ((req_member = '" . $db->real_escape_string($remove_request) . 
                "' AND app_member = '" . $db->real_escape_string($profileID) . "') OR " . 
                " (app_member = '" . $db->real_escape_string($remove_request) . 
                "' AND req_member = '" . $db->real_escape_string($profileID) . "'));";
          
     $result = $db->query($command);
     }

  //Reload the group profile page again to ensure that all information (e.g., number of members) is updated seamlessly
  header("Location: http://". $_SERVER['HTTP_HOST'] . "/social/profile.php?profileID=" . $profileID);
}

//Process new wall posts here
if ($_POST['create_wall_post']) {

  /*First, check to make sure that the post exists and contains no more than 150 characters. If not, 
  display a message to user. Otherwise, insert the post into the member_wall_posts table.*/
 $post_entry = trim($_POST['post_entry']);
 $author_id = $_POST['author_id'];
 $wall_id = $_POST['wall_id'];

 
 /* Limit the post to alphanumeric characters, digits, and simple punctuation. */
 $valid_post_entry = "^[A-Za-z0-9-\'\"\=\+\?\!\(\)\.\:\;\,\/\@\s]+$";
 
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
  
    //Insert the post into the member_wall_posts table
    $command = "INSERT INTO member_wall_posts (post_id, author_id, wall_id, post_entry, post_date) " . 
               "VALUES('', '" . $db->real_escape_string($author_id) .       
                       "', '" . $db->real_escape_string($wall_id) . 
                       "', '" . $db->real_escape_string($post_entry). "', " .
                       "now());"; 
                           
   $result = $db->query($command);   
 
  }
  
}

//Process comments here 
if ($_POST['create_wall_comment']) {

  /*First, check to make sure that the commentexists and contains no more than 150 characters. If not, 
  display a message to user. Otherwise, insert the commentinto the member_wall_comments table.*/
 $comment_entry = trim($_POST['comment_entry']);
 $author_id = $_POST['author_id'];
 $post_id = $_POST['post_id'];

 
 /* Limit the post to alphanumeric characters, digits, and simple punctuation. */
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
  
    //Insert the commentinto the member_wall_comments table
    $command = "INSERT INTO member_wall_comments (comment_id, post_id, author_id, comment_entry, comment_date) " . 
               "VALUES('', '" . $db->real_escape_string($post_id) .       
                       "', '" . $db->real_escape_string($author_id) . 
                       "', '" . $db->real_escape_string($comment_entry). "', " .
                       "now());"; 
                           
   $result = $db->query($command);   
  }
}

/*If the link is clicked to delete a post, mark the post as "inactive" in the database. */
if ($profileID && $_GET['delete_post']) {
     
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
     $command = "UPDATE member_wall_posts SET date_deactivated = '". $db->real_escape_string($date_deactivated) .
                "' WHERE post_id = '" . $db->real_escape_string($post_id) . "';";
          
     $result = $db->query($command);
     }

  //Reload the group profile page again to ensure that all information (e.g., number of members) is updated seamlessly
  header("Location: http://". $_SERVER['HTTP_HOST'] . "/social/profile.php?profileID=" . $profileID);
}

/*If the link is clicked to delete a comment, mark the post as "inactive" in the database. */
if ($profileID && $_GET['delete_comment']) {
     
     /*Verify the delete_comment& comment_id are valid, and that it only consists of numbers. */
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
     $command = "UPDATE member_wall_comments SET date_deactivated = '". $db->real_escape_string($date_deactivated) .
                "' WHERE comment_id = '" . $db->real_escape_string($comment_id) . "';";
          
     $result = $db->query($command);
     }

  //Reload the group profile page again to ensure that all information (e.g., number of members) is updated seamlessly
  header("Location: http://". $_SERVER['HTTP_HOST'] . "/social/profile.php?profileID=" . $profileID);
}


//Include header
include($_SERVER['DOCUMENT_ROOT']."/.php_files/social_header.inc");
?>

<span class="signed_in">Signed in as <? echo $_SESSION['user_email']?> </span><br>
<span class="signed_in">Not <a href="profile.php?logout=1"><? echo $_SESSION['first_name']?>? </a></span>
<br>
<h2><? echo $myprofile_array['first_name']. " " . $myprofile_array['last_name']; ?></h2>

<?
$is_my_friend = false;
if ($memberID) {
   
   if (are_friends($memberID, $profileID, $db)) {
      ?>
        <h4>You are friends with this member. <a href="profile.php?profileID=<? echo $profileID; ?>&remove_request=<? echo $memberID; ?>">Remove from your network</a></h4>     
      <?
        $is_my_friend = true;
   }
   else if ($memberID == $profileID) {
        
        $is_my_friend = true;
        $display_wall_posts = true;
                
   }
   else if (!($memberID == $profileID)) {
   
      if (request_pending($memberID, $profileID, $db)) {
      ?>
        <h4>You have requested friendship with this member.</h4>
      <?
      }
      else if (approval_pending($memberID, $profileID, $db)) {
      ?>
        <h4>This member has requested friendship with you. <a href="request.php">Click here</a> to approve the request.</h4>.</h4>
      <?
      }
      else {
      ?>
        <h4><a href="request.php?friend_request=<? echo $profileID; ?>">Click here</a> to request friendship with this member.</h4>
      <?
      }
   }
}

?>

<div class="left_col">
 <div class="box">
  <div class="top">
	<div class="inside">
	 <div class="title">
	  <div class="left">
	   My Friends <?  echo "(" . count(fetch_friends($profileID, $db)) .  ")"; ?>
	  </div>
	  <div class="right">
	  <? if ($memberID == $profileID) {
	     ?>
	      <a href="invite.php">Invite ></a>
	     <? } 
	  ?>
	     
	  </div>
	  <div class="clear_both"></div>
	 </div>
	</div>
   </div>
   <div class="bot">
	<div class="inside">
	 
	 <?
	   $friends_array = fetch_friends($profileID, $db);
	   $array_count = count($friends_array);
	   while (list($key, $this_friend) = each($friends_array)) {
	      ?>
	        <a href="profile.php?profileID=<? echo $this_friend['memberID']; ?>">
	      <?
	        echo $this_friend['first_name'] . " " . $this_friend['last_name'];
	      ?>
	        </a><br>
	      <?
	   }
	   if ($array_count <= 0) {
	     echo "No friends yet.";
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
	  My Profile
	 </div>
	 <div class="right">
	 <?  if ($memberID == $profileID) { 
	    ?>
	     <a href="edit.php">Edit ></a>
	    <? } 
	 ?>
	 </div>	
	 <div class="clear_both"></div>
	</div>
   </div>
  </div>
  <div class="bot">
   <div class="inside">
	
	<?
	
	  $my_group_membership_array = fetch_my_groups_members($profileID);
	   
	  $found = 0;
	  if ($myprofile_array['gender'] == 'm') {
	     echo "Gender: Male<br>";
	     $found = 1;
	  }
	  else if ($myprofile_array['gender'] == 'f') {
	    echo "Gender: Female<br>";
	    $found = 1;
	  }
	  if ($myprofile_array['relationship']) {
	    echo "Relationship Status: " . $myprofile_array['relationship'] . "<br>";
	    $found = 1;
	  }
	  if ($myprofile_array['birthdate'] && $myprofile_array['birthdate'] != '0000-00-00') {
	    echo "Birth Date: " . $myprofile_array['birthdate']  . "<br>";
	    $found = 1;
	  }
	  if ($is_my_friend) {
	   
	     if ($myprofile_array['address']) {
	        echo "Address: <table><tr><td>" .
                     $myprofile_array['address']."<br>" .
                     $myprofile_array['city'].", ".$myprofile_array['state']." ".$myprofile_array['zip']."<br>" .
                     $myprofile_array['country']."</td></tr></table><br>";
               $found = 1;
	     }
	     
	     if ($myprofile_array['email']) {
               echo "Email: ".$myprofile_array['email']."<br>";
               $found = 1;
	     }
	     
	     if (count($my_group_membership_array) > 0) {
	        
	        echo "<br>Member of: <br>";
	        while (list($key, $this_group) = each($my_group_membership_array)) {
	          ?>
	            <a href="groups/group_profile.php?group_id=<? echo $this_group['group_id']; ?>">
	          <?
	            echo $this_group['name'];
	          ?>
	            </a><br>
	          <?
	        }
	     }
	     else {
	       echo "<br>No group memberships.";
	     }
	     
	  }
         if (!($found)) {
            echo "No Profile Yet.";
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
	   My Wall Posts
	 <span style='float:right;margin-right:25px;'>
	 <?  if ($memberID == $profileID) { 
	    ?>
	     <a href="communication/newsfeed.php">News feed ></a>
	    <? } 
	 ?>
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
        if ($is_my_friend) {
        
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
           <input type="hidden" name="wall_id" value="<? echo $profileID; ?>">
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
         $wall_post_array = fetch_wall_posts($profileID, $db);
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
	        echo "<a href='profile.php?profileID=" .  $wall_post_array[$i]['memberID'] .  "'>". 
	        $wall_post_array[$i]['first_name'] . " " . $wall_post_array[$i]['last_name'] . "</a>";
	        
                echo "  <span style='color:#777;'> at " . $wall_post_array[$i]['post_date'] . ", wrote: </span> "; 
                 
                     if ($memberID == $profileID || $memberID == $wall_post_array[$i]['memberID']){
                   
                     echo " <a class='delete_post' href='profile.php?profileID=" . $profileID .  
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
                     
                     echo "<a href='profile.php?profileID=" .  $wall_comments_array[$j]['memberID'] .  "'>". 
	             $wall_comments_array[$j]['first_name'] . " " . $wall_comments_array[$j]['last_name'] . "</a>";
                     echo "<span style='color:#777;'> at " . $wall_comments_array[$j]['comment_date'] . " commented: </span>";
                     
                       if ($memberID == $profileID  || $memberID == $wall_comments_array[$i]['memberID']) {
                   
                       echo "  <a class='delete_post' href='profile.php?profileID=" . $profileID .  
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
       echo "You must be friends with this member to view their wall post activity.";
       
       }
       ?>
	</div>
   </div>
  </div>		
</div>
<div class="clear_both"></div>

<?

if (!($memberID == $profileID)) {
   ?>
   <br>
   <h4><a href="profile.php?profileID=<?echo $_SESSION['memberID']; ?>">Return to My Profile ></a></h4>
   <?
}
else {
   ?>
   <br>
   <h4><a href="request.php">View Requests ></a></h4>
   <h4><a href="groups/group_profile.php">Browse Groups ></a></h4>
   <h4><a href="search.php">Search For Friends ></a></h4>
   <?
}



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

echo request_pending($memberID, $profileID, $db);
echo approval_pending($memberID, $profileID, $db);

echo "Friends array: <br>";
echo "<pre>";
print_r($friends_array);
echo "</pre>";

echo "My groups array: <br>";
echo "<pre>";
print_r(fetch_my_groups_members($profileID));
echo "</pre>";

echo "Wall post array: <br>";
echo "<pre>";
print_r($wall_post_array);
echo "</pre>";

echo "Post array: <br>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

echo "Comments array: <br>";
echo "<pre>";
print_r(fetch_wall_comments(5, $db));
echo "</pre>";
*/


$db->close();
?> 
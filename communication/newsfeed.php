<?php
/*newsfeed.php -- interface for displaying the communications between friends */

//Start the session and set the member ID to the current session's memberID variable
session_start();
$memberID = $_SESSION['memberID'];

/*Ensure that the user is logged in*/
if (!$memberID) {
   
    //If the user is not logged in, redirect them to the home page:
    header("Location: http://". $_SERVER['HTTP_HOST'] ."/social/index.php");
}

/*Connect using the username "group_login" on the database "social_network" */
include($_SERVER['DOCUMENT_ROOT']."/social/social_member_login.php");

$db = new mysqli($host,$user,$pw,$database,$port,$socket) 
      or die("Cannot connect to mySQL.");

################
#              #
#  FUNCTIONS   #
#              #
################


  //This function will query all friends for the member (including the member himself)
  function fetch_friends($profileID, $db) {
    //friends array is a two dimensional array
    //it holds arrays of friend's information
    $friends_array = array();
    
    if ($profileID) {
      $command = "SELECT DISTINCT mi.memberID, mi.first_name, mi.last_name FROM  member_info mi, member_login ml, member_friends mf " . 
                 "WHERE mi.memberID = ml.memberID AND ((mi.memberID = mf.req_member AND mf.app_member='". $db->real_escape_string($profileID)."') " .
                 "OR (mi.memberID = mf.app_member AND mf.req_member = '". $db->real_escape_string($profileID)."')" .
                 "OR (mi.memberID = '". $db->real_escape_string($profileID). "'))  AND mf.date_deactivated <= 0 AND mf.app_date > 0;";
                 
      $result = $db->query($command);
      
      if ($result->num_rows > 0) {
                 
         while ($thisfriend_array = $result->fetch_assoc()) {
            array_push($friends_array, $thisfriend_array);
         }
      }
     }
    return $friends_array;
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
  
 /*This function runs a query to retrieve all of the member's friends' wall posts*/
  function fetch_all_wall_posts($profileID, $db) {

    $wall_post_array = array();
    
    if ($profileID) {
      $command = "SELECT DISTINCT mwp.post_id, mwp.author_id, mwp.wall_id, mwp.post_entry, date_format(mwp.post_date, '%M %D, %Y %h:%i %p') as post_date, " . 
                 "mi.memberID, mi.first_name, mi.last_name " . 
                 "FROM member_wall_posts mwp, member_friends mf, member_info mi where mi.memberID = mwp.author_id " . 
                 "AND ((mwp.author_id = '" . $db->real_escape_string($profileID) . "') " .
                 "OR ((mi.memberID = mf.req_member AND mf.app_member = '" . $db->real_escape_string($profileID) . "') " .
                 "OR (mi.memberID = mf.app_member AND mf.req_member = '" . $db->real_escape_string($profileID) . "') AND mf.date_deactivated <= 0 AND mf.app_date > 0)) " . 
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
  
   /*This function runs a query to retrieve all of the member's friends' wall posts comments */
  function fetch_all_wall_comments($post_id, $db) {

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


//Include header
include($_SERVER['DOCUMENT_ROOT']."/.php_files/social_header.inc");
?>


<span class="signed_in">Signed in as <? echo $_SESSION['user_email']?> </span><br>
<span class="signed_in">Not <a href="../profile.php?logout=1"><? echo $_SESSION['first_name']?>? </a></span>
<br>

<h2>News Feed for <a href="../profile.php?profileID=<?echo $_SESSION['memberID']; ?>"><? echo $_SESSION['first_name'] . " " . $_SESSION['last_name'] ?></a></h2>


<div class="full_col">
 <div class="box">
  <div class="top">
	<div class="inside">
	 <div class="title">
	  <div class="left">
	   News Feed
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
          <td colspan="2" align="left">
           <textarea rows="4" cols="70" maxlength="150" name="post_entry"  placeholder="Share your thoughts.." style="resize:none"><? if ($error_message) {echo $_POST['post_entry'];} ?></textarea>
          </td>
        </tr>
       
        <tr>
         <td colspan="2" align="right">
           <input type="hidden" name="author_id" value="<? echo $memberID; ?>">
           <input type="hidden" name="wall_id" value="<? echo $memberID; ?>">
           <input type="submit" name="create_wall_post" value="Post">
         </td>
       </tr>

       </table>
       </form>
       <?
       
       
       
         //Display any error messages here  regarding posted to a members wall
         if ($error_message) { echo "<span style='color:red;'>" . $error_message . "</span><br>";}
       
         /*Retrieve the wall posts created by the member or the member's friends. Get a count of the array to be used 
         in the loop. We want to limit the number of posts displayed (for instance, if the member had 1000 posts, we do
         not want to display them all at once. Limit the wall posts to 50 of the most recent posts. */

         $newsfeed_array = fetch_all_wall_posts($memberID, $db);   

         $newsfeed_count = count($newsfeed_array);
         
         if ($newsfeed_count > 50) {
             $newsfeed_count = 50;
         }
         
         /*If there are wall posts found for the member, cycle through each of them. This information is
         held in a two dimensional array. Display the name of the poster, date of the post, and option 
         to delete the post (if the post was created by the user), the post itself, and an option to make 
         a new comment. If the member or the member's friends have not posted anything yet, display a message
         stating "No wall post activity yet". */                
         if ($newsfeed_array) {
            
         echo "<div>";
         for ($f = 0; $f < $newsfeed_count; $f++) {
         
             
              echo "<div class='wall_posts'>";
	        echo "<a href='../profile.php?profileID=" .  $newsfeed_array[$f]['memberID'] .  "'>". 
	       $newsfeed_array[$f]['first_name'] . " " . $newsfeed_array[$f]['last_name'] . "</a>";
	        
                echo "  <span style='color:#777;'> at " . $newsfeed_array[$f]['post_date'] . ", wrote: </span> "; 
                 
                 
                     /*Allow the member to delete their own posts, but no one else's */
                     if ($memberID == $newsfeed_array[$f]['memberID']){
                   
                       echo " <a class='delete_post' href='newsfeed.php?profileID=" . $memberID .   
                       "&delete_post=1&post_id=" . $newsfeed_array[$f]['post_id']. "'>&#10007;</a>";
                     
                     }
                     
                echo "<br>" . $newsfeed_array[$f]['post_entry'] . "<br><br>";
                
                /*For each post, check for any related comments (these are related by post_id's 
                in the member_wall_comments and member_wall_comments tables) using another sub-loop. If
                comments are found for the post, display them, along with a link to delete them. */
                $wall_comments_array = fetch_all_wall_comments($newsfeed_array[$f]['post_id'], $db);
                $comments_count = count($wall_comments_array);
                
                if ($wall_comments_array) {
                   echo "<div class='wall_comments'>";
                   
                   for ($j = 0; $j < $comments_count; $j++) {
                     
                     echo "<a href='../profile.php?profileID=" .  $wall_comments_array[$j]['memberID'] .  "'>". 
	             $wall_comments_array[$j]['first_name'] . " " . $wall_comments_array[$j]['last_name'] . "</a>";
                     echo "<span style='color:#777;'> at " . $wall_comments_array[$j]['comment_date'] . " commented: </span>";
                     
                       /*Allow the member to delete their own posts, but no one else's */
                       if ($memberID == $wall_comments_array[$j]['memberID']) {
                   
                         echo "  <a class='delete_post' href='newsfeed.php?profileID=" . $memberID .  
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
           <input type="hidden" name="post_id" value="<? echo $newsfeed_array[$f]['post_id']; ?>">
           <input type="submit" name="create_wall_comment" value="Comment">
         </td>
       </tr>

       </table>
       </form>
       <?

              echo "</div>";
            }  //End Loop
       echo "</div>";     
       }   //End if statement
       
       else {

            echo "No wall activity yet.";

       }  

         
 
       ?>
	</div>
   </div>
  </div>		
</div>
<div class="clear_both"></div>




<?

//Include footer 
include($_SERVER['DOCUMENT_ROOT']."/.php_files/social_footer.inc");

/*
echo "POST array: <br>";
echo "<pre>";
print_r($_POST);
echo "</pre>";




echo "Friends array: <br>";
echo "<pre>";
print_r($friends_array);
echo "</pre>";


echo "newsfeed array: <br>";
echo "<pre>";
print_r(fetch_all_wall_posts($memberID, $db));
echo "</pre>";

*/

//Close the connection
$db->close();
?> 

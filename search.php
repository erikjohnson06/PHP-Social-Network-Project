<?php
/*search.php -- interface for searching for other members of */

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

/*This function will check inputs against a regular expression representing the proper input format.*/
function valid_input($myinput, $good_input) {
   if (preg_match("/$good_input/i", $myinput, $regs)) {
      return true;
   }
   else {
      return false;
   }
}

function fetch_suggested_friends($memberID, $db) {
    //friends array is a two dimensional array
    //it holds arrays of friend's information
    $suggested_friends_array = array();
    
    if ($memberID) {
      $command = "SELECT DISTINCT mi.memberID, mi.first_name, mi.last_name FROM member_info mi, member_friends mf " . 
                 "WHERE mi.memberID NOT IN (select req_member FROM member_friends WHERE req_member = '". $db->real_escape_string($memberID)."' " .
                 "OR app_member = '". $db->real_escape_string($memberID)."' AND date_deactivated <= 0) " .
                 "AND mi.memberID NOT IN (select app_member FROM member_friends WHERE req_member = '". $db->real_escape_string($memberID)."' " .
                 "OR app_member = '". $db->real_escape_string($memberID)."' AND date_deactivated <= 0) " .
                 "AND mi.memberID != '". $db->real_escape_string($memberID)."';";
                 
      $result = $db->query($command);
      
      if ($result->num_rows > 0) {
                 
         while ($data_array = $result->fetch_assoc()) {
            array_push($suggested_friends_array, $data_array);
         }
      }
     }
    return $suggested_friends_array;
  }

################
#              #
#  REQUEST     #
#  HANDLING    #
#              #
################

if ($_POST) {

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
              
        //Search the member_info table for matching names
        $command = "SELECT memberID, first_name, last_name FROM member_info " . 
                   "WHERE upper(first_name) LIKE '%" . $db->real_escape_string($search) ."%' " . 
                   "OR upper(last_name) LIKE '%" . $db->real_escape_string($search) ."%';"; 
    
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


//Include header
include($_SERVER['DOCUMENT_ROOT']."/.php_files/social_header.inc");
?>

<span class="signed_in">Signed in as <? echo $_SESSION['user_email']?> </span><br>
<span class="signed_in">Not <a href="profile.php?logout=1"><? echo $_SESSION['first_name']?>? </a></span>
<br>

<h2>Search Your Personal Network</h2>
<h4>Find other members to connect with!</h4>

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
           <input type="submit" value="Search">
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
	
	for ($i = 0; $i < $search_count; $i++) {
	
	    ?>
	       <a href="profile.php?profileID=<? echo $search_array[$i]['memberID']; ?>">
	    <?
	       echo $search_array[$i]['first_name'] . " " . $search_array[$i]['last_name'] . "</a><br>";
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
	   Suggested Friends
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
	
	/*Display a list of suggested friends. Shuffle the array so that the same suggestions
	aren't always displayed in the same order.*/
	
	$suggested_friends_array = fetch_suggested_friends($memberID, $db);
        shuffle($suggested_friends_array);
	$suggested_count = count($suggested_friends_array);
	
	/*If the number of suggested friends is less than 10, then cycle through the array and 
	display all suggestions. Otherwise, limit the number of suggestions to 10. */
	if ($suggested_count < 10) {
	
	   for ($i = 0; $i < $suggested_count; $i++) {
	
	      ?>
	         <a href="profile.php?profileID=<? echo $suggested_friends_array[$i]['memberID']; ?>">
	      <?
	         echo $suggested_friends_array[$i]['first_name'] . " " . $suggested_friends_array[$i]['last_name'] . "</a><br>";
	  }
	
	}
	
	else {
	
	   for ($i = 0; $i < 10; $i++) {
	
	      ?>
	         <a href="profile.php?profileID=<? echo $suggested_friends_array[$i]['memberID']; ?>">
	      <?
	         echo $suggested_friends_array[$i]['first_name'] . " " . $suggested_friends_array[$i]['last_name'] . "</a><br>";
	  }
	}
	?>

	</div>
   </div>
  </div>		
</div>
<div class="clear_both"></div>

<br>
<h4><a href="profile.php?profileID=<?echo $_SESSION['memberID']; ?>">Return to My Profile ></a></h4>







<?

//Include footer 
include($_SERVER['DOCUMENT_ROOT']."/.php_files/social_footer.inc");

/*
echo "POST array: <br>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

echo $search_count;

echo "Search array: <br>";
echo "<pre>";
print_r($search_array);
echo "</pre>";

echo "Suggesteed Friends array: <br>";
echo "<pre>";
print_r($suggested_friends_array);
echo "</pre>";

shuffle($suggested_friends_array);
echo "Suggesteed Friends array: <br>";
echo "<pre>";
print_r($suggested_friends_array);
echo "</pre>";
*/


//Close the connection
$db->close();
?> 
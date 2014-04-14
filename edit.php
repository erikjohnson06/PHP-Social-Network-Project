<?php
/*edit.php -- interface for updating your profile information*/


//Start the session and set the member ID to the current session's memberID variable
session_start();
$memberID = $_SESSION['memberID'];

/*Ensure that the user is logged in*/
if (!$memberID) {
   
    //If the user is not logged in, redirect them to the home page:
    header("Location: http://". $_SERVER['HTTP_HOST'] ."/social/index.php");
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

//Check to make the birthdate entered is valid
function isValidDate($date) {

//Get the current year for comparison
$get_date = getdate();
$current_date_mktime = mktime($date['year']);
$current_year = date("Y", $current_date_mktime);

/*Explode the date into an array, seperated by dashes, and use the 
checkdate function to verify the date is valid. Return true or 
false.  */
list($year, $month, $day) = explode("-", $date); 
if ($year > "1900" && $year <= $current_year) { 
        return checkdate($month,$day,$year); 
} 
else {
       return false;
}        
}

/*Use this function to ensure database integrity. If a user's name and contact info is already
existing elsewhere, return a false value. Otherwise, it is safe to proceed. */
function check_member($memberID, $first_name, $last_name, $address, $city, $state, $zip, $country) {
    
   /*Connect using the username "social_member" on the database "social_network" */
   include($_SERVER['DOCUMENT_ROOT']."/social/social_member_login.php");

   $db = new mysqli($host,$user,$pw,$database,$port,$socket) 
        or die("Cannot connect to mySQL.");
      
   $command = "SELECT * from member_info where first_name = '" . $db->real_escape_string($first_name) . 
                   "' and last_name = '" . $db->real_escape_string($last_name) . 
                   "' and address = '" . $db->real_escape_string($address) . 
                   "' and city = '" . $db->real_escape_string($city) . 
                   "' and state = '" . $db->real_escape_string($state) . 
                   "' and zip = '" . $db->real_escape_string($zip) . 
                   "' and country = '" . $db->real_escape_string($country) . 
                   "' and memberID != '" . $db->real_escape_string($memberID) . "';"; 

   $result = $db->query($command); 
                        
   if ($result->num_rows > 0) {
       return false;
   }
   else {
       return true;
   }
   $db->close();
}

/*Similar to check_member, ensure that each email in the database is unique. If not, 
do not allow the user to update his/her email address to that email address */
function check_email($memberID, $email) {

   /*Connect using the username "social_login" on the database "social_network" */
   include($_SERVER['DOCUMENT_ROOT']."/social/social_login_login.php");

   $db = new mysqli($host,$user,$pw,$database,$port,$socket) 
        or die("Cannot connect to mySQL.");
      
   $command = "SELECT * from member_login where email = '" . $db->real_escape_string($email) . 
                   "' and memberID != '" . $db->real_escape_string($memberID) . "';"; 

   $result = $db->query($command ); 
                        
   if ($result->num_rows > 0) {
       return false;
   }
   else {
       return true;
   }
   $db->close();
}

//Use an array of states and provinces to loop through to create the state dropdown menu
$state_list = array(	"US" => array(
				"AL" => "Alabama", 
				"AK" => "Alaska",
				"AZ" => "Arizona",
				"AR" => "Arkansas",
				"CA" => "California",
				"CO" => "Colorado",
				"CT" => "Connecticut",
				"DE" => "Delaware",
				"DC" => "District of Columbia",
				"FL" => "Florida",
				"GA" => "Georgia",
				"HI" => "Hawaii",
				"ID" => "Idaho",
				"IL" => "Illinois",
				"IN" => "Indiana",
				"IA" => "Iowa",
				"KS" => "Kansas",
				"KY" => "Kentucky",
				"LA" => "Louisiana",
				"ME" => "Maine",
				"MD" => "Maryland",
				"MA" => "Massachusetts",
				"MI" => "Michigan",
				"MN" => "Minnesota",
				"MS" => "Mississippi",
				"MO" => "Missouri",
				"MT" => "Montana",
				"NE" => "Nebraska",
				"NV" => "Nevada",
				"NH" => "New Hampshire",
				"NJ" => "New Jersey",
				"NM" => "New Mexico",
				"NY" => "New York",
				"NC" => "North Carolina",
				"ND" => "North Dakota",
				"OH" => "Ohio",
				"OK" => "Oklahoma",
				"OR" => "Oregon",
				"PA" => "Pennsylvania",
				"RI" => "Rhode Island",
				"SC" => "South Carolina",
				"SD" => "South Dakota",
				"TN" => "Tennessee",
				"TX" => "Texas",
				"UT" => "Utah",
				"VT" => "Vermont",
				"VA" => "Virginia",
				"WA" => "Washington",
				"WV" => "West Virginia",
				"WI" => "Wisconsin",
				"WY" => "Wyoming"),
			"CA" => array(
				"AB" => "Alberta",
				"BC" => "British Columbia",
				"MB" => "Manitoba",
				"NB" => "New Brunswick",
				"NL" => "Newfoundland",
				"NT" => "Northwest Territories",
				"NS" => "Nova Scotia",
				"NU" => "Nunavut",
				"ON" => "Ontario",
				"PE" => "Prince Edward Island",
				"QC" => "Quebec",
				"SK" => "Saskatchewan",
				"YT" => "Yukon Territory")
			);
    


/*If the form has been submitted, proceed with checking the input for errors*/
if ($_POST) {

    /*Set post variables here. Use the trim() function to remove any white spaces
    on either end of the input.*/	   
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $gender = $_POST['gender'];
    $relationship = $_POST['relationship'];
    $birthdate = trim($_POST['birthdate']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $state = $_POST['state'];
    $zip = trim($_POST['zip']);
    $country = $_POST['country'];
    
    /*Regular expressions for all text input. Names must contain dashes and apostrophes, 
    but must contain at least some letters. Birthdates must use YYYY-MM-DD format. Addresses
    must use contain digits, letters, spaces, slashes, pound signs, dashes, or periods only.
    City names must contain letters and spaces only. Zip codes must follow the standard format. 
    Use the country filters depending the country select to determine if the state is valid. */
    $valid_name = "^[A-Za-z][A-Za-z-']+$";
    $letter_filter = "[a-zA-Z]";
    $valid_birthdate = "^(19|20)\d\d-(0?[1-9]|1[012])-(0?[1-9]|[12][0-9]|3[01])$";
    $valid_address = "^[0-9a-zA-Z\s\/\#\-\.]+$";
    $valid_city  = "^[A-Za-z-\s]+$";
    $valid_zip = "^\d{5}(-\d{4})?$";
    //$country_filter_US = "(AL|AK|AZ|AR|CA|CO|CT|DE|DC|FL|GA|HI|ID|IL|IN|IA|KS|KY|LA|ME|MD|MA|MI|MN|MS|MO|MT|NE|NV|NH|NJ|NM|NY|NC|ND|OH|OK|OR|PA|RI|SC|SD|TN|TX|UT|VT|VA|WA|WV|WI|WY)";
    //$country_filter_CA = "(AB|BC|MB|NB|NL|NT|NS|NU|ON|PE|QC|SK|YT)";
    
    /*Ensure that all fields are completed upon submission*/
    if (!($first_name && $last_name && $email && $gender && $relationship && $birthdate && $address && $city && $state && $zip && $country)) {
        $error_message = "Please make sure you've filled in all the form fields. <br>";
    }
    
    /*Check all input for length and validity based on the regular expressions above*/
    if (strlen($first_name) > 25 || strlen($last_name) > 25) {
        $error_message  = $error_message. "Please make sure both your first and last names are fewer than 25 characters each.  <br>";
    }
    
    if (!(valid_input($first_name, $valid_name)) || !(valid_input($last_name, $valid_name)) ||
             !(valid_input($first_name, $letter_filter)) || !(valid_input($last_name, $letter_filter))) {
        $error_message = $error_message.  "Please make sure you enter a valid name, which contains letters, hyphens or apostrophes only. <br> ";
    }
    
    if (strlen($email) > 50 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message  = $error_message. "Please make sure you enter a valid email address, which is less than 50 characters.  <br>";
    }
    
    if (strlen($birthdate) > 10 || !(valid_input($birthdate, $valid_birthdate))) {
        $error_message  = $error_message. "Please use the following format for birthdates: YYYY-MM-DD.  <br>";
    }
    
    //If the date format is valid, then make sure that the actual date itself is valid by calling on the isValidDate function
    else if (!isValidDate($birthdate)) {     
        $error_message  = $error_message. "Please enter a valid date. Year must be between 1900-2014, month between 01-12, and day between 01-31.  <br>";   
    }
    
    if (strlen($address) > 100 || !(valid_input($address, $valid_address))) {
   
      $error_message  = $error_message. "Address must be 100 characters or less, and must contain alphanumeric characters, pound signs (#), dashes (-), slashes (/), periods and spaces only. <br>"; 
    }
    else if (!valid_input($address, $letter_filter)) {
      $error_message  = $error_message. "Please enter a valid address. <br>"; 
    }


   if (strlen($city) > 25 || !(valid_input($city, $valid_city))) {
      $error_message  = $error_message. "City must be 100 characters or less, and must contain letter characters, spaces or dashes (-) only. <br>"; 
   }
   else if (!valid_input($city, $letter_filter)) {
      $error_message  = $error_message. "Please enter a valid city. <br>"; 
   }


   if (strlen($zip) > 10 || strlen($zip) < 5 || !(valid_input($zip, $valid_zip))) {
     $error_message  = $error_message. "Zip code may contain 5 to 10 characters only. <br>"; 
   }

   //If the country selected is US, then ensure that the state selected is located within the US. And, vice versa. 
/*   if ($country == 'US') {
      if (!valid_input($state, $country_filter_US)) {
          $error_message  = $error_message. "Please select a state within the US. <br>"; 
      }
   }
  
  if ($country == 'CA') {
      if (!valid_input($state, $country_filter_CA)) {
          $error_message  = $error_message. "Please select a province within Canada. <br>"; 
      }
   }
 */  
  if ($_POST['state'] && $_POST['country']) { //if both values were selected
	if(!(array_key_exists($_POST['state'],$state_list[$_POST['country']]))){ //if the state/province is in the appropriate country
		$error_message  = $error_message. "Please select a matching state/province and country. <br>";
	} 
  } 
  
  else { //one or neither were selected
	$error_message  = $error_message. "Please select a state/province and a country.<br>";
  }

    
/*If any error messages occurred as a result of not passing validation, display them as messages 
to the user and do not proceed further. If the data passed the validation, perform database constraint
checks, and then insert the data into the database. */
if (!$error_message) {
    
   /*Check the user's name and contact info to make sure it remains unique in the database. */
   if (check_member($memberID, $first_name, $last_name, $address, $city, $state, $zip, $country) != true) {
       $error_message  = $error_message. "Member already exists! <br>"; 
   } 
   
   /*Check the email address and name to make sure it doesn't already exist and remains unique in the database. */
   else if (check_email($memberID, $email) != true) {
       $error_message  = $error_message. "Email address already exists! <br>"; 
   }

   /*If the database validation has passed, begin a transaction to update the member_info and member_login tables. */
   else {
       
       
       //Use this flag to determine the success of the transaction   
       $success = true; 
       
       /*Connect using the username "social_member" on the database "social_network" */
       include($_SERVER['DOCUMENT_ROOT']."/social/social_member_login.php");

       $db = new mysqli($host,$user,$pw,$database,$port,$socket) 
             or die("Cannot connect to mySQL.");
       
       //Set autocommit to "0", and begin the transaction.
       $command = "SET AUTOCOMMIT=0";
       $result = $db->query($command);
       $command = "BEGIN";
       $result = $db->query($command);
       
       //Update the member_info table with the data entered in the form
       $command = "UPDATE member_info SET first_name = '" . $db->real_escape_string($first_name) . 
                   "', last_name = '" . $db->real_escape_string($last_name) . 
                   "', gender = '" . $db->real_escape_string($gender) . 
                   "', relationship = '" . $db->real_escape_string($relationship) . 
                   "', birthdate = '" . $db->real_escape_string($birthdate) . 
                   "', address = '" . $db->real_escape_string($address) . 
                   "', city = '" . $db->real_escape_string($city) . 
                   "', state = '" . $db->real_escape_string($state) . 
                   "', zip = '" . $db->real_escape_string($zip) . 
                   "', country = '" . $db->real_escape_string($country) . 
                   "'  where memberID = '" . $db->real_escape_string($memberID) . "';"; 
       
       $result = $db->query($command);
       
       /*Record the success/failure of the query for the transaction*/
       if ($result == false) {
           $success = false;
       }
       
       /*If successful, proceed to updating the member_login table */
       else {
       
       //Update the user's email in the member_login table
       $command = "UPDATE member_login SET email = '" . $db->real_escape_string($email) . 
                   "'  where memberID = '" . $db->real_escape_string($memberID) . "';"; 

       $result = $db->query($command);
       
           /*Record the success/failure of the query for the transaction*/
           if ($result == false) {
           $success = false;
           }
       
       }
   
       /*If the transaction was successful, save the change to the database If not, rollback the changes.*/
       if ($success) {
          $command = "COMMIT";
          $result = $db->query($command);
          $success_message = "<br>Profile was updated successfully!<br>";
          
       }
       
       else {
          $command = "ROLLBACK";
          $result = $db->query($command);
          $error_message = $error_message . "<br>Profile could not be updated.<br>";
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
<span class="signed_in">Not <a href="profile.php?logout=1"><? echo $_SESSION['first_name']?>? </a></span>
<br>


<h2>Edit Your Profile</h2>

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

<form method="POST" action="">
 <table>

  <tr>
   <td align="right">
    First Name:
   </td>
   <td align="left">
     <input type="text" size="25" maxlength="25" name="first_name" value="<? if ($_POST) {echo $_POST['first_name'];} else {echo $_SESSION['first_name'];} ?>">
   </td>
</tr>

<tr>
  <td align="right">
    Last Name:
  </td>
  <td align="left">
    <input type="text" size="25" maxlength="25" name="last_name" value="<? if ($_POST) {echo $_POST['last_name'];} else {echo $_SESSION['last_name'];}?>">
  </td>
</tr>

<tr>
  <td align="right">
  Email address:
  </td>
  <td align="left">
    <input type="text" size="25" maxlength="50" name="email" value="<? if ($_POST) {echo $_POST['email'];} else {echo $_SESSION['user_email'];} ?>">
  </td>
</tr>

<tr>
   <td align="right"> 
     Gender:</td>
   <td align="left"><input type="radio" name="gender" value="m" <? if (!$_POST['gender'] || $_SESSION['gender'] == 'm') { echo "checked";}?>>M
     <input type="radio" name="gender" value="f" <? if ($_POST['gender'] == 'f') { echo "checked";}?>>F</td>
</tr>

<tr>
  <td align="right">
  Relationship:
  </td>
  <td align="left">
   <select name="relationship">
    <option value="">--Select---
    <option value="Single" <? if ($_POST['relationship'] == 'Single') { echo "selected";}?>>Single</option>
    <option value="Married" <? if ($_POST['relationship'] == 'Married') { echo "selected";}?>>Married</option>
    <option value="In a Relationship" <? if ($_POST['relationship'] == 'In a Relationship') { echo "selected";}?>>In a Relationship</option>
    <option value="No Comment" <? if ($_POST['relationship'] == 'No Comment') { echo "selected";}?>>No Comment</option>
   </select>
  </td>
</tr>

<tr>
  <td align="right">
  Date of Birth:
  </td>
  <td align="left">
    <input type="text" size="25" maxlength="50" name="birthdate" value="<? if ($_POST) {echo $_POST['birthdate'];} else {echo $_SESSION['birthdate'];} ?>" placeholder="YYYY-MM-DD">
  </td>
</tr>

<tr>
  <td align="right">
  Address:
  </td>
  <td align="left">
    <input type="text" size="25" maxlength="100" name="address" value="<? if ($_POST) {echo $_POST['address'];} else {echo $_SESSION['address'];} ?>">
  </td>
</tr>

<tr>
  <td align="right">
  City:
  </td>
  <td align="left">
    <input type="text" size="25" maxlength="25" name="city" value="<? if ($_POST) {echo $_POST['city'];} else {echo $_SESSION['city'];} ?>">
  </td>
</tr>

<tr>
  <td align="right">
  State:
  </td>
  <td align="left">
   <select name="state">
    <option value="">--Select---</option>
    <?
     //Cycle through the state_list array and populate the user's current state/province
      foreach ($state_list as $country => $list) { //will get US and CA
         foreach($list as $abbreviation=>$state){ //will go through the states/provinces
	   echo "<option value='" . $abbreviation . "'";
		if ($_POST['state'] == $abbreviation) { echo 'selected';} 
		echo ">" . $state . "</option>";
         }
      }
    ?>
    </select>
  </td>
</tr>

<tr>
  <td align="right">
  Zip Code:
  </td>
  <td align="left">
    <input type="text" size="25" maxlength="10" name="zip" value="<? if ($_POST) {echo $_POST['zip'];} else {echo $_SESSION['zip'];} ?>">
  </td>
</tr>

<tr>
  <td align="right">
  Country:
  </td>
  <td align="left">
   <select name="country">
    <option value="">--Select---
    <option value="US" <? if ($_POST['country'] == 'US') { echo "selected";}?>>United States
    <option value="CA" <? if ($_POST['country'] == 'CA') { echo "selected";}?>>Canada
  </select>
  </td>
</tr>

<tr>
  <td colspan="2" align="center">
   &nbsp;
  </td>
</tr>

<tr>
  <td colspan="2" align="center">
   <input type="submit" value="Update Profile">
  </td>
</tr>

</table>
</form>

<br>
<h4><a href="profile.php?profileID=<?echo $_SESSION['memberID']; ?>">Return to My Profile ></a></h4>

<h4><a href="password_reset.php">Reset My Password ></a></h4>


<?php

//Include footer 
include($_SERVER['DOCUMENT_ROOT']."/.php_files/social_footer.inc");

?> 
<?php

/*First, if a member is already logged in, redirect to the profile page. */
session_start();
if ($_SESSION['memberID']) {
    /*If already logged in, redirect to the profile page*/
    header("Location: /social/profile.php");
    exit();
}

/*Now, a nice looking header*/

include($_SERVER['DOCUMENT_ROOT']."/.php_files/social_header.inc");
?>
<br>
<table width="100%" height="100%"><tr>
 <td align="center" valign="middle">
 <a href="join.php"><img src="https://students.oreillyschool.com/resource/social_join.jpg"></a>
 </td>
 <td align="center" valign="middle">
  <a href="login.php"><img src="https://students.oreillyschool.com/resource/social_login.jpg"></a>
 </td>
 </tr></table>
<br>
<?
/*Now, a nice looking footer*/

include($_SERVER['DOCUMENT_ROOT']."/.php_files/social_footer.inc");

?>
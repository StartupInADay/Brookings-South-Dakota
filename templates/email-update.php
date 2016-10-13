<?php
$url = Homestead()->getUpdateUrl();

$url = add_query_arg(array(
  'email' => $email,
  'key'   => $key
), $url);
?>

Someone has requested to update your Homestead Connect profile. If this was a mistake, just ignore this email and nothing will happen.

To update your profile, visit the following address:

<?php echo $url; ?> 

Thanks!
The Homestead Team

P.S.- Interested in building your own community? You can download the Homestead Connect Wordpress plugin here: https://wordpress.org/plugins/homestead-connect/

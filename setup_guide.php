<div class="text-block">
<?php
$langfile = ABSPATH . 'wp-content/plugins/' . Participants_Db::PLUGIN_NAME . '/languages/setup_guide-' . WPLANG . '.html';
if (file_exists($langfile)) {
  $text = file_get_contents($langfile);
  echo wpautop($text);
} else {
?>
<h1>Participants Database<br />Quick Setup Guide</h1>
<h2>Initial Setup</h2>
<p>There are several settings that must be set for many of the plugin's functions to work. On this page, I will walk you through getting the plugin set up and running on your site. The first thing you will want to do is have a general idea of how you want the plugin to work, what information you need to gather and store and how your users will see and interact with that information. </p>
<h3>Here is one example of how to set up the plugin.</h3>
<p>Let&#8217;s just say you want to have a signup form that gathers a person&#8217;s name and email address. When they fill out and submit the form, they will be taken to another page and thanked. An email will be sent to them letting them know they&#8217;ve signed up and that they can return to the site to add more information to their record. A private link for this purpose is provided in the email.</p>
<p>Meanwhile, the site admin receives an email notifying them of the signup, and providing them with a direct link to the new record.</p>
<h3>Here&#8217;s How We Set That Up</h3>
<ol>
<li>Place the &#8220;&#91;pdb_signup]&#8221; shortcode on the page where you want your signup from to appear.</li>
<li>Go to the plugin settings page and click on the &#8220;Signup Form&#8221; tab.</li>
<li>Set the &#8220;Thanks Page&#8221; setting to point to the page you want them to go to after they sign up. Place the &#8220;&#91;pdb_signup_thanks]&#8221; shortcode on that page somewhere.</li>
<li>When the person who signed up clicks on the link provided them in the email, they will go to a page where they can fill out the rest of the form with information for their record.</li>
<li>Click on the &#8220;Record Form&#8221; tab and set the &#8220;Participant Record Page&#8221; setting to point to the page where you want them to go to edit their record. Put the &#8220;&#91;pdb_record]&#8221; shortcode on that page. This shortcode won&#8217;t show anything unless it is visited with the special private link provided to the user in the receipt email.</li>
</ol>
<h2>Setting Up the List Page and Detail Page</h2>
<p>When someone visits the site, you can show them a list of the people who have signed up. Each name on the list can be clicked to take the user to a detail page showing all the public information in their record. This is how to set that up:</p>
<ol>
<li>Place the &#8220;&#91;pdb_list]&#8221; shortcode on the page where you want the list of participants to go.</li>
<li>On the &#8220;Manage Database Fields&#8221; page you can determine which fields get shown in the list and which column they will be in. This is under the &#8220;Display&#8221; column and you give each field you want to show a number which determines which column the field will appear in. Zero means it won&#8217;t show at all.</li>
<li>On the page where you want the record detail to show, place the &#8220;&#91;pdb_single]&#8221; shortcode. This page won&#8217;t show anything unless it is visited using a link with the ID of the record to show in it. For example: <pre>/participants/detail?pid=27</pre></li>
<li>On the plugin settings page, under the &#8220;List Display&#8221; tab, set the &#8220;Single Record Link Field&#8221; to the field in the list (like 'first_name') where you want the link to the detail page to go.</li>
<li>Next, set the &#8220;Single Record Page&#8221; setting to point to the page where you put the &#8220;&#91;pdb_single]&#8221; shortcode.</li>
</ol>
<p>Now, go to the &#8220;Add Participant&#8221; page in the admin and enter a test record. You can now test the plugin functions to see how it all works.</p>
<?php } ?>
</div>
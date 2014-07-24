<?php
/*
 * add / edit / delete fields and field groups and their attributes
 * 
 * ver. 1.5.5
 */
if (!current_user_can(Participants_Db::$plugin_options['plugin_admin_capability'])) exit;
new PDb_Manage_Fields();
  ?>
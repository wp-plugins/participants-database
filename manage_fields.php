<?php
/*
 * add / edit / delete fields and field groups and their attributes
 * 
 * ver. 1.6
 */
if (!Participants_Db::current_user_has_plugin_role('admin')) exit;

new PDb_Manage_Fields();
  ?>
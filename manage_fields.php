<?php
/*
 * add / edit / delete fields and field groups and their attributes
 * 
 * ver. 1.6
 */
if ( ! defined( 'ABSPATH' ) ) die;
if (!Participants_Db::current_user_has_plugin_role('admin', 'manage fields')) exit;

new PDb_Manage_Fields();
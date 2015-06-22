<?php
if ( ! defined( 'ABSPATH' ) ) die;
/*
 * wrapper for the edit_participants script so that a different access level can be used for adding and editing
 */
$participant_id = false;
include 'edit_participant.php';
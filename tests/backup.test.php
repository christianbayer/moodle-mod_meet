<?php

define('CLI_SCRIPT', 1);

require_once('../../../config.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

$coursemoduletobackup = 990778; // Set this to one existing choice cmid in your dev site
$userdoingbackup   = 55612; // Set this to the id of your admin account

$bc = new backup_controller(backup::TYPE_1ACTIVITY, $coursemoduletobackup, backup::FORMAT_MOODLE, backup::INTERACTIVE_NO, backup::MODE_GENERAL, $userdoingbackup);
$bc->execute_plan();
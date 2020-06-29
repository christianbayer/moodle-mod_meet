<?php

define('CLI_SCRIPT', 1);

require_once('../../../config.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

// Transaction.
$transaction = $DB->start_delegated_transaction();

// Create new course.
$folder             = '50ab2f025743b09ac84efd59eeb6541f'; // as found in: $CFG->dataroot . '/temp/backup/'
$categoryid         = 1; // e.g. 1 == Miscellaneous
$userdoingrestore   = 55612; // e.g. 2 == admin
$courseid           = 34458;

print_r([$courseid]);

// Restore backup into course.
$controller = new restore_controller($folder, $courseid, backup::INTERACTIVE_NO, backup::MODE_GENERAL, $userdoingrestore, backup::TARGET_EXISTING_ADDING);
$controller->execute_precheck();
$controller->execute_plan();

// Commit.
$transaction->allow_commit();
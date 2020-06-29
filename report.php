<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Meet module main user interface
 *
 * @package   mod_meet
 * @copyright 2020 onwards, Univates
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Christian Bayer  (christian.bayer@universo.univates.br)
 */

require_once('../../config.php');

// Get parameters
$id   = required_param('id', PARAM_INT);
$mode = optional_param('mode', 'overview', PARAM_TEXT);

// Get course module
$cm = get_coursemodule_from_id('meet', $id, 0, false, MUST_EXIST);

// Get course record
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

// Security check
require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/meet:viewreports', $context);

// Get config and check if the reports are enabled
$config = get_config('meet');
if(!$config->enablereports) {
    print_error('reports_disabled', 'meet');
}

// Check if report exists
if( ! in_array($mode, array('overview', 'attendance'))) {
    print_error('invalid_report', 'meet');
}

// Include the given report and display it
$file = $CFG->dirroot . '/mod/meet/report/' . $mode . '/' . $mode . '_report.php';
if(is_readable($file)) {
    include_once($file);
}

// Get report class name
$classname = 'mod_meet_' . $mode . '_report';
if( ! class_exists($classname)) {
    print_error('invalid_report', 'meet');
}

// Display the report
$report = new $classname($mode, $course, $cm);
$report->display();

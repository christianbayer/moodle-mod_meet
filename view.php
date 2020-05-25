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

require('../../config.php');
require_once($CFG->dirroot . '/mod/meet/lib.php');

// Get course module ID
$id = optional_param('id', 0, PARAM_INT);

// Get course module
$cm = get_coursemodule_from_id('meet', $id, 0, false, MUST_EXIST);

// Get course record
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

// Security check
require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/meet:view', $context);

// Get meet record
$meet = $DB->get_record('meet', array('id' => $cm->instance), '*', MUST_EXIST);

// Completion and trigger events
meet_view($meet, $course, $cm, $context);

redirect($meet->gmeeturi);
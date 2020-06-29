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
 * Show/hide meet recording
 *
 * @package   mod_meet
 * @copyright 2020 onwards, Univates
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Christian Bayer  (christian.bayer@universo.univates.br)
 */

require_once('../../config.php');
require_once('./lib.php');

// Get ids
$id = required_param('id', PARAM_INT);
$recordingid = required_param('recordingid', PARAM_INT);

// Get records
$cm = get_coursemodule_from_id('meet', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$meet = $DB->get_record('meet', array('id' => $cm->instance), '*', MUST_EXIST);
$recording = $DB->get_record('meet_recordings', array('id' => $recordingid, 'meetid' => $meet->id, 'deleted' => 0), '*', MUST_EXIST);

// Security check
require_login($course, false, $cm);
require_sesskey();
$context = context_module::instance($cm->id);
require_capability('mod/meet:managerecordings', $context);

// Set page url
$PAGE->set_url('/mod/meet/show.php', array('id' => $id, 'recordingid' => $recordingid));

// Switch hidden state
$recording->hidden = ! $recording->hidden;

// Update record
$DB->update_record('meet_recordings', $recording);

\mod_meet\event\recording_updated::create_from_recording($meet, $recording, $context)->trigger();

redirect(new moodle_url('/mod/meet/view.php', array('id' => $cm->id)));

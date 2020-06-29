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
 * Delete meet recording
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

// Get confirmation
$confirm = optional_param('confirm', 0, PARAM_BOOL);

// Get records
$cm = get_coursemodule_from_id('meet', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$meet = $DB->get_record('meet', array('id' => $cm->instance), '*', MUST_EXIST);
$recording = $DB->get_record('meet_recordings', array('id' => $recordingid, 'meetid' => $meet->id), '*', MUST_EXIST);

// Security check
require_login($course, false, $cm);
require_sesskey();
$context = context_module::instance($cm->id);
require_capability('mod/meet:managerecordings', $context);

// Set page url
$PAGE->set_url('/mod/meet/delete.php', array('id' => $id, 'recordingid' => $recordingid));

// Continue only if the action was confirmed
if($confirm) {

    // Remove file sharing
    $config = get_config('meet');
    $gclient = meet_create_google_client($config);
    $gdriveservice = meet_create_google_drive_service($gclient);
    meet_remove_google_drive_file_permissions($gdriveservice, $recording->gfileid);

    // "Delete" the recording
    $recording->deleted = 1;
    $DB->update_record('meet_recordings', $recording);

    // Trigger event
    \mod_meet\event\recording_deleted::create_from_recording($meet, $recording, $context)->trigger();

    $message = get_string('recording_deleted', 'meet', (object) array(
        'title' => format_string($recording->name),
    ));

    redirect(new moodle_url('/mod/meet/view.php', array('id' => $cm->id)), $message);
}

redirect(new moodle_url('/mod/meet/view.php', array('id' => $cm->id)));

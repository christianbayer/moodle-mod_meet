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
require_once($CFG->libdir . '/getallheaders/lib.php');
require_once($CFG->dirroot . '/mod/meet/lib.php');

define('APIS_GOOGLE', 'APIs-Google; (+https://developers.google.com/webmasters/APIs-Google.html)');

// Get request headers
$headers = getallheaders();

// Check user agent
if($headers['User-Agent'] !== APIS_GOOGLE) {
    throw new moodle_exception('invalid_access', 'meet');
}

// Get configs for meet
$config = get_config('meet');

// Check channel id
if($headers['X-Goog-Channel-ID'] === $config->channelid){
    global $DB;

    // Get the client and services
    $gclient = meet_create_google_client($config);
    $gcalendarservice = meet_create_google_calendar_service($gclient);
    $gdriveservice = meet_create_google_calendar_drive($gclient);

    // Get the events
    $events = $gcalendarservice->events->listEvents($config->calendarid, array(
        'syncToken' => $config->nextsynctoken,
    ));

    // Start transaction
    $transaction = $DB->start_delegated_transaction();

    // Run through each event
    foreach ($events->getItems() as $event) {

        // Check if this event exists in DB
        if($meet = $DB->get_record('meet', array('geventid' => $event->getId()))) {

            // Update only participants responses and recordings
            meet_watcher_update_participants_responses($meet, $event);
            meet_update_recordings($meet, $event, $gdriveservice);

        }
    }

    // Update sync token
    set_config('nextsynctoken', $events->getNextSyncToken(), 'meet');

    // Commit transaction
    $DB->commit_delegated_transaction($transaction);
}

/**
 * Updates the participants responses of a meeting
 *
 * @param $meet
 * @param $event
 */
function meet_watcher_update_participants_responses($meet, $event) {
    global $DB;

    // Get attendees from event
    $attendees = $event->getAttendees();

    // Get enrolled users
    $context = context_course::instance($meet->course);
    $enrolledusers = get_enrolled_users($context);

    // Get saved participants
    $participants = array_values($DB->get_records('meet_participants', array('meetid' => $meet->id)));

    foreach ($participants as $participant) {
        // Get the user email address
        $email = $enrolledusers[$participant->userid]->email;

        if(($attendeekey = array_search($email, array_column($attendees, 'email'))) !== false) {
            $participant->status = $attendees[$attendeekey]->getResponseStatus();
            $participant->comment = $attendees[$attendeekey]->getComment();
            $participant->timemodified = time();
            $DB->update_record('meet_participants', $participant);
        }
    }
}
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
 * Meet module core interaction API
 *
 * @package   mod_meet
 * @copyright 2020 onwards, Univates
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Christian Bayer  (christian.bayer@universo.univates.br)
 */

require_once($CFG->libdir . '/google/lib.php');
require_once($CFG->libdir . '/enrollib.php');

defined('MOODLE_INTERNAL') || die;

const MEET_EVENT_TYPE_MEETING_START = 'meeting_start';

/**
 * Supported features
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function meet_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:                  return true;
        case FEATURE_GROUPINGS:               return true;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;

        default: return null;
    }
}

/**
 * Add meet instance.
 *
 * @param stdClass $data
 * @param stdClass $mform
 * @return int new book instance id
 */
function meet_add_instance($data, $mform) {
    global $DB;

    // Prepare data
    meet_process_pre_save($data);

    // Save the meet record to get the id
    $data->id = $DB->insert_record('meet', $data);

    // Save participants, reminders and create the event in the Google Calendar
    meet_process_post_save($data);

    // Update the meet record
    $DB->update_record('meet', $data);

    return $data->id;
}

/**
 * Update meet instance.
 *
 * @param stdClass $data
 * @param stdClass $mform
 * @return bool true
 */
function meet_update_instance($data, $mform) {
    global $DB;

    // Get the current record
    $currentMeet = $mform->get_current();

    // Prepare data
    meet_process_pre_save($data, $currentMeet);

    // Save participants, reminders and update the event in the Google Calendar
    meet_process_post_save($data, $currentMeet);

    // Update the meet record
    $DB->update_record('meet', $data);

    return true;
}

/**
 * Delete meet instance by activity id
 *
 * @param int $id
 * @return bool success
 */
function meet_delete_instance($id) {
    global $DB;

    if( ! $meet = $DB->get_record('meet', array('id' => $id))) {
        return false;
    }

    // Get configs for meet
    $config = get_config('meet');

    // Get the client
    $gClient = meet_create_google_client($config);

    // Get the service
    $gCalendarService = meet_create_google_calendar_service($gClient);

    try {
        // Delete the event in the Google Calendar
        meet_delete_google_calendar_event($gCalendarService, $config->calendarid, $meet->geventid, $meet->notify);
    } catch (Google_Service_Exception $exception) {
        // Already deleted
        if( ! $exception->getCode() === 410) {
            throw $exception;
        }
    }

    // Delete records
    $DB->delete_records('meet_participants', array('meetid' => $meet->id));
    $DB->delete_records('meet_reminders', array('meetid' => $meet->id));
    $DB->delete_records('meet', array('id' => $meet->id));
    $DB->delete_records('event', array('modulename' => 'meet', 'instance' => $meet->id));

    return true;
}

/**
 * Prepare data for save
 *
 * @param      $data
 * @param null $current
 */
function meet_process_pre_save(&$data, &$current = null) {
    // When creating a new record
    if( ! $current) {
        $data->timecreated = time();
    }

    // When editing
    if($current) {
        $data->id = $current->id;
    }

    // Always
    $data->timemodified = time();
    $data->notify = isset($data->notify) && $data->notify ? 1 : 0;
}

/**
 * Save the participants and reminders and create the event in the Google Calendar
 *
 * @param      $data    FormData
 * @param null $current Current meet instance, when editing
 * @throws Google_Exception
 * @throws dml_exception
 */
function meet_process_post_save(&$data, &$current = null) {

    // Set course context
    $data->context = context_course::instance($data->course);

    // Set configs for meet
    $data->config = get_config('meet');

    // Set course users
    $data->users = get_enrolled_users($data->context);

    // Set the client
    $data->gClient = meet_create_google_client($data->config);

    // Set the service
    $data->gCalendarService = meet_create_google_calendar_service($data->gClient);

    // Set the event
    $data->gEvent = $current->geventid ? meet_get_google_calendar_event($data->gCalendarService, $data->config->calendarid, $current->geventid) : null;

    // Set completion date
    meet_update_completion_date($data);

    // Save participants
    meet_save_participants($data);

    // Save reminders
    meet_save_reminders($data);

    // Create moodle calendar
    meet_prepare_update_events($data);

    // Create or update the event
    meet_create_or_update_google_calendar_event($data);

    // Set optional parameters for the service
    $optParams = array(
        'conferenceDataVersion' => 1,
        'sendUpdates'           => $data->notify ? 'all' : 'none',
    );

    // Insert or update event into Google Calendar
    if($current->geventid) {
        $data->gEvent = $data->gCalendarService->events->update($data->config->calendarid, $data->gEvent->getId(), $data->gEvent, $optParams);
    } else {
        $data->gEvent = $data->gCalendarService->events->insert($data->config->calendarid, $data->gEvent, $optParams);
    }

    // Set calendar properties
    $data->icaluid = $data->gEvent->getICalUID();
    $data->geventid = $data->gEvent->getId();
    $data->geventuri = $data->gEvent->getHtmlLink();
    $data->grequestid = $data->gEvent->getConferenceData()->getCreateRequest()->getRequestId();

    // Set entry points
    foreach ($data->gEvent->getConferenceData()->getEntryPoints() as $entryPoint) {
        if($entryPoint->getEntryPointType() == 'video') {
            $data->gmeeturi = $entryPoint->getUri();
        }
        if($entryPoint->getEntryPointType() == 'phone') {
            $data->gmeettel = $entryPoint->getLabel();
            $data->gmeettelpin = $entryPoint->getPin();
        }
    }
}

/**
 * Update completion data
 *
 * @param $data
 */
function meet_update_completion_date($data) {
    \core_completion\api::update_completion_date_event($data->coursemodule, 'meet', $data->id, $data->completionexpected);
}

/**
 * Function called when restoring a course module of type mod_meet.
 *
 * @param $meetid
 * @throws Google_Exception
 * @throws coding_exception
 * @throws dml_exception
 */
function meet_process_post_restore($meetid) {
    global $DB;

    // Get instance
    $meet = $DB->get_record('meet', array('id' => $meetid), '*', MUST_EXIST);

    // Set data
    $data = $meet;
    $data->cm = get_coursemodule_from_instance('meet', $meetid);
    $data->coursemodule = $data->cm->id;
    $data->context = context_course::instance($meet->course);
    $data->config = get_config('meet');
    $data->users = get_enrolled_users($data->context);
    $data->reminders = array_values($DB->get_records('meet_reminders', array('meetid' => $data->id)));
    $data->gClient = meet_create_google_client($data->config);
    $data->gCalendarService = meet_create_google_calendar_service($data->gClient);

    // Save participants
    meet_save_participants($data);

    // Create moodle calendar
    meet_prepare_update_events($meet, $data->cm);

    // Create or update the event
    meet_create_or_update_google_calendar_event($data);

    // Set optional parameters for the service
    $optParams = array(
        'conferenceDataVersion' => 1,
        'sendUpdates'           => $data->notify ? 'all' : 'none',
    );

    // Create the google calendar event
    $data->gEvent = $data->gCalendarService->events->insert($data->config->calendarid, $data->gEvent, $optParams);

    // Set calendar properties
    $meet->icaluid = $data->gEvent->getICalUID();
    $meet->geventid = $data->gEvent->getId();
    $meet->geventuri = $data->gEvent->getHtmlLink();
    $meet->grequestid = $data->gEvent->getConferenceData()->getCreateRequest()->getRequestId();

    // Set entry points
    foreach ($data->gEvent->getConferenceData()->getEntryPoints() as $entryPoint) {
        if($entryPoint->getEntryPointType() == 'video') {
            $meet->gmeeturi = $entryPoint->getUri();
        }
        if($entryPoint->getEntryPointType() == 'phone') {
            $meet->gmeettel = $entryPoint->getLabel();
            $meet->gmeettelpin = $entryPoint->getPin();
        }
    }

    // Update data
    $DB->update_record('meet', $meet);
}

/**
 * Save the participants
 *
 * @param $data
 * @throws coding_exception
 * @throws dml_exception
 */
function meet_save_participants(&$data) {
    global $DB;

    // Get course users
    $users = array_column($data->users, 'id');

    // Get saved participants
    $previousparticipants = array_values($DB->get_records('meet_participants', array('meetid' => $data->id)));

    // When editing
    if(count($previousparticipants)) {

        // Make a diff between users
        $participantstoberemoved = array_diff(array_column($previousparticipants, 'userid'), $users);
        $participantstobeadded = array_diff($users, array_column($previousparticipants, 'userid'));
        $participantstobeupdated = array_diff($users, $participantstobeadded, $participantstoberemoved);

        // Remove participants
        if(count($participantstoberemoved)) {
            $DB->execute("DELETE FROM {meet_participants} WHERE userid IN (" . implode(',', $participantstoberemoved) . ")");
        }

        // Update participants
        if(count($participantstobeupdated)) {

            // Get attendeees from event
            $attendees = $data->gEvent ? $data->gEvent->getAttendees() : [];

            // Update participants that will not be removed neither added
            foreach ($participantstobeupdated as $userid) {

                // Get the user email address
                $email = $data->users[$userid]->email;

                // Get participant and attendee keys
                $participantkey = array_search($userid, array_column($previousparticipants, 'userid'));
                $attendeekey = array_search($email, array_column($attendees, 'email'));

                // Get participant and attendee info
                if($participantkey !== false && $attendeekey !== false) {
                    $participant = $previousparticipants[$participantkey];
                    $attendee = $attendees[$attendeekey];

                    // Update data
                    $participant->status = $attendee->getResponseStatus();
                    $participant->comment = $attendee->getComment();
                    $participant->timemodified = time();

                    $DB->update_record('meet_participants', $participant);
                }
            }
        }

        // Update users
        $users = $participantstobeadded;
    }

    // Always insert new participants
    $participants = [];
    foreach ($users as $userid) {
        $participant = new stdClass();
        $participant->courseid = $data->course;
        $participant->meetid = $data->id;
        $participant->userid = $userid;
        $participant->comment = null;
        $participant->status = 'needsAction';
        $participant->timecreated = time();
        $participant->timemodified = time();
        $participants[] = $participant;
    }
    $DB->insert_records('meet_participants', $participants);

    // Set participants info into data object
    $data->participants = array_values($DB->get_records('meet_participants', array('meetid' => $data->id)));
}

/**
 * Save the reminders
 *
 * @param $data
 * @throws coding_exception
 * @throws dml_exception
 */
function meet_save_reminders(&$data) {
    global $DB;

    // Get reminders from data
    $reminders = [];
    for ($i = 0; $i < count($data->reminderbefore); $i ++) {
        if(strlen($data->reminderbefore[$i]) > 0) {
            $reminder = new stdClass();
            $reminder->courseid = $data->course;
            $reminder->meetid = $data->id;
            $reminder->type = $data->remindertype[$i];
            $reminder->unit = $data->reminderunit[$i];
            $reminder->before = $data->reminderbefore[$i];
            $reminder->timecreated = time();
            $reminder->timemodified = time();
            $reminders[] = $reminder;
        }
    }

    // Get saved reminders
    $previousreminders = array_values($DB->get_records('meet_reminders', array('meetid' => $data->id)));

    // There are more saved reminders then new ones
    if(count($previousreminders) > count($reminders)) {
        $reminderstoberemoved = array_column(array_splice($previousreminders, count($reminders)), 'id');
        $DB->execute("DELETE FROM {meet_reminders} WHERE id IN (" . implode(',', $reminderstoberemoved) . ")");
    }

    // Always insert new reminders
    if(count($previousreminders) < count($reminders)) {
        $DB->insert_records('meet_reminders', array_splice($reminders, count($previousreminders)));
    }

    // Update current reminders
    for ($i = 0; $i < count($previousreminders); $i ++) {
        $reminders[$i]->id = $previousreminders[$i]->id;
        $reminders[$i]->timecreated = $previousreminders[$i]->timecreated;
        $DB->update_record('meet_reminders', $reminders[$i]);
    }

    // Set reminders info into data object
    $data->reminders = array_values($DB->get_records('meet_reminders', array('meetid' => $data->id)));
}

/**
 * Get the Google Calendar Event
 *
 * @param $data
 * @param $eventid
 * @return Google_Service_Calendar_Event
 */
function meet_get_google_calendar_event(Google_Service_Calendar $service, $calendarid, $eventid) {
    return $service->events->get($calendarid, $eventid);
}

/**
 * Get the Google Drive File
 *
 * @param $data
 * @param $eventid
 * @return Google_Service_Drive_DriveFile
 */
function meet_get_google_drive_file(Google_Service_Drive $service, $fileid) {
    $optparams = array(
        'fields' => 'id,name,videoMediaMetadata/durationMillis,thumbnailLink,createdTime,modifiedTime'
    );

    return $service->files->get($fileid, $optparams);
}

/**
 * Set the Google Drive File permission to public access. This removes any shared user other then the owner.
 * @param Google_Service_Drive $service
 * @param                      $fileid
 * @return bool
 */
function meet_set_google_drive_file_permission(Google_Service_Drive $service, $fileid) {
    // Disable notification
    $optparams = array(
        'sendNotificationEmail' => false,
    );

    // Define permission
    $permission = new Google_Service_Drive_Permission();
    $permission->setType('anyone');
    $permission->setRole('reader');

    // Save the permission
    $service->permissions->create($fileid, $permission, $optparams);

    return true;
}

/**
 * @param Google_Service_Drive $service
 * @param                      $fileid
 * @return bool
 */
function meet_remove_google_drive_file_permissions(Google_Service_Drive $service, $fileid) {
    // Get current permissions
    $permissions = $service->permissions->listPermissions($fileid);

    // Remove any sharing that is with the owner or the "anyone" type
    foreach ($permissions->getPermissions() as $permission) {
        if($permission->getRole() != 'owner') {
            $service->permissions->delete($fileid, $permission->getId());
        }
    }

    return true;
}

/**
 * Update the Google Calendar Event
 *
 * @param Google_Service_Calendar       $service
 * @param int                           $calendarid
 * @param Google_Service_Calendar_Event $event
 * @param boolean                       $notify
 */
function meet_update_google_calendar_event($service, $calendarid, $event, $notify) {
    $optParams = array(
        'sendUpdates' => $notify ? 'all' : 'none',
    );
    $service->events->update($calendarid, $event->getId(), $event, $optParams);
}

/**
 * Create GoogleCalendarEvent
 *
 * @param $data
 */

function meet_create_or_update_google_calendar_event($data) {

    // Create necessary objects
    $gDateTimeStart = meet_create_google_calendar_event_date_time($data->timestart);
    $gDateTimeEnd = meet_create_google_calendar_event_date_time($data->timeend);
    $gAttendees = meet_create_google_calendar_event_attendees($data);
    $gReminders = meet_create_google_calendar_event_reminders($data->reminders);

    if( ! isset($data->gEvent)) {
        // Create a new Google Calendar Event
        $data->gEvent = new Google_Service_Calendar_Event();

        // Set conference data
        $data->gEvent->setConferenceData(meet_create_google_calendar_event_conference_data(meet_generate_request_id()));
    }

    // Set basic
    $data->gEvent->setSummary($data->name);
    $data->gEvent->setDescription($data->intro);
    $data->gEvent->setStart($gDateTimeStart);
    $data->gEvent->setEnd($gDateTimeEnd);

    // Set guest flags
    $data->gEvent->setGuestsCanModify(false);
    $data->gEvent->setGuestsCanInviteOthers(false);
    $data->gEvent->setGuestsCanSeeOtherGuests(false);

    // Set attendees
    $data->gEvent->setAttendees($gAttendees);

    // Set reminders
    $data->gEvent->setReminders($gReminders);
}

/**
 * Create a GoogleCalendarEventDateTime object
 *
 * @param $timestamp
 * @return Google_Service_Calendar_EventDateTime
 */
function meet_create_google_calendar_event_date_time($timestamp) {
    // Get DateTime object
    $datetime = meet_get_date_time_from_timestamp($timestamp);

    // Set DateTime in RFC3339 format and timezone
    $gDatetime = new Google_Service_Calendar_EventDateTime();
    $gDatetime->setDateTime($datetime->format(DateTime::RFC3339));
    $gDatetime->setTimeZone($datetime->getTimezone()->getName());

    return $gDatetime;
}

/**
 * Create a GoogleCalendarEventAttendees object
 *
 * @param $data
 * @return array
 */
function meet_create_google_calendar_event_attendees($data) {
    $attendees = [];
    foreach ($data->participants as $participant) {
        $attendee = new Google_Service_Calendar_EventAttendee();
        $attendee->setEmail($data->users[$participant->userid]->email);
        $attendee->setResponseStatus($participant->status);
        $attendee->setComment($participant->comment);
        $attendees[] = $attendee;
    }

    return $attendees;
}

/**
 * Create GoogleCalendarEventConferenceData object
 *
 * @param $requestId
 * @return Google_Service_Calendar_ConferenceData
 */
function meet_create_google_calendar_event_conference_data($requestId) {
    $data = new Google_Service_Calendar_ConferenceData();
    $data->setCreateRequest(meet_create_calendar_event_conference_request($requestId));

    return $data;
}

/**
 * Create GoogleCalendarConferenceRequest object
 *
 * @param $requestId
 * @return Google_Service_Calendar_CreateConferenceRequest
 */
function meet_create_calendar_event_conference_request($requestId) {
    $request = new Google_Service_Calendar_CreateConferenceRequest();
    $request->setRequestId($requestId);
    $request->setConferenceSolutionKey(meet_create_calendar_event_conference_solution());

    return $request;
}

/**
 * Create GoogleCalendarConferenceSolutionKey object
 *
 * @return Google_Service_Calendar_ConferenceSolutionKey
 */
function meet_create_calendar_event_conference_solution() {
    $solution = new Google_Service_Calendar_ConferenceSolutionKey();
    $solution->setType('hangoutsMeet');

    return $solution;
}

/**
 * Create GoogleCalendarEventReminders object
 *
 * @param $savedreminders
 * @return Google_Service_Calendar_EventReminders
 */
function meet_create_google_calendar_event_reminders($savedreminders) {
    $reminders = new Google_Service_Calendar_EventReminders();
    $reminders->setUseDefault(false);
    $overrides = [];
    foreach ($savedreminders as $reminder) {
        $overrides[] = array(
            'method'  => $reminder->type,
            'minutes' => $reminder->unit == 'hours' ? ($reminder->before * 60) : ($reminder->unit == 'days' ? ($reminder->before * 24 * 60) : $reminder->before),
        );
    }
    $reminders->setOverrides($overrides);

    return $reminders;
}

/**
 * Create a GoogleClient
 *
 * @param $config
 * @return Google_Client
 * @throws Google_Exception
 * @throws dml_exception
 */
function meet_create_google_client($config) {
    $gClient = new Google_Client();
    $gClient->setAuthConfig(meet_get_google_client_credentials($config->credentials));
    $gClient->setApplicationName("Moodle");
    $gClient->setScopes([
        Google_Service_Calendar::CALENDAR,
        Google_Service_Calendar::CALENDAR_EVENTS,
        Google_Service_Drive::DRIVE,
        Google_Service_Drive::DRIVE_FILE,
        Google_Service_Drive::DRIVE_APPDATA,
        Google_Service_Drive::DRIVE_METADATA,
    ]);
    $gClient->setSubject($config->calendarowner);

    return $gClient;
}

/**
 * Create a GoogleServiceCalendar
 *
 * @param $gClient
 * @return Google_Service_Calendar
 */
function meet_create_google_calendar_service($gClient) {
    return new Google_Service_Calendar($gClient);
}

/**
 * Create a GoogleServiceDrive
 *
 * @param $gClient
 * @return Google_Service_Drive
 */
function meet_create_google_calendar_drive($gClient) {
    return new Google_Service_Drive($gClient);
}

/**
 * Get the GoogleClientServiceAccount credentials from the uploaded config file
 *
 * @param $filepath
 * @return mixed
 */
function meet_get_google_client_credentials($filepath) {
    // Get the system context
    $context = context_system::instance();

    // Get the file storage system
    $fs = get_file_storage();

    // Credentials full path
    $fullpath = "/{$context->id}/meet/credentials/0{$filepath}";

    // Get the credentials file
    $file = $fs->get_file_by_hash(sha1($fullpath));

    return json_decode($file->get_content(), true);
}

/**
 * Convert timestamp to a DateTime object
 *
 * @param $timestamp
 * @return DateTime
 */
function meet_get_date_time_from_timestamp($timestamp) {
    $dateTime = new DateTime();
    $dateTime->setTimestamp($timestamp);

    return $dateTime;
}

/**
 * Generates a random request id for the meetign
 *
 * @return string id
 */
function meet_generate_request_id() {
    return uniqid();
}

/**
 * Delete the event from the Google Calendar
 *
 * @param $service
 * @param $calendarid
 * @param $eventid
 * @param $notify
 */
function meet_delete_google_calendar_event($service, $calendarid, $eventid, $notify) {
    // Set optional parameters for the service
    $optParams = array(
        'sendUpdates' => $notify ? 'all' : 'none',
    );
    $service->events->delete($calendarid, $eventid, $optParams);
}

/**
 * Update the Google Calendar Event name
 *
 * Called when the course module name is updated in the course page
 *
 * @param $meetid
 * @throws Google_Exception
 * @throws dml_exception
 */
function meet_update_google_calendar_event_name($meetid) {
    global $DB;

    // Get record
    $meet = $DB->get_record('meet', array('id' => $meetid));

    $config = get_config('meet');
    $gclient = meet_create_google_client($config);
    $gservice = meet_create_google_calendar_service($gclient);

    // Get event
    $gevent = meet_get_google_calendar_event($gservice, $config->calendarid, $meet->geventid);

    // Set summary
    $gevent->setSummary($meet->name);

    // Update the event
    meet_update_google_calendar_event($gservice, $config->calendarid, $gevent, $meet->notify);
}

/**
 * Add a user to the event and as an attendee in Google Calendar Event
 *
 * @param object|int $meetorid
 * @param object|int $userorid
 */
function meet_add_user_to_event($meetorid, $userorid) {
    global $DB;

    if( ! is_object($meetorid)) {
        $meetorid = $DB->get_record('meet', array('id' => $meetorid));
    }

    if( ! is_object($userorid)) {
        $userorid = $DB->get_record('user', array('id' => $userorid));
    }

    $config = get_config('meet');
    $gclient = meet_create_google_client($config);
    $gservice = meet_create_google_calendar_service($gclient);

    // Get event
    $gevent = meet_get_google_calendar_event($gservice, $config->calendarid, $meetorid->geventid);

    // Insert participant
    $participant = new stdClass();
    $participant->courseid = $meetorid->course;
    $participant->meetid = $meetorid->id;
    $participant->userid = $userorid->id;
    $participant->comment = null;
    $participant->status = 'needsAction';
    $participant->timecreated = time();
    $participant->timemodified = time();
    $DB->insert_record('meet_participants', $participant);

    // Set as attendee
    $attendees = $gevent->getAttendees();
    $attendee = new Google_Service_Calendar_EventAttendee();
    $attendee->setEmail($userorid->email);
    $attendee->setResponseStatus($participant->status);
    $attendee->setComment($participant->comment);
    $attendees[] = $attendee;
    $gevent->setAttendees($attendees);

    // Update the event
    meet_update_google_calendar_event($gservice, $config->calendarid, $gevent, $meetorid->notify);
}

/**
 * Remove a user from the event and as an attendee in Google Calendar Event
 *
 * @param object|int $meetorid
 * @param object|int $userorid
 */
function meet_remove_user_from_event($meetorid, $userorid) {
    global $DB;

    if( ! is_object($meetorid)) {
        $meetorid = $DB->get_record('meet', array('id' => $meetorid));
    }

    if( ! is_object($userorid)) {
        $userorid = $DB->get_record('user', array('id' => $userorid));
    }

    $config = get_config('meet');
    $gclient = meet_create_google_client($config);
    $gservice = meet_create_google_calendar_service($gclient);

    // Get event
    $gevent = meet_get_google_calendar_event($gservice, $config->calendarid, $meetorid->geventid);

    // Remove participant
    $DB->delete_records('meet_participants', array(
        'meetid' => $meetorid->id,
        'userid' => $userorid->id,
    ));

    // Remove as attendee
    $attendees = $gevent->getAttendees();
    $attendeekey = array_search($userorid->email, array_column($attendees, 'email'));
    if($attendeekey !== false) {
        unset($attendees[$attendeekey]);
    }
    $gevent->setAttendees($attendees);

    // Update the event
    meet_update_google_calendar_event($gservice, $config->calendarid, $gevent, $meetorid->notify);
}

/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param $meet
 * @param $recording
 * @param $join
 * @param $course
 * @param $cm
 * @param $context
 */
function meet_view($meet, $recording, $join, $course, $cm, $context) {

    if($join) {
        \mod_meet\event\meeting_joined::create_from_recording($meet, $context)->trigger();

        // Completion
        $completion = new completion_info($course);
        $completion->set_module_viewed($cm);

        return;
    }

    if($recording){
        \mod_meet\event\recording_played::create_from_recording($meet, $recording, $context)->trigger();

        // Completion
        $completion = new completion_info($course);
        $completion->set_module_viewed($cm);
        return;
    }

    \mod_meet\event\course_module_viewed::create_from_meet($meet, $context)->trigger();
}

/**
 * This standard function will check all instances of this module
 * and make sure there are up-to-date events created for each of them.
 * If courseid = 0, then every meet event in the site is checked, else
 * only meet events belonging to the course specified are checked.
 * This function is used, in its new format, by restore_refresh_events()
 * This function is also used when the users edit the course module name
 * by the inplace edit, to update the calendar name.
 *
 * @global object
 * @param int $courseid
 * @param int|stdClass $instance Meet module instance or ID.
 * @param int|stdClass $cm Course module object or ID.
 * @return bool
 */
function meet_refresh_events($courseid = 0, $instance = null, $cm = null) {
    global $DB;

    // If we have instance information then we can just update the one event instead of updating all events.
    if (isset($instance)) {
        if (!is_object($instance)) {
            $instance = $DB->get_record('meet', array('id' => $instance), '*', MUST_EXIST);
        }
        if (isset($cm)) {
            if (!is_object($cm)) {
                meet_prepare_update_events($instance);
                return true;
            } else {
                meet_prepare_update_events($instance, $cm);
                return true;
            }
        }
    }

    if ($courseid) {
        if (! $meets = $DB->get_records("meet", array("course" => $courseid))) {
            return true;
        }
    } else {
        if (! $meets = $DB->get_records("meet")) {
            return true;
        }
    }
    foreach ($meets as $meet) {
        meet_prepare_update_events($meet);
    }
    return true;
}

/**
 * Updates both the normal and completion calendar events for meet
 *
 * @param  stdClass $meet The meet object (from the DB)
 * @param  stdClass $cm The course module object.
 */
function meet_prepare_update_events($meet, $cm = null) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/calendar/lib.php');

    if( ! isset($cm)) {
        $cm = get_coursemodule_from_instance('meet', $meet->id, $meet->course);
    }
    $event = new stdClass();
    $event->name         = $meet->name;
    $event->type         = CALENDAR_EVENT_TYPE_ACTION;
    $event->eventtype    = MEET_EVENT_TYPE_MEETING_START;
    $event->description  = format_module_intro('meet', $meet, $cm ? $cm->id : $meet->coursemodule);
    $event->timestart    = $meet->timestart;
    $event->timeduration = $meet->timeend - $meet->timestart;
    $event->timesort     = $meet->timeend;
    $event->courseid     = $meet->course;
    $event->groupid      = 0;
    $event->userid       = 0;
    $event->modulename   = 'meet';
    $event->instance     = $meet->id;
    $event->visible      = $cm ? $cm->visible : $meet->visible;
    if ($event->id = $DB->get_field('event', 'id', array('modulename' => 'meet', 'instance' => $meet->id))) {
        $calendarevent = calendar_event::load($event->id);
        $calendarevent->update($event, false);
    } else {
        calendar_event::create($event, false);
    }
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_meet_core_calendar_provide_event_action(calendar_event $event,
                                                     \core_calendar\action_factory $factory,
                                                     $userid = 0) {
    global $DB, $USER;

    // Ensure userid
    if( ! $userid) {
        $userid = $USER->id;
    }

    // Get mod info
    $cm = get_fast_modinfo($event->courseid, $userid)->instances['meet'][$event->instance];

    // If the module is not visible to the user for any reason
    if( ! $cm->uservisible) {
        return null;
    }

    // Check capability to join
    $context = context_module::instance($cm->id);
    if( ! has_capability('mod/meet:join', $context, $userid)) {
        return null;
    }

    // Check for course completion
    $completion = new \completion_info($cm->get_course());
    $completiondata = $completion->get_data($cm, false, $userid);
    if($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }

    // Get meet
    $meet = $DB->get_record('meet', array('id' => $event->instance), '*', MUST_EXIST);

    // Get room availability
    $roomavailable = meet_is_meeting_room_available($meet);

    // Has passed
    if($roomavailable === null) {
        return null;
    }

    return $factory->create_instance(
        get_string('join', 'meet'),
        new \moodle_url('/mod/meet/view.php', array('id' => $cm->id)),
        1,
        $roomavailable
    );
}

/**
 * Get meeting room availability
 *
 * @param $meet
 * @return bool|null
 */
function meet_is_meeting_room_available($meet) {

    $timenow = time();
    $timestart = $meet->timestart;
    $timeend = $meet->timeend;

    // Is closed
    if($timenow < $timestart){
        return false;
    }

    // Is open
    if($timenow > $timestart && $timenow < $timeend) {
        return true;
    }

    // Has passed
    return null;
}

/**
 * Get the recordings of a meet. Get the file from Drive and change its sharing to anyone.
 *
 * @param      $meet
 * @param bool $forceupdate
 * @return array
 */
function meet_get_recordings($meet, $context, $forceupdate = false) {
    global $DB;

    $config = get_config('meet');
    $now = time();

    // Is time to fetch the recordings
    if(($now - $meet->timeend < $config->recordingsfetch && $now - $config->recordingscache > $meet->recordingslastcheck) || $forceupdate) {

        // Start transaction
        $transaction = $DB->start_delegated_transaction();

        // Get event attachments
        $gclient = meet_create_google_client($config);
        $gcalendarservice = meet_create_google_calendar_service($gclient);
        $gdriveservice = meet_create_google_calendar_drive($gclient);
        $gevent = meet_get_google_calendar_event($gcalendarservice, $config->calendarid, $meet->geventid);
        $gattachments = $gevent->getAttachments();

        if(count($gattachments)) {

            // Get current recordings
            $currentrecordings = array_values($DB->get_records('meet_recordings', array('meetid' => $meet->id)));

            // Run throug the calendar attachments
            foreach ($gattachments as $attachment) {

                // Check if is a video
                if($attachment->getMimeType() !== 'video/mp4') {
                    continue;
                }

                // Check if the record already exists
                if(($key = array_search($attachment->getFileId(), array_column($currentrecordings, 'gfileid'))) !== false) {
                    $recording = $currentrecordings[$key];

                    // The recording is "deleted", don't update it
                    if($recording->deleted) {
                        continue;
                    }

                } else {

                    // Create a new object
                    $recording = new stdClass();
                    $recording->courseid = $meet->course;
                    $recording->meetid = $meet->id;
                }

                // Get the file
                $file = meet_get_google_drive_file($gdriveservice, $attachment->getFileId());

                // Get thubm as base64
                $thumb = base64_encode(file_get_contents($file->getThumbnailLink()));

                // Update obj
                $recording->gfileid = $file->getId();
                $recording->gfilename = $file->getName();
                $recording->gfileduration = $file->getVideoMediaMetadata()->getDurationMillis();
                $recording->gfiletimecreated = (new DateTime($file->getCreatedTime()))->getTimestamp();
                $recording->gfiletimemodified = (new DateTime($file->getModifiedTime()))->getTimestamp();
                $recording->gfilethumbnail = $thumb ? 'data:image/jpeg;base64,' . $thumb : null;
                $recording->timemodified = time();

                if($key !== false) {
                    // This recording already exists in DB, update it
                    $DB->update_record('meet_recordings', $recording);
                } else {

                    // Before inserting it to DB, need to change the Drive File permissions
                    meet_remove_google_drive_file_permissions($gdriveservice, $attachment->getFileId());
                    meet_set_google_drive_file_permission($gdriveservice, $attachment->getFileId());

                    // Save
                    $recording->name = $file->getName();
                    $recording->description = null;
                    $recording->hidden = 0;
                    $recording->timecreated = time();
                    $recording->id = $DB->insert_record('meet_recordings', $recording);

                    // Trigger event
                    if($forceupdate) {
                        \mod_meet\event\recording_fetched_manually::create_from_recording($meet, $recording, $context)->trigger();
                    } else {
                        \mod_meet\event\recording_fetched_automatically::create_from_recording($meet, $recording, $context)->trigger();
                    }
                }
            }
        }

        // Update meet
        $meet->recordingslastcheck = time();
        $meet->timemodified = time();
        $DB->update_record('meet', $meet);

        // Commit transaction
        $DB->commit_delegated_transaction($transaction);
    }

    return $DB->get_records('meet_recordings', array('meetid' => $meet->id, 'deleted' => 0), 'gfiletimecreated');
}
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
const MEET_CHAT_LOG_FILE_AREA = 'chatlog';

/**
 * Supported features
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function meet_supports($feature) {
    switch ($feature) {
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        default:
            return null;
    }
}

/**
 * Map icons for font-awesome themes.
 */
function mod_meet_get_fontawesome_icon_map() {
    return array(
        'mod_meet:link'  => 'fa-external-link',
        'mod_meet:error' => 'fa-exclamation-circle text-danger',
    );
}

/**
 * Add meet instance.
 *
 * @param stdClass $data
 * @param stdClass $mform
 * @return int new meet instance id
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
    $currentmeet = $mform->get_current();

    // Prepare data
    meet_process_pre_save($data, $currentmeet);

    // Save participants, reminders and update the event in the Google Calendar
    meet_process_post_save($data, $currentmeet);

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

    // Check channel availability
    meet_check_hooks_channel_expiration();

    // Get configs for meet
    $config = get_config('meet');

    // Get the client and service
    $gclient = meet_create_google_client($config);
    $gcalendarservice = meet_create_google_calendar_service($gclient);

    try {
        // Delete the event in the Google Calendar
        meet_delete_google_calendar_event($gcalendarservice, $config->calendarid, $meet->geventid, $meet->notify);
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
 * This function extends the settings navigation block for the site.
 *
 * @param settings_navigation $settingsnav The settings navigation object
 * @param navigation_node     $meetnode    The node to add module settings to
 * @return void
 */
function meet_extend_settings_navigation($settingsnav, $meetnode) {
    global $PAGE;

    // We want to add these new nodes after the Edit settings node, and before the
    // Locally assigned roles node. Of course, both of those are controlled by capabilities.
    $keys = $meetnode->get_children_key_list();
    $beforekey = null;
    $i = array_search('modedit', $keys);
    if($i === false and array_key_exists(0, $keys)) {
        $beforekey = $keys[0];
    } else if(array_key_exists($i + 1, $keys)) {
        $beforekey = $keys[$i + 1];
    }

    // Get plugin config
    $config = get_config('meet');

    if(has_capability('mod/meet:viewreports', $PAGE->cm->context) && $config->enablereports) {

        $url = new moodle_url('/mod/meet/report.php', array('id' => $PAGE->cm->id));
        $reportnode = $meetnode->add_node(navigation_node::create(get_string('reports', 'meet'), $url,
            navigation_node::TYPE_SETTING), $beforekey);

        foreach (array('overview', 'attendance') as $mode) {
            $url = new moodle_url('/mod/meet/report.php', array('id' => $PAGE->cm->id, 'mode' => $mode));
            $reportnode->add_node(navigation_node::create(get_string('report_mode_' . $mode, 'meet'), $url,
                navigation_node::TYPE_SETTING, null, 'mod_meet_report_' . $mode));
        }
    }
}

/**
 * Prepare data for save
 *
 * @param      $data
 * @param null $current
 */
function meet_process_pre_save(&$data, &$current = null) {
    // Check channel availability
    meet_check_hooks_channel_expiration();

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
 */
function meet_process_post_save(&$data, &$current = null) {
    // Set course context
    $data->context = context_course::instance($data->course);

    // Set configs for meet
    $data->config = get_config('meet');

    // Set course users
    $data->users = get_enrolled_users($data->context);

    // Set the client
    $data->gclient = meet_create_google_client($data->config);

    // Set the service
    $data->gcalendarservice = meet_create_google_calendar_service($data->gclient);

    // Set the event
    $data->gevent = isset($current->geventid) ? meet_get_google_calendar_event($data->gcalendarservice, $data->config->calendarid, $current->geventid) : null;

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
    $optparams = array(
        'conferenceDataVersion' => 1,
        'sendUpdates'           => $data->notify ? 'all' : 'none',
    );

    // Insert or update event into Google Calendar
    if(isset($current->geventid)) {
        $data->gevent = $data->gcalendarservice->events->update($data->config->calendarid, $data->gevent->getId(), $data->gevent, $optparams);
    } else {
        $data->gevent = $data->gcalendarservice->events->insert($data->config->calendarid, $data->gevent, $optparams);
    }

    // Set calendar properties
    $data->gicaluid = $data->gevent->getICalUID();
    $data->geventid = $data->gevent->getId();
    $data->geventuri = $data->gevent->getHtmlLink();
    $data->geventuri = $data->gevent->getHtmlLink();
    $data->grequestid = $data->gevent->getConferenceData()->getCreateRequest()->getRequestId();
    $data->gmeetid = $data->gevent->getConferenceData()->getConferenceId();

    // Set entry points
    foreach ($data->gevent->getConferenceData()->getEntryPoints() as $entrypoint) {
        if($entrypoint->getEntryPointType() == 'video') {
            $data->gmeeturi = $entrypoint->getUri();
        }
        if($entrypoint->getEntryPointType() == 'phone') {
            $data->gmeettel = $entrypoint->getLabel();
            $data->gmeettelpin = $entrypoint->getPin();
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
    $data->gclient = meet_create_google_client($data->config);
    $data->gcalendarservice = meet_create_google_calendar_service($data->gclient);

    // Save participants
    meet_save_participants($data);

    // Create moodle calendar
    meet_prepare_update_events($meet, $data->cm);

    // Create or update the event
    meet_create_or_update_google_calendar_event($data);

    // Set optional parameters for the service
    $optparams = array(
        'conferenceDataVersion' => 1,
        'sendUpdates'           => $data->notify ? 'all' : 'none',
    );

    // Create the google calendar event
    $data->gevent = $data->gcalendarservice->events->insert($data->config->calendarid, $data->gevent, $optparams);

    // Set calendar properties
    $meet->gicaluid = $data->gevent->getICalUID();
    $meet->geventid = $data->gevent->getId();
    $meet->geventuri = $data->gevent->getHtmlLink();
    $meet->grequestid = $data->gevent->getConferenceData()->getCreateRequest()->getRequestId();

    // Set entry points
    foreach ($data->gevent->getConferenceData()->getEntryPoints() as $entrypoint) {
        if($entrypoint->getEntryPointType() == 'video') {
            $meet->gmeeturi = $entrypoint->getUri();
        }
        if($entrypoint->getEntryPointType() == 'phone') {
            $meet->gmeettel = $entrypoint->getLabel();
            $meet->gmeettelpin = $entrypoint->getPin();
        }
    }

    // Update data
    $DB->update_record('meet', $meet);
}

/**
 * Save the participants
 *
 * @param $data
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
            $attendees = $data->gevent ? $data->gevent->getAttendees() : [];

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
        'fields' => 'id,name,videoMediaMetadata/durationMillis,thumbnailLink,createdTime,modifiedTime',
    );

    return $service->files->get($fileid, $optparams);
}

/**
 * Streams a Google Drive File
 *
 * @param $data
 * @param $eventid
 * @return Google_Service_Drive_DriveFile
 */
function meet_stream_google_drive_file(Google_Service_Drive $service, $fileid) {
    $optparams = array(
        'alt' => 'media',
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
    $optparams = array(
        'sendUpdates' => $notify ? 'all' : 'none',
    );
    $service->events->update($calendarid, $event->getId(), $event, $optparams);
}

/**
 * Create GoogleCalendarEvent
 *
 * @param $data
 */

function meet_create_or_update_google_calendar_event($data) {

    // Create necessary objects
    $gdatetimestart = meet_create_google_calendar_event_date_time($data->timestart);
    $gdatetimeend = meet_create_google_calendar_event_date_time($data->timeend);
    $gattendees = meet_create_google_calendar_event_attendees($data);
    $greminders = meet_create_google_calendar_event_reminders($data->reminders);

    if( ! isset($data->gevent)) {
        // Create a new Google Calendar Event
        $data->gevent = new Google_Service_Calendar_Event();

        // Set conference data
        $data->gevent->setConferenceData(meet_create_google_calendar_event_conference_data(meet_generate_id()));
    }

    // Set basic
    $data->gevent->setSummary($data->name);
    $data->gevent->setDescription($data->intro);
    $data->gevent->setStart($gdatetimestart);
    $data->gevent->setEnd($gdatetimeend);

    // Set guest flags
    $data->gevent->setGuestsCanModify(false);
    $data->gevent->setGuestsCanInviteOthers(false);
    $data->gevent->setGuestsCanSeeOtherGuests(false);

    // Set attendees
    $data->gevent->setAttendees($gattendees);

    // Set reminders
    $data->gevent->setReminders($greminders);
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
    $gdatetime = new Google_Service_Calendar_EventDateTime();
    $gdatetime->setDateTime($datetime->format(DateTime::RFC3339));
    $gdatetime->setTimeZone($datetime->getTimezone()->getName());

    return $gdatetime;
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
 * @param $requestid
 * @return Google_Service_Calendar_ConferenceData
 */
function meet_create_google_calendar_event_conference_data($requestid) {
    $data = new Google_Service_Calendar_ConferenceData();
    $data->setCreateRequest(meet_create_calendar_event_conference_request($requestid));

    return $data;
}

/**
 * Create GoogleCalendarConferenceRequest object
 *
 * @param $requestid
 * @return Google_Service_Calendar_CreateConferenceRequest
 */
function meet_create_calendar_event_conference_request($requestid) {
    $request = new Google_Service_Calendar_CreateConferenceRequest();
    $request->setRequestId($requestid);
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
 */
function meet_create_google_client($config = null) {
    if( ! $config) {
        $config = get_config('meet');
    }
    $gclient = new Google_Client();
    $gclient->setAuthConfig(meet_get_google_client_credentials($config->credentials));
    $gclient->setApplicationName("Moodle");
    $gclient->setScopes([
        Google_Service_Calendar::CALENDAR,
        Google_Service_Calendar::CALENDAR_EVENTS,
        Google_Service_Drive::DRIVE,
        Google_Service_Drive::DRIVE_FILE,
        Google_Service_Drive::DRIVE_APPDATA,
        Google_Service_Drive::DRIVE_METADATA,
        Google_Service_Reports::ADMIN_REPORTS_AUDIT_READONLY,
    ]);
    $gclient->setSubject($config->calendarowner);

    return $gclient;
}

/**
 * Create a GoogleServiceCalendar
 *
 * @param $gclient
 * @return Google_Service_Calendar
 */
function meet_create_google_calendar_service($gclient = null) {
    if( ! $gclient) {
        $gclient = meet_create_google_client();
    }

    return new Google_Service_Calendar($gclient);
}

/**
 * Create a GoogleServiceDrive
 *
 * @param $gclient
 * @return Google_Service_Drive
 */
function meet_create_google_drive_service($gclient = null) {
    if( ! $gclient) {
        $gclient = meet_create_google_client();
    }

    return new Google_Service_Drive($gclient);
}

/**
 * Create a GoogleServiceReports
 *
 * @param $gclient
 * @return Google_Service_Reports
 */
function meet_create_google_reports_service($gclient = null) {
    if( ! $gclient) {
        $gclient = meet_create_google_client();
    }

    return new Google_Service_Reports($gclient);
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
function meet_generate_id() {
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
    $optparams = array(
        'sendUpdates' => $notify ? 'all' : 'none',
    );
    $service->events->delete($calendarid, $eventid, $optparams);
}

/**
 * Update the Google Calendar Event name
 *
 * Called when the course module name is updated in the course page
 *
 * @param $meetid
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

    // Set participants info into data object
    $data = new stdClass();
    $data->context = context_course::instance($meetorid->course);
    $data->users = get_enrolled_users($data->context);
    $data->participants = array_values($DB->get_records('meet_participants', array('meetid' => $meetorid->id)));

    // Get and set the new participants
    $gattendees = meet_create_google_calendar_event_attendees($data);
    $gevent->setAttendees($gattendees);

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

    // Set participants info into data object
    $data = new stdClass();
    $data->context = context_course::instance($meetorid->course);
    $data->users = get_enrolled_users($data->context);
    $data->participants = array_values($DB->get_records('meet_participants', array('meetid' => $meetorid->id)));

    // Get and set the new participants
    $gattendees = meet_create_google_calendar_event_attendees($data);
    $gevent->setAttendees($gattendees);

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
        \mod_meet\event\meeting_joined::create_from_meet($meet, $context)->trigger();

        // Completion
        $completion = new completion_info($course);
        $completion->set_module_viewed($cm);

        return;
    }

    if($recording) {
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
 * @param int          $courseid
 * @param int|stdClass $instance Meet module instance or ID.
 * @param int|stdClass $cm       Course module object or ID.
 * @return bool
 * @global object
 */
function meet_refresh_events($courseid = 0, $instance = null, $cm = null) {
    global $DB;

    // If we have instance information then we can just update the one event instead of updating all events.
    if(isset($instance)) {
        if( ! is_object($instance)) {
            $instance = $DB->get_record('meet', array('id' => $instance), '*', MUST_EXIST);
        }
        if(isset($cm)) {
            if( ! is_object($cm)) {
                meet_prepare_update_events($instance);

                return true;
            } else {
                meet_prepare_update_events($instance, $cm);

                return true;
            }
        }
    }

    if($courseid) {
        if( ! $meets = $DB->get_records("meet", array("course" => $courseid))) {
            return true;
        }
    } else {
        if( ! $meets = $DB->get_records("meet")) {
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
 * @param stdClass $meet The meet object (from the DB)
 * @param stdClass $cm   The course module object.
 */
function meet_prepare_update_events($meet, $cm = null) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/calendar/lib.php');

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
    $event->timesort     = $meet->timestart;
    $event->courseid     = $meet->course;
    $event->groupid      = 0;
    $event->userid       = 0;
    $event->modulename   = 'meet';
    $event->instance     = $meet->id;
    $event->visible      = $cm ? $cm->visible : $meet->visible;
    if($event->id = $DB->get_field('event', 'id', array('modulename' => 'meet',
        'instance' => $meet->id, 'eventtype' => MEET_EVENT_TYPE_MEETING_START))) {
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
 * @param calendar_event                $event
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
    if($timenow < $timestart) {
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

    // Check channel availability
    meet_check_hooks_channel_expiration();

    // Is time to fetch the recordings
    if(($now - $meet->timeend < $config->recordingsfetch && $now - $config->recordingscache > $meet->recordingslastcheck) || $forceupdate) {

        // Start transaction
        $transaction = $DB->start_delegated_transaction();

        // Get event attachments
        $gclient = meet_create_google_client($config);
        $gcalendarservice = meet_create_google_calendar_service($gclient);
        $gdriveservice = meet_create_google_drive_service($gclient);
        $gevent = meet_get_google_calendar_event($gcalendarservice, $config->calendarid, $meet->geventid);

        // Update recordings
        meet_update_recordings($meet, $gevent, $gdriveservice, $context, true, $forceupdate);

        // Update meet
        $meet->recordingslastcheck = time();
        $meet->timemodified = time();
        $DB->update_record('meet', $meet);

        // Commit transaction
        $DB->commit_delegated_transaction($transaction);
    }

    return $DB->get_records('meet_recordings', array(
        'meetid'  => $meet->id,
        'deleted' => 0,
    ), 'gfiletimecreated');
}

function meet_get_recording_thumbnail_from_attachment($file) {
    return $file->getThumbnailLink() ? ('data:image/jpeg;base64,' . base64_encode(file_get_contents($file->getThumbnailLink()))) : null;
}

/**
 * Updates the recordings of a meeting
 *
 * @param $meet
 * @param $event
 */
function meet_update_recordings($meet, $event, $gdriveservice, $context, $triggerevent = false, $forceupdate = null) {
    global $DB;

    // The event has attachments
    if(count($event->getAttachments())) {

        // Get current saved recordings
        $currentrecordings = array_values($DB->get_records('meet_recordings', array('meetid' => $meet->id)));

        // Run throug the event attachments
        foreach ($event->getAttachments() as $attachment) {

            // Check if is a video
            if($attachment->getMimeType() === 'video/mp4') {

                // Check if the record already exists
                if(($key = array_search($attachment->getFileId(), array_column($currentrecordings, 'gfileid'))) !== false) {

                    // Get the recording
                    $recording = $currentrecordings[$key];

                    // The recording is "deleted", don't update it
                    if($recording->deleted) {
                        continue;
                    }

                } else {

                    // Create a new object
                    $recording = new stdClass();
                    $recording->courseid    = $meet->course;
                    $recording->meetid      = $meet->id;
                    $recording->description = null;
                    $recording->hidden      = 0;
                    $recording->timecreated = time();

                }

                // Need to change the Drive File permissions
                meet_remove_google_drive_file_permissions($gdriveservice, $attachment->getFileId());
                meet_set_google_drive_file_permission($gdriveservice, $attachment->getFileId());

                // Get the file
                $gfile = meet_get_google_drive_file($gdriveservice, $attachment->getFileId());

                // Update recording data
                $recording->gfileid = $gfile->getId();
                $recording->gfilename = $gfile->getName();
                $recording->gfileduration = $gfile->getVideoMediaMetadata() ?
                    $gfile->getVideoMediaMetadata()->getDurationMillis() : 0;
                $recording->gfiletimecreated = (new DateTime($gfile->getCreatedTime()))->getTimestamp();
                $recording->gfiletimemodified = (new DateTime($gfile->getModifiedTime()))->getTimestamp();
                $recording->gfilethumbnail = meet_get_recording_thumbnail_from_attachment($gfile);
                $recording->timemodified = time();

                if($key !== false) {

                    // This recording already exists in DB, update it
                    $DB->update_record('meet_recordings', $recording);

                } else {

                    // Save
                    $recording->name = $gfile->getName();
                    $recording->id = $DB->insert_record('meet_recordings', $recording);

                    // Trigger event
                    if($triggerevent) {
                        \mod_meet\event\recording_fetched::create_from_recording($meet, $recording,
                            $forceupdate, $context)->trigger();
                    }
                }
            }

            // Check if is a chat log
            if($attachment->getMimeType() === 'text/plain' && pathinfo($attachment->getTitle())['extension'] === 'sbv') {

                // Get the recording associated to this log
                $chatrecording = $DB->get_record_sql("SELECT * FROM {meet_recordings} WHERE gfilename LIKE '%" . pathinfo($attachment->getTitle())['filename'] . "%'");

                // If the log is not assigned
                if( ! $chatrecording->gchatlogid) {

                    // Before inserting it to DB, need to change the Drive File permissions
                    meet_remove_google_drive_file_permissions($gdriveservice, $attachment->getFileId());
                    meet_set_google_drive_file_permission($gdriveservice, $attachment->getFileId());

                    // Get the streamed file
                    $gfile = meet_stream_google_drive_file($gdriveservice, $attachment->getFileId());

                    // Prepare file record object
                    $fileinfo = array(
                        'contextid' => $context->id,            // Context
                        'component' => 'meet',                  // Component
                        'filearea'  => MEET_CHAT_LOG_FILE_AREA, // Area
                        'itemid'    => $chatrecording->id,      // Associated record id
                        'filepath'  => '/',                     // Path
                        'filename'  => $attachment->getTitle()  // Filename
                    );

                    // Save the file
                    $fs = get_file_storage();
                    $file = $fs->create_file_from_string($fileinfo, $gfile->getBody());

                    // Update the recording
                    $chatrecording->gchatlogid = $attachment->getFileId();
                    $chatrecording->gchatlogname = $attachment->getTitle();
                    $chatrecording->chatlogid = $file->get_id();
                    $DB->update_record('meet_recordings', $chatrecording);
                }
            }
        }
    }
}

/**
 * Check for the hooks channel expiration time. If is expired, it will be renewed.
 */
function meet_check_hooks_channel_expiration() {
    // Get configs for meet
    $config = get_config('meet');

    // Check if is expired
    if( ! isset($config->channelid) || $config->channelexpiration / 1000 <= time()) {
        global $CFG;

        // Get the client and service
        $gclient = meet_create_google_client($config);
        $gcalendarservice = meet_create_google_calendar_service($gclient);

        // Create the channel
        $gchannel = new Google_Service_Calendar_Channel();
        $gchannel->setId(meet_generate_id());
        $gchannel->setType('web_hook');
        $gchannel->setAddress($CFG->wwwroot . "/mod/meet/watcher.php");
        $gchannel = $gcalendarservice->events->watch($config->calendarid, $gchannel);

        set_config('channelid', $gchannel->getId(), 'meet');
        set_config('channelexpiration', $gchannel->getExpiration(), 'meet');
    }
}

/**
 * Get the audit records for the given meeting code.
 *
 * @param                             $meetingcode
 * @param Google_Service_Reports|null $service
 * @return Google_Service_Reports_Activities
 */
function meet_get_google_reports_meet($meetingcode, Google_Service_Reports $service = null) {
    if( ! $service) {
        $service = meet_create_google_reports_service();
    }

    // Set optional parameters for the service
    $optparams = array(
        'maxResults' => 1000,
        'eventName'  => 'call_ended',
        'filters'    => 'meeting_code==' . $meetingcode,
    );

    return $service->activities->listActivities('all', 'meet', $optparams);
}

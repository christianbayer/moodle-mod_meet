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
 * Language File for Meet
 *
 * @package   mod_meet
 * @copyright 2020 onwards, Univates
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Christian Bayer  (christian.bayer@universo.univates.br)
 */

defined('MOODLE_INTERNAL') || die();

// Basic
$string['pluginname'] = 'Meet';
$string['pluginadministration'] = 'Meet administration';
$string['modulename'] = 'Meet';
$string['modulenameplural'] = 'Meet';
$string['modulename_link'] = 'mod/meet/view';
$string['modulename_help'] = 'Google Meet makes it easy to start a secure video meeting. Join from any modern web browser or download the app, and you\'re ready to go.';

// Settings
$string['settings_heading_google_api'] = 'Google Calendar API';
$string['settings_heading_google_api_description'] = 'This section configures the Google API and Google Service Account Credentials';
$string['settings_heading_google_recordings'] = 'Recordings';
$string['settings_heading_google_recordings_description'] = 'This section configures the recordings fetch';
$string['settings_credentials'] = 'Credentials file (.json)';
$string['settings_credentials_description'] = 'The JSON file with the Google Service Account credentials data';
$string['settings_calendar_owner'] = 'Calendar Owner E-mail';
$string['settings_calendar_owner_description'] = 'The Google Calendar owner e-mail that will be used to create the events';
$string['settings_calendar_id'] = 'Calendar ID';
$string['settings_calendar_id_description'] = 'The Google Calendar ID that will be used to create the events. Needs to be created with the owner e-mail';
$string['settings_recordings_fetch'] = 'Recordings fetch time';
$string['settings_recordings_fetch_description'] = 'Recordings are fetched each time that an instance is viewed. This setting defines how long after the meeting end they will still be fetched. Default is 7 days';
$string['settings_recordings_cache'] = 'Recordings cache time';
$string['settings_recordings_cache_description'] = 'Recordings are fetched each time that an instance is viewed. This setting defines the cache time for this fetch. Default is 2 hours';

// Form general
$string['form_block_general'] = 'General';
$string['form_field_name'] = 'Name';
$string['form_field_intro'] = 'Description';
$string['form_field_timestart'] = 'Starts at';
$string['form_field_timestart_help'] = 'Start date and time of the conference';
$string['form_field_timeend'] = 'Ends at';
$string['form_field_timeend_help'] = 'End date and time of the conference';
$string['form_field_notify'] = 'Notify participants';
$string['form_field_notify_help'] = 'If checked, all participants will be notified of changes to this event by email';
$string['form_field_description'] = 'Description';
$string['form_field_visible'] = 'Visible';

// Form reminders
$string['form_block_reminders'] = 'Reminders';
$string['form_label_reminder_count'] = 'Reminder {no}';
$string['form_label_reminder_count_help'] = 'Set a reminder for the participants';
$string['form_field_reminder_option_email'] = 'E-mail';
$string['form_field_reminder_option_popup'] = 'Notification';
$string['form_field_reminder_option_minutes'] = 'Minutes';
$string['form_field_reminder_option_hours'] = 'Hours';
$string['form_field_reminder_option_days'] = 'Days';
$string['form_button_add_reminder'] = 'Add reminder';

// Course module
$string['join'] = 'Join';
$string['recordings'] = 'Recordings';
$string['play'] = 'Play';
$string['name'] = 'Name';
$string['description'] = 'Description';
$string['thumbnail'] = 'Thumbnail';
$string['date'] = 'Date';
$string['duration'] = 'Duration';
$string['actions'] = 'Actions';
$string['delete_recording'] = 'Delete recording "{$a}"';
$string['edit_recording'] = 'Edit recording "{$a}"';
$string['editing_recording'] = 'Editing recording';
$string['hide_recording'] = 'Hide recording "{$a}"';
$string['show_recording'] = 'Show recording "{$a}"';
$string['recording_deleted'] = 'Recording "{$a->title}" was deleted';
$string['no_recordings'] = 'There are no recording to show.';
$string['error_recording'] = 'The recording was not found.';
$string['update_recordings'] = 'Update recordings';
$string['update_recordings_help'] = 'The recordings are automatically fetched, but if something got wrong or they are taking too long to update, you can do it manually.';
$string['meeting_room_not_available'] = 'This meeting room is not available yet.';
$string['meeting_room_available'] = 'The meeting room is ready.';
$string['meeting_room_closed'] = 'This meeting room is closed.';
$string['meeting_room_see_recordings'] = 'You can see the recordings below (not always available).';
$string['meeting_room_forbidden'] = 'You cannot join to this meeting room.';

// Capabilities
$string['meet:addinstance'] = 'Add a new Meet activity';
$string['meet:view'] = 'View a Meet activity';
$string['meet:join'] = 'Join a meeting on a Meet activity';
$string['meet:playrecordings'] = 'Play a meeting record on a Meet activity';
$string['meet:managerecordings'] = 'Manage Meet recordings';

// Events
$string['event_meeting_joined'] = 'Joined the meeting';
$string['event_recording_played'] = 'Recording played';
$string['event_recording_updated'] = 'Recording updated';
$string['event_recording_deleted'] = 'Recording deleted';
$string['event_recording_manually_fetched'] = 'Recording manually fetched';
$string['event_recording_automatically_fetched'] = 'Recording automatically fetched';

// Errors
$string['invalid_access'] = 'Invalid access.';

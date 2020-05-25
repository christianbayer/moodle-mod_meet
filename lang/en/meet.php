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
$string['modulename'] = 'Meet';
$string['modulenameplural'] = 'Meet';
$string['modulename_help'] = 'Google Meet makes it easy to start a secure video meeting. Join from any modern web browser or download the app, and you\'re ready to go.';

// Settings
$string['settings_credentials'] = 'Credentials file (.json)';
$string['settings_credentials_description'] = 'The JSON file with the Google Service Account credentials data';
$string['settings_calendar_owner'] = 'Calendar Owner E-mail';
$string['settings_calendar_owner_description'] = 'The Google Calendar owner e-mail that will be used to create the events.';
$string['settings_calendar_id'] = 'Calendar ID';
$string['settings_calendar_id_description'] = 'The Google Calendar ID that will be used to create the events. Needs to be created with the owner e-mail.';

// Form general
$string['form_block_general'] = 'General';
$string['form_field_name'] = 'Name';
$string['form_field_intro'] = 'Description';
$string['form_field_timestart'] = 'Starts at';
$string['form_field_timestart_help'] = 'Start date and time of the conference';
$string['form_field_timeend'] = 'Ends at';
$string['form_field_timeend_help'] = 'End date and time of the conference';
$string['form_field_notify'] = 'Notify participants';
$string['form_field_notify_help'] = 'If checked, all participants will be notified of this event by e-mail';

// Form reminders
$string['form_block_reminders'] = 'Reminders';
$string['form_label_reminder_count'] = 'Reminder {no}';
$string['form_label_reminder_help'] = 'Set a reminder for the participants';
$string['form_field_reminder_option_email'] = 'E-mail';
$string['form_field_reminder_option_popup'] = 'Notification';
$string['form_field_reminder_option_minutes'] = 'Minutes';
$string['form_field_reminder_option_hours'] = 'Hours';
$string['form_field_reminder_option_days'] = 'Days';
$string['form_button_add_reminder'] = 'Add reminder';

// Dashboard
$string['join'] = 'Join';

















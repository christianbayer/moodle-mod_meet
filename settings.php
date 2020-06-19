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
 * Meet plugin settings
 *
 * @package   mod_meet
 * @copyright 2020 onwards, Univates
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Christian Bayer  (christian.bayer@universo.univates.br)
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    // Google Calendar API heading
    $settings->add(new admin_setting_heading('settings_heading_google_api', get_string('settings_heading_google_api', 'meet'), get_string('settings_heading_google_api_description', 'meet')));

    // Credentials file
    $settings->add(new admin_setting_configstoredfile(
        'meet/credentials',
        get_string('settings_credentials', 'mod_meet'),
        get_string('settings_credentials_description', 'mod_meet'),
        'credentials'
    ));

    // Calendar owner
    $settings->add(new admin_setting_configtext(
        'meet/calendarowner',
        get_string('settings_calendar_owner', 'mod_meet'),
        get_string('settings_calendar_owner_description', 'mod_meet'),
        ''
    ));

    // Calendar id
    $settings->add(new admin_setting_configtext(
        'meet/calendarid',
        get_string('settings_calendar_id', 'mod_meet'),
        get_string('settings_calendar_id_description', 'mod_meet'),
        ''
    ));

    // Enable reports
    $settings->add(new admin_setting_configcheckbox(
        'meet/enablereports',
        get_string('settings_enable_reports', 'mod_meet'),
        get_string('settings_enable_reports_description', 'mod_meet'),
        0
    ));

    // Recordings heading
    $settings->add(new admin_setting_heading('settings_heading_google_recordings', get_string('settings_heading_google_recordings', 'meet'), get_string('settings_heading_google_recordings_description', 'meet')));

    // Recordings fetch
    $settings->add(new admin_setting_configtext(
        'meet/recordingsfetch',
        get_string('settings_recordings_fetch', 'mod_meet'),
        get_string('settings_recordings_fetch_description', 'mod_meet'),
        '604800',
        PARAM_INT
    ));

    // Recordings cache
    $settings->add(new admin_setting_configtext(
        'meet/recordingscache',
        get_string('settings_recordings_cache', 'mod_meet'),
        get_string('settings_recordings_cache_description', 'mod_meet'),
        '7200',
        PARAM_INT
    ));

}

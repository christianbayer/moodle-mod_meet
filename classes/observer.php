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
 * Event observers for mod_meet
 *
 * @package   mod_meet
 * @copyright 2020 onwards, Univates
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Christian Bayer  (christian.bayer@universo.univates.br)
 */

defined('MOODLE_INTERNAL') || die();

class mod_meet_observer {

    /**
     * Observer for \core\event\course_module_updated event.
     *
     * @param \core\event\course_module_updated $event
     * @return void
     */
    public static function course_module_updated(\core\event\course_module_updated $event) {
        global $CFG;

        if($event->other['modulename'] === 'meet') {

            // The update is coming from the inplace editable name
            if(isset($_REQUEST['info']) && $_REQUEST['info'] === 'core_update_inplace_editable') {
                require_once($CFG->dirroot . '/mod/meet/lib.php');

                meet_update_google_calendar_event_name($event->other['instanceid']);
            }
        }
    }



    /**
     * Triggered via user_enrolment_created event.
     *
     * @param \core\event\user_enrolment_created $event
     */
    public static function user_enrolment_created(\core\event\user_enrolment_created $event) {
        global $CFG, $DB;

        // Get meet modules in the course
        $cms = get_coursemodules_in_course('meet', $event->courseid);

        if(count($cms)) {

            // Get meet records that will still occur
            $records = $DB->get_records_sql('SELECT * FROM {meet} WHERE id IN (' . implode(',', array_column($cms, 'instance')) . ') AND timeend >= (SELECT EXTRACT(EPOCH FROM NOW()))');

            if(count($records)) {
                require_once($CFG->dirroot . '/mod/meet/lib.php');

                // Invite the user to every remaining meet
                foreach ($records as $meet) {
                    meet_add_user_to_event($meet, $event->relateduserid);
                }
            }
        }
    }

    /**
     * Triggered via user_enrolment_deleted event.
     *
     * @param \core\event\user_enrolment_deleted $event
     */
    public static function user_enrolment_deleted(\core\event\user_enrolment_deleted $event) {
        global $CFG, $DB;

        // Get meet modules in the course
        $cms = get_coursemodules_in_course('meet', $event->courseid);

        if(count($cms)) {

            // Get meet records that will still occur
            $records = $DB->get_records_sql('SELECT * FROM {meet} WHERE id IN (' . implode(',', array_column($cms, 'instance')) . ') AND timeend >= (SELECT EXTRACT(EPOCH FROM NOW()))');

            if(count($records)) {
                require_once($CFG->dirroot . '/mod/meet/lib.php');

                // Remove the user from every remaining meet
                foreach ($records as $meet) {
                    meet_remove_user_from_event($meet, $event->relateduserid);
                }
            }
        }
    }

}

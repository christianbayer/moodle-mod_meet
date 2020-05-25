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
 * Define all the restore steps that will be used by the restore_meet_activity_task
 *
 * @package   mod_meet
 * @copyright 2020 onwards, Univates
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Christian Bayer  (christian.bayer@universo.univates.br)
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Structure step to restore one meet activity
 */
class restore_meet_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        // Define paths
        $paths = array();
        $paths[] = new restore_path_element('meet', '/activity/meet');
        $paths[] = new restore_path_element('meet_reminders', '/activity/meet/reminders/reminder');

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_meet($data) {
        global $DB;

        // Prepare data
        $data = (object) $data;
        $data->course = $this->get_courseid();
        $data->timestart = $this->apply_date_offset($data->timestart);
        $data->timeend = $this->apply_date_offset($data->timeend);

        // Insert the meet record
        $newitemid = $DB->insert_record('meet', $data);

        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    protected function process_meet_reminders($data) {
        global $DB;

        // Prepare data
        $data = (object) $data;
        $data->courseid = $this->get_courseid();
        $data->meetid = $this->get_new_parentid('meet');

        // Insert the meet_reminder record
        $newitemid = $DB->insert_record('meet_reminders', $data);

        // Immediately after inserting "activity associated" record, call this
        $this->set_mapping('meet_reminders', $data->id, $newitemid);
    }

    protected function after_execute() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/meet/lib.php');

        // Add meet related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_meet', 'intro', null);

        // Process post save
        meet_process_post_restore($this->get_new_parentid('meet'));
    }

}

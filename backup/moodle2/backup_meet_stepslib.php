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
 * Define all the backup steps that will be used by the backup_meet_activity_task
 *
 * @package   mod_meet
 * @copyright 2020 onwards, Univates
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Christian Bayer  (christian.bayer@universo.univates.br)
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Structure step to backup one meet activity
 */
class backup_meet_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // Define each element separated
        $meet = new backup_nested_element('meet', array('id'), array(
            'name',
            'intro',
            'introformat',
            'notify',
            'timestart',
            'timeend',
            'timecreated',
            'timemodified',
        ));
        $reminders = new backup_nested_element('reminders');
        $reminder = new backup_nested_element('reminder', array('id'), array(
            'type',
            'unit',
            'before',
            'timecreated',
            'timemodified',
        ));

        // Build the tree
        $meet->add_child($reminders);
        $reminders->add_child($reminder);

        // Define sources
        $meet->set_source_table('meet', array('id' => backup::VAR_ACTIVITYID));
        $reminder->set_source_table('meet_reminders', array('meetid' => backup::VAR_PARENTID));

        // Define file annotations
        $meet->annotate_files('mod_meet', 'intro', null);

        // Return the root element (meet), wrapped into standard activity structure
        return $this->prepare_activity_structure($meet);
    }
}

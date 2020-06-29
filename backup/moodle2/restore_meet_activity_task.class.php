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
 * Defines restore_meet_activity_task class
 *
 * @package   mod_meet
 * @copyright 2020 onwards, Univates
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Christian Bayer  (christian.bayer@universo.univates.br)
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/meet/backup/moodle2/restore_meet_stepslib.php');

/**
 * Provides the steps to perform one complete restore of the meet instance
 */
class restore_meet_activity_task extends restore_activity_task
{
    /**
     * No specific settings for this activity
     */
    protected function define_my_settings() {
    }

    /**
     * Defines a restore step to restore the instance data from the meet.xml file
     */
    protected function define_my_steps() {
        $this->add_step(new restore_meet_activity_structure_step('meet_structure', 'meet.xml'));
    }

    /**
     * Define the contents in the activity that must be processed by the link decoder.
     *
     * @return array
     */
    public static function define_decode_contents() {
        $contents = array();
        $contents[] = new restore_decode_content('meet', array('intro'), 'meet');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging to the activity to be executed by the link decoder.
     *
     * @return array
     */
    public static function define_decode_rules() {
        $rules = array();
        $rules[] = new restore_decode_rule('MEETVIEWBYID', '/mod/meet/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('MEETINDEX', '/mod/meet/index.php?id=$1', 'course');

        return $rules;
    }

    /**
     * Define the restoring rules for logs belonging to the activity to be executed by the link decoder.
     *
     * @return array
     */
    public static function define_restore_log_rules() {
        $rules = array();
        $rules[] = new restore_log_rule('meet', 'add', 'view.php?id={course_module}', '{meet}');
        $rules[] = new restore_log_rule('meet', 'update', 'view.php?id={course_module}', '{meet}');
        $rules[] = new restore_log_rule('meet', 'view', 'view.php?id={course_module}', '{meet}');

        return $rules;
    }

    /**
     * Define the restoring rules for course associated to the activity to be executed by the link decoder.
     *
     * @return array
     */
    public static function define_restore_log_rules_for_course() {
        $rules = array();

        return $rules;
    }
}

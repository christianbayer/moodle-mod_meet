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
 * Defines backup_meet_activity_task class
 *
 * @package   mod_meet
 * @copyright 2020 onwards, Univates
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Christian Bayer  (christian.bayer@universo.univates.br)
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/mod/meet/backup/moodle2/backup_meet_stepslib.php');
require_once($CFG->dirroot.'/mod/meet/backup/moodle2/backup_meet_settingslib.php');

/**
 * Provides the steps to perform one complete backup of the meet instance
 */
class backup_meet_activity_task extends backup_activity_task
{
    /**
     * No specific settings for this activity
     */
    protected function define_my_settings() {
    }

    /**
     * Defines a backup step to store the instance data in the meet.xml file
     */
    protected function define_my_steps() {
        $this->add_step(new backup_meet_activity_structure_step('meet_structure', 'meet.xml'));
    }

    /**
     * Encodes URLs to the index.php and view.php scripts
     *
     * @param string $content some HTML text that eventually contains URLs to the activity instance scripts
     * @return string the content with the URLs encoded
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        // Link to the list of meets
        $search = "/(" . $base . "\/mod\/meet\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@MEETINDEX*$2@$', $content);

        // Link to meet view by moduleid
        $search = "/(" . $base . "\/mod\/meet\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@MEETVIEWBYID*$2@$', $content);

        return $content;
    }
}

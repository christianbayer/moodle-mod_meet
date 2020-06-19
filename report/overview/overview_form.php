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
 * Form class for Meet Overview Report.
 *
 * @package   mod_meet
 * @copyright 2020 onwards, Univates
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Christian Bayer  (christian.bayer@universo.univates.br)
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/meet/report/form.php');

/**
 * Form class for Meet Overview Report.
 *
 * @package   mod_meet
 * @copyright 2020 onwards, Univates
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Christian Bayer  (christian.bayer@universo.univates.br)
 */
class mod_meet_overview_report_form extends mod_meet_report_form {

    protected function other_report_includes_fields(MoodleQuickForm $mform) {
        $fieldsgroup = array(
            $mform->createElement('advcheckbox', 'showjoinedat', '', get_string('joined_at', 'meet')),
            $mform->createElement('advcheckbox', 'showleftat', '', get_string('left_at', 'meet')),
            $mform->createElement('advcheckbox', 'showcallduration', '', get_string('call_duration', 'meet')),
            $mform->createElement('advcheckbox', 'showvideoduration', '', get_string('video_duration', 'meet')),
        );
        $mform->addGroup($fieldsgroup, 'fieldsoptions', get_string('fields', 'meet'), array(' '), false);
    }

    protected function other_display_fields(MoodleQuickForm $mform) { }

}

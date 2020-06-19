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
 * Base form class for Meet Reports.
 *
 * @package   mod_meet
 * @copyright 2020 onwards, Univates
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Christian Bayer  (christian.bayer@universo.univates.br)
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Base form class for Meet Reports.
 *
 * @package   mod_meet
 * @copyright 2020 onwards, Univates
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Christian Bayer  (christian.bayer@universo.univates.br)
 */
abstract class mod_meet_report_form extends moodleform {

    public function __construct($action, $data)
    {
        parent::__construct($action);
        $this->set_data($data);
    }

    protected function definition() {
        $mform = $this->_form;

        // Report includes block
        $mform->addElement('header', 'reportincludes', get_string('report_block_report_includes', 'meet'));
        $this->standard_report_includes_fields($mform);
        $this->other_report_includes_fields($mform);

        // Report display block
        $mform->addElement('header', 'reportdisplay', get_string('report_block_report_display', 'meet'));
        $this->standard_display_fields($mform);
        $this->other_display_fields($mform);

        // Submit button
        $mform->addElement('submit', 'submitbutton', get_string('update_report', 'meet'));

        // Close form block
        $mform->closeHeaderBefore('submitbutton');
    }

    protected function standard_report_includes_fields(MoodleQuickForm $mform) {
        $mform->addElement('select', 'viewers', get_string('viewers', 'meet'), array(
            mod_meet_report::VIEWERS_ALL          => get_string('report_viewers_option_all', 'meet'),
            mod_meet_report::VIEWERS_ENROLLED     => get_string('report_viewers_option_enrolled', 'meet'),
            mod_meet_report::VIEWERS_NOT_ENROLLED => get_string('report_viewers_option_not_enrolled', 'meet'),
        ));
    }

    protected function standard_display_fields(MoodleQuickForm $mform) {
        $mform->addElement('text', 'pagesize', get_string('page_size', 'meet'));
        $mform->setType('pagesize', PARAM_INT);
    }

    abstract protected function other_report_includes_fields(MoodleQuickForm $mform);

    abstract protected function other_display_fields(MoodleQuickForm $mform);

}

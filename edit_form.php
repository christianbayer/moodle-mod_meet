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
 * Recording edit form
 *
 * @package   mod_meet
 * @copyright 2020 onwards, Univates
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Christian Bayer  (christian.bayer@universo.univates.br)
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');

class meet_recording_edit_form extends moodleform {

    function definition() {
        global $CFG;

        $mform = $this->_form;

        // Add the block
        $mform->addElement('header', 'general', get_string('form_block_general', 'meet'));

        // Name field
        $mform->addElement('text', 'name', get_string('form_field_name', 'meet'), array('size' => '255'));
        $mform->setType('name', PARAM_RAW);
        $mform->addRule('name', null, 'required', null, 'client');

        // Description field
        $mform->addElement('textarea', 'description', get_string('form_field_description', 'meet'));
        $mform->setType('description', PARAM_RAW);

        // Visible field
        $mform->addElement('advcheckbox', 'visible', get_string('form_field_visible', 'meet'));

        // Aditional
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // Aditional
        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);

        $this->add_action_buttons(true);

        // Set defaults
        $this->set_data($this->_customdata['recording']);
    }

}

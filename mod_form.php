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
 * Meet instance add/edit form
 *
 * @package   mod_meet
 * @copyright 2020 onwards, Univates
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Christian Bayer  (christian.bayer@universo.univates.br)
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_meet_mod_form extends moodleform_mod {

    function definition() {
        global $DB;

        $mform = $this->_form;

        // Get meet id when update
        $meetid = $this->get_instance();

        // Get saved reminders
        $reminders = [];
        if($meetid){
            $reminders = array_values($DB->get_records('meet_reminders', array('meetid' => $meetid), 'id'));
        }

        // Define form
        $this->add_block_general($mform);
        $this->add_block_reminders($mform, $reminders);
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    private function add_block_general(&$mform) {
        global $CFG;

        // Add the block
        $mform->addElement('header', 'general', get_string('form_block_general', 'meet'));

        // Name field
        $mform->addElement('text', 'name', get_string('form_field_name', 'meet'), array('size' => '64'));
        $mform->setType('name', empty($CFG->formatstringstriptags) ? PARAM_CLEANHTML : PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Intro field
        $this->standard_intro_elements(get_string('form_field_intro', 'meet'));

        // Start date field
        $mform->addElement('date_time_selector', 'timestart', get_string('form_field_timestart', 'meet'));
        $mform->addRule('timestart', null, 'required', null, 'client');
        $mform->addHelpButton('timestart', 'form_field_timestart', 'meet');

        // End date field
        $mform->addElement('date_time_selector', 'timeend', get_string('form_field_timeend', 'meet'));
        $mform->addRule('timeend', null, 'required', null, 'client');
        $mform->addHelpButton('timeend', 'form_field_timeend', 'meet');

        // Notify field
        $mform->addElement('checkbox', 'notify', get_string('form_field_notify', 'meet'));
        $mform->addHelpButton('notify', 'form_field_notify', 'meet');
        $mform->setDefault('notify', 1);

        // Add course name field
        $mform->addElement('checkbox', 'addcoursename', get_string('form_field_addcoursename', 'meet'));
        $mform->addHelpButton('addcoursename', 'form_field_addcoursename', 'meet');
        $mform->setDefault('addcoursename', 1);
    }

    private function add_block_reminders(&$mform, &$reminders) {

        // Set the block
        $mform->addElement('header', 'reminders', get_string('form_block_reminders', 'meet'));

        // Holders
        $repeated = array();
        $repeatedreminders = array();
        $repeatedoptions = array();

        // Options
        $remindertypesoptions = array(
            'email' => get_string('form_field_reminder_option_email', 'meet'),
            'popup' => get_string('form_field_reminder_option_popup', 'meet'),
        );
        $reminderunitoptions = array(
            'minutes' => get_string('form_field_reminder_option_minutes', 'meet'),
            'hours'   => get_string('form_field_reminder_option_hours', 'meet'),
            'days'    => get_string('form_field_reminder_option_days', 'meet'),
        );

        // Create the elements
        $repeatedreminders[] = $mform->createElement('select', 'remindertype', '', $remindertypesoptions);
        $repeatedreminders[] = $mform->createElement('text', 'reminderbefore', '', array('size' => 5));
        $repeatedreminders[] = $mform->createElement('select', 'reminderunit', '', $reminderunitoptions);

        // Create the group of elements
        $repeated[] = $mform->createElement('group', 'reminders', get_string('form_label_reminder_count', 'meet', '{no}'), $repeatedreminders, null, false);

        // Set type
        $repeatedoptions['reminderbefore']['type'] = PARAM_TEXT;

        // Define how many repeats
        $repeatcount = count($reminders) ?: 1;

        // Repeat elements
        $this->repeat_elements($repeated, $repeatcount, $repeatedoptions, 'noreminders', 'addreminder', 1, get_string('form_button_add_reminder', 'meet'), true);

        // Get repeated elements count
        $repeats = optional_param('noreminders', 0, PARAM_INT);
        $addfields = optional_param('addreminder', '', PARAM_TEXT);
        if(empty($addfields)) {
            $repeats = $repeatcount;
        } else {
            $repeats += 1;
        }

        // Set help button, numeric validation and edit values for each group
        for ($i = 0; $i < $repeats; $i ++) {
            $mform->addHelpButton('reminders[' . $i . ']', 'form_label_reminder_count', 'meet');
            $mform->addGroupRule('reminders[' . $i . ']', array(
                'reminderbefore[' . $i . ']' => array(array(null, 'numeric', null, 'client')),
            ));

            if(count($reminders)) {
                $mform->getElement('reminders[' . $i . ']')->setValue(array(
                    'remindertype[' . $i . ']' => $reminders[$i]->type,
                    'reminderbefore[' . $i . ']' => $reminders[$i]->before,
                    'reminderunit[' . $i . ']' => $reminders[$i]->unit,
                ));
            }
        }
    }

}

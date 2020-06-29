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
 * The mod_meet recording deleted event
 *
 * @package   mod_meet
 * @copyright 2020 onwards, Univates
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Christian Bayer  (christian.bayer@universo.univates.br)
 */

namespace mod_meet\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_meet recording deleted event
 *
 * @package   mod_meet
 * @copyright 2020 onwards, Univates
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Christian Bayer  (christian.bayer@universo.univates.br)
 */
class recording_deleted extends \core\event\base {

    public static function create_from_recording(\stdClass $meet, \stdClass $recording, \context_module $context) {
        $data = array(
            'context'  => $context,
            'objectid' => $recording->id,
        );
        $event = self::create($data);
        $event->add_record_snapshot('meet', $meet);
        $event->add_record_snapshot('meet_recordings', $recording);

        return $event;
    }

    public function get_description() {
        return "The user with id '$this->userid' deleted the recording with id '$this->objectid' for the 'meet' " .
            "activity with course module id '$this->contextinstanceid'.";
    }

    protected function get_legacy_logdata() {
        return array($this->courseid, 'meet', 'delete recording', 'view.php?id=' . $this->contextinstanceid, $this->objectid, $this->contextinstanceid);
    }

    public static function get_name() {
        return get_string('event_recording_deleted', 'meet');
    }

    public function get_url() {
        return new \moodle_url('/mod/meet/view.php', array('id' => $this->contextinstanceid));
    }

    protected function init() {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'meet_recordings';
    }

    public static function get_objectid_mapping() {
        return array('db' => 'meet_recordings', 'restore' => 'meet_recording');
    }

}

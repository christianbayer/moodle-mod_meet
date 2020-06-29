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
 * The mod_meet meeting joined event.
 *
 * @package   mod_meet
 * @copyright 2020 onwards, Univates
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Christian Bayer  (christian.bayer@universo.univates.br)
 */

namespace mod_meet\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_meet recording played event class.
 *
 * @package   mod_meet
 * @copyright 2020 onwards, Univates
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Christian Bayer  (christian.bayer@universo.univates.br)
 */
class meeting_joined extends \core\event\base {

    public static function create_from_meet(\stdClass $meet, \context_module $context) {
        $data = array(
            'context'  => $context,
            'objectid' => $meet->id,
        );
        $event = self::create($data);
        $event->add_record_snapshot('meet', $meet);

        return $event;
    }

    public function get_description() {
        return "The user with id '$this->userid' joined a meeting for the 'meet' activity with " .
            "course module id '$this->contextinstanceid'.";
    }

    protected function get_legacy_logdata() {
        return array($this->courseid, 'meet', 'join', 'view.php?id=' . $this->contextinstanceid, $this->objectid, $this->contextinstanceid);
    }

    public static function get_name() {
        return get_string('event_meeting_joined', 'meet');
    }

    public function get_url() {
        return new \moodle_url('/mod/meet/view.php', array('id' => $this->contextinstanceid));
    }

    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'meet';
    }

    public static function get_objectid_mapping() {
        return array('db' => 'meet', 'restore' => 'meet');
    }
}

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
 * Table class for the Meet Attendance report.
 *
 * @package   mod_meet
 * @copyright 2020 onwards, Univates
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Christian Bayer  (christian.bayer@universo.univates.br)
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/meet/report/table.php');

/**
 * Table class for the Meet Attendance report.
 *
 * @package   mod_meet
 * @copyright 2020 onwards, Univates
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Christian Bayer  (christian.bayer@universo.univates.br)
 */
class mod_meet_attendance_report_table extends mod_meet_report_table {

    protected function set_headers() {
        $this->headers[] = get_string('name', 'meet');
        $this->headers[] = get_string('email', 'meet');
    }

    protected function set_columns() {
        $this->columns[] = 'name';
        $this->columns[] = 'email';
    }

    protected function set_sortable() {
        $this->sortablecolumn = 'name';
        $this->sortabledirection = 'ASC';
    }

    protected function set_rows($data) {
        foreach ($data as $item) {
            // Init row
            $row = array();

            // Get the parameters
            $parameters = $item->getEvents()[0]->getParameters();

            // Set name
            $name = '';
            foreach ($parameters as $parameter) {
                if($parameter->getName() === 'display_name') {
                    $name = $parameter->getValue();
                    break;
                }
            }
            $row['name'] = $name;

            // Set e-mail
            $row['email'] = $item->getActor()->getEmail();

            // Ensure unique row values
            if( ! in_array($row, $this->rows)) {
                $this->rows[] = $row;
            }
        }
    }
}

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
 * Table class for the Meet Overview report.
 *
 * @package   mod_meet
 * @copyright 2020 onwards, Univates
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Christian Bayer  (christian.bayer@universo.univates.br)
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/meet/report/table.php');

/**
 * Table class for the Meet Overview report.
 *
 * @package   mod_meet
 * @copyright 2020 onwards, Univates
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Christian Bayer  (christian.bayer@universo.univates.br)
 */
class mod_meet_overview_report_table extends mod_meet_report_table {

    protected function set_headers() {
        $this->headers[] = get_string('name', 'meet');
        $this->headers[] = get_string('email', 'meet');
        if($this->is_downloading() || $this->options->showjoinedat) {
            $this->headers[] = get_string('joined_at', 'meet');
        }
        if($this->is_downloading() || $this->options->showleftat) {
            $this->headers[] = get_string('left_at', 'meet');
        }
        if($this->is_downloading() || $this->options->showcallduration) {
            $this->headers[] = get_string('call_duration', 'meet');
        }
        if($this->is_downloading() || $this->options->showvideoduration) {
            $this->headers[] = get_string('video_duration', 'meet');
        }
    }

    protected function set_columns() {
        $this->columns[] = 'name';
        $this->columns[] = 'email';
        if($this->is_downloading() || $this->options->showjoinedat) {
            $this->columns[] = 'joinedat';
        }
        if($this->is_downloading() || $this->options->showleftat) {
            $this->columns[] = 'leftat';
        }
        if($this->is_downloading() || $this->options->showcallduration) {
            $this->columns[] = 'callduration';
        }
        if($this->is_downloading() || $this->options->showvideoduration) {
            $this->columns[] = 'videoduration';
        }
    }

    protected function set_sortable() {
        $this->sortablecolumn = 'leftat';
        $this->sortabledirection = 'DESC';
    }

    protected function set_rows($data) {
        foreach ($data as $item) {
            // Init row
            $row = array();

            // Get the parameters
            $parameters = $item->getEvents()[0]->getParameters();

            // Init duration
            $duration = 0;

            // Get duration
            foreach ($parameters as $parameter) {
                if($parameter->getName() === 'duration_seconds') {
                    $duration = $parameter->getIntValue();
                    break;
                }
            }

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

            // Set joined at
            if($this->is_downloading() || $this->options->showjoinedat) {
                $datetimejoin = new DateTime($item->getId()->getTime());
                $datetimejoin->setTimezone(new DateTimeZone(core_date::get_user_timezone()));
                $datetimejoin = $datetimejoin->setTimestamp($datetimejoin->getTimestamp() - $duration);
                $row['joinedat'] = $datetimejoin->format('d/m/y H:i:s');
            }

            // Set left at
            if($this->is_downloading() || $this->options->showleftat) {
                $datetimeleft = new DateTime($item->getId()->getTime());
                $datetimeleft->setTimezone(new DateTimeZone(core_date::get_user_timezone()));
                $row['leftat'] = $datetimeleft->format('d/m/y H:i:s');
            }

            // Set call duration
            if($this->is_downloading() || $this->options->showcallduration) {
                $row['callduration'] = $this->format_time($duration);
            }

            // Set video duration
            if($this->is_downloading() || $this->options->showvideoduration) {
                $videosent = 0;
                foreach ($parameters as $parameter) {
                    if($parameter->getName() === 'video_send_seconds') {
                        $videosent = $parameter->getIntValue();
                        break;
                    }
                }
                $row['videoduration'] = $this->format_time($videosent);
            }

            // Set row
            $this->rows[] = $row;
        }
    }

    /**
     * Format the given seconds in time, like 00:00:00.
     *
     * @param int $seconds The seconds.
     * @return string The converted time.
     */
    private function format_time($seconds) {
        return sprintf('%02d:%02d:%02d', ($seconds / 3600), ($seconds / 60 % 60), $seconds % 60);
    }
}

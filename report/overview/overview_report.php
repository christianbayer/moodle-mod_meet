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
 * Report class for the Meet Overview report.
 *
 * @package   mod_meet
 * @copyright 2020 onwards, Univates
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Christian Bayer  (christian.bayer@universo.univates.br)
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/meet/report/report.php');
require_once($CFG->dirroot . '/mod/meet/report/overview/overview_form.php');
require_once($CFG->dirroot . '/mod/meet/report/overview/overview_options.php');
require_once($CFG->dirroot . '/mod/meet/report/overview/overview_table.php');

/**
 * Report class for the Meet Overview report.
 *
 * @package   mod_meet
 * @copyright 2020 onwards, Univates
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Christian Bayer  (christian.bayer@universo.univates.br)
 */
class mod_meet_overview_report extends mod_meet_report {

    public function display() {
        $this->options = new mod_meet_overview_report_options($this->cm->id, $this->mode);
        $this->mform = new mod_meet_overview_report_form($this->options->get_url(), $this->options->get_options());
        $this->table = new mod_meet_overview_report_table($this->options->get_url(), $this->options->get_options(), $this->audits->getItems(), $this->meet, $this->course);

        if( ! $this->table->is_downloading()) {
            $this->print_header();
            $this->mform->display();
            $this->table->display();
            $this->print_footer();
        } else {
            $this->table->display();
        }
    }

}

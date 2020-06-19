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
 * Base report class for Meet reports.
 *
 * @package   mod_meet
 * @copyright 2020 onwards, Univates
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Christian Bayer  (christian.bayer@universo.univates.br)
 */

require_once($CFG->dirroot . '/mod/meet/lib.php');

defined('MOODLE_INTERNAL') || die;

/**
 * Base report class for Meet reports.
 *
 * @package   mod_meet
 * @copyright 2020 onwards, Univates
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Christian Bayer  (christian.bayer@universo.univates.br)
 */
abstract class mod_meet_report {

    /** @var string Constant used for the options, means all viewers will be shown. */
    const VIEWERS_ALL = 'all';

    /** @var string Constant used for the options, means only enrolled users will be shown. */
    const VIEWERS_ENROLLED = 'enrolled';

    /** @var string Constant used for the options, means only not enrolled users will be shown. */
    const VIEWERS_NOT_ENROLLED = 'not_enrolled';

    /** @var string The report mode. */
    public $mode;

    /** @var string The report mode display name. */
    public $modename;

    /** @var object The meet being reported. */
    public $meet;

    /** @var object The course for the Meet being reported. */
    public $course;

    /** @var object The course module for the Meet being reported. */
    public $cm;

    /** @var Google_Service_Reports_Activities The audit activities for the meeting. */
    public $audits;

    /** @var QuickformForm The report preferences form. */
    public $mform;

    /** @var mod_meet_report_table The report table. */
    public $table;

    /** @var mod_meet_report_options The report options object. */
    public $options;

    /**
     * Constructor.
     *
     * @param string $mode Which report these options are for.
     * @param object $course The course for the Meet being reported.
     * @param object $cm The course module for the Meet being reported.
     */
    public function __construct($mode, $course, $cm) {
        global $DB;

        $this->mode    = $mode;
        $this->course  = $course;
        $this->cm      = $cm;
        $this->meet    = $DB->get_record('meet', array('id' => $cm->instance), '*', MUST_EXIST);

        \mod_meet\event\report_viewed::create_from_meet($this->meet, $this->mode, context_module::instance($cm->id))->trigger();

        $this->set_mode_name();
        $this->set_audits();
    }

    /**
     * Set the localized report name.
     */
    protected function set_mode_name() {
        $this->modename = get_string('report_mode_' . $this->mode, 'meet');
    }

    /**
     * Set audits.
     */
    protected function set_audits() {
        $this->audits = meet_get_google_reports_meet(str_replace('-', '', $this->meet->gmeetid));
    }

    /**
     * Get the base URL for the report.
     *
     * @throws moodle_exception
     */
    public function get_base_url() {
        return new moodle_url('/mod/meet/report.php', array('id' => $this->cm->id, 'mode' => $this->mode));
    }

    /**
     * Prints the header of the page.
     */
    public function print_header() {
        global $PAGE, $OUTPUT;

        $PAGE->set_pagelayout('report');
        $PAGE->set_url($this->options->get_url());
        $PAGE->set_title($this->modename);
        $PAGE->set_heading($this->course->fullname);

        echo $OUTPUT->header();
        echo $OUTPUT->heading($this->modename, 2);
    }

    /**
     * Prints the footer of the page.
     */
    public function print_footer() {
        global $OUTPUT;

        echo $OUTPUT->footer();
    }

    /**
     * Displays the report page.
     */
    public abstract function display();

}

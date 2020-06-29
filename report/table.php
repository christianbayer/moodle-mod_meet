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
 * Base table class for Meet Reports.
 *
 * @package   mod_meet
 * @copyright 2020 onwards, Univates
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Christian Bayer  (christian.bayer@universo.univates.br)
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

/**
 * Base table class for Meet Reports.
 *
 * @package   mod_meet
 * @copyright 2020 onwards, Univates
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Christian Bayer  (christian.bayer@universo.univates.br)
 */
abstract class mod_meet_report_table {

    /** @var string The table. */
    protected $table;

    /** @var array The table headers. */
    protected $headers = array();

    /** @var array The table columns. */
    protected $columns = array();

    /** @var array The table rows. */
    protected $rows = array();

    /** @var int The table total rows count */
    protected $rowscount = 0;

    /** @var string The column that sorts the results. */
    protected $sortablecolumn = '';

    /** @var string The direction that sorts the results. Can be 'ASC' or 'DESC'. */
    protected $sortabledirection = '';

    /** @var string The file name for the table download. */
    protected $filename = '';

    /** @var string The sheet title for the table download. */
    protected $sheettitle = '';

    /** @var mod_meet_report_options The report options object. */
    protected $options;

    /** @var object The meet being reported. */
    protected $meet;

    /** @var object The course for the Meet being reported. */
    protected $course;

    /** @var object The course context for the Meet being reported. */
    protected $coursecontext;

    /**
     * Constructor.
     * @param moodle_url              $url     The URL for the page with all parameters.
     * @param mod_meet_report_options $options The report options object.
     * @param array                   $data    The table raw data.
     * @param object                  $meet    The meet being reported.
     * @param object                  $course  The course for the Meet being reported.
     */
    public function __construct($url, $options, $data, $meet, $course) {

        // Set global
        $this->options = $options;
        $this->meet = $meet;
        $this->course = $course;
        $this->coursecontext = context_course::instance($course->id);

        // Create the table
        $this->table = new flexible_table(get_class($this));
        $this->table->define_baseurl($url);

        // Setup download
        $this->set_sheet_title();
        $this->set_file_name();
        $this->table->is_downloading($this->options->download, $this->filename, $this->sheettitle);

        // Set data from sub classes
        $this->set_headers();
        $this->set_columns();
        $this->set_sortable();
        $this->set_rows($data);

        // Get enrolled users and filter the viewers option
        if( ! $this->is_downloading()) {
            $this->get_enrolled_users();
        }

        // Define the table
        $this->table->define_headers($this->headers);
        $this->table->define_columns($this->columns);
        $this->table->pagesize($options->pagesize, count($this->rows));
        $this->table->sortable(true, $this->sortablecolumn,
            $this->sortabledirection == "ASC" ? SORT_ASC : SORT_DESC);

        // Setup
        $this->table->setup();

        // Sort rows
        $this->sort_rows();

        // Paginate
        if( ! $this->is_downloading()) {
            $this->slice_rows();
        }
    }

    /**
     * Get the sort column name
     *
     * @return string
     */
    private function get_sort_column() {
        return key($this->table->get_sort_columns());
    }

    /**
     * Get the sort direction.
     *
     * @return string
     */
    private  function get_sort_direction() {
        $column = $this->table->get_sort_columns();

        return array_shift($column) === SORT_ASC ? 'ASC' : 'DESC';
    }

    /**
     * Set the file name for download.
     */
    private function set_file_name() {
        $this->filename = $this->slugify($this->sheettitle);
    }

    /**
     * Set the sheet title for download
     */
    private function set_sheet_title() {
        $courseshortname = format_string($this->course->shortname, true,
            array('context' => $this->coursecontext));
        $this->sheettitle = $courseshortname . ' ' . format_string($this->meet->name, true) . ' ' .
            get_string('report_mode_' . $this->options->mode, 'meet');
    }

    /**
     * Sort the rows of the table.
     */
    private function sort_rows() {
        $sortcolumn = $this->get_sort_column();
        $sortdirection = $this->get_sort_direction();
        usort($this->rows, function ($a, $b) use ($sortcolumn, $sortdirection) {
            if($sortdirection == 'ASC') {
                return strcmp($a[$sortcolumn], $b[$sortcolumn]);
            }

            return strcmp($b[$sortcolumn], $a[$sortcolumn]);
        });
    }

    /**
     * Paginate the table.
     */
    private function slice_rows() {
        $this->rows = array_slice($this->rows,
            $this->table->currpage * $this->table->pagesize, $this->table->pagesize);
    }

    /**
     * Get the users enrolled in the given course by their e-mail address.
     * Also, filters the table data as the 'viewers' parameter is set.
     */
    private function get_enrolled_users() {
        global $DB, $OUTPUT;

        // Get all unique e-mails
        $emails = array_unique(array_column($this->rows, 'email'));

        // Get users by e-mail address
        $users = array_values($DB->get_records_sql("SELECT u.id, u.firstname, u.lastname, u.email 
                                         FROM {user} AS u 
                                   RIGHT JOIN {role_assignments} AS ra ON u.id = ra.userid 
                                        WHERE u.email IN ('" . implode("','", $emails) . "') 
                                          AND ra.contextid = " . $this->coursecontext->id . ";"));

        // Will keep the filtered table rows
        $filtered = array();

        // Run through each row
        foreach ($this->rows as $row){

            // Check if the current attendee is enrolled in this course
            $userkey = array_search($row['email'], array_column($users, 'email'));

            // The user is not enrolled and the 'viewers' option is set to show only enrolled users
            if($userkey === false && $this->options->viewers === mod_meet_report::VIEWERS_ENROLLED){
                continue;
            }

            // The user is enrolled and the 'viewers' option is set to show only not enrolled users
            if($userkey !== false && $this->options->viewers === mod_meet_report::VIEWERS_NOT_ENROLLED){
                continue;
            }

            // The user is enrolled and will be shown in the report
            if($userkey !== false) {
                $user = $users[$userkey];
                $url = new moodle_url('/user/profile.php', array('id' => $user->id));
                $row['namelink'] = html_writer::link($url, $user->firstname .' '. $user->lastname);
            }

            // Set as a filtered row
            $filtered[] = $row;
        }

        // Update rows
        $this->rows = $filtered;
    }

    /**
     * Print the table.
     */
    public function display() {

        // Begin output
        $this->table->start_output();

        // Set the table rows
        foreach ($this->rows as $row){

            // Change the name for the link to the profile
            if(isset($row['namelink'])){
                $row['name'] = $row['namelink'];
                unset($row['namelink']);
            }

            // Add the row to the table
            $this->table->add_data(array_values($row));
        }

        // End output
        $this->table->finish_output();
    }

    /**
     * Check if the table is being downloaded.
     *
     * @return bool Whether the table is being downloaded or not.
     */
    public function is_downloading() {
        return ! ! $this->table->is_downloading();
    }

    /**
     * Set the table headers.
     */
    abstract protected function set_headers();

    /**
     * Set the table columns.
     */
    abstract protected function set_columns();

    /**
     * Set the table sort column and direction.
     */
    abstract protected function set_sortable();

    /**
     * Prepare rows data.
     */
    abstract protected function set_rows($data);

    /**
     * Slugs the given string.
     *
     * @param $string The string to be slugged.
     * @return string The slugged string.
     */
    private function slugify($string) {
        $string = preg_replace('/[\t\n]/', ' ', $string);
        $string = preg_replace('/\s{2,}/', ' ', $string);
        $list = array(
            'Š' => 'S', 'š' => 's', 'Đ' => 'Dj', 'đ' => 'dj', 'Ž' => 'Z', 'ž' => 'z', 'Č' => 'C',
            'č' => 'c', 'Ć' => 'C', 'ć' => 'c', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A',
            'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E', 'Ê' => 'E',
            'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O',
            'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U',
            'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss', 'à' => 'a', 'á' => 'a',
            'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c', 'è' => 'e',
            'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'ý' => 'y', 'þ' => 'b',
            'ÿ' => 'y', 'Ŕ' => 'R', 'ŕ' => 'r', '/' => '-', ' ' => '-', '.' => '-',
        );
        $string = strtr($string, $list);
        $string = preg_replace('/-{2,}/', '-', $string);
        $string = strtolower($string);

        return $string;
    }

}

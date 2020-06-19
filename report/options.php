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
 * Base options class for Meet Reports.
 *
 * @package   mod_meet
 * @copyright 2020 onwards, Univates
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Christian Bayer  (christian.bayer@universo.univates.br)
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Base options class for Meet Reports.
 *
 * @package   mod_meet
 * @copyright 2020 onwards, Univates
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Christian Bayer  (christian.bayer@universo.univates.br)
 */
abstract class mod_meet_report_options {

    /** @var string Constant used for the default options, means that all viewers will be shown. */
    const DEFAULT_SHOW_VIEWERS = mod_meet_report::VIEWERS_ALL;

    /** @var string Constant used for the default options, means that the name column will be shown. */
    const DEFAULT_PAGE_SIZE = 30;

    /** @var string Course module id. */
    public $id;

    /** @var string Report mode. */
    public $mode;

    /** @var string Viewers option. */
    public $viewers;

    /** @var string Page size option. */
    public $pagesize;

    /** @var string Data format type: csv, xhtml, ods, etc. */
    public $download;

    /**
     * Constructor.
     *
     * @param object $cmid The course module id for the Meet being reported.
     * @param string $mode Which report these options are for.
     */
    public function __construct($cmid, $mode) {
        $this->id       = $cmid;
        $this->mode     = $mode;
        $this->viewers  = optional_param('viewers', self::DEFAULT_SHOW_VIEWERS, PARAM_TEXT);
        $this->pagesize = optional_param('pagesize', self::DEFAULT_PAGE_SIZE, PARAM_INT);
        $this->download = optional_param('download', '', PARAM_ALPHA);
    }

    /**
     * Get the URL parameters required to show the report with these options.
     *
     * @return array URL parameters array(name => value).
     */
    protected function get_url_params() {
        $options = array(
            'id'   => $this->id,
            'mode' => $this->mode,
        );

        if($this->viewers !== self::DEFAULT_SHOW_VIEWERS) {
            $options['viewers'] = $this->viewers;
        }
        if($this->pagesize !== self::DEFAULT_PAGE_SIZE) {
            $options['pagesize'] = $this->pagesize;
        }

        return $options;
    }

    /**
     * Get the URL to show the report with these options.
     *
     * @return moodle_url The URL.
     */
    public function get_url() {
        return new moodle_url('/mod/meet/report.php', $this->get_url_params());
    }

    /**
     * Get the options.
     *
     * @return object
     */
    public function get_options() {
        $options = new stdClass();
        $options->id       = $this->id;
        $options->mode     = $this->mode;
        $options->viewers  = $this->viewers;
        $options->pagesize = $this->pagesize;
        $options->download = $this->download;

        return $options;
    }

}

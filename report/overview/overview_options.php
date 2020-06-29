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
 * Options class for Meet Overview report.
 *
 * @package   mod_meet
 * @copyright 2020 onwards, Univates
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Christian Bayer  (christian.bayer@universo.univates.br)
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/meet/report/options.php');

/**
 * Options class for Meet Overview report.
 *
 * @package   mod_meet
 * @copyright 2020 onwards, Univates
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Christian Bayer  (christian.bayer@universo.univates.br)
 */
class mod_meet_overview_report_options extends mod_meet_report_options {

    /** @var string Constant used for the default options, means that the joined at column will be shown. */
    const DEFAULT_SHOW_JOINED_AT = 1;

    /** @var string Constant used for the default options, means that the left at column will be shown. */
    const DEFAULT_SHOW_LEFT_AT = 1;

    /** @var string Constant used for the default options, means that the call duration column will be shown. */
    const DEFAULT_SHOW_CALL_DURATION = 1;

    /** @var string Constant used for the default options, means that the video duration column will be shown. */
    const DEFAULT_SHOW_VIDEO_DURATION = 0;

    /** @var string Show joined at option. */
    public $showjoinedat;

    /** @var string Show left at option. */
    public $showleftat;

    /** @var string Show call duration option. */
    public $showcallduration;

    /** @var string Show video duration option. */
    public $showvideoduration;

    /**
     * Constructor.
     *
     * @param object $cmid The course module id for the Meet being reported.
     * @param string $mode Which report these options are for.
     */
    public function __construct($cmid, $mode) {
        parent::__construct($cmid, $mode);
        $this->showjoinedat      = optional_param('showjoinedat', self::DEFAULT_SHOW_JOINED_AT, PARAM_BOOL);
        $this->showleftat      = optional_param('showleftat', self::DEFAULT_SHOW_LEFT_AT, PARAM_BOOL);
        $this->showcallduration  = optional_param('showcallduration', self::DEFAULT_SHOW_CALL_DURATION, PARAM_BOOL);
        $this->showvideoduration = optional_param('showvideoduration', self::DEFAULT_SHOW_VIDEO_DURATION, PARAM_BOOL);
    }

    /**
     * Get the URL parameters required to show the report with these options.
     *
     * @return array URL parameters array(name => value).
     */
    protected function get_url_params() {
        $params = parent::get_url_params();

        if($this->showjoinedat !== self::DEFAULT_SHOW_JOINED_AT) {
            $params['showjoinedat'] = $this->showjoinedat;
        }
        if($this->showleftat !== self::DEFAULT_SHOW_LEFT_AT) {
            $params['showleftat'] = $this->showleftat;
        }
        if($this->showcallduration !== self::DEFAULT_SHOW_CALL_DURATION) {
            $params['showcallduration'] = $this->showcallduration;
        }
        if($this->showvideoduration !== self::DEFAULT_SHOW_VIDEO_DURATION) {
            $params['showvideoduration'] = $this->showvideoduration;
        }

        return $params;
    }

    /**
     * Get the options.
     *
     * @return object
     */
    public function get_options() {
        $options = parent::get_options();
        $options->showjoinedat      = $this->showjoinedat;
        $options->showleftat      = $this->showleftat;
        $options->showcallduration  = $this->showcallduration;
        $options->showvideoduration = $this->showvideoduration;

        return $options;
    }

}

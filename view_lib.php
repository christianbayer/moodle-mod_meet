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
 * Meet module view interaction API
 *
 * @package   mod_meet
 * @copyright 2020 onwards, Univates
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Christian Bayer  (christian.bayer@universo.univates.br)
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/meet/lib.php');

defined('MOODLE_INTERNAL') || die;

function meet_render_recording_view_page($course, $cm, $context, $recordingid) {
    global $DB, $USER, $PAGE, $OUTPUT;

    // Get meet record
    $meet = $DB->get_record('meet', array('id' => $cm->instance), '*', MUST_EXIST);

    // Get the recording
    $recording = $DB->get_record('meet_recordings', array('id' => $recordingid, 'meetid' => $meet->id, 'deleted' => 0));

    // Recording not found or hidden
    if (!$recording || ($recording->hidden && !meet_has_capability('managerecordings', $context))) {
        throw new moodle_exception('error_recording', 'meet');
    }

    // Trigger view event
    meet_view($meet, $recording, false, $course, $cm, $context);

    // Set page properties
    $PAGE->set_url('/mod/meet/view.php', array('id' => $cm->id, 'recordingid' => $recording->id));
    $PAGE->set_title($recording->name);
    $PAGE->set_cacheable(false);
    $PAGE->set_heading($course->fullname);
    $PAGE->navbar->add($recording->name, $PAGE->url);

    // Will keep the HTML content
    $output = '';

    // Page header
    $output .= $OUTPUT->header();

    // Box title
    $output .= $OUTPUT->heading($recording->name, 2);

    // Iframe
    $output .= '<div class="iframe-container">';
    $output .= '<iframe src="https://drive.google.com/file/d/' . $recording->gfileid . '/preview" frameborder="0" allowfullscreen></iframe>';
    $output .= '</div>';

    // Page footer
    $output .= $OUTPUT->footer();

    return $output;
}

function meet_render_view_page($course, $cm, $context, $forceupdate) {
    global $DB, $USER, $PAGE, $OUTPUT;

    // Get meet record
    $meet = $DB->get_record('meet', array('id' => $cm->instance), '*', MUST_EXIST);

    // Get the recordings
    $recordings = meet_get_recordings($meet, $context, ($forceupdate && meet_has_capability('managerecordings', $context)));

    // Trigger view event
    meet_view($meet, null, false, $course, $cm, $context);

    // Set page properties
    $PAGE->set_url('/mod/meet/view.php', array('id' => $cm->id));
    $PAGE->set_title($meet->name);
    $PAGE->set_cacheable(false);
    $PAGE->set_heading($course->fullname);

    // Will keep the HTML content
    $output = '';

    // Page header
    $output .= $OUTPUT->header();

    // Box title and description
    $output .= $OUTPUT->heading($meet->name, 2);
    $output .= file_rewrite_pluginfile_urls($meet->intro, 'pluginfile.php', $context->id, 'mod_meet', 'intro', null);

    // Availability message and button
    $output .= meet_render_spacer();
    $output .= meet_render_availability($meet, $cm, $context);
    $output .= meet_render_spacer();

    // Recordings section
    if(meet_has_capability('playrecordings', $context)){
        $output .= $OUTPUT->heading(get_string('recordings', 'meet'), 3);
        if(count($recordings)){
            $output .= meet_render_recordings_table($recordings, $cm, $context);
        } else {
            $output .= '<p>' . get_string('no_recordings', 'meet') . '</p>';
        }
    }

    if(meet_has_capability('managerecordings', $context)){
        $output .= meet_render_spacer();
        $output .= '<a href="' . (new moodle_url('/mod/meet/view.php', array('id' => $cm->id, 'forceupdate' => 1)))->out() . '" class="btn btn-primary">' . get_string('update_recordings', 'meet') . '</a>';
        $output .= meet_render_spacer();
    }

    // Page footer
    $output .= $OUTPUT->footer();

    return $output;
}

function meet_render_availability($meet, $cm, $context) {
    // Get availability of the room
    $available = meet_is_meeting_room_available($meet);

    // Will keep the HTML content
    $output = '';

    // Get url
    $url = (new moodle_url('/mod/meet/view.php', array('id' => $cm->id, 'join' => 1)))->out();

    if( ! meet_has_capability('join', $context)) {
        $output .= '<p>' . get_string('meeting_room_forbidden', 'meet') . '</p>';
    } else if($available === false) {
        $output .= '<p>' . get_string('meeting_room_not_available', 'meet') . '</p>';
        $output .= '<button type="button" class="btn btn-secondary disabled" disabled>' . get_string('join', 'meet') . '</button>';
    } else if($available === true) {
        $output .= '<p>' . get_string('meeting_room_available', 'meet') . '</p>';
        $output .= '<a href="' . $url . '" class="btn btn-primary" target="_blank">' . get_string('join', 'meet') . '</a>';
    } else {
        $output .= '<p>' . get_string('meeting_room_closed', 'meet') . ' ' . get_string('meeting_room_see_recordings', 'meet') . '</p>';
    }

    return $output;
}

function meet_render_spacer($margin = 2) {
    return "<div style='margin-top: {$margin}rem;'></div>";
}

function meet_render_recordings_table($recordings, $cm, $context) {
    global $OUTPUT, $USER;

    $link = function ($id) use ($cm) {
        return '<a href="' . (new moodle_url('/mod/meet/view.php', array('id' => $cm->id, 'recordingid' => $id)))->out() . '" class="btn btn-sm btn-default">' . get_string('play', 'meet') . '</a>';
    };

    $thumbnail = function ($id, $thumb) use ($cm) {
        $o = 'Unavailable';
        if($thumb) {
            $o = '<a href="' . (new moodle_url('/mod/meet/view.php', array('id' => $cm->id, 'recordingid' => $id)))->out() . '">';
            $o .= '<img src="' . $thumb . '"/>';
            $o .= '</a>';
        }

        return $o;
    };

    $time = function ($timestamp) {
        $dateTime = new DateTime();
        $dateTime->setTimestamp((int) $timestamp);

        return $dateTime->format('d/m/y H:i:s');
    };

    $duration = function ($millis) {
        $t = round($millis / 1000);

        return sprintf('%02d:%02d:%02d', ($t / 3600), ($t / 60 % 60), $t % 60);
    };

    $actions = function ($id, $title, $hidden) use ($OUTPUT, $USER, $context, $cm){

        // Get the formated title
        $titleunescaped = trim(format_string($title, true, array(
            'context' => $context,
            'escape'  => false,
        )));

        // Will keep the HTML
        $o = '';

        // Edit
        $o .= html_writer::link(
            new moodle_url('edit.php', array(
                'id'          => $id,
                'cmid' => $cm->id,
            )),
            $OUTPUT->pix_icon('t/edit', get_string('edit_recording', 'meet', $title)),
            array('title' => get_string('editchapter', 'meet', $titleunescaped))
        );

        // Hide/Show
        if($hidden) {
            $o .= html_writer::link(
                new moodle_url('show.php', array(
                    'id'          => $cm->id,
                    'recordingid' => $id,
                    'sesskey'     => $USER->sesskey,
                )),
                $OUTPUT->pix_icon('t/show', get_string('show_recording', 'meet', $title)),
                array('title' => get_string('show_recording', 'meet', $titleunescaped))
            );
        } else {
            $o .= html_writer::link(
                new moodle_url('show.php', array(
                    'id'          => $cm->id,
                    'recordingid' => $id,
                    'sesskey'     => $USER->sesskey,
                )),
                $OUTPUT->pix_icon('t/hide', get_string('hide_recording', 'meet', $title)),
                array('title' => get_string('hide_recording', 'meet', $titleunescaped))
            );
        }

        // Delete
        $o .= $OUTPUT->action_icon(
            new moodle_url('delete.php', [
                'id'          => $cm->id,
                'recordingid' => $id,
                'sesskey'     => sesskey(),
                'confirm'     => 1,
            ]),
            new pix_icon('t/delete', get_string('delete_recording', 'meet', $title)),
            new confirm_action(get_string('delete_recording', 'meet', $titleunescaped)),
            ['title' => get_string('delete_recording', 'meet', $titleunescaped)]
        );

        return $o;
    };

    // Create the table
    $table = new html_table();

    // Set header
    $table->head = array(
        get_string('play', 'meet'),
        get_string('name', 'meet'),
        get_string('description', 'meet'),
        get_string('thumbnail', 'meet'),
        get_string('date', 'meet'),
        get_string('duration', 'meet'),
    );
    if(meet_has_capability('managerecordings', $context)){
        $table->head[] = get_string('actions', 'meet');
    }

    // Set data
    foreach ($recordings as $recording) {
        if($recording->hidden && !meet_has_capability('managerecordings', $context)){
            continue;
        }
        $data = array(
            $link($recording->id),
            $recording->name,
            $recording->description,
            $thumbnail($recording->id, $recording->gfilethumbnail),
            $time($recording->gfiletimecreated),
            $duration($recording->gfileduration),
        );
        if(meet_has_capability('managerecordings', $context)){
            $data[] = $actions($recording->id, $recording->name, $recording->hidden);
        }
        $table->data[] = $data;
    }

    return html_writer::table($table);
}

function meet_has_capability($capabilityname, $context) {
    global $USER;

    return is_siteadmin($USER->id) || has_capability('mod/meet:' . $capabilityname, $context);
}
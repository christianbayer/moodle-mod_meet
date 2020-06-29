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
    global $DB, $PAGE, $OUTPUT;

    // Get meet record
    $meet = $DB->get_record('meet', array('id' => $cm->instance), '*', MUST_EXIST);

    // Get the recording
    $recording = $DB->get_record('meet_recordings', array(
        'id'      => $recordingid,
        'meetid'  => $meet->id,
        'deleted' => 0,
    ));

    // Recording not found or hidden
    if( ! $recording || ( ! meet_has_capability('managerecordings', $context) && ($recording->hidden || $recording->gfileduration == 0))) {
        throw new moodle_exception('error_recording_not_found', 'meet');
    }

    // Trigger view event
    meet_view($meet, $recording, false, $course, $cm, $context);

    // Set page properties
    $PAGE->set_url('/mod/meet/view.php', array(
        'id'          => $cm->id,
        'recordingid' => $recording->id,
    ));
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
    $output .= '<div class="recording-preview">';
    $output .= '<div class="iframe-wrapper">';
    $output .= '<div class="iframe-container">';
    $output .= '<iframe src="https://drive.google.com/file/d/' . $recording->gfileid . '/preview" frameborder="0" allowfullscreen></iframe>';
    $output .= '</div>';
    $output .= '</div>';

    if($recording->gchatlogid) {

        // Get the file
        $fs = get_file_storage();
        $file = $fs->get_file($context->id, 'meet', MEET_CHAT_LOG_FILE_AREA, $recording->id, '/', $recording->gchatlogname);

        // Ensure file is loaded
        if($file) {
            // Get the messages
            $content = $file->get_content();
            $rawmessages = preg_split('/\n\n(?=\d\d:)/', $content, -1, PREG_SPLIT_NO_EMPTY);
            $messages = array();
            foreach ($rawmessages as $rawmessage) {
                $starttime = strstr($rawmessage, ',', true);
                preg_match('/\n/', $rawmessage, $matches, PREG_OFFSET_CAPTURE);
                $content = substr($rawmessage, $matches[0][1]);
                $separator = strpos($content, ':');
                $author = substr($content, 0, $separator);
                $message = substr($content, $separator + 2);
                $messages[] = array(
                    'author'  => trim($author),
                    'message' => trim($message),
                    'time'    => date('H:i:s', strtotime($starttime)),
                );
            }

            // HTML
            $output .= '<div class="chat-log-wrapper">';
            $output .= '<h4 class="chat-log-title">' . get_string('chat_log', 'meet') . '</h4>';
            $output .= '<div class="chat-log-container">';
            $output .= '<div class="chat-log-list">';
            foreach ($messages as $message) {
                $output .= '<div class="chat-log-message-wrapper">';
                $output .= '<div class="chat-log-message-author">';
                $output .= $message['author'];
                $output .= '<span class="chat-log-message-time">';
                $output .= $message['time'];
                $output .= '</span>';
                $output .= '</div>';
                $output .= '<div class="chat-log-message">';
                $output .= $message['message'];
                $output .= '</div>';
                $output .= '</div>';
            }
            $output .= '</div>';
            $output .= '</div>';
            $output .= '</div>';
        }
    }
    $output .= '</div>';

    // Page footer
    $output .= $OUTPUT->footer();

    return $output;
}

function meet_render_view_page($course, $cm, $context, $forceupdate) {
    global $DB, $PAGE, $OUTPUT;

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
    if(meet_has_capability('playrecordings', $context)) {
        $output .= $OUTPUT->heading(get_string('recordings', 'meet'), 3);
        if(count($recordings)) {
            $output .= meet_render_recordings_table($recordings, $cm, $context);
        } else {
            $output .= '<p>' . get_string('no_recordings', 'meet') . '</p>';
        }
    }

    if(meet_has_capability('managerecordings', $context)) {
        $output .= meet_render_spacer();
        $output .= '<p>' . get_string('update_recordings_help', 'meet') . '</p>';
        $output .= '<a href="' . (new moodle_url('/mod/meet/view.php', array(
                'id'          => $cm->id,
                'forceupdate' => 1,
            )))->out() . '" class="btn btn-primary">' . get_string('update_recordings', 'meet') . '</a>';
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

    if(meet_has_capability('managerecordings', $context)) {
        global $PAGE;
        $PAGE->requires->js_call_amd('mod_meet/clipboard', 'init');
        $output .= '<button data-link="' . $meet->gmeeturi . '" class="btn btn-success" id="meet-copy-link-to-clipboard">' . get_string('copy_link', 'meet') . '</button>';
    }

    return $output;
}

function meet_render_spacer($margin = 2) {
    return "<div style='margin-top: {$margin}rem;'></div>";
}

function meet_render_recordings_table($recordings, $cm, $context) {
    global $OUTPUT, $USER;

    $link = function ($id) use ($cm) {
        return '<a href="' . (new moodle_url('/mod/meet/view.php',
                array('id' => $cm->id, 'recordingid' => $id)))->out() . '" 
                class="btn btn-sm btn-default">' . get_string('play', 'meet') . '</a>';
    };

    $thumbnail = function ($id, $thumb) use ($cm) {
        $o = get_string('unavailable', 'meet');
        if($thumb) {
            $o = '<a href="' . (new moodle_url('/mod/meet/view.php',
                    array('id' => $cm->id, 'recordingid' => $id)))->out() . '">';
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

    $duration = function ($millis) use ($OUTPUT) {
        $t = round($millis / 1000);
        $time = sprintf('%02d:%02d:%02d', ($t / 3600), ($t / 60 % 60), $t % 60);

        if($millis == 0) {
            $time = '<span style="display: inline-block; vertical-align: middle">' . $time . '</span> ';
            $time .= '<a class="btn btn-link p-0" ' .
                'style="display: inline-block; vertical-align: middle"' .
                'role="presentation" ' .
                'data-container="body" ' .
                'data-toggle="popover" ' .
                'data-placement="left" ' .
                'data-content="' . get_string('broken_recording', 'meet') . '" ' .
                'tabindex="0" ' .
                'data-trigger="hover">' .
                $OUTPUT->pix_icon('error', '', 'mod_meet') .
                '</a>';
        }

        return $time;
    };

    $actions = function ($id, $title, $hidden, $fileid) use ($OUTPUT, $USER, $context, $cm) {

        // Get the formated title
        $titleunescaped = trim(format_string($title, true, array(
            'context' => $context,
            'escape'  => false,
        )));

        // Will keep the HTML
        $o = '';

        // Edit
        $o .= html_writer::link(
            new moodle_url('edit.php', array('id' => $id, 'cmid' => $cm->id)),
            $OUTPUT->pix_icon('t/edit', get_string('edit_recording', 'meet', $title)),
            array('title' => get_string('edit_recording', 'meet', $titleunescaped))
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

        // Open in drive
        $o .= html_writer::link(
            'https://drive.google.com/file/d/' . $fileid . '/view',
            $OUTPUT->pix_icon('link', get_string('open_in_drive', 'meet'), 'mod_meet'),
            array('target' => '_blank', 'title' => get_string('open_in_drive', 'meet'),
            )
        );

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
    if(meet_has_capability('managerecordings', $context)) {
        $table->head[] = get_string('actions', 'meet');
    }

    // Set data
    foreach ($recordings as $recording) {
        if( ! meet_has_capability('managerecordings', $context)
            && ($recording->hidden || $recording->gfileduration == 0)) {
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
        if(meet_has_capability('managerecordings', $context)) {
            $data[] = $actions($recording->id, $recording->name, $recording->hidden, $recording->gfileid);
        }
        $table->data[] = $data;
    }

    $output = '<div class="meet-table-wrapper">';
    $output .= html_writer::table($table);
    $output .= '</div>';

    return $output;
}

function meet_has_capability($capabilityname, $context) {
    global $USER;

    return is_siteadmin($USER->id) || has_capability('mod/meet:' . $capabilityname, $context);
}

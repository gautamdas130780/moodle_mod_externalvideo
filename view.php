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
 * Externalvideo module main user interface
 *
 * @package    mod_externalvideo
 * @author     Gautam Kumar Das<gautam.arg@gmail.com>
 */

require('../../config.php');
require_once("$CFG->dirroot/mod/externalvideo/lib.php");
require_once("$CFG->dirroot/mod/externalvideo/locallib.php");
require_once($CFG->libdir . '/completionlib.php');

$id       = optional_param('id', 0, PARAM_INT);        // Course module ID
$u        = optional_param('u', 0, PARAM_INT);         // URL instance id
$redirect = optional_param('redirect', 0, PARAM_BOOL);

if ($u) {  // Two ways to specify the module
    $externalvideo = $DB->get_record('externalvideo', array('id'=>$u), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('externalvideo', $externalvideo->id, $externalvideo->course, false, MUST_EXIST);

} else {
    $cm = get_coursemodule_from_id('externalvideo', $id, 0, false, MUST_EXIST);
    $externalvideo = $DB->get_record('externalvideo', array('id'=>$cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/externalvideo:view', $context);

// Completion and trigger events.
externalvideo_view($externalvideo, $course, $cm, $context);

$PAGE->set_url('/mod/externalvideo/view.php', array('id' => $cm->id));

// Make sure URL exists before generating output - some older sites may contain empty urls
// Do not use PARAM_URL here, it is too strict and does not support general URIs!
$exturl = trim($externalvideo->externalurl);
if (empty($exturl) or $exturl === 'http://') {
    externalvideo_print_header($externalvideo, $cm, $course);
    externalvideo_print_heading($externalvideo, $cm, $course);
    externalvideo_print_intro($externalvideo, $cm, $course);
    notice(get_string('invalidstoredurl', 'externalvideo'), new moodle_url('/course/view.php', array('id'=>$cm->course)));
    die;
}
unset($exturl);

$displaytype = externalvideo_get_final_display_type($externalvideo);
if ($displaytype == RESOURCELIB_DISPLAY_OPEN) {
    // For 'open' links, we always redirect to the content - except if the user
    // just chose 'save and display' from the form then that would be confusing
    if (strpos(get_local_referer(false), 'modedit.php') === false) {
        $redirect = false;
    }
}

if ($redirect) {
    // coming from course page or externalvideo index page,
    // the redirection is needed for completion tracking and logging
    $fullurl = str_replace('&amp;', '&', externalvideo_get_full_url($externalvideo, $cm, $course));

    if (!course_get_format($course)->has_view_page()) {
        // If course format does not have a view page, add redirection delay with a link to the edit page.
        // Otherwise teacher is redirected to the external URL without any possibility to edit activity or course settings.
        $editurl = null;
        if (has_capability('moodle/course:manageactivities', $context)) {
            $editurl = new moodle_url('/course/modedit.php', array('update' => $cm->id));
            $edittext = get_string('editthisactivity');
        } else if (has_capability('moodle/course:update', $context->get_course_context())) {
            $editurl = new moodle_url('/course/edit.php', array('id' => $course->id));
            $edittext = get_string('editcoursesettings');
        }
        if ($editurl) {
            redirect($fullurl, html_writer::link($editurl, $edittext)."<br/>".
                    get_string('pageshouldredirect'), 10);
        }
    }
    redirect($fullurl);
}

switch ($displaytype) {
    case RESOURCELIB_DISPLAY_EMBED:
        externalvideo_display_embed($externalvideo, $cm, $course);
        break;
    case RESOURCELIB_DISPLAY_FRAME:
        externalvideo_display_frame($externalvideo, $cm, $course);
        break;
    default:
        externalvideo_print_workaround($externalvideo, $cm, $course);
        break;
}

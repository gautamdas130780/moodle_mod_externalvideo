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

/*
 * Handling all ajax request for externalvideo API
 *
 * @package    mod_externalvideo
 * @author     Gautam Kumar Das<gautam.arg@gmail.com>
 */
define('AJAX_SCRIPT', true);
define('NO_DEBUG_DISPLAY', true);

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/externalvideo/lib.php');
require_once($CFG->dirroot . '/mod/externalvideo/locallib.php');

$resource = required_param('resource', PARAM_RAW);
$stats = optional_param('stats', '', PARAM_RAW_TRIMMED);

list($origin, $resource, $id) = explode('_', $resource);

$cm = get_coursemodule_from_id('externalvideo', $id, 0, false, MUST_EXIST);

$externalvideo = $DB->get_record('externalvideo', array('id'=>$cm->instance), '*', MUST_EXIST);

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);

// Completion and trigger events.
externalvideo_view($externalvideo, $course, $cm, $context);

$PAGE->set_url('/mod/externalvideo/view.php', array('id' => $cm->id));

echo $OUTPUT->header(); // send headers

$returndata = new stdClass();
$returndata->status = null;
$returndata->error = null;

$stats = json_decode($stats);
$stats->origin = $origin;
if (externalvideo_save_video_stats($cm, $stats)) {
    $returndata->status = "OK";
    echo json_encode((array)$returndata);
    die;    
}


if (!isloggedin()) {
    // tell user to log in to view comments
    $returndata->status = "ERROR";
    $returndata->error = "require_login";
    echo json_encode((array)$returndata);
}
// ignore request
die;

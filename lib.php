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
 * Mandatory public API of externalvideo module
 *
 * @package    mod_externalvideo
 * @author     Gautam Kumar Das<gautam.arg@gmail.com>
 */

defined('MOODLE_INTERNAL') || die;

/**
 * List of features supported in URL module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function externalvideo_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:           return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;

        default: return null;
    }
}

/**
 * Returns all other caps used in module
 * @return array
 */
function externalvideo_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function externalvideo_reset_userdata($data) {
    return array();
}

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function externalvideo_get_view_actions() {
    return array('view', 'view all');
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function externalvideo_get_post_actions() {
    return array('update', 'add');
}

/**
 * Add externalvideo instance.
 * @param object $data
 * @param object $mform
 * @return int new externalvideo instance id
 */
function externalvideo_add_instance($data, $mform) {
    global $CFG, $DB;

    require_once($CFG->dirroot.'/mod/externalvideo/locallib.php');

    $parameters = array();
    for ($i=0; $i < 100; $i++) {
        $parameter = "parameter_$i";
        $variable  = "variable_$i";
        if (empty($data->$parameter) or empty($data->$variable)) {
            continue;
        }
        $parameters[$data->$parameter] = $data->$variable;
    }
    $data->parameters = serialize($parameters);

    $displayoptions = array();
    if ($data->display == RESOURCELIB_DISPLAY_POPUP) {
        $displayoptions['popupwidth']  = $data->popupwidth;
        $displayoptions['popupheight'] = $data->popupheight;
    }
    if (in_array($data->display, array(RESOURCELIB_DISPLAY_AUTO, RESOURCELIB_DISPLAY_EMBED, RESOURCELIB_DISPLAY_FRAME))) {
        $displayoptions['printintro']   = (int)!empty($data->printintro);
    }
    
    $displayoptions['embedoncoursepage'] = (int)!empty($data->embedoncoursepage);
    $displayoptions['saveseektime'] = $data->saveseektime;
    
    $data->displayoptions = serialize($displayoptions);

    $data->externalurl = externalvideo_fix_submitted_url($data->externalurl);

    $data->timemodified = time();
    $data->id = $DB->insert_record('externalvideo', $data);

    return $data->id;
}

/**
 * Update externalvideo instance.
 * @param object $data
 * @param object $mform
 * @return bool true
 */
function externalvideo_update_instance($data, $mform) {
    global $CFG, $DB;

    require_once($CFG->dirroot.'/mod/externalvideo/locallib.php');

    $parameters = array();
    for ($i=0; $i < 100; $i++) {
        $parameter = "parameter_$i";
        $variable  = "variable_$i";
        if (empty($data->$parameter) or empty($data->$variable)) {
            continue;
        }
        $parameters[$data->$parameter] = $data->$variable;
    }
    $data->parameters = serialize($parameters);

    $displayoptions = array();
    if ($data->display == RESOURCELIB_DISPLAY_POPUP) {
        $displayoptions['popupwidth']  = $data->popupwidth;
        $displayoptions['popupheight'] = $data->popupheight;
    }
    if (in_array($data->display, array(RESOURCELIB_DISPLAY_AUTO, RESOURCELIB_DISPLAY_EMBED, RESOURCELIB_DISPLAY_FRAME))) {
        $displayoptions['printintro']   = (int)!empty($data->printintro);
    }
    
    $displayoptions['embedoncoursepage'] = (int)!empty($data->embedoncoursepage);
    $displayoptions['saveseektime'] = $data->saveseektime;
    
    $data->displayoptions = serialize($displayoptions);

    $data->externalurl = externalvideo_fix_submitted_url($data->externalurl);

    $data->timemodified = time();
    $data->id           = $data->instance;

    $DB->update_record('externalvideo', $data);

    return true;
}

/**
 * Delete externalvideo instance.
 * @param int $id
 * @return bool true
 */
function externalvideo_delete_instance($id) {
    global $DB;

    if (!$externalvideo = $DB->get_record('externalvideo', array('id'=>$id))) {
        return false;
    }

    // note: all context files are deleted automatically

    $DB->delete_records('externalvideo', array('id'=>$externalvideo->id));
    
    $cm = get_coursemodule_from_instance('externalvideo', $externalvideo->id);
    $DB->delete_records('externalvideo_stats', array('cmid' => $cm->id));

    return true;
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 *
 * See {@link get_array_of_activities()} in course/lib.php
 *
 * @param object $coursemodule
 * @return cached_cm_info info
 */
function externalvideo_get_coursemodule_info($coursemodule) {
    global $CFG, $DB;
    require_once("$CFG->dirroot/mod/externalvideo/locallib.php");

    if (!$externalvideo = $DB->get_record('externalvideo', array('id'=>$coursemodule->instance),
            'id, name, display, displayoptions, externalurl, parameters, intro, introformat')) {
        return NULL;
    }

    $info = new cached_cm_info();
    $info->name = $externalvideo->name;

    //note: there should be a way to differentiate links from normal resources
    $info->icon = externalvideo_guess_icon($externalvideo->externalurl, 24);

    $display = externalvideo_get_final_display_type($externalvideo);

    if ($display == RESOURCELIB_DISPLAY_POPUP) {
        $fullurl = "$CFG->wwwroot/mod/externalvideo/view.php?id=$coursemodule->id&amp;redirect=1";
        $options = empty($externalvideo->displayoptions) ? array() : unserialize($externalvideo->displayoptions);
        $width  = empty($options['popupwidth'])  ? 620 : $options['popupwidth'];
        $height = empty($options['popupheight']) ? 450 : $options['popupheight'];
        $wh = "width=$width,height=$height,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes";
        $info->onclick = "window.open('$fullurl', '', '$wh'); return false;";

    } else if ($display == RESOURCELIB_DISPLAY_NEW) {
        $fullurl = "$CFG->wwwroot/mod/externalvideo/view.php?id=$coursemodule->id&amp;redirect=1";
        $info->onclick = "window.open('$fullurl'); return false;";

    }

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $info->content = format_module_intro('externalvideo', $externalvideo, $coursemodule->id, false);
    }

    return $info;
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function externalvideo_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $module_pagetype = array('mod-externalvideo-*'=>get_string('page-mod-externalvideo-x', 'externalvideo'));
    return $module_pagetype;
}

/**
 * Export externalvideo resource contents
 *
 * @return array of file content
 */
function externalvideo_export_contents($cm, $baseurl) {
    global $CFG, $DB;
    require_once("$CFG->dirroot/mod/externalvideo/locallib.php");
    $contents = array();
    $context = context_module::instance($cm->id);

    $course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
    $externalvideorecord = $DB->get_record('externalvideo', array('id'=>$cm->instance), '*', MUST_EXIST);

    $fullurl = str_replace('&amp;', '&', externalvideo_get_full_url($externalvideorecord, $cm, $course));
    $isurl = clean_param($fullurl, PARAM_URL);
    if (empty($isurl)) {
        return null;
    }

    $externalvideo = array();
    $externalvideo['type'] = 'externalvideo';
    $externalvideo['filename']     = clean_param(format_string($externalvideorecord->name), PARAM_FILE);
    $externalvideo['filepath']     = null;
    $externalvideo['filesize']     = 0;
    $externalvideo['fileurl']      = $fullurl;
    $externalvideo['timecreated']  = null;
    $externalvideo['timemodified'] = $externalvideorecord->timemodified;
    $externalvideo['sortorder']    = null;
    $externalvideo['userid']       = null;
    $externalvideo['author']       = null;
    $externalvideo['license']      = null;
    $contents[] = $externalvideo;

    return $contents;
}

/**
 * Register the ability to handle drag and drop file uploads
 * @return array containing details of the files / types the mod can handle
 */
function externalvideo_dndupload_register() {
    return array('types' => array(
                     array('identifier' => 'url', 'message' => get_string('createexternalvideo', 'externalvideo'))
                 ));
}

/**
 * Handle a file that has been uploaded
 * @param object $uploadinfo details of the file / content that has been uploaded
 * @return int instance id of the newly created mod
 */
function externalvideo_dndupload_handle($uploadinfo) {
    // Gather all the required data.
    $data = new stdClass();
    $data->course = $uploadinfo->course->id;
    $data->name = $uploadinfo->displayname;
    $data->intro = '<p>'.$uploadinfo->displayname.'</p>';
    $data->introformat = FORMAT_HTML;
    $data->externalurl = clean_param($uploadinfo->content, PARAM_URL);
    $data->timemodified = time();

    // Set the display options to the site defaults.
    $config = get_config('externalvideo');
    $data->display = $config->display;
    $data->popupwidth = $config->popupwidth;
    $data->popupheight = $config->popupheight;
    $data->printintro = $config->printintro;

    return externalvideo_add_instance($data, null);
}

/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param  stdClass $externalvideo        externalvideo object
 * @param  stdClass $course     course object
 * @param  stdClass $cm         course module object
 * @param  stdClass $context    context object
 * @since Moodle 3.0
 */
function externalvideo_view($externalvideo, $course, $cm, $context) {

    // Trigger course_module_viewed event.
    $params = array(
        'context' => $context,
        'objectid' => $externalvideo->id
    );

    $event = \mod_externalvideo\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('externalvideo', $externalvideo);
    $event->trigger();

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

/**
 * Sets dynamic information about a course module
 *
 * This function is called from cm_info when displaying the module
 * mod_folder can be displayed inline on course page and therefore have no course link
 *
 * @param cm_info $cm
 */
function externalvideo_cm_info_dynamic(cm_info $cm) {
    global $CFG, $DB, $PAGE;
    static $playerjscount = 0;
    require_once($CFG->dirroot.'/mod/externalvideo/locallib.php');
    if (!$externalvideo = $DB->get_record('externalvideo', array('id'=>$cm->instance),
            'id, name, display, displayoptions, externalurl, parameters, intro, introformat')) {
        return NULL;
    }
    $displayoptions = empty($externalvideo->displayoptions) ? array() : unserialize($externalvideo->displayoptions);
    
    $content = '';
    if ($displayoptions['embedoncoursepage']) { 
        $course = $cm->get_course();
        $content = externalvideo_get_embed($externalvideo, $cm, $course);
    }
    
    if ($cm->showdescription) {
        $content .= $cm->content;
    }
    $cm->set_content($content);
    return;
}
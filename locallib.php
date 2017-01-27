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
 * Private externalvideo module utility functions
 *
 * @package    mod_externalvideo
 * @author     Gautam Kumar Das<gautam.arg@gmail.com>
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/filelib.php");
require_once("$CFG->libdir/resourcelib.php");
require_once("$CFG->dirroot/mod/externalvideo/lib.php");

/**
 * This methods does weak externalvideo validation, we are looking for major problems only,
 * no strict RFE validation.
 *
 * @param $externalvideo
 * @return bool true is seems valid, false if definitely not valid URL
 */
function externalvideo_appears_valid_url($externalvideo) {
    if (preg_match('/^(\/|https?:|ftp:)/i', $externalvideo)) {
        // note: this is not exact validation, we look for severely malformed URLs only
        return (bool)preg_match('/^[a-z]+:\/\/([^:@\s]+:[^@\s]+@)?[a-z0-9_\.\-]+(:[0-9]+)?(\/[^#]*)?(#.*)?$/i', $externalvideo);
    } else {
        return (bool)preg_match('/^[a-z]+:\/\/...*$/i', $externalvideo);
    }
}

/**
 * Fix common URL problems that we want teachers to see fixed
 * the next time they edit the resource.
 *
 * This function does not include any XSS protection.
 *
 * @param string $externalvideo
 * @return string
 */
function externalvideo_fix_submitted_url($externalvideo) {
    // note: empty urls are prevented in form validation
    $externalvideo = trim($externalvideo);

    // remove encoded entities - we want the raw URI here
    $externalvideo = html_entity_decode($externalvideo, ENT_QUOTES, 'UTF-8');

    if (!preg_match('|^[a-z]+:|i', $externalvideo) and !preg_match('|^/|', $externalvideo)) {
        // invalid URI, try to fix it by making it normal URL,
        // please note relative urls are not allowed, /xx/yy links are ok
        $externalvideo = 'http://'.$externalvideo;
    }

    return $externalvideo;
}

/**
 * Return full url with all extra parameters
 *
 * This function does not include any XSS protection.
 *
 * @param string $externalvideo
 * @param object $cm
 * @param object $course
 * @param object $config
 * @return string url with & encoded as &amp;
 */
function externalvideo_get_full_url($externalvideo, $cm, $course, $config=null) {

    $parameters = empty($externalvideo->parameters) ? array() : unserialize($externalvideo->parameters);

    // make sure there are no encoded entities, it is ok to do this twice
    $fullurl = html_entity_decode($externalvideo->externalurl, ENT_QUOTES, 'UTF-8');

    if (preg_match('/^(\/|https?:|ftp:)/i', $fullurl) or preg_match('|^/|', $fullurl)) {
        // encode extra chars in URLs - this does not make it always valid, but it helps with some UTF-8 problems
        $allowed = "a-zA-Z0-9".preg_quote(';/?:@=&$_.+!*(),-#%', '/');
        $fullurl = preg_replace_callback("/[^$allowed]/", 'externalvideo_filter_callback', $fullurl);
    } else {
        // encode special chars only
        $fullurl = str_replace('"', '%22', $fullurl);
        $fullurl = str_replace('\'', '%27', $fullurl);
        $fullurl = str_replace(' ', '%20', $fullurl);
        $fullurl = str_replace('<', '%3C', $fullurl);
        $fullurl = str_replace('>', '%3E', $fullurl);
    }

    // add variable url parameters
    if (!empty($parameters)) {
        if (!$config) {
            $config = get_config('externalvideo');
        }
        $paramvalues = externalvideo_get_variable_values($externalvideo, $cm, $course, $config);

        foreach ($parameters as $parse=>$parameter) {
            if (isset($paramvalues[$parameter])) {
                $parameters[$parse] = rawurlencode($parse).'='.rawurlencode($paramvalues[$parameter]);
            } else {
                unset($parameters[$parse]);
            }
        }

        if (!empty($parameters)) {
            if (stripos($fullurl, 'teamspeak://') === 0) {
                $fullurl = $fullurl.'?'.implode('?', $parameters);
            } else {
                $join = (strpos($fullurl, '?') === false) ? '?' : '&';
                $fullurl = $fullurl.$join.implode('&', $parameters);
            }
        }
    }

    // encode all & to &amp; entity
    $fullurl = str_replace('&', '&amp;', $fullurl);

    return $fullurl;
}

/**
 * Unicode encoding helper callback
 * @internal
 * @param array $matches
 * @return string
 */
function externalvideo_filter_callback($matches) {
    return rawurlencode($matches[0]);
}

/**
 * Print externalvideo header.
 * @param object $externalvideo
 * @param object $cm
 * @param object $course
 * @return void
 */
function externalvideo_print_header($externalvideo, $cm, $course) {
    global $PAGE, $OUTPUT;

    $PAGE->set_title($course->shortname.': '.$externalvideo->name);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_activity_record($externalvideo);
    echo $OUTPUT->header();
}

/**
 * Print externalvideo heading.
 * @param object $externalvideo
 * @param object $cm
 * @param object $course
 * @param bool $notused This variable is no longer used.
 * @return void
 */
function externalvideo_print_heading($externalvideo, $cm, $course, $notused = false) {
    global $OUTPUT;
    echo $OUTPUT->heading(format_string($externalvideo->name), 2);
}

/**
 * Print externalvideo introduction.
 * @param object $externalvideo
 * @param object $cm
 * @param object $course
 * @param bool $ignoresettings print even if not specified in modedit
 * @return void
 */
function externalvideo_print_intro($externalvideo, $cm, $course, $ignoresettings=false) {
    global $OUTPUT;

    $options = empty($externalvideo->displayoptions) ? array() : unserialize($externalvideo->displayoptions);
    if ($ignoresettings or !empty($options['printintro'])) {
        if (trim(strip_tags($externalvideo->intro))) {
            echo $OUTPUT->box_start('mod_introbox', 'externalvideointro');
            echo format_module_intro('externalvideo', $externalvideo, $cm->id);
            echo $OUTPUT->box_end();
        }
    }
}

/**
 * Display externalvideo frames.
 * @param object $externalvideo
 * @param object $cm
 * @param object $course
 * @return does not return
 */
function externalvideo_display_frame($externalvideo, $cm, $course) {
    global $PAGE, $OUTPUT, $CFG;

    $frame = optional_param('frameset', 'main', PARAM_ALPHA);

    if ($frame === 'top') {
        $PAGE->set_pagelayout('frametop');
        externalvideo_print_header($externalvideo, $cm, $course);
        externalvideo_print_heading($externalvideo, $cm, $course);
        externalvideo_print_intro($externalvideo, $cm, $course);
        echo $OUTPUT->footer();
        die;

    } else {
        $config = get_config('externalvideo');
        $context = context_module::instance($cm->id);
        $exteurl = externalvideo_get_full_url($externalvideo, $cm, $course, $config);
        $navurl = "$CFG->wwwroot/mod/externalvideo/view.php?id=$cm->id&amp;frameset=top";
        $coursecontext = context_course::instance($course->id);
        $courseshortname = format_string($course->shortname, true, array('context' => $coursecontext));
        $title = strip_tags($courseshortname.': '.format_string($externalvideo->name));
        $framesize = $config->framesize;
        $modulename = s(get_string('modulename','externalvideo'));
        $contentframetitle = s(format_string($externalvideo->name));
        $dir = get_string('thisdirection', 'langconfig');

        $extframe = <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html dir="$dir">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title>$title</title>
  </head>
  <frameset rows="$framesize,*">
    <frame src="$navurl" title="$modulename"/>
    <frame src="$exteurl" title="$contentframetitle"/>
  </frameset>
</html>
EOF;

        @header('Content-Type: text/html; charset=utf-8');
        echo $extframe;
        die;
    }
}

/**
 * Print externalvideo info and link.
 * @param object $externalvideo
 * @param object $cm
 * @param object $course
 * @return does not return
 */
function externalvideo_print_workaround($externalvideo, $cm, $course) {
    global $OUTPUT;

    externalvideo_print_header($externalvideo, $cm, $course);
    externalvideo_print_heading($externalvideo, $cm, $course, true);
    externalvideo_print_intro($externalvideo, $cm, $course, true);

    $fullurl = externalvideo_get_full_url($externalvideo, $cm, $course);

    $display = externalvideo_get_final_display_type($externalvideo);
    if ($display == RESOURCELIB_DISPLAY_POPUP) {
        $jsfullurl = addslashes_js($fullurl);
        $options = empty($externalvideo->displayoptions) ? array() : unserialize($externalvideo->displayoptions);
        $width  = empty($options['popupwidth'])  ? 620 : $options['popupwidth'];
        $height = empty($options['popupheight']) ? 450 : $options['popupheight'];
        $wh = "width=$width,height=$height,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes";
        $extra = "onclick=\"window.open('$jsfullurl', '', '$wh'); return false;\"";

    } else if ($display == RESOURCELIB_DISPLAY_NEW) {
        $extra = "onclick=\"this.target='_blank';\"";

    } else {
        $extra = '';
    }

    echo '<div class="externalvideoworkaround">';
    print_string('clicktoopen', 'externalvideo', "<a href=\"$fullurl\" $extra>$fullurl</a>");
    echo '</div>';

    echo $OUTPUT->footer();
    die;
}

/**
 * Display embedded externalvideo file.
 * @param object $externalvideo
 * @param object $cm
 * @param object $course
 * @return does not return
 */
function externalvideo_display_embed($externalvideo, $cm, $course) {
    global $CFG, $PAGE, $OUTPUT;

    $code = externalvideo_get_embed($externalvideo, $cm, $course);
    
    externalvideo_print_header($externalvideo, $cm, $course);
    externalvideo_print_heading($externalvideo, $cm, $course);

    echo $code;

    externalvideo_print_intro($externalvideo, $cm, $course);

    echo $OUTPUT->footer();
    die;
}

function externalvideo_get_embed($externalvideo, $cm, $course) {
    global $CFG, $PAGE, $OUTPUT;
    
    $mimetype = resourcelib_guess_url_mimetype($externalvideo->externalurl);
    $fullurl  = externalvideo_get_full_url($externalvideo, $cm, $course);
    $title    = $externalvideo->name;

    $link = html_writer::tag('a', $fullurl, array('href'=>str_replace('&amp;', '&', $fullurl)));
    $clicktoopen = get_string('clicktoopen', 'externalvideo', $link);
    $moodleurl = new moodle_url($fullurl);

    $extension = resourcelib_get_extension($externalvideo->externalurl);
    
    $mediarenderer = $PAGE->get_renderer('mod_externalvideo');
    
    $embedoptions = array(
        core_media::OPTION_TRUSTED => true,
        core_media::OPTION_BLOCK => true
    );

    if (in_array($mimetype, array('image/gif','image/jpeg','image/png'))) {  // It's an image
        $code = resourcelib_embed_image($fullurl, $title);

    } else if ($mediarenderer->can_embed_url($moodleurl, $embedoptions)) {
        $embedoptions['externalvideo_objects'] = new stdClass();
        $embedoptions['externalvideo_objects']->instance = $externalvideo;
        $embedoptions['externalvideo_objects']->cm = $cm;
        $embedoptions['externalvideo_objects']->course = $course;
        // Media (audio/video) file.
        $code = $mediarenderer->embed_url($moodleurl, $title, 0, 0, $embedoptions);

    } else {
        // anything else - just try object tag enlarged as much as possible
        $code = resourcelib_embed_general($fullurl, $title, $clicktoopen, $mimetype);
    }
    return $code;
}

/**
 * Decide the best display format.
 * @param object $externalvideo
 * @return int display type constant
 */
function externalvideo_get_final_display_type($externalvideo) {
    global $CFG;

    if ($externalvideo->display != RESOURCELIB_DISPLAY_AUTO) {
        return $externalvideo->display;
    }

    // detect links to local moodle pages
    if (strpos($externalvideo->externalurl, $CFG->wwwroot) === 0) {
        if (strpos($externalvideo->externalurl, 'file.php') === false and strpos($externalvideo->externalurl, '.php') !== false ) {
            // most probably our moodle page with navigation
            return RESOURCELIB_DISPLAY_OPEN;
        }
    }

    static $download = array('application/zip', 'application/x-tar', 'application/g-zip',     // binary formats
                             'application/pdf', 'text/html');  // these are known to cause trouble for external links, sorry
    static $embed    = array('image/gif', 'image/jpeg', 'image/png', 'image/svg+xml',         // images
                             'application/x-shockwave-flash', 'video/x-flv', 'video/x-ms-wm', // video formats
                             'video/quicktime', 'video/mpeg', 'video/mp4',
                             'audio/mp3', 'audio/x-realaudio-plugin', 'x-realaudio-plugin',   // audio formats,
                            );

    $mimetype = resourcelib_guess_url_mimetype($externalvideo->externalurl);

    if (in_array($mimetype, $download)) {
        return RESOURCELIB_DISPLAY_DOWNLOAD;
    }
    if (in_array($mimetype, $embed)) {
        return RESOURCELIB_DISPLAY_EMBED;
    }

    // let the browser deal with it somehow
    return RESOURCELIB_DISPLAY_OPEN;
}

/**
 * Get the parameters that may be appended to URL
 * @param object $config externalvideo module config options
 * @return array array describing opt groups
 */
function externalvideo_get_variable_options($config) {
    global $CFG;

    $options = array();
    $options[''] = array('' => get_string('chooseavariable', 'externalvideo'));

    $options[get_string('course')] = array(
        'courseid'        => 'id',
        'coursefullname'  => get_string('fullnamecourse'),
        'courseshortname' => get_string('shortnamecourse'),
        'courseidnumber'  => get_string('idnumbercourse'),
        'coursesummary'   => get_string('summary'),
        'courseformat'    => get_string('format'),
    );

    $options[get_string('modulename', 'externalvideo')] = array(
        'externalvideoinstance'     => 'id',
        'externalvideocmid'         => 'cmid',
        'externalvideoname'         => get_string('name'),
        'externalvideoidnumber'     => get_string('idnumbermod'),
    );

    $options[get_string('miscellaneous')] = array(
        'sitename'        => get_string('fullsitename'),
        'serverexternalvideo'       => get_string('serverexternalvideo', 'externalvideo'),
        'currenttime'     => get_string('time'),
        'lang'            => get_string('language'),
    );
    if (!empty($config->secretphrase)) {
        $options[get_string('miscellaneous')]['encryptedcode'] = get_string('encryptedcode');
    }

    $options[get_string('user')] = array(
        'userid'          => 'id',
        'userusername'    => get_string('username'),
        'useridnumber'    => get_string('idnumber'),
        'userfirstname'   => get_string('firstname'),
        'userlastname'    => get_string('lastname'),
        'userfullname'    => get_string('fullnameuser'),
        'useremail'       => get_string('email'),
        'usericq'         => get_string('icqnumber'),
        'userphone1'      => get_string('phone1'),
        'userphone2'      => get_string('phone2'),
        'userinstitution' => get_string('institution'),
        'userdepartment'  => get_string('department'),
        'useraddress'     => get_string('address'),
        'usercity'        => get_string('city'),
        'usertimezone'    => get_string('timezone'),
        'userexternalvideo'         => get_string('webpage'),
    );

    if ($config->rolesinparams) {
        $roles = role_fix_names(get_all_roles());
        $roleoptions = array();
        foreach ($roles as $role) {
            $roleoptions['course'.$role->shortname] = get_string('yourwordforx', '', $role->localname);
        }
        $options[get_string('roles')] = $roleoptions;
    }

    return $options;
}

/**
 * Get the parameter values that may be appended to URL
 * @param object $externalvideo module instance
 * @param object $cm
 * @param object $course
 * @param object $config module config options
 * @return array of parameter values
 */
function externalvideo_get_variable_values($externalvideo, $cm, $course, $config) {
    global $USER, $CFG;

    $site = get_site();

    $coursecontext = context_course::instance($course->id);

    $values = array (
        'courseid'        => $course->id,
        'coursefullname'  => format_string($course->fullname),
        'courseshortname' => format_string($course->shortname, true, array('context' => $coursecontext)),
        'courseidnumber'  => $course->idnumber,
        'coursesummary'   => $course->summary,
        'courseformat'    => $course->format,
        'lang'            => current_language(),
        'sitename'        => format_string($site->fullname),
        'serverexternalvideo'       => $CFG->wwwroot,
        'currenttime'     => time(),
        'externalvideoinstance'     => $externalvideo->id,
        'externalvideocmid'         => $cm->id,
        'externalvideoname'         => format_string($externalvideo->name),
        'externalvideoidnumber'     => $cm->idnumber,
    );

    if (isloggedin()) {
        $values['userid']          = $USER->id;
        $values['userusername']    = $USER->username;
        $values['useridnumber']    = $USER->idnumber;
        $values['userfirstname']   = $USER->firstname;
        $values['userlastname']    = $USER->lastname;
        $values['userfullname']    = fullname($USER);
        $values['useremail']       = $USER->email;
        $values['usericq']         = $USER->icq;
        $values['userphone1']      = $USER->phone1;
        $values['userphone2']      = $USER->phone2;
        $values['userinstitution'] = $USER->institution;
        $values['userdepartment']  = $USER->department;
        $values['useraddress']     = $USER->address;
        $values['usercity']        = $USER->city;
        $now = new DateTime('now', core_date::get_user_timezone_object());
        $values['usertimezone']    = $now->getOffset() / 3600.0; // Value in hours for BC.
        $values['userexternalvideo']         = $USER->externalvideo;
    }

    // weak imitation of Single-Sign-On, for backwards compatibility only
    // NOTE: login hack is not included in 2.0 any more, new contrib auth plugin
    //       needs to be createed if somebody needs the old functionality!
    if (!empty($config->secretphrase)) {
        $values['encryptedcode'] = externalvideo_get_encrypted_parameter($externalvideo, $config);
    }

    //hmm, this is pretty fragile and slow, why do we need it here??
    if ($config->rolesinparams) {
        $coursecontext = context_course::instance($course->id);
        $roles = role_fix_names(get_all_roles($coursecontext), $coursecontext, ROLENAME_ALIAS);
        foreach ($roles as $role) {
            $values['course'.$role->shortname] = $role->localname;
        }
    }

    return $values;
}

/**
 * BC internal function
 * @param object $externalvideo
 * @param object $config
 * @return string
 */
function externalvideo_get_encrypted_parameter($externalvideo, $config) {
    global $CFG;

    if (file_exists("$CFG->dirroot/local/externserverfile.php")) {
        require_once("$CFG->dirroot/local/externserverfile.php");
        if (function_exists('extern_server_file')) {
            return extern_server_file($externalvideo, $config);
        }
    }
    return md5(getremoteaddr().$config->secretphrase);
}

/**
 * Optimised mimetype detection from general URL
 * @param $fullurl
 * @param int $size of the icon.
 * @return string|null mimetype or null when the filetype is not relevant.
 */
function externalvideo_guess_icon($fullurl, $size = null) {
    global $CFG;
    require_once("$CFG->libdir/filelib.php");

    if (substr_count($fullurl, '/') < 3 or substr($fullurl, -1) === '/') {
        // Most probably default directory - index.php, index.html, etc. Return null because
        // we want to use the default module icon instead of the HTML file icon.
        return null;
    }

    $icon = file_extension_icon($fullurl, $size);
    $htmlicon = file_extension_icon('.htm', $size);
    $unknownicon = file_extension_icon('', $size);

    // We do not want to return those icon types, the module icon is more appropriate.
    if ($icon === $unknownicon || $icon === $htmlicon) {
        return null;
    }

    return $icon;
}

/**
 * Return embedded externalvideo file.
 * @param object $externalvideo
 * @param object $cm
 * @param object $course
 * @return does not return
 */
function externalvideo_return_embed($externalvideo, $cm, $course) {
    global $CFG, $PAGE, $OUTPUT;

    $core_course_renderer = $PAGE->get_renderer('core', 'course');
    $completioninfo = new completion_info($course);
    
    $mimetype = resourcelib_guess_url_mimetype($externalvideo->externalurl);
    $fullurl  = externalvideo_get_full_url($externalvideo, $cm, $course);
    $title    = $externalvideo->name;

    $link = html_writer::tag('a', $fullurl, array('href'=>str_replace('&amp;', '&', $fullurl)));
    $clicktoopen = get_string('clicktoopen', 'externalvideo', $link);
    $moodleurl = new moodle_url($fullurl);

    $extension = resourcelib_get_extension($externalvideo->externalurl);

    $mediarenderer = $PAGE->get_renderer('core', 'media');
    $embedoptions = array(
        core_media::OPTION_TRUSTED => true,
        core_media::OPTION_BLOCK => true
    );

    if (in_array($mimetype, array('image/gif','image/jpeg','image/png'))) {  // It's an image
        $code = resourcelib_embed_image($fullurl, $title);

    } else if ($mediarenderer->can_embed_url($moodleurl, $embedoptions)) {
        // Media (audio/video) file.
        $code = $mediarenderer->embed_url($moodleurl, $title, 0, 0, $embedoptions);

    } else {
        // anything else - just try object tag enlarged as much as possible
        $code = resourcelib_embed_general($fullurl, $title, $clicktoopen, $mimetype);
    }
    $instancename = html_writer::start_div('sr-title');
    $instancename .= '<h4>'.$cm->get_formatted_name().'</h4>';
    $completionicon = $core_course_renderer->course_section_cm_completion($course, $completioninfo, $cm);
    $instancename .= html_writer::span($completionicon, 'sr-actions', array('id'=>'completion-icon-'.$cm->id));
    $instancename .= html_writer::end_div();
    $description = null;
    if (!empty($description = $cm->get_formatted_content(array('overflowdiv' => true, 'noclean' => true)))) {
        $description = html_writer::tag('div', $description, array('class'=>'sr-content'));
    }
    $code .= html_writer::div($instancename . $description, 'sr-details');
    return $code;
}

function externalvideo_get_url_repository() {
    static $url_repo = null;
    if ($url_repo != null) {
        return $url_repo;
    }
    global $CFG, $PAGE;
    require_once($CFG->dirroot.'/repository/lib.php');
    require_once($CFG->libdir.'/filelib.php');
    require_once($CFG->dirroot.'/repository/url/lib.php');
    $params = array();
    $params['context'] = array(context_system::instance());
    $params['currentcontext'] = $PAGE->context;
    $params['return_types'] = FILE_INTERNAL;
    $repos = repository::get_instances($params);
    $url_repo = false;
    foreach($repos as $repo) {
        $info = $repo->get_meta();
        if ($info->type == 'url') {
            $url_repo = $repo;
            break;
        }
    }
    return $url_repo;
}

function externalvideo_get_url_thumbnails($repo, $url) {
    $list = array();
    $info = $repo->get_meta();
    if ($info->type == 'url') {
        $url = clean_param($url, PARAM_URL);
        $url = htmlspecialchars_decode(url_to_absolute(null, $url));
        $curl = new curl;
        $curl->setopt(array('CURLOPT_FOLLOWLOCATION' => true, 'CURLOPT_MAXREDIRS' => 3));
        $msg = $curl->head($url);
        $info = $curl->get_info();
        if ($info['http_code'] == 200) {
            // parse as html
            $htmlcontent = $curl->get($info['url']);
            $ddoc = new DOMDocument();
            @$ddoc->loadHTML($htmlcontent);
            // extract <img>
            $tags = $ddoc->getElementsByTagName('img');
            foreach ($tags as $tag) {
                $url = $tag->getAttribute('src');
                $list[] = url_to_absolute($info['url'], htmlspecialchars_decode($url));
            }
        }
    }
    return $list;
}


function externalvideo_get_video_stats($cm) {
    global $CFG, $USER, $DB;
    $stat_obj = new object();
    $stat_obj->seekto = 0;
    if ($row= $DB->get_record('externalvideo_stats', array('cmid'=>$cm->id, 'userid'=>$USER->id))) {
        $stats = json_decode($row->stats);
        if ($stats->origin == 'vimeo') {
             $stat_obj->seekto = $stats->seconds;
        }
    }
    return $stat_obj;
}

/*
 * @param object $cm
 * @param object $stats must have property such as 'repository' and 'seekto' and may have other property(optional)
 */
function externalvideo_save_video_stats($cm, $stats) {
    global $CFG, $USER, $DB;
    $params = array();
    $params['cmid'] = $cm->id;
    $params['userid'] = $USER->id;
    if ($DB->record_exists('externalvideo_stats', $params)) {
        $id = $DB->get_field('externalvideo_stats', 'id', $params);
        $todb = new object();
        $todb->id = $id;
        $todb->stats = json_encode($stats);
        $todb->timemodified = time();
        $DB->update_record('externalvideo_stats', $todb);
    } else {
        $todb = new object();
        $todb->course = $cm->course;
        $todb->cmid = $cm->id;
        $todb->userid = $USER->id;
        $todb->stats = json_encode($stats);
        $todb->timemodified = time();
        $DB->insert_record('externalvideo_stats', $todb);
    }
    return true;
}

/*
 * @param array $courses an array of course ids.
 */
function externalvideo_get_externalvideo_last_viewed($courseids = array(), $cmids = array()) {
    global $CFG, $USER, $DB;
    $stat_obj = new object();
    $stat_obj->seekto = 0;
    
    if (empty($courseids) || !is_array($courseids) || count($courseids) == 0) {
        $courseids = $DB->get_fieldset_sql("SELECT DISTINCT(course) FROM {externalvideo_stats} WHERE userid =:userid", array('userid'=>$USER->id));
    } else {
        $courseids = array_unique($courseids);
    }
    
    $availablecmids = $params = array();
    foreach ($courseids as $courseid) {
        $modinfo = get_fast_modinfo($courseid, $USER->id);
        if (array_key_exists('externalvideo', $modinfo->instances)) {
            foreach ($modinfo->instances['externalvideo'] as $externalvideo) {
                if ($externalvideo->visible) {
                    $availablecmids[$externalvideo->id] = $externalvideo;
                }
            }
        }
    }
    if ($cmids) {
        $cmids = array_intersect($cmids, array_keys($availablecmids));
    }
    
    $cmidsql = null;
    
    if ($cmids) {
        list($cmidsql, $cmidparams)  = $DB->in_or_equal($cmids, SQL_PARAMS_NAMED, 'cmid');
        $cmidsql = " cmid $cmidsql";
        $params = array_merge($params, $cmidparams);
    }
    $sql = "SELECT s1.cmid FROM {externalvideo_stats} s1"
            . " WHERE s1.userid = :userid1 AND s1.timemodified = "
            . "(SELECT max(s2.timemodified) FROM {externalvideo_stats} s2 WHERE s2.userid = :userid2) $cmidsql limit 0,1";

    $params['userid1'] = $USER->id;
    $params['userid2'] = $USER->id;
    
    if ($cmid = $DB->get_field_sql($sql, $params)) {
       return $availablecmids[$cmid];
    }
    
    return false;
}
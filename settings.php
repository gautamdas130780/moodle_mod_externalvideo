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
 * Externalvideo module admin settings and defaults
 *
 * @package    mod_externalvideo
 * @author     Gautam Kumar Das<gautam.arg@gmail.com>
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once("$CFG->libdir/resourcelib.php");

    $displayoptions = resourcelib_get_displayoptions(array(RESOURCELIB_DISPLAY_FRAME,
                                                           RESOURCELIB_DISPLAY_OPEN,
                                                           RESOURCELIB_DISPLAY_NEW,
                                                           RESOURCELIB_DISPLAY_POPUP,
                                                           RESOURCELIB_DISPLAY_EMBED,
                                                          ));
    $defaultdisplayoptions = array(RESOURCELIB_DISPLAY_OPEN,
                                   RESOURCELIB_DISPLAY_POPUP,
                                   RESOURCELIB_DISPLAY_EMBED
                                  );

    //--- general settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_configtext('externalvideo/apiliburl',
        get_string('apiliburl', 'externalvideo'), get_string('configapiliburl', 'externalvideo'), 'https://player.vimeo.com/api/player.js', PARAM_URL));
    $settings->add(new admin_setting_configtext('externalvideo/framesize',
        get_string('framesize', 'externalvideo'), get_string('configframesize', 'externalvideo'), 130, PARAM_INT));
    $settings->add(new admin_setting_configpasswordunmask('externalvideo/secretphrase', get_string('password'),
        get_string('configsecretphrase', 'externalvideo'), ''));
    $settings->add(new admin_setting_configcheckbox('externalvideo/rolesinparams',
        get_string('rolesinparams', 'externalvideo'), get_string('configrolesinparams', 'externalvideo'), false));
    $settings->add(new admin_setting_configmultiselect('externalvideo/displayoptions',
        get_string('displayoptions', 'externalvideo'), get_string('configdisplayoptions', 'externalvideo'),
        $defaultdisplayoptions, $displayoptions));

    //--- modedit defaults -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('externalvideomodeditdefaults', get_string('modeditdefaults', 'admin'), get_string('condifmodeditdefaults', 'admin')));

    $settings->add(new admin_setting_configcheckbox('externalvideo/printintro',
        get_string('printintro', 'externalvideo'), get_string('printintroexplain', 'externalvideo'), 1));
    $settings->add(new admin_setting_configselect('externalvideo/display',
        get_string('displayselect', 'externalvideo'), get_string('displayselectexplain', 'externalvideo'), RESOURCELIB_DISPLAY_OPEN, $displayoptions));
    $settings->add(new admin_setting_configtext('externalvideo/popupwidth',
        get_string('popupwidth', 'externalvideo'), get_string('popupwidthexplain', 'externalvideo'), 620, PARAM_INT, 7));
    $settings->add(new admin_setting_configtext('externalvideo/popupheight',
        get_string('popupheight', 'externalvideo'), get_string('popupheightexplain', 'externalvideo'), 450, PARAM_INT, 7));
    
    $settings->add(new admin_setting_configtext('externalvideo/saveseektime',
        get_string('saveseektime', 'externalvideo'), get_string('saveseektime_help', 'externalvideo'), '10', PARAM_INT), 10);
    
}

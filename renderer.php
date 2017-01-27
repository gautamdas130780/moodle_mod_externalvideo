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
 * This file contains a renderer for the externalvideo class
 * @author    Gautam Kumar Das<gautam.arg@gmail.com>
 * @package   mod_externalvideo
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/externalvideo/locallib.php');

/**
 * A custom renderer class that extends the plugin_renderer_base and is used by the assign module.
 *
 * @package mod_assign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_externalvideo_renderer extends core_media_renderer {
    
    private $player_lists = null;
    
    public function __construct() {
        global $CFG;
        require_once($CFG->libdir . '/medialib.php');
    }
    
    protected function get_players_list() {
        global $CFG;

        // Save time by only building the list once.
        if (!$this->player_lists) {
            // Get raw list of players.
            $players = $this->get_players_raw();

            // Chuck all the ones that are disabled.
            foreach ($players as $key => $player) {
                if (!$player->is_enabled()) {
                    unset($players[$key]);
                }
            }
            $this->player_lists = $players;
        }
        return $this->player_lists;
    }
    
    public function get_media_origin($url, $options = array()) {
        $players = $this->get_players_list();   
        $origin = false;
        foreach ($players as $playername=>$player) {
            $supported = $player->list_supported_urls(array($url), $options);
            if ($supported) {                
                if ($playername == 'vimeo') {
                    $origin = $playername;
                    break;
                }                
            }
        }
        return $origin;
    }
    
    public function embed_url(moodle_url $url, $name = '', $width = 0, $height = 0, $options = array()) {
        $code = '';
        $media_origin = $this->get_media_origin($url);
        if ($media_origin == 'vimeo') {            
            $externalvideo = $options['externalvideo_objects']->instance;
            $cm = $options['externalvideo_objects']->cm;
            $course = $options['externalvideo_objects']->course;
            $code = $this->get_embed_code($externalvideo, $cm, $course);
            if ($code == NULL) {                
                $code = parent::embed_url($url, $name, $width, $height, $options);
            }
        } else {
            unset($options['externalvideo_objects']);
            $code = parent::embed_url($url, $name, $width, $height, $options);
        }
        return $code;
    }
    
    public function get_embed_code($externalvideo, $cm, $course) {
        global $CFG, $DB, $PAGE;
        
        static $playerjscount = 0;
        static $lastviewed = null;
        $config = get_config('externalvideo');
        
        if (empty($config->apiliburl)) {
            return NULL;
        }

        if (!externalvideo_appears_valid_url($config->apiliburl)) {
            debugging('API Library url is not valid', DEBUG_DEVELOPER);
            return NULL;
        }
        if ($lastviewed == null) {
            $lastviewed = externalvideo_get_externalvideo_last_viewed(array($course->id));            
        }
        
        $currentclass = ($lastviewed && $lastviewed->id == $cm->id) ? ' currentvideo' : null;
                
        $jscode = '';
        if ($playerjscount == 0) {            
            $jscode .= '<script>'.file_get_contents($config->apiliburl).'</script>';        
            $playerjscount++;

            $options = new object();
            $options->url = $CFG->wwwroot.'/mod/externalvideo/ajax.php';
            $options->timecheckinterval = $config->saveseektime;
            $jsmodule = array(
                'name' => 'mod_externalvideo',
                'fullpath' => '/mod/externalvideo/module.js',
                'requires' => array('json'),
            );
            $PAGE->requires->js_init_call('M.mod_externalvideo.player.init', array($options), true, $jsmodule);
        }
        
        $displayoptions = empty($externalvideo->displayoptions) ? array() : unserialize($externalvideo->displayoptions);
        $stats = externalvideo_get_video_stats($cm);
        $seekto = ($stats->seekto) ? $stats->seekto :0;        
        $code = '';        
        $code .= $jscode;        
        $code .= '<div data-vimeo-id="'.$externalvideo->externalurl.'" class="player_externalvideo '.$currentclass.'" data-seekto="'.$seekto.'" data-vimeo-defer id="vimeo_externalvideo_'.$cm->id.'"></div>';
        
        return $code;
    }
}


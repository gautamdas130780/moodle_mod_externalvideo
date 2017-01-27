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
 * Search area for mod_externalvideo activities.
 *
 * @package    mod_externalvideo
 * @author     Gautam Kumar Das<gautam.arg@gmail.com>
 */

namespace mod_externalvideo\search;

defined('MOODLE_INTERNAL') || die();

/**
 * Search area for mod_externalvideo activities.
 *
 * @package    mod_externalvideo
 * @author     Gautam Kumar Das<gautam.arg@gmail.com>
 */
class activity extends \core_search\area\base_activity {

    /**
     * Returns the document associated with this activity.
     *
     * Overwrites base_activity to add the provided URL as description.
     *
     * @param stdClass $record
     * @param array    $options
     * @return \core_search\document
     */
    public function get_document($record, $options = array()) {
        $doc = parent::get_document($record, $options);
        if (!$doc) {
            return false;
        }

        $doc->set('description1', $record->externalurl);
        return $doc;
    }
}

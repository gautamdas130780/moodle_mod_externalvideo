This file is part of Moodle - http://moodle.org/

Moodle is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Moodle is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

Author: Gautam Kumar Das<gautam.arg@gmail.com>


Externalvideo module
=============

Externalvideo module is one of the successors to original 'url' type plugin of Resource module.

Currently this plugin works with vimeo urls

Installation
=============

Before installing these plugins firstly make sure you are logged in as an Administrator and that you are using Moodle 3.0 or higher.

To install, all you need to do is copy all the files into the mod/externalvideo directory on your moodle installation. You should then go to "Site Administration" > "Notifications" and follow the on screen instructions.

To configure the plugin go to "Site Administration" > "Plugins" > "Activity Modules" > "Externalvideo" and enter your Turnitin account Id, shared key and API URL.
* Visit Settings > Site Administration > Notifications, you should find
  the module's tables successfully created

* Go to Site Administration > Plugins > Activity modules > Manage activities
  and you should find that this Externalvideo has been added to the list of
  installed modules.
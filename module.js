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
 * Javascript helper function for Externalvideo module.
 * @author     Gautam Kumar Das<gautam.arg@gmail.com>
 * @package   mod-externalvideo
 */

M.mod_externalvideo = {};
M.mod_externalvideo.player = { 
    
    init: function(Y, options) {
        var PlayerHelper = function (args) {
            PlayerHelper.superclass.constructor.apply(this, arguments);
        }
        Y.extend(PlayerHelper, Y.Base, {
            initializer: function(args) {
                this.ajaxurl = args.url;
                this.timecheckinterval = args.timecheckinterval;  
                this.players = new Array();
                var scope = this;
                Y.all('.player_externalvideo').each(function(e) {
                    var pid = e.get('id');
                    var p_arr = pid.split('_');
                    var player = p_arr[0];
                    if (player == 'vimeo') {
                        var playeroptions = {
                            width: parseInt(Y.one('#' + pid).getStyle('width')),                        
                        }            
                        var seekto = Y.one('#' + pid).getAttribute('data-seekto'); 
                        seekto = parseInt(seekto);
                        scope.players[pid] = new window.Vimeo.Player(pid, playeroptions); 
                        scope.players[pid].setCurrentTime(seekto).then(function(seconds) {
                            scope.players[pid].pause().then(function() {
                                if (Y.one('#' + pid).hasClass('currentvideo')) {
                                    scope.players[pid].play();
                                }
                            });
                        });
                        scope.players[pid].on("timeupdate", (data)=> scope.save_last_viewed(data, pid));
                        scope.players[pid].on('pause', (data)=> scope.update_last_viewed(data, pid));
                        scope.players[pid].on('play', (data)=> scope.update_last_viewed(data, pid));
                        scope.players[pid].on('seeked', (data)=> scope.update_last_viewed(data, pid));
                        
                    }
                });
            },
            request: function(pid, seekto, stats) {
                var scope = this;
                Y.one('#' + pid).setAttribute('data-seekto', seekto);  
                var params = 'resource=' + pid + '&stats=' + JSON.stringify(stats);;
                var cfg = {
                    method: 'POST',
                    on: {
                        complete : function(tid, outcome, args) {
                                var data = Y.JSON.parse(outcome.responseText);
                                if (data.status == 'OK'){
                                    // Do something
                                }
                        }
                    },
                    headers: {
                    },
                    data: params
                };
                Y.io(scope.ajaxurl, cfg);
            },
            update_last_viewed: function(data, pid) {                
                var scope = this;
                Y.one('#' + pid).setAttribute('data-seekto', data.seconds);  
                scope.request(pid, data.seconds, data);
            },
            save_last_viewed: function(stats, pid) {
                var scope = this;
                var seekto = Y.one('#' + pid).getAttribute('data-seekto');  
                seekto = parseInt(seekto) + parseInt(scope.timecheckinterval);
                if (stats.seconds > seekto) {    
                    scope.request(pid, stats.seconds, stats);
                }                
            }
        });
        new PlayerHelper(options);
    },
};

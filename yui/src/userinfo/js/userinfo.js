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
 * User info functionality for a popup in workflow block.
 *
 * @package   block_workflow
 * @copyright 2014 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

var CSS = {
    USERINFO: '#userinfo',
    USERINFOCLASS: '.userinfoclass'
};

var PARAMS = {
    id: 'id',
    HEADER: 'header',
    BODY: 'body',
    STEPNO: 'stepno'
};

var POPUP = function() {
    POPUP.superclass.constructor.apply(this, arguments);
};

Y.extend(POPUP, Y.Base, {

    initializer : function() {
        Y.all(CSS.USERINFOCLASS).each(function(node) {
            var stepno = node.getAttribute(PARAMS.STEPNO);
            var header = node.getAttribute(PARAMS.HEADER);
            var body = node.getAttribute(PARAMS.BODY);
            node.on('click', this.display_dialog, this, stepno, header, body);
        }, this);
    },

    display_dialog : function (e, stepno, header, body) {
        e.preventDefault();

        // Configure the popup.
        var config = {
            headerContent : header,
            bodyContent : body,
            draggable : true,
            modal : true,
            zIndex : 1000,
            centered: false,
            width: 'auto',
            visible: false,
            postmethod: 'form',
            footerContent: null
        };

        var popup = { dialog: null };
        popup.dialog = new M.core.dialogue(config);
        popup.dialog.show();
    }
});

M.block_workflow = M.block_workflow || {};
M.block_workflow.userinfo = M.block_workflow.userinfo || {};
M.block_workflow.userinfo.init = function() {
    return new POPUP();
};
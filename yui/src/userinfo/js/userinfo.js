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
    BODY: 'body'
};

var POPUP = function() {
    POPUP.superclass.constructor.apply(this, arguments);
};

Y.extend(POPUP, Y.Base, {
    userinfobutton: Y.one(CSS.USERINFO),

    header: null,
    body: null,

    initializer : function() {
        userinfoclass = Y.one(CSS.USERINFOCLASS);
        var node = userinfoclass._node;

        // Set popup header and body.
        this.header = node.getAttribute(PARAMS.HEADER);
        this.body = node.getAttribute(PARAMS.BODY);

        this.userinfobutton.on('click', this.display_dialog, this);
    },

    display_dialog : function (e) {
        e.preventDefault();

        // Configure the popup.
        var config = {
            headerContent : this.header,
            bodyContent : this.body,
            draggable : true,
            modal : true,
            zIndex : 1000,
            //context: [CSS.REPAGINATECOMMAND, 'tr', 'br', ['beforeShow']],
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
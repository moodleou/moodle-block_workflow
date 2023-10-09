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

import Notification from 'core/notification';

/**
 * JavaScript for the workflow to-do list.
 *
 * @module block_workflow/todolist
 * @copyright 2023 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
export class TodoList {
    TODOLISTNAME = 'blocks_workflow_todolist';
    AJAXURL = '/blocks/workflow/ajax.php';
    STATEID = 'stateid';
    CSS = {
        BLOCKTODOTASK: 'block-workflow-todotask',
        BLOCKTODOID: 'block-workflow-todoid'
    };

    constructor(options) {
        this.stateid = options.stateid;
    }

    /**
     * Initial function.
     */
    initializer() {
        // Take each of the workflow tasks, remove the anchor, and change it to
        // call our update function
        document.querySelectorAll('a.' + this.CSS.BLOCKTODOTASK).forEach(node => {
            node.closest('li').onclick = event => this.toggle(event, node);
            node.setAttribute('href', '#');
        });
    }

    /**
     * Toggle checkbox.
     *
     * @param {Object} event The event object.
     * @param {Object} node HTML node object.
     */
    toggle(event, node) {
        event.preventDefault();
        // Expression to fetch ID
        const reg = new RegExp(this.CSS.BLOCKTODOID + "-(\\d{1,})");
        // Build the data for submission
        const data = {
            sesskey: M.cfg.sesskey,
            action: 'toggletaskdone',
            stateid: this.stateid,
            todoid: node.getAttribute('id').match(reg)[1]
        };

        const xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (xhttp.readyState !== XMLHttpRequest.DONE) {
                return;
            }
            let result;
            if (xhttp.status === 200) {
                result = JSON.parse(this.responseText);
                if (result.error) {
                    Notification.exception(result);
                    return;
                }
            } else {
                Notification.exception(new Error(xhttp.statusText));
                return;
            }

            if (result.response.iscompleted) {
                node.parentNode.classList.add('completed');
            } else {
                node.parentNode.classList.remove('completed');
            }
        };
        xhttp.open("POST", M.cfg.wwwroot + this.AJAXURL);
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhttp.send(build_querystring(data)); // eslint-disable-line no-undef
    }
}

/**
 * Handle to-do list action.
 *
 * @param {Object} options The settings.
 */
export const initTodolist = (options) => {
    const todo = new TodoList(options);
    todo.initializer();
};

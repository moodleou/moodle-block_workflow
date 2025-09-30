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
import {call as fetchMany} from 'core/ajax';

/**
 * JavaScript for the workflow to-do list.
 *
 * @module block_workflow/todolist
 * @copyright 2023 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
export class TodoList {
    TODOLISTNAME = 'blocks_workflow_todolist';
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
        // We don't have a real checkbox, it is just image with different class. So we will check the status base on class.
        // When we click to the link with completed class, meaning we want to uncheck so the status should be false.
        // When we click to the link without the completed class, meaning we want to check so the status should be true.
        const check = !node.parentNode.classList.contains('completed');
        this.updateStepStateTaskState(this.stateid, node.getAttribute('id').match(reg)[1], check)
        .then(function(result) {
            if (result.error) {
                Notification.exception(result.error);
                return;
            }
            if (result.response) {
                node.parentNode.classList.add('completed');
            } else {
                node.parentNode.classList.remove('completed');
            }
        }).catch(Notification.exception);
    }

    /**
     * Update step_state to to
     *
     * @param {Number} stateid id of the current step_state
     * @param {Number} todoid id of the current to do.
     * @param {Boolean} check whether the current to is has been checked/uncheck.
     */
    updateStepStateTaskState(stateid, todoid, check) {
        return fetchMany([{
            methodname: 'block_workflow_update_step_state_task_state',
            args: {
                stateid: stateid,
                todoid: todoid,
                check: check
            },
        }])[0];
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

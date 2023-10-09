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

import ModalFactory from 'core/modal_factory';
import * as Str from 'core/str';
import Notification from 'core/notification';
import {TodoList} from 'block_workflow/todolist';
import Fragment from 'core/fragment';
import Templates from 'core/templates';

/**
 * JavaScript to handle comment.
 *
 * @module block_workflow/comments
 * @copyright 2023 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class Comment {

    AJAXURL = '/blocks/workflow/ajax.php';
    CSS = {
        BLOCKWORKFLOW:  'block_workflow',
        BLOCKCOMMENTS:  'block_workflow_comments',
        BLOCKCOMMBTN:   'block_workflow_editcommentbutton',
        BLOCKFINISHBTN: 'block_workflow_finishstepbutton',
        PANEL:          'block-workflow-panel',
        CONTENT:        'content',
        LIGHTBOX:       'loading-lightbox',
        SUBMIT:         'wfk-submit',
    };

    constructor(options) {
        this.editorid = options.editorid;
        this.editorname = options.editorname;
        this.stateid = options.stateid;
        this.contextid = options.contextid;
    }

    /**
     * Method that creates the comment modal.
     *
     * @returns {Promise} The modal promise (modal's body will be rendered later).
     */
    async buildCommentModal() {
        return ModalFactory.create({type: ModalFactory.types.DEFAULT});
    }

    /**
     * Initial function.
     */
    async initializer() {
        // Setup and attach the modal to DOM.
        this.modal = await this.buildCommentModal();
        this.loadingNode = document.querySelector(`.${this.CSS.PANEL} .${this.CSS.LIGHTBOX}`);
        this.modal.attachToDOM();
        this.modal.hide();
        this.attachEvents();
    }

    /**
     * Attach events to buttons.
     */
    attachEvents() {
        // Attach event for comment button.
        const commentButton = document.querySelector('.' + this.CSS.BLOCKCOMMBTN + ' button, .' +
            this.CSS.BLOCKCOMMBTN + ' input[type=submit]');
        if (commentButton) {
            commentButton.onclick = event => this.show(event, false);
        }
        // Attach event for finish step button.
        const finishButton = document.querySelector('.' + this.CSS.BLOCKFINISHBTN + ' button, .' +
            this.CSS.BLOCKFINISHBTN + ' input[type=submit]');
        if (finishButton) {
            finishButton.onclick = event => this.show(event, true);
        }
    }

    /**
     * Show comment action.
     *
     * @param {Object} event Event object.
     * @param {Boolean} finishStep Finish step flag.
     */
    async show(event, finishStep) {
        event.preventDefault();
        const [editCommentString, saveChangesString, finishString] = await Str.get_strings([
            {key: 'editcomments', component: 'block_workflow'},
            {key: 'savechanges', component: 'moodle'},
            {key: 'finishstep', component: 'block_workflow'},
        ]);

        let inputLabel;

        if (finishStep) {
            this.modal.setTitle(finishString);
            inputLabel = finishString;
        } else {
            inputLabel = saveChangesString;
            this.modal.setTitle(editCommentString);
        }

        const fragment = Fragment.loadFragment('block_workflow', 'commentform', this.contextid, {inputLabel});
        fragment.done(function(html, js) {
            this.modal.getBody()[0].innerHTML = html;
            Templates.runTemplateJS(js);
            const body = this.modal.getBody()[0];
            this.displayLoading();
            this.modal.getRoot()[0].querySelector('.modal-dialog').style.cssText = 'width: fit-content; max-width: 1280px;';
            this.modal.getRoot()[0].querySelector('.modal-dialog').style.width = 'fit-content';
            this.modal.show();
            body.querySelector(`.${this.CSS.PANEL} .col-md-3`).classList.add('hidden', 'd-none');
            const submitButton = body.querySelector(`button[name="${this.CSS.SUBMIT}"]`);
            // Change position of the button.
            submitButton.classList.add('ml-auto', 'mr-0');
            if (finishStep) {
                submitButton.onclick = this.finishStep.bind(this);
            } else {
                submitButton.onclick = this.save.bind(this);
            }

            const data = {
                sesskey: M.cfg.sesskey,
                action:  'getcomment',
                stateid: this.stateid
            };

            // Fetch the comment and update the form
            this.ajaxCall(data, false, result => {
                const editorId = this.editorid;
                const editor = document.getElementById(editorId + 'editable');
                // Atto specific.
                if (editor) {
                    editor.innerHTML = result.response.comment;
                }
                // Tiny specific.
                // To make sure Tiny MCE has already loaded.
                setTimeout(function() {
                    if (window.tinyMCE && window.tinyMCE.activeEditor) {
                        window.tinyMCE.activeEditor.setContent(result.response.comment);
                        window.tinyMCE.activeEditor.save();
                    }
                });
                document.getElementById(editorId).value = result.response.comment;
            });
        }.bind(this));
    }

    /**
     * Handle finish step.
     */
    finishStep() {
        const comment = document.getElementById(this.editorid).value;
        const worklowBlock = document.querySelector('.' + this.CSS.BLOCKWORKFLOW + ' .' + this.CSS.CONTENT);
        // Build the data for submission
        const data = {
            sesskey: M.cfg.sesskey,
            action:  'finishstep',
            stateid: this.stateid,
            text: comment,
            format: document.getElementsByName(this.editorname + '[format]')[0].value
        };

        this.ajaxCall(data, true, result => {
            if (result.response.blockcontent) {
                // Update content
                worklowBlock.innerHTML = result.response.blockcontent;
                if (result.response.stateid) {
                    // We are on the next step
                    this.stateid = result.response.stateid;
                    // Re-attach events to block buttons
                    this.attachEvents();
                    // Reinit to-do events
                    const todo = new TodoList({stateid: result.response.stateid});
                    todo.initializer();
                }
                if (result.response.listworkflows) {
                    // Last step, available workflows are listed
                    const selectId = worklowBlock.querySelector('.singleselect form select').getAttribute('id');
                    // Reinit single_select event
                    // This is horrible, but the core JS we need is now inline in the template,
                    // so we have to copy it.
                    document.getElementById(selectId).onchange = function() {
                        if (this.selectedOptions[0].dataset.ignore === undefined) {
                            this.closest('form').submit();
                        }
                    };
                }
            }
        });
    }

    /**
     * Handle save action.
     */
    async save() {
        const comment = document.getElementById(this.editorid).value;
        const commentsBlock = document.querySelector('.' + this.CSS.BLOCKWORKFLOW + ' .' + this.CSS.BLOCKCOMMENTS);
        const noCommentString = await Str.get_string('nocomments', 'block_workflow');
        // Build the data for submission
        const data = {
            sesskey: M.cfg.sesskey,
            action:  'savecomment',
            stateid: this.stateid,
            text: comment,
            format: document.getElementsByName(this.editorname + '[format]')[0].value
        };

        this.ajaxCall(data, true, function(result) {
            if (result.response.blockcomments) {
                commentsBlock.innerHTML = result.response.blockcomments;
            } else {
                commentsBlock.innerText = noCommentString;
            }
        });
    }

    /**
     * Display loading animation.
     */
    displayLoading() {
        let body = this.modal.getBody()[0].querySelector('.' + this.CSS.PANEL);
        if (!body) {
            body = this.modal.getBody()[0];
        }
        body.appendChild(this.loadingNode);
    }

    /**
     * Hide loading animation.
     */
    removeLoading() {
        const loadingEl = this.modal.getBody()[0].querySelector(`.${this.CSS.LIGHTBOX}`);
        if (!loadingEl) {
            return;
        }
        loadingEl.remove();
    }

    /**
     * Ajax call function.
     *
     * @param {Object} data The data value of the request.
     * @param {Boolean} hideModal Hide modal after request.
     * @param {Function} successCallback The callback function that will be triggered when the request is successful.
     */
    ajaxCall(data, hideModal, successCallback) {
        const xhttp = new XMLHttpRequest();
        xhttp.onloadstart = this.displayLoading.bind(this);
        xhttp.onreadystatechange = () => {
            if (xhttp.readyState !== XMLHttpRequest.DONE) {
                return;
            } else {
                this.removeLoading();
            }
            if (xhttp.status !== 200) {
                Notification.exception(new Error(xhttp.statusText));
                return;
            }
            const result = JSON.parse(xhttp.responseText);
            if (result.error) {
                Notification.exception(result);
                return;
            }

            // Trigger call back.
            successCallback(result);

            if (hideModal) {
                this.modal.hide();
            }
        };
        xhttp.open("POST", M.cfg.wwwroot + this.AJAXURL);
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhttp.send(build_querystring(data)); // eslint-disable-line no-undef
    }
}

/**
 * Handle action with comments.
 *
 * @param {Object} options The comment settings.
 */
export const initComments = (options) => {
    const comment = new Comment(options);
    comment.initializer();
};

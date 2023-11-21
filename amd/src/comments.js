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
import {call as fetchMany} from 'core/ajax';
import Pending from 'core/pending';
import {addIconToContainerRemoveOnCompletion} from 'core/loadingicon';

/**
 * JavaScript to handle comment.
 *
 * @module block_workflow/comments
 * @copyright 2023 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class Comment {

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
        const pendingModalReady = new Pending('block_workflow/actions:show');
        fragment.then(function(html, js) {
            this.modal.getBody()[0].innerHTML = html;
            Templates.runTemplateJS(js);
            const body = this.modal.getBody()[0];
            addIconToContainerRemoveOnCompletion(
                this.modal.getBody()[0].querySelector('.' + this.CSS.PANEL), pendingModalReady
            );
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
            const editorId = this.editorid;
            // Fetch the comment and update the form
            this.getStepStateComment(this.stateid).then(function(result) {
                const editor = document.getElementById(editorId + 'editable');
                // Atto specific.
                if (editor) {
                    editor.innerHTML = result.response;
                }
                // Tiny specific.
                // To make sure Tiny MCE has already loaded.
                setTimeout(function() {
                    if (window.tinyMCE && window.tinyMCE.activeEditor) {
                        window.tinyMCE.activeEditor.setContent(result.response);
                        window.tinyMCE.activeEditor.save();
                    }
                });
                document.getElementById(editorId).value = result.response;
                pendingModalReady.resolve();
            }).catch(Notification.exception);
        }.bind(this));
    }

    /**
     * Get the current comment of the step_state.
     *
     * @param {Number} stateid
     */
    getStepStateComment(stateid) {
        return fetchMany([{
            methodname: 'block_workflow_get_step_state_comment',
            args: {
                stateid: stateid,
            },
        }])[0];
    }

    /**
     * Handle finish step.
     */
    finishStep() {
        const comment = document.getElementById(this.editorid).value;
        const worklowBlock = document.querySelector('.' + this.CSS.BLOCKWORKFLOW + ' .' + this.CSS.CONTENT);
        const modal = this.modal;
        const commentCls = this;
        this.updateFinishStep(this.stateid, comment, document.getElementsByName(this.editorname + '[format]')[0].value)
            .then(function(result) {
                if (result.response) {
                    // Update content
                    worklowBlock.innerHTML = result.response;
                    if (result.stateid) {
                        // Re-attach events to block buttons
                        commentCls.attachEvents();
                        // Reinit to-do events
                        const todo = new TodoList({stateid: result.stateid});
                        commentCls.stateid = result.stateid;
                        // We are on the next step
                        todo.initializer();
                    }
                    if (result.listworkflows) {
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
                modal.hide();
            }).catch(Notification.exception);
    }

    /**
     * Finish the current step_state.
     *
     * @param {Number} stateid stateid id of the current step_state.
     * @param {String} comment new comment of the finish step.
     * @param {Number} format format of the editor.
     */
    updateFinishStep(stateid, comment, format) {
        return fetchMany([{
            methodname: 'block_workflow_finish_step',
            args: {
                stateid: this.stateid,
                text: comment,
                format: format
            },
        }])[0];
    }

    /**
     * Handle save action.
     */
    async save() {
        const comment = document.getElementById(this.editorid).value;
        const commentsBlock = document.querySelector('.' + this.CSS.BLOCKWORKFLOW + ' .' + this.CSS.BLOCKCOMMENTS);
        const noCommentString = await Str.get_string('nocomments', 'block_workflow');
        const modal = this.modal;
        this.updateStepStateComment(this.stateid, comment, document.getElementsByName(this.editorname + '[format]')[0].value)
            .then(function(result) {
                if (result.response) {
                    commentsBlock.innerHTML = result.response;
                } else {
                    commentsBlock.innerText = noCommentString;
                }
                modal.hide();
            }).catch(Notification.exception);
    }

    /**
     * Update the comment of the step_state.
     *
     * @param {Number} stateid id of the current step_state.
     * @param {String} comment new comment of
     * @param {Number} format format of the editor.
     */
    updateStepStateComment(stateid, comment, format) {
        return fetchMany([{
            methodname: 'block_workflow_update_step_state_comment',
            args: {
                stateid: this.stateid,
                newcomment: comment,
                newcommentformat: format
            },
        }])[0];
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

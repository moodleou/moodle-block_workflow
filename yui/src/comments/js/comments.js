var COMMENTSNAME = 'blocks_workflow_comments',
    AJAXURL = '/blocks/workflow/ajax.php',
    STATEID = 'stateid',
    EDITORID = 'editorid',
    EDITORNAME = 'editorname',
    CSS = {
        BLOCKWORKFLOW:  'block_workflow',
        BLOCKCOMMENTS:  'block_workflow_comments',
        BLOCKCOMMBTN:   'block_workflow_editcommentbutton',
        BLOCKFINISHBTN: 'block_workflow_finishstepbutton',
        PANEL:          'block-workflow-panel',
        CONTENT:        'content',
        COMMENTS:       'wkf-comments',
        LIGHTBOX:       'loading-lightbox',
        LOADINGICON:    'loading-icon',
        TEXTAREA:       'wfk-textarea',
        SUBMIT:         'wfk-submit',
        HIDDEN:         'hidden'
    };

var overlay = new M.core.dialogue({
    headerContent: '',
    bodyContent:   Y.one('.' + CSS.PANEL),
    visible:       false,
    modal:         true,
    width:         'auto',
    zIndex:        100
});

var COMMENTS = function() {
    COMMENTS.superclass.constructor.apply(this, arguments);
};

Y.extend(COMMENTS, Y.Base, {
    _formSubmitEvent:  null,
    _escCloseEvent:    null,
    _closeButtonEvent: null,
    _loadingNode:      null,

    initializer: function() {
        overlay.hide();
        this._loadingNode = Y.one('.' + CSS.PANEL).one('.' + CSS.LIGHTBOX);
        this.attachEvents();
    },

    show: function (e, finishstep) {
        e.halt();

        // Different scenario depending on whether we finishing the step or just editiong the comment
        if (finishstep) {
            overlay.set('headerContent', M.str.block_workflow.finishstep);
            Y.one('.' + CSS.PANEL).one('.' + CSS.SUBMIT + ' input').set('value', M.str.block_workflow.finishstep);
            this._formSubmitEvent = Y.one('.' + CSS.SUBMIT + ' input').on('click', this.finishstep, this);
        } else {
            overlay.set('headerContent', M.str.block_workflow.editcomments);
            Y.one('.' + CSS.PANEL).one('.' + CSS.SUBMIT + ' input').set('value', M.str.moodle.savechanges);
            this._formSubmitEvent = Y.one('.' + CSS.SUBMIT + ' input').on('click', this.save, this);
        }

        overlay.show(); //show the overlay
        // We add a new event on the body in order to hide the overlay for the next click
        this._escCloseEvent = Y.on('key', this.hide, document.body, 'down:27', this);

        // Remove the existing handler for the closebutton
        Y.Event.purgeElement(Y.one('.moodle-dialogue-hd .closebutton'), true);
        // Add a new event for close button.
        this._closeButtonEvent = Y.on('click', this.hide, Y.one('.moodle-dialogue-hd .closebutton'), this);

        // Build the data for submission
        var data = {
            sesskey: M.cfg.sesskey,
            action:  'getcomment',
            stateid: this.get(STATEID)
        };

        if (typeof tinyMCE !== 'undefined') {
            var ed = tinyMCE.get(this.get(EDITORID));

            // Resize then editor when first shown if it would otherwise be too small.
            var ifr = tinymce.DOM.get(this.get(EDITORID) + '_ifr');
            var size = tinymce.DOM.getSize(ifr);
            if (size.h === 30) {
                ed.theme.resizeTo(size.w, 90);
            }
        }

        // Fetch the comment and update the form
        Y.io(M.cfg.wwwroot + AJAXURL, {
            method:'POST',
            data:build_querystring(data),
            on: {
                start: this.displayLoading,
                complete: function(tid, outcome) {
                    var result;
                    try {
                        result = Y.JSON.parse(outcome.responseText);
                        if (result.error) {
                            return new M.core.ajaxException(result);
                        }
                    } catch (e) {
                        new M.core.exception(e);
                    }
                    if (typeof tinyMCE !== 'undefined') {
                        ed.setContent(result.response.comment);
                    } else {
                        var editorid = this.get(EDITORID);
                        var editor = Y.one(document.getElementById(editorid + 'editable'));
                        if (editor) {
                            editor.setHTML(result.response.comment);
                        }
                        Y.one(document.getElementById(editorid)).set(
                                'value', result.response.comment);
                    }
                },
                end: this.removeLoading
            },
            context:this
        });
    },

    hide: function () {
        overlay.hide(); //hide the overlay
        if (this._escCloseEvent) {
            this._escCloseEvent.detach();
            this._escCloseEvent = null;
        }
        if (this._closeButtonEvent) {
            this._closeButtonEvent.detach();
            this._closeButtonEvent = null;
        }
        if (this._formSubmitEvent) {
            this._formSubmitEvent.detach();
            this._formSubmitEvent = null;
        }
    },
    save: function () {
        var comment;
        if (typeof tinyMCE !== 'undefined') {
            comment = tinyMCE.get(this.get(EDITORID)).getContent();
        } else {
            comment = Y.one(document.getElementById(this.get(EDITORID))).get('value');
        }

        var commentsblock = Y.one('.' + CSS.BLOCKWORKFLOW + ' .' + CSS.BLOCKCOMMENTS);
        // Build the data for submission
        var data = {
            sesskey: M.cfg.sesskey,
            action:  'savecomment',
            stateid: this.get(STATEID),
            text:    comment,
            format:  document.getElementsByName(this.get(EDITORNAME) + '[format]')[0].value
        };

        Y.io(M.cfg.wwwroot + AJAXURL, {
            method:'POST',
            data:build_querystring(data),
            on: {
                start: this.displayLoading,
                complete: function(tid, outcome) {
                    var result;
                    try {
                        result = Y.JSON.parse(outcome.responseText);
                        if (result.error) {
                            return new M.core.ajaxException(result);
                        }
                    } catch (e) {
                        new M.core.exception(e);
                    }
                    if (result.response.blockcomments) {
                        commentsblock.setContent(result.response.blockcomments);
                    } else {
                        commentsblock.setContent(M.str.block_workflow.nocomments);
                    }
                },
                end: this.removeLoading
            },
            context:this
        });
        this.hide();
    },
    finishstep: function () {
        var comment;
        if (typeof tinyMCE !== 'undefined') {
            comment = tinyMCE.get(this.get(EDITORID)).getContent();
        } else {
            comment = Y.one(document.getElementById(this.get(EDITORID))).get('value');
        }

        var workflowblock = Y.one('.' + CSS.BLOCKWORKFLOW + ' .' + CSS.CONTENT);
        // Build the data for submission
        var data = {
            sesskey: M.cfg.sesskey,
            action:  'finishstep',
            stateid: this.get(STATEID),
            text:    comment,
            format:  document.getElementsByName(this.get(EDITORNAME) + '[format]')[0].value
        };

        Y.io(M.cfg.wwwroot + AJAXURL, {
            method:'POST',
            data:build_querystring(data),
            on: {
                start: this.displayLoading,
                complete: function(tid, outcome) {
                    var result;
                    try {
                        result = Y.JSON.parse(outcome.responseText);
                        if (result.error) {
                            return new M.core.ajaxException(result);
                        }
                    } catch (e) {
                        new M.core.exception(e);
                    }
                    if (result.response.blockcontent) {
                        // Update content
                        workflowblock.setContent(result.response.blockcontent);
                        if (result.response.stateid) {
                            // we are on the next step
                            this.set(STATEID, result.response.stateid);
                            // re-attach events to block buttons
                            this.attachEvents();
                            // reinit todo events
                            M.blocks_workflow.init_todolist({"stateid":result.response.stateid});
                        }
                        if (result.response.listworkflows) {
                            // Last step, avialable workflows are listed
                            var select_id = workflowblock.one('.singleselect form select').getAttribute('id');
                            // Reinit single_select event
                            M.core.init_formautosubmit({selectid: select_id, nothing: ''});
                        }
                    }
                },
                end: this.removeLoading
            },
            context:this
        });
        this.hide();
    },
    displayLoading: function() {
        this._loadingNode.removeClass(CSS.HIDDEN);
    },
    removeLoading: function() {
        this._loadingNode.addClass(CSS.HIDDEN);
    },
    attachEvents: function() {
        var commentbutton = Y.one('.' + CSS.BLOCKCOMMBTN + ' input');
        if (commentbutton) {
            commentbutton.on('click', this.show, this, false);
        }
        var finishbutton = Y.one('.' + CSS.BLOCKFINISHBTN + ' input');
        if (finishbutton) {
            finishbutton.on('click', this.show, this, true);
        }
    }

}, {
    NAME: COMMENTSNAME,
    ATTRS: {
        stateid: {
            value: null
        },
        editorid: {
            value: null
        },
        editorname: {
            validator: Y.Lang.isString,
            value: null
        }

    }
});

M.blocks_workflow = M.blocks_workflow || {};
M.blocks_workflow.init_comments = function(params) {
    return new COMMENTS(params);
};

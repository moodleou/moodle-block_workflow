YUI.add('moodle-block_workflow-comments', function(Y) {

    var COMMENTSNAME = 'blocks_workflow_comments',
        BASE = 'commentsBase',
        AJAXURL = '/blocks/workflow/ajax.php',
        STATEID = 'stateid',
        EDITORHTML = 'editorhtml',
        EDITORID = 'editorid',
        EDITORNAME = 'editorname',
        CSS = {
            BLOCKWORKFLOW : 'block_workflow',
            BLOCKCOMMENTS : 'block_workflow_comments',
            BLOCKCOMMBTN : 'block_workflow_editcommentbutton',
            BLOCKFINISHBTN : 'block_workflow_finishstepbutton',
            PANEL : 'block-workflow-panel',
            CONTENT : 'content',
            COMMENTS : 'wkf-comments',
            LIGHTBOX : 'loading-lightbox',
            LOADINGICON : 'loading-icon',
            TEXTAREA : 'wfk-textarea',
            SUBMIT : 'wfk-submit',
            HIDDEN : 'hidden'
        };

    var overlay = new M.core.dialogue({
        visible : false, //by default it is not displayed
        lightbox : true,
        width : 'auto', //'default 431px'
        zIndex : 100
    });

    var COMMENTS = function() {
        COMMENTS.superclass.constructor.apply(this, arguments);
    };

    Y.extend(COMMENTS, Y.Base, {
        _formSubmitEvent :null,
        _escCloseEvent : null,
        _closeButtonEvent: null,
        _loadingNode : null,

        initializer : function(params) {
            overlay.hide();
            this.set(BASE, Y.Node.create('<div class="'+CSS.PANEL+'"></div>')
                        .append(Y.Node.create('<div class="'+CSS.COMMENTS+'"></div>')
                            .append(Y.Node.create('<div class="'+CSS.TEXTAREA+'"></div>')
                                .append(Y.Node.create(this.get(EDITORHTML))))
                            .append(Y.Node.create('<div class="'+CSS.SUBMIT+'"></div>')
                                .append(Y.Node.create('<input type="button" class="submitbutton"/>')))
                        )
                        .append(Y.Node.create('<div class="'+CSS.LIGHTBOX+' '+CSS.HIDDEN+'"></div>')
                            .append(Y.Node.create('<img alt="loading" class="'+CSS.LOADINGICON+'" />')
                                .setAttribute('src', M.util.image_url('i/loading', 'moodle')))
                            .setStyle('opacity', 0.5)
                        ));
            this._loadingNode = this.get(BASE).one('.'+CSS.LIGHTBOX);

            // prepare content
            overlay.set('bodyContent', this.get(BASE));
            this.attachEvents();
        },

        show : function (e, finishstep) {
            e.halt();

            // Different scenario depending on whether we finishing the step or just editiong the comment
            if (finishstep) {
                overlay.set('headerContent', M.str.block_workflow.finishstep);
                this.get(BASE).one('.'+CSS.SUBMIT+' input').set('value', M.str.block_workflow.finishstep);
                this._formSubmitEvent = Y.one('.'+CSS.SUBMIT+' input').on('click', this.finishstep, this);
            } else {
                overlay.set('headerContent', M.str.block_workflow.editcomments);
                this.get(BASE).one('.'+CSS.SUBMIT+' input').set('value', M.str.moodle.savechanges);
                this._formSubmitEvent = Y.one('.'+CSS.SUBMIT+' input').on('click', this.save, this);
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
                sesskey : M.cfg.sesskey,
                action  : 'getcomment',
                stateid : this.get(STATEID)
            };

            var ed = tinyMCE.get(this.get(EDITORID));
            // Fetch the comment and update the form
            Y.io(M.cfg.wwwroot + AJAXURL, {
                method:'POST',
                data:build_querystring(data),
                on : {
                    start : this.displayLoading,
                    complete: function(tid, outcome) {
                        try {
                            var result = Y.JSON.parse(outcome.responseText);
                            if (result.error) {
                                return new M.core.ajaxException(result);
                            }
                        } catch (e) {
                            new M.core.exception(e);
                        }
                        ed.setContent(result.response.comment);
                    },
                    end : this.removeLoading
                },
                context:this
            });
        },

        hide : function (e) {
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
        save : function (e) {
            var ed = tinyMCE.get(this.get(EDITORID));
            var commentsblock = Y.one('.'+CSS.BLOCKWORKFLOW+' .'+CSS.BLOCKCOMMENTS);
            // Build the data for submission
            var data = {
                sesskey : M.cfg.sesskey,
                action  : 'savecomment',
                stateid : this.get(STATEID),
                text    : ed.getContent(),
                format  : document.getElementsByName(this.get(EDITORNAME)+'[format]')[0].value
            };

            Y.io(M.cfg.wwwroot + AJAXURL, {
                method:'POST',
                data:build_querystring(data),
                on : {
                    start : this.displayLoading,
                    complete: function(tid, outcome) {
                        try {
                            var result = Y.JSON.parse(outcome.responseText);
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
                    end : this.removeLoading
                },
                context:this
            });
            this.hide();
        },
        finishstep : function (e) {
            var ed = tinyMCE.get(this.get(EDITORID));
            var workflowblock = Y.one('.'+CSS.BLOCKWORKFLOW+' .'+CSS.CONTENT);
            // Build the data for submission
            var data = {
                sesskey : M.cfg.sesskey,
                action  : 'finishstep',
                stateid : this.get(STATEID),
                text    : ed.getContent(),
                format  : document.getElementsByName(this.get(EDITORNAME)+'[format]')[0].value
            };

            Y.io(M.cfg.wwwroot + AJAXURL, {
                method:'POST',
                data:build_querystring(data),
                on : {
                    start : this.displayLoading,
                    complete: function(tid, outcome) {
                        try {
                            var result = Y.JSON.parse(outcome.responseText);
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
                                this.set(STATEID, result.response.stateid)
                                // re-attach events to block buttons
                                this.attachEvents();
                                // reinit todo events
                                M.blocks_workflow.init_todolist({"stateid":result.response.stateid});
                            }
                            if (result.response.listworkflows) {
                                // Last step, avialable workflows are listed
                                var form_id = workflowblock.one('.singleselect form').getAttribute('id');
                                var select_id = workflowblock.one('.singleselect form select').getAttribute('id');
                                // Reinit single_select event
                                M.util.init_select_autosubmit(Y, form_id, select_id, "");
                            }
                        }
                    },
                    end : this.removeLoading
                },
                context:this
            });
            this.hide();
        },
        displayLoading : function() {
            this._loadingNode.removeClass(CSS.HIDDEN);
        },
        removeLoading : function() {
            this._loadingNode.addClass(CSS.HIDDEN);
        },
        attachEvents : function() {
            Y.one('.'+CSS.BLOCKCOMMBTN+' input').on('click', this.show, this, false);
            Y.one('.'+CSS.BLOCKFINISHBTN+' input').on('click', this.show, this, true);
        }

    }, {
        NAME : COMMENTSNAME,
        ATTRS : {
            stateid: {
                value: null
            },
            editorhtml : {
                validator : Y.Lang.isString,
                value: null
            },
            editorid : {
                value: null
            },
            editorname : {
                validator : Y.Lang.isString,
                value: null
            }

        }
    });

    M.blocks_workflow = M.blocks_workflow || {};
    M.blocks_workflow.init_comments = function(params) {
        return new COMMENTS(params);
    }

}, '@VERSION@', {
    requires:['base','overlay', 'moodle-enrol-notification']
});
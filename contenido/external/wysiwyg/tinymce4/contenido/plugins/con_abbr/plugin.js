// implement abbreviation plugin
tinymce.create('tinymce.plugins.ConAbbreviationPlugin', {
    init: function(ed) {
        // button image missing
        // FIXME: make sure button is not enabled if block level element is selected
        // FIXME: because block level elements are not allowed within abbr elements
        ed.addButton('conabbr', {
            title: "Abbreviation",
            image: false,
            onPostRender: function() {
                // after button is rendered

                // get handle of this button
                var ctrl = this;
                // if selection html node changes
                ed.on('NodeChange', function(ev) {

                    // Returns true/false if the selection range is collapsed or not.
                    // Collapsed means if it's a caret or a larger selection.
                    var collapsed = ed.selection.isCollapsed()

                    // search for abbr node
                    var node = ed.dom.getParent(ed.selection.getNode(), 'abbr');

                    ctrl.disabled(collapsed);
                    ctrl.active(false);

                    // if abbr node selected
                    if (node) {
                        do {
                            ctrl.disabled(false);
                            ctrl.active(true);
                        } while (node = node.parentNode)
                            
                    }
                });
            },
            onclick: function (ev) {
                // open new window to let user enter input
                var caller = window;

                var formItems = [
                    {
                        name: 'title',
                        type: 'textbox',
                        label: 'Title:',
                        onchange: function() {
                            diag.params.form.title = this.value();
                        }
                    },
                    {
                        name: 'id',
                        type: 'textbox',
                        label: 'ID:',
                        onchange: function() {
                            diag.params.form.id = this.value();
                        }
                    },
                    {
                        name: 'class',
                        type: 'textbox',
                        label: 'Class:',
                        onchange: function() {
                            diag.params.form.class = this.value();
                        }
                    },
                    {
                        name: 'style',
                        type: 'textbox',
                        label: 'Style:',
                        onchange: function() {
                            diag.params.form.style = this.value();
                        }
                    },
                    {
                        name: 'textdirection', type: 'listbox',
                        label: 'Text Direction:',
                        values: [
                            {
                                text: '-- Not set --',
                                value: '',
                                onselect: function() {
                                    diag.params.form.dir = this.value();
                                }
                            },
                            {
                                text: 'Left to right',
                                value: 'ltr',
                                onselect: function() {
                                    diag.params.form.dir = this.value();
                                }
                            },
                            {
                                text: 'Right to left',
                                value: 'rtl',
                                onselect: function() {
                                    diag.params.form.dir = this.value();
                                }
                            },
                        ]
                    },
                    {
                        name: 'language',
                        type: 'textbox',
                        label: 'Language:',
                        onchange: function() {
                            diag.params.form.lang = this.value();
                        }
                    }
                ];
                // open plugin window
                var diag = ed.windowManager.open({
                    title: 'Abbr',
                    bodyType: 'tabpanel',
                    body: [
                        {
                            title: 'General',
                            type: 'form',
                            items: formItems
                        }
                    ],
                    buttons: [
                        {
                            text: "Insert", onclick: function() {
                                diag.params.action = 'insert';
                                diag.submit();
                            }
                        },
                        {
                            text: "Update",
                            hidden: "hidden",
                            onclick: function() {
                                diag.params.action = 'update';
                                diag.submit();
                            }
                        },
                        {
                            type: "spacer",
                            flex: 1
                        },
                        {
                            text: "Remove",
                            hidden: "hidden",
                            onclick: function() {
                                diag.params.action = 'remove';
                                diag.submit();
                            }
                        },
                        {
                            text: "Cancel", onclick: function() {
                                diag.close();
                            }
                        }
                    ],
                    onload: function() {
                        alert("tut");
                    }
                //,onSubmit: onSubmitForm
                //,

//                    height: newHeight,
//                    width: newWidth
                }, {
                    "action": "none",
                    "form": {
                        "title": "",
                        "class": '',
                        "style": "",
                        "dir": "",
                        "lang": ""
                    }
                });

                // get the attribute value of current selection for given attribute name
                function getAttr(attrName) {
                    var node = ed.dom.getParent(ed.selection.getNode(), 'abbr');
                    if (!node) {
                        return '';
                    }

                    return ed.dom.getAttrib(node, attrName);
                }

                // set values
                diag.find('#title').value(getAttr('title'));

                diag.on('submit', function() {
                    // some action work on currently selected abbr tag
                    var node = ed.dom.getParent(ed.selection.getNode(), 'abbr');

                    switch (diag.params.action) {
                        case 'insert':
                            // there must be no abbr node selected because
                            // abbr inserts a new node and nested abbr nodes are not allowed
                            if (null === node) {
                                // get string of content
                                var content = ed.selection.getContent();

                                // do not insert abbr tag if selection is empty
                                if (0 === content.length) {
                                    return;
                                }

                                // Creates a new abbr element based on form values around editor selection content
                                var el = ed.dom.create('abbr', diag.params.form, content);

                                // Sets the current selection to the specified DOM element.
                                ed.selection.setNode(el);

                                //Adds a new undo level/snapshot to the undo list.
                                ed.undoManager.add();

                                // Dispatches out a onNodeChange event to all observers.
                                // This method should be called when you need to update the UI states or element path etc.
                                ed.nodeChanged();
                            }
                            break;
                        case 'update':
                            break;
                        case 'remove':
                            break;
                    }
                    console.log(diag.params);
                });
            }
        });
    }
});
tinymce.PluginManager.add('conabbr', tinymce.plugins.ConAbbreviationPlugin);

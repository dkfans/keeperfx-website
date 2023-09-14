/*
 |  tail.writer - A flexible and comfortable markup editor, written in vanilla JavaScript!
 |  @file       ./js/tail.writer-full.js
 |  @author     SamBrishes <sam@pytes.net>
 |  @version    0.4.1 - Beta
 |
 |  @website    https://github.com/pytesNET/tail.writer
 |  @license    X11 / MIT License
 |  @copyright  Copyright © 2015 - 2019 SamBrishes, pytesNET <info@pytes.net>
 */
/*
 |  CONTAINS ALL LANGUAGEs AND MARKUPs
 */
;(function(root, factory){
    if(typeof define === "function" && define.amd){
        define(function(){ return factory(root); });
    } else if(typeof module === "object" && module.exports){
        module.exports = factory(root);
    } else {
        if(typeof root.tail === "undefined"){
            root.tail = {};
        }
        root.tail.writer = factory(root);

        // jQuery Support
        if(typeof jQuery !== "undefined"){
            jQuery.fn.tailwriter = function(o){
                var r = [], i;
                this.each(function(){ if((i = tail.writer(this, o)) !== false){ r.push(i); } });
                return (r.length === 1)? r[0]: (r.length === 0)? false: r;
            };
        }

        // MooTools Support
        if(typeof(MooTools) != "undefined"){
            Element.implement({ tailwriter: function(o){ return new tail.writer(this, o); } });
        }
    }
}(this, function(root){
    "use strict";
    var w = root, d = root.document, tail = {};

    // Internal Helper Methods
    var cHAS = tail.cHAS = function(el, name){
        return (!el.classList)? false: el.classList.contains(name);
    };
    var cADD = tail.cADD = function(el, name){
        return (!el.classList)? false: (el.classList.add(name))? el: el;
    };
    var cREM = tail.cREM = function(el, name){
        return (!el.classList)? false: (el.classList.remove(name))? el: el;
    };
    var trigger = tail.trigger = function(el, event, opt){
        if(CustomEvent && CustomEvent.name){
            var ev = new CustomEvent(event, opt);
        } else {
            var ev = d.createEvent("CustomEvent");
            ev.initCustomEvent(event, !!opt.bubbles, !!opt.cancelable, opt.detail);
        }
        return el.dispatchEvent(ev);
    };
    var clone = tail.clone = function(obj, rep){
        return Object.assign({}, obj, rep || {});
    };
    var position = tail.position = function(e, abs){
        var position = {
            top:    e.offsetTop    || 0,
            left:   e.offsetLeft   || 0,
            width:  e.offsetWidth  || 0,
            height: e.offsetHeight || 0
        };
        if(abs){
            while(e = e.offsetParent){
                position.top  += e.offsetTop;
                position.left += e.offsetLeft;
            }
        }
        return position;
    };
    var create = tail.create = function(tag, classes){
        var r = d.createElement(tag);
            r.className = (classes && classes.join)? classes.join(" "): classes || "";
        return r;
    };

    /*
     |  CONSTRUCTOR
     |  @since  0.4.0 [0.2.0]
     */
    var writer = function(el, config){
        el = (typeof(el) == "string")? d.querySelectorAll(el): el;
        if(el instanceof NodeList || el instanceof HTMLCollection || el instanceof Array){
            for(var _r = [], l = el.length, i = 0; i < l; i++){
                _r.push(new writer(el[i], clone(config, {})));
            }
            return (_r.length === 1)? _r[0]: ((_r.length === 0)? false: _r);
        }
        if((el.tagName || "") != "TEXTAREA"){
            return false;
        }
        if(!(this instanceof writer)){
            return new writer(el, config);
        }

        // Check Element
        if(writer.inst[el.getAttribute("data-tail-writer")]){
            return writer.inst[el.getAttribute("data-tail-writer")];
        }
        if(el.getAttribute("data-writer")){
            var test = JSON.parse(el.getAttribute("data-writer").replace(/\'/g, '"'));
            if(test instanceof Object){
                config = clone(config, test); // This is an unofficial function ;3
            }
        }

        // Get Element Options
        config = (typeof(config) == "object")? config: {};
        config.width = (config.width === true)? el.offsetWidth: config.width;
        config.disabled = ("disabled" in config)? config.disabled: el.disabled;
        config.readonly = ("readonly" in config)? config.readonly: el.readOnly;
        config.classNames = ("classes" in config)? config.classes: config.classNames || null;

        // Init Instance
        this.e = {
            main: null,
            editor: el,
            tools: null,
            status: null
        };
        this.id = ++writer.count;
        this.con = clone(writer.defaults, config);
        this.keys = {};
        this.events = {};
        writer.inst["tail-" + this.id] = this;
        return this.init();
    };
    writer.__helper = tail;
    writer.version = "0.4.1";
    writer.status = "beta";
    writer.count = 0;
    writer.inst = {};

    /*
     |  STORE :: DEFAULT OPTIONS
     */
    writer.defaults = {
        classNames: null,
        debug: true,
        disabled: false,
        doubleLineBreak: false,
        fullscreenParent: d.body,
        height: [200, 500],
        indentTab: false,
        indentSize: 4,
        locale: "en",
        markup: "markdown",
        previewConverter: null,
        preventBindings: false,
        readonly: false,
        resize: true,
        statusbar: true,
        toolbar: "default",
        toolbarMultiLine: false,
        toolbarScrollable: true,
        tooltip: "top",
        width: "100%"
    };

    /*
     |  STORAGE :: MARKUPS
     */
    var markups = writer.markups = function(self, markup){
        if(typeof(this) == undefined){
            return new markups(self);
        }

        // Create Instance
        if(markup in markups.markups){
            this.actions = markups.markups[markup].actions;
            this.walkers = markups.markups[markup].walkers;
            this.toolbars = markups.markups[markup].toolbars;
            this.renderer = markups.markups[markup].renderer;
        } else {
            this.actions = {};
            this.walkers = {};
            this.toolbars = {};
            this.renderer = function(_){ return _; };
        }
        this.self = self;
        this.markup = markup;
        this.toolbar = [];
        this.walkers["indent"] = "\t";
        return this;
    };
    writer.markups.markups = {};
    writer.markups.globals = {

        // Special :: Indent
        indent: {
            syntax: "\t$1",
            action: function(markup, action, type){
                var sel = this.selection(), count = 1;
                if(this.walker !== false && this.previousLine() !== false){
                    count = this.indentation("count", this.previousLine());
                }
                count = "\t".repeat(count);

                if(sel.start === sel.end){
                    this.setContent(this.indentation("convert", count, true), "prepend", sel.start, null);
                } else {
                    var area = {start: 0, end: 0}, select = {start: 0, end: 0};

                    // Get Section
                    var section = this.splitContent(sel);
                        section[0] = section[0].split("\n");
                        section[2] = section[2].split("\n");
                    var content = section[0].pop() + section[1] + section[2].shift();

                    // Set Area
                    area.start = select.start  = (section[0].join("\n").length);
                    area.start = select.start += (section[0].length > 0)? 1: 0;
                    area.end   = area.start + content.length;

                    // Modify Content
                    content = this.indentation("indent", content);
                    select.end = select.start + content.length;
                    this.setContent(content, "replace", area, null);
                }
                return true;
            },
            walker: true
        },

        // Special :: Outdent
        outdent: {
            syntax: false,
            action: function(markup, action, type){
                var sel = this.selection();

                if(sel.start === sel.end){
                    var regex = new RegExp("(\\t|" + (new Array(this.con.indentSize + 1)).join(" ") + ")$");

                    // Line Handling
                    var line = this.splitContent(sel);
                        line[0] = line[0].split("\n");
                        line[2] = line[2].split("\n");
                    var curr = line[0].pop() + line[1] + line[2].shift(),
                        char = sel.start - (line[0].join("\n").length + (line[0].length? 1: 0)),
                        next = curr.substr(char),
                        prev = curr.substr(0, char);
                    this.currentLine(prev.replace(regex, "") + next);
                } else {
                    var area = {start: 0, end: 0}, select = {start: 0, end: 0};

                    // Get Section
                    var section = this.splitContent(sel);
                        section[0] = section[0].split("\n");
                        section[2] = section[2].split("\n");
                    var content = section[0].pop() + section[1] + section[2].shift();

                    // Set Area
                    area.start = select.start  = (section[0].join("\n").length);
                    area.start = select.start += (section[0].length > 0)? 1: 0;
                    area.end   = area.start + content.length;

                    // Modify Content
                    content = this.indentation("outdent", content);
                    select.end = select.start + content.length;
                    this.setContent(content, "replace", area, null);
                }
                return true;
            }
        },

        // Special :: Preview
        preview: {
            syntax: false,
            toolbar: function(tool){
                // CSS Translations (No idea how else to do it)
                tool.setAttribute("data-write", this.translate("modeWrite"));
                tool.setAttribute("data-preview", this.translate("modePreview"));
                return tool;
            },
            action: function(markup, action, type){
                var action = this.e.tools.querySelector(".action-preview");

                // Get Converter
                var convert = this.con.previewConverter, content, preview, tools;
                if(typeof(convert) !== "function"){
                    convert = this.markup.renderer || function(_1){ return _1; };
                }
                this.hideElement("dialog");
                this.hideElement("dropdown");

                // Handle
                if(!tail.cHAS(this.e.main, "preview")){
                    content = this.e.editor.value;
                    content = (content.length)? content: this.translate("previewEmpty");

                    // Render
                    preview = tail.create("DIV", "tail-writer-preview");
                    preview.innerHTML = convert.call(this, content, this.con.markup);
                    preview.style.width = this.e.editor.outerWidth + "px";
                    preview.style.height = this.e.editor.style.height;
                    preview.style.minHeight = this.e.editor.style.minHeight;
                    preview.style.maxHeight = this.e.editor.style.maxHeight;

                    // Inject
                    action.setAttribute("data-writer-title", this.translate("previewExit"));
                    this.e.editor.style.display = "none";
                    this.e.main.insertBefore(preview, this.e.editor);
                    cADD(this.e.main, "preview");

                    // Disable Buttons
                    tools = this.e.tools.querySelectorAll("button:not(.action-preview)");
                    for(var l = tools.length, i = 0; i < l; i++){
                        cADD(tools[i], "disabled");
                    }
                    cADD(this.e.tools.querySelector(".action-preview"), "active");
                } else {
                    cREM(this.e.main, "preview");
                    action.setAttribute("data-writer-title", this.translate("preview"));
                    this.e.main.removeChild(this.e.main.querySelector(".tail-writer-preview"));
                    this.e.editor.style.display = "block";

                    // Enable Buttons
                    tools = this.e.tools.querySelectorAll("button.disabled");
                    for(var i = 0; i < tools.length; i++){
                        cREM(tools[i], "disabled");
                    }
                    cREM(this.e.tools.querySelector(".action-preview"), "active");
                }
            }
        },

        // Special :: Fullscreen
        fullscreen: {
            syntax: false,
            action: function(markup, action, type){
                if(this.con.fullscreenParent === null){
                    this.con.fullscreenParent = d.body;
                }

                if(!cHAS(this.e.main, "fullscreen")){
                    cADD(this.e.main, "fullscreen");
                    d.body.style.cssText = "overflow:hidden;";

                    this.placeholder = create("DIV");
                    this.placeholder.id = this.e.main.id + "-placeholder";

                    this.e.main.parentElement.replaceChild(this.placeholder, this.e.main);
                    this.con.fullscreenParent.appendChild(this.e.main);
                } else {
                    cREM(this.e.main, "fullscreen");
                    d.body.style.removeProperty("overflow");

                    this.placeholder.parentElement.replaceChild(this.e.main, this.placeholder);
                    this.placeholder = false;
                }
            }
        },

        // Special :: Markup
        markup: {
            type: "select",
            values: function(){
                var object = {};
                for(var key in markups.markups){
                    object[key] = key;
                }
                return object;
            },
            selected: function(){
                return this.con.markup;
            },
            syntax: false,
            action: function(markup, action, type, args){
                if(!(args[0] in markups.markups)){
                    this.error("errorMarkup");
                    return false;
                }
                this.config("markup", args[0], true);
                return true;
            }
        },

        // Special :: About
        about: {
            syntax: false,
            action: function(markup, action, type){
                var status = writer.status.split("")[0].toUpperCase() + writer.status.slice(1);
                var version = "v." + writer.version + " (" + status + ")";
                var content = ''
                    + '<div class="about">'
                    + '    <div class="about-top">'
                    + '        <div class="about-logo">tail.<span>writer</span> ' + version + '</div>'
                    + '        <div class="about-desc">' + this.translate("aboutDesc1") + '</div>'
                    + '        <div class="about-desc">' + this.translate("aboutDesc2") + '</div>'
                    + '        <a href="https://www.pytes.net" target="_blank" class="pytesnet-logo">'
                    + '        <svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.41421"><path d="M17.344 6.354c.123-1.212.239-2.305.342-3.399.052-.556.228-.661.712-.376 1.042.613 2.081 1.233 3.119 1.852 1.235.737 2.465 1.483 3.705 2.21.242.142.346.301.324.582-.032.401-.005.808-.053 1.206-.035.279.087.417.285.562 1.63 1.187 3.258 2.378 4.879 3.578.107.079.237.252.216.352-.026.117-.189.237-.319.293-.405.172-.822.314-1.278.484.577.878 1.129 1.719 1.683 2.559.296.449.609.889.888 1.349.186.306-.034.437-.292.513-.512.151-1.027.295-1.59.456.658 1.17 1.297 2.295 1.917 3.43.084.154.071.36.103.542-.173.013-.353.062-.519.032-.593-.105-1.181-.237-1.82-.369.258 1.412.503 2.76.749 4.108.068.371.151.74.197 1.114.015.123-.024.313-.109.372-.085.059-.282.036-.388-.024-.902-.512-1.791-1.046-2.695-1.556-.164-.093-.377-.155-.563-.145-3.571.195-7.142.384-10.709.63-.54.037-1.069.336-1.59.546-1.834.739-3.663 1.491-5.492 2.241-.295.121-.559.102-.845-.058-1.94-1.084-3.881-2.166-5.835-3.224-.508-.275-.641-.783-.883-1.218-.477-.858-.899-1.748-1.347-2.623-.218-.426-.197-.529.232-.768 2.839-1.578 5.669-3.169 8.527-4.712.528-.285.602-.765.786-1.199.63-1.484 1.227-2.983 1.816-4.484.184-.471.601-.626.984-.834.319-.174.646-.338.982-.473.293-.117.387-.291.381-.609-.028-1.49-.03-2.98-.032-4.47 0-.131.045-.345.122-.373.125-.046.321-.011.44.064.821.513 1.628 1.048 2.444 1.569.148.095.315.163.526.27zM5.116 27.227c1.115.618 2.166 1.211 3.23 1.779.145.078.372.085.531.034.445-.144.874-.337 1.311-.508 3.055-1.197 6.111-2.392 9.165-3.593.309-.121.632-.238.9-.426 1.055-.739 2.087-1.512 3.142-2.253.113-.079.314-.031.475-.042-.074.136-.115.315-.227.402-.585.453-1.175.899-1.787 1.315-.718.489-1.344 1.12-2.207 1.393-.698.22-1.378.494-2.066.744.12.077.223.073.325.067 2.947-.166 5.894-.34 8.843-.486.301-.015.638.086.91.227.655.34 1.28.738 1.92 1.107.14.081.293.138.494.231-.028-.218-.039-.351-.063-.482-.269-1.468-.542-2.936-.81-4.405-.101-.551.028-.672.573-.551.519.116 1.04.226 1.63.354-.062-.137-.085-.196-.115-.251-.568-1.026-1.14-2.05-1.704-3.078-.211-.385-.134-.547.288-.673.473-.14.947-.281 1.529-.455-.116-.14-.202-.227-.269-.328-.737-1.11-1.469-2.224-2.208-3.333-.271-.406-.211-.606.258-.78.336-.125.67-.255 1.075-.409-.171-.133-.282-.223-.398-.308-1.238-.909-2.431-1.894-3.733-2.701-.942-.584-1.29-1.275-1.049-2.317.072-.307-.068-.485-.335-.641-1.465-.858-2.919-1.735-4.377-2.603-.722-.431-1.447-.859-2.204-1.308-.026.145-.049.234-.058.325-.145 1.527-.291 3.054-.431 4.582-.031.348-.165.571-.509.736-1.627.781-3.24 1.592-4.851 2.407-.148.075-.291.236-.356.39-.729 1.739-1.434 3.488-2.169 5.224-.091.214-.284.431-.486.545-1.808 1.024-3.635 2.012-5.437 3.043-.322.185-.567.511-.829.79-.146.154-.252.346-.379.519-.432.583-.872 1.162-1.293 1.754-.063.089-.094.266-.049.357.32.653.657 1.298 1.005 1.937.066.121.19.228.311.298.535.309 1.087.589 1.617.907.28.168.509.151.776-.018.933-.59 1.875-1.166 2.815-1.744 1.077-.664 2.148-1.337 3.239-1.977.228-.134.532-.202.798-.194 1.171.038 2.341.114 3.511.179.12.007.249.011.355.058.064.027.132.14.125.205-.008.067-.099.158-.169.174-.114.026-.24 0-.36-.006-1.168-.058-2.336-.121-3.504-.167-.162-.006-.349.041-.488.124-1.097.659-2.186 1.331-3.276 2.002-.963.592-1.923 1.189-2.955 1.828zm9.138-22.272c.014 1.558.028 3.016.042 4.527.813-.398 1.512-.779 2.242-1.086.509-.213.754-.51.72-1.075-.014-.221.029-.417-.19-.557-.907-.58-1.812-1.164-2.814-1.809zM11.403 16.02c.309-.543.585-1.037.868-1.526.594-1.024 1.199-2.04 1.781-3.07.18-.32.367-.335.646-.145.691.468 1.391.922 2.083 1.388.321.215.315.439-.018.641-1.609.981-3.218 1.962-4.836 2.929-.109.065-.291.043-.428.011-.052-.012-.072-.165-.096-.228zm3.059-4.343c-.698 1.217-1.362 2.376-2.026 3.535.039.031.079.061.118.092 1.263-.764 2.527-1.528 3.859-2.334-.695-.461-1.296-.859-1.951-1.293z"/></svg>'
                    + '         </a>'
                    + '    </div>'
                    + '    <div class="about-bottom">'
                    + '        <div class="about-desc">' + this.translate("aboutDevelop", '<a href="https://www.github.com/pytesNET" target="_blank">pytesNET</a>') + '</div>'
                    + '        <div class="about-desc">' + this.translate("aboutDesign", '<a href="https://octicons.github.com" target="_blank">Octicon Font 8.1.0</a>') + '</div>'
                    + '        <div class="about-desc about-links">'
                    + '            <a href="https://www.github.com/pytesNET/tail.writer" target="_blank">' + this.translate("aboutLink1") + '</a>'
                    + '            <a href="https://www.github.com/pytesNET/tail.writer/wiki" target="_blank">' + this.translate("aboutLink2") + '</a>'
                    + '            <a href="https://github.pytes.net/tail.writer" target="_blank">' + this.translate("aboutLink3") + '</a>'
                    + '            <a href="https://www.github.com/pytesNET/tail.writer/issues/new" target="_blank">' + this.translate("aboutLink4") + '</a>'
                    + '        </div>'
                    + '    </div>'
                    + '</div>';
                this.showElement("dialog", "about", content);
            }
        }
    };

    /*
     |  MARKUPS :: REGISTER A NEW MARKUP
     |  @since  0.4.0 [0.4.0]
     |
     |  @param  string  The unique Markup ID.
     |  @param  object  The action button objects.
     |  @param  object  The 'default', 'full' and 'minimal' toolbar sets
     |  @param  callb.  The callback function for the preview action.
     |
     |  @return bool    TRUE if everything is fluffy, FALSE if not.
     */
    writer.markups.register = function(id, actions, toolbars, renderer){
        actions = (typeof(actions) == "function")? actions.call(this): actions;

        // Add Markup
        if(!(id in this.markups)){
            this.markups[id] = {
                actions: {},
                walkers: {},
                toolbars: toolbars,
                renderer: renderer || function(_){ return _; }
            }
        }
        var m = this.markups[id];

        // Loop Actions
        for(var key in actions){
            if(typeof(actions[key].walker) !== "undefined" && actions[key].walker){
                if(typeof(actions[key].walker) === "function"){
                    m.walkers[key] = actions[key].walker;
                } else if(actions[key].walker === true && actions[key].syntax.substr){
                    m.walkers[key] = actions[key].syntax.substr(0, actions[key].syntax.indexOf("$1"));
                }
            }
            m.actions[key] = actions[key];
        }

        // Return
        if(writer.defaults.markup == null){
            writer.defaults.markup = id;
        }
        return true;
    };

    /*
     |  MARKUPS :: UNREGISTER AN EXISTING MARKUP
     |  @since  0.4.0 [0.4.0]
     |
     |  @param  string  The unique Markup ID.
     |
     |  @return bool    TRUE if everything is fluffy, FALSE if not.
     */
    writer.markups.unregister = function(id){
        if(!(id in this.markups)){
            return false;
        }
        delete this.markups[id];
        return true;
    };

    /*
     |  MARKDOWN MARKUP
     */
    var markdownMarkup = {
        codeblock: {
            syntax: "```\n$1\n```",
            action: "block"
        },
        header: {
            syntax: function(num){
                num = (num >= 1 && num <= 6)? parseInt(num): 1;
                if(num == 1){ return "$1\n=========="; }
                if(num == 2){ return "$1\n----------"; }
                return (new Array(num + 1).join("#")) + " $1";
            },
            action: "block",
            filter: function(_1, _2, _3, _s){
                if(_s == "\n==========" || _s == "\n----------"){
                    _s.start = _s.end = _s.length;
                }
                return _2;
            }
        },
        headers: {
            syntax: false,
            action: function(markup, action, type, header){
                var item = function(_, val){
                    return '<li data-value="' + ++val + '">Header ' + val + '</li>';
                };
                var count = (new Array((header.length == 1 && header[0] == "x3")? 3: 6)).fill(0);
                var inner = '<div class="dropdown-form">'
                          + '<ul class="form-list">\n' + count.map(item).join("\n") + '\n</ul>'
                          + '</div>'

                // Show Dropdown
                this.showElement("dropdown", action.id, inner, function(event){
                    this.perform("header", [parseInt(event.target.getAttribute("data-value"))]);
                    return this.hideElement("dropdown");
                });
            }
        },
        hr: {
            syntax: "----------",
            action: "block",
            filter: function(_1, _2, _3, _s){
                _2 = (_1.lastIndexOf("\n") + 1 !== _1.length)? "\n\n" + _2: _2;
                _2 = (_1.lastIndexOf("\n\n") + 1 !== _1.length)? "\n" + _2: _2;
                _2 = (_3.indexOf("\n") < 0)? _2 + "\n\n": _2;
                _2 = (_3.indexOf("\n\n") < 0)? _2 + "\n": _2;

                _s.start = _s.end = _s.start + _2.length + 2;
                return _2;
            }
        },
        image: {
            syntax: "![$1]($2)",
            action: function(markup, action, type, args){
                if(args.length == 0){
                    var self = this;
                    return this.do_inline(markup, action, type, function(value, url){
                        if(value.length === 0){
                            url   = self.translate("imageURL");
                            value = self.translate("imageTitle");
                        } else if(/^(file|http|https)\:/i.test(value)){
                            url   = value;
                            value = self.translate("imageTitle");
                        } else {
                            url   = self.translate("imageURL");
                        }
                        return markup.replace("$1", value).replace("$2", url);
                    });
                }

                // Dialog
                var inner = '<div class="form-row">'
                          + '    <input type="text" name="title" value="" style="width:100%;" placeholder="' + this.translate("imageTitle") + '" />'
                          + '</div>'
                          + '<div class="form-row">'
                          + '    <input type="text" name="url" value="" style="width:100%;" placeholder="' + this.translate("imageURL") + '" />'
                          + '</div>'
                          + '<div class="form-row">'
                          + '    <button data-value="submit">' + this.translate("imageButton") + '</button>'
                          + '</div>', dialog = tail.create("DIV", "dialog-form");
                dialog.innerHTML = inner;
                this.showElement("dialog", action.id, dialog, function(event, el){
                    var title = el.querySelector("input[name=title]").value;
                    var url = el.querySelector("input[name=url]").value;
                    var content = action.syntax.replace("$1", title).replace("$2", url);
                    this.hideElement("dialog");
                    this.writeContent(content, null);
                    return true;
                });
            },
            filter: function(_1, _2, _3, _s){
                var url   = this.translate("imageURL"),
                    title = this.translate("imageTitle");

                // Modify Selection
                var check = (_2.indexOf(url) < 0 || _2.indexOf(title) < _2.indexOf(url));
                if(_2.indexOf(title) > 0 && check){
                    _s.start = _1.length + _2.indexOf(title);
                    _s.end   = _s.start + title.length;
                } else if(_2.indexOf(url) > 0){
                    _s.start = _1.length + _2.indexOf(url);
                    _s.end   = _s.start + url.length;
                }
                return _2;
            }
        },
        link: {
            syntax: "[$1]($2)",
            action: function(markup, action, type, args){
                if(args.length == 0){
                    var self = this;
                    return this.do_inline(markup, action, type, function(value, url){
                        if(value.length === 0){
                            url   = self.translate("linkURL");
                            value = self.translate("linkTitle");
                        } else if(/^(file|ftp|http|https|mailto|news|telnet)\:/i.test(value)){
                            url   = value;
                            value = self.translate("linkTitle");
                        } else {
                            url   = self.translate("linkURL");
                        }
                        return markup.replace("$1", value).replace("$2", url);
                    });
                }

                // Dialog
                var inner = '<div class="form-row">'
                          + '    <input type="text" name="title" value="" style="width:100%;" placeholder="' + this.translate("linkTitle") + '" />'
                          + '</div>'
                          + '<div class="form-row">'
                          + '    <select name="scheme">'
                          + '        <option value="http">http://</option>'
                          + '        <option value="https">https://</option>'
                          + '        <option value="mailto">mailto:</option>'
                          + '        <option value="file">file://</option>'
                          + '        <option value="ftp">ftp://</option>'
                          + '    </select>'
                          + '    <input type="text" name="uri" value="" placeholder="' + this.translate("linkURL") + '" />'
                          + '</div>'
                          + '<div class="form-row">'
                          + '    <button data-value="submit">' + this.translate("linkButton") + '</button>'
                          + '</div>', dialog = tail.create("DIV", "dialog-form");
                dialog.innerHTML = inner;
                dialog.querySelector("input[name='uri']").addEventListener("input", function(event){
                    var regex = new RegExp("^((file|ftp|http|https|telnet):\\/\\/|(mailto|news):)", "i");
                    if(!regex.test(this.value)){
                        return true;
                    }
                    var scheme = this.value.substr(0, this.value.indexOf(":"));
                    var select = this.parentElement.querySelector("select");
                    select.querySelector("option[value=" + scheme + "]").selected = true;
                    this.value = this.value.replace(regex, "");
                });
                this.showElement("dialog", action.id, dialog, function(event, el){
                    var title = el.querySelector("input[name=title]").value;
                    var scheme = el.querySelector("select[name=scheme] option:checked").innerText;
                    var uri = el.querySelector("input[name=uri]").value;

                    var content = action.syntax.replace("$1", title).replace("$2", scheme + uri);
                    this.hideElement("dialog");
                    this.writeContent(content, null);
                    return true;
                });
            },
            filter: function(_1, _2, _3, _s){
                var url   = this.translate("linkURL"),
                    title = this.translate("linkTitle");

                // Modify Selection
                var check = (_2.indexOf(url) < 0 || _2.indexOf(title) < _2.indexOf(url));
                if(_2.indexOf(title) > 0 && check){
                    _s.start = _1.length + _2.indexOf(title);
                    _s.end   = _s.start + title.length;
                } else if(_2.indexOf(url) > 0){
                    _s.start = _1.length + _2.indexOf(url);
                    _s.end   = _s.start + url.length;
                }
                return _2;
            }
        },
        list: {
            title: function(type){
                var type = (typeof(type) == "undefined")? "unordered": type;
                return "list" + type[0].toUpperCase() + type.slice(1).toLowerCase();
            },
            syntax: function(type){
                switch(type){
                    case "checked":
                        return "- [x]\t$1";
                    case "unchecked":
                        return "- [ ]\t$1";
                    case "ordered":
                        return "1.\t$1";
                }
                return "-\t$1";
            },
            action: "block",
            filter: function(_1, _2, _3, _s){
                if(!this.previousLine()){
                    return _2;
                }
                var num = this.previousLine().match(/^([0-9]+)\.\s+/g);
                if(num){ return _2.replace("1", (parseInt(num)+1).toString()); }
                return _2;
            },
            walker: function(string, action){
                var regexps = {
                    "list:checked": "\\- \\[x\\] ",
                    "list:unchecked": "\\- \\[ \\] ",
                    "list:unordered": "\\- ",
                    "list:ordered": "[0-9]+\. ",
                };
                for(var key in regexps){
                    var regexp = regexps[key];
                    if(!(new RegExp(regexp)).test(string)){
                        continue;
                    }
                    if((new RegExp(regexp + "\\s+\\S+", "g")).test(string)){
                        return key;
                    }
                    var markup = action.syntax(key.substr(key.indexOf(":")+1));
                        markup = this.indentation("convert", markup, true);
                        markup = markup.substr(0, markup.indexOf("$1"))
                    if(string.length === markup.length){
                        this.currentLine("");
                    }
                    break;
                }
                return false;
            }
        },
        quote: {
            syntax: ">\t$1",
            action: "block",
            walker: true
        },
        table: {
            syntax: false,
            action: function(markup, action, type, args){
                var id = "writer-" + this.id + "-" + action.id + "-";
                var inner = '<div class="form-row">'
                          + '    <label for="' + id + 'rows">' + this.translate("tableRows") + '</label>'
                          + '    <input id="' + id + 'rows" type="number" name="rows" value="3" min="1" step="1" />'
                          + '</div>'
                          + '<div class="form-row">'
                          + '    <label for="' + id + 'cols">' + this.translate("tableCols") + '</label>'
                          + '    <input id="' + id + 'cols" type="number" name="cols" value="3" min="1" step="1" />'
                          + '</div>'
                          + '<div class="form-row">'
                          + '    <input id="' + id + 'header" type="checkbox" name="header" value="1" />'
                          + '    <label for="' + id + 'header">' + this.translate("tableHeader") + '</label>'
                          + '</div>'
                          + '<div class="form-row">'
                          + '    <button data-value="submit">' + this.translate("tableButton") + '</button>'
                          + '</div>', dialog = tail.create("DIV", "dialog-form");
                dialog.innerHTML = inner;

                // Handle Dialog
                this.showElement("dialog", action.id, dialog, function(event, el){
                    var head = el.querySelector("input[name=header]").checked,
                        rows = parseInt(el.querySelector("input[name=rows]").value) || 3,
                        cols = parseInt(el.querySelector("input[name=cols]").value) || 3;

                    var content = '', spaces = 12;
                    for(var i = 0; i <= rows; i++){
                        if(!head && i === 0){ continue; }

                        for(var i2 = 1; i2 <= cols; i2++){
                            content += "| ";
                            for(var s = 0; s < spaces; s++){ content += " "; }
                            content += " ";
                        }
                        content += "|\n";

                        if(i === 0){
                            for(var i2 = 1; i2 <= cols; i2++){
                                content += "| ";
                                for(var s = 0; s < spaces; s++){ content += "-"; }
                                content += " ";
                            }
                            content += "|\n";
                        }
                    }
                    this.hideElement("dialog");
                    this.writeContent(content, null);
                    return true;
                });
            }
        }
    };

    // Add Inlines
    var inlines = {
        bold: "**$1**", boldcode: "`$1`", italic: "_$1_", strikethrough: "~~$1~~", underline: "<u>$1</u>"
    };
    for(var key in inlines){
        markdownMarkup[key] = {
            syntax: inlines[key],
            action: "inline"
        };
    }

    /*
     |  REGISTER MARKDOWN MARKUP
     */
    writer.markups.register("markdown", markdownMarkup, {
        minimal: [
            "headers:x3", "|", "bold", "italic", "strikethrough", "indent", "outdent", "|",
            "link", "image", "table", "list:unordered", "list:ordered", "|", "preview"
        ],
        default: [
            "headers", "|", "bold", "italic", "strikethrough", "|", "quote", "code",
            "codeblock", "indent", "outdent", "|", "link", "image", "table", "hr", "|",
            "list:unordered", "list:ordered", "|", "preview", "fullscreen", "about"
        ],
        full: [
            "headers", "|", "bold", "italic", "underline", "strikethrough", "|", "quote", "code",
            "codeblock", "indent", "outdent", "|", "link:dialog", "image:dialog", "table", "hr", "|",
            "list:unordered", "list:ordered", "list:unchecked", "list:checked", "|",
            "preview", "fullscreen", "about"
        ]
    }, function(content, markup){
        if(typeof(marked) === "function"){
            content = marked(content, {
                gfm: true,
                headerIds: false
            });
        } else if(showdown && showdown.Converter && typeof(showdown.Converter) === "function"){
            var converter = new showdown.Converter({
                noHeaderId: true,
                strikethrough: true,
                tables: true,
                ghCodeBlock: true,
                tasklists: true,
            });
            converter.setFlavor("github");
            content = converter.makeHtml(content);
        }
        return content;
    });

    /*
     |  TEXTILE MARKUP
     */
    var textileMarkup = {
        acronym: {
            syntax: "$1()",
            action: "inline",
            filter: function(_1, _2, _3, _s){
                if(_2.length > 2){
                    _s.start = _s.end = _s.end - 1;
                }
                return _2.toUpperCase();
            }
        },
        codeblock: {
            syntax: "bc. $1",
            action: "block"
        },
        definition: {
            syntax: "- $1",
            action: "block",
            filter: function(_1, _2, _3, _s){
                var prev = this.previousLine();
                if(prev && prev.substr(0, 1) == "-"){
                    _s.start += 1;
                    _s.end += 1;
                    return ":=" + _2.substr(1);
                }
                return _2;
            },
            walker: function(string, action){
                if(/^(- |:= )/.test(string)){
                    if(/^(- |:= )(\s+)?\S+/.test(string)){
                        return "definition";
                    }
                    this.currentLine("");
                }
                return false;
            }
        },
        headers: {
            syntax: function(num){
                return "h" + num + ". $1";
            },
            action: function(markup, action, type, header){
                if(header.length == 1 && header[0] !== "x3"){
                    return this.do_block(action.markup, action, type, header);
                }

                // Render Dropdown
                var item = function(_, val){ return '<li data-value="' + ++val + '">Header ' + val + '</li>'; };
                var count = (new Array((header.length == 1 && header[0] == "x3")? 3: 6)).fill(0);
                var inner = '<div class="dropdown-form">'
                          + '<ul class="form-list">\n' + count.map(item).join("\n") + '\n</ul>'
                          + '</div>'

                // Show Dropdown
                this.showElement("dropdown", action.id, inner, function(event){
                    this.perform("headers", [parseInt(event.target.getAttribute("data-value"))]);
                    return this.hideElement("dropdown");
                });
            }
        },
        image: {
            syntax: "!$1($2)!",
            action: function(markup, action, type, args){
                if(args.length == 0){
                    var self = this;
                    return this.do_inline(markup, action, type, function(value, url){
                        if(value.length === 0){
                            url   = self.translate("imageURL");
                            value = self.translate("imageTitle");
                        } else if(/^(file|http|https)\:/i.test(value)){
                            url   = value;
                            value = self.translate("imageTitle");
                        } else {
                            url   = self.translate("imageURL");
                        }
                        return markup.replace("$1", value).replace("$2", url);
                    });
                }

                // Dialog
                var inner = '<div class="form-row">'
                          + '    <input type="text" name="title" value="" style="width:100%;" placeholder="' + this.translate("imageTitle") + '" />'
                          + '</div>'
                          + '<div class="form-row">'
                          + '    <input type="text" name="url" value="" style="width:100%;" placeholder="' + this.translate("imageURL") + '" />'
                          + '</div>'
                          + '<div class="form-row">'
                          + '    <button data-value="submit">' + this.translate("imageButton") + '</button>'
                          + '</div>', dialog = tail.create("DIV", "dialog-form");
                dialog.innerHTML = inner;
                this.showElement("dialog", action.id, dialog, function(event, el){
                    var title = el.querySelector("input[name=title]").value;
                    var url = el.querySelector("input[name=url]").value;
                    var content = action.syntax.replace("$1", title).replace("$2", url);
                    this.hideElement("dialog");
                    this.writeContent(content, null);
                    return true;
                });
            },
            filter: function(_1, _2, _3, _s){
                var url   = this.translate("imageURL"),
                    title = this.translate("imageTitle");

                // Modify Selection
                var check = (_2.indexOf(url) < 0 || _2.indexOf(title) < _2.indexOf(url));
                if(_2.indexOf(title) > 0 && check){
                    _s.start = _1.length + _2.indexOf(title);
                    _s.end   = _s.start + title.length;
                } else if(_2.indexOf(url) > 0){
                    _s.start = _1.length + _2.indexOf(url);
                    _s.end   = _s.start + url.length;
                }
                return _2;
            }
        },
        link: {
            syntax: '"$1":$2',
            action: function(markup, action, type, args){
                if(args.length == 0){
                    var self = this;
                    return this.do_inline(markup, action, type, function(value, url){
                        if(value.length === 0){
                            url   = self.translate("linkURL");
                            value = self.translate("linkTitle");
                        } else if(/^(file|ftp|http|https|mailto|news|telnet)\:/i.test(value)){
                            url   = value;
                            value = self.translate("linkTitle");
                        } else {
                            url   = self.translate("linkURL");
                        }
                        return markup.replace("$1", value).replace("$2", url);
                    });
                }

                // Dialog
                var inner = '<div class="form-row">'
                          + '    <input type="text" name="title" value="" style="width:100%;" placeholder="' + this.translate("linkTitle") + '" />'
                          + '</div>'
                          + '<div class="form-row">'
                          + '    <select name="scheme">'
                          + '        <option value="http">http://</option>'
                          + '        <option value="https">https://</option>'
                          + '        <option value="mailto">mailto:</option>'
                          + '        <option value="file">file://</option>'
                          + '        <option value="ftp">ftp://</option>'
                          + '    </select>'
                          + '    <input type="text" name="uri" value="" placeholder="' + this.translate("linkURL") + '" />'
                          + '</div>'
                          + '<div class="form-row">'
                          + '    <button data-value="submit">' + this.translate("linkButton") + '</button>'
                          + '</div>', dialog = tail.create("DIV", "dialog-form");
                dialog.innerHTML = inner;
                dialog.querySelector("input[name='uri']").addEventListener("input", function(event){
                    var regex = new RegExp("^((file|ftp|http|https|telnet):\\/\\/|(mailto|news):)", "i");
                    if(!regex.test(this.value)){
                        return true;
                    }
                    var scheme = this.value.substr(0, this.value.indexOf(":"));
                    var select = this.parentElement.querySelector("select");
                    select.querySelector("option[value=" + scheme + "]").selected = true;
                    this.value = this.value.replace(regex, "");
                });
                this.showElement("dialog", action.id, dialog, function(event, el){
                    var title = el.querySelector("input[name=title]").value;
                    var scheme = el.querySelector("select[name=scheme] option:checked").innerText;
                    var uri = el.querySelector("input[name=uri]").value;

                    var content = action.syntax.replace("$1", title).replace("$2", scheme + uri);
                    this.hideElement("dialog");
                    this.writeContent(content, null);
                    return true;
                });
            },
            filter: function(_1, _2, _3, _s){
                var url   = this.translate("linkURL"),
                    title = this.translate("linkTitle");

                // Modify Selection
                var check = (_2.indexOf(url) < 0 || _2.indexOf(title) < _2.indexOf(url));
                if(_2.indexOf(title) > 0 && check){
                    _s.start = _1.length + _2.indexOf(title);
                    _s.end   = _s.start + title.length;
                } else if(_2.indexOf(url) > 0){
                    _s.start = _1.length + _2.indexOf(url);
                    _s.end   = _s.start + url.length;
                }
                return _2;
            }
        },
        list: {
            title: function(type){
                var type = (typeof(type) == "undefined")? "unordered": type;
                return "list" + type[0].toUpperCase() + type.slice(1).toLowerCase();
            },
            syntax: function(type){
                return (type == "ordered")? "# $1": "* $1";
            },
            action: "block",
            filter: function(_1, _2, _3, _s){
                var count = this.previousLine();
                if(count.substr && count.substr(0, 1) === "#"){
                    count = count.replace(/[^\#]/g, "").split("").length;
                    count = (new Array(count)).join("#");
                } else if(count.substr && count.substr(0, 1) === "*"){
                    count = count.replace(/[^\*]/g, "").split("").length;
                    count = (new Array(count)).join("*");
                } else {
                    return _2;
                }
                _s.start += count.length;
                _s.end += count.length;
                return count + _2;
            },
            walker: function(string, action){
                var loop = {"list:ordered": "\\#", "list:unordered": "\\*"};
                for(var key in loop){
                    if(!(new RegExp("^[" + loop[key] + "]+\\s+")).test(string)){
                        continue;
                    }
                    if((new RegExp("^[" + loop[key] + "]+\\s+\\S+", "g")).test(string)){
                        return key;
                    }
                    this.currentLine("");
                    break;
                }
                return false;
            }
        },
        pre: {
            syntax: "pre. $1",
            action: "block"
        },
        quote: {
            syntax: "bq. $1",
            action: "block"
        },
        table: {
            syntax: false,
            action: function(markup, action, type, args){
                var id = "writer-" + this.id + "-" + action.id + "-";
                var inner = '<div class="form-row">'
                          + '    <label for="' + id + 'rows">' + this.translate("tableRows") + '</label>'
                          + '    <input id="' + id + 'rows" type="number" name="rows" value="3" min="1" step="1" />'
                          + '</div>'
                          + '<div class="form-row">'
                          + '    <label for="' + id + 'cols">' + this.translate("tableCols") + '</label>'
                          + '    <input id="' + id + 'cols" type="number" name="cols" value="3" min="1" step="1" />'
                          + '</div>'
                          + '<div class="form-row">'
                          + '    <input id="' + id + 'header" type="checkbox" name="header" value="1" />'
                          + '    <label for="' + id + 'header">' + this.translate("tableHeader") + '</label>'
                          + '</div>'
                          + '<div class="form-row">'
                          + '    <input id="' + id + 'footer" type="checkbox" name="footer" value="1" />'
                          + '    <label for="' + id + 'footer">' + this.translate("tableFooter") + '</label>'
                          + '</div>'
                          + '<div class="form-row">'
                          + '    <button data-value="submit">' + this.translate("tableButton") + '</button>'
                          + '</div>', dialog = tail.create("DIV", "dialog-form");
                dialog.innerHTML = inner;

                // Handle Dialog
                this.showElement("dialog", action.id, dialog, function(event, el){
                    var header = el.querySelector("input[name=header]").checked,
                        footer = el.querySelector("input[name=footer]").checked,
                        rows = parseInt(el.querySelector("input[name=rows]").value) || 3,
                        cols = parseInt(el.querySelector("input[name=cols]").value) || 3;

                    var content = '', spaces;
                    for(var i = 0; i < rows + header + footer; i++){
                        if(header & i === 0){
                            content += "|^. \n";
                        }
                        if(footer & i === 0 + header){
                            content += "|~. \n";
                        }

                        for(var i2 = 1; i2 <= cols; i2++){
                            if((header & i === 0) || (footer & i === 0 + header)){
                                content += "|_. ";
                                spaces = 10;
                            } else {
                                content += "| ";
                                spaces = 12;
                            }
                            for(var s = 0; s < spaces; s++){
                                content += " ";
                            }
                            content += " ";
                        }
                        content += "|\n";

                        if((header & i === 0) || (footer & i === 0 + header)){
                            content += "|-. \n";
                        }
                    }
                    this.hideElement("dialog");
                    this.writeContent(content, null);
                    return true;
                });
            }
        }
    };

    // Add Inlines
    var inlines = {
        bold: "**$1**", cite: "??$1??", code: "@$1@", emphasize: "_$1_", italic: "__$1__",
        strikethrough: "-$1-", span: "%$1%", strong: "*$1*", sub: "~$1~", sup: "^$1^",
        underline: "+$1+"
    };
    for(var key in inlines){
        textileMarkup[key] = {
            syntax: inlines[key],
            action: 'inline'
        };
    }

    // Add Paragraphs
    var paragraphs = {
        left: "p<. $1", center: "p=. $1", right: "p>. $1", justify: "p<>. $1"
    };
    for(var key in paragraphs){
        textileMarkup[key] = {
            id: 'align-' + key,
            syntax: paragraphs[key],
            action: 'block',
            filter: function(before, content, after, sel){
                var prev = (this.previousLine() || '').trim(),
                    next = (this.nextLine() || '').trim();
                if(prev.length !== 0){
                    sel.start++; sel.end++;
                    content = "\n" + content;
                }
                return (next.length !== 0)? content + "\n": content;
            }
        };
    }

    /*
     |  REGISTER TEXTILE MARKUP
     */
    writer.markups.register("textile", textileMarkup, {
        minimal: [
            "headers:x3", "|", "bold", "italic", "underline", "strikethrough", "indent", "outdent",
            "|", "link", "image", "table","list:unordered", "list:ordered", "|", "preview"
        ],
        default: [
            "headers", "|", "bold", "italic", "underline", "strikethrough", "|", "code", "codeblock",
            "quote", "indent", "outdent", "|", "left", "center", "right", "justify", "|", "link",
            "image", "list:unordered", "list:ordered", "table", "|", "preview", "fullscreen", "about"
        ],
        full: [
            "headers", "|", "bold", "italic", "underline", "strikethrough", "|", "cite", "code",
            "span", "sub", "sup", "|", "pre", "codeblock", "quote", "indent", "outdent", "|",
            "left", "center", "right", "justify", "|", "link:dialog", "image:dialog", "acronym", "|",
            "list:unordered", "list:ordered", "definition", "table", "hr", "|", "preview",
            "fullscreen", "about"
        ]
    }, function(content){
        if(typeof(textile) === "function"){
            content = textile(content);
        }
        return content;
    });

    /*
     |  BBCODE MARKUP
     */
    var bbcodeMarkup = {
        codeblock: {
            syntax: "[codeblock]\n$1\n[/codeblock]",
            action: "block"
        },
        color: {
            id: "text-color",
            syntax: function(color){
                return '[color=' + ((typeof(color) == "string")? color: "inherit") + ']$1[/color]';
            },
            action: function(markup, action, type, colors){
                if(colors.length == 1){
                    return this.do_inline(action.markup, action, type);
                }

                // Render Dropdown
                if(colors.length === 0){
                    colors = ["Yellow", "Orange", "Red", "Violet", "Blue", "Green"];
                }
                var item = function(val){
                    return '<li data-value="' + val + '" style="color:' + val + ';">' + val + '</li>';
                };
                var inner = '<div class="dropdown-form">'
                          + '<ul class="form-list">\n' + colors.map(item).join("\n") + '\n</ul>'
                          + '</div>'

                // Show Dropdown
                this.showElement("dropdown", action.id, inner, function(event){
                    this.perform("color", [event.target.getAttribute("data-value")]);
                    return this.hideElement("dropdown");
                });
            }
        },
        email: {
            syntax: '[email="$2"]$1[/email]',
            action: function(markup, action, type, args){
                if(args.length == 0){
                    var self = this;
                    return this.do_inline(markup, action, type, function(value, url){
                        if(value.length === 0){
                            url   = self.translate("emailAddress");
                            value = self.translate("emailTitle");
                        } else if(/^mailto\:/i.test(value)){
                            url   = value;
                            value = self.translate("emailTitle");
                        } else {
                            url   = self.translate("emailAddress");
                        }
                        return markup.replace("$1", value).replace("$2", url);
                    });
                }

                // Dialog
                var id = "writer-" + this.id + "-" + action.id + "-target";
                var inner = '<div class="form-row">'
                          + '    <input type="text" name="title" value="" placeholder="' + this.translate("emailTitle") + '" />'
                          + '</div>'
                          + '<div class="form-row">'
                          + '    <input type="text" name="uri" value="" placeholder="' + this.translate("emailAddress") + '" />'
                          + '</div>'
                          + '<div class="form-row">'
                          + '    <button data-value="submit">' + this.translate("emailButton") + '</button>'
                          + '</div>', dialog = tail.create("DIV", "dialog-form");
                dialog.innerHTML = inner;

                // Show Dialog
                this.showElement("dialog", action.id, dialog, function(event, el){
                    var uri = el.querySelector("input[name=uri]").value,
                        title = el.querySelector("input[name=title]").value;

                    // Replace Content & Return
                    var content = action.syntax.replace("$1", title).replace("$2", "mailto:" + uri);
                    this.hideElement("dialog");
                    this.writeContent(content, null);
                    return true;
                });
            },
            filter: function(_1, _2, _3, _s){
                var url   = this.translate("emailAddress"),
                    title = this.translate("emailTitle");

                // Modify Selection
                var check = (_2.indexOf(url) < 0 || _2.indexOf(title) < _2.indexOf(url));
                if(_2.indexOf(title) > 0 && check){
                    _s.start = _1.length + _2.indexOf(title);
                    _s.end   = _s.start + title.length;
                } else if(_2.indexOf(url) > 0){
                    _s.start = _1.length + _2.indexOf(url);
                    _s.end   = _s.start + url.length;
                }
                return _2;
            }
        },
        font: {
            id: "font-family",
            syntax: function(family){
                return '[font="' + ((typeof(family) == "string")? family: "inherit") + '"]$1[/font]';
            },
            action: function(markup, action, type, family){
                if(family && family.length == 1){
                    return this.do_inline(action.markup, action, type);
                }

                // Render Dropdown
                if(family.length == 0){
                    family = ["Arial", "Bookman", "Comic Sans MS", "Courier New", "Georgia", "Helvetica",
                              "Impact", "Palatino", "Times New Roman", "Trebuchet MS", "Verdana"];
                }
                var item = function(val){
                    return '<li data-value="' + val + '" style="font-family:\'' + val + '\';">' + val + '</li>';
                };
                var inner = '<div class="dropdown-form">'
                          + '<ul class="form-list">\n' + family.map(item).join("\n") + '\n</ul>'
                          + '</div>'

                // Show Dropdown
                this.showElement("dropdown", action.id, inner, function(event){
                    this.perform("font", [event.target.getAttribute("data-value")]);
                    return this.hideElement("dropdown");
                });
            }
        },
        headers: {
            syntax: function(num){
                return '[h' + num + ']$1[/h' + num + ']\n';
            },
            action: function(markup, action, type, header){
                if(header.length == 1 && header[0] !== "x3"){
                    return this.do_block(action.markup, action, type);
                }

                // Render Dropdown
                var item = function(_, val){ return '<li data-value="' + ++val + '">Header ' + val + '</li>'; };
                var count = (new Array((header.length == 1 && header[0] == "x3")? 3: 6)).fill(0);
                var inner = '<div class="dropdown-form">'
                          + '<ul class="form-list">\n' + count.map(item).join("\n") + '\n</ul>'
                          + '</div>'

                // Show Dropdown
                this.showElement("dropdown", action.id, inner, function(event){
                    this.perform("headers", [parseInt(event.target.getAttribute("data-value"))]);
                    return this.hideElement("dropdown");
                });
            }
        },
        hr: {
            syntax: "[hr]",
            action: "block",
            filter: function(before, content, after, sel){
                if(before.lastIndexOf("\n") + 1 !== before.length){
                    content = "\n\n" + content;
                } else if(before.lastIndexOf("\n\n") + 1 !== before.length){
                    content = "\n" + content;
                }
                if(after.indexOf("\n") < 0){
                    content = content + "\n\n";
                } else if(after.indexOf("\n\n") < 0){
                    content = content + "\n";
                }

                // Modify Selection
                sel.start = sel.end = sel.start + content.length + 2;
                return content;
            }
        },
        image: {
            syntax: '[image="$2"]$1[/image]',
            action: function(markup, action, type, args){
                if(args.length == 0){
                    var self = this;
                    return this.do_inline(markup, action, type, function(value, url){
                        if(value.length === 0){
                            url   = self.translate("imageURL");
                            value = self.translate("imageTitle");
                        } else if(/^(file|http|https)\:/i.test(value)){
                            url   = value;
                            value = self.translate("imageTitle");
                        } else {
                            url   = self.translate("imageURL");
                        }
                        return markup.replace("$1", value).replace("$2", url);
                    });
                }

                // Dialog
                var inner = '<div class="form-row">'
                          + '    <input type="text" name="title" value="" style="width:100%;" placeholder="' + this.translate("imageTitle") + '" />'
                          + '</div>'
                          + '<div class="form-row">'
                          + '    <input type="text" name="url" value="" style="width:100%;" placeholder="' + this.translate("imageURL") + '" />'
                          + '</div>'
                          + '<div class="form-row">'
                          + '    <button data-value="submit">' + this.translate("imageButton") + '</button>'
                          + '</div>', dialog = tail.create("DIV", "dialog-form");
                dialog.innerHTML = inner;
                this.showElement("dialog", action.id, dialog, function(event, el){
                    var title = el.querySelector("input[name=title]").value;
                    var url = el.querySelector("input[name=url]").value;
                    var content = action.syntax.replace("$1", title).replace("$2", url);
                    this.hideElement("dialog");
                    this.writeContent(content, null);
                    return true;
                });
            },
            filter: function(_1, _2, _3, _s){
                var url   = this.translate("imageURL"),
                    title = this.translate("imageTitle");

                // Modify Selection
                var check = (_2.indexOf(url) < 0 || _2.indexOf(title) < _2.indexOf(url));
                if(_2.indexOf(title) > 0 && check){
                    _s.start = _1.length + _2.indexOf(title);
                    _s.end   = _s.start + title.length;
                } else if(_2.indexOf(url) > 0){
                    _s.start = _1.length + _2.indexOf(url);
                    _s.end   = _s.start + url.length;
                }
                return _2;
            }
        },
        link: {
            syntax: '[link="$2"]$1[/link]',
            action: function(markup, action, type, args){
                if(args.length == 0){
                    var self = this;
                    return this.do_inline(markup, action, type, function(value, url){
                        if(value.length === 0){
                            url   = self.translate("linkURL");
                            value = self.translate("linkTitle");
                        } else if(/^(file|ftp|http|https|mailto|news|telnet)\:/i.test(value)){
                            url   = value;
                            value = self.translate("linkTitle");
                        } else {
                            url   = self.translate("linkURL");
                        }
                        return markup.replace("$1", value).replace("$2", url);
                    });
                }

                // Dialog
                var id = "writer-" + this.id + "-" + action.id + "-target";
                var inner = '<div class="form-row">'
                          + '    <input type="text" name="title" value="" style="width:100%;" placeholder="' + this.translate("linkTitle") + '" />'
                          + '</div>'
                          + '<div class="form-row">'
                          + '    <select name="scheme">'
                          + '        <option value="http">http://</option>'
                          + '        <option value="https">https://</option>'
                          + '        <option value="mailto">mailto:</option>'
                          + '        <option value="file">file://</option>'
                          + '        <option value="ftp">ftp://</option>'
                          + '    </select>'
                          + '    <input type="text" name="uri" value="" placeholder="' + this.translate("linkURL") + '" />'
                          + '</div>'
                          + '<div class="form-row">'
                          + '    <input id="' + id + '" type="checkbox" name="target" value="1" />'
                          + '    <label for="' + id + '">' + this.translate("linkNewTab") + '</label>'
                          + '</div>'
                          + '<div class="form-row">'
                          + '    <button data-value="submit">' + this.translate("linkButton") + '</button>'
                          + '</div>', dialog = tail.create("DIV", "dialog-form");
                dialog.innerHTML = inner;

                // Handle Dialog
                dialog.querySelector("input[name='uri']").addEventListener("input", function(event){
                    var regex = new RegExp("^((file|ftp|http|https|telnet):\\/\\/|(mailto|news):)", "i");
                    if(!regex.test(this.value)){
                        return true;
                    }
                    var scheme = this.value.substr(0, this.value.indexOf(":"));
                    var select = this.parentElement.querySelector("select");
                    select.querySelector("option[value=" + scheme + "]").selected = true;
                    this.value = this.value.replace(regex, "");
                });

                // Show Dialog
                this.showElement("dialog", action.id, dialog, function(event, el){
                    var uri = el.querySelector("input[name=uri]").value,
                        title = el.querySelector("input[name=title]").value,
                        scheme = el.querySelector("select[name=scheme] option:checked").innerText;

                    // New Tab
                    var content = action.syntax;
                    if(el.querySelector("input[name=target]").checked){
                        content = content.replace('"$2"', '"$2" target="_blank"');
                    }

                    // Replace Content & Return
                    this.hideElement("dialog");
                    this.writeContent(content.replace("$1", title).replace("$2", scheme+uri), null);
                    return true;
                });
            },
            filter: function(_1, _2, _3, _s){
                var url   = this.translate("linkURL"),
                    title = this.translate("linkTitle");

                // Modify Selection
                var check = (_2.indexOf(url) < 0 || _2.indexOf(title) < _2.indexOf(url));
                if(_2.indexOf(title) > 0 && check){
                    _s.start = _1.length + _2.indexOf(title);
                    _s.end   = _s.start + title.length;
                } else if(_2.indexOf(url) > 0){
                    _s.start = _1.length + _2.indexOf(url);
                    _s.end   = _s.start + url.length;
                }
                return _2;
            }
        },
        list: {
            title: function(type){
                var type = (typeof(type) !== "undefined" && type)? type: "unordered";
                return "list" + type[0].toUpperCase() + type.slice(1);
            },
            syntax: "[*] $1",
            action: function(markup, action, type, args){
                if(this.walker === "list"){
                    return this.do_block(action.markup, action, args);
                }
                if(args[0] == "ordered"){
                    return this.do_block("[list=\"1\"]\n" + action.markup, action, args);
                }
                return this.do_block("[list]\n" + action.markup, action, args);
            },
            walker: function(string, action){
                var markup = this.indentation("convert", "[*] ", true);
                if(string.indexOf(markup) >= 0){
                    if(string.length > markup.length){
                        return "list";
                    } else {
                        this.currentLine("[/list]\n");
                        this.selection(this.selection().end);
                    }
                }
                return false;
            }
        },
        quote: {
            syntax: "[quote]$1[/quote]",
            action: "block"
        },
        size: {
            id: "text-size",
            syntax: function(size){
                return '[size=' + ((typeof(size) == "string")? size: "inherit") + ']$1[/size]';
            },
            action: function(markup, action, type, sizes){
                if(sizes.length == 1){
                    return this.do_inline(action.markup, action, type);
                }

                // Render Dropdown
                if(sizes.length === 0){
                    sizes = [12, 20, 2];
                }
                var item = function(val){
                    return '<li data-value="' + val + '" style="font-size:' + val + 'px;line-height:30px;">Size ' + val + '</li>';
                };
                var count = (new Array(Math.ceil((sizes[1] - sizes[0]) / sizes[2]) + 1)).fill(0).map(function(_, num){
                    return sizes[0] + (sizes[2] * num);
                });
                var inner = '<div class="dropdown-form">'
                          + '<ul class="form-list">\n' + count.map(item).join("\n") + '\n</ul>'
                          + '</div>'

                // Show Dropdown
                this.showElement("dropdown", action.id, inner, function(event){
                    this.perform("size", [event.target.getAttribute("data-value") + "px"]);
                    return this.hideElement("dropdown");
                });
            }
        },
        table: {
            syntax: false,
            action: function(markup, action, type, args){
                var id = "writer-" + this.id + "-" + action.id + "-";
                var inner = '<div class="form-row">'
                          + '    <label for="' + id + 'rows">' + this.translate("tableRows") + '</label>'
                          + '    <input id="' + id + 'rows" type="number" name="rows" value="3" min="1" step="1" />'
                          + '</div>'
                          + '<div class="form-row">'
                          + '    <label for="' + id + 'cols">' + this.translate("tableCols") + '</label>'
                          + '    <input id="' + id + 'cols" type="number" name="cols" value="3" min="1" step="1" />'
                          + '</div>'
                          + '<div class="form-row">'
                          + '    <input id="' + id + 'header" type="checkbox" name="header" value="1" />'
                          + '    <label for="' + id + 'header">' + this.translate("tableHeader") + '</label>'
                          + '</div>'
                          + '<div class="form-row">'
                          + '    <input id="' + id + 'footer" type="checkbox" name="footer" value="1" />'
                          + '    <label for="' + id + 'footer">' + this.translate("tableFooter") + '</label>'
                          + '</div>'
                          + '<div class="form-row">'
                          + '    <button data-value="submit">' + this.translate("tableButton") + '</button>'
                          + '</div>', dialog = tail.create("DIV", "dialog-form");
                dialog.innerHTML = inner;

                // Handle Dialog
                this.showElement("dialog", action.id, dialog, function(event, el){
                    var header = el.querySelector("input[name=header]").checked,
                        footer = el.querySelector("input[name=footer]").checked,
                        rows = parseInt(el.querySelector("input[name=rows]").value) || 3,
                        cols = parseInt(el.querySelector("input[name=cols]").value) || 3;

                    var content = "[table]\n";
                    for(var i = 0; i < rows + header + footer; i++){
                        if(header & i === 0){
                            content += "[thead]\n";
                        }
                        if(footer & i === 0 + header){
                            content += "[tfoot]\n";
                        }

                        content += "[tr]\n";
                        for(var i2 = 1; i2 <= cols; i2++){
                            if((header & i === 0) || (footer & i === 0 + header)){
                                content += "    [th]  [/th]\n";
                            } else {
                                content += "    [td]  [/td]\n";
                            }
                        }
                        content += "[/tr]\n";

                        if(header & i === 0){
                            content += "[/thead]\n\n";
                        }
                        if(footer & i === 0 + header){
                            content += "[/tfoot]\n\n";
                        }
                    }
                    content += "[/table]\n";
                    this.hideElement("dialog");
                    this.writeContent(content, null);
                    return true;
                });
            }
        }
    };

    // Add Inlines
    var inlines = {
        big: "big", bold: "b", code: "code", italic: "i", small: "small", strikethough: "del",
        sub: "sub", sup: "sup", underline: "u",
    };
    for(var key in inlines){
        bbcodeMarkup[key] = {
            syntax: '[' + inlines[key] + ']$1[/' + inlines[key] + ']',
            action: 'inline'
        }
    }

    // Add Paragraphs
    var paragraphs = {
        left: "left", right: "right", center: "center", justify: "justify"
    };
    for(var key in paragraphs){
        bbcodeMarkup[key] = {
            id: 'align-' + paragraphs[key],
            syntax: '[' + paragraphs[key] + ']$1[/' + paragraphs[key] + ']',
            action: 'block'
        }
    }

    /*
     |  REGISTER BBCODE MARKUP
     */
    writer.markups.register("bbcode", bbcodeMarkup, {
        minimal: [
            "headers:x3", "|", "bold", "italic", "underline", "strikethrough", "indent", "outdent",
            "|", "link", "image", "list:unordered", "list:ordered", "table", "|", "preview"
        ],
        default: [
            "headers", "font", "size", "color", "|", "bold", "italic", "underline", "strikethrough",
            "|", "code", "codeblock", "quote", "indent", "outdent", "|", "left", "center", "right",
            "justify", "|", "link", "image", "list:unordered", "list:ordered", "table", "|",
            "preview", "fullscreen", "about"
        ],
        full: [
            "headers", "font", "size", "color", "|", "bold", "italic", "underline", "strikethrough",
            "|", "code", "big", "small", "sub", "sup", "|", "quote", "codeblock", "indent", "outdent",
            "|", "left", "center", "right", "justify", "|", "link:dialog", "email:dialog", "image:dialog",
            "|", "list:unordered", "list:ordered", "table", "hr", "|", "preview", "fullscreen", "about"
        ]
    }, function(content, markup){
        if(typeof(w.tail.BBSolid) === "function"){
            content = w.tail.BBSolid(content, {
                prettyPrint: false,
                showLineBreaks: false
            });
        }
        return content;
    });

    /*
     |  MARKUPS :: INSTANCE PROTOTYPE
     */
    writer.markups.prototype = {
        /*
         |  GET A MARKUP ACTION
         |  @since  0.4.0 [0.4.0]
         */
        get: function(id, args){
            if(args === false){
                args = id.split(":");
                id = args.shift();
            }
            args = (args instanceof Array)? args: [];

            // Get Action
            if(id in this.actions){
                var action = this.actions[id];
            } else if(id in markups.globals){
                var action = markups.globals[id];
            } else {
                return false;
            }

            // Handle Main Data
            action.id = id;
            action.type = (action.type)? action.type: "button";
            action.params = args;
            action.classes = "action-" + id + ((args.length)? " action-" + id + "-" + args.join("-"): "");

            // Handle Syntax
            if(typeof(action.syntax) == "function"){
                action.markup = action.syntax.apply(this.self, (args.join)? args: [args]);
            } else {
                action.markup = action.syntax;
            }

            // Handle Title
            if(typeof(action.title) == "function"){
                action.string = action.title.apply(this.self, args);
            } else {
                action.string = id;
            }
            return action;
        },

        /*
         |  SET A MARKUP ACTION - ON THE FLY
         |  @since  0.4.0 [0.4.0]
         */
        set: function(id, action){
            if(typeof(action.walker) === "function"){
                this.walker[id] = action.walker;
            } else if(action.walker === true && action.syntax.substr){
                this.walker[id] = action.syntax.substr(0, action.syntax.indexOf("$1"));
            } else if(type in this.walker){
                delete this.walker[id];
            }
            this.actions[id] = action;
            return true;
        },

        /*
         |  SET TOOLBAR
         |  @since  0.4.0 [0.4.0]
         */
        setToolbar: function(toolbar){
            if(typeof(toolbar) === "string"){
                if(!(toolbar in this.toolbars)){
                    toolbar = "default";
                }
                this.toolbar = this.toolbars[toolbar];
            } else if(typeof(toolbar) === "function"){
                this.toolbar = toolbar.call(this.self, this.self.con.markup, this);
            } else {
                this.toolbar = (toolbar instanceof Array)? toolbar: [];
            }
            return true;
        },

        /*
         |  LOOP TOOLBAR
         |  @since  0.4.0 [0.4.0]
         */
        loopToolbar: function(){
            if(!(this.toolbar instanceof Array)){
                return false;
            }

            // Init
            if(typeof(this._toolbarItem) === "undefined"){
                this._toolbarItem = 0;
            }

            // Walk
            if(this._toolbarItem < this.toolbar.length){
                return this.toolbar[this._toolbarItem++];
            }
            this._toolbarItem = false;
            return false;
        },

        /*
         |  APPLY ACTION FILTER
         |  @since  0.4.0 [0.4.0]
         */
        filter: function(id, before, content, after, selection){
            var action = this.get(id, false);
            if(typeof(action.filter) === "function"){
                return action.filter.call(this.self, before, content, after, selection);
            }
            return content;
        }
    }

    /*
     |  STORAGE :: STRINGS
     */
    writer.strings = {
        en: {
            chars: "Characters",
            lines: "Lines",
            words: "Words",

            // Markups
            bbcode: "BBCode",
            markdown: "Markdown",
            textile: "Textile",

            // Actions
            about: "About",
            aboutDevelop: "Developed by $1",
            aboutDesc1: "written in pure vanilla JavaScript",
            aboutDesc2: "published under the MIT License",
            aboutDesign: "Designed with $1",
            aboutLink1: "GitHub Repository",
            aboutLink2: "Documentation",
            aboutLink3: "Demonstration",
            aboutLink4: "Report a Bug",
            fullscreen: "Toggle Fullscreen",
            indent: "Indent",
            markup: "Change Markup",
            modeWrite: "Write",
            modePreview: "Preview",
            outdent: "Outdent",
            preview: "Toggle Preview",
            previewEmpty: "There is nothing to Preview yet!",

            // Debug
            errorAction: "The passed action is unknown!",
            errorMarkup: "The passed markup doesn't exist!",

            // Markups
            acronym: "Acronym",
            big: "Big",
            bold: "Bold",
            center: "Center Paragraph",
            cite: "Citation",
            code: "Code (Inline)",
            codeblock: "Code (Block)",
            color: "Text Color",
            definition: "Definition List",
            emphasize: "Emphasize",
            email: "eMail",
            emailAddress: "eMail Address",
            emailButton: "Embed eMail Link",
            emailTitle: "eMail Title",
            font: "Font Family",
            header: "Heading",
            headers: "Headings",
            hr: "Horizontal Rule",
            image: "Image",
            imageButton: "Embed Image",
            imageTitle: "Image Title",
            imageURL: "Image URL",
            italic: "Italic",
            justify: "Justify Paragraph",
            left: "Left Paragraph",
            link: "Hyperlink",
            linkNewTab: "Open Link in a new Tab",
            linkButton: "Embed Link",
            linkTitle: "Link Title",
            linkURL: "Link URL",
            listOrdered: "Ordered List",
            listUnordered: "Unordered List",
            listChecked: "Checked List",
            listUnchecked: "Unchecked List",
            pre: "Pre-Formatted Text",
            quote: "Blockquote",
            right: "Right Paragraph",
            size: "Text Size",
            small: "Small",
            span: "Span",
            strikethrough: "Strikethrough",
            strong: "Strong",
            sub: "Subscript",
            sup: "Superscript",
            table: "Table",
            tableButton: "Embed Table",
            tableHeader: "Add Table Header",
            tableFooter: "Add Table Footer",
            tableCols: "Columns",
            tableRows: "Rows",
            underline: "Underline"
        },
        de: {
            chars: "Zeichen",
            lines: "Zeilen",
            words: "Wörter",

            // Markups
            bbcode: "BBCode",
            markdown: "Markdown",
            textile: "Textile",

            // Actions
            about: "Über",
            aboutDevelop: "Entwickelt von $1",
            aboutDesc1: "geschrieben in purem Vanilla JavaScript",
            aboutDesc2: "veröffentlicht unter der MIT Lizenz",
            aboutDesign: "Designed mit $1",
            aboutLink1: "GitHub Repository",
            aboutLink2: "Dokumentation",
            aboutLink3: "Demonstration",
            aboutLink4: "Fehler melden",
            fullscreen: "Vollbild wechseln",
            indent: "Einzug vergrößern",
            markup: "Change Markup",
            modeWrite: "Schreiben",
            modePreview: "Vorschau",
            outdent: "Einzug verkleinern",
            preview: "Vorschau wechseln",
            previewEmpty: "Keine Vorschau möglich!",

            // Debug
            errorAction: "Die gewünschte Aktion ist unbekannt!",
            errorMarkup: "Die gewünschte Markup ist unbekannt!",

            // Markups
            acronym: "Akronym",
            big: "Großschrift",
            bold: "Fett",
            center: "Zentriert",
            cite: "Zitieren",
            code: "Quellcode (Inzeilig)",
            codeblock: "Quellcode (Block)",
            color: "Textfarbe",
            definition: "Definitionsliste",
            emphasize: "Emphasis",
            email: "eMail",
            emailAddress: "eMail Adresse",
            emailButton: "eMail Link einfügen",
            emailTitle: "eMail Titel",
            font: "Schriftart",
            header: "Überschrift",
            headers: "Überschriften",
            hr: "Vertikaler Trennstrich",
            image: "Bild",
            imageButton: "Bild einfügen",
            imageTitle: "Bild Titel",
            imageURL: "Bild URL",
            italic: "Kursiv",
            justify: "Blocksatz",
            left: "Linksbündig",
            link: "Hyperlink",
            linkNewTab: "In einem neuen Tag öffnen",
            linkButton: "Link einfügen",
            linkTitle: "Link Titel",
            linkURL: "Link URL",
            listOrdered: "Geordnete Liste",
            listUnordered: "Ungeordnete Liste",
            listChecked: "Abgehakte Liste",
            listUnchecked: "Unabgehakte Liste",
            pre: "Vorformatierter Text",
            quote: "Zitat",
            right: "Rechtsbündig",
            size: "Textgröße",
            small: "Kleinschrift",
            span: "Span",
            strikethrough: "Durchgestrichen",
            strong: "Fettdruck",
            sub: "Tiefgestellt",
            sup: "Hochgestellt",
            table: "Tabelle",
            tableButton: "Tabelle einfügen",
            tableHeader: "Mit Tabellenkopf",
            tableFooter: "Mit Tabellenfuß",
            tableCols: "Spalten",
            tableRows: "Zeilen",
            underline: "Unterstrichen"
        },
        pl: {
            chars: "Znaki",
            lines: "Linie",
            words: "Słowa",

            // Markups
            bbcode: "BBCode",
            markdown: "Markdown",
            textile: "Textile",

            // Actions
            about: "Informacje",
            aboutDevelop: "Stworzone przez $1 (tłum. Wojciech Jodła)",
            aboutDesc1: "zaprogramowane w vanilla JavaScript",
            aboutDesc2: "opublikowane na licencji MIT",
            aboutDesign: "Zaprojektowane przy użyciu $1",
            aboutLink1: "Repozytorium GitHub",
            aboutLink2: "Dokumentacja",
            aboutLink3: "Demonstracja",
            aboutLink4: "Zgłoś błąd",
            fullscreen: "tryb pełnoekranowy",
            indent: "wcięcie",
            markup: "zmień język znaczników",
            modeWrite: "Edycja",
            modePreview: "Podgląd",
            outdent: "usuń wcięcie",
            preview: "okno podglądu",
            previewEmpty: "Nie została jeszcze wprowadzona żadna treść!",

            // Debug
            errorAction: "Użyta akcja nie jest zdefiniowana!",
            errorMarkup: "Użyty znacznik nie istnieje!",

            // Markups
            acronym: "akronim",
            big: "duży tekst",
            bold: "pogrubienie",
            center: "wycentruj paragraf",
            cite: "cytat",
            code: "kod (liniowy)",
            codeblock: "kod (blokowy)",
            color: "Kolor tekstu",
            definition: "lista definicji",
            emphasize: "emfaza",
            email: "e-mail",
            emailAddress: "adres e-mail",
            emailButton: "wstaw link e-mail",
            emailTitle: "tytuł e-maila",
            font: "font",
            header: "fagłówek",
            headers: "nagłówki",
            hr: "linia pozioma",
            image: "obraz",
            imageButton: "wstaw obraz",
            imageTitle: "tytuł obrazu",
            imageURL: "adres obrazu",
            italic: "kursywa",
            justify: "wyjustuj paragraf",
            left: "wyrównaj do lewej",
            link: "hiperlink",
            linkNewTab: "otwórz link w nowej zakładce",
            linkButton: "wstaw odnośnik",
            linkTitle: "tytuł odnośnika",
            linkURL: "adres odnośnika",
            listOrdered: "lista numerowana",
            listUnordered: "lista wypunktowana",
            listChecked: "lista zaznaczona",
            listUnchecked: "lista odznaczona",
            pre: "tekst preformatowany",
            quote: "blok cytatu",
            right: "wyrównaj do prawej",
            size: "rozmiar tekstu",
            small: "mały tekst",
            span: "element span",
            strikethrough: "przekreślenie",
            strong: "wytłuszczenie tekstu",
            sub: "indeks dolny",
            sup: "indeks górny",
            table: "tabela",
            tableButton: "wstaw tabelę",
            tableHeader: "dodaj nagłówek tabeli",
            tableFooter: "dodaj stopkę tabeli",
            tableCols: "Kolumny",
            tableRows: "Rzędy",
            underline: "podkreślenie"
        },

        /*
         |  REGISTER A NEW LOCALE
         |  @since  0.4.0 [0.4.0]
         */
        register: function(locale, object){
            if(typeof(locale) != "string" || !(object instanceof Object)){
                return false;
            }
            this[locale] = object;
            return true;
        },

        /*
         |  UNREGISTER A EXISTING LOCALE
         |  @since  0.4.0 [0.4.0]
         */
        unregister: function(locale){
            if(!(locale in this)){
                return false;
            }
            delete this[locale];
            return true;
        },

        /*
         |  GET A LOCALE STRING
         |  @since  0.4.0 [0.4.0]
         */
        get: function(locale, id){
            if(!(id in (this[locale] || {}))){
                return id;
            }
            return this[locale][id];
        },

        /*
         |  SET / MODIFY LOCALE STRINGs
         |  @since  0.4.0 [0.4.0]
         */
        set: function(locale, id, string){
            if(!(locale in this)){
                return false;
            }
            if((id instanceof Object)){
                for(var key in id){
                    this.set(locale, key, id[key]);
                }
            } else {
                this[locale][id] = (typeof(string) == "string")? string: this[locale][id];
            }
            return true;
        }
    };

    /*
     |  WRITER HOOK
     */
    writer.hook = function(id, plugin, callback){
        if(typeof(id) !== "string" || typeof(plugin) !== "string"){
            return false;
        }
        if(!(id in this.hooks)){
            this.hooks[id] = {};
        }
        if(plugin in this.hooks[id] || typeof(callback) !== "function"){
            return false;
        }
        this.hooks[id][plugin] = callback;
        return true;
    };
    writer.load = function(id, args){
        if(!(args instanceof Array)){
            args = [null];
        }

        // Hook
        if(!(id in writer.hooks)){
            return args[0];
        }
        for(var key in writer.hooks[id]){
            args[0] = writer.hooks[id][key].apply(this, args);
        }
        return args[0];
    }
    writer.hooks = {};

    /*
     |  WRITER PROTOTYPE
     */
    writer.prototype = {
        /*
         |  INTERNAL :: INIT WRITER
         |  @since  0.4.0 [0.4.0]
         */
        init: function(){
            var self = this;
            this.__ = clone(writer.strings.en, writer.strings[this.con.locale] || {});
            this.val = this.e.editor.value;

            // Check Markup
            this.markup = new markups(this, this.con.markup);
            this.markup.setToolbar(this.con.toolbar);
            this.build();

            // Event & Key Listeners (Unique Attacher)
            if(this.firstInit !== true && !this.con.preventBindings){
                for(var ev in {click: 1, input: 1, keydown: 1, keypress: 1, keyup: 1}){
                    this.e.editor.addEventListener(ev, function(event){
                        self.handle.call(self, event);
                        self.resize.call(self, true);
                        self.update.call(self);
                    });
                }
                d.body.onresize = function(){
                    self.toolbarResize.call(self);
                };

                // Key Listener :: F1
                this.bind(112, function(event){
                    event.preventDefault();
                    return this.perform("about", []);
                }, 0, 0, 0, "keydown");

                // Key Listener :: F11
                this.bind(122, function(event){
                    event.preventDefault();
                    this.perform("fullscreen", []);
                    this.e.editor.focus();
                    return this;
                }, 0, 0, 0, "keydown");

                // Key Listener :: Walker
                this.bind(13, function(event){
                    this.walker = false;
                    if((this.walker = this.walkable(this.currentLine())) !== false){
                        event.preventDefault();
                        var walker = this.walker.split(":");
                        this.setContent("\n", "append", null, null);
                        return this.perform(walker[0], (walker[1] || "").split(","));
                    }
                    if(this.con.doubleLineBreak){
                        this.writeContent("\n");
                    }
                    return true;
                }, 0, 0, 0, "keydown");

                // Key Listener :: Indent
                this.bind(9, function(event){
                    event.preventDefault();
                    return this.perform("indent", []);
                }, 0, 0, 0, "keydown");

                // Key Listener :: Outdent
                this.bind(9, function(event){
                    event.preventDefault();
                    return this.perform("outdent", []);
                }, 1, 0, 0, "keydown");

                // Key Listener :: Duplicate
                this.bind(68, function(event){
                    event.preventDefault();
                    var sel = this.selection();
                    if(sel.start === sel.end){
                        return this.writeContent("\n" + this.currentLine());
                    }
                    var val = this.getContent(sel);
                    this.selection(sel.end, sel.end);
                    return this.writeContent(val, {start: sel.end, end: sel.end + val.length});
                }, 0, 1, 0, "keydown");

                // Key Listener :: Cut Line
                this.bind(88, function(event){
                    event.preventDefault();
                    var sel = this.selection();
                    if(sel.start !== sel.end){
                        var val = this.getContent(sel);
                    } else {
                        var start = this.val.slice(0, sel.start).split("\n"),
                            end = this.val.slice(sel.start).split("\n"),
                            line = start.pop() + end.shift();
                        sel.start = start.join("\n").length
                        sel.end = sel.start + line.length + 1;
                        this.selection(sel.start, sel.end);
                    }
                    if(d.execCommand){
                        d.execCommand("copy");
                    }
                    return this.writeContent("", { start: sel.start, end: sel.start });
                }, 0, 1, 0, "keydown");
            }

            // Apply States
            this.e.editor.disabled = (this.con.disabled === true);
            this.e.editor.readOnly = (this.con.readonly === true);

            // Load Plugins & Return
            writer.load.call(this, "init");
            this.resize(false);
            this.firstInit = true;
            return this;
        },

        /*
         |  INTERNAL :: BUILD WRITER
         |  @since  0.4.0 [0.2.0]
         */
        build: function(){
            var self = this, classes = ["tail-writer"], con = this.con,
                regexp = /^[0-9.]+(?:cm|mm|in|px|pt|pc|em|ex|ch|rem|vw|vh|vmin|vmax|\%)$/i;

            // Init ClassNames
            var c = (con.classNames === true)? this.e.editor.className: con.classNames;
            classes.push((c && c.push)? c.join(" "): (c && c.split)? c: "");
            if(con.disabled){ classes.push("disabled"); }
            if(con.readonly){ classes.push("readonly"); }

            // Create & Append Editor
            this.e.main = create("DIV", classes);
            if(con.width && regexp.test(con.width)){
                this.e.main.style.width = con.width;
            } else if(con.width && !isNaN(parseFloat(con.width, 10))){
                this.e.main.style.width = con.width + "px";
            } else {
                this.e.main.style.width = "100%";
            }
            this.e.editor.setAttribute("data-tail-writer", "tail-" + this.id);
            this.e.editor.parentElement.replaceChild(this.e.main, this.e.editor);
            this.e.main.appendChild(this.e.editor);

            // Build Bars
            this.toolbar();
            this.statusbar();

            // Calculate Height
            if(!(con.height instanceof Array)){
                con.height = [con.height, con.height];
            }
            if(regexp.test(con.height[0])){
                this.e.editor.style.height = con.height[0];
            } else if(!isNaN(parseFloat(con.height[0], 10))){
                this.e.editor.style.height = con.height[0] + "px";
            } else {
                this.e.editor.style.height = "250px";
            }
            var th = w.getComputedStyle(this.e.tools);
            th = this.e.tools.offsetHeight + Math.max(parseInt(th.marginTop), 0) + Math.max(parseInt(th.marginBottom), 0);
            var sh = w.getComputedStyle(this.e.status);
            if(typeof(sh) !== "object"){
                sh = 0;
            } else {
                sh = this.e.status.offsetHeight + Math.max(parseInt(sh.marginTop), 0) + Math.max(parseInt(sh.marginBottom), 0);
            }
            var height = this.e.editor.offsetHeight - (th + sh + 2);

            // Append Height
            this.e.editor.className += " tail-writer-editor";
            this.e.editor.style.height = height + "px";
            this.e.editor.style.minHeight = height + "px";
            if(con.height[0] === con.height[1] || !con.height[1]){
                this.e.editor.style.maxHeight = height + "px";
            } else {
                if(regexp.test(con.height[1])){
                    this.e.editor.style.maxHeight = con.height[1];
                } else if(!isNaN(parseFloat(con.height[1], 10))){
                    this.e.editor.style.maxHeight = con.height[1] + "px";
                }
            }

            // Tab Size
            if(con.indentTab){
                this.e.editor.style.tabSize = con.indentSize;
            }
            return this;
        },

        /*
         |  INTERNAL :: BUILD TOOLBAR
         |  @since  0.4.0 [0.4.0]
         */
        toolbar: function(){
            var inner = create("DIV", "toolbar-inner");
            this.e.tools = create("DIV", "tail-writer-toolbar");
            this.e.tools.appendChild(inner);

            // Loop Toolbar
            var self = this, item, tool, action, key, opt, sel, values, kev, scroller, replace;
            while(item = this.markup.loopToolbar()){
                if(item === "$" && this.con.multiLineBreak){
                    inner.appendChild(create("DIV", "toolbar-linebreak"));
                    continue;
                } else if(item === "|"){
                    inner.appendChild(create("SPAN", "toolbar-separator"));
                    continue;
                } else if(!(action = this.markup.get(item, false))){
                    continue;
                }

                // Render :: Button
                if(action.type === "button"){
                    tool = create("BUTTON", "toolbar-action " + action.classes);
                    tool.addEventListener("click", function(event){
                        event.preventDefault();
                        if(self.con.disabled || cHAS(event.target, "disabled")){
                            return false;
                        }
                        return self.perform.call(self, this.getAttribute("data-writer-action"), false);
                    });
                } else

                // Render :: Select
                if(action.type === "select"){
                    tool = create("SELECT", "toolbar-action " + action.classes);
                    tool.addEventListener("change", function(event){
                        if(self.con.disabled || cHAS(event.target, "disabled")){
                            return false;
                        }
                        var handle = this.getAttribute("data-writer-action") + ":" + this.value;
                        return self.perform.call(self, handle, false);
                    });

                    // Add Options
                    sel = (action.selected.call)? action.selected.call(this): action.selected;
                    values = (action.values.call)? action.values.call(this): action.values;
                    if(typeof(values) === "object"){
                        for(key in values){
                            opt = create("OPTION");
                            opt.value = key;
                            opt.selected = (opt.value === sel);
                            opt.innerText = this.translate(values[key]);
                            tool.appendChild(opt);
                        }
                    } else {
                        continue;
                    }
                } else {
                    continue;
                }

                // Add Tooltip
                for(kev in {mouseover: 1, mouseout: 1, click: 1}){
                    tool.addEventListener(kev, function(ev){ self.tooltip.call(self, ev, this); });
                }

                // Add Tool
                tool.setAttribute("data-writer-action", item);
                tool.setAttribute("data-writer-tooltip", this.translate(action.string));
                if(action.toolbar && typeof(action.toolbar) === "function"){
                    tool = action.toolbar.call(this, tool);
                }
                inner.appendChild(tool);
            }

            // Scrollable Toolbar
            if(!this.con.toolbarMultiLine){
                inner.style.overflow = "hidden";
                inner.style.whiteSpace = "nowrap";
                if(this.con.toolbarScrollable){
                    for(var dir in {left: 1, right: 1}){
                        scroller = create("BUTTON", "toolbar-scroll action-scroll-" + dir);
                        scroller.setAttribute("tail-writer-action", "scroll-" + dir);
                        scroller.addEventListener("click", function(event){
                            event.preventDefault();
                            var el = this.parentElement.querySelector(".toolbar-inner"),
                                dir = this.getAttribute("tail-writer-action"),
                                calc = el.scrollLeft + (dir == "scroll-right"? +105: -105);


                            if(el.scrollTo){
                                el.scrollTo({ top: 0, left: calc, behavior: "smooth" });
                            } else {
                                el.scrollLeft = calc;
                            }
                        })
                        this.e.tools.insertBefore(scroller, this.e.tools.children[0]);
                    }
                }
            }

            // Append and Return
            if((replace = this.e.main.querySelector(".tail-writer-toolbar")) !== null){
                this.e.main.replaceChild(this.e.tools, replace);
            } else {
                this.e.main.insertBefore(this.e.tools, this.e.main.children[0]);
            }
            return this.toolbarResize();
        },

        /*
         |  INTERNAL :: HANDLE TOOLBAR NAVIGATION
         |  @since  0.4.0 [0.4.0]
         */
        toolbarResize: function(){
            if(!(!this.con.toolbarMultiLine && this.con.toolbarScrollable)){
                return this;
            }
            var tools = this.e.tools.querySelector(".toolbar-inner");
            if(tools.scrollWidth > tools.offsetWidth){
                cADD(this.e.tools, "scrollable");
            } else {
                cREM(this.e.tools, "scrollable");
            }
            return this;
        },

        /*
         |  INTERNAL :: BUILD STATUSBAR
         |  @since  0.4.0 [0.4.0]
         */
        statusbar: function(){
            this.e.status = create("DIV", "tail-writer-statusbar");

            // Get Fields
            if(typeof(this.con.statusbar) === "function"){
                var fields = this.con.statusbar.call(this);
                if(typeof(fields) !== "object"){
                    return false;
                }
            } else if(this.con.statusbar === false){
                return false;
            } else {
                var fields = {}, count = this.count();
                fields[this.translate("lines")] = count.lines;
                fields[this.translate("chars")] = count.chars;
                fields[this.translate("words")] = count.words;
            }

            // Handle Fields
            var inner = "";
            for(var key in fields){
                inner += '<div class="statusbar-field">'
                      +  '    <span class="field-title">' + key + ':</span>'
                      +  '    <span class="field-value">' + fields[key] + '</span>'
                      +  '</div>';
            }

            // Create Statusbar
            this.e.status.innerHTML = inner;
            this.e.main.appendChild(this.e.status);

            // Append and Return
            var replace = this.e.main.querySelector(".tail-writer-statusbar");
            if(replace !== null){
                this.e.main.replaceChild(this.e.status, replace);
            } else {
                this.e.main.insertBefore(this.e.status, this.e.main.children[0]);
            }
            return this;
        },

        /*
         |  HANDLE :: COUNT DATA
         |  @since  0.4.0 [0.4.0]
         */
        count: function(){
            var counts = {
                lines: ((this.val.match(/^|\n/g) || [""]).length).toString(),
                chars: (this.val.length).toString(),
                words: "0"
            };

            // Count Words
            var words = this.val.trim().replace(/[\:\-\.\?\!]/g, "").trim();
                words = words.replace(/[\n\t\s]+/g, " ").replace(/[^\w\s]+/g, "").split(" ");
            if(words.length <= 1 && words[0].length == 0){
                counts.words = "0";
            } else {
                counts.words = words.length.toString();
            }
            return counts;
        },

        /*
         |  INTERNAL :: HANDLE EVENTS
         |  @since  0.4.0 [0.2.0]
         */
        handle: function(event){
            var self = this;

            // Event Listener
            trigger(this.e.main, "tail.writer::" + event.type, {
                bubbles: false, cancelable: true, detail: {args: arguments, self: this}
            });
            for(var l = (this.events[event.type] || []).length, i = 0; i < l; i++){
                this.events[event.type][i].cb.apply(this, (function(args, a, b){
                    for(var l = a.length, i = 0; i < l; ++i){
                        args[i-1] = a[i];
                    }
                    args[i] = b;
                    return args;
                }(new Array(arguments.length), arguments, this.events[event.type][i].args)));
            }

            // On MouseClick
            if(event.type == "click"){
                var parent = event.target;
                while(parent !== false){
                    if(cHAS(parent, "tail-writer-dropdown") || cHAS(parent, "tail-writer-dialog")){
                        parent = true;
                        break;
                    }
                    if(cHAS(parent, "tail-writer")){
                        parent = false;
                        break;
                    }
                    parent = parent.parentElement;
                }
                if(!parent){
                    this.hideElement("dialog");
                    this.hideElement("dropdown");
                }
                return true;
            }

            // On Input
            if(event.type == "input"){
                this.val = this.e.editor.value;
            }

            // On Key
            if(["keydown", "keyup", "keypress"].indexOf(event.type) >= 0){
                var alt = event.altKey, ctrl = event.ctrlKey, shift = event.shiftKey;
                var key = event.which || event.keyCode,
                    com = 0 + (shift? 16: 0) + (ctrl? 17: 0) + (alt? 18: 0);

                if(!(event.type + "-" + key in this.keys)){
                    return false;
                }
                var keys = this.keys[event.type + "-" + key];
                return (com in keys)? keys[com].call(self, event): false;
            }
            return false;
        },

        /*
         |  INTERNAL :: WALKABLE CHECK
         |  @since  0.4.0 [0.2.0]
         */
        walkable: function(string){
            if(string.length == 0){
                return false;
            }

            // Loop Walkers
            var walk = false,
                markups = writer.markups[this.con.markup],
                walkers = this.markup.walkers, actions = this.markup.actions;
            for(var key in walkers){
                if(typeof(walkers[key]) === "function"){
                    if((walk = walkers[key].call(this, string, actions[key])) !== false){
                        break;
                    }
                } else {
                    var markup = this.indentation("convert", walkers[key], true);
                    if(string.indexOf(markup) >= 0){
                        if(string.length > markup.length){
                            walk = key;
                        } else {
                            this.currentLine("");
                        }
                        break;
                    }
                }
            }
            return walk;
        },

        /*
         |  INTERNAL :: ERROR HANDLER
         |  @since  0.4.0 [0.4.0]
         */
        error: function(string){
            if(this.con.debug){
                console.error("tail.writer Error: " + this.translate(string));
            }
        },


        /*
         |  HELPER :: TRANSLATE
         |  @version:   0.4.0 [0.4.0]
         |
         |  @param  string  The respective key to translate.
         |
         |  @return string  The translated string on success, the passed string otherwise.
         */
        translate: function(){
            if(arguments.lengt == 0){
                return "";
            }
            var string = arguments[0];
                string = (string in this.__)? this.__[string]: string;

            // Replace Artefacts
            for(var i = 1; i < arguments.length; i++){
                string = string.replace("$" + i, arguments[i]);
            }
            return string;
        },

        /*
         |  HELPER :: GET / SET SELECTION
         |  @since  0.4.0 [0.2.0]
         |
         |  @param  multi   The integer where the new selection should start, undefined to return the
         |                  current selection. If start is negative, the made selection will start at
         |                  the start'th character from the end of the content.
         |  @param  multi   The integer where the new seleciton should end, undefined to just set the
         |                  cursor to the respective position without to make a real selection. If end
         |                  is negative the made selection will end until the start'th character +1,
         |                  so use "-1" to select until the last character of the content.
         |
         |  @return object  Returns always a {start: int, end: int, lines: int} selection object.
         */
        selection: function(start, end){
            if(typeof(start) === "undefined"){
                var sel = {
                    start: this.e.editor.selectionStart,
                    end:   this.e.editor.selectionEnd
                };
                sel.lines = this.getContent(sel).split("\n").length-1;
                return sel;
            }

            // Convert Object
            if(typeof(start) === "object" && "start" in start){
                end   = start.end || end || undefined;
                start = start.start;
            }

            // Convert Numbers
            start = isNaN(start)? this.val.length: start;
            start = (start < 0)? this.val.length + start: start;
            end   = (isNaN(end) || end === null)?  start: end;
            end   = (end < 0)? this.val.length + end + 1: end;

            // Handle Selection
            this.e.editor.focus();
            this.e.editor.selectionStart = start;
            this.e.editor.selectionEnd = end;
            return this.selection();
        },

        /*
         |  HELPER :: INDENTATION
         |  @since  0.4.0 [0.4.0]
         |
         |  @param  string  The respective action to apply:
         |                      'indent'    Indent Content by one
         |                      'outdent'   Outdent Content by one
         |                      'convert'   Convert wrong Indentations into right ones (settings)
         |                      'revert'    Convert right Indentions into wrong ones (settings)
         |                      'count'     Just count the existing indention
         |  @param  multi   The respective content as string, where the indentation helper should
         |                  apply undefined to use the complete textarea value, or an selection
         |                  array or object to apply it just on the respective part of the content.
         |  @param  bool    TRUE to handle each indent, FALSE to just the starting ones.
         |
         |  @return multi   The intended or outdented new content or the count of the intentations.
         */
        indentation: function(action, content, entire){
            if(typeof(content) === "undefined"){
                content = this.val;
            }
            var right = (this.con.indentTab)? "tab": "space",
                wrong = (this.con.indentTab)? "space": "tab",
                space = (this.con.indentSize % 2 === 1)? this.con.indentSize++: this.con.indentSize,
                maine = {"tab": "\\t", "space": (new Array(space + 1)).join(" ")},
                replace = function(string, from, to){
                    var regexp = (entire)? new RegExp(maine[from], "gm"): new RegExp("^" + maine[from], "gm");
                    return string.replace(regexp, (to == "tab"? "\t": maine[to]));
                };

            // Handle Action
            switch(action){
                case "indent":
                    content = content.split("\n").map(function(string){
                        return "\t" + string;
                    }).join("\n");
                    break;
                case "outdent":
                    content = content.split("\n").map(function(string){
                        return string.replace((new RegExp("^(\\t|" + maine["space"] + ")")), "");
                    }).join("\n");
                    break;
                case "convert":     //@fallthrough
                case "revert":
                    if((action == "convert" && this.con.indentTab) || (action == "revert" && !this.con.indentTab)){
                        content = replace(content, "space", "tab");
                    } else {
                        content = replace(content, "tab", "space");
                    }
                    break;
                case "count":
                    entire = true;
                    content = replace(content, "space", "tab");
                    content = (content.match(/\t/g) || []).length;
                    break;
            }
            return content
        },

        /*
         |  HELPER :: INDENTER
         |  @since  0.4.0 [0.2.0]
         |  @deprecated 0.4.0 [0.6.0] (MARKED FOR REMOVAL)
         */
        indenter: function(string, action){
            this.error("The `indent()` function is deprecated, please use `indentation()` instead!", true);
            if(action === "count"){
                return this.indentation("count", string);
            } else if(action === "create"){
                return this.indentation("indent", string);
            }
            return this.indentation("convert", string);
        },


        /*
         |  CONTENT :: GET CONTENT
         |  @since  0.4.0 [0.4.0]
         |
         |  @param  multi   Use undefined to get the complete content or a valid cursor position
         |                  (can also be negative). You can also pass null for the current selection
         |                  or a valid selection object ([start, end] or {start: int, end: int}).
         |
         |  @return string  The respective content of the passed area, or an empty string.
         */
        getContent: function(area){
            this.val = this.e.editor.value;

            // Complete Content
            if(typeof(area) === "undefined"){
                return this.val;
            }

            // Integer
            if(!isNaN(area)){
                return this.val.substr((area < 0)? this.val.length + area: area);
            }

            // Selection
            if(area === null){
                area = this.selection();
            }
            if(!((area.length && area.length == 2) || ("start" in area && "end" in area))){
                return "";
            }
            return this.val.substring(
                ("start" in area)? area.start: area[0],
                ("end" in area)? area.end: area[1]
            );
        },

        /*
         |  CONTENT :: READ CONTENT PER LINE
         |  @since  0.4.0 [0.4.0]
         |
         |  @param  string  The line number where you want to start reading. Use 0 to start with
         |                  the first line. You can also pass a negative integer.
         |  @param  string  The line number where you want to stop reading. Don't pass any number
         |                  to read until the end. You can also pass a negative integer.
         |
         |  @return string  The respective content.
         */
        readContent: function(start, end){
            if(typeof(start) === "undefined" || (start === 0 && typeof(end) === "undefined")){
                return this.getContent();
            }
            var content = this.getContent().split("\n");

            // Calc Start
            if(start < 0){
                start = content.length + start;
            }
            if(typeof(end) == "undefined" || end < start){
                end = content.length;
            }
            return content.slice(start, end).join("\n");
        },

        /*
         |  CONTENT :: READ
         |  @since  0.4.0 [0.2.0]
         |  @deprecated 0.4.0 [0.6.0] (MARKED FOR REMOVAL)
         */
        read: function(){
            this.error("The `read()` function is deprecated, please use `getContent()` or `readContent()` instead!", true);
            return this.getContent();
        },

        /*
         |  CONTENT :: SPLIT CONTENT
         |  @since  0.4.0 [0.4.0]
         |
         |  @param  multi   Use undefined to affect the complete content or a valid cursor position
         |                  (can also be negative). You can also pass null for the current selection
         |                  or a valid selection object ([start, end] or {start: int, end: int}).
         |
         |  @return multi   An array with 3 elements [start, select, end], or FALSE on failure.
         */
        splitContent: function(area){
            var content = this.getContent();
            if(area === null){
                area = this.selection();
            }

            // Select
            if(typeof(area) === "undefined"){
                return ["", content, ""];
            } else if(!isNaN(area)){
                return [content.slice(0, area), content.slice(area), ""];
            } else if("start" in area && "end" in area){
                return [
                    content.slice(0, area.start),
                    content.slice(area.start, area.end),
                    content.slice(area.end)
                ];
            } else if(area.length && area.length == 2){
                return [
                    content.slice(0, area[0]),
                    content.slice(area[0], area[1]),
                    content.slice(area[1])
                ];
            }
            return false;
        },

        /*
         |  CONTENT :: SET CONTENT
         |  @since  0.4.0 [0.4.0]
         |
         |  @param  string  The new content, which should be written.
         |  @param  string  The handler, where the new content should be placed.
         |                      'replace'   Replaces the complete content with the new one, pass
         |                                  an content area to restrict the replacement.
         |                      'append'    Append the new content on the end of the existing one
         |                                  or on the end of the area if passed.
         |                      'prepend'   Prepend the new content on the start of the existing one
         |                                  or on the start of the area if passed.
         |  @param  multi   Use undefined to affect the complete content or a valid cursor position
         |                  (can also be negative). You can also pass null for the current selection
         |                  or a valid selection object ([start, end] or {start: int, end: int}).
         |  @param  multi   The new selection, which should be made after the content has been
         |                  replaced. Can be an [int, int] or an {start: int, end: int} object.
         |
         |  @return multi   The new content as string. FALSE on failure.
         */
        setContent: function(content, handle, area, selection){
            var section = this.splitContent(area), sel = this.selection();
            if(!section){
                return false;
            }
            var count = section.join("").length;

            // Affect new Content
            switch(handle){
                case "append":      // <old>.<new>
                    content = this.indentation("convert", content);

                    if(selection === null){
                        sel.start += content.length;
                        sel.end += content.length;
                    }
                    section[1] = section[1] + content;
                    break;
                case "prepend":     // <new>.<old>
                    content = this.indentation("convert", content);

                    if(selection === null){
                        sel.start += content.length;
                        sel.end += content.length;
                    }
                    section[1] = content + section[1];
                    break;
                default:            //    <new>
                    content = this.indentation("convert", content);

                    if(selection === null){
                        sel.end = sel.end - section[1].length + content.length;
                    }
                    section[1] = content;
                    break;
            }

            // Inject new Content
            this.e.editor.value = this.val = this.indentation("convert", section.join(""));

            // Return
            if(typeof(selection) !== "undefined"){
                if(selection === null){
                    selection = sel;
                }
                this.selection(selection);
            }
            this.resize(false);
            return this.val;
        },

        /*
         |  CONTENT :: WRITE CONTENT (HELPER FOR `.setContent()`)
         |  @since  0.4.0 [0.4.0]
         |
         |  @param  string  The new content, which should be written.
         |  @param  multi   The new selection, which should be made after the content has been
         |                  replaced. Can be an [int, int] or an {start: int, end: int} object.
         |
         |  @return string  The new content as string. FALSE on failure.
         */
        writeContent: function(content, selection){
            var sel = this.selection();
            if(sel.start !== sel.end){
                return this.setContent(content, "replace", sel, selection);
            }
            return this.setContent(content, "append", sel, selection);
        },

        /*
         |  CONTENT :: WRITE
         |  @since  0.4.0 [0.2.0]
         |  @deprecated 0.4.0 [0.6.0] (MARKED FOR REMOVAL)
         */
        write: function(content, selection){
            this.error("The `write()` function is deprecated, please use `setContent()` or `writeContent()` instead!", true);
            return this.setContent(content, "replace", undefined, selection);
        },

        /*
         |  CONTENT :: GET / SET PREVIOUS LINE
         |  @since  0.4.0 [0.4.0]
         |
         |  @param  multi   Use `undefined` to return the previous line, depending on the cursor
         |                  position. Pass a line to replace it.
         |
         |  @return multi   The (changed) previous line on success, FALSE otherwise.
         */
        previousLine: function(content){
            var sel = this.selection(),
                prev = this.val.slice(0, sel.start).split("\n");
                prev.pop();

            if(typeof(content) === "string"){
                if(prev.length > 0){
                    var area = prev.pop().length, length = prev.join("\n").length;
                    this.setContent(content, "replace", [length, area + length], null);
                } else {
                    this.setContent(content + "\n", "prepend", null);
                }
                return content;
            }
            return (prev.length > 0)? prev.pop(): false;
        },

        /*
         |  CONTENT :: CURRENT LINE
         |  @since  0.4.0 [0.4.0]
         |
         |  @param  multi   Use `undefined` to return the current line, depending on the cursor
         |                  position. Pass a line to replace it.
         |
         |  @return multi   The (changed) current line on success, FALSE otherwise.
         */
        currentLine: function(content){
            var sel = this.selection(),
                prev = this.val.slice(0, sel.start).split("\n"),
                next = this.val.slice(sel.start).split("\n"),
                curr = prev.pop() + next.shift();

            if(typeof(content) === "string"){
                var area = prev.join("\n").length + (prev.length? 1: 0);
                this.setContent(content, "replace", [area, area + curr.length], null);
                return content;
            }
            return curr;
        },

        /*
         |  CONTENT :: NEXT LINE
         |  @since  0.4.0 [0.4.0]
         |
         |  @param  multi   Use `undefined` to return the nextline, depending on the cursor
         |                  position. Pass a line to replace it.
         |
         |  @return multi   The (changed) next line on success, FALSE otherwise.
         */
        nextLine: function(content){
            var sel = this.selection(),
                next = this.val.slice(sel.start).split("\n"),
                area = sel.start - next.shift().length + 1;

            if(typeof(content) === "string"){
                if(next.length > 0){
                    this.setContent(content, "replace", [area, area + next.shift().length], null);
                } else {
                    this.setContent("\n" + content, "append", null);
                }
                return content;
            }
            return (next.length > 0)? next.shift(): false;
        },


        /*
         |  HANDLE :: RESIZE EDITOR EIGHT
         |  @since  0.4.0 [0.2.0]
         */
        resize: function(scroll){
            if(!this.con.resize){
                return false;
            }
            var css = "height:auto;min-height:none;max-height:none;opacity:0;position:absolute;";

            // Clone Textarea
            var calc = this.e.editor.cloneNode();
                calc.style.cssText = css;

            // Calculate Height
            this.e.main.insertBefore(calc, this.e.editor);
            var height = calc.scrollHeight + "px";
            if(parseInt(this.e.editor.style.minHeight, 10) > calc.scrollHeight){
                height = this.e.editor.style.minHeight;
            } else if(parseInt(this.e.editor.style.maxHeight, 10) < calc.scrollHeight){
                height = this.e.editor.style.maxHeight;
            }

            // Set Height
            this.e.editor.style.height = (parseInt(height, 10) + 2) + "px";
            this.e.main.removeChild(calc);
            return this;
        },

        /*
         |  HANDLE :: TOOLTIPS
         |  @since  0.4.0 [0.2.0]
         */
        tooltip: function(event, element){
            if(cHAS(this.e.main, "disabled") || cHAS(this.e.main, "readonly")){
                return false;
            } else if(cHAS(event.target, "disabled") || this.con.tooltip === false){
                return false;
            }
            if(typeof(this.con.tooltip) === "function"){
                return this.con.tooltip.call(this, event, element);
            }
            var id = element.getAttribute("data-writer-action").replace(/[^a-z_-]/g, "-"), tip;

            // Show Tooltip
            if(event.type === "mouseover"){
                var tip = d.createElement("DIV"), pos = position(element);

                if(!this.e.main.querySelector("#tooltip-" + id)){
                    tip.id = "tooltip-" + id;
                    tip.innerHTML = element.getAttribute("data-writer-tooltip");
                    tip.className = "tail-writer-tooltip tooltip-position-" + this.con.tooltip;
                    this.e.main.appendChild(tip);

                    switch(this.con.tooltip){
                        case "left":
                            tip.style.top = (pos.top + (pos.height/2) - (tip.offsetHeight/2)) + "px";
                            tip.style.left = (pos.left - tip.offsetWidth - 10) + "px";
                            break;
                        case "right":
                            tip.style.top = (pos.top + (pos.height/2) - (tip.offsetHeight/2)) + "px";
                            tip.style.left = (pos.left + pos.width + 10) + "px";
                            break;
                        case "bottom":
                            tip.style.top = (pos.top + pos.height + 10) + "px";
                            tip.style.left = (pos.left + (pos.width / 2) - (tip.offsetWidth / 2)) + "px";
                            break;
                        default:
                            tip.style.top = (pos.top - tip.offsetHeight - 10) + "px";
                            tip.style.left = (pos.left + (pos.width / 2) - (tip.offsetWidth / 2)) + "px";
                            break;
                    }

                    // Fix on Fullwidth
                    if(this.e.tools.offsetLeft > 1){
                        tip.style.top = (parseInt(tip.style.top) + this.e.tools.offsetTop) + "px";
                        tip.style.left = (parseInt(tip.style.left) + this.e.tools.offsetLeft) + "px";
                    }

                    // Fix on Scrollable
                    var inner = this.e.tools.querySelector(".toolbar-inner");
                    if(inner && inner.scrollLeft > 0){
                        tip.style.left = (parseInt(tip.style.left) - inner.scrollLeft) + "px";
                    }
                }
                (function(tid){
                    setTimeout(function(){
                        if(d.querySelector("#" + tid)){ cADD(d.querySelector("#" + tid), "show"); }
                    }, 350);
                }("tooltip-" + id));
                return true;
            }

            // Hide Tooltip
            if(!(tip = this.e.main.querySelector("#tooltip-" + id))){
                return false;
            }
            if(cHAS(tip, "show")){
                cREM(tip, "show");
                return (function(e){
                    setTimeout(function(){
                        if(e && e.parentElement){ e.parentElement.removeChild(e); }
                    }, 200);
                })(tip);
            }
            return (tip && tip.parentElement)? tip.parentElement.removeChild(tip): undefined;
        },

        /*
         |  HANDLE :: UPDATE WRITER
         |  @since  0.4.0 [0.4.0]
         */
        update: function(){
            writer.load.call(this, "update");
            return this.statusbar();
        },


        /*
         |  API :: PERFORM ACTION
         |  @since  0.4.0 [0.2.0]
         */
        perform: function(id, args){
            if(cHAS(this.e.main, "disabled") || cHAS(this.e.main, "readonly")){
                this.walker = false;
                return;
            }

            // Get Action
            var action = this.markup.get(id, args);
            if(action === false){
                return this.error("errorAction");
            }
            if(typeof(action.markup) === "string"){
                action.markup = this.indentation("convert", action.markup, true);
            }

            // Call Action
            if(typeof(action.action) == "function"){
                action.action.call(this, action.markup, action, action.id, action.params);
            } else if(["inline", "block"].indexOf(action.action) >= 0){
                this["do_" + action.action].call(this, action.markup, action, action.params);
            }

            // Set Walker
            this.walker = false;
            return true;
        },

        /*
         |  API :: GENERAL INLINE ACTION
         |  @since  0.4.0 [0.2.0]
         */
        do_inline: function(markup, action, args, map){
            var sel = this.selection(), val = this.splitContent(sel);

            // Modify Content
            val[1] = val[1].split("\n").map(map || function(value){
                return markup.replace("$1", value);
            }).join("\n");

            // Change Selection
            if(sel.start === sel.end){
                sel.end = sel.start += markup.indexOf("$1");
            } else {
                sel.end = sel.start + val[1].length;
            }

            // Change Content
            var content = this.markup.filter(action.id, val[0], val[1], val[2], sel);
            return this.writeContent(content, sel);
        },

        /*
         |  API :: GENERAL BLOCK ACTION
         |  @since  0.4.0 [0.2.0]
         */
        do_block: function(markup, action, args, map){
            var sel = this.selection(), val = this.splitContent(sel);

            // Modify Content
            if(action.walker){
                val[1] = val[1].split("\n").map(map || function(value){
                    return markup.replace("$1", value);
                }).join("\n");
            } else {
                val[1] = markup.replace("$1", val[1]);
            }

            // Change Selection
            if(sel.start === sel.end){
                sel.start = sel.end = sel.start + markup.indexOf("$1");
            } else {
                sel.end = sel.start  = val[0].length + val[1].length;
                sel.end = sel.start += (!action.walker)? -markup.indexOf("$1"): 0;
            }

            // Change Content
            var content = this.markup.filter(action.id, val[0], val[1], val[2], sel);
            return this.writeContent(content, sel);
        },

        /*
         |  API :: SHOW ELEMENT
         |  @since  0.4.0 [0.4.0]
         */
        showElement: function(type, id, inner, callback){
            if(cHAS(this.e.main, "disabled") || cHAS(this.e.main, "readonly")){
                return;
            }
            if(this.e.main.querySelector("#" + type + "-" + id)){
                return this.hideElement(type, id);
            }
            this.hideElement(type);

            // Create Element
            var element = create("DIV", "tail-writer-" + type + " tail-writer-" + type + "-" + id);
                element.id = type + "-" + id;
            if(inner instanceof Element){
                element.appendChild(inner);
            } else {
                element.innerHTML = inner;
            }
            this.e.main.appendChild(element);

            // Format Element
            if(type == "dropdown"){
                var btn = this.e.tools.querySelector(".action-" + id);
                element.style.top = btn.offsetTop + btn.offsetHeight + "px";
                element.style.left = btn.offsetLeft + "px";
            } else if(type == "dialog"){
                element.style.top = "50%";
                element.style.left = "50%";
                element.style.marginTop = "-" + (element.offsetHeight / 2) + "px";
                element.style.marginLeft = "-" + (element.offsetWidth / 2) + "px";
            }

            // Plugin API
            element = writer.load.call(this, "showElement", [element, type, id, inner, callback]);
            if(!(element instanceof Element)){
                return false;
            }

            // Hook Element
            (function(cb, self){
                if(!element.querySelector(".dropdown-form,.dialog-form")){
                    return false;
                }
                var el = element.querySelector(".dropdown-form,.dialog-form");
                el.addEventListener("click", function(event){
                    if(event.target.hasAttribute("data-value")){
                        event.preventDefault();
                        cb.call(self, event, el);
                    }
                });
            }(callback, this));

            // Animate
            cADD(element, "show");
            return true;
        },

        /*
         |  API :: SHOW DIALOG
         |  @since  0.4.0 [0.2.0]
         |  @deprecated 0.4.0 [0.6.0] (MARKED FOR REMOVAL)
         */
        showDialog: function(_1, _2, _3){
            this.error("The `showDialog()` function is deprecated, please use `showElement()` instead!", true);
            return this.showElement("dialog", _1, _2, _3);
        },

        /*
         |  API :: SHOW DROPDOWN
         |  @since  0.4.0 [0.2.0]
         |  @deprecated 0.4.0 [0.6.0] (MARKED FOR REMOVAL)
         */
        showDropdown: function(_1, _2, _3){
            this.error("The `showDropdown()` function is deprecated, please use `showElement()` instead!", true);
            return this.showElement("dropdown", _1, _2, _3);
        },

        /*
         |  API :: HIDE ELEMENT
         |  @since  0.4.0 [0.4.0]
         */
        hideElement: function(type, id){
            if(id === undefined){
                var elements = this.e.main.querySelectorAll(".tail-writer-" + type);
            } else {
                var elements = this.e.main.querySelectorAll(".tail-writer-" + type + "-" + id);
            }

            // Plugin API
            elements = writer.load.call(this, "hideElement", [elements, type, id]);
            if(!elements.length || elements.length && elements.length == 0){
                return false;
            }

            // Remove
            for(var l = elements.length, i = 0; i < l; i++){
                cREM(elements[i], "show");
                (function(e){
                    setTimeout(function(){
                        if(e && e.parentElement){ e.parentElement.removeChild(e); }
                    }, 200);
                })(elements[i]);
            }
            return true;
        },

        /*
         |  API :: HIDE DIALOG
         |  @since  0.4.0 [0.2.0]
         |  @deprecated 0.4.0 [0.6.0] (MARKED FOR REMOVAL)
         */
        hideDialog: function(){
            this.error("The `hideDialog()` function is deprecated, please use `hideElement()` instead!", true);
            return this.hideElement("dialog");
        },

        /*
         |  API :: HIDE DROPDOWN
         |  @since  0.4.0 [0.2.0]
         |  @deprecated 0.4.0 [0.6.0] (MARKED FOR REMOVAL)
         */
        hideDropdown: function(){
            this.error("The `hideDropdown()` function is deprecated, please use `hideElement()` instead!", true);
            return this.hideElement("dropdown");
        },


        /*
         |  PUBLIC :: EVENT LISTENER
         |  @since  0.4.0 [0.4.0]
         |
         |  @param  string  The respective event listener name. The core ones are:
         |                  'input'             Triggers when the content gets changed
         |                  'action'            Triggers when an action button gets pressed
         |                  'status'            Triggers when the statusbar gets updated
         |                  'click'             Triggers when the user clicks on the element.
         |                  'keydown'           Triggers when the user press a key.
         |                  'keyup'             Triggers when the user release a key.
         |                  'keypress'          Triggers when the user press+release a key.
         |                  'dialog:open'       Triggers when a dialog gets opened
         |                  'dropdown:open'     Triggers when a dropdown gets opened
         |                  'dialog:close'      Triggers when a dialog gets closed
         |                  'dropdown:close'    Triggers when a dropdown gets closed
         |  @param  callb.  A custom callback function.
         |  @param  array   An array with own arguments, which should pass to the callback too.
         |
         |  @return bool    TRUE if everything is fluffy, FALSE if not.
         */
        on: function(event, callback, args){
            if(typeof(event) !== "string" || typeof(callback) !== "function"){
                return false;
            }
            if(!(event in this.events)){
                this.events[event] = [];
            }
            this.events[event].push({cb: callback, args: (args instanceof Array)? args: []});
            return true;
        },

        /*
         |  PUBLIC :: BIND KEY / COMBINATION
         |  @since  0.4.0 [0.4.0]
         |
         |  @param  int     The respective event keyCode (not the key name!).
         |  @param  callb.  The callback function.
         |  @param  bool    TRUE if SHIFT need to be pressed too.
         |  @param  bool    TRUE if CTRL need to be pressed too.
         |  @param  bool    TRUE if ALT need to be pressed too.
         |  @param  string  The respective event type (keydown, keypress or keyup)
         |
         |  @return bool    TRUE if everything is fluffy, FALSE if not.
         */
        bind: function(keyCode, callback, shift, ctrl, alt, type){
            type = typeof(type) != "string"? "keydown": type;
            if(["keydown", "keypress", "keyup"].indexOf(type) < 0){
                return false;
            }

            // Check Key
            if(typeof(keyCode) != "number" || typeof(callback) != "function"){
                return false;
            }
            var com = 0 + (shift? 16: 0) + (ctrl? 17: 0) + (alt? 18: 0);

            // Add Key
            if(!(type + "-" + keyCode in this.keys)){
                this.keys[type + "-" + keyCode] = {};
            }
            this.keys[type + "-" + keyCode][com.toString()] = callback;
            return false;
        },

        /*
         |  PUBLIC :: GET | SET CONFIGURATION
         |  @since  0.4.0 [0.4.0]
         |
         |  @param  multi   Use undefined to return the complete config object, use the single key to
         |                  return or set the respective value of this option or pass an object with
         |                  multiple key => value pairs to set multiple values at once.
         |  @param  multi   Set a new value to the respective option passed in `key`.
         |  @param  bool    TRUE to call reload (if you set one or more options), FALSE otherwise.
         |
         |  @return multi   The option object (on undefined), the respective value (if the key is
         |                  defined) or the prototype instance otherwise.
         */
        config: function(key, value, rebuild){
            if(typeof(key) == "undefined"){
                return this.con;
            }

            // Multiple
            if(key instanceof Object){
                for(var k in key){ this.con[k] = key[k]; }
                return (rebuild)? this.reload: this;
            }

            // Single
            if(typeof(value) == "undefined"){
                return (key in this.con)? this.con[key]: undefined;
            }
            this.con[key] = value;
            return (rebuild)? this.reload(): this;
        },

        /*
         |  PUBLIC :: DISABLE INSTANCE AND EDITOR
         |  @since  0.4.0 [0.4.0]
         |
         |  @return multi   The option object (on undefined), the respective value (if the key is
         |                  defined) or the prototype instance otherwise.
         */
        disable: function(){
            this.e.editor.disabled = (!state)? false: true;
            return this.config("disabled", true, true);
        },

        /*
         |  PUBLIC :: ENABLE INSTANCE AND EDITOR
         |  @since  0.4.0 [0.4.0]
         |
         |  @return multi   The option object (on undefined), the respective value (if the key is
         |                  defined) or the prototype instance otherwise.
         */
        enable: function(){
            this.e.editor.disabled = false;
            return this.config("disabled", false, true);
        },

        /*
         |  PUBLIC :: SET INTO READONLY MODE
         |  @since  0.4.0 [0.4.0]
         |
         |  @param  bool    TRUE to enable readonly, FALSE to disable.
         |
         |  @return multi   The option object (on undefined), the respective value (if the key is
         |                  defined) or the prototype instance otherwise.
         */
        readonly: function(state){
            this.e.editor.readOnly = (!state)? false: true;
            return this.config("readonly", (!state)? false: true, true);
        },

        /*
         |  PUBLIC :: REMOVE WRITER
         |  @since  0.4.0 [0.2.0]
         |
         |  @return this    The prototype instance.
         */
        remove: function(){
            this.e.main.removeChild(this.e.tools);
            this.e.main.removeChild(this.e.status);

            var textarea = this.e.main.querySelector("textarea");
            textarea.removeAttribute("data-tail-writer");
            cREM(textarea, "tail-writer-editor");

            this.e.main.parentElement.replaceChild(textarea, this.e.main);
            writer.load.call(this, "remove");
            return this;
        },

        /*
         |  PUBLIC :: RELOAD WRITER
         |  @since  0.4.0 [0.4.0]
         |
         |  @return this    The prototype instance.
         */
        reload: function(){
            return this.remove().init();
        }
    };

    // Return to Factory
    return writer;
}));

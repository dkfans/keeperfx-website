/*
 |  tail.writer - A flexible and comfortable markup editor, written in vanilla JavaScript!
 |  @file       ./markups/tail.markup-markdown.js
 |  @author     SamBrishes <sam@pytes.net>
 |  @version    0.4.1 - Beta
 |
 |  @website    https://github.com/pytesNET/tail.writer
 |  @license    X11 / MIT License
 |  @copyright  Copyright © 2015 - 2019 SamBrishes, pytesNET <info@pytes.net>
 */
;(function(factory){
    if(typeof(define) == "function" && define.amd){
        define(function(){
            return function(writer){ factory(writer); };
        });
    } else {
        if(typeof(window.tail) != "undefined" && window.tail.writer){
            factory(window.tail.writer);
        }
    }
}(function(writer){
    "use strict";
    var w = window, d = window.document, tail = writer.__helper;

    /*
     |  ADD MARKDOWN MARKUP
     */
    var markdownMarkup = {

        // Block :: Code Block
        codeblock: {
            syntax: "```\n$1\n```",
            action: "block"
        },

        // Block :: Heading (Single Elements)
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

        // Block :: Headers (Dropdown)
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

        // Block :: Horizontal Ruler
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

        // Inline :: Image
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

        // Inline :: Link
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

        // Block :: Lists
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

        // Block :: Quote
        quote: {
            syntax: ">\t$1",
            action: "block",
            walker: true
        },

        // Block :: Table
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

    // Return Object
    return writer;
}));

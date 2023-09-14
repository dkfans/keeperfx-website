/*
 |  tail.writer - A flexible and comfortable markup editor, written in vanilla JavaScript!
 |  @file       ./markups/tail.markup-textile.js
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
     |  ADD TEXTILE MARKUP
     */
    var textileMarkup = {

        // Inline :: Acronym
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

        // Block :: Codeblock
        codeblock: {
            syntax: "bc. $1",
            action: "block"
        },

        // Block :: Definition List
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

        // Block :: Headers
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

        // Inline :: Image
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

        // Inline :: Link
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

        // Block :: List
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

        // Block :: Pre
        pre: {
            syntax: "pre. $1",
            action: "block"
        },

        // Block :: Blockquote
        quote: {
            syntax: "bq. $1",
            action: "block"
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

    // Return Object
    return writer;
}));

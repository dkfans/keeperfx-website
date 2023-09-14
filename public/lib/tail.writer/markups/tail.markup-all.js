/*
 |  tail.writer - A flexible and comfortable markup editor, written in vanilla JavaScript!
 |  @file       ./markups/tail.markup-all.js
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

    return writer;
}));

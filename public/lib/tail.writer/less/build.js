/*
 |  tail.writer - A flexible and comfortable markup editor, written in vanilla JavaScript!
 |  @file       ./less/build.less
 |  @author     SamBrishes <sam@pytes.net>
 |  @version    0.4.1 [0.4.0] - Alpha
 |
 |  @website    https://github.com/pytesNET/tail.writer
 |  @license    X11 / MIT License
 |  @copyright  Copyright © 2015 - 2019 SamBrishes, pytesNET <info@pytes.net>
 */
/*
 |  THIS IS A NODEJS SCRIPT TO COMPILE  ALL THE LESS
 |  FILES INTO THE CSS FILES USING OUR CODING STYLES
 */
const file = require("fs");
const path = require("path");
const less = require("less");
const clean = require("clean-css");

/*
 |  PREPARE RENDERING
 */
const headerCSS = `@charset "UTF-8";
/*
 |  tail.writer - A flexible and comfortable markup editor, written in vanilla JavaScript!
 |  @file       ./css/tail.writer-{design}.css
 |  @author     SamBrishes <sam@pytes.net>
 |  @version    0.4.1 - Beta
 |
 |  @website    https://github.com/pytesNET/tail.writer
 |  @license    X11 / MIT License
 |  @copyright  Copyright © 2015 - 2019 SamBrishes, pytesNET <info@pytes.net>
 */

{css}
/*# sourceMappingURL={source} */
`; // Empty Last Line

const headerMIN = `@charset "UTF-8"; /* tail.writer v0.4.1 (Beta) */
/* @author SamBrishes <sam@pytes.net> | @github pytesNET/tail.writer | @license MIT | @copyright pytesNET <info@pytes.net> */
{css}
/*# sourceMappingURL={source} */`;


/*
 |  LESS RENDERING
 */
const optionsLess = {
   sourceMap: {
       outputFilename: "tail.writer-source.map"
   }
};

// Render Process
const renderLess = function(content, design){
    less.render(content, optionsLess).then((data) => {
        let css = compileLess(data.css);
        let min = new clean().minify(data.css).styles;

        let writeCSS = headerCSS.replace("{design}", design);
            writeCSS = writeCSS.replace("{source}", `tail.writer-${design}.map`);
            writeCSS = writeCSS.replace("{css}", css);
        let writeMIN = headerMIN.replace("{design}", design);
            writeMIN = writeMIN.replace("{source}", `tail.writer-${design}.map`);
            writeMIN = writeMIN.replace("{css}", min);
            writeMIN = writeMIN.replace(/    /g, "");

        // Write Files
        file.writeFile(`../css/tail.writer-${design}.css`, writeCSS, "utf8", (err) => {
            if(err) throw err;
        });
        file.writeFile(`../css/tail.writer-${design}.min.css`, writeMIN, "utf8", (err) => {
            if(err) throw err;
        });
        file.writeFile(`../css/tail.writer-${design}.map`, data.map, "utf8", (err) => {
            if(err) throw err;
        });
    }, (err) => {
        if(err) throw err;
    });
};

// Compile CSS Process
const compileLess = function(css){
    css = css.replace(/^([  ]+)([^ ])/gm, (string, space, item) => {
        return " ".repeat(space.length*2) + item;
    });
    css = css.replace(/((^[\*\.\:\#\[\w]+[^\n|{]*)(\,\n|))+(\{)/gm, (string, selectors) => {
        var _return = [], current = -1, count = 0;

        string.split("\n").forEach((item, num) => {
            if(num == 0){
                _return.push("");
                current++;
            }
            item = item.trim();

            if(_return[current].length + item.length > 100){
                if(_return[current].length == 0){
                    _return[current] = item;
                } else {
                    _return.push(item);
                    current++;
                }
                count = 0;
            } else {
                _return[current] = (_return[current] + " " + item).trim();
                count += item.length;
            }
        });
        return (_return.length > 0)? _return.join("\n"): string;
    });
    css = css.replace(/ {/gm, "{");
    return css.replace(/\*\/\n\/\*/gmi, "\*\/\n\n\/\*");
};


/*
 |  START RENDERING
 */
file.readdir("./", "utf-8", (err, files) => {
    if(err) throw err;

    files.forEach((filename) => {
        if(filename.indexOf("tail.writer") !== 0){
            return false;
        }
        let design = filename.replace(/tail\.writer\-([a-z0-9_-]+)\.less/g, "$1");

        // Less Rendering
        file.readFile("./" + filename, "utf-8", (err, content) => {
            if(err) throw err;
            renderLess(content, design);
        });
    });
});

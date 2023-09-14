let file = require("fs");
let icons = {
    black: [],
    white: []
};

/*
 |  WRITE ICONS TO LESS FILE
 */
let writeIcons = () => {
    let inner = `//
//  tail.writer - A flexible and comfortable markup editor, written in vanilla JavaScript!
//  @file       ./less/_octicons.less
//  @author     SamBrishes <sam@pytes.net>
//  @version    0.4.1 - Beta
//
//  @website    https://github.com/pytesNET/tail.writer
//  @license    X11 / MIT License
//  @copyright  Copyright © 2015 - 2019 SamBrishes, pytesNET <info@pytes.net>
//

//
//  THIS FILE JUST OFFERS THE ICON MAPs FOR ALL OTHER FILES
//
#icons(){
    .black(){
${icons.black.sort().join("\n")}
    }
}

#icons(){
    .white(){
${icons.white.sort().join("\n")}
    }
}`

    file.writeFile("../less/_octicons.less", inner, "utf-8", (err) => {
        if(err){ console.log(err); }
        console.log("YaY");
    });
};

/*
 |  PARSE ICONs
 */
file.readdir("./svg/", (err, files) => {
    if(err){ console.log(err); }

    // Loop Files
    let loop = [];
    for(let num in files){
        if(files[num].indexOf(".svg") !== files[num].length - 4){
            continue;
        }
        loop.push(files[num]);
    }

    // Read Icons
    for(let num in loop){
        let icon = loop[num];
        let name = icon.substr(0, icon.length - 4);

        file.readFile("./svg/" + icon, "utf-8", (err, data) => {
            if(err){ console.log(err); }

            // Black Icon
            let string1 = Buffer.from(data, "utf8").toString("base64");
            let black = [
                string1.substr(0, 99 - (38 + name.length)) + "\\",
                "\n        " + string1.substr(99 - (38 + name.length)).match(/.{1,91}/g).join("\\\n        ")
            ];
            icons.black.push(`        @${name}: "data:image/svg+xml;base64,${black.join("")}";`);

            /// White Icon
            let string2 = Buffer.from(data.replace("<path", `<path style="fill:#ffffff;"`), "utf8").toString("base64");
            let white = [
                string2.substr(0, 99 - (38 + name.length)) + "\\",
                "\n        " + string2.substr(99 - (38 + name.length)).match(/.{1,92}/g).join("\\\n        ")
            ];
            icons.white.push(`        @${name}: "data:image/svg+xml;base64,${white.join("")}";`);

            // Write File
            if(icons.black.length === loop.length){
                writeIcons();
            }
        });
    }
});

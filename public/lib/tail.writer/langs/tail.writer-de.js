/*
 |  tail.writer - A flexible and comfortable markup editor, written in vanilla JavaScript!
 |  @file       ./langs/tail.writer-de.js
 |  @author     SamBrishes <sam@pytes.net>
 |  @version    0.4.1 - Beta
 |
 |  @website    https://github.com/pytesNET/tail.writer
 |  @license    X11 / MIT License
 |  @copyright  Copyright © 2015 - 2019 SamBrishes, pytesNET <info@pytes.net>
 */
/*
 |  Translator:     SamBrishes - (https://www.pytes.net)
 |  GitHub:         <internal>
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

    w.tail.writer.strings.register("de", {
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
    });
    return writer;
}));

Changelog
=========

Version 0.4.1 - Beta
--------------------
-   Info: This is the first version, which drops IE 9 support!
-   Add: The new Polish Translation. Many Thanks to [Wojciech Jodla](https://github.com/Joduai)!
-   Add: Support for module exporting, tested with browserify.
-   Update: The `package.json`and `bower.json` variables.
-   Update: Using `classList` to add / remove / check class names.
-   Update: Using `Object.assign` only to merge / clone object properties.
-   Update: Clone language strings (with the english ones, for backward compatibilities).
-   Remove: Support for Internet Explorer 9.

Version 0.4.0 - Beta
--------------------
-   Info: This is the first version which supports different markup languages, therefore the
          package style has been adapted to the new environment. So `tail.writer(.min).js` doesn't
          contain any markup button code anymore, use `js/tail.writer-{markup}(.min).js` or
          include the markup `markups/tail.markup-{markup}.js` in addition to the main file!
-   Add: The new `tail.writer-full(.min).js` file contains all languages AND all markup actions!
-   Add: The textile markup language (You can use the direct package `tail.writer-textile(.min).js`
         or the separate markup `markup/tail.markup-textile.js` in addition to the main file!
-   Add: The bbCode markup language (You can use the direct package `tail.writer-bbcode(.min).js`
         or the separate markup `markup/tail.markup-bbcode.js` in addition to the main file!
-   Add: Support as Asynchronous Module Definition, tested with requireJS (I'm new with AMD).
-   Add: A complete new markup system and interface.
-   Add: A complete new translation system and interface.
-   Add: Support for the Markdown parser [showdown](https://github.com/showdownjs/showdown).
-   Add: Support for the textile parser [textilejs](https://github.com/borgar/textile-js).
-   Add: Support for the BBCode parser [tail.BBSolid](https://github.com/pytesNET/tail.BBSolid).
-   Add: New `classNames` option replaces `classes` and allows NULL (No custom classes),
         Boolean (true -> copy source textarea classes), String (space-separated classes)
         or Array (full with classname strings).
-   Add: New `debug` option allows to dis-/enable the debug messages [Unfinished, Prepared].
-   Add: New `disabled` option allows to dis-/enable the tail.writer field
-   Add: New `fullscreenParent` option for the "fullscreen" global action.
-   Add: New `locale` option to handle the used language locale strings.
-   Add: New `markup` option set and change the used Markup Syntax.
-   Add: New `previewConverter` option, to define an own preview callback function.
-   Add: New `readonly` option, which sets the tail.writer instance into a readonly mode.
-   Add: The new global action `fullscreen`, to enable the fullscreen mode.
-   Add: The new global action `markup`, to switch between the markup language interfaces.
-   Add: The new global action `about`, to show an about / copyright text.
-   Add: The new internal variable `tailWriter.__helper` to use the helper functions on the
         markup files too.
-   Add: The new global function structure `tailWriter.markups` to manage markup languages.
-   Add: The new global function structure `tailWriter.strings` to manage strings / translations.
-   Add: A custom event listener which allows more details / arguments.
-   Add: The new internal method `.init()`, to initialize the tail.writer script.
-   Add: The new internal method `.toolbar()`, to build up the toolbar interface.
-   Add: The new internal method `.statusbar()`, to build up the statusbar interface.
-   Add: The new internal method `.update()`, to update the tail.writer script.
-   Add: The new internal method `.error()`, to show / debug the tail.writer script.
-   Add: The new helper method `.translate()`, to translate the strings.
-   Add: The new helper method `.indentation()`, to indent the content, replaces `.indenter()`!
-   Add: The new API methods `.getContent()` and `.readContent()`, to get the content in different
         ways and with different settings, replaces `.read()`!
-   Add: The new API methods `.setContent()` and `.writeContent()`, to get the content in different
         ways and with different settings, replaces `.write()` and `.writeLine()`!
-   Add: The new API method `.splitContent()` to get a splitted content view for replacement.
-   Add: The new API methods `.showElement()` and `.hideElement()`, which replaces the old ways
         dropdown and dialog show/hide methods.
-   Add: The new public method `.on()` to use the new custom event listener.
-   Add: The new public method `.bind()` to bind keys listeners / key combinations.
-   Add: The new public method `.config()` to get and set configurations after init.
-   Add: The new public method `.disable()` to disable the instance and the editor.
-   Add: The new public method `.enable()` to enable the instance and the editor.
-   Add: The new public method `.readonly()` to enable/disable the readOnly mode.
-   Add: The new public method `.reload()` to remove and re-init the tail.writer script.
-   Add: The new class names `tail-writer`, `disabled`, `readonly`, `fullscreen` and `preview` on
         the main element to show the different states / behaviours.
-   Add: Scrollable toolbar, if the width doesn't fit the amount of actions.
-   Add: The new keyListeners for "F1" (About Popup) and F11 (Toggle Fullscreen).
-   Add: An experimental Plugin Hook API.
-   Add: Less Pre-Compiled Stylesheets, Minified CSS Stylesheets and CSS Source Maps.
-   Update: The jQuery and MooTools Bindings.
-   Update: The `statusbar` option allows now also a function to create a custom statusbar.
-   Update: The `toolbar` option allows now also a function as argument.
-   Update: The `tooltip` option allows now also "left", "right" and "bottom" + a function
            to create custom tooltips!
-   Update: The default `tooltip` function has now a delay and a CSS-based animation.
-   Update: The `width` option allows now also a Boolean (true -> Copy source textarea width.
            false -> don't set any width at all), as well as a number and other width units.
-   Update: The `height` option allows no also a single string / integer.
-   Update: Return ALWAYS an array, if a string or an Array(-Like) object has been passed!
-   Update: The "Cut" function cuts the line and copies it in the clipboard.
-   Update: The "Duplicate" function can now duplicate complete lines or single selections!
-   Update: Using a new self-wrapped animation function.
-   Update: A complete new helper structure incl. updated methods.
-   Update: The `.remove()` method returns the class instance, instead of `true`.
-   Update: The `.clone()` helper method has been updated.
-   Update: The markdown markup language code has been split into `markup/tail.markup-markdown.js`,
            you can also use the complete package `markup/tail.writer-markdown(.min).js`.
-   Update: The `.handle()` method now only assigns the event to the respective key listeners and
            triggers the custom event listeners as well.
-   Update: The single Key-Listeners has been moved into own "global" functions.
-   Update: The indent and outdent actions are now "global" actions.
-   Update: Change for loop expression style.
-   Update: German translation and fixed typos and wrong words.
-   Remove: The `.animate()` helper method.
-   Remove: The `animate` option, because the animation is now directly handled by CSS.
-   Remove: The `toolbarPosition` option, because it doesn't has any effect (may gets
            re-implemented later, in future released).
-   Remove: The internal variable `dialogs` and `dropdowns`.
-   Rename: The internal class variable `tailWriter` has been renamed into `writer`.
-   Rename: The internal variable `tailWriter.instances` has been renamed into `writer.inst`.
-   Rename: The internal variable `tailWriter.counter` has been renamed into `writrer.count`.
-   Remove: Useless `placeholder` writer variable.
-   Remove: The old markup system and interface has been completely removed.
-   Remove: The `Dropdown` option for links and images (these actions can only be used directly
            or as dialog!).
-   Remove: CSS Unit Stylings for the Preview Mode (uses now the Website-specific stylings).
-   Bugfix: Constructor Instance check.
-   Bugfix: The instance -> attribute strong doesn't match (2 different names).
-   Bugfix: The `position` property has been assigned with the value `position` (invalid) on the
            cloned element within the `resize()` method!
-   Bugfix: The inline markup action gets now repeated per line.
-   Bugfix: Start (following / new) ordered Lists at 1. (check empty Lines between 2 ordered lists)
            Thanks to [#4](https://github.com/pytesNET/tail.writer/issues/4).
-   Bugfix: Ordered Lists aren't walkable anymore after the second item
            Thanks to [#4](https://github.com/pytesNET/tail.writer/issues/4).
-   Deprecated: The `classes` option is deprecated, please use `classNames` instead.
-   Deprecated: The `.intender()` method is deprecated and gets removed in 0.6.0!
-   Deprecated: The `.read()` method is deprecated and gets removed in 0.6.0!
-   Deprecated: The `.write()` method is deprecated and gets removed in 0.6.0!
-   Deprecated: The `.showDialog()` method is deprecated and gets removed in 0.6.0!
-   Deprecated: The `.showDropdown()` method is deprecated and gets removed in 0.6.0!
-   Deprecated: The `.hideDialog()` method is deprecated and gets removed in 0.6.0!
-   Deprecated: The `.hideDropdown()` method is deprecated and gets removed in 0.6.0!

Version 0.3.2 - Alpha
---------------------
-   Info: Official support for IE >= 9 starts now :(
-   Add: New `clone()` helper function as Fallback for IE >= 9.
-   Add: New `.IE` helper variable for Fallback use for IE >= 9.
-   Add. New `animate` option, to disable it for IE (temporary solution).
-   Bugfix: Almost complete IE >= 9 support, except the `animate` option.

Version 0.3.1 - Alpha
---------------------
-   Add: A German translation.
-   Add: A minified version, minified with [jsCompress](https://jscompress.com/).
-   Add: New helper methos `trigger` to trigger tail.DateTime specific CustomEvents.
-   BugFix: Wrong added line-break when the cursor is not at the end of the line. [#1](https://github.com/pytesNET/tail.writer/issues/1)

Version 0.3.0 - Alpha
---------------------
-   **Info: The new version is now written in Vanilla JS only, but with a jQuery and MooTools implementation.**
-   Add: Show the selected character / word / line character within the statusbar.
-   Add: CTRL + D (Duplicate a Line) and CTRL + X (~~Cut~~ Remove a Line) key-shortcuts.
-   Add: The tail helper methods: `hasClass()`, `addClass()`, `removeClass()`, `position()` and `animate()`.
-   Add: New `perform()` method, which replaces the `do_action()` method.
-   Add: The `tooltip` option allows now `top` and `bottom` to change the position.
-   Add: The "Table Header" option on the `table` markdown element.
-   Add: Configurable markdown elements.
-   Add: A callback option for the markdown elements.
-   Add: A (title) filter / hook option for the markdown elements.
-   Update: The Default / Dark / GitHub theme.
-   Update: Move the walker indicator to the markdown elements.
-   Update: Change the syntax / markup reference.
-   Update: Repeat the `inline` Syntax on multiple line breaks `\n\n` and mark the complete block.
-   Update: Relocate markdown parser method into `tailWriter.parse()`, outside of the prototype object.
-   Update: Relocate GFM markdown actions into the `tailWriter.actions`, outside of the prototype object.
-   Update: A complete new Render / Action Handling method / process.
-   Update: Marks the selected text after pressing a toolbar button on inline actions.
-   Update: The `tailWriter.extend` method is completely rewritten.
-   Update: The new base64 encoded SVG images replaces the additional icon font.
-   Rename: The option `indent_tab` into `indentTab`.
-   Rename: The option `indent_size` into `indentSize`.
-   Rename: The option `toolbar_pos` into `toolbarPosition`.
-   Rename: The option `tooltip_show` into `tooltip`.
-   Rename: The option `statusbar_show` into `statusbar`.
-   Remove: The option `action_header1` (Use the tail.writer.actions object instead).
-   Remove: The option `action_header2` (Use the tail.writer.actions object instead).
-   Remove: The option `action_bold` (Use the tail.writer.actions object instead).
-   Remove: The option `action_italic` (Use the tail.writer.actions object instead).
-   Remove: Separate jQuery and MooTools Edition / Version.
-   BugFix: Invalid Walker execution.
-   BugFix: Invalid Indent and Outdent Execution / TeamPlay.
-   BugFix: Text Selection and Replacing Bugs.
-   BugFix: Resize Error on init / build.

Version 0.2.0 - Alpha
---------------------
-   Add: A jQuery Version (1.8.0 - 3.2.0)
-   Add: Support for IE 9 - 11, Firefox, Opera and Vivaldi!
-   Add: New popup-kind dialogs.
-   Add: Small "fade" animation in dropdowns (142ms).
-   Add: Some some header variations: "header-{i}" and "header-x3".
-   Add: New help and info dialog option.
-   Add: A Preview mode and action (Requries [marked](https://github.com/chjj/marked)).
-   Add: Dialog actions for "table", "image" and "link".
-   Add: A new "GitHub" theme.
-   Add: Configurable bold and italic markups.
-   Add: Auto-Resize function.
-   Add: Translatable strings.
-   Add: Function to add custom actions.
-   Add: Tooltip (and tooltip_show option) on each Action button.
-   Add: Key listener for TAB (indent) and SHIFT + TAB (outdent) actions.
-   Update: Split "list-checkbox" into "list-checked" and "list-unchecked".
-   Update: Replace FontAwesome icons with [Octicons](https://octicons.github.com/).
-   Update: Dark and Light default themes.
-   Update: Class option can now be a string or an array.
-   Update: The class names and "data-" attribute.
-   Update: "height" option needs now to be an array.
-   Rename: "class" option has renamend into "classes".
-   Rename: "header1_block" and "header2_block" has renamed into "action_header{i}".
-   Rename: "indent_mode" has renamed into "indent_tab" and accepts now only boolean values.
-   Rename: "list-bullets" has renamed into "list-unordered".
-   Rename: "list-numeric" has renamed into "list-ordered".
-   Rename: Existing dialogs into dropdowns.
-   Remove: tailWriter_Object class (Merged with the main class).
-   BugFix: Check if dialog / dropdown is already open.
-   BugFix: Position of the dropdowns on a small editor field.
-   BugFix: Check if action exists.
-   BugFix: Too many line breaks on block elements.
-   BugFix: Auto-Insert function on walkable actions.

Version 0.1.0 - Alpha
---------------------
-	First Alpha Version

== The Wordpress of Management Systems ==

TableField is an open source web-based database management system,
Kinda like a Data-CMS or Admin panel generator.
The programming approach is to be a flexible as can be.
It is a kind of Web-Based MS Access or Open Office Base database wizard.
It is not a replacement for phpMyAdmin or Adminer, it has much more features in terms of viewing and editing different data types, and very few database maintenance features.

== Built for the user ==
The UI approach is Tables and Fields.
After installation you'll use a wizard that detects your database tables and fields, and adds them. Make some fine tuning and set permissions if necessary and you are ready to go.

== Built for Flexibility ==
The greatest power of TableField is the option to change nearly anything - from adding link to the top bar from on the configuration screen, to including HTML or PHP files at the head, body or footer; 
And most important - to inherit, improve and create new Field classes in PHP or jQuery.

== Features ==
Usually field types are just plain text, a number or date.
That's what you'll get when you install TF and start the wizard.
But you can set those Field types to what they really are!

Out of the box it comes with a wide range of field types, such as:
 * `email`, `URL`, `HTML` wysiwyg, `image`, `file` , `date` (w/ or w/out year), `datetime`, `number` (with min/max set), `phone`, etc
 * `pass2` - Password validation with two fields
 * `pass3` - Password change form that requires current password
 * `stars` - Star based ranking. You'll need to set okmax to determine how many stars.
 * `params` - Key-value pairs. Optionally set them in advance. Saved as URL-encoded query string.

Also supports several options of connecting to other tables:
 * xkey = The basic external key. You'll need to set: 
    * `type` select/combo/radio - Input value in a Select-box, Combobox (datalist) or Radio-select option.
    * `xtable` External table name
    * `xname` Display value. Field name or SQL-Expression i.e {{{CONCAT(`firstname`,' ',`lastname`)}}}
    * `xkey` Key on external table to bind to (defaults to primary key)
    * `xwhere` Optionally limit the available results from the external table.
    * `xorder` Optionally set a sorting order. i.e {{{`Lastname` ASC}}}
    * `strict` When set to false it turns the 
 * `xkeys` Multiple values from an external table. In addition to the above you have:
    * `type` select/checkbox/tagmanager/manifest - Input value in Multi-Select-box or 
list of Checkboxes, jQuery TagManager plugin, jQuery Manifest plugin
 * `xlist` Have a single big table with lists for all list-capable fields on your system...

TableField also supports adding pseudo-fields to add more features to a table management - such as displaying calculated values, or adding actions and quick-links.

== Permissions ==
TableField has a per field permission system.
Each field has a list of user-groups that are allowed to view, edit, new and/or delete it's value.

== Inside TABLE ==
Tables are based on a PHP class TfTable.
You can set new tables types in case of need, such as Views, or pseudo-tables.
You can set sub-tables, primary key, html-form-name (for multiple forms on same page), etc.
Table data is actually stored on the tf_info table under row with empty `fname`.
You can add commends, actions (=links) to a table.
Set who is allowed to view, edit or add records to the table.

When editing only the changed values are sent to server, to save a lot of upload time, server processing and bandwidth. The original values are cached locally and new values are compared to original values with javascript.


Params column:
 * `d` Set the default layout of the table. Only b(box)/l(list) for now. s(spreadsheet) is due.

URL parameters:
 * `d` `d=l` List layout, `d=b` Boxes layout, `d=1` Single record layout must have `id=123` as well. `d=q` Quiet mode used for AJAX.
 * `a` `a=v` View mode (default). `a=e` Edit mode. `a=n` Add records (new) mode.
 * `id` id of record to focus on
 * `i` mIni mode. When 1 it will hide unnecessary buttons and make them smaller.
 * `s`+`q` Search. `s`=field name, `q`=search value
 * `pp` Per-Page. How many records to show in each page
 * `p` Current Page 
 * `te` Repeat Title Every that many records. Relevant only to List layout (d=l).
 * `nn` No add New records at the bottom of the Edit page. On by defualt, so please use `nn=0` to do show it.
 * `ne` No Edit mode button
 * `no` No Options - Do not display any toolbar or paging options.
 * `nt` No Topbar - Do not show the top bar
 * `np` No Paging toolbar at the bottom
 * `ns` No Search - hide the search option
 * `sc` Show Const - on Add mode will show the values that cannot be changed.
 * `sv` Show View - TBD


== Inside FIELD ==
All fields classes are PHP classes. It gives huge flexibility and it is the lead advantage of TableField.
As a PHP programmer you can create a full featured data admin solution in just a few days, while meeting any special requirements of your DB and system needs.
As long as it's on MySQL...

==Planned Features==

 * Unit Testing
 * Wordpress's translation system
 * Plugin system for TfTypes
 * Prepare several ready-to-use database templates
 * Field Class base controlled by javascript and jQuery plugins
 * Views - Query-based pseudo tables
 * Spreadsheet mode
 * Single-line/field AJAX editing option

==Known Issues==
(which are not going to be resolved soon...)

 * Only MySQL databases are supported.
 * xkey based classes are allowing too much SQL - potential security risk.
 * Primary keys can only be numeric, no string or multi-fields primary keys.
 * Table Field is not suitable for non-php-programmers and non-programmers.
 * Updates are done manually
 * No backwards compatibility (no need to at the moment, but also not planned)
 * Table Class - Add the option to generate view, edit or add a form in any of the available layouts.
 * Update system - with unit testing

=== UpStream Project Timeline ===
Contributors: upstreamplugin, deenison, andergmartins
Requires at least: 4.5
Tested up to: 4.9
Requires PHP: 5.6
Stable tag: 1.5.1
License: GPL-3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

== Changelog ==

The format is based on [Keep a Changelog](http://keepachangelog.com)
and this project adheres to [Semantic Versioning](http://semver.org).

= [1.5.1] - 2020-01-03 =
* Fix overview bug due to nonstandard characters

= [1.5.0] - 2019-12-22 =
* Now shows orphaned tasks
* Added support for shortcodes

= [1.3.6] - 2019-12-02 =
* Fix for IE11

= [1.3.5] - 2019-11-30 =
* Added option to prohibit drag and drop editing

= [1.3.4] - 2019-11-11 =
* Fixed warning message with empty IDs

= [1.3.3] - 2019-11-09 =
* Fixed potential XSS vulnerability
* Fixed time offset bug when dragging and dropping

= [1.3.2] - 2019-10-15 =
* Added support for advanced permissions

= [1.3.1] - 2019-09-20 =
* Fixed bugs due to timezone differences

= [1.3.0] - 2019-07-29 =

* Added a Timeline Overview page listing more than one project;
* Renamed some methods in the class UpStream_Gantt_Chart;
* Added a new class UpStream_Gantt_Utils;
* Changed the priority of the init method to the default value;
* Changed the minimum version of UpStream version to 1.24.3;
* Changed the timeline to use the project's start and end dates as the date range for the chart;

= [1.2.3] - 2019-07-03 =

* Fixed drag-and-drop for milestones in the timeline;

= [1.2.2] - 2019-05-22 =

* Fixed the color for milestones;
* Remove deprecated method to retrieve milestone titles;

= [1.2.1] - 2019-04-29 =

* Fixed "undefined" text when there is no assigned user to a task;
* Refactored for the new milestone data architecture;
* Added UpStream 1.24 as minimum required version;

= [1.2.0] - 2019-03-07 =

* Added feature to click on items in the timeline to edit;
* Removed text selection on the timeline;
* Increased from 2 to 30 days the range of days before and after the loaded items;
* Added feature to move items in the timeline clicking and dragging by the left edge of items;
* Added feature to resize items (change the duration) clicking and dragging by the right edge of items;

= [1.1.0] - 2018-12-13 =

* Added persistent state to collapsed box in the front-end;
* Added sortable behavior to the timeline panel in the front-end;
* Added ID to the menu item;

= [1.0.21] - 2018-10-03 =

Fixed:
* Fixed JavaScript issue related to Uncaught TypeError: $(...).gantt is not a function;

= [1.0.20] - 2018-08-14 =

Fixed:
* Fixed invalid dates result of some wrong timezone calculation;

= [1.0.19] - 2018-06-20 =

Fixed:
*Fixed wrong dates in the timeline for the first and last days of a month;

= [1.0.18] - 2018-05-29 =

Changed:
* Fixed a PHP warning when a task has no status;
* Fixed the vertical align of the left column when using the zoom;

= [1.0.17] - 2018-04-26 =

Changed:
* Added support for dynamic Milestones, Colors and Statuses

= [1.0.16] - 2018-03-27 =

Fixed:
* Fix potential miscompensation of automatically detected timezones by the user's browser over the chart
* Fixed minor PHP error

= [1.0.15] - 2018-03-08 =

Changed:
* Added support for multiple assignees
* Better End Dates detection

Fixed:
* Fixed Timeline layout when there's no data to be displayed

= [1.0.14] - 2018-02-22 =

Changed:
* Removed no longer required timezone conversions since Start/End Dates will be already on UTC/GMT

Fixed:
* Fixed quotes within "Title"s breaking the timeline

= [1.0.13] - 2018-02-15 =

Changed:
* Month names are now scoped into plugin lang domain
* Minor texts enhancements
* Update year in copyright info

Fixed:
* Days of the week abbreviations were added into the plugin lang domain
* Fixed Tasks Start/End dates timezones

= [1.0.12] - 2018-01-31 =

Fixed:
* Prevent uncommon PHP warning
* Fixed month names not being translated

= [1.0.11] - 2017-12-28 =

Changed:
* Update check process now takes at least half the time it took on previous versions
* Milestones/Tasks are now ordered by Start Date

Fixed:
* Fixed timeline missing dates by 1 day

= [1.0.10] - 2017-10-31 =

Fixed:
* Fixed Timeline appearing on wrong order on sidebar and frontend in some environments

= [1.0.9] - 2017-10-23 =

Changed:
* The Timeline box was moved to below project details
* Code enhancements

Fixed:
* Fixed missing icon on box's title
* Fixed more JS errors

= [1.0.8] - 2017-09-18 =

Updated:
* Enhanced support for translatable strings

= [1.0.7] - 2017-08-21 =

Changed:
* Updated plugin minimum requirements
* Make timeline ux less painful on smaller screens

Fixed:
* Hide Timeline section if Milestones are disabled for a given project

= [1.0.6] - 2017-08-07 =

Fixed:
* Fixed update API URL
* Fixed user chosen display name not being used on the chart

= [1.0.5] - 2017-08-01 =

Changed:
- Enhanced support for internationalization
- Enhancements to the licensing system

Fixed:
- Fixed PHP error thrown when a Task had no one assigned to it

= [1.0.4] - 2017-06-29 =

Fixed:
* Fixed JS dependency errors
* Fixed a PHP error that was being thrown under project specific conditions

= [1.0.3] - 2017-06-26 =

Changed:
* Changed the way license are validated

= [1.0.2] - 2017-06-06 =

Fixed:
* Fixed bug where milestones were being displayed even if they were disabled for the given project
* Fixed language domain

= [1.0.1] - 2017-02-16 =

Fixed:
* Minor fixes. Mainly fixing isset() errors

= [1.0.0] - 2017-01-20 =

* Initial release

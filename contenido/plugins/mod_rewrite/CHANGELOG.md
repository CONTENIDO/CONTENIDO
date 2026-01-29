# Changelog

## 2.1.0

* feat: #529 - PHP 8.4 support. Revise of plugin source code and refactoring. Rename class-files and mark old classes as deprecated. Move functions to classes and mark them as deprecated.

## 1.0.0 - 2.0.0

* #434 - Refactoring and optimizations.
* #396 - Added file extensions *.json & *.mp4 to exclude from rewriting.
* #373 - Enhance translation process.
* #366 - UI: Rework and unify styles.
* #100 - Changed backend CSS- and JS-files aren't loaded after a CONTENIDO update.
* #342 - Compatibility with PHP 8.1.
* #281 - Fix thrown PHP Warnings.
* #273 - ModRewrite Plugin: unnecessary check throws logfile error, and prevent PHP warnings in plugin configuration file.
* #CON-2777 - fixed linendings, fixed return value, client check and error display.
* #CON-2783 - adhere to naming conventions for CONTENIDO functions.
* #CON-2569 - enforced win lineendings for plugins.
* #CON-2749 - Removed not needed, redundant integration of atooltip files in templates.
* #CON-2744 - Fixed usage of undefined variables.
* #CON-2741 - amended plugin docs.
* #CON-2728 - Fixed PHP 7.2 compatibility issues.
* #CON-2566 - Deledted wasted Frontend.CreateURL chain function.
* #CON-2426 - Adopted fix from Erdal.
* #CON-2236 - removed SVN related docs.
* #CON-2396 - Added method to identifier nav_main entries via name, changed all plugins configuration files with navm attribute at nav_sub entries with string instead of integer
* #CON-1764 - Deprecated various resources.
* #CON-2228 - Replaced old with fixed code from Timo.
* #CON-2096 - added rewrite rule for DBFS.
* #CON-1900 - Added English translations for ModRewrite_ContentExpertController class and performed German strings.
* #CON-1898 - Optimized AMR translations.
* #CON-1587 - Disabled required registration of chains in CEC registry.
* #CON-1859 - Changed the notifications to the right color and image.
* #CON-1616 - Extend CONTENIDO backend templates handling.
* #CON-1184 - Install mod_rewrite and url_shortener plugins within navigation "Extras".
* #CON-1389 - replaced preg_replace modifier /e by callback.
* #FFBCON-137 - set request URL to lowerdase if setting says so.
* #CON-1266 - make incomming URL lowercase if option "URLS to lowercase" is set.
* #FFBCON-130 - Fix css for tree, update html head for all templates to 4.01, fix js errors for ie.
* #CON-1225 - Use current set language as a fallback to detect article id by it's urlname.
* #CON-1213 - Fixed the mod_rewrite bug.
* #CON-1197 - Fixed English translation and moved all header notifications into one table row.
* #CON-1197 - Added feature to detect empty aliases and to inform the user about it.
* #CON-572 - Removed CSS from PHP files. ALso fixed some small bugs in plugins.
* #CON-572 - Removed even more CSS from PHP files.
* #CON-972|#CON-1003|#CON-1030 - Removed deprecated code and fixed bugs (Some plugins still use depcrated classes).
* #CON-762 - Reworked url resolving in AMR plug-in for a better/stricter category and article detection.
* #CON-702 - Untranslated and not existing actions.
* #CON-711 - Implement exception handling.
* #CON-704 - contenido_url , ... renamed.
* #CON-84 - Integration of amr-plugin (advanced mod rewrite) into the core package.

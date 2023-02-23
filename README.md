![CONTENIDO Logo](./contenido/images/conlogo.gif)

# CONTENIDO CMS

[DE] CONTENIDO ist ein freies und Open-Source Web-Content-Management-System aus Deutschland.

[EN] CONTENIDO is a free and open source web content management system from Germany.

----

## CONTENIDO Version 4.10.1

**[DE] Lizenz/ [EN] Licence:**

GNU General Public Licence (GPL)

[DE] Um den vollen Funktionsumfang von CONTENIDO nutzen zu können, müssen die folgenden Voraussetzungen erfüllt sein.

[EN] In order to use CONTENIDO with full functionality and without problems, there are several requirements that your system must fulfill.

**Copyright:**

(c) 2000-2019, four for business AG

**[DE] WARNUNG:**

Versionen, die mit Alpha, Beta oder RC markiert sind, sind definitiv nicht für den produktiven Einsatz gedacht!

Keine Haftung und Gewährleistung für mittelbare und unmittelbare Schäden. Weitere Informationen finden Sie in der GPL-Lizenz.

**[EN] WARNING:**

Please do not use versions marked as alpha, beta or RC for productive systems - never.

No Warranty - take a look at the GPL at the end of this file.

----

## [DE] Systemvoraussetzungen für den Einsatz von CONTENIDO / [EN] System requirements for using CONTENIDO

**PHP Version**

| Version | [DE] Beschreibung / [EN] Description |
|---------|--------------------------------------|
|>= 7.0.0 < 8.0.0|[DE] Voll funktionsfähig / [EN] Fully functional|
|>= 8.0.0 < 8.1.0|[DE] Entwicklungs-Branch voll funktionsfähig (\*) / [EN] Develop branch fully functional (\*)|
|>= 8.1.0 < 8.2.0|[DE] Entwicklungs-Branch größtenteils funktionsfähig (\*) / [EN] Develop branch mostly functional (\*)|
|>= 8.2.0|[DE] Entwicklungs-Branch größtenteils funktionsfähig (\*) / [EN] Develop branch mostly functional (\*)|

(*) [DE] Mit PHP Fehlerbehandlung eingestellt auf `error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT` /
[EN] With PHP error reporting set to `error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT`

**[DE] Benötigte PHP Erweiterungen / [EN] Required PHP extensions**

- BC Math (`bcmath`)
- DOM (`dom`)
- Fileinfo (`fileinfo`)
- GD (`gd`)
- iconv (`iconv`)
- JSON (`json`)
- Multibyte String (`mbstring`)
- Mysqli (`mysqli`)
- SimpleXML (`simplexml`)
- Zip (`zip`)

**MySQL/MariaDB Version**

| Version | [DE] Beschreibung / [EN] Description |
|---------|--------------------------------------|
|MySQL 5.5 - 5.7|[DE] nicht im strict-Modus / [EN] not in strict mode|
|MySQL 8.0|[DE] nicht im strict-Modus / [EN] not in strict mode|
|MariaDB 5.5|[DE] nicht im strict-Modus / [EN] not in strict mode|
|MariaDB 10|[DE] nicht im strict-Modus / [EN] not in strict mode|

[DE] MySQL/MariaDB-Server mit folgenden SQL-Modi können die Funktionalität von CONTENIDO beeinträchtigen /
[EN] MySQL/MariaDB servers with the following SQL modes can affect the functionality of CONTENIDO:

- `ONLY_FULL_GROUP_BY`, `STRICT_TRANS_TABLES`, `STRICT_ALL_TABLES`, `NO_ZERO_IN_DATE`, `NO_ZERO_DATE`

[DE] Folgende SQL-Modi werden empfohlen /
[EN] The following SQL modes are recommended:

| Version | [DE] Empfohlener SQL-Modus / [EN] Recommendet SQL mode |
|---------|--------------------------------------------------------|
|MySQL <= 5.7 | `SET GLOBAL sql_mode = 'MYSQL40';` oder / or `SET SESSION sql_mode = 'MYSQL40';` |
|MySQL >= 8.0 | `SET GLOBAL sql_mode = '';` oder / or `SET SESSION sql_mode = '';` |
|MariaDB 5.5 | `SET GLOBAL sql_mode = 'MYSQL40';` oder / or `SET SESSION sql_mode = 'MYSQL40';` |
|MariaDB 10.x | `SET GLOBAL sql_mode = '';` oder / or `SET SESSION sql_mode = '';` |

----

## [DE] Installation und Aktualisierung / [EN] Installation and Upgrade

[DE] Anleitungen zur Installation oder Aktualisierung finden sie im Verzeichnis [docs/](./docs) oder unter der [Online Dokumentation](https://contenido.atlassian.net/wiki/spaces/COND).

[EN] Installation or upgrade guides can be found in the [docs/](./docs) folder or at the [online documentation](https://contenido.atlassian.net/wiki/spaces/CONE).

----

## [DE] Weitere Informationen zu CONTENIDO / [EN] More information on CONTENIDO

- [Homepage](https://www.contenido.org)
- [Community Forum](https://forum.contenido.org)
- [Documentation Portal](https://contenido.atlassian.net/wiki)
- [FAQ](https://www.contenido.org/deutsch/hilfe/faq/index.html)
- [API documentation](https://www.contenido.org/deutsch/hilfe/api-dokumentation/index.html)
- [GitHub](https://github.com/CONTENIDO/CONTENIDO)
- [Twitter](https://twitter.com/contenido)
- [Facebook](https://facebook.com/cms.contenido)

----

## [DE] Lizenzen von verwendeten Produkten / [EN] Licenses of used third party products

|Name & Version|[DE] Lizenz / [EN] Licence|Information|
|--------------|--------------------------|-----------|
|CodeMirror 2.32|CodeMirror license|https://codemirror.net/LICENSE|
|IDNA Converter 0.8.0|GNU Lesser General Public License 2.1|http://phlylabs.de|
|jQuery 1.8.2|MIT License|https://www.jquery.com|
|jQuery timepicker addon 1.0.2|MIT or GPL licenses|https://trentrichardson.com/examples/timepicker/|
|jQuery UI 1.8.23|MIT License|https://www.jqueryui.com|
|Pseudo-Cron (Cron-Emulator) 1.2.1|GNU General Public Licence (GPL)|http://www.bitfolge.de/?l=en&s=pseudocron|
|Smarty 3.1.47|GNU Lesser General Public License 2.1 or later|https://www.smarty.net/|
|SwiftMailer 5.4.6|GNU Lesser General Public License v3|https://swiftmailer.org/|
|TinyMCE 3.5.12|GNU Lesser General Public License|https://www.tinymce.com/|
|TinyMCE 4.1.10|GNU Lesser General Public License 2.1|https://www.tinymce.com/|
|tipsy, facebook style tooltips for jquery 1.0.0a|MIT license|https://onehackoranother.com/projects/jquery/tipsy/|
|Valums AJAX Upload 3.6|MIT license|https://valums.com/ajax-upload/|
|Valums File Uploader 2.0|MIT license, GNU GPL 2 or later, GNU LGPL 2 or later, see license.txt|https://github.com/Valums-File-Uploader/file-uploader|

----

## [DE] Bemerkungen / [EN] Remarks

[DE] Alle Informationen zu Gewährleistung, Garantie und Lizenzbestimmungen finden Sie unter www.contenido.org.

[EN] All information about warranty, guarantee and licence is provided on www.contenido.org.

[DE] Ihr CONTENIDO-Team / [EN] Your CONTENIDO-Team

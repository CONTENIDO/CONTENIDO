![CONTENIDO Logo](./contenido/images/conlogo.gif)

# CONTENIDO CMS

CONTENIDO ist ein freies und Open-Source Web-Content-Management-System aus Deutschland.

----

## CONTENIDO Version 4.10.1

**Lizenz:**

GNU General Public Licence (GPL)

Um den vollen Funktionsumfang von CONTENIDO nutzen zu können, müssen die folgenden Voraussetzungen erfüllt sein.

**Copyright:**

(c) 2000-2019, four for business AG

**WARNUNG:**

Versionen, die mit Alpha, Beta oder RC markiert sind, sind definitiv nicht für den produktiven Einsatz gedacht!

Keine Haftung und Gewährleistung für mittelbare und unmittelbare Schäden. Weitere Informationen finden Sie in der GPL-Lizenz.

----

## Systemvoraussetzungen für den Einsatz von CONTENIDO

**PHP Version**

| Version          | Beschreibung                                    |
|------------------|-------------------------------------------------|
| >= 7.1 && < 8.0  | Letztes Release voll funktionsfähig             |
| >= 7.1 && <= 8.4 | develop-Branch größtenteils funktionsfähig (\*) |

(*) Mit PHP Fehlerbehandlung eingestellt auf `error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT`

**Benötigte PHP Erweiterungen**

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

| Version     | Beschreibung          |
|-------------|-----------------------|
| MySQL 5.7   | nicht im strict-Modus |
| MySQL 8.0   | nicht im strict-Modus |
| MariaDB 5.5 | nicht im strict-Modus |
| MariaDB 10  | nicht im strict-Modus |

MySQL/MariaDB-Server mit folgenden SQL-Modi können die Funktionalität von CONTENIDO beeinträchtigen:

`ONLY_FULL_GROUP_BY`, `STRICT_TRANS_TABLES`, `STRICT_ALL_TABLES`, `NO_ZERO_IN_DATE`, `NO_ZERO_DATE`

Folgende SQL-Modi werden empfohlen:

| Version      | Empfohlener SQL-Modus                                                            |
|--------------|----------------------------------------------------------------------------------|
| MySQL 5.7    | `SET GLOBAL sql_mode = 'MYSQL40';` oder / or `SET SESSION sql_mode = 'MYSQL40';` |
| MySQL >= 8.0 | `SET GLOBAL sql_mode = '';` oder / or `SET SESSION sql_mode = '';`               |
| MariaDB 5.5  | `SET GLOBAL sql_mode = 'MYSQL40';` oder / or `SET SESSION sql_mode = 'MYSQL40';` |
| MariaDB 10.x | `SET GLOBAL sql_mode = '';` oder / or `SET SESSION sql_mode = '';`               |

----

## Installation und Aktualisierung

Anleitungen zur Installation oder Aktualisierung finden sie im Verzeichnis [docs/](./docs) oder unter der [Onlinedokumentation](https://contenido.atlassian.net/wiki/spaces/COND).

----

## Weitere Informationen zu CONTENIDO

- [Homepage](https://www.contenido.org)
- [Community Forum](https://forum.contenido.org)
- [Documentation Portal](https://contenido.atlassian.net/wiki)
- [FAQ](https://www.contenido.org/deutsch/hilfe/faq/index.html)
- [API documentation](https://www.contenido.org/deutsch/hilfe/api-dokumentation/index.html)
- [GitHub](https://github.com/CONTENIDO/CONTENIDO)
- [Twitter](https://twitter.com/contenido)
- [Facebook](https://facebook.com/cms.contenido)

----

## Lizenzen von verwendeten Produkten

| Name & Version                                   | Lizenz                                                                | Information                                           |
|--------------------------------------------------|-----------------------------------------------------------------------|-------------------------------------------------------|
| CodeMirror 2.32                                  | CodeMirror license                                                    | https://codemirror.net/LICENSE                        |
| IDNA Converter 0.8.0                             | GNU Lesser General Public License 2.1                                 | https://phlylabs.de                                   |
| jQuery 1.8.2                                     | MIT License                                                           | https://www.jquery.com                                |
| jQuery timepicker addon 1.0.2                    | MIT or GPL licenses                                                   | https://trentrichardson.com/examples/timepicker/      |
| jQuery UI 1.8.23                                 | MIT License                                                           | https://www.jqueryui.com                              |
| normalize.css v8.0.1                             | MIT License                                                           | https://github.com/necolas/normalize.css              |
| Pseudo-Cron (Cron-Emulator) 1.2.1                | GNU General Public Licence (GPL)                                      | https://www.bitfolge.de/?l=en&s=pseudocron            |
| Smarty 4.5.6                                     | GNU Lesser General Public License 2.1 or later                        | https://www.smarty.net/                               |
| Swift Mailer 5.4.12                              | GNU Lesser General Public License v3                                  | https://swiftmailer.org/                              |
| TinyMCE 3.5.12                                   | GNU Lesser General Public License                                     | https://www.tinymce.com/                              |
| TinyMCE 4.9.11                                   | GNU Lesser General Public License 2.1                                 | https://www.tinymce.com/                              |
| tipsy, facebook style tooltips for jquery 1.0.0a | MIT license                                                           | https://onehackoranother.com/projects/jquery/tipsy/   |
| Valums AJAX Upload 3.6                           | MIT license                                                           | https://valums.com/ajax-upload/                       |
| Valums File Uploader 2.0                         | MIT license, GNU GPL 2 or later, GNU LGPL 2 or later, see license.txt | https://github.com/Valums-File-Uploader/file-uploader |

----

## Bemerkungen

Alle Informationen zu Gewährleistung, Garantie und Lizenzbestimmungen finden Sie unter www.contenido.org.

Ihr CONTENIDO-Team

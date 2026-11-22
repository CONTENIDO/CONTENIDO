![CONTENIDO Logo](./contenido/images/conlogo.gif)

# CONTENIDO CMS

CONTENIDO is a free and open-source web content management system from Germany.

----

## CONTENIDO Version 4.11.0-alpha

**Licence:**

GNU General Public Licence (GPL)

In order to use CONTENIDO with full functionality and without problems, there are several requirements that your system must fulfill.

**Copyright:**

(c) 2000-2019, four for business AG

**WARNING:**

Please do not use versions marked as alpha, beta or RC for productive systems - never.

No Warranty - take a look at the GPL at the end of this file.

----

## System requirements for using CONTENIDO

**PHP Version**

| Version          | Description                           |
|------------------|---------------------------------------|
| >= 7.1 && < 8.0  | Latest release fully functional       |
| >= 8.0 && <= 8.4 | Develop branch mostly functional (\*) |

(*) With PHP error reporting set to `error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT`

**Required PHP extensions**

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

| Version     | Description         |
|-------------|---------------------|
| MySQL 5.7   | not in strict mode  |
| MySQL 8.0   | not in strict mode  |
| MariaDB 5.5 | not in strict mode  |
| MariaDB 10  | not in strict mode  |

MySQL/MariaDB servers with the following SQL modes can affect the functionality of CONTENIDO:

`ONLY_FULL_GROUP_BY`, `STRICT_TRANS_TABLES`, `STRICT_ALL_TABLES`, `NO_ZERO_IN_DATE`, `NO_ZERO_DATE`

The following SQL modes are recommended:

| Version      | Recommended SQL mode                                                             |
|--------------|----------------------------------------------------------------------------------|
| MySQL 5.7    | `SET GLOBAL sql_mode = 'MYSQL40';` oder / or `SET SESSION sql_mode = 'MYSQL40';` |
| MySQL >= 8.0 | `SET GLOBAL sql_mode = '';` oder / or `SET SESSION sql_mode = '';`               |
| MariaDB 5.5  | `SET GLOBAL sql_mode = 'MYSQL40';` oder / or `SET SESSION sql_mode = 'MYSQL40';` |
| MariaDB 10.x | `SET GLOBAL sql_mode = '';` oder / or `SET SESSION sql_mode = '';`               |

----

## Installation and Upgrade

Installation or upgrade guides can be found in the [docs/](./docs) folder or at the [online documentation](https://contenido.atlassian.net/wiki/spaces/CONE).

----

## More information on CONTENIDO

- [Homepage](https://www.contenido.org)
- [Community Forum](https://forum.contenido.org)
- [Documentation Portal](https://contenido.atlassian.net/wiki)
- [FAQ](https://www.contenido.org/deutsch/hilfe/faq/index.html)
- [API documentation](https://www.contenido.org/deutsch/hilfe/api-dokumentation/index.html)
- [GitHub](https://github.com/CONTENIDO/CONTENIDO)
- [X](https://x.com/contenido)
- [Facebook](https://facebook.com/cms.contenido)

----

## Licenses of used third party products

| Name & Version                                   | Licence                                                               | Information                                           |
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

## Remarks

All information about warranty, guarantee and licence is provided on www.contenido.org.

Your CONTENIDO-Team

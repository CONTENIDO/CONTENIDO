Advanced Mod Rewrite Plugin 0.5.5 für Contenido 4.8.1x

####################################################################################################
TOC (Table of contents)

- BESCHREIBUNG
- CHANGELOG
- BEKANNTE BUGS
- FEATURES
- VORAUSSETZUNGEN
- INSTALLATION
  * ALLGEMEIN
  * UPDATE AUF VERSION >= 0.5.0
  * ANPASSEN DER MODULE DES BEISPIELMANDANTEN
- WICHTIGES ZUM INHALT
  * ALLGEMEIN
  * VERSION >= 0.5.0
- FAQ
- ADVANCED MOD REWRITE THEMEN IM CONTENIDO FORUM
- SCHLUSSBEMERKUNG



####################################################################################################
BESCHREIBUNG

Das Plugin Advanced Mod Rewrite ist eine Erweiterung für das Contenido-CMS zur Generierung von 
alternativen URLs.

Normalerweise werden die URLs zu Seiten einer auf ein CMS (z. B. Contenido) basierenden 
Webpräsenz nach dem Muster "/index.php?page=12&amp;language=de" generiert, also in Form von 
dynamischen URLs. Eine Möglichkeit solche dynamische URLs zu Webseiten, deren Inhalt in der Regel 
aus Datenbanken kommen, gegen statische URLs wie z. B. "/de/page-name.html" umzustellen, gibt es in 
Kombination mit dem Apache mod_rewrite-Modul. Dabei werden die URLs zu den Seiten als sogenannte 
"Clean URLs" ausgegeben, Requests zur der gewünschten Ressource werden vom Webserver nach 
definierten Regeln verarbeitet und intern an die Webanwendung weitergeleitet.

Solche statische URLs können aufgrund der Keyworddichte (die Ressource beschreibende Wörter in der 
URL) vorteilhaft für Suchmaschinen sein und User können sich die URLs einfacher merken.

Bei einer Contenido-Installation lassen sich solche URLs mit dem Advanced Mod Rewrite Plugin 
generieren, URLs zu Frontendseiten, wie z. B. "/cms/front_content.php?idart=12&amp;lang=1" werden 
vom Plugin als statische URLs wie "/de/page-name.html" ausgegeben. Diverse Einstellungen zum 
Ausgabeformat der URLs lassen sich im Contenido-Backend konfigurieren.

Das Plugin Advanced Mod Rewrite basiert auf die geniale Erweiterung Advanced Mod Rewrite für 
Contenido, welches als Bundle von stese bis zur Contenido Version 4.6.15 entwickelt und betreut 
wurde.

Wichtiger Aspekt bei der Umsetzung war die Implementierung als Plugin mit so wenig wie möglichen 
Eingriffen in den Contenido Core (trotzdem ging es nicht ohne einige Anpassungen an bestimmten 
Sourcen).

Daher enthält das Archiv einige überarbeitete Contenido Sourcen, die eventuell nicht auf dem 
neuesten Stand sein können.



####################################################################################################
CHANGELOG

xxxx-xx-xx 
    * Initial setting of empty category and article aliases


2010-02-23  Advanced Mod Rewrite Plugin 0.5.5 for Contenido >=4.8.10
    * bugfix: Fixed some potential security vulnerabilities
    * bugfix: Replaced german umlauts which may result in broken characters depending on configured encoding
    * bugfix: Category synchronization sets now urlpath of synchronized categories
    * bugfix: Renaming of categories updates also urlpaths of subcategories
    * bugfix: Removed old plugin related entries from related tables
    * bugfix: Takeover query parameter of routed URLs into superglobal $_GET

2009-05-10  Advanced Mod Rewrite Plugin 0.5.4 for Contenido >=4.8.10
    * bugfix: Wrong URL generation to articles having same alias in different languages
    * bugfix: Added missing preclean of previous percieved URLs to URL stack handler
    * bugfix: Deactivated plugin related header output in mr_header()
    * bugfix: Some comments in htaccess_simple.txt caused an server error
    * change: Removed plugins CEC 'Contenido.Frontend.CreateURL' from Contenido chain configuration 
              to plugins configuration
    * new:    Added handling of ports in URLs and also URL query items being an array (e. g. foo[bar]=1)
              to UrlBuilder

2009-02-08  Advanced Mod Rewrite Plugin 0.5.3 for Contenido >=4.8.10
    * bugfix: Occurance of invalid frontend URLs result in invalid rewritten URLs to root
    * bugfix: Defined separator modified client path, which is not desired

2009-01-18  Advanced Mod Rewrite Plugin 0.5.2 for Contenido >=4.8.10
    * change: Adapted to Contenido 4.8.10
    * change: Additional .htaccess with easy to handle rewrite rules
    * bugfix: Calling the method getmicrotime() ends in PHP error during Contenido installation
              (thanks to josh, see http://forum.contenido.org/viewtopic.php?p=126526#126526)
    * bugfix: Corrected missing language switch detection
              (thanks to lunsen_de, see http://forum.contenido.org/viewtopic.php?p=126873#126873)

2008-12-24  Advanced Mod Rewrite Plugin 0.5.1 for Contenido 4.8.9
    * change: Adapted to Contenido 4.8.9
    * change: Includes the fixes for redirection issues
              (see http://forum.contenido.org/viewtopic.php?t=22976)

2008-12-21  Advanced Mod Rewrite Plugin 0.5.0 for Contenido 4.8.8
    * bugfix: Fixed invalid creation of URLs
              (thanks to Polardrache, see http://forum.contenido.org/viewtopic.php?p=125714#125714)
    * bugfix: Added case sensitive handling of paths to plugins path resolver
    * change: Some cleanup

2008-11-25 Advanced Mod Rewrite Plugin 0.5.0rc for Contenido 4.8.8
    * bugfix: Corrected wrong handling of similar categories/articles in different languages 
              (thanks to Tbird, see http://forum.contenido.org/viewtopic.php?p=123523#123523)
    * change: Adaption of sources to PHP5 coding standards
    * change: Redesigned handling of userdefined category-/article separators
    * change: Modified plugin installer (source and template)
    * new:    Feature to reduce database queries during URL creation

2008-09-08 Advanced Mod Rewrite Plugin 0.4.5 for Contenido 4.8.8
    * change: Adapted to Contenido 4.8.8
    * change: Moved index_controller.php into plugin include dir, .htaccess substitutes now to 
              front_content.php
    * change: Enhanced resolving process of incomming urls
    * bugfix: Wrong resolving of paths if option "Create categories and articles as HTML ressource" 
              is selected
              (thanks to speedmaster, see http://forum.contenido.org/viewtopic.php?p=122821#122821)
    * new:    Added new Chain to process resolving at front_content.php

2008-09-03 Advanced Mod Rewrite Plugin 0.4.4 for Contenido 4.8.7
    * bugfix: Wrong URL creation in front_content.php
              (thanks to mojo, see http://forum.contenido.org/viewtopic.php?p=121749#121749)
    * bugfix: Corrected invalid replacement for base href interpretation
              (thanks to Tbird, see http://forum.contenido.org/viewtopic.php?p=122710#122710)
    * bugfix: Fixed URL creation, now Urls starting with '/front_...' or './front_...' will also
              be identified, affects also defined redirect urls in article properties
    * change: Replaced adding of PHP4 handler against PHP5 handler in .htaccess
              (thanks to Supporter, see http://forum.contenido.org/viewtopic.php?p=122821#122821)
    * new:    New client setting for articles, which are to exclude from output processing, example
              Type                      Name              Value
              frontend.no_outputbuffer  idart             11,12,34

2008-08-12 Advanced Mod Rewrite Plugin 0.4.3 for Contenido 4.8.7
    * bugfix: Added missing framework initialization to plugin installer
    * bugfix: Workaround for non set variables in globals_off.inc.php
    * bugfix: Corrected wrong handling of configuration parameter 'startfromroot'
    * bugfix: Article word separator was not used correct
    * change: New rule to catch another exploit in .htaccess
    * change: Improved performance of class ModRewrite
    * change: Extended ConfigBase and ConfigSerializer, added lifetime control for cached data
    * new:    Testscript (/cms/mr_test.php) to validate functionality of the plugin

2008-08-03 Advanced Mod Rewrite Plugin 0.4.0 for Contenido 4.8.7
    * change: Adapted to Contenido 4.8.7
    * bugfix: Error at validation of defined .htaccess file, if client docroot differs from Contenido 
              backend docroot
              (thanks to tono, see http://forum.contenido.org/viewtopic.php?p=120731#120731)
    * bugfix: Prevention of duplicated content, which occurs, if  prepending of root category to the 
              url is activated
              (thanks to philla, see http://forum.contenido.org/viewtopic.php?p=120618#120618)
    * change: Enabling routing definition from root
    * change: Removed execution of CEC_Hook to build URLs from $sess->url() and $sess->self_url()
    * new:    New Contenido_UrlBuilder_MR class based on Contenido UrlBuilder specifications.
              NOTE: Plugin is no more PHP4 compatible!

2008-07-20 Advanced Mod Rewrite Plugin 0.3.3 for Contenido 4.8.6
    * new:    Some new rules in htaccess to precatch common exploits
    * change: Removal of nonused function mr_get_setting_override()
    * bugfix: Calling of parse_url results in a PHP warning, if the URL is invalid
    * bugfix: Instantiating of cApiArticleLanguage throws an error in some cases
              (thanks to TripleM, see http://forum.contenido.org/viewtopic.php?p=120552#120552)
    * bugfix: Usage of $auth->url()/$auth->purl() returns Scriptname which isn't front_content.php
              (thanks to stefkey, see http://forum.contenido.org/viewtopic.php?p=120262#120262)

2008-06-22 Advanced Mod Rewrite Plugin 0.3.2 for Contenido 4.8.6
    * bugfix: Corrected wrong handling of category aliases
              (thanks to Supporter, see http://forum.contenido.org/viewtopic.php?p=119352#119352)

2008-06-18 Advanced Mod Rewrite Plugin 0.3.1 for Contenido 4.8.6
    * new:    Added SQL-Statements for pluginupdate
    * change: Adapted to Contenido 4.8.6
    * bugfix: Later setting of article urlname won't work
              (see http://forum.contenido.org/viewtopic.php?p=118754#118754)

2008-05-26 Advanced Mod Rewrite Plugin 0.3.0 for Contenido 4.8.4
    * change: Adapted to Contenido 4.8.4
    * bugfix: Usage of new configuration in ModRewriteController::_setIdart() instead of 
              clientsetting
    * new:    Feature to add default articlenames (userdefined name or name of startarticle) to 
              created category URLs
    * change: Some cleanup and improvement of query execution

2008-05-20: Advanced Mod Rewrite Plugin 0.2.1rc for Contenido 4.8.x
    * bugfix: Hard coded path in include.mod_rewrite_content.php (thanks to tono)
    * bugfix: Added file_put_contents() function in file class.confighandler.php to support PHP4
              (thanks to tono)
    * bugfix: Added missed handling of defined rootdir for .htaccess file in 
              class.modrewritecontroller.php (thanks to tono)

2008-05-19: Advanced Mod Rewrite Plugin 0.2rc
    * first release



####################################################################################################
BEKANNTE BUGS

Urls sollten unbedingt eine Endung wie z. B. '.html' bekommen, da ansonsten die Erkennungsroutine den
Artikel aus der ankommenden URL nicht ermitteln kann.

Wenn der Clean-URL die Sprache oder der Mandant vorangestellt wird, funktioniert die Fehlererkennung 
unter Unständen nicht richtig, d. h. es gibt keine Weiterleitung zur Fehlerseite, sofern dies im 
Plugin eingestellt wurde.



####################################################################################################
FEATURES

- Erstellung Suchmaschinenoptimierter URLs, Contenido interne URLs wie 
  /front_content.php?idcat=12&idart=34 werden z. B. als /kategoriename/artikelname.html umschrieben
- Unterstützung mehrerer Sprachen 
- Unterstützung mehrerer Mandanten im gleichen Verzeichnis 
- Umschreiben der URLs entweder bei der Ausgabe des HTML Codes oder beim Generieren des Codes der 
  Seiten 
- Routing von URLs (Umleiten eingehender URLs auf andere Ziel-URLs)



####################################################################################################
VORAUSSETZUNGEN

- Alle Voraussetzungen von Contenido 4.8.x gelten auch für das Plugin
- PHP ab Version 5.1 (Das Plugin war bis Version 0.3.3 PHP 4.4.x kompatibel)
- Apache HTTP Server 2 mit Mod Rewrite 



####################################################################################################
INSTALLATION

ALLGEMEIN
=========

- Backup der Contenido Installation also der Sourcen und der Datenbank (Damit es ein Weg zurück gibt) 
- Kopieren aller Dateien in die entsprechenden Verzeichnisse. 
- Schreibrechte für PHP in das Verzeichnis /contenido/plugins/mod_rewrite/includes/ setzen. Das 
  Plugin speichert die Advanced Mod Rewrite Konfiguration der Mandanten in das Verzeichnis.
  (Der einfachste Weg ist das Setzen der Rechte auf 777, empfohlen ist eine restriktivere Vergabe) 
- In die Adresszeile des Browsers http://localhost/contenido/plugins/mod_rewrite/install.php 
  eingeben, dann sollte das Anmeldefenster des Backends erscheinen.
  ("http://localhost/" ist eventuell gegen anderen virtual Host oder Domainnamen ersetzen) 
- Im Backend anmelden
  TIP: Sollte der Plugininstaller nach der Anmeldung nicht erscheinen, kann die URL zum Installer 
  manuell aufgerufen werden. Der URL muss die aktuell gültige Contenido Session-ID angehängt werden.
  Beispiel: http://localhost/contenido/plugins/mod_rewrite/install.php?contenido={my_session_id}
- Advanced Mod Rewrite Plugin installieren 
  HINWEIS: Der Plugininstaller erstellt eine Kopie der Tabelle "{prefix}_plugins_{YYYYMMDD}", falls 
  die Tabelle die Voraussetzungen des Plugins nicht erfüllt. Wenn vorher Plugins installiert wurden, 
  müssen die Einträge von der Kopie der Tabelle manuell in die neue Tabelle übernommen werden. 
- Advanced Mod Rewrite konfigurieren (Im Backend unter Menü "Content" -> "Advanced Mod Rewrite") 
- Die .htaccess Datei (kommt in der Regel in das wwwroot) bei Bedarf anpassen

Weitere Hinweise zur Installation/zu Upgrades:
Sollte ein Ugrade oder eine Neuinstallation des Plugins nötig sein, weil z. B. die Contenido-
Version einem Upgrade unterzogen wurde, ist auch ein Upgrade für das Plugin nachzuziehen. Eine 
ausführliche Beschreibung dazu gibt es unter
http://forum.contenido.org/viewtopic.php?p=119362#119362


UPDATE AUF VERSION >= 0.5.0
===========================
Die Behandlung der benutzerdefinierten Seperatoren wurde in der Version 0.5.0 (rc) grundlegend 
geändert. Daher sollte bei einem Update des Plugins von Version <= 0.4.5 auf Version >= 0.5.0 die 
Konfiguration gegebenenfalls angepasst werden - es kann sein, dass die vorher gesetzten Seperatoren 
bei einem Update nicht korrekt erkannt und übernommen werden.


ANPASSEN DER MODULE DES BEISPIELMANDANTEN
=========================================
Die Contenido Module des Beispielmandanten verwenden seit der Contenido-Version 4.8.11, die neue 
UrlBuilder-Funktionalität.
In den Modulcodes werden URLs generiert, die nicht Kompatibel mit dem AMR-Plugin sind - Daher sind
an den Modulen noch kleinere Anpassungen nötig. Diese sind beschrieben unter
http://forum.contenido.org/viewtopic.php?f=66&t=23501


####################################################################################################
WICHTIGES ZUM INHALT

ALLGEMEIN
=========

.htaccess:
----------
Die Konfiguration des Apache, in der das mod_rewrite-Modul aktiviert und mit diversen Anweisungen 
konfiguriert wird. Die Einstellungen bewirken, dass ankommende Anfragen wie z. B. 
/kategorie/artikel.html an die front_content.php im Mandantenverzeichnis weitergeleitet werden.

Seit der Version 0.5.2 gibt es 2 Vorlagen der Datei, die htaccess_restrictive.txt und die 
htaccess_simple.txt.

htaccess_restrictive.txt:
Enthält Regeln mit restriktiveren Einstellungen.
Alle Anfragen, die auf die Dateienendung js, ico, gif, jpg, jpeg, png, css, pdf gehen, werden vom 
Umschreiben ausgeschlossen. Alle anderen Anfragen, werden an front_content.php umschrieben.
Ausgeschlossen davon sind 'contenido/', 'setup/', 'cms/upload', 'cms/front_content.php', usw.
Jede neue Ressource, die vom Umschreiben ausgeschlossen werden soll, muss explizit definiert werden.

htaccess_simple.txt:
Enthält eine einfachere Sammlung an Regeln. Alle Anfragen, die auf gültige symlinks, Verzeichnisse oder 
Dateien gehen, werden vom Umschreiben ausgeschlossen. Restliche Anfragen werden an front_content.php
umschrieben.

Der Inhalt der .htaccess ist enthält die Regeln aus htaccess_restrictive.txt. Auf Wunsch kann es auch 
mit dem Inhalt der htaccess_simple.txt ersetzt werden.


contenido/plugins/mod_rewrite/*:
--------------------------------
Die Sourcen des Plugins.

contenido/classes/mp/*:
-----------------------
Zusätzliche Klassen für Konfiguration, Debugging, Zugriff auf $GLOBALS, die vom Plugin verwendet werden 
aber nicht explizit für das Plugin implementiert wurden.

contenido/classes/UrlBuilder/Contenido_UrlBuilder_MR.class.php:
---------------------------------------------------------------
UrlBuilder Klasse des Plugins (seit Version 0.4.0), zum Generieren der URLs anhand der Pluginkonfiguration. 
Verwendet die in den Contenido Core implementierte UrlBuilder-Funktionalität und erweitert diesen um die 
pluginspezifischen Features.


VERSION >= 0.5.0
================

Handhabung von Seperatoren:
Die Aliase für Kategorie-/ und Artikelnamen werden im Gegensatz zu früheren Versionen nicht mit den
in der Pluginkonfiguration definierten Trennzeichen gespeichert, sondern mit den in Contenido per 
default gesetzten Trennzeichen "-". Die Entscheidung zur dieser Vorgehensweise wurde gefällt, um der 
Contenidoinstallation nicht die Pluginkonfiguration "aufzuzwingen", sondern die pluginspezifischen 
Einstellungen "on the fly" während der Ausgabe zu setzen.

Die Flexibilität, die das Plugin über das Setzen der benutzerdefinierten Seperatoren bietet, bedarf 
einer einheitlichen, maschinell verarbeitbaren Struktur der Aliase, daher sind Aliase, die aus 
mehreren Wörtern oder mit Leerzeichen zusammengesetzten Zeichen bestehen, mit einem Bindestrich 
"-" anzugeben.

Bei der Ausgabe der URLs, werden dann die Bindestriche gegen die in der Pluginkonfiguration gesetzten 
Seperatoren ersetzt - Beispiel:
[code]
Artikelname:           Contenido Highlights
Artikelalias:          Contenido-Highlights
Artikelwort-Separator: _
Ausgabe in der URL:    Contenido_Highlights

Kategoriename:           Was ist Contenido
Kategoriealias:          Was-ist-Contenido
Kategoriewort-Separator: ~
Ausgabe in der URL:      Was~ist~Contenido
[/code]

Der Nachteil dabei ist natürlich, dass man bei der Vergabe der Aliase nicht mehr flexibel ist, und 
sich an die Vorgaben des Plugins richten muss.



####################################################################################################
FAQ

Der Plugininstaller lässt sich nicht aufrufen, wie kann ich dennoch das Plugin installieren?
--------------------------------------------------------------------------------------------
Normalerweise wird der Plugininstaller mit folgender URL aufgerufen:
http://localhost/contenido/plugins/mod_rewrite/install.php
("http://localhost/" ist eventuell gegen anderen virtual Host oder Domainnamen ersetzen)

Es erscheint das Anmeldeformular zum Backend, über den man sich am System anmelden kann. Nach 
erfolgreicher Anmeldung wird man normalerweise zum Plugininstaller weitergeleitet.

Manchmal kann es vorkommen, dass die Weiterleitung nach der Anmeldung nicht klappt und man nicht den 
Plugininstaller aufrufen kann.
Um dennoch den Installer aufzurufen, reicht es aus, der URL die aktuell gültige Contenido Session ID
anzuhängen, z. B. /contenido/plugins/mod_rewrite/install.php?contenido={my_session_id}.


Wie teste ich, ob mod_rewrite am Server richtig konfiguriert ist?
-----------------------------------------------------------------
Obwohl mod_rewrite am Server installiert ist, kommt es manchmal vor, dass es nicht funktioniert.

Das kann einfach getestet werden, erstelle eine .htaccess im Rootverzeichnis und schreibe folgendes 
rein:
[code]
RewriteEngine on
RewriteRule ^ http://www.contenido.org [R,L]
[/code]

Nach Eingabe der URL in die Adresszeile des Browsers, sollte auf www.contenido.org weitergeleitet 
werden.
Wenn nicht, dann kann eines der folgenden Punkte der Grund dafür sein:
Das mod_rewrite Modul ist nicht geladen, das ist in der httpd.conf zu setzen
[code]
LoadModule rewrite_module modules/mod_rewrite.so
[/code]

Die Direktive "AllowOverride" ist nicht korrekt gesetzt. Damit die Angaben in der .htaccess auch 
benützt werden können, muss für das betreffende Verzeichnis die Direktive "AllowOverride" in der 
httpd.conf angegeben werden:
[code]
# Beispielkonfiguration
<Directory "/var/www/mywebproject">
    AllowOverride FileInfo
</Directory>
[/code]

 
Wie richte ich Advanced Mod Rewrite für eine Contenidoinstallation in einem Unterverzeichnis ein?
-------------------------------------------------------------------------------------------------

Als Beispiel gehen wir davon aus, dass Contenido im Verzeichnis /mypage/ unterhalb vom Webroot 
installiert wurde und das Mandantenverzeichnis per default /mypage/cms/ ist.

In der Pluginkonfiguration (Backend) den Pfad zur .htaccess Datei (aus Sicht des Web-Browsers) 
folgendermaßen anpassen:
[code]
/mypage/
[/code]

Die /mypage/.htaccess öffnen und die RewriteBase folgendermaßen anpassen:
[code]
RewriteBase /mypage/cms/
[/code]


Welche Einstellungen sind nötig, wenn das Mandantenverzeichnis das wwwroot ist?
-------------------------------------------------------------------------------
Normalerweise liegt das Mandantenverzeichnis innerhalb des wwwroot und ist über 
http://domain.tld/cms/front_content.php erreichbar.
Manchmal ist es erwünscht, dass der Ordner /cms/ in der URL nicht sichbar sein soll, also 
erreichbar über http://domain.tld/front_content.php.

In diesem Fall sind zwei Anpassungen nötig, damit Mod Rewrite korrekt funktioniert:
1. Die .htaccess Datei in das Verzeichnis /cms/ kopieren, da die Datei im wwwroot sein muss.
2. In der .htaccess die RewriteBase Option anpassen 

# von
RewriteBase /cms

# auf
RewriteBase / 


Wie kann ich das Verarbeiten bestimmter Seiten vom Plugin unterbinden?
----------------------------------------------------------------------
Wenn das Plugin so konfiguriert wurde, dass die URLs bei der Ausgabe des HTML Codes der Seite 
angepasst werden, kann dieses Verhalten bei manchen Seiten unerwünscht sein. Das kann bei einer 
Ausgabe der Fall sein, dessen Inhalt kein HTML ist (z. B. Dateidownload), dann macht es keinen Sinn, 
die Ausgabe anzupassen.

Ab Contenido 4.8.8 gibt es eine neue Einstellung, mit der man unterbinden kann, dass die Ausgabe im 
Frontend nicht in den Ausgabepuffer geschrieben wird. Ist dies für eine Seite definiert worden, wird 
auch die Funktion vom Plugin, die die URLs anpasst, nicht ausgeführt.

Einstellen lässt sich das über Mandanteneinstellungen wie folgt:
[code]
Typ                          Name     Wert
frontend.no_outputbuffer     idart    12,14,40
[/code]
Inhalte der Artikel mit der id 12, 14 und 40 werden dann von der Ausgabepufferung ausgeschlossen.


Warum werden URLs trotz richtiger Vorraussetzungen nicht umschrieben?
---------------------------------------------------------------------
Ist die .htaccess und die Konfiguration des Plugins als Fehlerquelle auszuschließen und das Plugin 
soll die URLs bei der Ausgabe der Seite umschreiben (Standardeinstellung), könnte ein vorzeitig 
geleerter Ausgabepuffer der Grund sein.

In der front_content.php wird der HTML-Code in den Ausgabepuffer geschrieben, damit der Code vor der 
endgültigen Ausgabe bearbeitet werden kann. Das Plugin fügt der Chain 
"Contenido.Frontend.HTMLCodeOutput" eine eigene Funktion, die den Code aus dem Ausgabepuffer erhält, 
um die darin URLs zu umschreiben.

Wird aber der Ausgabepuffer vorher geleert, z. B. durch Verwendung von ob_flush() in einem Modul, 
wird der Code direkt an den Client rausgeschickt. Das hat den Effekt, dass in der front_content.php 
kein Code mehr aus dem Ausgabepuffer zur Verfügung steht, der nicht weiterverarbeitet werden kann, 
auch das Plugin kann dann keine URLs umschreiben.


Alle URLs zu Kategorien werden mit / oder /index.html umschrieben:
------------------------------------------------------------------
Ist Contenido mit der Konfiguration $cfg["is_start_compatible"] = true; 
(siehe contenidoincludes/config.php) eingestellt, um die Startartikeldefinition in Kategorien 
kompatibel zu älteren Contenido-Versionen halten, kann das Plugin die URLs zu Kategorien nicht
generieren, weil es diese Konfiguration nicht unterstützt.

Die einfachste Lösung ist, die Konfiguration $cfg["is_start_compatible"] auf false zu setzen und im 
Backend in den vorhandenen Kategorien erneut die Startartikel zu setzen.



####################################################################################################
ADVANCED MOD REWRITE THEMEN IM CONTENIDO FORUM

Plugin Advanced Mod Rewrite für Contenido 4.8.x:
http://www.contenido.de/forum/viewtopic.php?t=21578

Original Advanced Mod Rewrite 4.6.23:
http://www.contenido.de/forum/viewtopic.php?t=18454

Original Advanced Mod Rewrite 4.6.15:
http://www.contenido.de/forum/viewtopic.php?t=11162

Advanced Mod Rewriting Contenido 4.4.4:
http://www.contenido.de/forum/viewtopic.php?t=6713



####################################################################################################
SCHLUSSBEMERKUNG

Benutzung des Plugins auf eigene Gefahr!

Murat Purc, murat@purc.de

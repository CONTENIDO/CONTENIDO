Advanced Mod Rewrite Plugin f�r CONTENIDO >= 4.9.0

################################################################################
TOC (Table of contents)

- BESCHREIBUNG
- CHANGELOG
- BEKANNTE BUGS
- FEATURES
- VORAUSSETZUNGEN
- INSTALLATION
- FAQ
- ADVANCED MOD REWRITE THEMEN IM CONTENIDO FORUM
- SCHLUSSBEMERKUNG



################################################################################
BESCHREIBUNG

Das Plugin Advanced Mod Rewrite ist eine Erweiterung f�r das CONTENIDO-CMS zur Generierung von 
alternativen URLs.

Normalerweise werden die URLs zu Seiten einer auf ein CMS (z. B. Contenido) basierenden 
Webpr�senz nach dem Muster "/index.php?page=12&amp;language=de" generiert, also in Form von 
dynamischen URLs. Eine M�glichkeit solche dynamische URLs zu Webseiten, deren Inhalt in der Regel 
aus Datenbanken kommen, gegen statische URLs wie z. B. "/de/page-name.html" umzustellen, gibt es in 
Kombination mit dem Apache mod_rewrite-Modul. Dabei werden die URLs zu den Seiten als sogenannte 
"Clean URLs" ausgegeben, Requests zur der gew�nschten Ressource werden vom Webserver nach 
definierten Regeln verarbeitet und intern an die Webanwendung weitergeleitet.

Solche statische URLs k�nnen aufgrund der Keyworddichte (die Ressource beschreibende W�rter in der 
URL) vorteilhaft f�r Suchmaschinen sein und User k�nnen sich die URLs einfacher merken.

Bei einer CONTENIDO-Installation lassen sich solche URLs mit dem Advanced Mod Rewrite Plugin 
generieren, URLs zu Frontendseiten, wie z. B. "/cms/front_content.php?idart=12&amp;lang=1" werden 
vom Plugin als statische URLs wie "/de/page-name.html" ausgegeben. Diverse Einstellungen zum 
Ausgabeformat der URLs lassen sich im CONTENIDO-Backend konfigurieren.

Das Plugin Advanced Mod Rewrite basiert auf die geniale Erweiterung Advanced Mod Rewrite f�r 
CONTENIDO, welches als Bundle von stese bis zur CONTENIDO Version 4.6.15 entwickelt und betreut 
wurde.

Wichtiger Aspekt bei der Umsetzung war die Implementierung als Plugin mit so wenig wie m�glichen 
Eingriffen in den CONTENIDO Core (trotzdem ging es nicht ohne einige Anpassungen an bestimmten 
Sourcen).

Daher enth�lt das Archiv einige �berarbeitete CONTENIDO Sourcen, die eventuell nicht auf dem 
neuesten Stand sein k�nnen.



################################################################################
CHANGELOG

2011-04-11  Advanced Mod Rewrite Plugin integration into the CONTENIDO core



################################################################################
BEKANNTE BUGS

Urls sollten unbedingt eine Endung wie z. B. '.html' bekommen, da ansonsten die Erkennungsroutine den
Artikel aus der ankommenden URL nicht ermitteln kann.

Wenn der Clean-URL die Sprache oder der Mandant vorangestellt wird, funktioniert die Fehlererkennung 
unter Unst�nden nicht richtig, d. h. es gibt keine Weiterleitung zur Fehlerseite, sofern dies im 
Plugin eingestellt wurde.



################################################################################
FEATURES

- Erstellung Suchmaschinenoptimierter URLs, CONTENIDO interne URLs wie 
  /front_content.php?idcat=12&idart=34 werden z. B. als /kategoriename/artikelname.html umschrieben
- Unterst�tzung mehrerer Sprachen 
- Unterst�tzung mehrerer Mandanten im gleichen Verzeichnis 
- Umschreiben der URLs entweder bei der Ausgabe des HTML Codes oder beim Generieren des Codes der 
  Seiten 
- Routing von URLs (Umleiten eingehender URLs auf andere Ziel-URLs)



################################################################################
VORAUSSETZUNGEN

- Alle Voraussetzungen von CONTENIDO 4.8.x gelten auch f�r das Plugin
- PHP ab Version 5.1 (Das Plugin war bis Version 0.3.3 PHP 4.4.x kompatibel)
- Apache HTTP Server 2 mit Mod Rewrite Modul und .htaccess



################################################################################
INSTALLATION

Das Plugin kann im CONTENIDO Setupprocess installiert werden.

################################################################################
WICHTIGES ZUM INHALT

ALLGEMEIN
=========

.htaccess:
----------
Die Konfiguration des Apache, in der das mod_rewrite-Modul aktiviert und mit diversen Anweisungen 
konfiguriert wird. Die Einstellungen bewirken, dass ankommende Anfragen wie z. B. 
/kategorie/artikel.html an die front_content.php im Mandantenverzeichnis weitergeleitet werden.

Die .htaccess liegt nicht im CONTENIDO Installationsverzeichnis vor, es muss entweder dorthin 
kopiert oder eine vorhanene .htaccess Datei angepasst werden.

Als Vorlage existieren folgende 2 Versionen der .htaccess:

htaccess_restrictive.txt:
Enth�lt Regeln mit restriktiveren Einstellungen.
Alle Anfragen, die auf die Dateienendung js, ico, gif, jpg, jpeg, png, css, pdf gehen, werden vom 
Umschreiben ausgeschlossen. Alle anderen Anfragen, werden an front_content.php umschrieben.
Ausgeschlossen davon sind 'contenido/', 'setup/', 'cms/upload', 'cms/front_content.php', usw.
Jede neue Ressource, die vom Umschreiben ausgeschlossen werden soll, muss explizit definiert werden.

htaccess_simple.txt:
Enth�lt eine einfachere Sammlung an Regeln. Alle Anfragen, die auf g�ltige symlinks, Verzeichnisse oder 
Dateien gehen, werden vom Umschreiben ausgeschlossen. Restliche Anfragen werden an front_content.php
umschrieben.


contenido/plugins/mod_rewrite/*:
--------------------------------
Die Sourcen des Plugins.

contenido/classes/uri/class.uriBuilder.MR.php:
---------------------------------------------------------------
UriBuilder Klasse des Plugins (seit Version 0.4.0), zum Generieren der URLs anhand der Pluginkonfiguration. 
Verwendet die in den CONTENIDO Core implementierte UriBuilder-Funktionalit�t und erweitert diesen um die 
pluginspezifischen Features.


################################################################################
FAQ

Der Plugininstaller l�sst sich nicht aufrufen, wie kann ich dennoch das Plugin installieren?
--------------------------------------------------------------------------------------------
Normalerweise wird der Plugininstaller mit folgender URL aufgerufen:
http://localhost/contenido/plugins/mod_rewrite/install.php
("http://localhost/" ist eventuell gegen anderen virtual Host oder Domainnamen ersetzen)

Es erscheint das Anmeldeformular zum Backend, �ber den man sich am System anmelden kann. Nach 
erfolgreicher Anmeldung wird man normalerweise zum Plugininstaller weitergeleitet.

Manchmal kann es vorkommen, dass die Weiterleitung nach der Anmeldung nicht klappt und man nicht den 
Plugininstaller aufrufen kann.
Um dennoch den Installer aufzurufen, reicht es aus, der URL die aktuell g�ltige CONTENIDO Session ID
anzuh�ngen, z. B. /contenido/plugins/mod_rewrite/install.php?contenido={my_session_id}.


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
Wenn nicht, dann kann eines der folgenden Punkte der Grund daf�r sein:
Das mod_rewrite Modul ist nicht geladen, das ist in der httpd.conf zu setzen
[code]
LoadModule rewrite_module modules/mod_rewrite.so
[/code]

Die Direktive "AllowOverride" ist nicht korrekt gesetzt. Damit die Angaben in der .htaccess auch 
ben�tzt werden k�nnen, muss f�r das betreffende Verzeichnis die Direktive "AllowOverride" in der 
httpd.conf angegeben werden:
[code]
# Beispielkonfiguration
<Directory "/var/www/mywebproject">
    AllowOverride FileInfo
</Directory>
[/code]

 
Wie richte ich Advanced Mod Rewrite f�r eine CONTENIDO-Installation in einem Unterverzeichnis ein?
-------------------------------------------------------------------------------------------------

Als Beispiel gehen wir davon aus, dass CONTENIDO im Verzeichnis /mypage/ unterhalb vom Webroot 
installiert wurde und das Mandantenverzeichnis per default /mypage/cms/ ist.

In der Pluginkonfiguration (Backend) den Pfad zur .htaccess Datei (aus Sicht des Web-Browsers) 
folgenderma�en anpassen:
[code]
/mypage/
[/code]

Die /mypage/.htaccess �ffnen und die RewriteBase folgenderma�en anpassen:
[code]
RewriteBase /mypage/cms/
[/code]


Welche Einstellungen sind n�tig, wenn das Mandantenverzeichnis das wwwroot ist?
-------------------------------------------------------------------------------
Normalerweise liegt das Mandantenverzeichnis innerhalb des wwwroot und ist �ber 
http://domain.tld/cms/front_content.php erreichbar.
Manchmal ist es erw�nscht, dass der Ordner /cms/ in der URL nicht sichbar sein soll, also 
erreichbar �ber http://domain.tld/front_content.php.

In diesem Fall sind zwei Anpassungen n�tig, damit Mod Rewrite korrekt funktioniert:
1. Die .htaccess Datei in das Verzeichnis /cms/ kopieren, da die Datei im wwwroot sein muss.
2. In der .htaccess die RewriteBase Option anpassen 

# von
RewriteBase /cms

# auf
RewriteBase / 


Wie kann ich das Verarbeiten bestimmter Seiten vom Plugin unterbinden?
----------------------------------------------------------------------
Wenn das Plugin so konfiguriert wurde, dass die URLs bei der Ausgabe des HTML Codes der Seite 
angepasst werden, kann dieses Verhalten bei manchen Seiten unerw�nscht sein. Das kann bei einer 
Ausgabe der Fall sein, dessen Inhalt kein HTML ist (z. B. Dateidownload), dann macht es keinen Sinn, 
die Ausgabe anzupassen.

Ab CONTENIDO 4.8.8 gibt es eine neue Einstellung, mit der man unterbinden kann, dass die Ausgabe im 
Frontend nicht in den Ausgabepuffer geschrieben wird. Ist dies f�r eine Seite definiert worden, wird 
auch die Funktion vom Plugin, die die URLs anpasst, nicht ausgef�hrt.

Einstellen l�sst sich das �ber Mandanteneinstellungen wie folgt:
[code]
Typ                          Name     Wert
frontend.no_outputbuffer     idart    12,14,40
[/code]
Inhalte der Artikel mit der id 12, 14 und 40 werden dann von der Ausgabepufferung ausgeschlossen.


Warum werden URLs trotz richtiger Vorraussetzungen nicht umschrieben?
---------------------------------------------------------------------
Ist die .htaccess und die Konfiguration des Plugins als Fehlerquelle auszuschlie�en und das Plugin 
soll die URLs bei der Ausgabe der Seite umschreiben (Standardeinstellung), k�nnte ein vorzeitig 
geleerter Ausgabepuffer der Grund sein.

In der front_content.php wird der HTML-Code in den Ausgabepuffer geschrieben, damit der Code vor der 
endg�ltigen Ausgabe bearbeitet werden kann. Das Plugin f�gt der Chain 
"Contenido.Frontend.HTMLCodeOutput" eine eigene Funktion, die den Code aus dem Ausgabepuffer erh�lt, 
um die darin URLs zu umschreiben.

Wird aber der Ausgabepuffer vorher geleert, z. B. durch Verwendung von ob_flush() in einem Modul, 
wird der Code direkt an den Client rausgeschickt. Das hat den Effekt, dass in der front_content.php 
kein Code mehr aus dem Ausgabepuffer zur Verf�gung steht, der nicht weiterverarbeitet werden kann, 
auch das Plugin kann dann keine URLs umschreiben.


Alle URLs zu Kategorien werden mit / oder /index.html umschrieben:
------------------------------------------------------------------
Ist CONTENIDO mit der Konfiguration $cfg["is_start_compatible"] = true; 
(siehe contenidoincludes/config.php) eingestellt, um die Startartikeldefinition in Kategorien 
kompatibel zu �lteren CONTENIDO-Versionen halten, kann das Plugin die URLs zu Kategorien nicht
generieren, weil es diese Konfiguration nicht unterst�tzt.

Die einfachste L�sung ist, die Konfiguration $cfg["is_start_compatible"] auf false zu setzen und im 
Backend in den vorhandenen Kategorien erneut die Startartikel zu setzen.



################################################################################
ADVANCED MOD REWRITE THEMEN IM CONTENIDO FORUM

Plugin Advanced Mod Rewrite f�r CONTENIDO 4.8.x:
http://www.contenido.de/forum/viewtopic.php?t=21578

Original Advanced Mod Rewrite 4.6.23:
http://www.contenido.de/forum/viewtopic.php?t=18454

Original Advanced Mod Rewrite 4.6.15:
http://www.contenido.de/forum/viewtopic.php?t=11162

Advanced Mod Rewriting CONTENIDO 4.4.4:
http://www.contenido.de/forum/viewtopic.php?t=6713



################################################################################
SCHLUSSBEMERKUNG

Benutzung des Plugins auf eigene Gefahr!

Murat Purc, murat@purc.de

# Advanced Mod Rewrite Plugin für CONTENIDO >= 4.10.2

################################################################################

## TOC (Table of contents)

1. [Beschreibung](#Beschreibung)
2. [Features](#Features)
3. [Voraussetzungen](#Voraussetzungen)
4. [Installation](#Installation)
5. [Aktualisierung](#Aktualisierung)
6. [Wichtiges zum Inhalt](#Wichtiges-zum-Inhalt)
7. [FAQ](#FAQ)
8. [Advanced Mod Rewrite Themen im CONTENIDO Forum](#Advanced-Mod-Rewrite-Themen-im-CONTENIDO-Forum)


## Beschreibung

Das Plugin Advanced Mod Rewrite ist eine Erweiterung für das CONTENIDO-CMS zur Generierung von
alternativen URLs.

Normalerweise werden die URLs zu Seiten einer auf ein CMS (z. B. Contenido) basierenden
Webpräsenz nach dem Muster `/index.php?page=12&amp;language=de` generiert, also in Form von
dynamischen URLs. Eine Möglichkeit, solche dynamischen URLs zu Webseiten, deren Inhalte in der Regel
aus Datenbanken kommen, gegen statische URLs wie z. B. `/de/page-name.html` umzustellen, gibt es in
Kombination mit dem Apache mod_rewrite-Modul. Dabei werden die URLs zu den Seiten als sogenannte
"Clean URLs" ausgegeben, Requests zu der gewünschten Ressource werden vom Webserver nach
definierten Regeln verarbeitet und intern an die Webanwendung weitergeleitet.

Solche statischen URLs können aufgrund der Keyword-Dichte (die Ressource beschreibende Wörter in der
URL) vorteilhaft für Suchmaschinen sein und User können sich die URLs einfacher merken.

Bei einer CONTENIDO-Installation lassen sich solche URLs mit dem Advanced Mod Rewrite Plugin
generieren, URLs zu Frontendseiten, wie z. B. `/cms/front_content.php?idart=12&amp;lang=1` werden
vom Plugin als statische URLs wie `/de/page-name.html` ausgegeben. Diverse Einstellungen zum
Ausgabeformat der URLs lassen sich im CONTENIDO-Backend konfigurieren.

Das Plugin Advanced Mod Rewrite basiert auf die geniale Erweiterung Advanced Mod Rewrite für
CONTENIDO, welches als Bundle von stese bis zur CONTENIDO Version 4.6.15 entwickelt und betreut
wurde.

Dieses Plugin ist seit CONTENIDO 4.9 ein Teil des CONTENIDO-Bundles.

## Features

- Erstellung suchmaschinenoptimierter URLs, CONTENIDO interne URLs wie
  `/front_content.php?idcat=12&idart=34` werden z. B. als `/kategoriename/artikelname.html` umschrieben
- Unterstützung mehrerer Sprachen
- Unterstützung mehrerer Mandanten im gleichen Verzeichnis
- Umschreiben der URLs entweder bei der Ausgabe des HTML-Codes oder beim Generieren des Codes der
  Seiten
- Routing von URLs (Umleiten eingehender URLs auf andere Ziel-URLs)

## Voraussetzungen

- Alle Voraussetzungen von CONTENIDO 4.10.2 gelten auch für das Plugin
- PHP ab Version 7.1
- Apache HTTP Server 2 mit Mod Rewrite Modul und `.htaccess`

## Installation

Das Plugin ist im CONTENIDO-Bundles enthalten und kann über das Backend installiert werden.

## Aktualisierung

Da das Plugin im CONTENIDO-Bundle enthalten ist, wird die neue Version automatisch bei der
Installation des neuen Contenido-Releases übernommen, indem man die Dateien aus dem neuen
Release in die entsprechenden Verzeichnisse kopiert.

## Wichtiges zum Inhalt

### Allgemein

`.htaccess`:

Die Konfiguration des Apache-Servers, in der das mod_rewrite-Modul aktiviert und mit diversen
Anweisungen konfiguriert wird. Die Einstellungen bewirken, dass ankommende Anfragen wie z. B.
`/kategorie/artikel.html` an die `front_content.php` im Mandantenverzeichnis weitergeleitet werden.

Die `.htaccess` liegt nicht im CONTENIDO-Installationsverzeichnis vor, es muss entweder dorthin
kopiert oder eine vorhandene `.htaccess` Datei angepasst werden.

Als Vorlage existieren folgende 2 Versionen der `.htaccess`:

`htaccess_restrictive.txt`:

Enthält Regeln mit restriktiveren Einstellungen.
Alle Anfragen, die auf die Dateiendungen `js`, `ico`, `gif`, `jpg`, `jpeg`, `png`, `css`, `pdf`,
usw. gehen, werden vom Umschreiben ausgeschlossen. Alle anderen Anfragen werden an `front_content.php`
umschrieben. Ausgeschlossen davon sind `contenido/`, `setup/`, `cms/upload`, `cms/front_content.php`, usw.
Jede neue Ressource, die vom Umschreiben ausgeschlossen werden soll, muss explizit definiert werden.
Diese Angaben können bei Bedarf um weitere auszuschließende Dateiendungen erweitert werden.

`htaccess_simple.txt`:

Enthält eine einfachere Sammlung an Regeln. Alle Anfragen, die auf gültige symlinks, Verzeichnisse oder
Dateien gehen, werden vom Umschreiben ausgeschlossen. Restliche Anfragen werden an `front_content.php`
umschrieben.

`contenido/plugins/mod_rewrite/*`:

Die Quellcode-Dateien des Plugins.

`contenido/classes/uri/class.uribuilder.mr.php`:

UrlBuilder Klasse des Plugins, die anhand der Plugin-Konfiguration die URLs generiert.
Verwendet die in den CONTENIDO Core implementierte UrlBuilder-Funktionalität und erweitert diesen um die
plugin-spezifischen Features.

## FAQ

### Wie teste ich, ob mod_rewrite am Server richtig konfiguriert ist?

Obwohl `mod_rewrite` auf dem Server installiert ist, kommt es manchmal vor, dass es nicht funktioniert.

Das kann einfach getestet werden, erstelle eine `.htaccess` im Root-Verzeichnis und schreibe Folgendes
rein:

```.htaccess
RewriteEngine on
RewriteRule ^ https://www.contenido.org [R,L]
```

Nach Eingabe der URL in die Adresszeile des Browsers sollte auf www.contenido.org weitergeleitet
werden.
Falls nicht, dann kann eines der folgenden Punkte der Grund dafür sein:
Das mod_rewrite Modul ist nicht geladen, das ist in der `httpd.conf `zu setzen

```.htaccess
LoadModule rewrite_module modules/mod_rewrite.so
```

Die Direktive `AllowOverride` ist nicht korrekt gesetzt. Damit die in der `.htaccess` definierten
Regeln auch greifen, muss für das betreffende Verzeichnis die Direktive `AllowOverride` in der
`httpd.conf` angegeben werden:

```.htaccess
# Beispielkonfiguration
<Directory "/var/www/mywebproject">
    AllowOverride FileInfo
</Directory>
```

### Wie richte ich Advanced Mod Rewrite für eine CONTENIDO-Installation in einem Unterverzeichnis ein?

Als Beispiel gehen wir davon aus, dass CONTENIDO im Verzeichnis `/my-page/` unterhalb vom Webroot
installiert wurde und das Mandantenverzeichnis standardmäßig `/my-page/cms/` ist.

In der Plugin-Konfiguration (Backend) den Pfad zur `.htaccess` Datei (aus Sicht des Webbrowsers)
folgendermaßen anpassen:

```
/my-page/
```

Die `/my-page/.htaccess` öffnen und die RewriteBase folgendermaßen anpassen:

```.htaccess
RewriteBase /my-page/cms/
```

### Welche Einstellungen sind nötig, wenn das Mandantenverzeichnis zugleich das www-root ist?

Normalerweise liegt das Mandantenverzeichnis innerhalb des www-root und ist über
http://domain.tld/cms/front_content.php erreichbar.
Manchmal ist es erwünscht, dass der Ordner `/cms/` in der URL nicht sichtbar sein soll, also
erreichbar über http://domain.tld/front_content.php.

In diesem Fall sind zwei Anpassungen nötig, damit Mod Rewrite korrekt funktioniert:

1. Die `.htaccess` Datei in das Verzeichnis `/cms/` kopieren, da die Datei im www-root sein muss.
2. In der `.htaccess` die RewriteBase Option anpassen

```.htaccess
# von
RewriteBase /cms

# auf
RewriteBase /
```

### Wie kann ich das Verarbeiten bestimmter Seiten vom Plugin unterbinden?

Wenn das Plugin so konfiguriert wurde, dass die URLs bei der Ausgabe des HTML-Codes der Seite
angepasst werden, kann dieses Verhalten bei manchen Seiten unerwünscht sein. Das kann bei einer
Ausgabe der Fall sein, deren Inhalt kein HTML ist (z. B. Dateidownload), dann ergibt es keinen Sinn,
die Ausgabe anzupassen.

Ab CONTENIDO 4.8.8 gibt es eine neue Einstellung, mit der man unterbinden kann, dass die Ausgabe im
Frontend nicht in den Ausgabepuffer geschrieben wird. Ist dies für eine Seite definiert worden, wird
auch die Funktion vom Plugin, die die URLs anpasst, nicht ausgeführt.

Einstellen lässt sich das über Mandanteneinstellungen wie folgt:
```
Typ                          Name     Wert
frontend.no_outputbuffer     idart    12,14,40
```

Inhalte der Artikel mit der id 12, 14 und 40 werden dann von der Ausgabepufferung ausgeschlossen.

### Warum werden URLs trotz richtiger Voraussetzungen nicht umschrieben?

Ist die `.htaccess` und die Konfiguration des Plugins als Fehlerquelle auszuschließen und das Plugin
soll die URLs bei der Ausgabe der Seite umschreiben (Standardeinstellung), könnte ein vorzeitig
geleerter Ausgabepuffer der Grund sein.

In der `front_content.php` wird der HTML-Code in den Ausgabepuffer geschrieben, damit der Code vor der
endgültigen Ausgabe bearbeitet werden kann. Das Plugin fügt der Chain
`Contenido.Frontend.HTMLCodeOutput` eine eigene Funktion, die den Code aus dem Ausgabepuffer erhält,
um die darin URLs zu umschreiben.

Wird aber der Ausgabepuffer vorher geleert, z. B. durch Verwendung von `ob_flush()` in einem Modul,
wird der Code direkt an den Client herausgeschickt. Das hat den Effekt, dass in der `front_content.php`
kein Code mehr aus dem Ausgabepuffer zur Verfügung steht, der nicht weiterverarbeitet werden kann,
auch das Plugin kann dann keine URLs umschreiben.

## Advanced Mod Rewrite Themen im CONTENIDO Forum

* [CONTENIDO 4.10 > Module und Plugins](https://forum.contenido.org/viewforum.php?f=116)
* [CONTENIDO 4.9 > Module und Plugins](https://forum.contenido.org/viewforum.php?f=99)
* [Plugin Advanced Mod Rewrite für CONTENIDO 4.8.x](http://www.contenido.de/forum/viewtopic.php?t=21578)
* [Original Advanced Mod Rewrite 4.6.23](http://www.contenido.de/forum/viewtopic.php?t=18454)
* [Original Advanced Mod Rewrite 4.6.15](http://www.contenido.de/forum/viewtopic.php?t=11162)
* [Advanced Mod Rewriting CONTENIDO 4.4.4](http://www.contenido.de/forum/viewtopic.php?t=6713)

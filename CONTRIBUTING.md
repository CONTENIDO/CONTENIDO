
# Contributing

Anleitung für Entwickler um beim CONTENIDO-Projekt mitwirken.


## Tickets erstellen

Bevor man ein Ticket für ein Feature oder ein Bug erstellt, sollte man vorher prüfen, ob es schon Tickets dafür gibt.

Falls kein Ticket für das Feature oder den Bug existiert, kann man eines erstellen.

Der Titel eines Tickets sollte kurz und knapp sein, dennoch ausreichend erklärend.

Der Inhalt des Tickets sollte das Feature oder den Bug erklären, so dass jeder, der nicht mit dem Thema vertraut ist, auf anhieb versteht, um was es genau geht.

Dabei ist es hilfreich, wenn der Inhalt folgende Fragen beantwortet:
- Warum ist ein Feature nötig?
- Wie und wann kommt es zu einem Fehler? Idealerweise gibt es eine Schritt-für-Schritt-Anleitung zum Reproduzieren des Fehlers
- In welcher Umgebung (Betriebssystem, Web-Server, PHP, Datenbank Browser, inkl. Version) tritt der Fehler auf?
- Gibt es ein Link im CONTENIDO-Forum, in der das Problem oder das Thema besprochen wird? Falls, ja, kann die Angabe des Links hilfreich sein.

Dem Ticket kann man diverse Labels wie folgt zuweisen:
- Bei einem Feature das Label "enhancement"
- Bei einem Bug das Label "bug"

Bei einer Sicherheitslücke wäre es sinnvoll, diese mit weiteren CONTENIDO-Entwicklern zu besprechen bevor man ein Ticket für die Sicherheitslücke erstellt. Eine nicht öffentliche Sicherheitslücke ist besser als eine, die im ganzen Web bekannt ist.


## Tickets bearbeiten

Idealerweise sollten zuallererst Sicherheitslücken und Fehler behoben werden. Die Weiterentwicklung von Features ist zwar auch wichtig, hat aber eine geringere Priorität.

Bevor man ein Ticket bearbeitet, sollte man prüfen, ob das Ticket schon einem Entwickler zugewiesen ist. Bei so einem Fall sollte man ohne vorherige Rücksprache mit dem Entwickler das Ticket nicht sich selber zuweisen.

Falls das Ticket keinem Entwickler zugewiesen ist, kann man es sich zuweisen, um mit der Bearbeitung des Tickets anzufangen. 


## Git Branch erstellen

Bevor man ein Ticket bearbeitet, sollte man lokal auf seinem Rechner von der develop-Branch eine Branch für das Ticket erstellen.

Der Branchname sollte mit der Ticketid beginnen, gefolgt vom Titel, der in Kleinbuchstaben geschrieben ist, ohne Umlaute und Sonderzeichen und Leerzeichen mit Bindestrichen ersetzt.

Dem Branchnamen sollte der Branchtyp (feature oder bug) vorangestellt werden.

Beispiele:
- Der Titel des Tickets lautet "Mein GitHub Ticket für CONTENIDO"
- Die Ticketid lautet "#123"
- Es handelt sich um ein Feature
- Die zu erstellende Branch sollte "feature/#123-mein-github-ticket-fuer-contenido" lauten

- Der Titel des Tickets lautet "Foobar Fehler in der Qwertz".
- Die Ticketid lautet "#321"
- Es handelt sich um ein Bug.
- Die zu erstellende Branch sollte "bug/#321-foobar-fehler-in-der-qwertz" lauten


## Code Änderungen durchführen

Beim Ändern des Codes ist es wichtig, dass man sich an die vorgegeben Codierungskonventionen (coding conventions) hält. Wenn unterschiedliche Entwickler an einem Projekt mitmachen un jeder sein Programmierstil verwendet, kommt am Ende ein Code heraus, das aus verschieden Programmierstilen besteht. Das kann man mit einem Buch vergleichen, das verschiedene Abstände, Schriftarten, Schriftgrößen, usw. hat. Das Lesen des Codes wird erschwert.

Siehe [docs/coding_convention_zend_10_1_php.xml](docs/coding_convention_zend_10_1_php.xml).

Ein Paar grundlegende Vorgaben:
- Die Zeichenkodierung von Dateien ist UTF-8
- Einrückungen mit 4 Leerzeichen (keine Tabs!)
- Zeilenumbrüche in Unix (LF)

Ausgenommen von den Codierungskonventionen ist der verwendete Code von Drittanbietern, wie z. B. TinyMCE, Smarty oder SwiftMailer, usw. Hier sollte man alles im Originalzustand belassen.


## Testen der Änderungen

Jede Änderung oder Erweiterung sollte idealerweise getestet werden, am Besten mit entsprechenden Unit-Tests.


## Committen der Änderungen

Änderungen am Code sollten mit einer Commit-Message committet werden, die die getane Arbeit beschreibt.
Die Commit-Message sollte mit der Ticketid beginnen, gefolgt von der Beschreibung der Änderung.

Beispiel:
- Der Titel des Tickets lautet "Mein GitHub Ticket für CONTENIDO"
- Die Ticketid lautet "#123"
- Gearbeitet wurde an der Formatierung eines Skriptes
- Commit-Message lautet "#123 Formate im Skript abc.php angepasst."


## Pushen der Änderungen

Ein Git-Commit fügt die Änderung dem lokalen Git-Repository hinzu, um die Änderung auch dem zentralen Repository (in GitHub) hinzuzufügen, muss man die Änderung mit dem "push"-Befehl hochladen.


## Pull Request erstellen

Nach dem Hochladen der Änderung auf das zentrale Repository, ist die Änderung nur in der für das Ticket erstellte Branch enthalten. Diese Änderung sollte auch der default-Branch, also dem "develop"-Branch, hinzugefügt werden, sofern es im nächsten Release dabei sein soll.
Dies geschieht mit einem Pull-Request. Dabei wechselt man bei GitHub zur der Branch, an der man gearbeitet und mit einem pull auf das zentrale Repository hochgeladen hat und erstellt einen Pull Request.
Hier kann man zusätzlich Prüfer hinzufügen, damit die Entwickler benachrichtigt werden und den Pull Request prüfen. Nach Prüfung des Codes wird der Pull Request genehmigt, also der "develop"-Branch hinzugefügt, oder abgelehnt, falls Nachbesserungen nötig sind.


## Git Befehle

Im Folgenden sind einige Git-Befehle beschrieben, die man für die Arbeit bei Git-Projekten benötigt.

Moderne IDEs (PhpStorm, Visual Studio Code, Eclipse, NetBeans, usw.) haben schon Funktionen für die Arbeit mit Git-Projekten integriert und bieten intuitive Oberflächen dafür an. Da jede IDE eine etwas andere Bedienung hat, werden hier Kommandozeilen-Befehle für Git vorgestellt. 


### Projekt aus GitHub auschecken
```
git clone https://github.com/CONTENIDO/CONTENIDO.git
cd CONTENIDO
```

**Wichtig:**

Folgende Befehle sind alle im Projektordner "CONTENIDO" auszuführen!


### Eine vorhandene Remote-Branch "develop" auschecken

**Befehl:**

git checkout \<branch-name\>

**Beispiel:**

```
git checkout develop
```

**Wichtig:**

Wenn man Änderungen gemacht und diese nicht committet hat, sollte man diese vorher committen oder die Änderungen stashen.


### Eine neue lokale Branch erstellen und dabei die Branch von der "develop"-Branch abzweigen

**Befehl:**

git checkout -b \＜neue-branch\＞ \＜vorhandene-branch\＞

**Beispiel:**

```
git checkout -b feature/#123-mein-github-ticket-fuer-contenido develop
```


### Änderungen dem Commit hinzufügen
Vor dem Commit, muss man die Änderungen zuerst hinzufügen.
Nehmen wir an, die Datei "cms/front_content.php" wurde geändert.

**Befehl:**

git add \<pfad-zur-datei\>

**Beispiel:**

```
git add cms/front_content.php
```

### Änderungen committen

**Befehl:**

git commit -m"\<commit-message\>"

**Beispiel:**

```
git commit -m"#123 Formate in front_content.php angepasst."
```


### Änderungen pushen

**Befehl:**

git push origin feature/#123-mein-github-ticket-fuer-contenido

**Beispiel:**

```
git push origin \<branch-name\>
```

Danach kann man in GitHub einen Pull Request erstellen.


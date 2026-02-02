
# Contributing

Anleitung für Entwickler, um beim CONTENIDO-Projekt mitzuwirken.

## Vorwort

Dieses Repository lebt von einer aktiven, neugierigen und engagierten Community. Jede Idee, jeder Bugfix und jeder 
Verbesserungsvorschlag trägt dazu bei, CONTENIDO stabiler, moderner und vielseitiger zu machen.

Ob du einen kleinen Tippfehler korrigierst oder ein größeres Feature entwickelst – dein Beitrag ist wertvoll.
Wir freuen uns über jede Form der Mitwirkung und möchten dir den Einstieg so einfach wie möglich machen.

Lass uns gemeinsam CONTENIDO weiterentwickeln und ein starkes Open‑Source‑Ökosystem gestalten.

Der Prozess zur Entwicklung eines Features oder eines Bugfixes ist bei CONTENIDO ähnlich dem von Gitflow oder
GitHub Flow und besteht aus folgenden Schritten:

- [Ticket erstellen](#Ticket-erstellen)
- [Git Branch für das Ticket erstellen](#Git-Branch-erstellen)
- [Änderungen am Quellcode durchführen](#Änderungen-durchführen), [committen](#Committen-der-Änderungen)
  und [pushen](#Pushen-der-Änderungen)
- [Pull-Request erstellen](#Pull-Request-erstellen)
- [Vorgehensweise bei Forks](#Vorgehensweise-bei-Forks)

## Ticket erstellen

Bevor man ein Ticket für ein Feature oder ein Bug erstellt, sollte man vorher prüfen, ob es schon Tickets dafür gibt.

Falls kein Ticket für das Feature oder den Bug existiert, kann man eines erstellen.

Bei einer Sicherheitslücke wäre es sinnvoll, diese mit weiteren CONTENIDO-Entwicklern zu besprechen, bevor man ein 
Ticket für die Sicherheitslücke erstellt. Eine nicht öffentliche Sicherheitslücke ist besser als eine, die im ganzen
Web bekannt ist.

Neben dem Titel und der Beschreibung gibt es noch eine rechte Spalte, in der einige Eigenschaften
(Label, Typ, Projekt, Meilenstein) zu setzen sind.

**Titel:**

Der Titel eines Tickets sollte kurz und knapp sein, dennoch ausreichend erklärend.

*Beschreibung:**

Die Beschreibung des Tickets sollte das Feature oder den Bug ausreichend erklären, sodass alle, die nicht mit dem
Thema vertraut sind, auf Anhieb verstehen, worum es sich genau handelt.

Dabei ist es hilfreich, wenn die Beschreibung folgende Fragen beantwortet:
- Warum ist ein Feature nötig?
- Wie und wann kommt es zu einem Fehler? Idealerweise gibt es eine Schritt-für-Schritt-Anleitung zum Reproduzieren
  des Fehlers.
- In welcher Umgebung (Betriebssystem, Web-Server, PHP, Datenbank Browser, inkl. Version) tritt der Fehler auf?
- Gibt es einen Link zu einem Beitrag im CONTENIDO-Forum, in der das Problem oder das Thema besprochen wird?
  Falls, ja, kann die Angabe des Links hilfreich sein.

**Label:**

Dem Ticket kann man diverse Labels wie zuweisen. Die Übersicht über alle Labels findet 
ihr [hier](https://github.com/CONTENIDO/CONTENIDO/labels). 

**Typ:**

Der Typ des Tickets kann entweder `bug`, `feature` oder `task` sein. Je nach Art des Tickets eines davon auswählen.

**Projekt:**

Als Projekt ist in der Regel `Kanban board` auszuwählen.

**Meilenstein:**

Der Meilenstein ist optional und kann bei Bedarf gesetzt werden.
Hier lässt sich angeben, in welcher CONTENIDO Version das Ticket bearbeitet werden soll.

## Ticket bearbeiten

Bevor man ein Ticket bearbeitet, sollte man prüfen, ob das Ticket schon einem Entwickler zugewiesen ist. 
Bei so einem Fall sollte man ohne vorherige Rücksprache mit dem Entwickler das Ticket nicht selber zuweisen.

Falls das Ticket keinem Entwickler zugewiesen ist, kann man es sich zuweisen, um mit der Bearbeitung
des Tickets anzufangen. 

Idealerweise sollten zuallererst Sicherheitslücken und Fehler behoben werden. Die Weiterentwicklung von Features
ist zwar auch wichtig, hat aber eine geringere Priorität.

## Git Branch erstellen

Bevor man ein Ticket bearbeitet, sollte man lokal auf seinem Rechner von der develop-Branch eine Branch
für das Ticket erstellen.

Der Branchname sollte mit der Ticket-ID beginnen, gefolgt vom Titel, der in Kleinbuchstaben geschrieben ist,
ohne Umlaute, Sonderzeichen und Leerzeichen mit Bindestrichen ersetzt.

Dem Branchnamen sollte der Ticket-Typ (`task`, `feature` oder `bug`) vorangestellt werden.

**Beispiel für ein Ticket vom Typ `task`:**
- Der Titel des Tickets lautet "Sourcecode Dokumentation"
- Die Ticket-ID lautet "#123"
- Es handelt sich um eine Aufgabe (`task`)
- Die zu erstellende Branch sollte `task/123-mein-github-ticket-fuer-contenido` lauten

**Beispiel für ein Ticket vom Typ `feature`:**
- Der Titel des Tickets lautet "Mein GitHub Ticket für CONTENIDO"
- Die Ticket-ID lautet "#234"
- Es handelt sich um ein Feature
- Die zu erstellende Branch sollte `feature/234-mein-github-ticket-fuer-contenido` lauten

**Beispiel für ein Ticket vom Typ `bug`:**
- Der Titel des Tickets lautet "Foobar Fehler in der Qwertz".
- Die Ticket-ID lautet "#345"
- Es handelt sich um einen Fehler (`bug`).
- Die zu erstellende Branch sollte `bug/345-foobar-fehler-in-der-qwertz` lauten


## Änderungen durchführen

Beim Ändern des Codes ist es wichtig, dass man sich an die vorgegeben Codierungskonventionen (coding conventions) hält. 
Wenn unterschiedliche Entwickler an einem Projekt mitmachen und alle ihren Programmierstil verwenden, kommt am Ende ein
Quellcode heraus, der aus verschieden Programmierstilen besteht.

Das kann man mit einem Buch vergleichen, das verschiedene Abstände, Schriftarten, Schriftgrößen usw. hat. Das Lesen des
Codes wird erschwert.

Für CONTENIDO gibt es eine Codierungskonvention,
siehe [docs/coding_convention_zend_10_1_php.xml](docs/coding_convention_zend_10_1_php.xml).

Idealerweise sollte besten der [PSR-12](https://www.php-fig.org/psr/psr-12/) Coding-Style-Guide genutzt werden,
der von der [PHP-FIG](https://www.php-fig.org/) entwickelt wurde. Da gibt es für IDEs entsprechende Erweiterungen,
die darauf achten, dass der Coding-Standard eingehalten wird. 

Einrichtung von PSR-12 in verschiedenen IDEs:
- [PhpStorm](https://www.jetbrains.com/help/phpstorm/configuring-coding-convention.html)
- [VS Code](https://marketplace.visualstudio.com/items?itemName=EditorConfig.EditorConfig)
- [Eclipse](https://marketplace.eclipse.org/content/editorconfig-eclipse-code-formatter)

Ihr könnt euch an die im Quelltext vorhandene Formatierung halten. Schaut euch, wie der Programmierstil in der Datei
ist, und halt euch bei den Änderungen daran.

Ein Paar grundlegende Vorgaben:
- Die Zeichenkodierung von Dateien ist UTF-8
- Einrückungen mit 4 Leerzeichen (keine Tabs!)
- Zeilenumbrüche in Unix-Format (LF)

Ausgenommen von den Codierungskonventionen ist der verwendete Code von Drittanbietern, wie z. B. TinyMCE, Smarty oder
SwiftMailer, usw. Hier sollte man alles im Originalzustand belassen.


## Testen der Änderungen

Jede Änderung oder Erweiterung sollte idealerweise getestet werden, am besten mit entsprechenden Unit-Tests.


## Committen der Änderungen

Änderungen am Code sollten mit einer Commit-Message committet werden, in der die Änderungen beschrieben werden.

Das Format der Commit-Message ist dabei wichtig. Idealerweise sollte man sich dabei an die unter
[Konventionelle Commits](https://www.conventionalcommits.org/de/v1.0.0/) beschriebene Vorgehensweise anlehnen.

Die Commit-Message sollte mit dem Commit-Typ beginnen, gefolgt von der Ticket-ID, dem Titel des Tickets und der
Beschreibung der Änderung.

**Format:**
```
<type>[optional scope]: <description>

[optional body]

[optional footer(s)]
```

Weitere Details dazu gibt es unter:
- [Konventionelle Commits > Zusammenfassung](https://www.conventionalcommits.org/de/v1.0.0/#zusammenfassung)
- [conventional-changelog-metahub > Commit types](https://github.com/pvdlg/conventional-changelog-metahub?tab=readme-ov-file#commit-types)

**Beispiel für eine Commit-Message vom Commit-Typ `fix`:**
- Der Titel des Tickets lautet "Core: Type error in class Foobar"
- Die Ticket-ID lautet "#234"
- Der Typisierungsfehler in der Foobar-Klasse wurde behoben.
- Commit-Message lautet:
  ````
  fix: [#234] Core: Type error in class Foobar.
  
  Fixed type error in class Foobar.
  ````

**Beispiel für eine Commit-Message vom Commit-Typ `style`:**
- Der Titel des Tickets lautet "My GitHub ticket for CONTENIDO"
- Die Ticket-ID lautet "#123"
- Gearbeitet wurde an der Formatierung eines Skriptes
- Commit-Message lautet:
  ````
  style: [#123] My GitHub ticket for CONTENIDO.
  
  Formatted script abc.php.
  ````

## Pushen der Änderungen

Ein Git-Commit fügt die Änderung dem lokalen Git-Repository hinzu. 
Um die Änderung auch dem zentralen Repository (in GitHub) hinzuzufügen, muss man die Änderung mit dem
`git push`-Befehl hochladen.


## Pull-Request erstellen

Nachdem die Änderungen in das Remote-Repository gepusht (hochgeladen) wurden, kann man in GitHub einen Pull-Request
erstellen. Die Ziel-Branch ist in der Regel die `develop`-Branch.

Hier sollte man Prüfer (Code-Reviewer) hinzufügen, damit die Entwickler benachrichtigt werden und den
Pull-Request prüfen. 

Nach Prüfung des Codes wird der Pull-Request genehmigt, also der `develop`-Branch hinzugefügt, oder abgelehnt,
falls Nachbesserungen nötig sind.

Der Pull-Request lässt sich auch direkt nach der Erzeugung des Branches erstellen.

## Vorgehensweise bei Forks

Natürlich kann man auch von einem Fork aus ein Pull-Request erstellen.
Wie die Änderungen in dem Fork implementiert werden oder welche Prozesse in dem Fork für die Entwicklung genutzt
werden, ist dabei unerheblich, das können alle selbst entscheiden.

Wichtig ist nur, dass es im CONTENIDO-Repository ein Ticket dazu gibt, ein Pull-Request erstellt wird und die
Änderungen aus dem Pull-Request den Codierungskonventionen entsprechen.

## Glossar

- Ticket: Ein Ticket ist ein Eintrag in einem Ticket-System, in dem ein Entwickler einen Fehler, eine Funktion oder
  eine Aufgabe beschreibt. In GitHub werden Tickets in Form von Issues gespeichert.
- Ticket-ID: Die Nummer des Tickets, mit einer Raute als Prefix, z. B. "#123". In GitHub ist es die Issue-Nummer

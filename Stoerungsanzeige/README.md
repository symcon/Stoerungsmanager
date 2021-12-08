# VerbraucherAlarm
Das Modul dient dazu aktive Variablen im Webfront anzuzeigen und je nach Einstellung nach Eingabe oder Deaktivierung auszublenden. 
Wenn die Einstellung 'Meldung verschwindet automatisch' ausgewählt wurde, wird ein Link sichtbar, sobald die überwachte Variable aktiv ist. Dieser verschwindet sobald die überwachte Variabel deaktiviert ist.
Wenn die Einstellung 'Quittiere Meldung' ausgewählt wurde, wird eine Variable erstellt, sobald die überwachte Variable aktiv ist. Diese verschwindet sobald bei der erstellten Variable der Wert auf über 0 geändert wurde. 
Wenn die Einstellung 'Quittiere Meldung, verschwindet, wenn die Störung behoben wurde' ausgewählt wurde, wird eine Variable erstellt, sobald die überwachte Variable aktiv ist. Diese verschwindet sobald bei der erstellten Variable der Wert auf über 0 geändert wurde und die überwachte Variable deaktiviert wurde. 

### Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)

### 1. Funktionsumfang

* Anzeige von aktiven Variablen
* Möglichkeit diese je nach Einstellung zu quittieren

### 2. Voraussetzungen

- IP-Symcon ab Version 6.0

### 3. Software-Installation

* Über den Module Store das Modul Verbraucher-Alarm installieren.
* Alternativ über das Module Control folgende URL hinzufügen:
`https://github.com/symcon/Stoerungsanzeige`

### 4. Einrichten der Instanzen in IP-Symcon

- Unter "Instanz hinzufügen" kann das 'Störungsanzeige'-Modul mithilfe des Schnellfilters gefunden werden.
    - Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)

__Konfigurationsseite__:

Name                   | Beschreibung
---------------------- | ---------------------------------
Variable               | Variable, welche überwacht werden sollen.
Umgang mit Nachrichten | Auswahl wie mit den Variablen umgegangen werden soll, wenn diese aktiv sind.

### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

##### Statusvariablen

Name                    | Typ     | Beschreibung
----------------------- | ------- | ----------------
Link: Variablennamen    | Link    | Link, welcher sichtbar oder unsichtbar ist. Wird erstellt, wenn 'Meldung verschwindet automatisch' ausgewählt wird.
Variablennamen - Status | Integer | Variable, welche erstellt wird, wenn 'Quittiere Meldung' oder 'Quittiere Meldung, verschwindet, wenn die Störung behoben wurde' ausgewählt wurde. 


##### Profile:

Bezeichnung        | Beschreibung
------------------ | -----------------
STA.Confirm        | Profil für erstellte Variablen

### 6. WebFront
Über das WebFront werden die erstellten Variablen angezeigt und können verändert werden, wenn diese quittiert werden müssen.

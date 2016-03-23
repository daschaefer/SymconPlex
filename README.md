Plex Home Theater PHP Module für IP-Symcon
===
Dieses IP-Symcon PHP Modul steuert vorhandene Plex Home Theater Clients.
Außerdem werden applikationsweite Methoden zur Steuerung der Plex Home Theater Clients bereitgestellt.

**Content**

1. [Funktionsumfang](#1-funktionsumfang)
2. [Anforderungen](#2-anforderungen)
3. [Vorbereitung & Installation & Konfiguration](#3-vorbereitung--installation--konfiguration)
4. [Variablen](#4-variablen)
5. [Hintergrund Skripte](#5-hintergrund-skripte)
6. [Funktionen](#6-funktionen)

## 1. Funktionsumfang  
Die folgenden Funktionalitäten sind implementiert:
- Steuerung
  - Lautstärke
  - Menu Bewegung
  - Auswahl von Titeln
  - Wiedergabesteuerung
- Einblendungen von Nachrichten
- Auslesen des Client Zustandes

## 2. Anforderungen

- IP-Symcon 4.x installation (Linux / Windows)
- Bereits bestehende Plex Home Theater Instanz
  - Windows
  - Linux
  - OSX
  - Rasplex
- Netzwerkverbindung der Plex Home Theater Instanz

## 3. Vorbereitung & Installation & Konfiguration

### Vorbereitung am Plex Home Theater Client
Damit der Plex Home Theater Client über die bereits vorhandene, aber deaktivierte JSON RPC Schnittstelle gesteuert werden kann muss im jeweiligen Programmverzeichnis folgende Datei:

- Windows
```
//%APPDATA%\Plex Home Theater\userdata\guisettings.xml
```
- OSX
```
~/Library/Application Support/Plex Home Theater/userdata/guisettings.xml
```
- Linux
```
~/.plexht/temp/userdata/guisettings.xml
```
- Rasplex
```
/storage/.plexht/userdata/guisettings.xml
```

geöffnet werden und folgender XML Eintrag gesetzt werden (üblicherweise von false auf true):

```xml
<esallinterfaces>true</esallinterfaces>
```

Anschließend muss die Plex Home Theater Applikation neu gestartet werden! 
Unter Rasplex muss der Raspberry Pi neugestartet werden!

### Installation in IPS 4.x
Im "Module Control" (Kern Instanzen->Modules) die URL "git://github.com/daschaefer/SymconPlex.git" hinzufügen.  
Danach ist es möglich eine neue Plex Instanz innerhalb des Objektbaumes von IP-Symcon zu erstellen.
### Konfiguration
**Client IP-Adresse:**

*Die IP-Adresse/Hostname unter dem der Plex Home Theater Client zu erreichen ist (in der Regel macht hier eine statische IP-Adressvergabe Sinn).*

**Client Port:**

*Der Port unter dem der Plex Home Theater Client zu erreichen ist.
Automatisch vorbelegt ist `3005` und muss in der Regel nicht angepasst werden.*

**Client MAC-Adresse:**

*Die MAC Adresse der Netzwerkkarte des Plex Home Theater Clients.
Dient dazu den Client per Wake on LAN (WOL, Magic Paket) auf zu wecken.*

**Client Socket:**

*Es wird automatisch bei der Installation der Plex Instanz ein Client Socket hinzugefügt, dieser dient der Kommunikation mit dem Plex Home Theater Client.
Dieser Client Socket wird hier automatisch verlinkt und muss in der Regel nicht angepasst werden.*

**Server IP-Adresse:**

*Die IP-Adresse/Hostname des Plex Media Servers. Wird verwendet um z.B. Cover und weitere Detailinformationen zu laden.*

**Server Port:**

*Der Port über den die Kommunikation mit dem Plex Media Server stattfindet.
Automatisch vorbelegt ist `32400` und muss in der Regel nicht angepasst werden.*

## 4. Variablen
**Client Status**

*Gibt den aktuellen Status des Plex Home Theater Clients wieder, je nach Zustand:*
```
Eingeschaltet = Aktiv
Ausgeschaltet = Inaktiv
```

**Cover**

*Das Cover des aktuellen Titels im Großformat.*

**HTML**

*Geplante HTML Formatierte Ausgabe mit diversen Informationen zum aktuellen Titel. Wird aktuell noch nicht gefüllt!*

**Item**

*Item ID des aktuell gespielten Titels (zur internen Verwendung).*

**Lautstärke**

Die Lautstärke des Clients.

**Player ID**

*Die aktuelle Player ID des Clients (zur internen Verwendung).*

**Power**

*Buttons zum Einschalten (WOL) und Ausschalten (Herunterfahren) des Clients.*

**Status**

Der aktuelle Status des Clients:
  - Spielt
  - Pausiert

**Steuerung**

*Buttons zur Steuerung (Hoch, Runter, Links, Rechts, Auswahl, Zurück) auf der Clientoberfläche.*

**Titel**

Der aktuell gespielte Titel.

**Wiedergabe Steuerung**

*Buttons zur Steuerung der Wiedergabe (Play, Pause, Stop, Next, Prev).*

**Wiederholung**

*Buttons zur Steuerung der Repeat Funktion.*

## 5. Hintergrund Skripte
Wenn eine Plex Instanz erstellt wird, werden zwei Skripte angelegt:

**ClientController**

*Dieses Skript dient dazu Steuerbefehle an den Plex Home Theater Client zu senden.*

**SocketController**

*Dieses Skript dient dazu den aktuellen Zustand des Plex Home Theater Clients zu überwachen, und entsprechend den Client Socket zu aktivieren/deaktivieren.
Es wird mit einem Timer versehen, der im Standard jede Sekunde aufgerufen wird. Der Timer kann nach eigenem Ermessen verändert werden, jedoch hat sich ein Intervall von 1s als Optimal herausgestellt.*

## 6. Funktionen

```php
PHT_Back(integer $InstanceID)
```
Sendet Befehl 'Zurück' an Plex Home Theater Client.

---
```php
PHT_Down(integer $InstanceID)
```
Sendet Befehl 'Runter' an Plex Home Theater Client.

---
```php
PHT_GetPlayerID(integer $InstanceID)
```
Liefert die aktuelle Player ID.

---
```php
PHT_GetSocketID(integer $InstanceID)
```
Liefert die aktuelle ID des zugehörigen Client Sockets.

---
```php
PHT_Left(integer $InstanceID)
```
Sendet Befehl 'Links' an Plex Home Theater Client.

---
```php
PHT_Next(integer $InstanceID)
```
Springt zum nächsten Titel.

---
```php
PHT_Pause(integer $InstanceID)
```
Pausiert die Wiedergabe.

---
```php
PHT_Play(integer $InstanceID)
```
Setzt die Wiedergabe fort. 

---
```php
PHT_PowerOff(integer $InstanceID)
```
Veranlasst ein Herunterfahren des Plex Home Theater Client Systemes.

---
```php
PHT_PowerOn(integer $InstanceID)
```
Sendet ein Wake on LAN (WOL) Befehl an die MAC-Adresse des Plex Home Theater Client Systemes. 

---
```php
PHT_Prev(integer $InstanceID)
```
Springt zum vorherigen Titel.

---
```php
PHT_RepeatActualElement(integer $InstanceID)
```
Setzt die Wiederholung auf das aktuell abgespielte Element.

---
```php
PHT_RepeatAll(integer $InstanceID)
```
Setzt die Wiederholung auf die aktuelle Playlist.

---
```php
PHT_RepeatOff(integer $InstanceID)
```
Wiederholungen ausschalten.

---
```php
PHT_Right(integer $InstanceID)
```
Sendet Befehl 'Rechts' an Plex Home Theater Client.

---
```php
PHT_Select(integer $InstanceID)
```
Sendet Befehl 'Auswahl' an Plex Home Theater Client.

---
```php
PHT_Send(integer $InstanceID, string $JSONString)
```
Sendet einen Befehl an den Plex Home Theater Client.
Der Befehl muss full qualified JSON String sein. 
Dazu sieht man sich am besten die API Dokumentation des XMBC Kodi Projektes an: http://kodi.wiki/view/JSON-RPC_API

---
```php
PHT_SendMessage(integer $InstanceID, string $title, string $message)
```
Sendet eine Nachricht mit Titel an den Plex Home Theater Client. Die Nachricht wird dann auf der Oberfläche des Clients angezeigt.

---
```php
PHT_SetVolume(integer $InstanceID, integer $level)
```
Passt die Lautstärke an.
Mögliche Werte liegen zwischen 0 and 100.

---
```php
PHT_Stop(integer $InstanceID)
```
Stoppt die Wiedergabe.
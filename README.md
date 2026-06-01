# NestSignal

NestSignal ist ein Physical-Computing-Prototyp mit WebApp, der Eltern mit Kleinkindern dabei hilft zu erkennen, ob ihr Kind wirklich aufgewacht ist, ohne unnötige Fehlalarme.

**Modul:** Interaktive Medien 4 – FH Graubünden (FS26)  
**Team Physical Computing:** Indira Hagmann, Lorenzo Reimann  
**Team WebApp:** Damiana Daffré, Melinda Widmer

---

## Das Problem

Herkömmliche Babyphones reagieren auf jedes kleine Geräusch und wecken Eltern unnötig. NestSignal erkennt erst bei wiederholten Signalen, ob das Kind wirklich wach ist.

---

## WebApp

🔗 [https://djil.afopulax.myhostpoint.ch](https://djil.afopulax.myhostpoint.ch)  
🎥 Video-Dokumentation: [Link ergänzen]  
🎨 Figma: [Link zum Prototyp](https://www.figma.com/design/orPrJ2k2AYkupkEzLjqHvH/App-Konzeption_4-Gewinnt?node-id=23-533&t=qFewYKgeL5NJSKaq-1)

---

## Setup

### WebApp

1. Repository klonen
2. `system/config.php` erstellen (Vorlage: `system/config.example.php`)
3. SQL-Datei `database/migration.sql` in phpMyAdmin importieren
4. PHPMailer in `phpmailer/` ablegen
5. Dateien per SFTP auf Webserver hochladen
6. Testgerät in phpMyAdmin eintragen:
```sql
INSERT INTO families (name) VALUES ('Testfamilie');
INSERT INTO devices (serial_nr, family_id) VALUES ('NEST-0001-TEST', 1);
```

### Physical Computing

**Hardware:** ESP32-C6 DevKitC-1, INMP441 Mikrofon, SR602 PIR Sensor, LiFePO4 Akku + Lademodul

**Pinbelegung:**

| Sensor | Pin | GPIO |
|--------|-----|------|
| SR602 | OUT | GPIO2 |
| SR602 | VCC | 3V3 |
| SR602 | GND | GND |
| INMP441 | WS | GPIO5 |
| INMP441 | SCK | GPIO6 |
| INMP441 | SD | GPIO4 |
| INMP441 | VDD | 3V3 |
| INMP441 | GND / L/R | GND |

**Arduino IDE:**
1. Board: `ESP32C6 Dev Module`
2. Sketch: `arduino/NestSignal_ESP32/NestSignal_ESP32.ino`
3. WLAN + API-Secret in Zeilen 22–29 eintragen
4. Hochladen → Serial Monitor (115200 Baud)

---

## Wie es funktioniert

Der ESP32 misst alle 20 Sekunden Bewegung und Lärm und sendet die Daten per HTTP POST an die PHP-API. Bei 3 aufeinanderfolgenden positiven Messungen wird ein Wake-Event ausgelöst.

---

## Known Bugs

- Lärmschwelle muss je nach Umgebung manuell angepasst werden (`LAERM_SCHWELLE` im Arduino-Code)
- Abstand-Bug im Desktop-Layout der Einstellungsseite (Admin-Ansicht)
- Push-Notifications noch nicht implementiert

---

## Reflexion

**Physical Computing (Indira Hagmann)**

Ich hätte nie gedacht, dass ich einen Microcontroller mit einer Datenbank verbinden kann. Die grösste Überraschung war, wie viel Kleinkram zwischen Hardware und Software liegt – ein falscher GPIO-Pin, ein Sonderzeichen im Passwort, ein fehlender COM-Port. Aber genau das hat mich am meisten gelernt: systematisch debuggen. Das Team hat super funktioniert.

**WebApp (Damiana Daffré)**

Die grösste Herausforderung war, die WebApp fertigzustellen bevor die Sensoren liefen. Viel musste mit Mock-Daten getestet werden. Den SMTP-Versand und die Token-Authentifizierung von Grund auf selbst aufzubauen war aufwändig, aber sehr lehrreich.

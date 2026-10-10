# WebCert

[![Lizenz: GPL v3](https://img.shields.io/badge/Lizenz-GPLv3-blue.svg)](LICENSE)
![PHP ≥ 8.1](https://img.shields.io/badge/PHP-%E2%89%A5%208.1-777bb4.svg)
![Docker](https://img.shields.io/badge/Docker-bereit-2496ed.svg)

**Eigene Root-CA für das Heimnetz – mit Weboberfläche.**

WebCert erstellt eine private Zertifizierungsstelle (CA) und stellt damit TLS-Zertifikate für Webseiten und Geräte im Heimnetz aus: NAS, Router, Home Assistant, Proxmox, Pi-hole, interne Webseiten … Einmal das CA-Zertifikat auf allen Geräten installieren – danach gibt es keine Browser-Warnungen mehr.

Reines PHP mit der `openssl`-Erweiterung, ohne Datenbank, ohne Composer, ohne weitere Abhängigkeiten. Oberfläche auf Deutsch und Englisch.

---

## Inhalt

- [Funktionen](#funktionen)
- [Installation mit Docker](#installation-mit-docker)
- [Manuelle Installation](#manuelle-installation)
- [Nutzung](#nutzung)
- [Konfiguration](#konfiguration)
- [Datenablage und Sicherung](#datenablage-und-sicherung)
- [Hinweise zur Laufzeit](#hinweise-zur-laufzeit)
- [Sicherheit](#sicherheit)
- [Lizenz](#lizenz)

## Funktionen

- Root-CA erstellen (RSA 2048–4096 oder ECDSA P-256/P-384, 10–50 Jahre Laufzeit)
- CA-Schlüssel optional mit Passphrase verschlüsselt (AES-256)
- Optionale **Namensbeschränkung** (z. B. nur `*.lan`, `192.168.0.0/16`): Selbst ein gestohlener CA-Schlüssel taugt dann nicht für Angriffe auf fremde Webseiten
- Serverzertifikate mit beliebig vielen Hostnamen, Wildcards und IP-Adressen (IPv4/IPv6)
- Externe CSRs signieren (für Geräte, die ihren Schlüssel selbst erzeugen)
- Downloads: `.crt`, `.key`, `fullchain.pem`, kombiniertes PEM, PKCS#12/`.p12`
- Zertifikate erneuern, CA sichern und wieder importieren
- Installationsanleitung für Windows, macOS, iOS, Android, Firefox und Linux direkt in der Oberfläche
- CA-Zertifikat ohne Anmeldung abrufbar (`?a=ca-download`), damit Geräte es direkt laden können
- Oberfläche auf Deutsch und Englisch, weitere Sprachen leicht ergänzbar

## Installation mit Docker

Das ist der einfachste Weg. Voraussetzung: [Docker](https://docs.docker.com/get-docker/) mit Compose.

### Mit Docker Compose (empfohlen)

```bash
git clone https://github.com/sjanzon/WebCert.git
cd WebCert
docker compose up -d --build
```

WebCert läuft danach unter **http://localhost:8080** bzw. `http://<IP-des-Servers>:8080`.

Die mitgelieferte [`compose.yaml`](compose.yaml):

```yaml
services:
  webcert:
    build: .
    image: webcert
    container_name: webcert
    restart: unless-stopped
    ports:
      - "8080:80"
    environment:
      TZ: Europe/Berlin
      # WEBCERT_LANG: en
    volumes:
      - webcert-data:/data

volumes:
  webcert-data:
    name: webcert-data
```

Alle Daten (CA, Zertifikate, Passwort-Hash) liegen im Volume `webcert-data` und bleiben bei Updates und Neustarts erhalten.

### Mit `docker run`

```bash
docker build -t webcert .
docker volume create webcert-data
docker run -d --name webcert --restart unless-stopped \
  -p 8080:80 \
  -e TZ=Europe/Berlin \
  -v webcert-data:/data \
  webcert
```

### Datenordner statt Volume (Bind-Mount)

Wer die Daten lieber in einem Ordner auf dem Host haben möchte, ersetzt in der `compose.yaml` das Volume durch einen Pfad. Der Ordner muss dem Benutzer `www-data` (UID 33) im Container gehören:

```bash
mkdir -p ./data && sudo chown 33:33 ./data && sudo chmod 700 ./data
```

```yaml
    volumes:
      - ./data:/data
```

### Update

```bash
git pull
docker compose up -d --build
```

### Docker-Volume sichern und wiederherstellen

```bash
# Sichern
docker run --rm -v webcert-data:/data -v "$PWD":/backup alpine \
  tar czf /backup/webcert-data.tgz -C /data .

# Wiederherstellen
docker run --rm -v webcert-data:/data -v "$PWD":/backup alpine \
  sh -c "tar xzf /backup/webcert-data.tgz -C /data && chown -R 33:33 /data"
```

### HTTPS vor dem Container

Der Container spricht nur HTTP. Für den Dauerbetrieb empfiehlt es sich, einen Reverse-Proxy (Caddy, nginx, Traefik …) davorzusetzen und dafür ein mit WebCert selbst ausgestelltes Zertifikat zu verwenden. Beispiel für Caddy:

```caddyfile
webcert.lan {
    tls /etc/caddy/certs/webcert.lan-fullchain.pem /etc/caddy/certs/webcert.lan.key
    reverse_proxy webcert:80
}
```

## Manuelle Installation

### Voraussetzungen

- PHP **8.1 oder neuer** mit der Erweiterung `openssl` (unter Windows in der `php.ini` `extension=openssl` aktivieren)
- Ein Webserver (Apache, nginx, Caddy …) oder für einen ersten Test der eingebaute PHP-Server

### Schnelltest

```bash
git clone https://github.com/sjanzon/WebCert.git
cd WebCert
php -S 0.0.0.0:8080 -t public
```

Danach http://localhost:8080 aufrufen. Der eingebaute Server ist nur zum Ausprobieren gedacht.

### Dauerbetrieb mit Apache oder nginx

**Wichtig:** Das Document Root muss auf den Ordner **`public/`** zeigen – niemals auf das Projektverzeichnis. Nur so bleiben `src/` und `data/` (mit den privaten Schlüsseln!) unerreichbar.

Der Webserver-Benutzer braucht Schreibrechte auf `data/`:

```bash
mkdir -p data && sudo chown www-data:www-data data && sudo chmod 700 data
```

<details>
<summary>Apache – virtueller Host</summary>

```apache
<VirtualHost *:80>
    ServerName webcert.lan
    DocumentRoot /var/www/WebCert/public

    <Directory /var/www/WebCert/public>
        Require all granted
    </Directory>
</VirtualHost>
```
</details>

<details>
<summary>nginx mit PHP-FPM</summary>

```nginx
server {
    listen 80;
    server_name webcert.lan;
    root /var/www/WebCert/public;
    index index.php;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php-fpm.sock;
    }
}
```
</details>

<details>
<summary>Laragon / XAMPP (Windows)</summary>

Projekt in das `www`- bzw. `htdocs`-Verzeichnis legen und das Document Root des virtuellen Hosts auf `WebCert/public` setzen. Sicherstellen, dass in der `php.ini` `extension=openssl` aktiv ist.
</details>

## Nutzung

### 1. Ersteinrichtung

Beim ersten Aufruf legst du ein **Passwort für die Oberfläche** fest (mindestens 8 Zeichen). Wer Zugriff auf WebCert hat, kann Zertifikate ausstellen, denen alle deine Geräte vertrauen – also ein starkes Passwort wählen.

### 2. Root-CA erstellen

Unter **Root-CA erstellen**:

- **Name** der CA, z. B. „Heimnetz Root CA“
- **Laufzeit**: 10–50 Jahre. Die CA-Laufzeit begrenzt auch die Laufzeit aller ausgestellten Zertifikate.
- **Schlüssel**: empfohlen RSA 4096 oder ECDSA P-384
- **Passphrase** (empfohlen): Der CA-Schlüssel wird verschlüsselt gespeichert und die Passphrase bei jeder Ausstellung abgefragt. **Geht die Passphrase verloren, lassen sich keine Zertifikate mehr ausstellen.**
- **Namensbeschränkung** (optional, empfohlen): z. B. `lan`, `home.arpa`, `fritz.box`, `192.168.0.0/16`. Die CA kann dann nur Zertifikate für diese Domains und Netze ausstellen. Lässt sich später nicht ändern.

Alternativ lässt sich eine bestehende CA importieren, z. B. aus einer WebCert-Sicherung.

### 3. CA-Zertifikat auf den Geräten installieren

Auf der Seite **Root-CA** stehen der Download (`.crt` / `.cer`) und Schritt-für-Schritt-Anleitungen für Windows, macOS, iPhone/iPad, Android, Firefox und Linux. Der direkte Download-Link funktioniert ohne Anmeldung – praktisch, um ihn auf dem Smartphone zu öffnen:

```
http://<webcert-adresse>/?a=ca-download
```

Vergleiche beim Installieren den angezeigten SHA-256-Fingerabdruck mit dem Wert in WebCert.

### 4. Zertifikat ausstellen

Unter **Ausstellen**:

- **Haupt-Hostname**, z. B. `nas.lan`
- **Weitere Hostnamen / IP-Adressen**, eine pro Zeile, z. B. `nas`, `*.nas.lan`, `192.168.1.10`. Trage alle Namen ein, unter denen das Gerät aufgerufen wird – auch die IP-Adresse, wenn du per IP zugreifst.
- **Laufzeit**: Standard 825 Tage (Maximum für Apple-Geräte, siehe [unten](#hinweise-zur-laufzeit))
- Optional: zusätzlich für **Client-Authentifizierung** (VPN, mTLS)

WebCert erzeugt Schlüssel und Zertifikat. Anschließend stehen diese Downloads bereit:

| Datei | Inhalt | Typische Verwendung |
|---|---|---|
| `<name>-fullchain.pem` | Zertifikat + CA-Zertifikat | nginx `ssl_certificate`, Caddy, Traefik, Home Assistant |
| `<name>.key` | Privater Schlüssel | nginx `ssl_certificate_key`, Apache `SSLCertificateKeyFile` |
| `<name>.crt` | Nur das Zertifikat | Apache `SSLCertificateFile`, Geräte mit getrennter Kette |
| `<name>-combined.pem` | Schlüssel + Zertifikat + CA | HAProxy, Pi-hole/lighttpd, manche Router |
| `<name>.p12` | PKCS#12 mit Passwort | Windows/IIS, Synology, Java |

### 5. CSR signieren

Manche Geräte (Router, NAS, Drucker, Proxmox …) erzeugen ihren privaten Schlüssel selbst und exportieren nur einen *Certificate Signing Request*. Unter **CSR signieren** die CSR-Datei hochladen oder den PEM-Text einfügen und alle Hostnamen/IP-Adressen angeben. Der private Schlüssel verlässt das Gerät dabei nie.

### 6. Erneuern und Löschen

Auf der Detailseite eines Zertifikats stellt **Erneuern** ein neues Zertifikat mit denselben Namen und derselben Laufzeit aus. Das alte bleibt bestehen, bis du es löschst – so kannst du das neue in Ruhe einspielen. Zertifikate, die in 30 Tagen oder weniger ablaufen, sind in der Übersicht markiert.

### Sprache

Umschalten über **DE / EN** oben rechts; die Wahl wird per Cookie gespeichert.

## Konfiguration

Die Konfiguration erfolgt über Umgebungsvariablen:

| Variable | Standard | Bedeutung |
|---|---|---|
| `WEBCERT_DATA` | `data/` im Projekt (Docker: `/data`) | Speicherort für CA, Zertifikate und Passwort-Hash |
| `WEBCERT_LANG` | – | Standardsprache (`de`, `en`), falls im Browser noch keine gewählt wurde |
| `TZ` | `Europe/Berlin` | Zeitzone (nur Docker; sonst `date.timezone` in der `php.ini`) |

Die Sprache wird in dieser Reihenfolge bestimmt: Auswahl im Umschalter → `WEBCERT_LANG` → Browser-Sprache → Deutsch.

**Weitere Sprachen hinzufügen:** `src/lang/en.php` kopieren (z. B. nach `src/lang/fr.php`), `name` und Formate anpassen und die Texte übersetzen. Die Sprache erscheint automatisch im Umschalter. Schlüssel ist jeweils der deutsche Originaltext; fehlende Übersetzungen werden auf Deutsch angezeigt.

**Passwort vergessen?** Die Datei `auth.json` im Datenordner löschen – beim nächsten Aufruf wird ein neues Passwort festgelegt. CA und Zertifikate bleiben erhalten.

## Datenablage und Sicherung

Alle Daten liegen im Datenordner (`data/` bzw. das Docker-Volume):

```
auth.json            Passwort-Hash der Oberfläche
ca/ca.crt, ca.key    Root-CA
certs/<id>/          cert.pem, key.pem bzw. csr.pem, meta.json
```

- Den Datenordner regelmäßig sichern – oder in der Oberfläche unter **Root-CA → Sicherung** CA-Zertifikat und -Schlüssel als eine Datei herunterladen. Damit lässt sich die CA bei einer Neuinstallation wieder importieren.
- Die privaten Schlüssel der Serverzertifikate liegen unverschlüsselt im Datenordner. Zugriff auf den Server entsprechend schützen.
- Der Datenordner ist per `.gitignore` und `.dockerignore` ausgeschlossen und darf **niemals** in ein Repository oder Image gelangen.

## Hinweise zur Laufzeit

- Apple-Geräte (iOS, iPadOS, macOS/Safari) akzeptieren TLS-Serverzertifikate nur bis **825 Tage** – auch von privaten CAs. Daher ist das der Standardwert.
- Windows, Android, Chrome und Firefox akzeptieren bei selbst installierten CAs auch deutlich längere Laufzeiten (10 Jahre und mehr).
- Ein Zertifikat kann nie länger gültig sein als die CA; längere Angaben werden automatisch gekürzt.

## Sicherheit

- WebCert sollte **nur im Heimnetz** erreichbar sein – niemals direkt aus dem Internet. Am besten stellst du für WebCert selbst ein Zertifikat aus und betreibst es per HTTPS.
- Wer die Oberfläche bedienen kann, kann Zertifikate ausstellen, denen alle deine Geräte vertrauen. Starkes Passwort und CA-Passphrase verwenden, Namensbeschränkung nutzen.
- Ein Widerruf (CRL/OCSP) ist nicht implementiert. Kompromittierte Zertifikate auf dem Server austauschen; bei kompromittierter CA die CA neu erstellen und auf allen Geräten austauschen.

## Lizenz

WebCert ist freie Software unter der [GNU General Public License v3.0](LICENSE) oder (nach deiner Wahl) einer späteren Version.

Du darfst das Programm nutzen, verändern und weitergeben. Wenn du eine veränderte Version weitergibst, muss sie ebenfalls unter der GPL stehen und ihr Quellcode verfügbar sein. Das Programm wird **ohne jegliche Gewährleistung** bereitgestellt – Details siehe [LICENSE](LICENSE).

Copyright © 2026 Sven Janzon

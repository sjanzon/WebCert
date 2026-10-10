# WebCert

[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](LICENSE)
![PHP ≥ 8.1](https://img.shields.io/badge/PHP-%E2%89%A5%208.1-777bb4.svg)
![Docker](https://img.shields.io/badge/Docker-ready-2496ed.svg)

[Deutsch](README.md) · **English**

**Your own root CA for your home network – with a web interface.**

WebCert creates a private certificate authority (CA) and uses it to issue TLS certificates for websites and devices on your home network: NAS, router, Home Assistant, Proxmox, Pi-hole, internal websites … Install the CA certificate on all your devices once – and browser warnings are gone for good.

Plain PHP with the `openssl` extension, no database, no Composer, no other dependencies. Interface in German and English.

---

## Contents

- [Features](#features)
- [Installation with Docker](#installation-with-docker)
- [Manual installation](#manual-installation)
- [Usage](#usage)
- [Configuration](#configuration)
- [Data storage and backup](#data-storage-and-backup)
- [Notes on validity periods](#notes-on-validity-periods)
- [Security](#security)
- [License](#license)

## Features

- Create a root CA (RSA 2048–4096 or ECDSA P-256/P-384, 10–50 years validity)
- CA key optionally encrypted with a passphrase (AES-256)
- Optional **name constraints** (e.g. only `*.lan`, `192.168.0.0/16`): even a stolen CA key is then useless for attacks on other websites
- Server certificates with any number of hostnames, wildcards and IP addresses (IPv4/IPv6)
- Sign external CSRs (for devices that generate their own key)
- Downloads: `.crt`, `.key`, `fullchain.pem`, combined PEM, PKCS#12/`.p12`
- Renew certificates, back up and re-import the CA
- Installation instructions for Windows, macOS, iOS, Android, Firefox and Linux right in the interface
- CA certificate available without login (`?a=ca-download`) so devices can fetch it directly
- Interface in German and English, more languages easy to add

## Installation with Docker

This is the easiest way. Requirement: [Docker](https://docs.docker.com/get-docker/) with Compose.

### With Docker Compose (recommended)

```bash
git clone https://github.com/sjanzon/WebCert.git
cd WebCert
docker compose up -d --build
```

WebCert then runs at **http://localhost:8080** or `http://<server-ip>:8080`.

The included [`compose.yaml`](compose.yaml):

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

All data (CA, certificates, password hash) is stored in the `webcert-data` volume and survives updates and restarts. To default the interface to English, uncomment `WEBCERT_LANG: en`.

### With `docker run`

```bash
docker build -t webcert .
docker volume create webcert-data
docker run -d --name webcert --restart unless-stopped \
  -p 8080:80 \
  -e TZ=Europe/Berlin \
  -v webcert-data:/data \
  webcert
```

### Data folder instead of a volume (bind mount)

If you prefer to keep the data in a folder on the host, replace the volume in `compose.yaml` with a path. The folder must be owned by the `www-data` user (UID 33) inside the container:

```bash
mkdir -p ./data && sudo chown 33:33 ./data && sudo chmod 700 ./data
```

```yaml
    volumes:
      - ./data:/data
```

### Updating

```bash
git pull
docker compose up -d --build
```

### Backing up and restoring the Docker volume

```bash
# Back up
docker run --rm -v webcert-data:/data -v "$PWD":/backup alpine \
  tar czf /backup/webcert-data.tgz -C /data .

# Restore
docker run --rm -v webcert-data:/data -v "$PWD":/backup alpine \
  sh -c "tar xzf /backup/webcert-data.tgz -C /data && chown -R 33:33 /data"
```

### HTTPS in front of the container

The container only speaks HTTP. For permanent use, put a reverse proxy (Caddy, nginx, Traefik …) in front of it, using a certificate issued by WebCert itself. Example for Caddy:

```caddyfile
webcert.lan {
    tls /etc/caddy/certs/webcert.lan-fullchain.pem /etc/caddy/certs/webcert.lan.key
    reverse_proxy webcert:80
}
```

## Manual installation

### Requirements

- PHP **8.1 or newer** with the `openssl` extension (on Windows, enable `extension=openssl` in `php.ini`)
- A web server (Apache, nginx, Caddy …) or, for a first test, PHP's built-in server

### Quick test

```bash
git clone https://github.com/sjanzon/WebCert.git
cd WebCert
php -S 0.0.0.0:8080 -t public
```

Then open http://localhost:8080. The built-in server is meant for trying things out only.

### Permanent use with Apache or nginx

**Important:** the document root must point to the **`public/`** folder – never to the project directory. Only then do `src/` and `data/` (containing the private keys!) stay unreachable.

The web server user needs write access to `data/`:

```bash
mkdir -p data && sudo chown www-data:www-data data && sudo chmod 700 data
```

<details>
<summary>Apache – virtual host</summary>

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
<summary>nginx with PHP-FPM</summary>

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

Place the project in the `www` or `htdocs` directory and set the virtual host's document root to `WebCert/public`. Make sure `extension=openssl` is enabled in `php.ini`.
</details>

## Usage

### 1. Initial setup

On first access you set a **password for the interface** (at least 8 characters). Anyone with access to WebCert can issue certificates that all your devices trust – so choose a strong password.

### 2. Create the root CA

Under **Create root CA**:

- **Name** of the CA, e.g. "Home Network Root CA"
- **Validity**: 10–50 years. The CA's validity also limits the validity of all certificates it issues.
- **Key**: RSA 4096 or ECDSA P-384 recommended
- **Passphrase** (recommended): the CA key is stored encrypted and the passphrase is requested every time you issue a certificate. **If the passphrase is lost, no more certificates can be issued.**
- **Name constraints** (optional, recommended): e.g. `lan`, `home.arpa`, `fritz.box`, `192.168.0.0/16`. The CA can then only issue certificates for these domains and networks. Cannot be changed later.

Alternatively, you can import an existing CA, e.g. from a WebCert backup.

### 3. Install the CA certificate on your devices

The **Root CA** page offers the download (`.crt` / `.cer`) and step-by-step instructions for Windows, macOS, iPhone/iPad, Android, Firefox and Linux. The direct download link works without login – handy for opening it on a smartphone:

```
http://<webcert-address>/?a=ca-download
```

When installing, compare the SHA-256 fingerprint shown with the value in WebCert.

### 4. Issue a certificate

Under **Issue**:

- **Primary hostname**, e.g. `nas.lan`
- **Additional hostnames / IP addresses**, one per line, e.g. `nas`, `*.nas.lan`, `192.168.1.10`. Enter every name the device is accessed by – including the IP address if you access it by IP.
- **Validity**: default 825 days (maximum for Apple devices, see [below](#notes-on-validity-periods))
- Optional: also for **client authentication** (VPN, mTLS)

WebCert generates the key and certificate. The following downloads are then available:

| File | Contents | Typical use |
|---|---|---|
| `<name>-fullchain.pem` | Certificate + CA certificate | nginx `ssl_certificate`, Caddy, Traefik, Home Assistant |
| `<name>.key` | Private key | nginx `ssl_certificate_key`, Apache `SSLCertificateKeyFile` |
| `<name>.crt` | Certificate only | Apache `SSLCertificateFile`, devices with a separate chain |
| `<name>-combined.pem` | Key + certificate + CA | HAProxy, Pi-hole/lighttpd, some routers |
| `<name>.p12` | PKCS#12 with password | Windows/IIS, Synology, Java |

### 5. Sign a CSR

Some devices (routers, NAS, printers, Proxmox …) generate their private key themselves and only export a *certificate signing request*. Under **Sign CSR**, upload the CSR file or paste the PEM text and enter all hostnames/IP addresses. The private key never leaves the device.

### 6. Renew and delete

On a certificate's detail page, **Renew** issues a new certificate with the same names and the same validity. The old one stays until you delete it – so you can deploy the new one at your own pace. Certificates expiring in 30 days or less are highlighted in the overview.

### Language

Switch via **DE / EN** in the top right; the choice is stored in a cookie.

## Configuration

Configuration is done via environment variables:

| Variable | Default | Meaning |
|---|---|---|
| `WEBCERT_DATA` | `data/` in the project (Docker: `/data`) | Storage location for CA, certificates and password hash |
| `WEBCERT_LANG` | – | Default language (`de`, `en`) if none has been chosen in the browser yet |
| `TZ` | `Europe/Berlin` | Time zone (Docker only; otherwise `date.timezone` in `php.ini`) |

The language is determined in this order: selection in the switcher → `WEBCERT_LANG` → browser language → German.

**Adding more languages:** copy `src/lang/en.php` (e.g. to `src/lang/fr.php`), adjust `name` and the formats, and translate the texts. The language appears in the switcher automatically. Each key is the original German text; missing translations are shown in German.

**Forgot your password?** Delete the `auth.json` file in the data folder – a new password is set on the next visit. The CA and certificates are kept.

## Data storage and backup

All data lives in the data folder (`data/` or the Docker volume):

```
auth.json            Password hash for the interface
ca/ca.crt, ca.key    Root CA
certs/<id>/          cert.pem, key.pem or csr.pem, meta.json
```

- Back up the data folder regularly – or download the CA certificate and key as a single file in the interface under **Root CA → Backup**. This lets you re-import the CA after a fresh installation.
- The private keys of the server certificates are stored unencrypted in the data folder. Protect access to the server accordingly.
- The data folder is excluded via `.gitignore` and `.dockerignore` and must **never** end up in a repository or image.

## Notes on validity periods

- Apple devices (iOS, iPadOS, macOS/Safari) only accept TLS server certificates valid for up to **825 days** – even from private CAs. That is why it is the default.
- Windows, Android, Chrome and Firefox accept much longer validity periods (10 years and more) for manually installed CAs.
- A certificate can never be valid longer than the CA; longer values are shortened automatically.

## Security

- WebCert should be reachable **only from your home network** – never directly from the internet. Ideally, issue a certificate for WebCert itself and run it over HTTPS.
- Anyone who can use the interface can issue certificates that all your devices trust. Use a strong password and a CA passphrase, and make use of name constraints.
- Revocation (CRL/OCSP) is not implemented. Replace compromised certificates on the server; if the CA is compromised, create a new CA and replace it on all devices.

## License

WebCert is free software licensed under the [GNU General Public License v3.0](LICENSE) or (at your option) any later version.

You may use, modify and distribute the program. If you distribute a modified version, it must also be licensed under the GPL and its source code must be made available. The program is provided **without any warranty** – see [LICENSE](LICENSE) for details.

Copyright © 2026 Sven Janzon

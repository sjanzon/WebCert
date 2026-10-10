<?php
// Englische Übersetzung. Schlüssel ist jeweils der deutsche Originaltext aus dem Code.
return [
    'name'      => 'English',
    'date'      => 'Y-m-d',
    'datetime'  => 'Y-m-d H:i',
    'decimal'   => '.',
    'thousands' => ',',
    'strings'   => [
        // ---------------------------------------------------------- Allgemein & Navigation
        'Hauptnavigation'  => 'Main navigation',
        'Sprache'          => 'Language',
        'Übersicht'        => 'Overview',
        'Root-CA'          => 'Root CA',
        'Ausstellen'       => 'Issue',
        'CSR signieren'    => 'Sign CSR',
        'Abmelden'         => 'Log out',
        'Zur Übersicht'    => 'Back to overview',
        'Nicht gefunden'   => 'Not found',
        'Diese Seite gibt es nicht.' => 'This page does not exist.',
        'Fehler'           => 'Error',
        'Ungültiges Formular-Token. Bitte die Seite neu laden.' => 'Invalid form token. Please reload the page.',

        // ---------------------------------------------------------- Einrichtung & Anmeldung
        'Einrichtung'      => 'Setup',
        'Anmelden'         => 'Log in',
        'Passwort'         => 'Password',
        'Passwort wiederholen' => 'Repeat password',
        'Passwort speichern'   => 'Save password',
        'Falsches Passwort.'   => 'Wrong password.',
        'Willkommen bei WebCert' => 'Welcome to WebCert',
        'Lege ein Passwort für die Verwaltungsoberfläche fest. Wer Zugriff auf diese Oberfläche hat, kann Zertifikate ausstellen, denen alle deine Geräte vertrauen – wähle also ein starkes Passwort.'
            => 'Set a password for the admin interface. Anyone with access to this interface can issue certificates that all your devices will trust – so choose a strong password.',
        'Das Passwort muss mindestens 8 Zeichen lang sein.' => 'The password must be at least 8 characters long.',
        'Die Passwörter stimmen nicht überein.' => 'The passwords do not match.',
        'Passwort gespeichert. Als Nächstes erstellst du deine Root-CA.' => 'Password saved. Next, create your root CA.',

        // ---------------------------------------------------------- Status
        'abgelaufen'                  => 'expired',
        'läuft in {days} Tagen ab'    => 'expires in {days} days',
        'gültig'                      => 'valid',

        // ---------------------------------------------------------- Übersicht
        'Noch keine Root-CA' => 'No root CA yet',
        'Erstelle zuerst eine eigene Root-CA. Deren Zertifikat installierst du einmalig auf deinen Geräten – danach vertrauen sie allen Zertifikaten, die du hier ausstellst.'
            => 'First create your own root CA. You install its certificate once on your devices – after that they trust every certificate you issue here.',
        'Root-CA erstellen'      => 'Create root CA',
        'Zertifikat ausstellen'  => 'Issue certificate',
        'gültig bis {date} (noch {years} Jahre)' => 'valid until {date} ({years} years left)',
        'CA-Zertifikat (.crt)'   => 'CA certificate (.crt)',
        'Installationsanleitung' => 'Installation guide',
        'Zertifikate'            => 'Certificates',
        'Noch keine Zertifikate ausgestellt.' => 'No certificates issued yet.',
        'Das erste Zertifikat ausstellen'     => 'Issue the first certificate',
        'Name'             => 'Name',
        'Hostnamen / IPs'  => 'Hostnames / IPs',
        'Schlüssel'        => 'Key',
        'Gültig bis'       => 'Valid until',
        'Status'           => 'Status',
        'bis'              => 'until',

        // ---------------------------------------------------------- Root-CA
        'CA-Zertifikat (.crt, PEM)' => 'CA certificate (.crt, PEM)',
        'Details'          => 'Details',
        'Typ'              => 'Type',
        'Schlüssel verschlüsselt'   => 'key encrypted',
        'Schlüssel unverschlüsselt' => 'key not encrypted',
        'Gültig'           => 'Valid',
        'Seriennummer'     => 'Serial number',
        'Beschränkt auf'   => 'Restricted to',
        'Vergleiche beim Installieren auf einem Gerät den angezeigten Fingerabdruck mit diesem Wert.'
            => 'When installing on a device, compare the fingerprint shown there with this value.',
        'Direkter Download-Link für Geräte (ohne Anmeldung):' => 'Direct download link for devices (no login required):',
        'Auf Geräten als vertrauenswürdig installieren' => 'Install as trusted on your devices',
        '<code>{file}.crt</code> herunterladen und doppelklicken → <em>Zertifikat installieren…</em>'
            => 'Download <code>{file}.crt</code> and double-click it → <em>Install Certificate…</em>',
        '<em>Lokaler Computer</em> wählen → <em>Alle Zertifikate in folgendem Speicher speichern</em> → <em>Vertrauenswürdige Stammzertifizierungsstellen</em>.'
            => 'Choose <em>Local Machine</em> → <em>Place all certificates in the following store</em> → <em>Trusted Root Certification Authorities</em>.',
        'Browser neu starten.' => 'Restart the browser.',
        'Alternativ in einer Administrator-Konsole:' => 'Alternatively, in an administrator console:',
        'Datei doppelklicken → Schlüsselbund <em>System</em> wählen.' => 'Double-click the file → choose the <em>System</em> keychain.',
        'In der Schlüsselbundverwaltung das Zertifikat öffnen → <em>Vertrauen</em> → <em>Bei Verwendung dieses Zertifikats: Immer vertrauen</em>.'
            => 'Open the certificate in Keychain Access → <em>Trust</em> → <em>When using this certificate: Always Trust</em>.',
        'Terminal:' => 'Terminal:',
        'Den Download-Link in <strong>Safari</strong> öffnen → Profil laden erlauben.' => 'Open the download link in <strong>Safari</strong> → allow the profile download.',
        '<em>Einstellungen → Profil geladen</em> → <em>Installieren</em>.' => '<em>Settings → Profile Downloaded</em> → <em>Install</em>.',
        '<em>Einstellungen → Allgemein → Info → Zertifikatsvertrauenseinstellungen</em> → für die CA <em>Volles Vertrauen</em> aktivieren.'
            => '<em>Settings → General → About → Certificate Trust Settings</em> → enable <em>full trust</em> for the CA.',
        '<code>.crt</code> herunterladen.' => 'Download the <code>.crt</code> file.',
        '<em>Einstellungen → Sicherheit → Weitere Sicherheitseinstellungen → Verschlüsselung &amp; Anmeldedaten → Zertifikat installieren → CA-Zertifikat</em> (Bezeichnung je nach Hersteller abweichend).'
            => '<em>Settings → Security → More security settings → Encryption &amp; credentials → Install a certificate → CA certificate</em> (wording varies by manufacturer).',
        'Hinweis: Chrome nutzt so installierte CAs, viele Apps vertrauen Benutzer-CAs unter Android aber grundsätzlich nicht.'
            => 'Note: Chrome uses CAs installed this way, but many Android apps never trust user-installed CAs.',
        'Unter Windows und macOS übernimmt Firefox die System-Zertifikate automatisch. Andernfalls: <em>Einstellungen → Datenschutz &amp; Sicherheit → Zertifikate anzeigen → Zertifizierungsstellen → Importieren</em> → <em>Dieser CA vertrauen, um Websites zu identifizieren</em>.'
            => 'On Windows and macOS, Firefox picks up the system certificates automatically. Otherwise: <em>Settings → Privacy &amp; Security → View Certificates → Authorities → Import</em> → <em>Trust this CA to identify websites</em>.',
        'Chrome/Chromium unter Linux nutzt eine eigene NSS-Datenbank:' => 'Chrome/Chromium on Linux uses its own NSS database:',
        'Sicherung' => 'Backup',
        'Lädt CA-Zertifikat und privaten Schlüssel (weiterhin mit deiner Passphrase verschlüsselt) als eine Datei herunter.'
            => 'Downloads the CA certificate and private key (still encrypted with your passphrase) as a single file.',
        'Lädt CA-Zertifikat und privaten Schlüssel <strong>unverschlüsselt</strong> als eine Datei herunter.'
            => 'Downloads the CA certificate and private key <strong>unencrypted</strong> as a single file.',
        'Damit lässt sich die CA bei einer Neuinstallation wieder importieren. Bewahre die Datei sicher auf.'
            => 'Use it to import the CA again after a reinstall. Keep the file in a safe place.',
        'Sicherung herunterladen' => 'Download backup',
        'CA löschen' => 'Delete CA',
        'Löscht die CA <strong>und alle ausgestellten Zertifikate</strong>. Vorhandene Zertifikate auf deinen Servern bleiben bis zum Ablauf gültig, können aber nicht mehr erneuert werden.'
            => 'Deletes the CA <strong>and all issued certificates</strong>. Certificates already deployed on your servers stay valid until they expire, but can no longer be renewed.',
        'LÖSCHEN'           => 'DELETE',
        '{word} eingeben'   => 'Type {word}',
        'Endgültig löschen' => 'Delete permanently',
        'Zum Löschen bitte LÖSCHEN in das Bestätigungsfeld eingeben.' => 'To delete, please type DELETE into the confirmation field.',
        'Die CA und alle Zertifikate wurden gelöscht.' => 'The CA and all certificates have been deleted.',
        'Bitte zuerst eine Root-CA erstellen.' => 'Please create a root CA first.',

        // ---------------------------------------------------------- CA erstellen / importieren
        'CA erstellen' => 'Create CA',
        'Heimnetz Root CA' => 'Home Network Root CA',
        'Heimnetz' => 'Home Network',
        'Die Root-CA ist die Vertrauensbasis für dein Heimnetz. Ihr Zertifikat wird auf allen Geräten als vertrauenswürdig installiert; anschließend stellst du damit Zertifikate für deine Webseiten aus.'
            => 'The root CA is the foundation of trust for your home network. Its certificate is installed as trusted on all your devices; you then use it to issue certificates for your websites.',
        'Bezeichnung'     => 'Name',
        'Name der CA (Common Name)' => 'CA name (common name)',
        'Organisation'    => 'Organization',
        'Abteilung'       => 'Organizational unit',
        'Land (2 Buchstaben)' => 'Country (2 letters)',
        'Technik'         => 'Technical',
        'Laufzeit'        => 'Validity',
        '{n} Jahre'       => '{n} years',
        'Für die CA empfiehlt sich RSA 4096 oder ECDSA P-384. Die Laufzeit der CA begrenzt auch die maximale Laufzeit der ausgestellten Zertifikate.'
            => 'RSA 4096 or ECDSA P-384 is recommended for the CA. The CA\'s validity also limits the maximum validity of the certificates it issues.',
        'Passphrase für den CA-Schlüssel' => 'Passphrase for the CA key',
        'Passphrase'      => 'Passphrase',
        'Passphrase wiederholen' => 'Repeat passphrase',
        'Empfohlen. Der private Schlüssel der CA wird damit AES-256-verschlüsselt gespeichert und die Passphrase wird bei jeder Ausstellung abgefragt. Leer lassen = unverschlüsselt. <strong>Ohne Passphrase lassen sich keine Zertifikate mehr ausstellen – gut aufbewahren!</strong>'
            => 'Recommended. The CA\'s private key is stored AES-256 encrypted and the passphrase is requested every time you issue a certificate. Leave empty = unencrypted. <strong>Without the passphrase no more certificates can be issued – keep it safe!</strong>',
        'Namensbeschränkung (optional, empfohlen für mehr Sicherheit)' => 'Name constraints (optional, recommended for extra security)',
        'Erlaubte Domains und Netze' => 'Permitted domains and networks',
        'Beschränkt die CA technisch auf die angegebenen Domains (inkl. Subdomains) und IP-Netze. Selbst wenn der CA-Schlüssel gestohlen würde, ließen sich damit keine gültigen Zertifikate für z. B. <code>google.com</code> oder deine Bank erzeugen. Werden nur Domains angegeben, sind IP-Adressen nicht beschränkt (und umgekehrt). Die Beschränkung kann später nicht geändert werden.'
            => 'Technically restricts the CA to the given domains (including subdomains) and IP networks. Even if the CA key were stolen, it could not be used to create valid certificates for e.g. <code>google.com</code> or your bank. If only domains are given, IP addresses are not restricted (and vice versa). The constraints cannot be changed later.',
        'Bestehende CA importieren' => 'Import existing CA',
        '(z. B. aus einer Sicherung)' => '(e.g. from a backup)',
        'CA-Zertifikat (Datei)' => 'CA certificate (file)',
        'Privater Schlüssel (Datei)' => 'Private key (file)',
        '… oder Zertifikat als PEM einfügen' => '… or paste the certificate as PEM',
        '… oder Schlüssel als PEM einfügen' => '… or paste the key as PEM',
        'Bei einer WebCert-Sicherung (<code>*-backup.pem</code>) kann dieselbe Datei in beiden Feldern ausgewählt werden.'
            => 'For a WebCert backup (<code>*-backup.pem</code>) you can select the same file in both fields.',
        'Passphrase des Schlüssels (falls verschlüsselt)' => 'Key passphrase (if encrypted)',
        'CA importieren' => 'Import CA',
        'Die CA wurde importiert.' => 'The CA has been imported.',
        'Die Root-CA wurde erstellt. Installiere jetzt das CA-Zertifikat auf deinen Geräten.' => 'The root CA has been created. Now install the CA certificate on your devices.',

        // ---------------------------------------------------------- Schlüsseltypen
        'RSA 2048 Bit (maximale Kompatibilität)' => 'RSA 2048 bit (maximum compatibility)',
        'RSA 3072 Bit' => 'RSA 3072 bit',
        'RSA 4096 Bit' => 'RSA 4096 bit',
        'ECDSA P-256 (schnell, modern)' => 'ECDSA P-256 (fast, modern)',

        // ---------------------------------------------------------- Zertifikat ausstellen / CSR
        'Für Geräte, die ihren privaten Schlüssel selbst erzeugen (Router, NAS, Drucker, Proxmox …). Lade den dort erstellten Certificate Signing Request hoch – der private Schlüssel verlässt das Gerät nie.'
            => 'For devices that generate their own private key (routers, NAS, printers, Proxmox …). Upload the certificate signing request created there – the private key never leaves the device.',
        'Erzeugt einen neuen privaten Schlüssel und ein von deiner Root-CA signiertes Zertifikat.' => 'Generates a new private key and a certificate signed by your root CA.',
        'CSR-Datei' => 'CSR file',
        '… oder PEM einfügen' => '… or paste PEM',
        'Weitere Hostnamen / IP-Adressen' => 'Additional hostnames / IP addresses',
        'Der Common Name aus dem CSR wird automatisch übernommen. Erweiterungen aus dem CSR werden ignoriert – trage alle Namen hier ein, unter denen das Gerät erreichbar ist.'
            => 'The common name from the CSR is included automatically. Extensions in the CSR are ignored – enter every name under which the device is reachable here.',
        'Namen' => 'Names',
        'Haupt-Hostname (Common Name)' => 'Primary hostname (common name)',
        'Eine Angabe pro Zeile (oder durch Komma getrennt). Der Haupt-Hostname wird automatisch aufgenommen. Wildcards wie <code>*.home.lan</code> sind möglich. Trage auch die IP-Adresse ein, wenn du die Seite per IP aufrufst.'
            => 'One entry per line (or comma-separated). The primary hostname is included automatically. Wildcards such as <code>*.home.lan</code> are allowed. Also add the IP address if you access the site by IP.',
        'Organisation (optional)' => 'Organization (optional)',
        'Laufzeit &amp; Schlüssel' => 'Validity &amp; key',
        '397 Tage (≈ 1 Jahr)' => '397 days (≈ 1 year)',
        '825 Tage (≈ 2,3 Jahre) – Maximum für Apple-Geräte' => '825 days (≈ 2.3 years) – maximum for Apple devices',
        'Benutzerdefiniert …' => 'Custom …',
        'Tage (max. {n})' => 'Days (max. {n})',
        '<strong>Hinweis zur Laufzeit:</strong> Apple-Geräte (iPhone, iPad, Mac/Safari) lehnen TLS-Serverzertifikate mit mehr als 825 Tagen Laufzeit ab – auch von eigenen CAs.'
            => '<strong>Note on validity:</strong> Apple devices (iPhone, iPad, Mac/Safari) reject TLS server certificates valid for more than 825 days – even from your own CA.',
        'Windows, Android, Chrome und Firefox akzeptieren bei privaten CAs auch deutlich längere Laufzeiten.'
            => 'Windows, Android, Chrome and Firefox accept much longer validity periods for private CAs.',
        'Nutzt du keine Apple-Geräte, kannst du problemlos 10 Jahre und mehr wählen.'
            => 'If you don\'t use Apple devices, you can safely choose 10 years or more.',
        'Auch für Client-Authentifizierung verwenden (z. B. VPN, mTLS)' => 'Also use for client authentication (e.g. VPN, mTLS)',
        'Freigabe' => 'Authorization',
        'Passphrase der CA' => 'CA passphrase',
        'Zertifikat für {cn} wurde ausgestellt.' => 'Certificate for {cn} has been issued.',
        'Die Laufzeit wurde auf {days} Tage gekürzt, da ein Zertifikat nicht länger gültig sein kann als die CA.'
            => 'The validity was shortened to {days} days, because a certificate cannot be valid longer than the CA.',
        'Bitte eine Datei hochladen oder den PEM-Inhalt einfügen.' => 'Please upload a file or paste the PEM content.',

        // ---------------------------------------------------------- Zertifikat
        'Zertifikat' => 'Certificate',
        '{n} Tage' => '{n} days',
        'per CSR, Schlüssel liegt nur auf dem Gerät' => 'via CSR, key exists only on the device',
        'Verwendung' => 'Usage',
        'Aussteller' => 'Issuer',
        'Erstellt' => 'Created',
        'Herunterladen' => 'Download',
        'Zertifikat + CA-Zertifikat. Für nginx (<code>ssl_certificate</code>), Caddy, Traefik, Home Assistant.'
            => 'Certificate + CA certificate. For nginx (<code>ssl_certificate</code>), Caddy, Traefik, Home Assistant.',
        'Privater Schlüssel (geheim halten!). nginx: <code>ssl_certificate_key</code>.' => 'Private key (keep it secret!). nginx: <code>ssl_certificate_key</code>.',
        'Nur das Zertifikat. Für Apache (<code>SSLCertificateFile</code>) oder Geräte, die Zertifikat und Kette getrennt wollen.'
            => 'Certificate only. For Apache (<code>SSLCertificateFile</code>) or devices that want certificate and chain separately.',
        'Schlüssel + Zertifikat + CA in einer Datei, z. B. für HAProxy, Pi-hole/lighttpd, manche Router.'
            => 'Key + certificate + CA in one file, e.g. for HAProxy, Pi-hole/lighttpd, some routers.',
        'PKCS#12 / PFX (Windows, IIS, Synology, Java, …)' => 'PKCS#12 / PFX (Windows, IIS, Synology, Java, …)',
        'Passwort für die Datei' => 'Password for the file',
        'Erneuern' => 'Renew',
        'Stellt ein neues Zertifikat mit denselben Namen und derselben Laufzeit aus (auf Basis des gespeicherten CSR).'
            => 'Issues a new certificate with the same names and validity (based on the stored CSR).',
        'Stellt ein neues Zertifikat mit denselben Namen und derselben Laufzeit aus (mit neuem Schlüssel).'
            => 'Issues a new certificate with the same names and validity (with a new key).',
        'Dieses Zertifikat bleibt bestehen, bis du es löschst.' => 'This certificate remains until you delete it.',
        'Löschen' => 'Delete',
        'Zertifikat und Schlüssel endgültig löschen' => 'Permanently delete certificate and key',
        'Neues Zertifikat mit denselben Parametern ausgestellt. Das alte Zertifikat kann gelöscht werden, sobald das neue eingespielt ist.'
            => 'New certificate issued with the same parameters. The old certificate can be deleted once the new one is deployed.',
        'Zertifikat gelöscht.' => 'Certificate deleted.',
        'Unbekanntes Format.' => 'Unknown format.',

        // ---------------------------------------------------------- Fehlermeldungen der CA
        'Verzeichnis {dir} konnte nicht angelegt werden.' => 'Directory {dir} could not be created.',
        'Es existiert bereits eine CA.' => 'A CA already exists.',
        'Die Laufzeit der CA muss zwischen 1 und 100 Jahren liegen.' => 'The CA validity must be between 1 and 100 years.',
        'Die Passphrasen stimmen nicht überein.' => 'The passphrases do not match.',
        'CSR für die CA konnte nicht erzeugt werden' => 'Could not create the CSR for the CA',
        'CA-Zertifikat konnte nicht signiert werden' => 'Could not sign the CA certificate',
        'Das CA-Zertifikat ist ungültig' => 'The CA certificate is invalid',
        'Das Zertifikat ist kein CA-Zertifikat (basicConstraints CA:TRUE fehlt).' => 'The certificate is not a CA certificate (basicConstraints CA:TRUE is missing).',
        'Der Schlüssel ist verschlüsselt – bitte Passphrase angeben.' => 'The key is encrypted – please enter the passphrase.',
        'Der private Schlüssel konnte nicht gelesen werden (Passphrase falsch?)' => 'Could not read the private key (wrong passphrase?)',
        'Zertifikat und privater Schlüssel gehören nicht zusammen.' => 'Certificate and private key do not match.',
        'CSR konnte nicht erzeugt werden' => 'Could not create the CSR',
        'Der CSR ist ungültig' => 'The CSR is invalid',
        'Zertifikat nicht gefunden.' => 'Certificate not found.',
        'Für dieses Zertifikat liegt kein privater Schlüssel vor (per CSR signiert).' => 'There is no private key for this certificate (signed via CSR).',
        'PKCS#12-Datei konnte nicht erstellt werden' => 'Could not create the PKCS#12 file',
        'Zertifikat konnte nicht gelesen werden.' => 'Could not read the certificate.',
        'Zertifikat konnte nicht signiert werden' => 'Could not sign the certificate',
        'Es wurde noch keine CA erstellt.' => 'No CA has been created yet.',
        'Bitte die Passphrase der CA angeben.' => 'Please enter the CA passphrase.',
        'Die CA-Passphrase ist falsch.' => 'The CA passphrase is wrong.',
        'Privater Schlüssel konnte nicht exportiert werden' => 'Could not export the private key',
        'Unbekannter Schlüsseltyp.' => 'Unknown key type.',
        'Schlüssel konnte nicht erzeugt werden' => 'Could not generate the key',
        'Die Laufzeit muss mindestens 1 Tag betragen.' => 'The validity must be at least 1 day.',
        'Die CA ist abgelaufen.' => 'The CA has expired.',
        'Der Common Name (CN) ist ein Pflichtfeld.' => 'The common name (CN) is required.',
        'Das Land muss als zweistelliger Code angegeben werden (z. B. DE).' => 'The country must be a two-letter code (e.g. US).',
        'Felder des Distinguished Name dürfen höchstens 64 Zeichen lang sein.' => 'Distinguished name fields may be at most 64 characters long.',
        'Ungültiger Hostname oder IP-Adresse: {entry}' => 'Invalid hostname or IP address: {entry}',
        'Mindestens ein Hostname oder eine IP-Adresse ist erforderlich.' => 'At least one hostname or IP address is required.',
        '„{value}“ liegt außerhalb der Namensbeschränkung der CA ({permitted}). Browser würden das Zertifikat ablehnen.'
            => '"{value}" is outside the CA\'s name constraints ({permitted}). Browsers would reject the certificate.',
        'Ungültiges Netz in den Namensbeschränkungen: {entry}' => 'Invalid network in the name constraints: {entry}',
        'Ungültige Domain in den Namensbeschränkungen: {entry}' => 'Invalid domain in the name constraints: {entry}',
        'Ungültige Zertifikats-ID.' => 'Invalid certificate ID.',
        'Datei konnte nicht gelesen werden: {file}' => 'Could not read file: {file}',
    ],
];

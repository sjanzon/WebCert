<?php $file = h(safe_filename($ca['cn'])); ?>
<div class="page-head">
    <h1><?= h($ca['cn']) ?></h1>
    <div class="actions">
        <a class="btn btn-primary" href="<?= url('ca-download') ?>"><?= t('CA-Zertifikat (.crt, PEM)') ?></a>
        <a class="btn" href="<?= url('ca-download', ['fmt' => 'der']) ?>">.cer (DER)</a>
    </div>
</div>

<div class="grid-main">
    <section class="card">
        <h2 class="card-title"><?= t('Details') ?></h2>
        <dl class="details">
            <dt><?= t('Typ') ?></dt><dd><?= t('Root-CA') ?> <?= $encrypted ? '<span class="badge badge-ok">' . t('Schlüssel verschlüsselt') . '</span>' : '<span class="badge badge-warn">' . t('Schlüssel unverschlüsselt') . '</span>' ?></dd>
            <dt><?= t('Gültig') ?></dt><dd><?= fmt_date($ca['validFrom']) ?> – <?= fmt_date($ca['validTo']) ?></dd>
            <dt><?= t('Schlüssel') ?></dt><dd><?= h($ca['key']) ?></dd>
            <dt><?= t('Seriennummer') ?></dt><dd><code><?= h($ca['serial']) ?></code></dd>
            <dt>SHA-256</dt><dd><code class="fp"><?= h($ca['sha256']) ?></code></dd>
            <dt>SHA-1</dt><dd><code class="fp"><?= h($ca['sha1']) ?></code></dd>
            <?php if ($ca['constraints']): ?>
                <dt><?= t('Beschränkt auf') ?></dt><dd><pre class="plain"><?= h(trim(str_replace(['Permitted:', 'IP:'], ['', 'IP: '], $ca['constraints']))) ?></pre></dd>
            <?php endif ?>
        </dl>
        <p class="hint"><?= t('Vergleiche beim Installieren auf einem Gerät den angezeigten Fingerabdruck mit diesem Wert.') ?></p>
        <p class="hint"><?= t('Direkter Download-Link für Geräte (ohne Anmeldung):') ?><br>
            <code class="selectable"><?= h((empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off' ? 'http' : 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . strtok($_SERVER['REQUEST_URI'] ?? '/', '?') . url('ca-download')) ?></code></p>
    </section>

    <section class="card">
        <h2 class="card-title"><?= t('Auf Geräten als vertrauenswürdig installieren') ?></h2>
        <div class="howto">
            <details open>
                <summary>Windows (Edge, Chrome, Firefox)</summary>
                <ol>
                    <li><?= t('<code>{file}.crt</code> herunterladen und doppelklicken → <em>Zertifikat installieren…</em>', ['file' => $file]) ?></li>
                    <li><?= t('<em>Lokaler Computer</em> wählen → <em>Alle Zertifikate in folgendem Speicher speichern</em> → <em>Vertrauenswürdige Stammzertifizierungsstellen</em>.') ?></li>
                    <li><?= t('Browser neu starten.') ?></li>
                </ol>
                <p><?= t('Alternativ in einer Administrator-Konsole:') ?> <code>certutil -addstore -f Root <?= $file ?>.crt</code></p>
            </details>
            <details>
                <summary>macOS (Safari, Chrome)</summary>
                <ol>
                    <li><?= t('Datei doppelklicken → Schlüsselbund <em>System</em> wählen.') ?></li>
                    <li><?= t('In der Schlüsselbundverwaltung das Zertifikat öffnen → <em>Vertrauen</em> → <em>Bei Verwendung dieses Zertifikats: Immer vertrauen</em>.') ?></li>
                </ol>
                <p><?= t('Terminal:') ?> <code>sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain <?= $file ?>.crt</code></p>
            </details>
            <details>
                <summary>iPhone / iPad</summary>
                <ol>
                    <li><?= t('Den Download-Link in <strong>Safari</strong> öffnen → Profil laden erlauben.') ?></li>
                    <li><?= t('<em>Einstellungen → Profil geladen</em> → <em>Installieren</em>.') ?></li>
                    <li><?= t('<em>Einstellungen → Allgemein → Info → Zertifikatsvertrauenseinstellungen</em> → für die CA <em>Volles Vertrauen</em> aktivieren.') ?></li>
                </ol>
            </details>
            <details>
                <summary>Android</summary>
                <ol>
                    <li><?= t('<code>.crt</code> herunterladen.') ?></li>
                    <li><?= t('<em>Einstellungen → Sicherheit → Weitere Sicherheitseinstellungen → Verschlüsselung &amp; Anmeldedaten → Zertifikat installieren → CA-Zertifikat</em> (Bezeichnung je nach Hersteller abweichend).') ?></li>
                </ol>
                <p><?= t('Hinweis: Chrome nutzt so installierte CAs, viele Apps vertrauen Benutzer-CAs unter Android aber grundsätzlich nicht.') ?></p>
            </details>
            <details>
                <summary>Firefox</summary>
                <p><?= t('Unter Windows und macOS übernimmt Firefox die System-Zertifikate automatisch. Andernfalls: <em>Einstellungen → Datenschutz &amp; Sicherheit → Zertifikate anzeigen → Zertifizierungsstellen → Importieren</em> → <em>Dieser CA vertrauen, um Websites zu identifizieren</em>.') ?></p>
            </details>
            <details>
                <summary>Linux (Debian/Ubuntu)</summary>
                <p><code>sudo cp <?= $file ?>.crt /usr/local/share/ca-certificates/ &amp;&amp; sudo update-ca-certificates</code></p>
                <p><?= t('Chrome/Chromium unter Linux nutzt eine eigene NSS-Datenbank:') ?> <code>certutil -d sql:$HOME/.pki/nssdb -A -t "C,," -n "<?= h($ca['cn']) ?>" -i <?= $file ?>.crt</code></p>
            </details>
        </div>
    </section>
</div>

<section class="card">
    <h2 class="card-title"><?= t('Sicherung') ?></h2>
    <p><?= $encrypted
            ? t('Lädt CA-Zertifikat und privaten Schlüssel (weiterhin mit deiner Passphrase verschlüsselt) als eine Datei herunter.')
            : t('Lädt CA-Zertifikat und privaten Schlüssel <strong>unverschlüsselt</strong> als eine Datei herunter.') ?>
        <?= t('Damit lässt sich die CA bei einer Neuinstallation wieder importieren. Bewahre die Datei sicher auf.') ?></p>
    <a class="btn" href="<?= url('ca-backup') ?>"><?= t('Sicherung herunterladen') ?></a>
</section>

<section class="card danger">
    <h2 class="card-title"><?= t('CA löschen') ?></h2>
    <p><?= t('Löscht die CA <strong>und alle ausgestellten Zertifikate</strong>. Vorhandene Zertifikate auf deinen Servern bleiben bis zum Ablauf gültig, können aber nicht mehr erneuert werden.') ?></p>
    <form method="post" action="<?= url('ca-delete') ?>" class="inline-form">
        <?= csrf_field() ?>
        <input name="confirm" placeholder="<?= h(t('{word} eingeben', ['word' => t('LÖSCHEN')])) ?>" autocomplete="off">
        <button class="btn btn-danger"><?= t('Endgültig löschen') ?></button>
    </form>
</section>

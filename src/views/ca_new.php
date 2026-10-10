<?php $v = fn(string $k, string $d = '') => h($old[$k] ?? $d); ?>
<div class="narrow wide">
    <h1><?= t('Root-CA erstellen') ?></h1>
    <p class="lead"><?= t('Die Root-CA ist die Vertrauensbasis für dein Heimnetz. Ihr Zertifikat wird auf allen Geräten als vertrauenswürdig installiert; anschließend stellst du damit Zertifikate für deine Webseiten aus.') ?></p>

    <form method="post" class="card">
        <?= csrf_field() ?>
        <input type="hidden" name="mode" value="create">

        <h2 class="card-title"><?= t('Bezeichnung') ?></h2>
        <label><?= t('Name der CA (Common Name)') ?> *
            <input name="cn" value="<?= $v('cn') ?>" required maxlength="64">
        </label>
        <div class="grid-3">
            <label><?= t('Organisation') ?> <input name="o" value="<?= $v('o') ?>" maxlength="64"></label>
            <label><?= t('Abteilung') ?> <input name="ou" value="<?= $v('ou') ?>" maxlength="64"></label>
            <label><?= t('Land (2 Buchstaben)') ?> <input name="c" value="<?= $v('c') ?>" maxlength="2" pattern="[A-Za-z]{2}"></label>
        </div>

        <h2 class="card-title"><?= t('Technik') ?></h2>
        <div class="grid-2">
            <label><?= t('Laufzeit') ?>
                <select name="years">
                    <?php foreach ([10, 20, 25, 30, 50] as $y): ?>
                        <option value="<?= $y ?>" <?= (int)($old['years'] ?? 25) === $y ? 'selected' : '' ?>><?= t('{n} Jahre', ['n' => $y]) ?></option>
                    <?php endforeach ?>
                </select>
            </label>
            <label><?= t('Schlüssel') ?>
                <select name="key_type">
                    <?php foreach (CertificateAuthority::KEY_TYPES as $k => $t): ?>
                        <option value="<?= $k ?>" <?= ($old['key_type'] ?? '') === $k ? 'selected' : '' ?>><?= h(t($t['label'])) ?></option>
                    <?php endforeach ?>
                </select>
            </label>
        </div>
        <p class="hint"><?= t('Für die CA empfiehlt sich RSA 4096 oder ECDSA P-384. Die Laufzeit der CA begrenzt auch die maximale Laufzeit der ausgestellten Zertifikate.') ?></p>

        <h2 class="card-title"><?= t('Passphrase für den CA-Schlüssel') ?></h2>
        <div class="grid-2">
            <label><?= t('Passphrase') ?> <input type="password" name="passphrase" autocomplete="new-password"></label>
            <label><?= t('Passphrase wiederholen') ?> <input type="password" name="passphrase2" autocomplete="new-password"></label>
        </div>
        <p class="hint"><?= t('Empfohlen. Der private Schlüssel der CA wird damit AES-256-verschlüsselt gespeichert und die Passphrase wird bei jeder Ausstellung abgefragt. Leer lassen = unverschlüsselt. <strong>Ohne Passphrase lassen sich keine Zertifikate mehr ausstellen – gut aufbewahren!</strong>') ?></p>

        <details class="advanced" <?= !empty($old['name_constraints']) ? 'open' : '' ?>>
            <summary><?= t('Namensbeschränkung (optional, empfohlen für mehr Sicherheit)') ?></summary>
            <label><?= t('Erlaubte Domains und Netze') ?>
                <textarea name="name_constraints" rows="3" placeholder="lan&#10;home.arpa&#10;fritz.box&#10;192.168.0.0/16&#10;10.0.0.0/8"><?= $v('name_constraints') ?></textarea>
            </label>
            <p class="hint"><?= t('Beschränkt die CA technisch auf die angegebenen Domains (inkl. Subdomains) und IP-Netze. Selbst wenn der CA-Schlüssel gestohlen würde, ließen sich damit keine gültigen Zertifikate für z. B. <code>google.com</code> oder deine Bank erzeugen. Werden nur Domains angegeben, sind IP-Adressen nicht beschränkt (und umgekehrt). Die Beschränkung kann später nicht geändert werden.') ?></p>
        </details>

        <button class="btn btn-primary"><?= t('Root-CA erstellen') ?></button>
    </form>

    <details class="card import">
        <summary><strong><?= t('Bestehende CA importieren') ?></strong> <span class="muted"><?= t('(z. B. aus einer Sicherung)') ?></span></summary>
        <form method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="mode" value="import">
            <div class="grid-2">
                <label><?= t('CA-Zertifikat (Datei)') ?> <input type="file" name="cert_file" accept=".crt,.pem,.cer"></label>
                <label><?= t('Privater Schlüssel (Datei)') ?> <input type="file" name="key_file" accept=".key,.pem"></label>
            </div>
            <div class="grid-2">
                <label><?= t('… oder Zertifikat als PEM einfügen') ?> <textarea name="cert_pem" rows="4" class="mono"></textarea></label>
                <label><?= t('… oder Schlüssel als PEM einfügen') ?> <textarea name="key_pem" rows="4" class="mono"></textarea></label>
            </div>
            <p class="hint"><?= t('Bei einer WebCert-Sicherung (<code>*-backup.pem</code>) kann dieselbe Datei in beiden Feldern ausgewählt werden.') ?></p>
            <label><?= t('Passphrase des Schlüssels (falls verschlüsselt)') ?> <input type="password" name="import_pass" autocomplete="off"></label>
            <button class="btn"><?= t('CA importieren') ?></button>
        </form>
    </details>
</div>

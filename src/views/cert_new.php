<?php
$v = fn(string $k, string $d = '') => h($old[$k] ?? $d);
$presets = [
    397  => t('397 Tage (≈ 1 Jahr)'),
    825  => t('825 Tage (≈ 2,3 Jahre) – Maximum für Apple-Geräte'),
    1825 => t('{n} Jahre', ['n' => 5]),
    3650 => t('{n} Jahre', ['n' => 10]),
    7300 => t('{n} Jahre', ['n' => 20]),
];
$presets = array_filter($presets, fn($d) => $d <= $maxDays, ARRAY_FILTER_USE_KEY);
$selected = (string)($old['days'] ?? CertificateAuthority::APPLE_MAX_DAYS);
$custom = !isset($presets[(int)$selected]) || $selected === 'custom';
?>
<div class="narrow wide">
    <h1><?= h($title) ?></h1>
    <?php if ($isCsr): ?>
        <p class="lead"><?= t('Für Geräte, die ihren privaten Schlüssel selbst erzeugen (Router, NAS, Drucker, Proxmox …). Lade den dort erstellten Certificate Signing Request hoch – der private Schlüssel verlässt das Gerät nie.') ?></p>
    <?php else: ?>
        <p class="lead"><?= t('Erzeugt einen neuen privaten Schlüssel und ein von deiner Root-CA signiertes Zertifikat.') ?></p>
    <?php endif ?>

    <form method="post" class="card" <?= $isCsr ? 'enctype="multipart/form-data"' : '' ?>>
        <?= csrf_field() ?>

        <?php if ($isCsr): ?>
            <h2 class="card-title">CSR</h2>
            <label><?= t('CSR-Datei') ?> <input type="file" name="csr_file" accept=".csr,.pem,.req,.der"></label>
            <label><?= t('… oder PEM einfügen') ?>
                <textarea name="csr_pem" rows="6" class="mono" placeholder="-----BEGIN CERTIFICATE REQUEST-----"><?= $v('csr_pem') ?></textarea>
            </label>
            <label><?= t('Weitere Hostnamen / IP-Adressen') ?>
                <textarea name="sans" rows="3" placeholder="nas.lan&#10;192.168.1.10"><?= $v('sans') ?></textarea>
            </label>
            <p class="hint"><?= t('Der Common Name aus dem CSR wird automatisch übernommen. Erweiterungen aus dem CSR werden ignoriert – trage alle Namen hier ein, unter denen das Gerät erreichbar ist.') ?></p>
        <?php else: ?>
            <h2 class="card-title"><?= t('Namen') ?></h2>
            <label><?= t('Haupt-Hostname (Common Name)') ?> *
                <input name="cn" value="<?= $v('cn') ?>" required maxlength="64" placeholder="nas.lan" autofocus>
            </label>
            <label><?= t('Weitere Hostnamen / IP-Adressen') ?>
                <textarea name="sans" rows="3" placeholder="nas&#10;*.nas.lan&#10;192.168.1.10"><?= $v('sans') ?></textarea>
            </label>
            <p class="hint"><?= t('Eine Angabe pro Zeile (oder durch Komma getrennt). Der Haupt-Hostname wird automatisch aufgenommen. Wildcards wie <code>*.home.lan</code> sind möglich. Trage auch die IP-Adresse ein, wenn du die Seite per IP aufrufst.') ?></p>
            <label><?= t('Organisation (optional)') ?> <input name="o" value="<?= $v('o') ?>" maxlength="64"></label>
        <?php endif ?>

        <h2 class="card-title"><?= t('Laufzeit &amp; Schlüssel') ?></h2>
        <div class="grid-2">
            <label><?= t('Laufzeit') ?>
                <select name="days" id="days">
                    <?php foreach ($presets as $d => $label): ?>
                        <option value="<?= $d ?>" <?= !$custom && (int)$selected === $d ? 'selected' : '' ?>><?= h($label) ?></option>
                    <?php endforeach ?>
                    <option value="custom" <?= $custom ? 'selected' : '' ?>><?= t('Benutzerdefiniert …') ?></option>
                </select>
            </label>
            <label id="custom-days" <?= $custom ? '' : 'hidden' ?>><?= t('Tage (max. {n})', ['n' => $maxDays]) ?>
                <input type="number" name="custom_days" min="1" max="<?= $maxDays ?>" value="<?= h($old['custom_days'] ?? ($custom && ctype_digit($selected) ? $selected : $maxDays)) ?>">
            </label>
            <?php if (!$isCsr): ?>
                <label><?= t('Schlüssel') ?>
                    <select name="key_type">
                        <?php foreach (CertificateAuthority::KEY_TYPES as $k => $t): ?>
                            <option value="<?= $k ?>" <?= ($old['key_type'] ?? '') === $k ? 'selected' : '' ?>><?= h(t($t['label'])) ?></option>
                        <?php endforeach ?>
                    </select>
                </label>
            <?php endif ?>
        </div>
        <div class="alert alert-info small" id="apple-warning">
            <?= t('<strong>Hinweis zur Laufzeit:</strong> Apple-Geräte (iPhone, iPad, Mac/Safari) lehnen TLS-Serverzertifikate mit mehr als 825 Tagen Laufzeit ab – auch von eigenen CAs.') ?>
            <?= t('Windows, Android, Chrome und Firefox akzeptieren bei privaten CAs auch deutlich längere Laufzeiten.') ?>
            <?= t('Nutzt du keine Apple-Geräte, kannst du problemlos 10 Jahre und mehr wählen.') ?>
        </div>

        <label class="check">
            <input type="checkbox" name="client_auth" value="1" <?= !empty($old['client_auth']) ? 'checked' : '' ?>>
            <?= t('Auch für Client-Authentifizierung verwenden (z. B. VPN, mTLS)') ?>
        </label>

        <?php if ($encrypted): ?>
            <h2 class="card-title"><?= t('Freigabe') ?></h2>
            <label><?= t('Passphrase der CA') ?> *
                <input type="password" name="ca_pass" required autocomplete="off">
            </label>
        <?php endif ?>

        <button class="btn btn-primary"><?= $isCsr ? t('CSR signieren') : t('Zertifikat ausstellen') ?></button>
    </form>
</div>
<script>
    (() => {
        const select = document.getElementById('days');
        const custom = document.getElementById('custom-days');
        const warning = document.getElementById('apple-warning');
        const update = () => {
            custom.hidden = select.value !== 'custom';
            const days = select.value === 'custom' ? +custom.querySelector('input').value : +select.value;
            warning.classList.toggle('alert-warn', days > <?= CertificateAuthority::APPLE_MAX_DAYS ?>);
        };
        select.addEventListener('change', update);
        custom.addEventListener('input', update);
        update();
    })();
</script>

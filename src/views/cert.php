<?php
$i = $cert['info'];
$m = $cert['meta'];
$id = $cert['id'];
[$cls, $label] = cert_status($i['validTo']);
$name = h(safe_filename($m['cn']));
?>
<div class="page-head">
    <div>
        <a class="muted small back" href="<?= url('dashboard') ?>">← <?= t('Übersicht') ?></a>
        <h1><?= h($m['cn']) ?> <span class="badge badge-<?= $cls ?>"><?= h($label) ?></span></h1>
    </div>
</div>

<div class="grid-main">
    <section class="card">
        <h2 class="card-title"><?= t('Details') ?></h2>
        <dl class="details">
            <dt><?= t('Hostnamen / IPs') ?></dt>
            <dd><?php foreach ($i['sans'] as $san): ?><code><?= h($san) ?></code> <?php endforeach ?></dd>
            <dt><?= t('Gültig') ?></dt><dd><?= fmt_date($i['validFrom']) ?> – <?= fmt_date($i['validTo']) ?> (<?= t('{n} Tage', ['n' => (int)round(($i['validTo'] - $i['validFrom']) / 86400)]) ?>)</dd>
            <dt><?= t('Schlüssel') ?></dt><dd><?= h($i['key']) ?><?= $m['source'] === 'csr' ? ' · ' . t('per CSR, Schlüssel liegt nur auf dem Gerät') : '' ?></dd>
            <dt><?= t('Verwendung') ?></dt><dd><?= h($i['eku']) ?></dd>
            <dt><?= t('Aussteller') ?></dt><dd><?= h($i['issuer']) ?></dd>
            <dt><?= t('Seriennummer') ?></dt><dd><code><?= h($i['serial']) ?></code></dd>
            <dt>SHA-256</dt><dd><code class="fp"><?= h($i['sha256']) ?></code></dd>
            <dt><?= t('Erstellt') ?></dt><dd><?= fmt_datetime($m['created']) ?></dd>
        </dl>
    </section>

    <section class="card">
        <h2 class="card-title"><?= t('Herunterladen') ?></h2>
        <ul class="downloads">
            <li><a class="btn" href="<?= url('cert-download', ['id' => $id, 'fmt' => 'chain']) ?>"><?= $name ?>-fullchain.pem</a>
                <span><?= t('Zertifikat + CA-Zertifikat. Für nginx (<code>ssl_certificate</code>), Caddy, Traefik, Home Assistant.') ?></span></li>
            <?php if ($cert['hasKey']): ?>
                <li><a class="btn" href="<?= url('cert-download', ['id' => $id, 'fmt' => 'key']) ?>"><?= $name ?>.key</a>
                    <span><?= t('Privater Schlüssel (geheim halten!). nginx: <code>ssl_certificate_key</code>.') ?></span></li>
            <?php endif ?>
            <li><a class="btn" href="<?= url('cert-download', ['id' => $id, 'fmt' => 'crt']) ?>"><?= $name ?>.crt</a>
                <span><?= t('Nur das Zertifikat. Für Apache (<code>SSLCertificateFile</code>) oder Geräte, die Zertifikat und Kette getrennt wollen.') ?></span></li>
            <?php if ($cert['hasKey']): ?>
                <li><a class="btn" href="<?= url('cert-download', ['id' => $id, 'fmt' => 'combined']) ?>"><?= $name ?>-combined.pem</a>
                    <span><?= t('Schlüssel + Zertifikat + CA in einer Datei, z. B. für HAProxy, Pi-hole/lighttpd, manche Router.') ?></span></li>
            <?php endif ?>
        </ul>

        <?php if ($cert['hasKey']): ?>
            <form method="post" action="<?= url('cert-p12') ?>" class="p12">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= h($id) ?>">
                <label><?= t('PKCS#12 / PFX (Windows, IIS, Synology, Java, …)') ?>
                    <span class="inline-form">
                        <input type="password" name="p12_pass" placeholder="<?= h(t('Passwort für die Datei')) ?>" autocomplete="new-password">
                        <button class="btn"><?= $name ?>.p12</button>
                    </span>
                </label>
            </form>
        <?php endif ?>
    </section>
</div>

<section class="card">
    <h2 class="card-title"><?= t('Erneuern') ?></h2>
    <p><?= $m['source'] === 'csr'
            ? t('Stellt ein neues Zertifikat mit denselben Namen und derselben Laufzeit aus (auf Basis des gespeicherten CSR).')
            : t('Stellt ein neues Zertifikat mit denselben Namen und derselben Laufzeit aus (mit neuem Schlüssel).') ?>
        <?= t('Dieses Zertifikat bleibt bestehen, bis du es löschst.') ?></p>
    <form method="post" action="<?= url('cert-renew') ?>" class="inline-form">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= h($id) ?>">
        <?php if ($encrypted): ?>
            <input type="password" name="ca_pass" placeholder="<?= h(t('Passphrase der CA')) ?>" required autocomplete="off">
        <?php endif ?>
        <button class="btn"><?= t('Erneuern') ?></button>
    </form>
</section>

<section class="card danger">
    <h2 class="card-title"><?= t('Löschen') ?></h2>
    <form method="post" action="<?= url('cert-delete') ?>" class="inline-form">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= h($id) ?>">
        <label class="check"><input type="checkbox" required> <?= t('Zertifikat und Schlüssel endgültig löschen') ?></label>
        <button class="btn btn-danger"><?= t('Löschen') ?></button>
    </form>
</section>

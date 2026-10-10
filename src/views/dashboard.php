<?php if (!$ca): ?>
    <div class="narrow sealed">
        <h1><?= t('Noch keine Root-CA') ?></h1>
        <div class="card">
            <p><?= t('Erstelle zuerst eine eigene Root-CA. Deren Zertifikat installierst du einmalig auf deinen Geräten – danach vertrauen sie allen Zertifikaten, die du hier ausstellst.') ?></p>
            <a class="btn btn-primary" href="<?= url('ca-new') ?>"><?= t('Root-CA erstellen') ?></a>
        </div>
    </div>
<?php return; endif ?>

<div class="page-head">
    <h1><?= t('Übersicht') ?></h1>
    <div class="actions">
        <a class="btn" href="<?= url('csr-sign') ?>"><?= t('CSR signieren') ?></a>
        <a class="btn btn-primary" href="<?= url('cert-new') ?>"><?= t('Zertifikat ausstellen') ?></a>
    </div>
</div>

<section class="card ca-summary">
    <div>
        <div class="eyebrow"><?= t('Root-CA') ?></div>
        <div class="ca-name"><?= h($ca['cn']) ?></div>
        <div class="muted small"><?= h($ca['key']) ?> · <?= t('gültig bis {date} (noch {years} Jahre)', ['date' => fmt_date($ca['validTo']), 'years' => fmt_number(days_left($ca['validTo']) / 365.25, 1)]) ?></div>
    </div>
    <div class="actions">
        <a class="btn" href="<?= url('ca-download') ?>"><?= t('CA-Zertifikat (.crt)') ?></a>
        <a class="btn" href="<?= url('ca') ?>"><?= t('Installationsanleitung') ?></a>
    </div>
</section>

<h2><?= t('Zertifikate') ?> <span class="count"><?= count($certs) ?></span></h2>
<?php if (!$certs): ?>
    <div class="card empty"><?= t('Noch keine Zertifikate ausgestellt.') ?> <a href="<?= url('cert-new') ?>"><?= t('Das erste Zertifikat ausstellen') ?></a></div>
<?php else: ?>
    <div class="table-wrap card flush">
        <table class="cert-table">
            <thead>
            <tr><th><?= t('Name') ?></th><th><?= t('Hostnamen / IPs') ?></th><th><?= t('Schlüssel') ?></th><th><?= t('Gültig bis') ?></th><th><?= t('Status') ?></th></tr>
            </thead>
            <tbody>
            <?php foreach ($certs as $c): [$cls, $label] = cert_status($c['info']['validTo']); ?>
                <tr>
                    <td class="c-name"><a class="strong" href="<?= url('cert', ['id' => $c['id']]) ?>"><?= h($c['meta']['cn']) ?></a>
                        <?php if ($c['meta']['source'] === 'csr'): ?><span class="tag">CSR</span><?php endif ?></td>
                    <td class="sans c-sans"><?php foreach ($c['info']['sans'] as $san): ?><code><?= h(preg_replace('/^(DNS|IP):/', '', $san)) ?></code> <?php endforeach ?></td>
                    <td class="muted small c-key"><?= h($c['info']['key']) ?></td>
                    <td class="c-valid small" data-label="<?= h(t('bis')) ?>"><?= fmt_date($c['info']['validTo']) ?></td>
                    <td class="c-status"><span class="badge badge-<?= $cls ?>"><?= h($label) ?></span></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
<?php endif ?>

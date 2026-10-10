<!doctype html>
<html lang="<?= h(lang()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($title ?? 'WebCert') ?> · WebCert</title>
    <meta name="theme-color" content="#15233f">
    <link rel="stylesheet" href="assets/style.css?v=3">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'><path fill='%232b4bb8' d='M12 1 3 5v6c0 5.5 3.8 10.7 9 12 5.2-1.3 9-6.5 9-12V5l-9-4z'/></svg>">
</head>
<body>
<header class="topbar">
    <div class="topbar-inner">
        <a class="brand" href="<?= url('dashboard') ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 1 3 5v6c0 5.5 3.8 10.7 9 12 5.2-1.3 9-6.5 9-12V5l-9-4zm-1.2 15.2-3.5-3.5 1.4-1.4 2.1 2.1 4.9-4.9 1.4 1.4-6.3 6.3z"/></svg>
            WebCert
        </a>
        <?php if (logged_in()): $current = (string)($_GET['a'] ?? 'dashboard'); ?>
            <nav aria-label="<?= h(t('Hauptnavigation')) ?>">
                <?php foreach (['dashboard' => t('Übersicht'), 'ca' => t('Root-CA'), 'cert-new' => t('Ausstellen'), 'csr-sign' => t('CSR signieren')] as $a => $label): ?>
                    <a href="<?= url($a) ?>"<?= $current === $a ? ' aria-current="page"' : '' ?>><?= h($label) ?></a>
                <?php endforeach ?>
                <a href="<?= url('logout') ?>" class="logout"><?= h(t('Abmelden')) ?></a>
            </nav>
        <?php endif ?>
        <?php if (count(languages()) > 1): ?>
            <div class="lang-switch" role="group" aria-label="<?= h(t('Sprache')) ?>">
                <?php foreach (languages() as $code => $pack): ?>
                    <a href="<?= h(lang_url($code)) ?>" lang="<?= h($code) ?>" hreflang="<?= h($code) ?>" title="<?= h($pack['name']) ?>"<?= $code === lang() ? ' aria-current="true"' : '' ?>><?= h(strtoupper($code)) ?></a>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </div>
</header>
<main class="container">
    <?php foreach (take_flashes() as [$type, $message]): ?>
        <div class="alert alert-<?= h($type) ?>"><?= h($message) ?></div>
    <?php endforeach ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= h($error) ?></div>
    <?php endif ?>
    <?= $content ?>
</main>
</body>
</html>

<div class="narrow sealed">
    <h1><?= t('Anmelden') ?></h1>
    <form method="post" action="<?= url('login') ?>" class="card">
        <?= csrf_field() ?>
        <label><?= t('Passwort') ?>
            <input type="password" name="password" required autofocus autocomplete="current-password">
        </label>
        <button class="btn btn-primary"><?= t('Anmelden') ?></button>
    </form>
</div>

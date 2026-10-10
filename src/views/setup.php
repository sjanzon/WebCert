<div class="narrow sealed">
    <h1><?= t('Willkommen bei WebCert') ?></h1>
    <p class="lead"><?= t('Lege ein Passwort für die Verwaltungsoberfläche fest. Wer Zugriff auf diese Oberfläche hat, kann Zertifikate ausstellen, denen alle deine Geräte vertrauen – wähle also ein starkes Passwort.') ?></p>
    <form method="post" class="card">
        <?= csrf_field() ?>
        <label><?= t('Passwort') ?>
            <input type="password" name="password" minlength="8" required autofocus autocomplete="new-password">
        </label>
        <label><?= t('Passwort wiederholen') ?>
            <input type="password" name="password2" minlength="8" required autocomplete="new-password">
        </label>
        <button class="btn btn-primary"><?= t('Passwort speichern') ?></button>
    </form>
</div>

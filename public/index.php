<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

$action = (string)($_GET['a'] ?? 'dashboard');
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
if ($isPost) {
    csrf_check();
}

$ca = new CertificateAuthority(DATA_DIR);

// Das CA-Zertifikat ist öffentlich (enthält nichts Geheimes) – so können Geräte es ohne Anmeldung laden.
if ($action === 'ca-download') {
    if (!$ca->hasCa()) {
        http_response_code(404);
        exit(t('Es wurde noch keine CA erstellt.'));
    }
    $name = safe_filename($ca->caInfo()['cn']);
    if (($_GET['fmt'] ?? 'pem') === 'der') {
        send_download(pem_to_der($ca->caCertPem()), "$name.cer", 'application/pkix-cert');
    }
    send_download($ca->caCertPem(), "$name.crt", 'application/x-x509-ca-cert');
}

// ------------------------------------------------- Ersteinrichtung & Login

if (!auth_configured()) {
    $error = null;
    if ($isPost) {
        $password = (string)($_POST['password'] ?? '');
        if (strlen($password) < 8) {
            $error = t('Das Passwort muss mindestens 8 Zeichen lang sein.');
        } elseif ($password !== ($_POST['password2'] ?? '')) {
            $error = t('Die Passwörter stimmen nicht überein.');
        } else {
            auth_set_password($password);
            login();
            flash('success', t('Passwort gespeichert. Als Nächstes erstellst du deine Root-CA.'));
            redirect('ca-new');
        }
    }
    render('setup', ['title' => t('Einrichtung'), 'error' => $error]);
}

if ($action === 'logout') {
    $_SESSION = [];
    session_destroy();
    redirect('login');
}

if (!logged_in()) {
    $error = null;
    if ($action === 'login' && $isPost) {
        if (auth_verify((string)($_POST['password'] ?? ''))) {
            login();
            redirect('dashboard');
        }
        sleep(1);
        $error = t('Falsches Passwort.');
    }
    render('login', ['title' => t('Anmelden'), 'error' => $error]);
}

// ------------------------------------------------------- Angemeldet

$error = null;

try {
    switch ($action) {
        case 'dashboard':
            render('dashboard', [
                'title' => t('Übersicht'),
                'ca'    => $ca->hasCa() ? $ca->caInfo() : null,
                'certs' => $ca->hasCa() ? $ca->all() : [],
            ]);

        case 'ca-new':
            if ($ca->hasCa()) {
                redirect('ca');
            }
            $old = ['years' => 25, 'key_type' => 'rsa4096', 'c' => 'DE', 'cn' => t('Heimnetz Root CA'), 'o' => t('Heimnetz')];
            if ($isPost) {
                $old = $_POST;
                try {
                    if (($_POST['mode'] ?? '') === 'import') {
                        $ca->importCa(
                            uploaded_or_text('cert_file', 'cert_pem'),
                            uploaded_or_text('key_file', 'key_pem'),
                            (string)($_POST['import_pass'] ?? ''),
                        );
                        flash('success', t('Die CA wurde importiert.'));
                    } else {
                        $ca->createCa($_POST);
                        flash('success', t('Die Root-CA wurde erstellt. Installiere jetzt das CA-Zertifikat auf deinen Geräten.'));
                    }
                    redirect('ca');
                } catch (RuntimeException $e) {
                    $error = $e->getMessage();
                }
            }
            render('ca_new', ['title' => t('CA erstellen'), 'old' => $old, 'error' => $error]);

        case 'ca':
            require_ca($ca);
            render('ca', ['title' => t('Root-CA'), 'ca' => $ca->caInfo(), 'encrypted' => $ca->caKeyEncrypted()]);

        case 'ca-backup':
            require_ca($ca);
            $name = safe_filename($ca->caInfo()['cn']);
            send_download($ca->caCertPem() . $ca->caKeyPem(), "$name-backup.pem", 'application/x-pem-file');

        case 'ca-delete':
            require_ca($ca);
            if ($isPost && ($_POST['confirm'] ?? '') === t('LÖSCHEN')) {
                $ca->deleteCa();
                flash('success', t('Die CA und alle Zertifikate wurden gelöscht.'));
                redirect('ca-new');
            }
            flash('error', t('Zum Löschen bitte LÖSCHEN in das Bestätigungsfeld eingeben.'));
            redirect('ca');

        case 'cert-new':
        case 'csr-sign':
            require_ca($ca);
            $isCsr = $action === 'csr-sign';
            $old = ['days' => CertificateAuthority::APPLE_MAX_DAYS, 'key_type' => 'rsa2048'];
            if ($isPost) {
                $old = $_POST;
                $_POST['days'] = (int)(($_POST['days'] ?? '') === 'custom' ? ($_POST['custom_days'] ?? 0) : ($_POST['days'] ?? 0));
                try {
                    $id = $isCsr
                        ? $ca->signCsr(uploaded_or_text('csr_file', 'csr_pem'), $_POST)
                        : $ca->issue($_POST);
                    $cert = $ca->get($id);
                    $actualDays = (int)round(($cert['info']['validTo'] - $cert['info']['validFrom']) / 86400);
                    flash('success', t('Zertifikat für {cn} wurde ausgestellt.', ['cn' => $cert['meta']['cn']]));
                    if ($actualDays < $_POST['days']) {
                        flash('warn', t('Die Laufzeit wurde auf {days} Tage gekürzt, da ein Zertifikat nicht länger gültig sein kann als die CA.', ['days' => $actualDays]));
                    }
                    redirect('cert', ['id' => $id]);
                } catch (RuntimeException $e) {
                    $error = $e->getMessage();
                }
            }
            render('cert_new', [
                'title'     => $isCsr ? t('CSR signieren') : t('Zertifikat ausstellen'),
                'isCsr'     => $isCsr,
                'old'       => $old,
                'error'     => $error,
                'encrypted' => $ca->caKeyEncrypted(),
                'maxDays'   => $ca->maxLeafDays(),
            ]);

        case 'cert':
            require_ca($ca);
            render('cert', ['title' => t('Zertifikat'), 'cert' => $ca->get((string)($_GET['id'] ?? '')), 'encrypted' => $ca->caKeyEncrypted()]);

        case 'cert-download':
            $cert = $ca->get((string)($_GET['id'] ?? ''));
            $name = safe_filename($cert['meta']['cn']);
            $id = $cert['id'];
            match ($_GET['fmt'] ?? '') {
                'crt'      => send_download($cert['certPem'], "$name.crt", 'application/x-x509-cert'),
                'key'      => send_download($ca->keyPem($id), "$name.key", 'application/x-pem-file'),
                'chain'    => send_download($cert['certPem'] . $ca->caCertPem(), "$name-fullchain.pem", 'application/x-pem-file'),
                'combined' => send_download($ca->keyPem($id) . $cert['certPem'] . $ca->caCertPem(), "$name-combined.pem", 'application/x-pem-file'),
                default    => throw new RuntimeException(t('Unbekanntes Format.')),
            };

        case 'cert-p12':
            if (!$isPost) {
                redirect('dashboard');
            }
            $cert = $ca->get((string)($_POST['id'] ?? ''));
            send_download($ca->pkcs12($cert['id'], (string)($_POST['p12_pass'] ?? '')), safe_filename($cert['meta']['cn']) . '.p12', 'application/x-pkcs12');

        case 'cert-renew':
            if (!$isPost) {
                redirect('dashboard');
            }
            $id = (string)($_POST['id'] ?? '');
            try {
                $newId = $ca->renew($id, (string)($_POST['ca_pass'] ?? ''));
                flash('success', t('Neues Zertifikat mit denselben Parametern ausgestellt. Das alte Zertifikat kann gelöscht werden, sobald das neue eingespielt ist.'));
                redirect('cert', ['id' => $newId]);
            } catch (RuntimeException $e) {
                flash('error', $e->getMessage());
                redirect('cert', ['id' => $id]);
            }

        case 'cert-delete':
            if ($isPost) {
                $ca->delete((string)($_POST['id'] ?? ''));
                flash('success', t('Zertifikat gelöscht.'));
            }
            redirect('dashboard');

        default:
            http_response_code(404);
            render('message', ['title' => t('Nicht gefunden'), 'message' => t('Diese Seite gibt es nicht.')]);
    }
} catch (RuntimeException $e) {
    render('message', ['title' => t('Fehler'), 'message' => $e->getMessage()]);
}

function require_ca(CertificateAuthority $ca): void
{
    if (!$ca->hasCa()) {
        flash('warn', t('Bitte zuerst eine Root-CA erstellen.'));
        redirect('ca-new');
    }
}

/** Liest PEM-Inhalt aus einem Datei-Upload oder – falls leer – aus einem Textfeld. */
function uploaded_or_text(string $fileField, string $textField): string
{
    $file = $_FILES[$fileField] ?? null;
    if ($file && $file['error'] === UPLOAD_ERR_OK && $file['size'] > 0) {
        $content = (string)file_get_contents($file['tmp_name']);
        // DER-Dateien (binär) in PEM umwandeln
        if (!str_contains($content, '-----BEGIN')) {
            $label = match ($fileField) {
                'csr_file' => 'CERTIFICATE REQUEST',
                'key_file' => 'PRIVATE KEY',
                default    => 'CERTIFICATE',
            };
            $content = "-----BEGIN $label-----\n" . chunk_split(base64_encode($content), 64, "\n") . "-----END $label-----\n";
        }
        return $content;
    }
    $text = trim((string)($_POST[$textField] ?? ''));
    if ($text === '') {
        throw new RuntimeException(t('Bitte eine Datei hochladen oder den PEM-Inhalt einfügen.'));
    }
    return $text;
}

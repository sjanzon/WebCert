<?php
declare(strict_types=1);

/**
 * Dateibasierte Mini-CA auf Basis der PHP-OpenSSL-Erweiterung.
 *
 * Ablage:
 *   data/ca/ca.crt, data/ca/ca.key           Root-CA (Schlüssel optional mit Passphrase verschlüsselt)
 *   data/certs/<id>/cert.pem, key.pem, csr.pem, meta.json
 */
final class CertificateAuthority
{
    public const KEY_TYPES = [
        'rsa2048' => ['label' => 'RSA 2048 Bit (maximale Kompatibilität)', 'private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048],
        'rsa3072' => ['label' => 'RSA 3072 Bit', 'private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 3072],
        'rsa4096' => ['label' => 'RSA 4096 Bit', 'private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 4096],
        'ec256'   => ['label' => 'ECDSA P-256 (schnell, modern)', 'private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1'],
        'ec384'   => ['label' => 'ECDSA P-384', 'private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'secp384r1'],
    ];

    /** Apple-Geräte akzeptieren TLS-Serverzertifikate nur mit maximal 825 Tagen Laufzeit – auch von privaten CAs. */
    public const APPLE_MAX_DAYS = 825;

    private array $tempFiles = [];

    public function __construct(private readonly string $dir)
    {
        foreach ([$dir, "$dir/ca", "$dir/certs"] as $d) {
            if (!is_dir($d) && !mkdir($d, 0700, true) && !is_dir($d)) {
                throw new RuntimeException(t('Verzeichnis {dir} konnte nicht angelegt werden.', ['dir' => $d]));
            }
        }
        if (!is_file("$dir/.htaccess")) {
            file_put_contents("$dir/.htaccess", "Require all denied\n");
        }
    }

    public function __destruct()
    {
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }
    }

    // ------------------------------------------------------------------ CA

    public function hasCa(): bool
    {
        return is_file("$this->dir/ca/ca.crt") && is_file("$this->dir/ca/ca.key");
    }

    public function caCertPem(): string
    {
        return $this->read("$this->dir/ca/ca.crt");
    }

    public function caKeyPem(): string
    {
        return $this->read("$this->dir/ca/ca.key");
    }

    public function caKeyEncrypted(): bool
    {
        return str_contains($this->caKeyPem(), 'ENCRYPTED');
    }

    public function caInfo(): array
    {
        return $this->describe($this->caCertPem());
    }

    public function createCa(array $o): void
    {
        if ($this->hasCa()) {
            throw new RuntimeException(t('Es existiert bereits eine CA.'));
        }
        $years = (int)($o['years'] ?? 25);
        if ($years < 1 || $years > 100) {
            throw new RuntimeException(t('Die Laufzeit der CA muss zwischen 1 und 100 Jahren liegen.'));
        }
        $pass = (string)($o['passphrase'] ?? '');
        if ($pass !== '' && $pass !== (string)($o['passphrase2'] ?? '')) {
            throw new RuntimeException(t('Die Passphrasen stimmen nicht überein.'));
        }

        $ext = [
            'basicConstraints'       => 'critical, CA:TRUE, pathlen:0',
            'keyUsage'               => 'critical, keyCertSign, cRLSign',
            'subjectKeyIdentifier'   => 'hash',
            'authorityKeyIdentifier' => 'keyid:always',
        ];
        $constraints = $this->parseNameConstraints((string)($o['name_constraints'] ?? ''));
        if ($constraints) {
            $ext['nameConstraints'] = 'critical, ' . implode(', ', $constraints);
        }

        $dn = $this->buildDn($o);
        $days = (int)(new DateTimeImmutable('today'))->diff(new DateTimeImmutable("today +$years years"))->days;
        $key = $this->newKey((string)($o['key_type'] ?? 'rsa4096'));

        $this->clearErrors();
        $csr = openssl_csr_new($dn, $key, $this->config());
        if ($csr === false) {
            throw $this->error(t('CSR für die CA konnte nicht erzeugt werden'));
        }
        $cert = openssl_csr_sign($csr, null, $key, $days, $this->config($ext), $this->serial());
        if ($cert === false) {
            throw $this->error(t('CA-Zertifikat konnte nicht signiert werden'));
        }

        openssl_x509_export($cert, $certPem);
        $this->storeCa($certPem, $this->exportKey($key, $pass));
    }

    public function importCa(string $certPem, string $keyPem, string $pass): void
    {
        if ($this->hasCa()) {
            throw new RuntimeException(t('Es existiert bereits eine CA.'));
        }
        $this->clearErrors();
        $cert = openssl_x509_read($certPem);
        if ($cert === false) {
            throw $this->error(t('Das CA-Zertifikat ist ungültig'));
        }
        if (!$this->describe($certPem)['isCa']) {
            throw new RuntimeException(t('Das Zertifikat ist kein CA-Zertifikat (basicConstraints CA:TRUE fehlt).'));
        }
        if (str_contains($keyPem, 'ENCRYPTED') && $pass === '') {
            throw new RuntimeException(t('Der Schlüssel ist verschlüsselt – bitte Passphrase angeben.'));
        }
        $key = openssl_pkey_get_private($keyPem, $pass);
        if ($key === false) {
            throw $this->error(t('Der private Schlüssel konnte nicht gelesen werden (Passphrase falsch?)'));
        }
        if (!openssl_x509_check_private_key($cert, $key)) {
            throw new RuntimeException(t('Zertifikat und privater Schlüssel gehören nicht zusammen.'));
        }
        openssl_x509_export($cert, $normalized);
        $this->storeCa($normalized, $this->exportKey($key, $pass));
    }

    public function deleteCa(): void
    {
        foreach ($this->all() as $cert) {
            $this->delete($cert['id']);
        }
        @unlink("$this->dir/ca/ca.crt");
        @unlink("$this->dir/ca/ca.key");
    }

    // ------------------------------------------------------- Zertifikate

    /** Erzeugt Schlüssel + Zertifikat. Gibt die ID des neuen Zertifikats zurück. */
    public function issue(array $o): string
    {
        $keyType = (string)($o['key_type'] ?? 'rsa2048');
        $sans = $this->collectSans((string)($o['cn'] ?? ''), (string)($o['sans'] ?? ''));
        $dn = $this->buildDn($o);
        $requestedDays = (int)($o['days'] ?? self::APPLE_MAX_DAYS);
        $days = $this->clampDays($requestedDays);
        $this->assertPermitted($sans);
        $caKey = $this->caKey((string)($o['ca_pass'] ?? ''));
        $key = $this->newKey($keyType);

        $this->clearErrors();
        $csr = openssl_csr_new($dn, $key, $this->config());
        if ($csr === false) {
            throw $this->error(t('CSR konnte nicht erzeugt werden'));
        }
        $certPem = $this->sign($csr, $caKey, $days, $this->leafExtensions(str_starts_with($keyType, 'rsa'), !empty($o['client_auth'])), $sans);
        openssl_pkey_export($key, $keyPem, null, $this->config());

        return $this->save($certPem, $keyPem, null, [
            'source'        => 'generated',
            'cn'            => $dn['commonName'],
            'dn'            => $dn,
            'sans'          => $this->sansToStrings($sans),
            'key_type'      => $keyType,
            'client_auth'   => !empty($o['client_auth']),
            'days'          => $requestedDays,
        ]);
    }

    /** Signiert einen extern erzeugten CSR (z. B. von Router, NAS, Drucker). */
    public function signCsr(string $csrPem, array $o): string
    {
        $this->clearErrors();
        $csrPem = trim($csrPem);
        $subject = @openssl_csr_get_subject($csrPem, false);
        $pub = @openssl_csr_get_public_key($csrPem);
        if ($subject === false || $pub === false) {
            throw $this->error(t('Der CSR ist ungültig'));
        }
        $cn = (string)($subject['commonName'] ?? '');
        $sans = $this->collectSans($cn, (string)($o['sans'] ?? ''));
        $requestedDays = (int)($o['days'] ?? self::APPLE_MAX_DAYS);
        $days = $this->clampDays($requestedDays);
        $this->assertPermitted($sans);
        $caKey = $this->caKey((string)($o['ca_pass'] ?? ''));
        $isRsa = openssl_pkey_get_details($pub)['type'] === OPENSSL_KEYTYPE_RSA;

        $certPem = $this->sign($csrPem, $caKey, $days, $this->leafExtensions($isRsa, !empty($o['client_auth'])), $sans);

        return $this->save($certPem, null, $csrPem . "\n", [
            'source'      => 'csr',
            'cn'          => $cn !== '' ? $cn : $sans[0][1],
            'sans'        => $this->sansToStrings($sans),
            'key_type'    => null,
            'client_auth' => !empty($o['client_auth']),
            'days'        => $requestedDays,
        ]);
    }

    /** Stellt ein neues Zertifikat mit denselben Parametern aus (neuer Schlüssel, bei CSR derselbe Schlüssel). */
    public function renew(string $id, string $caPass): string
    {
        $cert = $this->get($id);
        $m = $cert['meta'];
        $o = [
            'sans'        => implode("\n", array_map(fn($s) => substr($s, strpos($s, ':') + 1), $m['sans'])),
            'days'        => $m['days'],
            'client_auth' => $m['client_auth'],
            'ca_pass'     => $caPass,
        ];
        if ($m['source'] === 'csr') {
            return $this->signCsr($this->read("$this->dir/certs/$id/csr.pem"), $o);
        }
        return $this->issue($o + $this->dnToForm($m['dn'] ?? ['commonName' => $m['cn']]) + ['key_type' => $m['key_type']]);
    }

    /** @return list<array> */
    public function all(): array
    {
        $list = [];
        foreach (glob("$this->dir/certs/*/meta.json") ?: [] as $file) {
            $list[] = $this->get(basename(dirname($file)));
        }
        usort($list, fn($a, $b) => $b['meta']['created'] <=> $a['meta']['created']);
        return $list;
    }

    public function get(string $id): array
    {
        $path = $this->certDir($id);
        if (!is_file("$path/meta.json")) {
            throw new RuntimeException(t('Zertifikat nicht gefunden.'));
        }
        $certPem = $this->read("$path/cert.pem");
        return [
            'id'      => $id,
            'meta'    => json_decode($this->read("$path/meta.json"), true, flags: JSON_THROW_ON_ERROR),
            'info'    => $this->describe($certPem),
            'certPem' => $certPem,
            'hasKey'  => is_file("$path/key.pem"),
        ];
    }

    public function keyPem(string $id): string
    {
        $file = $this->certDir($id) . '/key.pem';
        if (!is_file($file)) {
            throw new RuntimeException(t('Für dieses Zertifikat liegt kein privater Schlüssel vor (per CSR signiert).'));
        }
        return $this->read($file);
    }

    public function delete(string $id): void
    {
        $path = $this->certDir($id);
        foreach (glob("$path/*") ?: [] as $file) {
            unlink($file);
        }
        @rmdir($path);
    }

    public function pkcs12(string $id, string $password): string
    {
        $cert = $this->get($id);
        $this->clearErrors();
        $ok = openssl_pkcs12_export($cert['certPem'], $out, $this->keyPem($id), $password, [
            'extracerts'    => [$this->caCertPem()],
            'friendly_name' => $cert['meta']['cn'],
        ]);
        if (!$ok) {
            throw $this->error(t('PKCS#12-Datei konnte nicht erstellt werden'));
        }
        return $out;
    }

    /** Zerlegt ein Zertifikat in die für die Oberfläche relevanten Angaben. */
    public function describe(string $certPem): array
    {
        $x = openssl_x509_parse($certPem);
        if ($x === false) {
            throw new RuntimeException(t('Zertifikat konnte nicht gelesen werden.'));
        }
        $details = openssl_pkey_get_details(openssl_pkey_get_public($certPem));
        $key = $details['type'] === OPENSSL_KEYTYPE_EC
            ? 'ECDSA ' . ($details['ec']['curve_name'] ?? '')
            : 'RSA ' . $details['bits'] . ' Bit';
        $sans = isset($x['extensions']['subjectAltName'])
            ? array_map('trim', explode(',', $x['extensions']['subjectAltName']))
            : [];

        return [
            'cn'          => $this->flatten($x['subject']['CN'] ?? ''),
            'subject'     => $x['subject'],
            'issuer'      => $this->flatten($x['issuer']['CN'] ?? ''),
            'serial'      => implode(':', str_split(strtoupper($x['serialNumberHex']), 2)),
            'validFrom'   => $x['validFrom_time_t'],
            'validTo'     => $x['validTo_time_t'],
            'sans'        => str_replace('IP Address:', 'IP:', $sans),
            'key'         => $key,
            'isCa'        => str_contains($x['extensions']['basicConstraints'] ?? '', 'CA:TRUE'),
            'constraints' => $x['extensions']['nameConstraints'] ?? null,
            'eku'         => $x['extensions']['extendedKeyUsage'] ?? null,
            'sha256'      => implode(':', str_split(strtoupper(openssl_x509_fingerprint($certPem, 'sha256')), 2)),
            'sha1'        => implode(':', str_split(strtoupper(openssl_x509_fingerprint($certPem, 'sha1')), 2)),
        ];
    }

    /** Verbleibende Tage, die ein neues Zertifikat noch gültig sein darf (begrenzt durch die CA-Laufzeit). */
    public function maxLeafDays(): int
    {
        return max(0, intdiv($this->caInfo()['validTo'] - time(), 86400));
    }

    // ------------------------------------------------------------ intern

    private function sign(mixed $csr, OpenSSLAsymmetricKey $caKey, int $days, array $ext, array $sans): string
    {
        $this->clearErrors();
        $cert = openssl_csr_sign($csr, $this->caCertPem(), $caKey, $days, $this->config($ext, $sans), $this->serial());
        if ($cert === false) {
            throw $this->error(t('Zertifikat konnte nicht signiert werden'));
        }
        openssl_x509_export($cert, $pem);
        return $pem;
    }

    private function leafExtensions(bool $rsa, bool $clientAuth): array
    {
        return [
            'basicConstraints'       => 'critical, CA:FALSE',
            'keyUsage'               => 'critical, ' . ($rsa ? 'digitalSignature, keyEncipherment' : 'digitalSignature'),
            'extendedKeyUsage'       => $clientAuth ? 'serverAuth, clientAuth' : 'serverAuth',
            'subjectKeyIdentifier'   => 'hash',
            'authorityKeyIdentifier' => 'keyid, issuer',
        ];
    }

    private function save(string $certPem, ?string $keyPem, ?string $csrPem, array $meta): string
    {
        $id = bin2hex(random_bytes(8));
        $path = $this->certDir($id);
        mkdir($path, 0700, true);
        file_put_contents("$path/cert.pem", $certPem);
        if ($keyPem !== null) {
            file_put_contents("$path/key.pem", $keyPem);
            @chmod("$path/key.pem", 0600);
        }
        if ($csrPem !== null) {
            file_put_contents("$path/csr.pem", $csrPem);
        }
        $meta['created'] = time();
        file_put_contents("$path/meta.json", json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $id;
    }

    private function storeCa(string $certPem, string $keyPem): void
    {
        file_put_contents("$this->dir/ca/ca.crt", $certPem);
        file_put_contents("$this->dir/ca/ca.key", $keyPem);
        @chmod("$this->dir/ca/ca.key", 0600);
    }

    private function caKey(string $pass): OpenSSLAsymmetricKey
    {
        if (!$this->hasCa()) {
            throw new RuntimeException(t('Es wurde noch keine CA erstellt.'));
        }
        if ($this->caKeyEncrypted() && $pass === '') {
            throw new RuntimeException(t('Bitte die Passphrase der CA angeben.'));
        }
        $key = openssl_pkey_get_private($this->caKeyPem(), $pass);
        if ($key === false) {
            $this->clearErrors();
            throw new RuntimeException(t('Die CA-Passphrase ist falsch.'));
        }
        return $key;
    }

    private function exportKey(OpenSSLAsymmetricKey $key, string $pass): string
    {
        $args = $this->config();
        if ($pass !== '') {
            $args += ['encrypt_key' => true, 'encrypt_key_cipher' => OPENSSL_CIPHER_AES_256_CBC];
        }
        if (!openssl_pkey_export($key, $pem, $pass === '' ? null : $pass, $args)) {
            throw $this->error(t('Privater Schlüssel konnte nicht exportiert werden'));
        }
        return $pem;
    }

    private function newKey(string $type): OpenSSLAsymmetricKey
    {
        if (!isset(self::KEY_TYPES[$type])) {
            throw new RuntimeException(t('Unbekannter Schlüsseltyp.'));
        }
        $this->clearErrors();
        $params = self::KEY_TYPES[$type];
        unset($params['label']);
        $key = openssl_pkey_new($params + $this->config());
        if ($key === false) {
            throw $this->error(t('Schlüssel konnte nicht erzeugt werden'));
        }
        return $key;
    }

    private function clampDays(int $days): int
    {
        if ($days < 1) {
            throw new RuntimeException(t('Die Laufzeit muss mindestens 1 Tag betragen.'));
        }
        if (!$this->hasCa()) {
            throw new RuntimeException(t('Es wurde noch keine CA erstellt.'));
        }
        $max = $this->maxLeafDays();
        if ($max < 1) {
            throw new RuntimeException(t('Die CA ist abgelaufen.'));
        }
        return min($days, $max);
    }

    private function buildDn(array $o): array
    {
        $map = [
            'cn' => 'commonName',
            'c'  => 'countryName',
            'st' => 'stateOrProvinceName',
            'l'  => 'localityName',
            'o'  => 'organizationName',
            'ou' => 'organizationalUnitName',
        ];
        $dn = [];
        foreach ($map as $field => $name) {
            $value = trim((string)($o[$field] ?? ''));
            if ($value !== '') {
                $dn[$name] = $value;
            }
        }
        if (!isset($dn['commonName'])) {
            throw new RuntimeException(t('Der Common Name (CN) ist ein Pflichtfeld.'));
        }
        if (isset($dn['countryName'])) {
            $dn['countryName'] = strtoupper($dn['countryName']);
            if (!preg_match('/^[A-Z]{2}$/', $dn['countryName'])) {
                throw new RuntimeException(t('Das Land muss als zweistelliger Code angegeben werden (z. B. DE).'));
            }
        }
        foreach ($dn as $value) {
            if (mb_strlen($value) > 64) {
                throw new RuntimeException(t('Felder des Distinguished Name dürfen höchstens 64 Zeichen lang sein.'));
            }
        }
        return $dn;
    }

    private function dnToForm(array $dn): array
    {
        $map = ['commonName' => 'cn', 'countryName' => 'c', 'stateOrProvinceName' => 'st', 'localityName' => 'l', 'organizationName' => 'o', 'organizationalUnitName' => 'ou'];
        $form = [];
        foreach ($dn as $name => $value) {
            if (isset($map[$name])) {
                $form[$map[$name]] = $value;
            }
        }
        return $form;
    }

    /**
     * SANs aus dem Formular lesen; ist der CN ein Hostname bzw. eine IP, wird er automatisch ergänzt
     * (Browser werten nur noch den SAN aus, nicht den CN).
     *
     * @return list<array{0: 'DNS'|'IP', 1: string}>
     */
    private function collectSans(string $cn, string $input): array
    {
        $entries = preg_split('/[\s,;]+/', $input, -1, PREG_SPLIT_NO_EMPTY);
        $cn = trim($cn);
        if ($cn !== '' && $this->classifyName($cn) !== null) {
            array_unshift($entries, $cn);
        }
        $sans = [];
        foreach ($entries as $entry) {
            $type = $this->classifyName($entry);
            if ($type === null) {
                throw new RuntimeException(t('Ungültiger Hostname oder IP-Adresse: {entry}', ['entry' => $entry]));
            }
            $value = $type === 'DNS' ? strtolower($entry) : $entry;
            $sans["$type:$value"] = [$type, $value];
        }
        if (!$sans) {
            throw new RuntimeException(t('Mindestens ein Hostname oder eine IP-Adresse ist erforderlich.'));
        }
        return array_values($sans);
    }

    private function classifyName(string $name): ?string
    {
        if (filter_var($name, FILTER_VALIDATE_IP)) {
            return 'IP';
        }
        $label = '[a-z0-9_](?:[a-z0-9_-]{0,61}[a-z0-9])?';
        return preg_match("/^(?:\\*\\.)?$label(?:\\.$label)*$/i", $name) && strlen($name) <= 253 ? 'DNS' : null;
    }

    /** Verhindert Zertifikate, die Browser wegen der Namensbeschränkung der CA ablehnen würden. */
    private function assertPermitted(array $sans): void
    {
        $constraints = $this->caInfo()['constraints'];
        if (!$constraints) {
            return;
        }
        $permittedBlock = explode('Excluded:', $constraints)[0];
        preg_match_all('/^\s*(DNS|IP):(\S+)/m', $permittedBlock, $matches, PREG_SET_ORDER);
        $permitted = ['DNS' => [], 'IP' => []];
        foreach ($matches as [, $type, $value]) {
            $permitted[$type][] = strtolower($value);
        }
        foreach ($sans as [$type, $value]) {
            if (!$permitted[$type]) {
                continue; // Für diesen Namenstyp gibt es keine Beschränkung
            }
            $ok = false;
            foreach ($permitted[$type] as $allowed) {
                if ($type === 'DNS') {
                    $name = preg_replace('/^\*\./', '', $value);
                    $ok = $name === $allowed || str_ends_with($name, ".$allowed");
                } else {
                    [$net, $mask] = array_map('inet_pton', explode('/', $allowed, 2));
                    $ip = inet_pton($value);
                    $ok = strlen($ip) === strlen($net) && ($ip & $mask) === $net;
                }
                if ($ok) {
                    break;
                }
            }
            if (!$ok) {
                throw new RuntimeException(t('„{value}“ liegt außerhalb der Namensbeschränkung der CA ({permitted}). Browser würden das Zertifikat ablehnen.', ['value' => $value, 'permitted' => implode(', ', $permitted[$type])]));
            }
        }
    }

    private function sansToStrings(array $sans): array
    {
        return array_map(fn($s) => "$s[0]:$s[1]", $sans);
    }

    /** "lan", "home.arpa", "192.168.0.0/16", "fd00::/8" → OpenSSL-Syntax für nameConstraints */
    private function parseNameConstraints(string $input): array
    {
        $result = [];
        foreach (preg_split('/[\s,;]+/', $input, -1, PREG_SPLIT_NO_EMPTY) as $entry) {
            if (str_contains($entry, '/')) {
                [$ip, $prefix] = explode('/', $entry, 2);
                $bin = @inet_pton($ip);
                $bits = strlen((string)$bin) * 8;
                if ($bin === false || !ctype_digit($prefix) || (int)$prefix > $bits) {
                    throw new RuntimeException(t('Ungültiges Netz in den Namensbeschränkungen: {entry}', ['entry' => $entry]));
                }
                $mask = str_repeat("\xff", intdiv((int)$prefix, 8));
                if ((int)$prefix % 8) {
                    $mask .= chr((0xff << (8 - (int)$prefix % 8)) & 0xff);
                }
                $mask = str_pad($mask, $bits / 8, "\0");
                $result[] = 'permitted;IP:' . inet_ntop($bin & $mask) . '/' . inet_ntop($mask);
            } else {
                $domain = ltrim(strtolower($entry), '.');
                if ($this->classifyName($domain) !== 'DNS' || str_contains($domain, '*')) {
                    throw new RuntimeException(t('Ungültige Domain in den Namensbeschränkungen: {entry}', ['entry' => $entry]));
                }
                $result[] = "permitted;DNS:$domain";
            }
        }
        return $result;
    }

    /** Schreibt eine temporäre OpenSSL-Konfiguration (nötig für Extensions/SAN und unter Windows ohne openssl.cnf). */
    private function config(array $ext = [], array $sans = []): array
    {
        $lines = ['[ req ]', 'distinguished_name = req_dn', '', '[ req_dn ]', '', '[ ext ]'];
        foreach ($ext as $name => $value) {
            $lines[] = "$name = $value";
        }
        if ($sans) {
            $lines[] = 'subjectAltName = @alt_names';
            $lines[] = '';
            $lines[] = '[ alt_names ]';
            $counter = ['DNS' => 0, 'IP' => 0];
            foreach ($sans as [$type, $value]) {
                $lines[] = sprintf('%s.%d = %s', $type, ++$counter[$type], $value);
            }
        }
        $file = tempnam(sys_get_temp_dir(), 'webcert');
        file_put_contents($file, implode("\n", $lines) . "\n");
        $this->tempFiles[] = $file;

        $args = ['config' => $file, 'digest_alg' => 'sha256'];
        if ($ext || $sans) {
            $args['x509_extensions'] = 'ext';
        }
        return $args;
    }

    private function serial(): int
    {
        return random_int(1, PHP_INT_MAX);
    }

    private function certDir(string $id): string
    {
        if (!preg_match('/^[a-f0-9]{16}$/', $id)) {
            throw new RuntimeException(t('Ungültige Zertifikats-ID.'));
        }
        return "$this->dir/certs/$id";
    }

    private function read(string $file): string
    {
        $content = @file_get_contents($file);
        if ($content === false) {
            throw new RuntimeException(t('Datei konnte nicht gelesen werden: {file}', ['file' => basename($file)]));
        }
        return $content;
    }

    private function flatten(string|array $value): string
    {
        return is_array($value) ? implode(', ', $value) : $value;
    }

    private function clearErrors(): void
    {
        while (openssl_error_string() !== false);
    }

    private function error(string $message): RuntimeException
    {
        $details = [];
        while (($e = openssl_error_string()) !== false) {
            $details[] = $e;
        }
        return new RuntimeException($message . ($details ? ': ' . implode('; ', $details) : '.'));
    }
}

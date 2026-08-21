<?php

declare(strict_types=1);

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath((string)$_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    http_response_code(404);
    exit;
}

const MIFP_REG_GUARD = '<?php http_response_code(404); exit; ?>';

/**
 * Small dependency-free YAML reader matching the limited YAML subset used by
 * conference.yaml. If ext-yaml is installed, PHP uses it instead.
 */
function yaml_scalar_value(string $raw): mixed
{
    $value = trim($raw);
    if ($value === '') {
        return '';
    }

    $first = $value[0] ?? '';
    $last = $value[strlen($value) - 1] ?? '';
    if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
        if ($first === '"') {
            $decoded = json_decode($value, true);
            return json_last_error() === JSON_ERROR_NONE ? $decoded : substr($value, 1, -1);
        }
        return str_replace("''", "'", substr($value, 1, -1));
    }

    if (preg_match('/^(true|false)$/i', $value) === 1) {
        return strtolower($value) === 'true';
    }
    if (preg_match('/^(null|~)$/i', $value) === 1) {
        return null;
    }
    if (preg_match('/^-?(?:\d+\.?\d*|\.\d+)$/', $value) === 1) {
        return str_contains($value, '.') ? (float)$value : (int)$value;
    }
    return $value;
}

function yaml_strip_comment(string $line): string
{
    $quote = null;
    $length = strlen($line);
    for ($i = 0; $i < $length; $i++) {
        $char = $line[$i];
        if (($char === '"' || $char === "'") && ($i === 0 || $line[$i - 1] !== '\\')) {
            $quote = $quote === $char ? null : ($quote ?? $char);
        }
        if ($char === '#' && $quote === null && ($i === 0 || ctype_space($line[$i - 1]))) {
            return rtrim(substr($line, 0, $i));
        }
    }
    return rtrim($line);
}

function yaml_mapping_entry(string $text): ?array
{
    $quote = null;
    $length = strlen($text);
    for ($i = 0; $i < $length; $i++) {
        $char = $text[$i];
        if (($char === '"' || $char === "'") && ($i === 0 || $text[$i - 1] !== '\\')) {
            $quote = $quote === $char ? null : ($quote ?? $char);
            continue;
        }
        if ($char === ':' && $quote === null) {
            $key = trim(substr($text, 0, $i));
            if ($key === '' || in_array($key, ['__proto__', 'prototype', 'constructor'], true)) {
                throw new RuntimeException('Invalid YAML key.');
            }
            return [$key, trim(substr($text, $i + 1))];
        }
    }
    return null;
}

function yaml_tokenize(string $raw): array
{
    if (strlen($raw) > 2_000_000) {
        throw new RuntimeException('conference.yaml is unexpectedly large.');
    }

    $tokens = [];
    $lines = preg_split('/\r?\n/', ltrim($raw, "\xEF\xBB\xBF")) ?: [];
    foreach ($lines as $index => $rawLine) {
        $line = str_replace("\t", '  ', $rawLine);
        $clean = yaml_strip_comment($line);
        if (trim($clean) === '') {
            continue;
        }
        preg_match('/^\s*/', $clean, $match);
        $tokens[] = [
            'indent' => strlen($match[0] ?? ''),
            'text' => trim($clean),
            'line' => $index + 1,
        ];
    }
    return $tokens;
}

function yaml_read_multiline(array $tokens, int $position, int $parentIndent, bool $fold): array
{
    $parts = [];
    $count = count($tokens);
    while ($position < $count && $tokens[$position]['indent'] > $parentIndent) {
        $parts[] = $tokens[$position]['text'];
        $position++;
    }
    return [$fold ? implode(' ', $parts) : implode("\n", $parts), $position];
}

function yaml_parse_block(array $tokens, int $position, int $indent, int $depth = 0): array
{
    if ($depth > 40) {
        throw new RuntimeException('conference.yaml nesting is too deep.');
    }
    $count = count($tokens);
    if ($position >= $count) {
        return [[], $position];
    }

    $isArray = $tokens[$position]['indent'] === $indent && str_starts_with($tokens[$position]['text'], '- ');
    $container = [];

    while ($position < $count) {
        $token = $tokens[$position];
        if ($token['indent'] < $indent) {
            break;
        }
        if ($token['indent'] > $indent) {
            throw new RuntimeException('Unexpected YAML indentation at line ' . $token['line'] . '.');
        }

        if ($isArray) {
            if (!str_starts_with($token['text'], '- ')) {
                break;
            }
            $body = trim(substr($token['text'], 2));
            if ($body === '') {
                if ($position + 1 < $count && $tokens[$position + 1]['indent'] > $indent) {
                    [$value, $position] = yaml_parse_block($tokens, $position + 1, $tokens[$position + 1]['indent'], $depth + 1);
                    $container[] = $value;
                } else {
                    $container[] = null;
                    $position++;
                }
                continue;
            }

            $pair = yaml_mapping_entry($body);
            if ($pair === null) {
                $container[] = yaml_scalar_value($body);
                $position++;
                continue;
            }

            $object = [];
            [$key, $valueText] = $pair;
            if ($valueText === '|' || $valueText === '>') {
                [$value, $position] = yaml_read_multiline($tokens, $position + 1, $indent, $valueText === '>');
                $object[$key] = $value;
            } elseif ($valueText !== '') {
                $object[$key] = yaml_scalar_value($valueText);
                $position++;
            } elseif ($position + 1 < $count && $tokens[$position + 1]['indent'] > $indent) {
                [$value, $position] = yaml_parse_block($tokens, $position + 1, $tokens[$position + 1]['indent'], $depth + 1);
                $object[$key] = $value;
            } else {
                $object[$key] = [];
                $position++;
            }

            while ($position < $count && $tokens[$position]['indent'] > $indent) {
                $child = $tokens[$position];
                $childIndent = $child['indent'];
                if (str_starts_with($child['text'], '- ')) {
                    break;
                }
                $childPair = yaml_mapping_entry($child['text']);
                if ($childPair === null) {
                    break;
                }
                [$childKey, $childValueText] = $childPair;
                if ($childValueText === '|' || $childValueText === '>') {
                    [$childValue, $position] = yaml_read_multiline($tokens, $position + 1, $childIndent, $childValueText === '>');
                    $object[$childKey] = $childValue;
                } elseif ($childValueText !== '') {
                    $object[$childKey] = yaml_scalar_value($childValueText);
                    $position++;
                } elseif ($position + 1 < $count && $tokens[$position + 1]['indent'] > $childIndent) {
                    [$childValue, $position] = yaml_parse_block($tokens, $position + 1, $tokens[$position + 1]['indent'], $depth + 1);
                    $object[$childKey] = $childValue;
                } else {
                    $object[$childKey] = [];
                    $position++;
                }
            }

            $container[] = $object;
            continue;
        }

        $pair = yaml_mapping_entry($token['text']);
        if ($pair === null) {
            throw new RuntimeException('Expected YAML key/value at line ' . $token['line'] . '.');
        }
        [$key, $valueText] = $pair;
        if ($valueText === '|' || $valueText === '>') {
            [$value, $position] = yaml_read_multiline($tokens, $position + 1, $indent, $valueText === '>');
            $container[$key] = $value;
        } elseif ($valueText !== '') {
            $container[$key] = yaml_scalar_value($valueText);
            $position++;
        } elseif ($position + 1 < $count && $tokens[$position + 1]['indent'] > $indent) {
            [$value, $position] = yaml_parse_block($tokens, $position + 1, $tokens[$position + 1]['indent'], $depth + 1);
            $container[$key] = $value;
        } else {
            $container[$key] = [];
            $position++;
        }
    }

    return [$container, $position];
}

function parse_conference_yaml(string $file): array
{
    if (!is_file($file) || !is_readable($file)) {
        throw new RuntimeException('conference.yaml is unavailable.');
    }

    if (function_exists('yaml_parse_file')) {
        $parsed = @yaml_parse_file($file);
        if (is_array($parsed)) {
            return $parsed;
        }
    }

    $raw = file_get_contents($file);
    if (!is_string($raw)) {
        throw new RuntimeException('conference.yaml could not be read.');
    }
    $tokens = yaml_tokenize($raw);
    if ($tokens === []) {
        throw new RuntimeException('conference.yaml is empty.');
    }
    [$parsed] = yaml_parse_block($tokens, 0, $tokens[0]['indent']);
    return $parsed;
}

function yaml_path(array $config, array $path, mixed $default = null): mixed
{
    $value = $config;
    foreach ($path as $key) {
        if (!is_array($value) || !array_key_exists($key, $value)) {
            return $default;
        }
        $value = $value[$key];
    }
    return $value;
}

function registration_field_options(array $config, string $fieldName): array
{
    $sections = yaml_path($config, ['registration', 'form', 'sections'], []);
    if (!is_array($sections)) {
        return [];
    }
    foreach ($sections as $section) {
        if (!is_array($section)) {
            continue;
        }
        $fields = $section['fields'] ?? [];
        if (!is_array($fields)) {
            continue;
        }
        foreach ($fields as $field) {
            if (!is_array($field) || (string)($field['name'] ?? '') !== $fieldName) {
                continue;
            }
            $options = $field['options'] ?? [];
            if (!is_array($options)) {
                return [];
            }
            $values = [];
            foreach ($options as $option) {
                if (is_array($option)) {
                    $candidate = $option['value'] ?? $option['label'] ?? null;
                } else {
                    $candidate = $option;
                }
                if (is_scalar($candidate) && trim((string)$candidate) !== '') {
                    $values[] = (string)$candidate;
                }
            }
            return array_values(array_unique($values));
        }
    }
    return [];
}

function yaml_string_list(mixed $value): array
{
    if (!is_array($value)) {
        return is_scalar($value) && trim((string)$value) !== '' ? [(string)$value] : [];
    }
    $result = [];
    foreach ($value as $item) {
        if (is_scalar($item) && trim((string)$item) !== '') {
            $result[] = (string)$item;
        }
    }
    return $result;
}

function load_settings(string $file): array
{
    $config = parse_conference_yaml($file);
    $conference = yaml_path($config, ['conference'], []);
    $registration = yaml_path($config, ['registration'], []);
    $form = yaml_path($config, ['registration', 'form'], []);
    $backend = yaml_path($config, ['registration', 'form', 'backend'], []);
    $mail = yaml_path($config, ['registration', 'form', 'mail'], []);
    $appearance = yaml_path($config, ['appearance'], []);
    $runtime = yaml_path($config, ['runtime'], []);

    if (!is_array($conference) || !is_array($registration) || !is_array($form) || !is_array($backend) || !is_array($mail) || !is_array($appearance) || !is_array($runtime)) {
        throw new RuntimeException('Registration configuration in conference.yaml is invalid.');
    }

    $contactEmail = trim((string)($conference['email'] ?? ''));
    if ($contactEmail === '') {
        throw new RuntimeException('conference.email must be configured.');
    }

    $adminEmails = yaml_string_list($mail['admin_emails'] ?? []);
    if ($adminEmails === []) {
        $adminEmails = [$contactEmail];
    }

    $city = trim((string)($conference['city'] ?? ''));
    $country = trim((string)($conference['country'] ?? ''));
    $location = trim($city . ($city !== '' && $country !== '' ? ', ' : '') . $country);

    $categories = registration_field_options($config, 'registration_type');
    if ($categories === []) {
        foreach (($registration['plans'] ?? []) as $plan) {
            if (is_array($plan) && trim((string)($plan['name'] ?? '')) !== '') {
                $categories[] = (string)$plan['name'];
            }
        }
    }

    $warnings = array_merge(
        yaml_string_list($registration['warnings'] ?? []),
        yaml_string_list($registration['important_notes'] ?? [])
    );

    $settings = [
        'conference' => [
            'event' => (string)(yaml_path($config, ['site', 'title'], '') ?: ($conference['acronym'] ?? 'MIFP Conference')),
            'full_name' => (string)($conference['full_name'] ?? ''),
            'date_label' => (string)($conference['date_label'] ?? ''),
            'location' => $location,
            'back_url' => (string)($form['back_url'] ?? '../registration.html'),
            'privacy_url' => (string)($form['privacy_url'] ?? '../privacy.html'),
            'contact_email' => $contactEmail,
            'registration_visible' => (bool)($registration['enabled'] ?? true),
            'registration_open' => (bool)($form['submit_enabled'] ?? false),
            'closed_message' => (string)($form['closed_message'] ?? $form['unavailable_label'] ?? 'Online registration is not currently available.'),
        ],
        'mail' => [
            'from_email' => (string)($mail['from_email'] ?? $contactEmail),
            'from_name' => (string)($mail['from_name'] ?? ($conference['contact_name'] ?? 'MIFP')),
            'admin_emails' => $adminEmails,
            'subject_prefix' => (string)($mail['subject_prefix'] ?? '[MIFP]'),
            'send_user_confirmation' => (bool)($mail['send_user_confirmation'] ?? true),
        ],
        'form' => [
            'max_upload_mb' => (int)($backend['max_upload_mb'] ?? 5),
            'tshirt_enabled' => (bool)($backend['tshirt_enabled'] ?? true),
            'required_note' => (string)($form['required_note'] ?? 'Fields marked with * are required.'),
            'submit_label' => (string)($form['submit_label'] ?? 'Submit registration'),
        ],
        'categories' => $categories,
        'payment_methods' => registration_field_options($config, 'payment_method'),
        'tshirt_sizes' => registration_field_options($config, 'tshirt_size'),
        'dietary_choices' => registration_field_options($config, 'dietary_choice'),
        'content' => [
            'intro' => (string)($form['intro'] ?? ''),
            'payment' => is_array($registration['payment'] ?? null) ? $registration['payment'] : [],
            'payment_methods_detail' => is_array($registration['payment_methods'] ?? null) ? $registration['payment_methods'] : [],
            'provider' => (string)($registration['provider'] ?? ''),
            'provider_note' => (string)($registration['provider_note'] ?? ''),
            'guide_title' => (string)($registration['guide_title'] ?? 'Registration & Payment Guide'),
            'steps' => yaml_string_list($registration['guide_steps'] ?? []),
            'warnings' => $warnings,
            'privacy_notice' => is_array($form['privacy_notice'] ?? null) ? $form['privacy_notice'] : [],
        ],
        'appearance' => [
            'default_theme' => (string)($appearance['default_theme'] ?? ''),
            'default_palette' => (string)($appearance['default_palette'] ?? ''),
            'remember_preferences' => (bool)($appearance['remember_preferences'] ?? true),
            'max_content_width' => (string)($appearance['max_content_width'] ?? '1080px'),
            'component_radius' => (string)($appearance['component_radius'] ?? '8px'),
            'panel_radius' => (string)($appearance['panel_radius'] ?? '10px'),
            'control_radius' => (string)($appearance['control_radius'] ?? '6px'),
            'themes' => is_array($appearance['themes'] ?? null) ? $appearance['themes'] : [],
            'palettes' => is_array($appearance['palettes'] ?? null) ? $appearance['palettes'] : [],
        ],
        'runtime' => [
            'debug' => (bool)($runtime['debug'] ?? false),
            'log_level' => (string)($runtime['log_level'] ?? 'info'),
            'debug_log_level' => (string)($runtime['debug_log_level'] ?? 'debug'),
            'log_prefix' => (string)($runtime['log_prefix'] ?? 'MIFP'),
        ],
        'security' => [
            'rate_limit_requests' => (int)($backend['rate_limit_requests'] ?? 5),
            'rate_limit_window_seconds' => (int)($backend['rate_limit_window_seconds'] ?? 900),
            'minimum_fill_seconds' => (int)($backend['minimum_fill_seconds'] ?? 2),
            'trust_proxy' => (bool)($backend['trust_proxy'] ?? false),
            'trusted_proxies' => yaml_string_list($backend['trusted_proxies'] ?? []),
        ],
        'storage' => [
            'path' => (string)($backend['storage_path'] ?? 'storage'),
            'persist_submissions' => (bool)($backend['persist_submissions'] ?? true),
        ],
    ];

    foreach (['categories', 'payment_methods', 'dietary_choices'] as $requiredList) {
        if ($settings[$requiredList] === []) {
            throw new RuntimeException('Missing registration options in conference.yaml: ' . $requiredList);
        }
    }

    return $settings;
}

function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function text_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

function clean_text(mixed $value, int $maxLength, bool $multiline = false): string
{
    $text = is_string($value) ? trim($value) : '';
    $text = str_replace("\0", '', $text);
    if (!$multiline) {
        $text = preg_replace('/[\r\n\t]+/u', ' ', $text) ?? $text;
    } else {
        $text = preg_replace("/\r\n?|\n/u", "\n", $text) ?? $text;
    }
    $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? $text;

    if (text_length($text) > $maxLength) {
        return function_exists('mb_substr') ? mb_substr($text, 0, $maxLength, 'UTF-8') : substr($text, 0, $maxLength);
    }
    return $text;
}

function bool_setting(mixed $value, bool $default = false): bool
{
    if (is_bool($value)) {
        return $value;
    }
    if (is_int($value)) {
        return $value !== 0;
    }
    if (is_string($value)) {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }
    return $default;
}

function resolve_storage_path(string $baseDir, string $configured): string
{
    $configured = trim($configured);
    if ($configured === '') {
        throw new RuntimeException('Storage path is not configured.');
    }

    $isAbsolute = str_starts_with($configured, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $configured) === 1;
    return $isAbsolute ? rtrim($configured, DIRECTORY_SEPARATOR) : $baseDir . DIRECTORY_SEPARATOR . trim($configured, '/\\');
}

function ensure_storage(string $path): void
{
    if (!is_dir($path) && !mkdir($path, 0700, true) && !is_dir($path)) {
        throw new RuntimeException('Registration storage could not be created.');
    }
    @chmod($path, 0700);
    if (!is_writable($path)) {
        throw new RuntimeException('Registration storage is not writable.');
    }
}

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Strict');

    $secure = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
        || ((string)($_SERVER['SERVER_PORT'] ?? '') === '443');

    session_name('MIFPREG');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => rtrim(dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/')), '/') . '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

function issue_security_headers(?string $styleNonce = null): void
{
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: SAMEORIGIN');
    header("Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()");
    $styleSrc = "'self'" . ($styleNonce !== null && $styleNonce !== '' ? " 'nonce-" . preg_replace('/[^A-Za-z0-9+\/=]/', '', $styleNonce) . "'" : '');
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src " . $styleSrc . "; img-src 'self' data:; form-action 'self'; base-uri 'none'; object-src 'none'; frame-ancestors 'self'");
    header('X-Robots-Tag: noindex, nofollow, noarchive');
    header('Cache-Control: no-store, max-age=0');
    header('Pragma: no-cache');

    $secure = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
        || ((string)($_SERVER['SERVER_PORT'] ?? '') === '443');
    if ($secure) {
        header('Strict-Transport-Security: max-age=31536000');
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf(mixed $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && is_string($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function reset_form_security_state(): void
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['form_started_at'] = time();
}

function current_client_ip(array $security): string
{
    $remote = filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP) ?: '0.0.0.0';
    $trustProxy = bool_setting($security['trust_proxy'] ?? false);
    $trusted = $security['trusted_proxies'] ?? [];
    if (!is_array($trusted)) {
        $trusted = [$trusted];
    }

    if ($trustProxy && in_array($remote, $trusted, true)) {
        $forwarded = (string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
        if ($forwarded !== '') {
            $candidate = trim(explode(',', $forwarded)[0]);
            if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                return $candidate;
            }
        }
    }

    return $remote;
}


function storage_secret(string $storagePath): string
{
    $path = $storagePath . DIRECTORY_SEPARATOR . '.secret.php';
    if (!is_file($path)) {
        $secret = bin2hex(random_bytes(32));
        $fp = @fopen($path, 'x');
        if ($fp !== false) {
            fwrite($fp, MIFP_REG_GUARD . "\n" . $secret . "\n");
            fflush($fp);
            fclose($fp);
            @chmod($path, 0600);
            return $secret;
        }
    }

    $raw = @file_get_contents($path);
    if (!is_string($raw)) {
        throw new RuntimeException('Security secret storage is unavailable.');
    }
    $newline = strpos($raw, "\n");
    if ($newline === false || trim(substr($raw, 0, $newline)) !== MIFP_REG_GUARD) {
        throw new RuntimeException('Security secret storage is invalid.');
    }
    $secret = trim(substr($raw, $newline + 1));
    if (!preg_match('/^[a-f0-9]{64}$/', $secret)) {
        throw new RuntimeException('Security secret storage is invalid.');
    }
    return $secret;
}

function rate_limit_or_throw(string $storagePath, array $security): void
{
    $limit = max(1, (int)($security['rate_limit_requests'] ?? 5));
    $window = max(60, (int)($security['rate_limit_window_seconds'] ?? 900));
    $secret = storage_secret($storagePath);
    $key = hash_hmac('sha256', current_client_ip($security), $secret);
    $path = $storagePath . DIRECTORY_SEPARATOR . '.rate-' . $key . '.php';
    $now = time();

    $fp = fopen($path, 'c+');
    if ($fp === false) {
        throw new RuntimeException('Rate limit storage is unavailable.');
    }

    try {
        if (!flock($fp, LOCK_EX)) {
            throw new RuntimeException('Rate limit lock failed.');
        }
        rewind($fp);
        $raw = stream_get_contents($fp) ?: '';
        $state = [];
        if ($raw !== '') {
            $newline = strpos($raw, "\n");
            if ($newline !== false && trim(substr($raw, 0, $newline)) === MIFP_REG_GUARD) {
                $parsed = json_decode(substr($raw, $newline + 1), true);
                $state = is_array($parsed) ? $parsed : [];
            }
        }

        $started = (int)($state['started'] ?? $now);
        $count = (int)($state['count'] ?? 0);
        if (($now - $started) >= $window) {
            $started = $now;
            $count = 0;
        }
        if ($count >= $limit) {
            throw new DomainException('Too many registration attempts. Please try again later.');
        }

        $state = ['started' => $started, 'count' => $count + 1, 'updated' => $now];
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, MIFP_REG_GUARD . "\n" . json_encode($state, JSON_UNESCAPED_SLASHES));
        fflush($fp);
        @chmod($path, 0600);
        flock($fp, LOCK_UN);
    } finally {
        fclose($fp);
    }
}

function validate_date_value(mixed $value): ?string
{
    if (!is_string($value) || $value === '') {
        return null;
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $value : null;
}

function validate_form(array $post, array $settings): array
{
    $errors = [];
    $data = [];

    $requiredText = [
        'first_name' => ['First name', 100],
        'last_name' => ['Last name', 100],
        'affiliation' => ['Affiliation / Institution / Company', 180],
        'country' => ['Country', 100],
        'address' => ['Full address', 350],
    ];

    foreach ($requiredText as $field => [$label, $max]) {
        $value = clean_text($post[$field] ?? '', $max, $field === 'address');
        if ($value === '') {
            $errors[$field] = $label . ' is required.';
        }
        $data[$field] = $value;
    }

    $email = clean_text($post['email'] ?? '', 254);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }
    $data['email'] = $email;

    $arrival = validate_date_value($post['arrival_date'] ?? null);
    $departure = validate_date_value($post['departure_date'] ?? null);
    if ($arrival === null) {
        $errors['arrival_date'] = 'Enter a valid arrival date.';
    }
    if ($departure === null) {
        $errors['departure_date'] = 'Enter a valid departure date.';
    }
    if ($arrival !== null && $departure !== null && $departure < $arrival) {
        $errors['departure_date'] = 'Departure date cannot be before arrival date.';
    }
    $data['arrival_date'] = $arrival ?? '';
    $data['departure_date'] = $departure ?? '';

    $categories = $settings['categories'];
    $registrationType = clean_text($post['registration_type'] ?? '', 60);
    if (!in_array($registrationType, array_values($categories), true)) {
        $errors['registration_type'] = 'Choose a valid registration type.';
    }
    $data['registration_type'] = $registrationType;

    $payments = $settings['payment_methods'];
    $paymentMethod = clean_text($post['payment_method'] ?? '', 60);
    if (!in_array($paymentMethod, array_values($payments), true)) {
        $errors['payment_method'] = 'Choose a valid payment method.';
    }
    $data['payment_method'] = $paymentMethod;

    $tshirtEnabled = bool_setting($settings['form']['tshirt_enabled'] ?? true, true);
    $shirt = clean_text($post['tshirt_size'] ?? '', 16);
    if ($tshirtEnabled) {
        $sizes = $settings['tshirt_sizes'] ?? [];
        if (!is_array($sizes) || !in_array($shirt, array_values($sizes), true)) {
            $errors['tshirt_size'] = 'Choose a valid T-shirt size.';
        }
    } else {
        $shirt = '';
    }
    $data['tshirt_size'] = $shirt;

    $dietary = clean_text($post['dietary_choice'] ?? '', 80);
    $choices = $settings['dietary_choices'];
    if (!in_array($dietary, array_values($choices), true)) {
        $errors['dietary_choice'] = 'Choose a valid dietary option.';
    }
    $data['dietary_choice'] = $dietary;
    $data['dietary_notes'] = clean_text($post['dietary_notes'] ?? '', 500, true);

    if (($post['privacy_acceptance'] ?? null) !== '1') {
        $errors['privacy_acceptance'] = 'You must accept the privacy notice and registration conditions.';
    }
    $data['privacy_acceptance'] = empty($errors['privacy_acceptance']);

    return [$data, $errors];
}

function validate_upload(array $files, int $maxBytes): array
{
    if (!isset($files['proof_of_payment']) || !is_array($files['proof_of_payment'])) {
        throw new DomainException('Proof of payment is required.');
    }

    $file = $files['proof_of_payment'];
    $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error !== UPLOAD_ERR_OK) {
        $messages = [
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the server upload limit.',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file is too large.',
            UPLOAD_ERR_PARTIAL => 'The file upload was incomplete. Please try again.',
            UPLOAD_ERR_NO_FILE => 'Proof of payment is required.',
        ];
        throw new DomainException($messages[$error] ?? 'The file upload failed.');
    }

    $size = (int)($file['size'] ?? 0);
    $tmp = (string)($file['tmp_name'] ?? '');
    if ($size < 1 || $size > $maxBytes) {
        throw new DomainException('Proof of payment must be smaller than the allowed upload size.');
    }
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        throw new DomainException('The uploaded file could not be verified.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file($tmp);
    $allowed = [
        'application/pdf' => ['extension' => 'pdf', 'label' => 'PDF'],
        'image/jpeg' => ['extension' => 'jpg', 'label' => 'JPEG'],
        'image/png' => ['extension' => 'png', 'label' => 'PNG'],
    ];
    if (!isset($allowed[$mime])) {
        throw new DomainException('Only PDF, JPEG and PNG files are accepted.');
    }

    if ($mime === 'application/pdf') {
        $fh = fopen($tmp, 'rb');
        $signature = $fh ? fread($fh, 5) : false;
        if (is_resource($fh)) {
            fclose($fh);
        }
        if ($signature !== '%PDF-') {
            throw new DomainException('The uploaded PDF is not valid.');
        }
    } else {
        $imageInfo = @getimagesize($tmp);
        $expectedType = $mime === 'image/jpeg' ? IMAGETYPE_JPEG : IMAGETYPE_PNG;
        if ($imageInfo === false || (int)($imageInfo[2] ?? 0) !== $expectedType) {
            throw new DomainException('The uploaded image is not valid.');
        }
    }

    return [
        'tmp_path' => $tmp,
        'mime' => $mime,
        'extension' => $allowed[$mime]['extension'],
        'label' => $allowed[$mime]['label'],
        'size' => $size,
    ];
}

function registrations_csv_path(string $storagePath): string
{
    return $storagePath . DIRECTORY_SEPARATOR . 'registrations.csv';
}

function proofs_storage_path(string $storagePath): string
{
    return $storagePath . DIRECTORY_SEPARATOR . 'proofs';
}

function ensure_registration_repository(string $storagePath): void
{
    ensure_storage($storagePath);
    $proofsPath = proofs_storage_path($storagePath);
    if (!is_dir($proofsPath) && !mkdir($proofsPath, 0700, true) && !is_dir($proofsPath)) {
        throw new RuntimeException('Proof-of-payment storage could not be created.');
    }
    @chmod($proofsPath, 0700);
    if (!is_writable($proofsPath)) {
        throw new RuntimeException('Proof-of-payment storage is not writable.');
    }
}

function store_proof_of_payment(string $storagePath, array $upload, string $receiptId): array
{
    ensure_registration_repository($storagePath);
    $safeId = preg_replace('/[^A-Z0-9-]/', '', strtoupper($receiptId)) ?: 'REGISTRATION';
    $filename = 'proof-' . $safeId . '.' . (string)$upload['extension'];
    $destination = proofs_storage_path($storagePath) . DIRECTORY_SEPARATOR . $filename;

    if (file_exists($destination)) {
        throw new RuntimeException('Proof-of-payment filename collision.');
    }
    if (!move_uploaded_file((string)$upload['tmp_path'], $destination)) {
        throw new RuntimeException('Proof of payment could not be stored.');
    }
    @chmod($destination, 0600);

    $stored = $upload;
    $stored['tmp_path'] = $destination;
    $stored['stored_filename'] = $filename;
    $stored['stored_path'] = $destination;
    return $stored;
}

function csv_safe_value(mixed $value): string
{
    $text = is_bool($value) ? ($value ? 'true' : 'false') : (string)($value ?? '');
    $text = str_replace("\0", '', $text);
    if ($text !== '' && in_array($text[0], ['=', '+', '-', '@'], true)) {
        $text = "'" . $text;
    }
    return $text;
}

function persist_submission_csv(string $storagePath, array $record): void
{
    ensure_registration_repository($storagePath);
    $path = registrations_csv_path($storagePath);
    $headers = [
        'receipt_id', 'submitted_at', 'first_name', 'last_name', 'email', 'affiliation', 'country', 'address',
        'arrival_date', 'departure_date', 'tshirt_size', 'dietary_choice', 'dietary_notes', 'registration_type',
        'payment_method', 'proof_file', 'proof_type', 'proof_size_bytes', 'privacy_accepted'
    ];

    $fp = fopen($path, 'c+b');
    if ($fp === false) {
        throw new RuntimeException('Registration CSV could not be opened.');
    }
    try {
        if (!flock($fp, LOCK_EX)) {
            throw new RuntimeException('Registration CSV lock failed.');
        }
        $stat = fstat($fp);
        $size = is_array($stat) ? (int)($stat['size'] ?? 0) : 0;
        fseek($fp, 0, SEEK_END);
        if ($size === 0) {
            fputcsv($fp, $headers);
        }
        $row = [];
        foreach ($headers as $header) {
            $row[] = csv_safe_value($record[$header] ?? '');
        }
        if (fputcsv($fp, $row) === false) {
            throw new RuntimeException('Registration CSV could not be written.');
        }
        fflush($fp);
        @chmod($path, 0600);
        flock($fp, LOCK_UN);
    } finally {
        fclose($fp);
    }
}

function make_receipt_id(): string
{
    return 'REG-' . gmdate('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
}

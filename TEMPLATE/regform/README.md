# MIFP Secure Registration Form

Self-contained PHP registration page for the conference template. It stays isolated in `regform/`, but **all configuration comes from the main `../conference.yaml`**.

## Configure

Edit only `conference.yaml`.

The public conference/contact email is read from:

```yaml
conference:
  email: TBC
```

The PHP registration behaviour is controlled under `registration.form`:

```yaml
registration:
  form:
    mode: php
    action: regform/
    submit_enabled: true
    back_url: ../registration.html
    privacy_url: ../privacy.html
    mail:
      from_name: MIFP Registration
      subject_prefix: "[MIFP]"
      send_user_confirmation: true
    backend:
      max_upload_mb: 5
      storage_path: registrations
      persist_submissions: true
      rate_limit_requests: 5
      rate_limit_window_seconds: 900
      minimum_fill_seconds: 2
      trust_proxy: true
      trusted_proxies:
        - 127.0.0.1
        - "::1"
```

For the current local test setup, `conference.email`, `mail.from_email` and the only `mail.admin_emails` recipient are `TBC`. Keep SMTP credentials out of `conference.yaml`: PHP `mail()` should use the local mail transport configured on the server.

The allowed registration categories, payment methods, T-shirt sizes and dietary choices are read directly from the options already defined in `registration.form.sections`.

## Local test

Run the conference root with PHP, not with a static-only server:

```bash
php -S 127.0.0.1:8000
```

Then open `http://127.0.0.1:8000/regform/` directly, or use any Registration CTA on the conference site.

For email delivery, PHP `mail()` must have a working local mail transport. A valid submission is saved locally before email is attempted, so an SMTP problem does not discard the registration or proof of payment.

## Security

- CSRF token with secure session cookies (`HttpOnly`, `SameSite=Strict`, `Secure` on HTTPS).
- Persistent per-IP rate limiting; forwarded IP headers are trusted only from explicitly configured proxies.
- Honeypot and minimum form-fill time.
- Server-side allowlist validation for select values and strict date/email validation.
- Proof-of-payment upload limit plus MIME/signature validation; only PDF, JPEG and PNG are accepted.
- The client filename is never used for storage or attachment naming.
- The registration is appended to `registrations/registrations.csv` and the proof is stored under `registrations/proofs/` using a server-generated name.
- Organizer notification and participant confirmation are separate messages.
- `registrations/` is denied by `.htaccess`; runtime data should not be committed to source control.
- PHP errors are logged server-side and are not rendered to visitors.
- CSP, no-sniff, referrer, frame, permissions, no-cache and no-index headers are emitted by PHP.

## Storage

The default `regform/registrations/` directory contains `registrations.csv` and the `proofs/` subdirectory. It is protected by `.htaccess` on Apache. For a production deployment, an absolute writable directory outside the document root is still preferable when the hosting layout allows it.

The CSV is append-only during normal form operation. Proof files are stored as `proof-<internal-id>.<ext>`; the original uploaded filename is never used.

## PHP

Designed for PHP 8.0+ and requires the standard `session`, `filter` and `fileinfo` extensions. If the optional PHP YAML extension is installed it is used; otherwise the form includes its own small parser for the YAML subset used by this template.

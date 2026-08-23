## 1.22.16

- Certificate stamp enlarged to 30 mm and centered beneath the right-hand signature block in preview/PDF/rendered DOCX.
- Certificate center logo enlarged to 44 × 16 mm.
- Updated the bundled filled-certificate DOCX template to match the new logo and stamp sizing/placement.
- No conference project folder is bundled; the editor continues to use only `templates/conference-template.zip` as the New conference source.

## 1.22.15

- Fixed certificate stamp rendering by decoding the selected project asset directly from its Blob, with image-element fallback. This avoids preview object-URL lifecycle issues.
- Document rendering now accepts both HTML images and ImageBitmap drawables; the same robust loader is used for rendered PDF/DOCX and filled-template media replacement.
- Simplified conference creation to one template source of truth: `templates/conference-template.zip`. The duplicate root `TEMPLATE/` directory is no longer required or shipped.
- Release package no longer includes a sample `PLMCN-2027` conference when that conference is not being modified.

## 1.22.14

- Fixed the Certificates stamp/logo image picker breaking the editor layout.
- Certificate image controls are no longer nested inside a `<label>`; buttons and the hidden select are now isolated correctly.
- Image selection opens in a dedicated, fixed modal that cannot participate in the document panel grid.
- Certificate logo/stamp controls now use a stable responsive layout.
- Keeps the PHP/Aruba compatibility fixes from 1.22.11 and later.

## 1.22.13

- Certificate signature blocks moved upward to leave more breathing room in the lower part of the page.
- Certificate stamp enlarged and moved to the bottom-right, below the right-hand signature area.
- Updated the filled certificate DOCX template to place the stamp on the right as well.

## 1.22.12

- Fixed the Badges configuration panel layout after the enhanced image picker: controls are grouped in a stable 4-column desktop grid, 2 columns on narrower screens, and 1 column on mobile.
- Bottom logo / sponsor now occupies its own full-width row instead of compressing the badge sizing controls.

## 1.22.11

- Regform compatibility widened to PHP 7.0+ as well as PHP 8.x.
- Removed numeric literal separators such as `2_000_000`, which fail on PHP 7.3 and older.
- Removed PHP 7.1-only nullable type syntax, `void` return types, and short array destructuring from the registration module.
- Keeps the Aruba-safe `.htaccess` rules from 1.22.9.
- PLMCN-2027 and the embedded conference template contain the same compatible regform implementation.

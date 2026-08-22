## 1.22.10

- Regform is now compatible with PHP 7.4 as well as PHP 8.x.
- Removed PHP 8-only `mixed` type declarations and native `str_contains()` / `str_starts_with()` dependencies from the registration module.
- Added small prefixed compatibility helpers and applied the same code to PLMCN-2027, TEMPLATE, and the embedded New conference template ZIP.

## 1.22.9

- Regform deployment compatibility fix for Aruba/shared Linux hosting.
- Replaced `Options -Indexes` / Apache authz directives with `mod_rewrite`-only protection for `settings.yaml`, `src/`, and `registrations/`.
- Applied the same regform protection to PLMCN-2027, TEMPLATE, and the embedded conference template ZIP.

## 1.22.8
- Demo People now uses explicit placeholder-style values (`First Name 01`, `Last Name 01`, `Affiliation 01`, etc.) instead of realistic personal names.
- Home Speakers now shows a themed TBC card whenever there are no visible Invited Speaker rows, matching the full Speakers page fallback.
- Updated PLMCN-2027 and TEMPLATE site asset cache versions to 1.6.8.

## 1.22.7
- People editor: Demo People now loads a normal editable dataset. Save People / Save all persists it to `people.csv`; until the first save, Restore previous People returns to the prior list.
- Export ZIP and site preview use the currently loaded People rows, including unsaved demo rows.
- Updated the conference UI Kit to document current states and components, including TBC people state and media galleries.

## 1.22.6
- Speakers page: polished TBC state when no configured people are published.
- People editor: non-destructive Demo People mode for realistic site previews; demo rows are never saved to people.csv or exported in conference ZIPs.

## 1.22.5
- Reworked image editing throughout the editor: large current preview, clear Choose/Upload/Replace/View/Remove actions, advanced path hidden by default, searchable visual asset picker, and clearer missing-image state.
- Image galleries now open as image collections with thumbnails in item headers and a dedicated “Add image” action.
- Badge/certificate logo and stamp selectors now use the same visual image workflow instead of plain selects.

## 1.22.4

- Regform theme contrast audit: fixed visited CTA colors, dark-theme accent text contrast, focus states, and cache-busting for regform assets.

## 1.22.3

- Fixed the registration form bootstrap when optional fields/sections are removed from `regform/settings.yaml` (for example the PLMCN-2027 dietary section).
- Registration fields are now rendered and validated from the actual `regform/settings.yaml` sections instead of a stale hard-coded field list.
- The registration page inherits the conference `appearance` theme/palette and uses the conference branding assets while retaining the dedicated secure PHP backend.
- Updated both `PLMCN-2027/regform` and `TEMPLATE/regform`, including the embedded New conference template ZIP.

## 1.22.2
- Rebuilt Content Editor → Program → download as a dedicated compact editor.
- Download mode is now a two-choice select: generated from program.csv or local PDF; visibility is controlled separately by Enabled.
- Contextual local/generated filename controls and a cleaner PDF asset picker/upload workflow.

## 1.22.1

- Fixed New/Add, Delete, Duplicate and move controls for arrays inside the dedicated Regform settings page.
- Regform array mutations now re-render the Regform page instead of the Content Editor.
- Adding an item after deleting all sections/fields now creates a valid structured section/field template.
- Regform asset chooser/upload refreshes the correct page.
- Added explicit diagnostic logging for failed array mutations.

## 1.22.0

- Regform is now a dedicated sidebar page.
- Badge title uses the full conference identifier (for example PLMCN-2027).
- Added dummy test-page exports for badges and certificates.
- Empty document selection is treated as validation, not an export failure.

# MIFP Conference Editor

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![Vanilla JS](https://img.shields.io/badge/stack-HTML%20%2F%20CSS%20%2F%20JavaScript-111827.svg)](#architecture)
[![Offline first](https://img.shields.io/badge/workflow-offline--first-7f1d1d.svg)](#how-it-works)

A local, offline-first editor for building and maintaining reusable conference websites from ordinary files.

The editor is designed around a simple idea: **conference data should remain portable, inspectable and deployable without a CMS or application backend**. Conference content lives in YAML/CSV files and local assets; the editor provides a structured UI on top of them.

> **Repository scope:** this repository contains the editor, printable document templates and the placeholder-only `TEMPLATE/` scaffold. Real conference instances are intentionally kept outside version control.

## Live editor

Once GitHub Pages is enabled for this repository, the editor is available at:

**https://matginesi.github.io/mifp-conference-editor/**

The hosted editor can open and edit a local workspace in a Chromium-based browser. **Preview site** builds an in-memory preview from the selected local conference, so ignored conference folders do not need to be published to GitHub Pages. PHP registration testing still requires a PHP-capable local/server environment.

## Features

### Conference content

- Page-first visual editor for public `conference.yaml` plus a dedicated Registration Form editor for `regform/settings.yaml`.
- Raw YAML editor for advanced changes.
- Important dates editor.
- Local asset browser and replacement tools.
- Editorial **TBD / TBC / TBA / TODO** checker with navigation back to unresolved content.
- Conference versioning and ZIP export.
- **New conference** creates a clean conference from the versioned `TEMPLATE/` / bundled scaffold: no old conference text, empty People, empty Program, placeholder dates/venue/content and no copied registration submissions or secrets.

### People

- Spreadsheet-style People editor.
- CSV and XLSX import/export.
- Multi-file Excel/CSV import with merge or replace workflows.
- Exact duplicate cleanup and similar-name review.
- Multi-role normalization while preserving meaningful committee roles.
- Face-image association through `assets/people/`.
- Portable **People bundle ZIP** containing CSV/XLSX data, manifest and the conference face-image set from `assets/people/` (plus any explicitly referenced image paths).
- Bundle re-import through the same merge/deduplication workflow.

### Program

- Flat, spreadsheet-style conference program.
- CSV and XLSX import/export.
- Flexible schedule rows without graph IDs or parent/child editing.
- Public Program PDF source is explicitly selectable in the Program editor:
  - **Generate from `program.csv`** using the browser PDF generator;
  - **Use uploaded local PDF** from `assets/documents/`;
  - **Hide PDF download** entirely.

### Badges and certificates

- A4 PDF generation in the browser.
- DOCX generation and bundled editable DOCX reference templates.
- Batch export from an explicit People selection.
- Badge layout with **First Name → Last Name**.
- Certificate presentation title/type support.
- Configurable signatures, central logo and optional stamp.

### Public conference website workflow

The editor works with conference folders built from normal static files. A typical conference project contains:

```text
CONFERENCE-2027/
├── conference.yaml
├── conference.version.json
├── data/
│   ├── people.csv
│   └── program.csv
├── assets/
│   ├── branding/
│   ├── documents/
│   ├── people/
│   └── venue/
├── css/
├── js/
├── regform/
│   ├── settings.yaml # isolated form/mail/backend settings
│   └── ...           # optional PHP registration module
└── *.html
```

Conference instances are deliberately not committed to this repository. The default `.gitignore` excludes top-level conference directories whose names end in a year, for example `PLMCN-2027/` or `ICP2DC-2028/`.

## How it works

The editor is intentionally file-based and does not maintain its own database.

- `conference.yaml` is the source of public conference/site configuration.
- `regform/settings.yaml` is the separate source of PHP form, email, upload and rate-limit settings.
- `data/people.csv` stores people and roles.
- `data/program.csv` stores the program.
- conference assets remain ordinary local files.
- the browser reads and writes the selected workspace directly when the File System Access API is available.

This keeps the generated conference portable: the public website can be deployed separately from the editor and does not depend on the editor at runtime.

## Run locally

Clone the repository:

```bash
git clone https://github.com/matginesi/mifp-conference-editor.git
cd mifp-conference-editor
```

The editor can be opened directly from `index.html`, but using a small local HTTP server is recommended:

```bash
php -S 127.0.0.1:8000
```

Then open:

```text
http://127.0.0.1:8000/
```

A static server is sufficient for the editor itself. PHP is useful when testing a conference's optional `regform/` locally.

### Browser support

For direct folder read/write, use a recent **Chromium-based browser** (Chrome, Chromium, Edge, Brave, etc.) with File System Access API support.

The editor also provides a folder-import fallback, but direct write-back capabilities can differ between browsers.

## Local workspace

A workspace is simply a directory containing one or more conference folders.

The editor discovers the available projects after opening that directory. Conference folders remain local and can be deployed or archived independently.

A workspace may also be empty: after opening it, **New conference** can create the first placeholder project. The generated project contains the complete static/PHP scaffold but starts with `TBC`/placeholder editorial content, empty `people.csv`, empty `program.csv`, generic placeholder artwork, and clean registration storage. Use **Content Check** to walk through unresolved values before publishing.

`config/workspace.json` contains editor-level workspace settings. `TEMPLATE/` is the canonical placeholder-only conference scaffold and is intentionally versioned. A compressed copy is also shipped as `templates/conference-template.zip`, allowing **New conference** to work even when the selected local workspace does not already contain a template folder.

## Architecture

No framework and no build pipeline are required.

```text
mifp-conference-editor/
├── assets/
│   ├── css/
│   └── js/
│       ├── editor/
│       └── lib/
├── config/
│   └── workspace.json
├── TEMPLATE/                    # placeholder-only conference scaffold
├── templates/
│   ├── badges_template.docx
│   ├── certificate_template.docx
│   └── conference-template.zip # bundled New conference scaffold
├── index.html
└── README.md
```

The codebase uses:

- vanilla HTML;
- vanilla CSS;
- vanilla JavaScript;
- browser File System Access APIs;
- lightweight local YAML, CSV, XLSX, ZIP and document utilities.

There is no Node.js runtime, npm dependency tree, analytics SDK or tracking layer required by the editor.

## GitHub Pages

This repository can publish the editor itself directly from the `main` branch root. No build step is required.

After enabling GitHub Pages with `main / (root)` as the source, the editor will be available at:

```text
https://matginesi.github.io/mifp-conference-editor/
```

GitHub Pages hosts only the editor UI. Conference instances remain local and excluded from the repository. The editor preview uses local files through an in-memory virtual preview, so a URL such as `/mifp-conference-editor/PLMCN-2027/` is not required. PHP registration testing still needs a PHP server.

## Privacy and data

The editor is designed to work locally with files selected by the user.

- No analytics are required.
- No advertising or tracking services are required.
- People data and conference assets do not need to be uploaded to this repository.
- Real conference folders are ignored by Git by default.

Always review conference content before publishing, especially personal data, registration exports, photos and third-party assets.

## License

The editor source code is released under the [MIT License](LICENSE).

Copyright © 2026 Matteo Ginesi.

The MIT license applies to the software source code. MIFP names, logos, trademarks, conference artwork and third-party assets may be subject to separate rights and are not automatically relicensed by the software license.

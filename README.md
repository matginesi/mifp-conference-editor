# MIFP Conference Editor

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![Vanilla JS](https://img.shields.io/badge/stack-HTML%20%2F%20CSS%20%2F%20JavaScript-111827.svg)](#architecture)
[![Offline first](https://img.shields.io/badge/workflow-offline--first-7f1d1d.svg)](#how-it-works)

A local, offline-first editor for building and maintaining reusable conference websites from ordinary files.

The editor is designed around a simple idea: **conference data should remain portable, inspectable and deployable without a CMS or application backend**. Conference content lives in YAML/CSV files and local assets; the editor provides a structured UI on top of them.

> **Repository scope:** this repository contains the editor and reusable document templates. Real conference instances are intentionally kept outside version control.

## Live editor

Once GitHub Pages is enabled for this repository, the editor is available at:

**https://matginesi.github.io/mifp-conference-editor/**

The hosted editor can be used to open a local workspace in a Chromium-based browser. For full local preview of a conference website, DOCX template filling and PHP registration testing, run the editor from a local HTTP server as described below.

## Features

### Conference content

- Page-first visual editor for `conference.yaml`.
- Raw YAML editor for advanced changes.
- Important dates editor.
- Local asset browser and replacement tools.
- Editorial **TBD / TBC / TBA / TODO** checker with navigation back to unresolved content.
- Conference versioning and ZIP export.
- **New conference** workflow that creates a placeholder conference from an available workspace scaffold.

### People

- Spreadsheet-style People editor.
- CSV and XLSX import/export.
- Multi-file Excel/CSV import with merge or replace workflows.
- Exact duplicate cleanup and similar-name review.
- Multi-role normalization while preserving meaningful committee roles.
- Face-image association through `assets/people/`.
- Portable **People bundle ZIP** containing people data, manifest and referenced face images.
- Bundle re-import through the same merge/deduplication workflow.

### Program

- Flat, spreadsheet-style conference program.
- CSV and XLSX import/export.
- Flexible schedule rows without graph IDs or parent/child editing.
- Public Program PDF can be:
  - generated from `program.csv`,
  - provided as a local uploaded PDF,
  - or hidden.

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
├── regform/          # optional PHP registration form
└── *.html
```

Conference instances are deliberately not committed to this repository. The default `.gitignore` excludes top-level conference directories whose names end in a year, for example `PLMCN-2027/` or `ICP2DC-2028/`.

## How it works

The editor is intentionally file-based and does not maintain its own database.

- `conference.yaml` is the main source of conference/site configuration.
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

`config/workspace.json` contains editor-level workspace settings. The preferred reusable scaffold directory name is `TEMPLATE`.

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
├── templates/
│   ├── badges_template.docx
│   └── certificate_template.docx
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

GitHub Pages is useful for hosting the editor UI. Conference instances remain local and excluded from this repository. Full local conference preview and PHP registration testing should still be done with a local server.

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

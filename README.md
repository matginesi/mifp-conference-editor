# MIFP Conference Editor v1.17.1

Local, offline-first conference editor. Vanilla HTML/CSS/JS, no framework, CDN, analytics or tracking.

## Run

The editor itself can be opened from `index.html`. Direct read/write of conference folders works in Chromium-based browsers through the File System Access API.

For **Preview site** and the optional **filled legacy DOCX template batch** use a tiny local HTTP server because browsers block local `fetch()` from `file://`:

```bash
php -S 127.0.0.1:8000
```

Then open `http://127.0.0.1:8000/`.

The A4 Badge/Certificate PDF and DOCX generators do not require a backend.







## v1.17.1 changes

- Fixed committee-role semantics for historical People spreadsheets. A plain `Committee` still maps to **Program Committee**, but `Chairman` now establishes **Organizing Committee** membership, while `Program Committee + Chairman` becomes **Program Committee Chairman**.
- `Organizer` / `Local Organizer` remain local-organization roles; when a row combines generic `Committee` with a local-organizer role, the generic committee token no longer incorrectly duplicates that person in Program Committee.
- Added support for **Co-chairman**, **Program Committee Chairman** and **Program Committee Co-chairman** as distinct role labels. The public Committee cards show the specific title instead of flattening every member to `Program Committee`.
- Public Committee group matching is backward compatible with older rows that contain `Chairman` without an explicit `Organizing Committee` token.
- Bumped the public site cache key to 1.6.5 so the corrected committee renderer is loaded immediately.

## v1.17.0 changes

- Added a dedicated **Program PDF** control to the Program editor: the public download can be generated from `program.csv`, point to an uploaded local PDF, or be hidden. Local PDFs can be uploaded directly into `assets/documents/`.
- Added **Content Check**, an editor section that finds TBC/TBD/TBA/TODO/placeholder values across `conference.yaml`, People and Program, with filters plus Previous/Next/Open navigation back to the field or spreadsheet row.
- **New conference** now creates a clean placeholder conference from the workspace scaffold, resets People and Program to empty tables, resets version metadata, removes copied registration runtime data, and sets the core conference facts to placeholders.
- Added a portable **People bundle ZIP** export/import containing `people.csv`, `people.xlsx`, a manifest and all face images referenced by People. Bundle import uses the same merge/deduplication/similar-person review flow as multi-Excel import and restores face assets.
- Committee terminology is canonicalized on **Program Committee**: legacy `Committee Members`, `Program Committee Member(s)` and misspellings normalize to the same role, and the public Committee renderer no longer repeats a second `Program Committee Members` list when that role is already a featured group.
- Retains the v1.16.3 responsive editor, multi-Excel People merge assistant, historical badge/certificate styling, terminal registration success view, and full-width horizontal committee-member grids.

## v1.16.3 changes

- Finished the editor-wide mobile responsive pass: navigation, workspaces, forms, YAML/settings, People, Program, assets, dates, document tools, totems and modals now reflow cleanly on narrow screens while data tables retain controlled horizontal scrolling.
- People import supports selecting multiple Excel/CSV files in one operation, with an explicit merge/replace workflow, exact duplicate cleanup, conflict policy, union of roles and a review list for similar-name possible duplicates before applying changes.
- Badge and certificate templates follow the historical MIFP visual hierarchy more closely while retaining the modern configurable document controls: FIRST NAME then LAST NAME on badges; 20 mm certificate margins; presentation title support; improved signatures; selectable lower central logo and optional bottom-left stamp.
- Registration success is terminal for the current submission view: after a successful request the confirmation summary is shown and the registration form/guidance are not rendered again.
- Committee groups on the public conference page are full-width sections whose members flow horizontally over multiple columns.

## v1.15.0 changes

- Badges now print **FIRST NAME** before **LAST NAME** in both canvas export and the bundled DOCX template.
- Badges & Certificates use an automatic, stable preview; People selection is now explicitly **for export only**.
- Document previews are debounced and keep the previous canvas visible while updating, eliminating the typing flicker/layout jumping.
- Certificate page uses a 20 mm white border, improved Noto typography, higher signature blocks, signature rules below the printed identity, a selectable logo below all signatures, and an optional bottom-left stamp.
- Added **Clear People** with destructive confirmation while preserving headers and role definitions.
- Replaced browser `confirm()` / `prompt()` dialogs with a shared in-editor confirmation/input totem.
- Export person picker now has search, Select all / None and a clear selected count.

## v1.14.4 changes

- Certificate signature area redesigned as a true one/two-column signing grid.
- Every signer now reads vertically as **Name Surname**, **Affiliation**, optional smaller title, with a real signature line/space above and clear breathing room before the next signer row.
- The bundled certificate DOCX now exposes up to four signature blocks in the same two-column / two-row structure (`DOC_SIGN_1_*` … `DOC_SIGN_4_*`).

## v1.14.3 changes

- People Excel import now imports every named row regardless of `Response`; no confirmed/unconfirmed filtering.
- `Role` is imported for every person and common role spellings are normalized without discarding multi-role assignments.
- `Abstract Title`, `Talk Title`, `Presentation Title`, and related aliases map to `Presentation Title`; presentation type/format aliases map to `Presentation Type`.
- Certificates use the title stored on the person first, then fall back to matching the Program table; presentation type is inferred from Role when not explicitly supplied.
- People table shows presentation/custom imported columns instead of silently hiding them.
- Dark-theme select/Role picker contrast is explicitly styled, including native dropdown options.

## v1.14.2 changes

- People Excel import now understands the usual Google Sheets participant export (`Surname` + `Name`, `Response`, `Role`, affiliation/country columns).
- If a response/confirmation column contains explicit YES values, only confirmed rows are imported into the public People dataset; administrative/private columns such as email, response, invitation and abstract tracking are not copied into `people.csv`.
- Common role labels and typos are normalized (for example Invited → Invited Speaker, Oral → Oral Speaker, Poster → Poster Presenter, Committee → Program Committee).
- XLSX import ignores trailing styled-but-empty spreadsheet columns.
- People and Program remain spreadsheet tables at all viewport widths, using horizontal scrolling instead of switching to cards.
- Conference page navigation no longer forces no-store reloads; the loading overlay is delayed to avoid the heavy flash on normal page changes, and same-origin document transitions are enabled when supported.

## v1.14.1 changes

- Fixed `project.load_failed: Unexpected indentation at line 12`: `conference.yaml` is now serialized with the editor's own YAML serializer, so every scalar is valid for `yaml-lite.js` and long strings are never emitted as unsupported wrapped quoted scalars.
- Revalidated the complete PLMCN-2027 configuration with both the editor and site YAML parsers.
- The exact user-supplied panoramic Belgrade image is now the PLMCN-2027 hero; Hotel Majestic photography remains in Venue/Accommodation galleries.
- Added an editor-level `favicon.ico` so `http://127.0.0.1:8000/favicon.ico` no longer returns 404.

## v1.14.0 changes

- Certificate signature blocks are now fully configurable: **title, name and affiliation/second line** are entered directly in the Certificates panel, independently of People roles.
- Certificate signatures can render **one per row or two side by side**, and additional signature blocks can be added, reordered or removed.
- The generated certificate renderer uses the configurable signature grid; the editable DOCX template supports the first four signature blocks as two columns with two stacked rows.
- The PLMCN-2027 hero uses the dedicated panoramic Belgrade artwork; the user-supplied Hotel Majestic photograph is used in venue/accommodation imagery. Venue, accommodation, school and social galleries now use multiple real photographs of **Hotel Majestic and Belgrade** stored locally in the project.
- Image credits were expanded for the bundled Wikimedia Commons photographs; the user-supplied hero is explicitly marked for rights verification before deployment.
- Privacy and cookie copy was rewritten to match the actual implementation: static public pages, no analytics/advertising/marketing trackers, a strictly necessary PHP session cookie for registration security/CSRF, and local browser preferences for notice/theme settings.
- Registration privacy acknowledgement now links clearly to the **Privacy & Cookies Policy** and separately accepts the registration conditions.
- Filled certificate template exports support up to four manually configured signature blocks, keeping titles, names and affiliations editable rather than tied to fixed chairman roles.

## v1.13.8 changes

- Badge export actions are compact again on narrow screens.
- Badges can include any number of blank name slots while retaining the conference artwork.
- Badge preview supports page navigation and exports can target all pages or only the current A4 page.
- Badge artwork and filled badge templates no longer include the hotel/venue; they use city/country plus conference dates.

## v1.13.7 changes

- Certificate export buttons now sit below the certificate preview, matching the badge panel layout.
- People and Program tables use content-weighted column widths instead of near-uniform columns.
- ResizeObserver-based table/card switching reacts to the actual editor panel width (People < 1360px, Program < 1560px).
- Card layouts were refined for two-column editing where useful and a clean single-column mobile fallback.

- Reworked the spreadsheet foundation instead of relying on large minimum column widths: People and Program now use a bounded fixed-layout table on genuinely wide panels and switch to editable cards before columns become cramped.
- Removed the `max-content`/large input minimum widths that could make the editor wider than the viewport.
- Raw YAML and per-section YAML now soft-wrap long lines inside the textarea and never create page-level horizontal overflow.
- Badges & Certificates panels use responsive auto-fit sizing; participant lists use fewer, wider columns.
- Document previews are sized by the actual canvas aspect ratio, so A4 landscape badge sheets and A4 portrait certificates are shown whole rather than clipped by a fixed-height viewport.
- Badge location/date and role text now shrink-to-fit within each badge.

## v1.13.5 changes

- People and Program editors now switch from spreadsheet tables to responsive editable cards based on the editor container width, not only the viewport.
- Program rows expose field labels in card mode and keep all controls editable without horizontal page overflow.
- Badge and certificate participant selection, configuration controls and previews are responsive on tablet/mobile.
- Badge/certificate canvases preserve their intrinsic page aspect ratio in inline and enlarged previews.
- Raw YAML and per-section YAML editors are constrained to their panels and scroll internally instead of widening the application.
- editor version 1.13.5.

## v1.13.4 changes

- unified compact control system across the whole editor: inputs, selects, buttons, textareas, checkboxes and spreadsheet cells now share the same sizing, spacing and focus treatment;
- desktop controls are denser without reducing text legibility; mobile controls remain touch-friendly without becoming oversized;
- Badges and Certificates now have a larger inline preview plus a clickable **Open preview** modal before export;
- Badge-sheet and certificate previews now always preserve the rendered page aspect ratio at desktop, tablet and mobile sizes;
- A7/A6 badge artwork redesigned as a clean borderless portrait badge; optional printing guides are corner crop marks rather than a visible rectangle around each badge;
- improved badge typography and information hierarchy for conference, participant, affiliation, country and role;
- certificate typography redesigned with an editorial serif/sans hierarchy, a smaller MIFP/contact header and a lighter double frame;
- generated certificate keeps the supplied MIFP structure but gives substantially more visual priority to participant/conference content;
- bundled DOCX reference templates updated to match the refined badge/certificate direction; badge remains A7 portrait and borderless;
- A4 PDF/DOCX exports and filled-template DOCX exports remain available;
- editor version 1.13.4.
- enlarged responsive MIFP logo in the public site footer while preserving its aspect ratio.
- People editor now has a permanent **Roles** section with add, rename, delete, usage counts and YAML synchronization; role renames also keep people-group mappings aligned.
- People stays a spreadsheet on wide screens and becomes editable person cards on tablet/mobile instead of forcing a very wide fixed table.
- Home hero now uses **PLMCN-2027** as the main title and shows the full conference name directly below it.
- Public-site first-paint colors now match the default Graphite theme, removing the white flash when navigating with a dark theme.

## DOCX template placeholders

### Badges

- `DOC_CONF_SHORT`
- `DOC_LOCATION`
- `DOC_DATE`
- `DOC_SURNAME`
- `DOC_NAME`
- `AFFILIATION_`
- `COUNTRY_`

### Certificates

- `DOC_SURNAME`
- `DOC_NAME`
- `DOC_CONF_FULL`
- `DOC_CONF_SHORT`
- `DOC_PRESENTED_PREFIX`
- `DOC_PRESENTATION_TYPE`
- `DOC_ABSTRACT_TITLE`
- `DOC_LOCATION`
- `DOC_DATE_RANGE`
- `DOC_ORGANIZER_ADDRESS`
- `DOC_PHONE`
- `DOC_EMAIL`
- `DOC_SIGN_1_TITLE`
- `DOC_SIGN_1_NAME`
- `DOC_SIGN_1_AFF`
- `DOC_SIGN_2_TITLE`
- `DOC_SIGN_2_NAME`
- `DOC_SIGN_2_AFF`

Legacy `DOC_CHAIR_LEFT` / `DOC_CHAIR_RIGHT` replacements remain supported for older custom templates, but the bundled certificate template now uses the generic `DOC_SIGN_*` fields.

The built-in templates live under `templates/` and can be edited/replaced manually as long as the placeholder names are preserved.

## Important Dates

The dedicated editor works directly on:

```yaml
important_dates:
  enabled: true
  label: Timeline
  title: Important Dates
  items:
    - date: 23 November 2026 · TBC
      description: Abstract submission opens · provisional
```

The date itself uses a browser date picker. The suffix after `·` is exposed as the optional **Note** field so existing values such as `TBC` are not lost.


## v1.12.1 asset-path fix

The editor now normalizes HTTP directory URLs before loading local assets, so opening it from a workspace subdirectory cannot make `assets/js/...` resolve one level too high. CSS/JS references are explicitly relative (`./assets/...`). The release ZIP is also packaged with the editor files at archive root, so it can be extracted directly into the workspace root.

## Page-first Content Editor

The structured editor is now organized by website page rather than by raw top-level YAML keys. Select **Home**, **Program**, **Speakers & People**, **Venue**, **Accommodation**, **Social Program**, **Travel**, **Registration**, **Privacy**, **Global site**, or **Technical**. Each page shows all of its editable sections as separate cards; every visual section editor is immediately followed by the raw YAML for that exact section, with local Apply/Reset controls. The full Raw YAML view remains available as the advanced whole-file escape hatch.

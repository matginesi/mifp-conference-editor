# MIFP Conference Editor

A local, browser-based editor for creating and maintaining MIFP conference websites.

It is designed to keep the conference workflow simple: edit the website content, manage people and the program, organize assets, configure the registration form, generate badges and certificates, preview the result, and export the complete conference as a ZIP.

**No npm, Node.js, build step, database, or editor backend is required.**

> The easiest setup is: download the project as a ZIP, extract it, start a small local web server, and open it in Chrome/Chromium/Edge.

## What you can do

- Create a new conference from the bundled template.
- Open and edit an existing conference folder.
- Edit `conference.yaml` through a visual editor or directly as YAML.
- Manage conference pages, important dates, venue, travel, accommodation and social program.
- Configure the separate registration form.
- Manage People and Program data with CSV/Excel import and export.
- Manage images and other conference assets.
- Preview the complete conference website.
- Generate badges and certificates as PDF/DOCX.
- Run content checks before publishing.
- Export the complete conference as a versioned ZIP.

---

# Quick start — without Git

This is the recommended method for non-technical users.

## 1. Download the project

On the GitHub repository page:

1. Click the green **Code** button.
2. Click **Download ZIP**.
3. Extract the downloaded ZIP somewhere on your computer.
4. Open the extracted `mifp-conference-editor` folder.

Repository:

<https://github.com/matginesi/mifp-conference-editor>

## 2. Start the editor locally

Do **not** simply double-click `index.html`.

Some functions need the editor to be opened through a small local HTTP server. Python is enough and no additional package is required.

Open a terminal inside the `mifp-conference-editor` folder and run:

### Linux / macOS

```bash
python3 -m http.server 8000
```

### Windows

```powershell
py -m http.server 8000
```

If `py` is not available, try:

```powershell
python -m http.server 8000
```

Then open this address in your browser:

<http://localhost:8000/>

Keep the terminal window open while you use the editor.

To stop the local server, return to the terminal and press **Ctrl+C**.

## 3. Use Chrome, Chromium, or Edge

For the complete experience, use a Chromium-based browser such as:

- Google Chrome
- Chromium
- Microsoft Edge

These browsers support direct read/write access to local folders after you explicitly grant permission.

Other browsers may still open an existing conference in fallback mode, but some operations — especially creating a new conference and saving directly into its folder — may not be available. In fallback mode you can still export the updated conference as a ZIP.

---

# First conference

Once the editor is open:

1. Click **New conference**.
2. Choose the folder where you want the new conference to be created.
3. Enter a conference folder/acronym, for example `ICP2DC-2028`.
4. Allow the browser to access the selected folder when asked.
5. Edit the conference from the sections in the left sidebar.
6. Add or import People and Program data.
7. Add the required images and documents in **Assets**.
8. Check the website with **Content Check** and **Preview site**.
9. Click **Save all**.
10. Use **Export ZIP** when you want a complete portable copy ready for publication or backup.

A newly created conference starts from the bundled placeholder template, so it does not copy people, program, dates, or private data from another event.

---

# Open an existing conference

To continue working on an existing conference:

1. Start the editor as described above.
2. Click **Open conference**.
3. Select either:
   - the conference folder itself, containing `conference.yaml`; or
   - a parent folder containing one or more conference folders.
4. If more than one conference is found, choose the one you want to edit.
5. Make your changes and click **Save all**.

When full local-folder access is available, changes are written directly to the conference files.

If the editor says that changes are **staged for ZIP export**, your browser is using fallback mode. Export the conference ZIP to save the modified version.

---

# Alternative installation — Git clone

If you already use Git, clone the repository instead of downloading the ZIP:

```bash
git clone https://github.com/matginesi/mifp-conference-editor.git
cd mifp-conference-editor
python3 -m http.server 8000
```

Then open:

<http://localhost:8000/>

To update an existing clone later:

```bash
git pull
```

---

# Main conference files

You normally do not need to edit these files by hand because the editor manages them for you, but it is useful to know what they contain.

| File / folder | Purpose |
| --- | --- |
| `conference.yaml` | Main conference content and website configuration |
| `data/people.csv` | Speakers, committee members, organizers and other people |
| `data/program.csv` | Conference program |
| `assets/` | Images, logos, documents and other public assets |
| `regform/settings.yaml` | Registration form configuration |
| `conference.version.json` | Conference version and release metadata |

The editor itself uses `templates/conference-template.zip` as the source for **New conference**.

---

# Typical workflow

A simple workflow is:

**Create/Open conference → edit content → add assets → edit People → edit Program → check → preview → save → export ZIP**

The ZIP export is also useful as a backup before making major changes.

---

# Publishing a conference

The editor is only the tool used to prepare the conference files. It does not need to be installed on the public web server.

When the conference is ready:

1. Export the complete conference ZIP.
2. Extract it if necessary.
3. Upload the conference folder to your web hosting.

The conference website itself is mostly file-based. The registration module is separate and uses PHP, so PHP must be available on the hosting server if the registration form is enabled.

Always test the public website and registration form after deployment.

---

# Common problems

## `New conference` does not work

Make sure you did not open the editor using a `file://` address by double-clicking `index.html`.

Start it with:

```bash
python3 -m http.server 8000
```

and open <http://localhost:8000/>.

Also use Chrome, Chromium, or Edge for direct folder write access.

## The browser does not let me save into the folder

The editor needs permission to write to the conference folder. Re-open the conference and allow folder access when the browser asks.

If your browser does not support direct folder access, use **Export ZIP** instead.

## `No conference.yaml found`

You selected the wrong folder. Select the conference folder that contains `conference.yaml`, or its direct parent folder.

## Port 8000 is already in use

Use another port, for example:

```bash
python3 -m http.server 8001
```

Then open:

<http://localhost:8001/>

## Changes are shown as `staged for ZIP export`

The editor is running in fallback mode rather than direct filesystem mode. Export the ZIP to obtain the modified files, or reopen the editor in Chrome/Chromium/Edge and grant folder access.

---

# Local-first design

The editor runs in the browser and does not require an application backend. Conference files are read from and written to the folders you explicitly select, or kept in the browser session until you export them in fallback mode.

This makes the editor easy to archive, move between computers, and use without installing a development stack.

---

# Requirements

For normal use:

- A modern desktop browser.
- **Chrome, Chromium, or Edge recommended.**
- Python 3 only for the simple local web server.

You do **not** need:

- Node.js
- npm
- Docker
- a database
- a build command
- a backend server for the editor

---

# License

This project is released under the [MIT License](LICENSE).

Copyright © 2026 Matteo Ginesi.

---

# Current editor version

**1.22.16**

Recent changes include improved certificate logo/stamp placement and sizing, more robust certificate image loading, stable badge/certificate asset pickers, responsive badge controls, and PHP compatibility fixes for the registration form.

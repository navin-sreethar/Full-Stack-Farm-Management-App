# GitHub Upload Guide

This guide explains what files are intentionally excluded from GitHub,
why they are excluded, and how to set up the project after cloning.

---

## What Is NOT on GitHub (and Why)

These files exist on the real server but are blocked by `.gitignore`
and will never appear in the public repository.

### `config/secrets.php`
- **What it contains:** The live database password
- **Why excluded:** This is the most sensitive file in the project. If this
  password were public, anyone could connect directly to the production database.
- **What to do:** Copy `config/secrets.example.php` → `config/secrets.php`
  and fill in the real password.

### `config/database.php`
- **What it contains:** The production database hostname, database name,
  username, and a reference to `secrets.php` for the password.
  Also contains local MAMP development credentials.
- **Why excluded:** Exposing the production host and username makes it
  easier for attackers to target the database even without the password.
- **What to do:** Copy `config/database.example.php` → `config/database.php`
  and fill in your credentials.

### `config/mail.php`
- **What it contains:** The SMTP email address and Gmail App Password used
  to send automated reports and account notifications.
- **Why excluded:** A Gmail App Password gives full send-access to the
  email account. Anyone with it could send emails impersonating the organization.
- **What to do:** Copy `config/mail.example.php` → `config/mail.php`
  and fill in the real email and app password.

### `logs/`
- **What it contains:** Daily application log files (errors, user actions, etc.)
- **Why excluded:** Logs can contain user data, IP addresses, error stack
  traces, and internal system paths — all useful to an attacker.

### `uploads/` and `order form images/`
- **What it contains:** Files uploaded by real customers (order photos, etc.)
- **Why excluded:** This is private customer data and should never be
  stored in a public code repository.

### `backup_db.php`
- **Why excluded:** This script has database credentials hard-coded for
  the production environment and is a server-only maintenance tool.

### `*.pdf` and `*.docx`
- **Why excluded:** Any personal or company documents accidentally placed
  in the project folder are blocked as a safety net.

### `.DS_Store`
- **What it is:** A hidden macOS metadata file created automatically by Finder.
- **Why excluded:** Contains local folder structure info and is meaningless
  to anyone else.

---

## Setting Up After Cloning

When someone clones this repo for the first time, they need to
create the three config files from the provided examples:

```bash
# 1. Copy example files to real config files
cp config/secrets.example.php     config/secrets.php
cp config/database.example.php    config/database.php
cp config/mail.example.php        config/mail.php

# 2. Open each file and fill in the real credentials
#    (database password, SMTP details, etc.)
```

Then follow the setup steps in `README.md` to import the database
and start the server.

---

## Step-by-Step: How to Push This Project to GitHub

Follow these steps in order. Do this once to set up the repository.

### Step 1 — Create a new repository on GitHub

1. Go to [github.com](https://github.com) and log in
2. Click the **+** button (top right) → **New repository**
3. Give it a name, e.g. `farm-app`
4. Set it to **Public** or **Private** (your choice)
5. **Do NOT** check "Add a README file" — you already have one
6. Click **Create repository**
7. GitHub will show you a page with a URL like:
   `https://github.com/YOUR_USERNAME/farm-app.git`
   **Copy that URL** — you'll need it in Step 4.

### Step 2 — Open Terminal

On Mac: press `Cmd + Space`, type `Terminal`, press Enter.

Then navigate to the project folder:

```bash
cd "/Users/navin/Desktop/farm app"
```

### Step 3 — Initialize Git and stage files

```bash
git init
git add .
```

### Step 4 — Double-check what will be uploaded

Run this command and review the list carefully:

```bash
git status
```

✅ You SHOULD see files like:
- `index.php`, `README.md`, `GITHUB_SETUP.md`, `.gitignore`
- `controllers/`, `models/`, `views/`, `core/`, `api/`
- `config/database.example.php`, `config/mail.example.php`, `config/secrets.example.php`
- `database/`, `docker/`, `public/`

🚫 You should NOT see any of these — if you do, stop and ask for help:
- `config/secrets.php`
- `config/mail.php`
- `config/database.php`
- Any `.pdf` or `.docx` files
- Anything inside `logs/` or `uploads/`

### Step 5 — Make your first commit

```bash
git commit -m "Initial public release of Farm App"
```

### Step 6 — Connect to GitHub and push

Replace the URL below with the one you copied from Step 1:

```bash
git branch -M main
git remote add origin https://github.com/YOUR_USERNAME/farm-app.git
git push -u origin main
```

It will ask for your GitHub username and password.
> **Note:** GitHub no longer accepts your account password here.
> You need a **Personal Access Token** instead. See the next section.

### Step 7 — If it asks for a password (Personal Access Token)

GitHub requires a token instead of your password for command-line pushes:

1. Go to [github.com/settings/tokens](https://github.com/settings/tokens)
2. Click **Generate new token (classic)**
3. Give it a name like `farm-app-upload`
4. Check the **repo** checkbox
5. Scroll down → Click **Generate token**
6. Copy the token (it starts with `ghp_...`)
7. When Terminal asks for your password, **paste the token** instead

### Step 8 — Verify it worked

Go to `https://github.com/YOUR_USERNAME/farm-app` in your browser.
You should see all your files there, and `config/secrets.php` should
NOT appear in the config folder.

---

## Ongoing: Pushing Future Updates

After the initial setup, whenever you make changes:

```bash
cd "/Users/navin/Desktop/farm app"
git add .
git commit -m "Brief description of what you changed"
git push
```

---

## Questions?

If anything goes wrong during setup, check the error message and
refer back to this guide. The most common issues are:

- **"remote already exists"** → run `git remote remove origin` then retry Step 6
- **"authentication failed"** → make sure you're using a Personal Access Token, not your password
- **"nothing to commit"** → run `git status` to see the current state

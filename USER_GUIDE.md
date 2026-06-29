# SEO Rank Tracker — User Guide

## Table of Contents

1. [Overview](#overview)
2. [Getting Started](#getting-started)
3. [User Roles & Permissions](#user-roles--permissions)
4. [Dashboard](#dashboard)
5. [Managing Websites](#managing-websites)
6. [Managing Keywords](#managing-keywords)
7. [Viewing Rankings](#viewing-rankings)
8. [Exporting Data](#exporting-data)
9. [Running a Manual Rank Check](#running-a-manual-rank-check)
10. [API Settings](#api-settings)
11. [User Management](#user-management)
12. [Audit Log](#audit-log)
13. [Profile](#profile)
14. [Artisan Commands (Technical)](#artisan-commands-technical)

---

## Overview

SEO Rank Tracker is a web application that monitors your websites' Google search rankings for a set of tracked keywords. It fetches daily rank positions through third-party SERP APIs (Serper, SerpAPI, or DataForSEO), stores a history of every result, and lets you export the data to Excel.

---

## Getting Started

### Login

Navigate to `/login` and enter your email address and password.

- If your account is inactive, you will see an error message. Contact your administrator.
- A **Remember me** checkbox keeps you logged in across browser sessions.

### After Login

You are taken directly to the **Dashboard**, which shows a portfolio summary of all active websites and their current keyword rankings.

---

## User Roles & Permissions

The system has three roles. An administrator assigns your role when creating your account.

| Action | Viewer | Manager | Admin |
|---|:---:|:---:|:---:|
| View Dashboard | Yes | Yes | Yes |
| View Keywords | Yes | Yes | Yes |
| View Rankings | Yes | Yes | Yes |
| Export Rankings | Yes | Yes | Yes |
| Add / Edit Website | — | Yes | Yes |
| Add / Edit Keyword | — | Yes | Yes |
| Delete Keyword | — | — | Yes |
| Trigger Manual Rank Check | — | Yes | Yes |
| Manage Users | — | — | Yes |
| Configure API Settings | — | — | Yes |
| View Audit Log | — | — | Yes |

---

## Dashboard

**URL:** `/`

The Dashboard is your top-level view across all active websites.

### Portfolio Stats (top row)

| Card | What it shows |
|---|---|
| **Websites** | Total number of active websites being tracked |
| **Total Keywords** | Sum of all keywords across all websites |
| **Portfolio Avg. Rank** | Average Google rank position across all ranked keywords |
| **Top 10 Keywords** | Total count of keywords currently ranking in positions 1–10 |

The active SERP provider name and remaining API credits are shown below the Websites table heading.

### Websites Table

Each row represents one website with:

- **Website name** and active/inactive status
- **Domain** — clickable link to the live site
- **Keywords** — total keywords assigned to that site
- **Ranked** — how many keywords have a position today vs. total
- **Avg. Rank** — colour-coded: green (≤10), amber (11–20), red (>20)
- **Top 10** — count of keywords in positions 1–10

**Actions per row:**
- **Rankings** — opens the per-website rankings detail page
- **Keywords** — opens the Keywords list pre-filtered to this website
- **Edit** (pencil icon, managers only) — opens the Edit Website modal

---

## Managing Websites

### Add a Website

1. On the Dashboard, click **Add Website** (managers and admins only).
2. Fill in:
   - **Website Name** — a friendly label (e.g. "Mumbai Darshan Bus")
   - **Domain** — the bare domain (e.g. `mumbaidarshanbusplaces.com`)
   - **Base URL** — the full URL including protocol (e.g. `https://mumbaidarshanbusplaces.com`)
3. Click **Add Website**.

### Edit a Website

1. On the Dashboard, click the pencil icon in the website's row.
2. Update Name, Domain, or Base URL as needed.
3. Use the **Active** checkbox to temporarily pause tracking for a website without deleting it.
4. Click **Save Changes**.

---

## Managing Keywords

**URL:** `/keywords`

### Keyword List

The Keywords page shows all tracked keywords with filters at the top:

- **Search** — filter by keyword text or target URL
- **Website** — filter by a specific website

Each row shows: Keyword, Target URL, Website, Monthly Searches, KD (Keyword Difficulty), Competition, Intent, and the latest rank position.

### Add a Keyword

1. Click **Add Keyword** (managers and admins only).
2. Fill in the form:

| Field | Required | Description |
|---|:---:|---|
| Keyword | Yes | The search phrase to track (e.g. "best bus tours in Mumbai") |
| Target URL | Yes | The specific page on your site you want to rank |
| Website | No | Associate this keyword with a website |
| Monthly Searches | No | Average monthly search volume |
| SEMrush Volume | No | Volume figure from SEMrush |
| KD (0–100) | No | Keyword difficulty score |
| Competition | No | Low / Medium / High |
| Intent | No | I (Informational), T (Transactional), N (Navigational), C (Commercial) |
| Currency | No | Defaults to INR |

3. Click **Add Keyword**.

### Edit a Keyword

Click the edit (pencil) icon on a keyword row to update any of its fields. Changes are saved to the audit log.

### Delete a Keyword

Click the delete (trash) icon on a keyword row. Only admins can delete keywords. Deleted keywords and all their historical ranking data are removed permanently.

---

## Viewing Rankings

**URL:** `/websites/{website}`

Click **Rankings** on any website row from the Dashboard to open the detailed rankings view for that site.

### Summary Cards

| Card | Description |
|---|---|
| **Total Keywords** | Keywords tracked for this website |
| **Avg. Rank** | Average position today across all ranked keywords |
| **Top 10** | Count of keywords in positions 1–10 today, with change vs. yesterday |
| **New This Week** | Keywords added in the last 7 days |

An up/down trend arrow compares today's average rank to yesterday's.

### Date Range

Use the **7 Days / 14 Days / 30 Days** toggle to control how many historical columns are shown in the table.

### Rankings Table

Each row is one keyword with columns for each day in the selected date range. The rank position for each day is shown in the corresponding cell. Blank means no data was collected for that day.

Additional columns:

- **Today** — current rank position
- **Change** — difference from yesterday (green = improved, red = dropped)
- **Sparkline** — a small 7-day trend bar chart
- **KD** — keyword difficulty
- **Intent** — search intent code

**Last Checked** timestamp is shown at the top right so you know when the latest data was fetched.

---

## Exporting Data

**All authenticated users** can export rankings to Excel.

### How to Export

1. On the Keywords page or Rankings page, click **Export to Excel**.
2. A `.xlsx` file is downloaded named `rankings-YYYY-MM-DD.xlsx`.

### Export Format

The spreadsheet contains the last **30 days** of ranking history with the following columns:

| Column | Description |
|---|---|
| Sr. No. | Row number |
| Keyword | The tracked keyword |
| Currency | Currency code (e.g. INR) |
| Avg. Monthly Searches | Search volume |
| Volume | SEMrush volume |
| Competition | Low / Medium / High |
| Intent | I / T / N / C |
| KD | Keyword difficulty (0–100) |
| URL | Target URL |
| Rank (date)… | One column per day with the rank position |

The header row is bold with a green background. Rows alternate white and light grey for readability. Auto-filter is enabled on the header row.

---

## Running a Manual Rank Check

By default, the system automatically checks rankings once per day (at midnight via the scheduler). Managers and admins can also trigger an immediate check.

### From the UI

1. Go to a **Website Rankings** page.
2. Click **Check Now** (button visible to managers and admins).
3. A background job is dispatched. Rankings will update within a few minutes depending on how many keywords are tracked.

> Rank checks are rate-limited to one keyword per second to avoid exceeding API quotas.

### Skip Logic

If a keyword has already been checked today, it is skipped automatically — the job will not double-bill your API credits.

---

## API Settings

**URL:** `/settings/api` — Admin only

### Configuring a SERP Provider

The system supports three Google SERP data providers. Choose one and enter the corresponding API key.

| Provider | Notes |
|---|---|
| **Serper** | Fast, affordable. Requires a Serper API key. |
| **SerpAPI** | Widely used. Requires a SerpAPI key. |
| **DataForSEO** | Enterprise-grade. Requires a login (email) + API password. |

**Steps:**

1. Select your **Provider** from the dropdown.
2. Enter your **API Key** (and **Login** if using DataForSEO).
3. Select the **Country** (2-letter ISO code, e.g. `in` for India, `us` for USA). All rank checks run for this country's Google results.
4. Click **Save Settings**.

The existing key is masked (shows only last 4 characters). Leave the key field untouched if you only want to change the country or provider without re-entering the key.

### Testing the Connection

Click **Test Connection** to verify your API key works. A success or failure message, along with remaining API credits, is displayed immediately.

---

## User Management

**URL:** `/users` — Admin only

### Inviting a New User

1. Click **Invite User**.
2. Fill in:
   - **Name** — full name
   - **Email** — must be unique
   - **Role** — Viewer, Manager, or Admin
   - **Password** — leave blank to auto-generate a 12-character password
3. Click **Invite**.

The temporary password is shown in the success message. You must share it with the user manually (email invitation is planned but not yet enabled).

### Editing a User

Click the edit icon on a user row to:
- Change their **role**
- Toggle their **active** status (deactivating blocks login immediately)

---

## Audit Log

**URL:** `/audit` — Admin only

The Audit Log records every significant action performed in the system.

### Filters

- **User** — filter by a specific team member
- **Action** — filter by event type (e.g. `keyword.added`, `user.invited`, `api.settings_updated`)
- **From / To** — date range filter

### Logged Events

| Action | Trigger |
|---|---|
| `website.created` | A new website is added |
| `keyword.added` | A keyword is created |
| `keyword.updated` | A keyword's data is edited |
| `url.updated` | A keyword's Target URL is changed |
| `keyword.deleted` | A keyword is deleted |
| `user.invited` | A new user account is created |
| `user.updated` | A user's role or status is changed |
| `api.settings_updated` | SERP provider or key is changed |

Each entry records the old values and new values so you can see exactly what changed.

---

## Profile

**URL:** `/profile`

View your own account details including name, email, role, last login time, and login count. Password changes and profile editing are planned for a future update.

---

## Artisan Commands (Technical)

For server administrators running commands directly on the server.

### Manual Rank Check

```bash
php artisan ranks:check
```

Dispatches the `RankCheckJob` to the queue (or runs synchronously if `QUEUE_CONNECTION=sync`).

```bash
php artisan ranks:check --dry-run
```

Lists all active keywords and their target URLs **without** making any API calls. Useful for verifying the keyword list before spending credits.

### Running the Queue Worker

If your server uses a real queue driver (database, Redis), start the worker so jobs process:

```bash
php artisan queue:work --queue=default
```

### Scheduler

The automatic daily rank check relies on Laravel's task scheduler. Add this cron entry to the server to enable it:

```
* * * * * cd /path/to/seoRankTracker && php artisan schedule:run >> /dev/null 2>&1
```

---

## Frequently Asked Questions

**Why is a keyword showing no rank?**
A keyword with no rank in the last 100 Google results is recorded as "not ranked" — the cell appears blank. This is not an error; the site simply did not appear in the top 100 results for that search on that day.

**Why are today's rankings not showing yet?**
The automatic check runs at midnight. You can trigger an immediate check using the **Check Now** button (managers/admins) or by running `php artisan ranks:check`.

**Can I track keywords for the same page across multiple websites?**
Yes. Keywords are linked to a Website but the target URL is independent. You can track the same URL under different websites if needed.

**How many API credits does a rank check use?**
Each keyword costs one credit per check. If you have 50 keywords, one daily run costs 50 credits. The remaining credit balance is shown on the Dashboard and API Settings page.

**Can I deactivate a website without losing data?**
Yes. Edit the website and uncheck **Active**. The website and all its rankings history are preserved; it just won't appear in the Dashboard's active list or be included in future rank checks.

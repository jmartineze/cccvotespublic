# CCC Votes

Private voting dashboard for the **Culture Cuties Contest (CCC)** — a panel of judges scores art/character submissions across multiple themed contests.

Built mobile-first since judges primarily use phones to browse and vote.

---

## Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 13 · PHP 8.3 |
| Frontend | Blade · Tailwind CSS v4 · Alpine.js v3 |
| Database | MySQL |
| Build | Vite 8 |

---

## Features

### Multi-tenant
- A **super-admin** creates independent **tenant admins**, each running their own isolated space of contests and judges (`/super-admin/tenants`)
- Tenants can't see or edit each other's contests, submissions, votes, or judges — enforced via `owner_id` scoping
- Judges log in with a **username + password**, no email required; usernames are **globally unique**
- **A judge can belong to multiple tenants.** When a tenant admin adds a judge whose username already exists, a modal offers to *invite* that judge to the tenant instead of creating a duplicate (no new account, password untouched). On login the judge sees every contest from every tenant they're a member of; each contest card shows a badge with the owner tenant's name. Removing a judge removes them from that tenant only — the account is deleted just when it has no tenants left.
- Super-admins and tenant admins keep email-based login; the same `/login` form accepts either an email or a username, matched against both columns — anyone who has set both can sign in with whichever they prefer (same password)
- **Forgot password?** on the login screen emails a time-limited reset link (60 min) via the configured SMTP account to any user who has an email on file; judges without an email ask their tenant admin for a manual reset
- **Co-admins** — a tenant admin promotes any of their judges to *co-admin* (Judges & Co-admins page). A co-admin gets the full admin panel for that tenant (contests, submissions, judges) but can't promote/revoke co-admins. Co-admin is **per tenant**: a judge can co-admin several tenants at once; a nav dropdown picks which one they're managing.
- **Judge mode toggle** — tenant admins and co-admins have a *Judge view* switch in the top bar. It persists on the account: while on, they browse and vote exactly like a judge (blind-voting lock included) and the admin panel is hidden until they switch back.
- **Removing a judge** keeps their votes on **closed** contests untouched; a confirmation modal asks whether to also delete their votes on active/draft contests. The account itself is deleted only when it has no tenants left. Password reset by an admin is available only for judges that belong to a single tenant; shared accounts change their own credentials from the Profile tab.

### For Judges
- Search submissions instantly by character name or Discord user (client-side, no reload)
- Browse submissions in a 2-column grid with category filters
- View all images and videos per submission in a swipeable carousel (supports 1–12 files); videos autoplay with loop when the slide is active
- Score each submission with touch-friendly sliders defined by the contest's own criteria — live total preview
- Add a comment explaining your vote (required on `character_scenario` contests, optional on `image` contests)
- Mark one submission per contest as your **Honorable Mention** — shown in the results even if it didn't place in the top 3; can be changed even after the contest closes to resolve conflicts
- Toggle any of the contest's **special prizes** on any number of submissions — no limits, no effect on scores
- See your own votes and your HM pick at a glance; leaderboard locked until contest closes
- **Profile tab** (bottom nav, every role) — edit your own name, username, email and password. Email is optional for judges; leaving the password blank keeps the current one.

### For Admins
- Create and manage multiple contests (Draft → Active → Closed)
- Define each contest's own **scoring criteria** (name + max score) and **tiebreaker order** — locked once voting starts
- Add non-scoring **special prizes** (name + description) — side awards like "Best image" or "Made me laugh". Editable at any time; they don't touch the score
- Choose a contest type: `image` (visual-only) or `character_scenario` (adds a required scenario field to submissions and a required judge comment)
- Search submissions by character name or Discord user with real-time filtering
- Upload submissions with drag-and-drop support for images and videos (mp4, webm, mov)
- Manage judges: create accounts (username + password), **invite an existing judge from another tenant**, reset passwords, remove judges, promote to / revoke co-admin
- View full leaderboard at any time regardless of contest status
- Switch to *Judge view* to vote in your own contest, then switch back

### Results & Scoring
- Total score is the sum of the contest's own configurable criteria (no fixed max — set per contest)
- Rankings grouped by category (Female Anime, Male Realistic, etc.)
- **Tiebreakers**: resolved in the order the admin configured (1st criterion, 2nd, ...); if every level ties, both entries are flagged ⚡ "Committee Vote Required"
- **Honorable Mentions**: each judge picks one submission as HM; conflicts (duplicate picks or winners marked as HM) are flagged in the results page
- **Special prizes**: for each prize, results list submissions ordered by how many judges checked them (submissions with zero checks are hidden)

---

## Local Setup

### Requirements
- PHP 8.3+, Composer
- Node.js 18+, npm
- MySQL (or use Docker)

### With Docker (recommended)
```bash
git clone https://github.com/jmartineze/cccvotes.git
cd cccvotes

docker-compose up -d
docker-compose exec app bash

composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
npm install && npm run build
```

### Without Docker
```bash
git clone https://github.com/jmartineze/cccvotes.git
cd cccvotes

composer install
cp .env.example .env
# Edit .env — set DB_* credentials

php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
npm install && npm run build
php artisan serve
```

### Dev servers
```bash
php artisan serve   # Laravel on :8000
npm run dev         # Vite HMR
```

---

## Seed Accounts

| Role | Login | Password |
|------|-------|----------|
| Super Admin | `admin@ccc.local` (email) | `password` |
| Tenant Admin | `tenant1@ccc.local` (email) | `password` |
| Judge Alpha | `alpha` (username) | `password` |
| Judge Beta | `beta` (username) | `password` |
| Judge Gamma | `gamma` (username) | `password` |
| Co-admin Delta | `delta` (username) | `password` |

---

## Production Deployment

```bash
git clone https://github.com/jmartineze/cccvotes.git
cd cccvotes

composer install --no-dev --optimize-autoloader
cp .env.example .env
# Edit .env: APP_ENV=production, APP_URL, DB_* credentials
# For password-reset emails, set MAIL_MAILER=smtp and MAIL_HOST / MAIL_PORT /
# MAIL_USERNAME / MAIL_PASSWORD / MAIL_ENCRYPTION / MAIL_FROM_ADDRESS for your SMTP account.
# APP_URL must be the real public URL — the reset link in the email is built from it.

php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
npm install && npm run build

php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Pulling updates
```bash
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm run build
php artisan config:clear && php artisan route:clear && php artisan view:clear
```

> Back up the database before running `migrate --force` on a release that includes schema-breaking migrations (e.g. the multi-tenant upgrade drops legacy columns). Never run `php artisan db:seed --force` against an existing production database — it's for fresh installs only.

---

## Project Structure

```
app/Http/Controllers/
├── Admin/
│   ├── ContestController.php       # Contest CRUD + criteria & tiebreaker config
│   ├── SubmissionController.php    # Upload & delete submissions
│   └── UserController.php          # Judge management (scoped to own tenant)
├── SuperAdmin/
│   └── TenantController.php        # Tenant admin management
├── Judge/
│   └── VotingController.php        # Browse, view & submit votes
├── AuthController.php               # Dual email/username login
├── DashboardController.php
├── ProfileController.php           # Self-service name / username / email / password
└── ResultsController.php           # Leaderboard + configurable tie-breaker logic

resources/views/
├── admin/{contests,submissions,users}/
├── superadmin/tenants/
├── judge/voting/
├── results/
├── profile/
├── partials/                        # Reusable cards & forms
└── layouts/app.blade.php

resources/css/app.css                # Full design system
```

---

## Business Rules

- Tenants (contests, judges, submissions, votes) are fully isolated by `owner_id`; a tenant admin can never see or edit another tenant's data. Super-admins bypass this isolation
- One submission per Discord user per gender per contest (`contest_id + discord_user + gender` unique)
- One vote per judge per submission
- One username per tenant (`owner_id + username` unique) — judges never need an email
- Admins cannot vote
- Leaderboard hidden from judges until contest is `closed`
- A contest's scoring criteria lock once the first vote is cast
- Max 12 files per submission (images + videos combined, 10 MB each); thumbnail shows the first image, or the first video frame if all files are videos

---

## License

Private project — all rights reserved.

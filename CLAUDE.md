# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**CCC Votes** — Private web-based voting dashboard for the Culture Cuties Contest (CCC).
Built with Laravel 13, Blade, Tailwind CSS v4, and Alpine.js.

Judges use this system exclusively on mobile to score art/character submissions across multiple contests. Admins manage contests, submissions, and judge accounts.

## Architecture

### Roles (`users.role` = `super_admin` | `tenant_admin` | `judge`)
- **super_admin**: manages tenants (`/super-admin/*`). Not tenant-scoped. Does not run contests, cannot vote, no judge mode.
- **tenant_admin**: owns a tenant — contests/submissions, judge roster, promote/revoke co-admins, full results any time. Cannot vote *while in admin mode*.
- **judge** (`users.role = 'judge'`, the only member role): the actual per-tenant capability lives on the `tenant_memberships` pivot.

### Tenant memberships (`tenant_memberships(tenant_id, user_id, role)`)
- A judge can belong to **many tenants**, and be **`judge` or `co_admin` per tenant** (`pivot.role`). `users.owner_id` is only the "home"/creator tenant and is mirrored into a `judge` membership row by a `User::created` hook.
- **co_admin** = a membership with `role = 'co_admin'`. Full admin powers for *that* tenant (contests, submissions, judges) except promote/revoke co-admins (tenant_admin only). A judge can co-admin several tenants.
- `User` helpers: `memberTenantIds()` (all memberships; `[id]` for tenant_admin), `adminTenantIds()` (co-admin memberships; `[id]` for tenant_admin), `isCoAdmin()` / `isCoAdminOf($t)`, `currentTenantId()`, `belongsToTenant($t)`.
- **`currentTenantId()`** = the tenant being administered now: `id` for tenant_admin; for a multi-tenant co-admin it's `session('admin_tenant_id')` (set by `POST /mode/tenant`, nav `<select>` shown when `count(adminTenantIds()) > 1`), else the first admin tenant.
- **`TenantScope` on `Contest`**: admin view → `whereIn(owner_id, [currentTenantId()])`; judge view → `whereIn(owner_id, memberTenantIds())`; empty → `[0]` (matches nothing).
- **Usernames are globally unique** (`users_username_unique`). Creating a judge whose username belongs to an existing judge shows an "invite instead?" prompt (`session('invite_prompt')` → modal in `admin/users/create`). `POST /admin/users/invite` adds a `judge` membership, never touches the password. A tenant-admin username → plain "taken" error.
- **Removing a judge** (`UserController::destroy`): drops the membership. Votes/HM on that tenant's **closed** contests are always kept; on active/draft contests only when `delete_open_votes` is passed (confirm modal in `admin/users/index`). When it was their last membership the account + all its votes/HM are deleted.
- **Password reset** by a tenant admin only works if the judge belongs to exactly one tenant. A shared account changes its own credentials from **Profile** (`/profile`, bottom-nav tab for every role): name, username, email, password. `username` required for `role = 'judge'` / optional otherwise; `email` required for the email-login roles / optional for judges; a blank password field keeps the current one (a new one needs `current_password`).
- Contest cards / lists / voting header show a tenant-owner badge (`$contest->owner->name`) to judges. Eager-load `owner`.

### View mode (`users.judge_mode`, tenant_admin + any co-admin)
- Persistent per-account toggle (`POST /mode/toggle`, `ModeController`). When on, the user browses and votes as a judge and the admin panel 403s.
- `User` helpers: `canSwitchMode()`, `inJudgeMode()`, `actingAsAdmin()`, `actingAsJudge()`. **Gate on `actingAsAdmin()` / `actingAsJudge()`, not `isAnyAdmin()`**, anywhere the current view matters (middleware, VotingController, ResultsController, MediaController, BelongsToTenant, TenantScope, nav).
- `AdminMiddleware` requires `actingAsAdmin()`; `demote` clears `judge_mode` when no co-admin memberships remain.

### Key Models & Relationships
```
Contest → has many Submission → has many SubmissionImage (max 12)
                              → has many Vote
                              → has many HonorableMention
User (Judge) → has many Vote
             → has many HonorableMention (one per contest, enforced by unique constraint)
Vote → belongs to User + Submission
HonorableMention → belongs to User + Submission + Contest
```

### Scoring
| Criterion | Max | Notes |
|---|---|---|
| Composition | 10 | Visual balance, pose, framing |
| Cultural Authenticity | 20 | Accuracy & depth of cultural representation |
| Allure | 10 | Visual appeal & overall impression |
| Backstory | 10 | Creativity & quality of written backstory |
| **Total** | **50** | Auto-calculated on Vote save |

### Tie-breaker Logic (ResultsController)
1. Higher `cultural_score` sum wins
2. If still tied → both flagged with `committee_vote = true` → "⚡ Committee Vote" badge shown

### Honorable Mention
- One HM per judge per contest (`honorable_mentions` table, unique on `user_id + contest_id`)
- Picking a new one replaces the old; picking the same one removes it (toggle)
- Admins cannot select HMs
- **Editable after contest closes** — judges can change/remove their HM even when the contest is `closed`, so they can resolve conflicts; voting scores remain locked
- Access when closed: dashboard shows a "My Votes" secondary button on closed contests → leads to `judge.voting.index` → from there judges open any submission detail to change their HM
- Conflict detection in `ResultsController::show` (unlocked state):
  - **Duplicate pick** — `$hmBySubmission` groups with `count > 1`
  - **Winner as HM** — HM submission appears in top-3 of any leaderboard category (`$hmWinnerConflicts`)
- Locked results: judge sees only their own HM pick at the bottom of personal scores
- Unlocked results: all HMs listed with judge names; conflict alerts shown above the list

### Special Prizes (`special_prizes`, `special_prize_votes`)
- Non-scoring, toggle-style side awards per contest (name + optional description). Configured in the contest form alongside criteria, but **stay editable at any point** (id-aware `ContestController::syncSpecialPrizes` — dropped prizes cascade-delete their votes; kept prizes keep theirs).
- A judge toggles any number of submissions for any number of prizes: `POST /contests/{contest}/special-prize/{prize}/{submission}` (`VotingController::specialPrize`) — creates/deletes a `special_prize_votes` row. Unique on `(special_prize_id, user_id, submission_id)`. Requires `actingAsJudge()` + `status = active`; prize/submission must belong to the bound contest.
- Voting detail (`judge/voting/show`): checkbox list in the active view; a read-only badge list of the judge's own picks in the closed view.
- Results (`ResultsController::show`, unlocked): `$specialPrizeResults` — per prize, submissions with ≥1 check ordered by `prize_checks` desc (zero-check submissions omitted). Locked view shows the judge's own picks.

### Blind Voting Rule
Results page is **locked** while a contest is `active` for judges — they see only their own votes. Full leaderboard unlocks when contest status changes to `closed`. Admins always see full results.

### Voting access rules (VotingController)
- Judges may only act on contests owned by a tenant they belong to — `Contest` route binding carries `TenantScope` (`whereIn owner_id, memberTenantIds()`), so a foreign contest 404s. `$submission->contest_id` is checked against the bound contest.
- `vote()` requires `status === 'active'`. `index()` / `show()` / `honorableMention()` reject `draft` (404); HM stays editable on `closed`.
- `Vote.user_id` is always `auth()->id()` — never taken from the request.

### Abuse protection
- `POST /login`: the identifier is matched against `email` **or** `username` (`User::where('email', $id)->orWhere('username', $id)`), so a user with both signs in with either. 5 failed attempts per `identifier+IP` → cooldown (`AuthController`), plus `throttle:20,1`.
- `password.email` / `password.update`: `throttle:6,1`. `judge.voting.vote` / `.hm`: `throttle:60,1`.
- `robots.txt` is `Disallow: /`; all views send `noindex, nofollow`.

### Media Files
- Submissions support up to 12 files (images **and** videos: jpg, png, gif, webp, mp4, webm, mov)
- Max 10 MB per file; nginx `client_max_body_size` and PHP `post_max_size` are both set to 120 MB to cover the full multi-file request
- `SubmissionImage` stores all media types; use `$image->isVideo()` to distinguish them
- Thumbnails: `Submission::thumbnailImage()` returns the first non-video image; fallback is a `<video preload="metadata">` showing the first frame
- **Files are served through auth-gated routes, not `/storage` links.** `$image->url` → `media.submission-image`, `$contest->cover_image_url` → `media.contest-cover` (both `MediaController`, `auth` middleware). Submission media checks the contest is visible under `TenantScope` and hides `draft` contests from judges. Files still live on the `public` disk; production should drop the `public/storage` symlink or `deny` `/storage/` in nginx so the gate can't be bypassed.

### Unique Constraints
- `submissions`: `['contest_id', 'discord_user', 'gender']` — one entry per gender per contest per Discord user
- `votes`: `['user_id', 'submission_id']` — one vote per judge per submission
- `honorable_mentions`: `['user_id', 'contest_id']` — one HM per judge per contest
- `special_prize_votes`: `['special_prize_id', 'user_id', 'submission_id']` (`sp_vote_unique`) — one check per judge per submission per prize
- `users`: `username` — globally unique (was `['owner_id', 'username']`)
- `tenant_memberships`: `['tenant_id', 'user_id']` — one membership row per judge per tenant; `role` = `judge` | `co_admin`

## File Structure

```
app/
  Http/
    Controllers/
      AuthController.php
      PasswordResetController.php  # Forgot-password flow via Laravel's Password broker (SMTP)
      MediaController.php          # Auth-gated submission images / contest covers
      ModeController.php           # POST /mode/toggle (judge-mode) + /mode/tenant (co-admin tenant switch)
      DashboardController.php
      ProfileController.php        # GET/POST /profile — self-service name/username/email/password (all roles)
      ResultsController.php
      Admin/
        ContestController.php      # Contest CRUD (tenant_admin + co_admin)
        SubmissionController.php   # Submission upload + delete
        UserController.php         # Judges + co-admins: create, reset pw, remove, promote/demote
      Judge/
        VotingController.php       # Voting index, show, submit
    Middleware/
      AdminMiddleware.php
    Requests/
      StoreContestRequest.php
      UpdateContestRequest.php
      StoreSubmissionRequest.php   # Validates duplicate discord_user+gender
      StoreVoteRequest.php
  Models/
    Contest.php
    Submission.php
    SubmissionImage.php
    Vote.php                       # Auto-calculates total_score on save
    HonorableMention.php
    SpecialPrize.php               # Non-scoring toggle award per contest
    SpecialPrizeVote.php           # judge × submission × prize check
    TenantMembership.php           # judge ↔ tenant pivot (multi-tenant judges)
    User.php

resources/
  css/app.css                      # Full design system (dark theme, custom components)
  views/
    layouts/app.blade.php
    auth/{login,forgot-password,reset-password}.blade.php
    dashboard.blade.php
    admin/contests/{index,create,edit}.blade.php
    admin/submissions/{index,create}.blade.php
    admin/users/{index,create}.blade.php
    judge/voting/{index,show}.blade.php
    results/{index,show}.blade.php
    profile/edit.blade.php
    partials/{contest-card,submission-card,contest-form}.blade.php
```

## Commands

### Development
```bash
php artisan serve     # HTTP server
npm run dev           # Vite dev server (Tailwind v4 via @tailwindcss/vite)
```

### Build
```bash
npm run build         # Build production frontend assets
```

### Testing
```bash
php artisan test                          # Run all tests
php artisan test --filter=TestClassName   # Run a single test class
```

### Code Style
```bash
./vendor/bin/pint     # Format PHP code (Laravel Pint)
```

### Database
```bash
php artisan migrate                # Run migrations
php artisan migrate:fresh          # Drop all tables and re-run migrations
php artisan db:seed                # Seed admin + sample judges
php artisan storage:link           # Required once — links public/storage
```

### Docker
```bash
docker-compose up -d          # Start all services (app, web, db)
docker-compose exec app bash  # Shell into PHP container
```

## Seed Accounts

| Role | Login | Password |
|------|-------|----------|
| super_admin | `admin@ccc.local` (email) | `password` |
| tenant_admin | `tenant1@ccc.local` / `tenant2@ccc.local` (email) | `password` |
| judge | `alpha` / `beta` / `gamma` (username) | `password` |
| co_admin | `delta` (username) | `password` |

`alpha` is a judge in both `tenant1` and `tenant2`; `delta` is a co-admin of both (multi-tenant demo).

## Design System

- **Theme**: Dark ("Neon Harajuku") — background `#070711`, accent pink `#ff2d78`, cyan `#00d4ff`
- **Fonts**: Syne (headings) · Outfit (body) · Space Mono (scores/numbers)
- **Components**: `.card`, `.card-glass`, `.btn`, `.badge`, `.input`, `.submission-card`, `.leaderboard-row` — all defined in `resources/css/app.css`
- **Navigation**: Fixed bottom nav bar (mobile-first), sticky top bar
- **Image uploads**: stored in `storage/app/public/submissions/`, streamed by `MediaController` behind `auth` (no public `/storage` links)

## Deployment (Production)

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env && php artisan key:generate
# Edit .env: DB credentials, APP_URL, APP_ENV=production
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
npm install && npm run build
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

### Pull updates
```bash
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm run build
php artisan config:clear && php artisan view:clear && php artisan route:clear
```

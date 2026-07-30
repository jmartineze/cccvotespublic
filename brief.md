# Project Brief: CCC Voting Dashboard (Laravel + Tailwind CSS)

## 1. Project Context
I need to build a private, web-based voting dashboard for a panel of judges evaluating an art/character contest (The Culture Cuties Contest). The app will be built using Laravel (Blade) and Tailwind CSS. The system must handle manual data entry by an admin, blind voting by judges using sliders, and automatic score tallying with specific tie-breaker logic.

## 2. Tech Stack
* Backend: Laravel (PHP)
* Frontend: Blade Templates, Tailwind CSS (Alpine.js is welcome for slider reactivity/modals).
* Database: MySQL or SQLite.

## 3. Database Schema & Models
Please generate the migrations and Models for the following:

**Model: `Submission`**
* `discord_user` (string)
* `character_name` (string)
* `country` (string)
* `backstory` (text, max 1000 chars)
* `image_url` (string)
* `gender` (enum: 'Male', 'Female', 'Trans')
* `style` (enum: 'Anime', 'Realistic')
* *Constraint:* A user can only submit one entry per gender. Add a unique composite index: `$table->unique(['discord_user', 'gender']);`

**Model: `User` (Judges)**
* Standard Laravel auth table.
* Role management (Admin vs. Judge) is optional for now; assume all logged-in users are judges, but only one admin does the manual uploading.

**Model: `Vote`**
* `user_id` (foreign key to User/Judge)
* `submission_id` (foreign key to Submission)
* `composition_score` (integer, 0-10)
* `cultural_score` (integer, 0-20)
* `allure_score` (integer, 0-10)
* `backstory_score` (integer, 0-10)
* `total_score` (integer, virtual or calculated before save)
* *Constraint:* A judge can only vote once per submission. `$table->unique(['user_id', 'submission_id']);`

## 4. Core Features & Business Logic to Implement

**A. Manual Upload View (Admin)**
* A simple form to create a `Submission`.
* Must include Laravel Form Request validation to prevent inserting a duplicate `discord_user` + `gender` combination. Return a friendly error if attempted.

**B. Judge Voting Interface (Blind Voting)**
* A grid/list of all submissions.
* When a judge clicks a submission, they see the image, country, and backstory.
* Underneath, there should be 4 HTML range sliders (or input numbers) for the scoring categories.
* Judges MUST NOT see the current average or other judges' votes. 

**C. Results & Leaderboard Logic (The Math)**
Create a Results view that groups submissions by their category (e.g., Male Anime, Female Realistic) and ranks them by the sum of all judges' `total_score`.

**Tie-Breaker Logic (Crucial):**
If two submissions in the same category have the exact same overall total score, the system must apply the First Tie-Breaker:
1. Compare the total sum of their `cultural_score`. The entry with the higher cultural score wins the tie.
2. If the `cultural_score` is also tied, flag both entries with a visual badge (e.g., "COMMITTEE VOTE REQUIRED") on the dashboard so the admin knows a manual tie-breaker is needed.

## 5. Output Request
Please provide:
1. The Migration files for `submissions` and `votes`.
2. The Eloquent Models with relationships.
3. The Form Request class for the Submission creation (handling the unique rule).
4. The Controller method (`ResultsController`) that queries the database, calculates the totals, and handles the First Tie-Breaker logic.
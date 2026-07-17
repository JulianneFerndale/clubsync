# ClubSync — SRS & SDD Revision Brief

Plug this file into Claude alongside the existing **Software Requirements Specification
(SRS)** and **Software Design Document (SDD)** and ask it to revise both to match the current
implementation. Two themes drive the changes: the backend database moved from **MySQL to
cloud PostgreSQL**, and the app was **optimised for speed and user experience**. The sections
below are the authoritative list of changes; treat them as the source of truth where they
conflict with the older documents.

## 1. Database Migration: MySQL → Supabase (PostgreSQL, Tokyo)

- The database is now **Supabase PostgreSQL** in the **`ap-northeast-1` (Tokyo)** region;
  MySQL is fully retired.
- The app connects through the **Supabase session pooler on port 5432** — not the transaction
  pooler (6543), which breaks migrations and prepared statements.
- Connections use TLS (`sslmode`) and a short connect timeout to fail fast rather than hang.
- **PostgreSQL behavioural differences handled in code:**
  - Search/filter uses `LOWER(col) LIKE ?` (Postgres `LIKE` is case-sensitive; MySQL was not).
  - Login email lookups are normalised to lowercase before matching.
  - All tables live in the `public` schema (a stray `laravel.` prefix was removed).
- **Free-tier storage (~500 MB)** is now a hard constraint — see §4.
- SRS: DB technology, hosting region, data locality, storage limits. SDD: data-tier and
  deployment diagrams, connection topology, migration/compatibility notes.

## 2. Architecture & Stack Corrections

- Stack: **Laravel + Blade + Tailwind CSS 4 + Vite**; the UI is **server-rendered Blade** —
  remove any SRS/SDD claim that it is an Inertia/Vue single-page application.
- Client interactivity is **vanilla JavaScript** plus **Hotwire Turbo** (new) for no-reload
  navigation. It is a **Progressive Web App** with an offline page and service worker.
- **Authentication** is Firebase (REST) with state in the Laravel session (no Laravel
  auth-guard login). A **refresh token is now persisted** so active users are not logged out
  when the 1-hour Firebase token expires.
- **Real-time:** database-backed queue + **Laravel Reverb** WebSocket server for broadcasting.

## 3. New / Revised Features

- **AI Club Narratives (Google Gemini):** on activity completion a post-event narrative is
  drafted automatically, held in a **human review queue (club adviser)**, and published only
  after approval. No AI output is ever auto-published.
- **Tamper-evident Audit Log:** append-only, SHA-256 **hash-chained** records of AI generation
  and approval actions, retained for **at least one academic year**.
- **Churn Risk Engine:** per-member engagement score (attendance, semester presence, unpaid
  fees, inactivity) tagged Low/Medium/High. **DSA sees all clubs; officers only their own;
  members and advisers are blocked (HTTP 403).**
- **Real-time notifications:** Reverb broadcasts a private per-user event; the bell badge and a
  toast update live, with no refresh.
- **Officer panel:** the home lists the officer's joined academic & non-academic clubs (mirrors
  the member home); officers can enroll in non-academic clubs; create-activity / draft-post
  tools live on the per-club dashboard.

## 4. Data Retention & Storage (new non-functional requirement)

- A daily scheduled job measures database size against the **500 MB** cap and **alerts admins**
  at 75% and 90% usage.
- It **auto-prunes only transient data** (expired sessions, old read notifications).
- Admins can **archive an old semester**: attendance and fee-payment records export to Excel,
  then prune. The current + previous semester and audit logs (≥ 1 year) are always retained.

## 5. User-Experience & Performance Optimisation

- **Session and cache drivers moved from `database` to `file`**, removing a remote round-trip
  to Tokyo on every request — light-page response fell from ~1.8 s warm / ~25 s cold to under
  0.1 s.
- **N+1 query fixes** via eager loading; duplicate per-page count queries consolidated.
- **Hotwire Turbo** gives SPA-style navigation (no full reloads or white flashes); file
  downloads opt out and download natively.
- **Global loading UI:** a branded splash during login/registration, a **cancellable dialog**
  during data-saving actions, and a navigation spinner, all with graceful exception handling.

## 6. Security & Privacy Constraints (reaffirm in both documents)

- Institutional-email-only registration; minimum-PII collection; per-club access isolation.
- Churn-risk data must never be visible to students; AI output always passes a human gate.
- Every data mutation, approval, and AI event is recorded in the tamper-evident audit log.

## 7. Revision Checklist

**SRS** — revise: technology stack; database and hosting region; storage/capacity limits; the
data-retention non-functional requirement; the real-time notification requirement; the
churn-engine access rules; and the AI human-review constraint.

**SDD** — revise: architecture and deployment diagrams (Supabase / Reverb / Turbo); the data
model and schema/region notes; the authentication and refresh-token flow; the broadcasting
sequence; the retention/archival job design; and the caching/session strategy.

## 8. Notes for the Reviser

- Keep requirement IDs stable; mark changed items as *Revised* and new ones as *Added*.
- Where the old documents describe MySQL, an Inertia/Vue SPA, or database-backed sessions,
  treat those passages as superseded by this brief.

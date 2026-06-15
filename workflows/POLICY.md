# POLICY.md

Business rules and institutional policies for ClubSync. These govern behavior that must be enforced in code — not UI preferences.

---

## Authentication & Passwords

- Registration requires an official SCC institutional email (`@sccpag.edu.ph`). No other domain is accepted.
- Password complexity: minimum 8 characters, at least one uppercase letter, one number, one special character.
- **Time-gated password changes:** a user may change their password at most once every 7 calendar days. Enforce server-side using the `lastPasswordChanged` timestamp. Display the next available change date on rejection.
- On failed login, always return a generic message ("The email or password you entered is incorrect.") — never reveal which field is wrong.
- Suspended accounts return: "Your account has been suspended. Please contact the Dean of Student Affairs for assistance."
- Session tokens expire after 30 minutes of inactivity. "Remember Me" stores the refresh token in an HttpOnly cookie with a 30-day expiry.

## Registration & Auto-Enrollment

- On course selection during registration, the student is immediately and automatically enrolled in the corresponding academic club. This is non-optional and cannot be bypassed for academic clubs.
- If no club is mapped to a course, complete registration, flag the student's profile as `Pending Club Assignment`, and alert the DSA Admin. Inform the student their club assignment is pending review.

## Role-Based Access & Churn Risk

- **Churn Risk data (engagement scores and risk tags) must never be visible to Student Members** — not even in aggregate or anonymized form. Enforce at both the UI and the server route/middleware level; return 403 on direct URL access.
- Churn risk is scoped: Club Officers see only their own club's members; DSA sees all clubs.
- Club Advisers do not have access to the Churn Risk Engine.
- An officer who belongs to multiple clubs gets elevated privileges per-club, not globally.

## Announcements & Content Workflow

- All MMO-drafted announcements and letters must pass through Adviser approval before publication. No announcement may be published directly by the MMO.
- Announcement statuses: `Draft` → `Pending Adviser Review` → `Published` | `Revision Required` | `Rejected`.
- If no active Adviser is assigned to a club, hold submissions in queue and alert the DSA Admin.

## Event Creation

- Event creation by officers is direct — no approval queue, no letter drafting required. On submission, the event is immediately saved as `Scheduled`.
- Required fields: Event Title, Date, Venue (from predefined SCC venue list), Purpose/Objectives, Expected Number of Participants. Block submission if any are missing.
- Venues must come from the SCC-configured list (Gymnasium, Main AV Room, Open Court, Function Hall, Covered Court, etc.) — not free-text.

## AI & Automation

- **No AI-generated content may be dispatched automatically.** All AI outputs (compliance notifications, post-event narratives) must pass through a human review queue before being sent or published. This gate cannot be bypassed by any role.
- If the Gemini API is unavailable: disable AI-assisted features gracefully, keep manual alternatives fully functional, and flag AI-generated drafts as "AI Unavailable — Manual Draft Required."

## Data Privacy (RA 10173)

- Collect only the minimum PII necessary (name, institutional email, EDP number, course, financial records).
- All PII must be encrypted at rest.
- Cross-club data access is blocked at middleware level — users cannot access data from clubs they are not members or officers of.
- Every data modification, approval action, login attempt, and AI content generation event must be written to the tamper-resistant audit log with timestamp, actor UID, and affected resource.
- Audit logs are retained for a minimum of one complete academic year.

## Notifications

- Notifications are in-system only — no SMS or direct email integrations.
- Notifications must be persisted so offline users see them on next login.
- Dismissal state is stored in the database; dismissed notifications must not reappear.

## Club Registry

- There are exactly 12 academic clubs (course-mapped) and 22 non-academic clubs (open to all students), for 34 total. The registry is maintained by the Administrator and is the authoritative source for auto-enrollment and the AI chatbot's recommendations.
- The four SCC colleges for enrollment routing: College of Teacher Education, Arts and Sciences; College of Business Education (CBE); College of Criminology (COC); College of Computing Studies (CCS).

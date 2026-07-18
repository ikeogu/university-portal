# Handoff: Automated Result Statement Portal

## Overview
A departmental result-management system for a Nigerian university (5.0 CGPA system). It lets:
1. The **Exam Officer / HoD** onboard students with bio-data, manage lecturers and courses, bulk-upload data, and produce the **Master Mark Sheet** listing eligible graduating students in batches (sets).
2. **Lecturers** enter CA/exam scores for their allocated courses and print official score sheets.
3. **Students** (public, no account) check per-semester results by matriculation number and print an official *Statement of Student's Academic Results*.

Only the HoD and Exam Officer have admin access. Lecturers see only score entry for their own courses.

## About the Design Files
`Result Portal.dc.html` is a **design reference created in HTML** — a working prototype showing intended look, flows and behavior. It is NOT production code. Recreate these screens in the target codebase's environment (e.g. React/Next.js + a real backend) using its established patterns. If no codebase exists yet, a sensible default stack: **Next.js + PostgreSQL (Prisma) + Auth (role-based)**, with server-side spreadsheet parsing (e.g. SheetJS) for bulk uploads.

## Fidelity
**High-fidelity.** Colors, typography, spacing, copy and print formats are final and should be recreated pixel-perfectly. The demo data (students, scores, the simulated file-upload results) is placeholder — replace with real persistence and real file parsing.

## Roles & Permissions
| Role | Access |
|---|---|
| Public (student) | Landing, result check (mat no + optional DOB), own result view, print statement |
| Lecturer | Sign-in, dashboard of allocated courses per semester, score entry (CA/exam), print course score sheet |
| Exam Officer / HoD | Everything above plus admin: students, lecturers & allocation, courses, bulk uploads, master mark sheet |

## Screens
All app screens live in a mobile shell: max-width 480px, centered, background `#f7f8fb`, page background `#e7ebf3`, shadow `0 0 44px rgba(18,32,66,.10)`.

### 1. Landing (public)
- Full-height navy gradient `linear-gradient(175deg,#1c355f 0%,#16294f 58%,#111f3e 100%)`, white text.
- Crest circle (44px, `rgba(255,255,255,.12)` bg, 1.5px `rgba(255,255,255,.35)` border) + university name.
- Headline 31px/800/-0.5px letter-spacing: "Results without the queue."
- Subcopy 13.5px, line-height 1.65, 78% opacity.
- CTAs: "Check my result" (white pill button, navy text, 52px, radius 14px), "Staff sign-in" (outline, `rgba(255,255,255,.4)` border).
- Bottom feature panel: `rgba(255,255,255,.06)` bg, radius 26px 26px 0 0; three icon rows (36px icon tiles `rgba(255,255,255,.12)`, radius 11px) — Instant result statements / Print-ready documents / Graduating sets in batches.
- Elements stagger in (see Animations).

### 2. Student result check (public)
- Navy header card (radius 0 0 28px 28px) with back button, crest, department name.
- Floating white card (-26px overlap, radius 20px, shadow `0 8px 28px rgba(18,32,66,.10)`): mat-no input, optional DOB input (config flag), semester toggle (First/Second), error banner (`#b3261e` on `#fbeeed`), primary CTA "View my result".
- Secondary card linking to staff sign-in.
- Validation: mat no required; unknown mat no → error; DOB required when the `requireDob` flag is on.

### 3. Student result view
- Sticky white header w/ back. Navy student card: name, mat no, programme; chips (mode of study, state, set + level).
- Semester toggle. Course table: code+title / CU / mark / grade badge (grade colors below). Summary card: TCU, GPA, CGPA (green `#0a7d4f`).
- "Print result statement" → print preview overlay.

### 4. Staff sign-in
- Email + password fields (48px, radius 12px, border `#d5dbe8`, bg `#fbfcfe`); prototype quick-access buttons for Lecturer / Exam Officer / HoD. In production: real auth with roles.

### 5. Lecturer dashboard
- Navy header (radius 0 0 24px 24px), greeting, "Lecturer" chip, sign out.
- Semester toggle; list of allocated courses for that semester (card per course: code 15px/800, title, "x units · n students registered"). Empty state (dashed border card) when none.

### 6. Score entry
- Sticky header with course code/title/semester. Table: # / student / CA input (max 30) / Exam input (max 70) / total / grade badge. Grades recompute live; inputs clamp 0..cap.
- Sticky footer: "Save scores" (primary) + "Print score sheet" (outline).

### 7. Admin (Exam Officer / HoD) — 5 bottom tabs (56px bar, active tab: navy text + 2.5px top border)
- **Students**: search, "+ Onboard", "⬆ Bulk"; stat cards (Students / Courses / Lecturers counts); student list rows (name, mat, mode, state, set) → opens result view.
- **Lecturers**: card per lecturer with role chip; course-code chips toggle allocation (filled navy = assigned); add-lecturer input; bulk upload button.
- **Courses**: per-semester lists with credit-unit badges and assigned lecturer names; totals per semester; "Add a course" form (code, title, CU select 1/2/3/4/6, semester select); bulk upload button.
- **Upload**: type chips (Students / Lecturers / Courses / Semester scores) → 4-stage flow: empty dropzone (dashed `#b9c4da`) → file card with column-mapping chips → processing spinner (~1.1s) → success card with contextual next actions. Column mappings per type:
  - Students: A Mat No, B Full name, C DOB, D State of origin, E Marital status, F Mode of study
  - Lecturers: A Full name, B Role, C Course codes (comma-separated, auto-assign)
  - Courses: A Code, B Title, C Credit units, D Semester
  - Scores: A Mat No, B Name, C+ CA/Exam per course
  - Duplicate keys (mat no / course code / name) are skipped and reported.
- **Master**: set chips (e.g. 2021 SET / 2022 SET), class-distribution bar (stacked, animated), class-count chips, ranked table (S/N, student, CGPA, class). Print → full master sheet.

### 8. Onboard student
- Form: Full name (surname first), mat no, DOB, state of origin, marital status (Single/Married), mode of study (Full time/Part time). Validation: name+mat required, duplicate mat rejected. Set is derived from mat no (`U2022/…` → 2022 set).

### 9. Print overlays (Times New Roman, white sheets on `#dfe4ee` desk, toolbar hidden on print)
- **Statement of Student's Academic Results** (A4 portrait): university header, bio grid (FULL NAMES, DOB, MAT NO, STATE OF ORIGIN, Programme, Mode of Study, Marital Status, Academic Year), grading key box, Semester I & II tables (CODE/TITLE/CU/CA/EXAM/MARK/GRADE/GP/QP), TCU/TQP/GPA lines, CGPA, Exam Officer + HoD signature lines, disclaimer. Failed grades printed red `#b3261e`.
- **Course Score Sheet** (A4 portrait): course meta, S/N/MAT/NAME/CA/EXAM/TOTAL/GRADE table, lecturer + HoD signatures.
- **Master Mark Sheet** (landscape, 1160px): title "MASTER MARK SHEET FOR THE AWARD OF …", overall summary counts (zero-padded, e.g. "02"), one table with batch header rows per set ("2021 SET", "2022 SET"), columns S/N, MAT. NO., NAME, YEAR I–IV (QP / CU), TQP / TCU, CGPA, CLASS (abbrev 1st, 2:1, 2:2, 3rd, Pass); HoD, Dean, External Examiner signatures.

## Business Rules (Nigeria 5.0 system)
- Mark = CA (0–30) + Exam (0–70).
- Grades: A 70–100 → 5 · B 60–69 → 4 · C 50–59 → 3 · D 45–49 → 2 · E 40–44 → 1 · F 0–39 → 0.
- QP = GP × CU. GPA = ΣQP ÷ ΣCU per semester. CGPA = cumulative ΣQP ÷ ΣCU.
- Class of degree: ≥4.50 First Class · 3.50–4.49 2nd Upper · 2.40–3.49 2nd Lower · 1.50–2.39 3rd · 1.00–1.49 Pass.
- Courses belong to a semester and have credit units; only registered courses count.
- Master sheet groups graduating students by set (entry year from mat no), default ordered by CGPA desc (configurable to mat-no order).

## Interactions & Animations
- Screen transitions: `screenin` — fade + 10px rise, 0.3s ease (`@keyframes screenin{from{opacity:0;transform:translateY(10px)}}`); landing hero staggers with delays 0 / .08s / .15s / .22s / .3s.
- Cards/success states: `popin` — fade + scale from .94, 0.25–0.3s ease.
- Upload processing: 40px spinner (3.5px ring, `#e7ebf3` with navy top) `spin .8s linear infinite`, ~1.1s before success.
- Master distribution bar segments grow in via flex-grow animation `barin .7s ease`.
- All buttons: `transition: background .18s, border-color .18s, color .18s, transform .12s`; `:active { transform: scale(.97) }`.
- Toast: dark pill (`#17233f`), bottom-center, `toastin .25s`, auto-dismiss 2.4s — used for saves, allocations, additions.
- Focus: 2px navy outline on inputs/selects.

## State Management
Entities: `students {mat, name, dob, stateOfOrigin, maritalStatus, mode, set}`, `lecturers {name, role, courseCodes[]}`, `courses {code, title, cu, semester}`, `scores[semester][mat][courseCode] = {ca, exam}`, session `user {name, role}`.
UI state: route, active semester, active admin tab, upload type + stage, selected set, search query, form fields, toast.
Config flags: `requireDob` (bool), `masterSort` ('cgpa' | 'matno').
In production: persist entities in a database; derive grades/GPA/CGPA/class server-side; audit-log score changes; per-role authorization on every mutation.

## Design Tokens
- Font: **Plus Jakarta Sans** (Google Fonts, 400–800); print documents use **Times New Roman**.
- Navy dark `#1c355f` · primary `#22407e` · primary hover `#1a3266` · ink `#17233f` · body text `#44506e` · muted `#66708a` · faint `#8c95ab` / `#9aa3b8`.
- Surfaces: page `#e7ebf3` · shell `#f7f8fb` · card `#fff` · input bg `#fbfcfe` · borders `#e7ebf3`, `#dde3ee`, inputs `#d5dbe8` · table header bg `#f2f5fa` · tint `#eef2fa`.
- Semantic: success `#0a7d4f` on `#e7f4ed` · error `#b3261e` on `#fbeeed`.
- Grade badge colors: A `#0a7d4f`, B `#15697c`, C `#22407e`, D `#8a5b00`, E `#a34c0a`, F `#b3261e`.
- Radii: cards 14–20px, buttons 11–14px, inputs 12px, chips 999px. Spacing rhythm: 16px screen gutters, 8–10px gaps.
- Type scale: 10–11.5px labels (uppercase, 600–700, .4px tracking), 12.5–13.5px body, 15px titles, 17–18px headers, 31px landing headline.

## Assets
None required — the crest is a typographic "US" circle placeholder; replace with the real university crest. Icons are text glyphs; swap for a proper icon set (e.g. Lucide) in production.

## Files
- `Result Portal.dc.html` — the complete prototype (all screens, logic, demo data, print layouts).
- `uploads/Result Statement Sample for Full Time Undergraduate Students.pdf` — official statement format reference.
- `uploads/Sample Master Mark Sheet.xlsx` — official master mark sheet format reference (batching + summary).

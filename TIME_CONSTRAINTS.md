# WFH Attendance Portal — Time Constraints

## Clock Action Windows

| Action             | Window              | Condition                        | Message When Outside Window                              |
|--------------------|---------------------|----------------------------------|----------------------------------------------------------|
| **AM In**          | 7:45 AM – 8:15 AM  | —                                | "Clock-in unavailable at this time."                     |
| **AM Out**         | 12:00 PM            | Employee has AM In               | "AM Out is only available at 12:00 PM."                  |
| **PM In (fallback)** | 12:01 PM          | Employee missed AM In            | "Employees with AM In cannot use 12:01 PM for PM In."   |
| **PM In (regular)**| 1:00 PM – 1:15 PM  | All employees                    | "PM In is only allowed from 1:00 PM to 1:15 PM."        |
| **PM In lock**     | After 1:15 PM       | Everyone                         | "PM In is locked after 1:15 PM."                         |
| **PM Out**         | 5:00 PM – 6:00 PM  | —                                | "PM Out is only available from 5:00 PM to 6:00 PM."     |
| **PM Out lock**    | After 6:00 PM       | Everyone                         | "PM Out is locked after 6:00 PM."                        |

## AM Status Classification (AM In)

| Status      | Time Range           |
|-------------|----------------------|
| **On Time** | 7:45 AM – 8:00 AM   |
| **Grace**   | 8:01 AM – 8:15 AM   |
| **Late**    | After 8:15 AM        |

## Absent AM

- Triggered when no AM In is recorded and current time is **past 8:15 AM**.
- Employee skips AM In and AM Out steps, and starts from **PM In (Step 3)**.
- At **12:01 PM**, absent-AM employees may log PM In (fallback window).
- At **1:00 PM – 1:15 PM**, absent-AM employees who missed 12:01 PM may still log PM In.
- Employees who used the 12:01 PM fallback already count as PM In and **cannot** log again at 1:00 PM.
- Employees **with** an AM In record are **blocked** from using 12:01 PM as PM In.

## Sequence Validation

Every clock action is validated against the employee's existing logs for the same date:
1. AM In → 2. AM Out → 3. PM In → 4. PM Out
- Steps must be completed in order (except absent-AM skip to step 3).
- Duplicate steps are rejected.

## Day Restriction (Currently Disabled)

- Clock actions were originally restricted to **Fridays only** (ISO day 5).
- This check is currently **commented out** for development/debugging.
- Friday-based restrictions now apply **only** to IDLAR uploading/editing.
- Locations of the disabled checks:
  - `includes/functions.php` → `isWithinWindow()`
  - `includes/functions.php` → `isTimeInWindowPassed()`
  - `employee/clock.php` → `$isFriday` variable

## IDLAR Edit Window

- Opens **Friday 5:00 PM**, closes **Monday 11:59 PM**.
- Defined in `includes/functions.php` → `checkAccomplishmentWindow()`.
- Enforced on: accomplishment save/delete, leave marking (IDLAR), attachment upload/delete.
- On Monday, the active week is still the **previous ISO week** (containing the Friday that opened the window).
- Edits to previous weeks are blocked; only the current active week is editable.

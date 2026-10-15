# MBG-Track — project-root agent router (ANTIGRAVITY)

## Non-negotiable protected paths
- Actual Laravel root: `P:\Tugas PPL MBG`.
- `P:\Tugas PPL MBG\MBG-Track-Documentation` is read-only.
- `P:\Tugas PPL MBG\MBG-Track-Documentation-v3` is read-only.
- `P:\Tugas PPL MBG\MBG-Track-Documentation-v4-RepoFirst` is read-only.
- NEVER run installer/formatter/cleanup/migration with any of these as target.

## Repository-first mandatory rules for UI, mobile, copy, a11y, comments
Read REAL upstream core from `.agents/skills/antislop/SKILL.md` before UI work. Also load relevant ORIGINAL skill folders: `antislop-ui`, `antislop-layoutmobile`, `antislop-human`, `antislop-copywriting`; `antislop-code` for comment-only cleanup. If skill missing, report NOT INSTALLED; do not silently substitute an adapted summary.

Read `MBG-Track-Documentation-v4-RepoFirst/DESIGN.md` and only relevant page spec from `docs/pages/` for each task. For an existing UI audit, default to upstream **After** and request approval before fixing identified items. For a newly built page use **During**. Run original 4-block Delivery Gate with evidence before calling work complete.

## Business authority
12 PBI and five internal roles + public are frozen. Read `docs/product/PBI-BACKLOG.md`, role matrix, status transitions and database dictionary before altering Laravel business logic. Protect per-SPPG/school scopes and private GPS/evidence; labels distinguish demo vs live. Do not treat demo registered beneficiaries as confirmed daily portions.

## Current development state
Existing Laravel project in root, Sprint 0 and PBI-01 reportedly done. **Verify actual Git/tests before saying complete.** Next scoped work PBI-02; no re-bootstrap. Avoid mass refactoring before approval.

## Logging
Use `P:\Tugas PPL MBG\HANDOFF-LOG.md` and `DECISION-LOG.md` outside protected docs. Never edit logs inside documentation.

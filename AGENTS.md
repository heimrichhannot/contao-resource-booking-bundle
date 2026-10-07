# AGENTS.md

Notes for AI agents and reviewers working on this bundle.

## Intended behavior that looks like a bug

- **Resources are unique to their archive.** `BlockingBookingQuery::hasOverlap()` only checks bookings of the same
  booking archive (`b.pid`). This is intended: do not report or "fix" it as double booking across booking archives.
- **`huh:rb:pipeline:process` is a debugging tool.** `ProcessUnfinishedBookingsCommand` is only run by hand to debug
  the booking pipeline and never as a cron job. Nothing retries the pipeline automatically, so do not treat the command
  as a retry mechanism or design features around it running on a schedule.

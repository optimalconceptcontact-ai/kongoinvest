---
paths:
  - 'app/Models/**'
---

# Models

## Use MatchModel, never Match
`match` is a PHP 8 reserved keyword (case-insensitive) — a class named `Match` cannot compile, and `Match::` is a parse error. The match model lives at App\Models\MatchModel (table `matches`). Always import/use MatchModel in controllers, actions, tests, data objects, and policies; grep for `Match::` and `App\Models\Match` if files fail to parse.

# `Match` is a reserved keyword — use `MatchModel`

PHP 8 treats `match` as a hard keyword. A class literally named `Match` is a
parse error: `class Match {}`, `Match::query()`, `Match::class`, and
`Match $x` type hints all fail with `syntax error, unexpected token "::"`.

**Rule:** the matching domain model is `App\Models\MatchModel` (table
`matches`, factory `Database\Factories\MatchModelFactory`). Never generate
`use App\Models\Match;`, `Match::anything()`, `Match $param`, or a file
`app/Models/Match.php`.

**History:** a generator/session repeatedly re-created these files with the
broken `Match` name (July 2026 — reverted 6+ times). Sweep all classes,
jobs, controllers, policies, data objects, and tests with
`MatchModel` when touching this domain. Run
`php -l` on every touched PHP file before finishing; the parser error
surfaces in `package:discover`/`route:list` as
`unexpected token "::", expecting "("`.
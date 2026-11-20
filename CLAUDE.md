# CLAUDE.md

Claude Code reads this file automatically. The instructions for every AI agent live in `AGENTS.md`, imported below, so all agents follow the same rules.

@AGENTS.md

## Claude Code notes

- Read `README.md` for the user-facing behaviour of the library and `llms.txt` for a short summary.
- Before you report a change as done, run `composer test:strict` (or `composer test` on PHP < 8.2), `composer analyse`, and `composer test` in `examples/random-app-example`. Report the PHP and PHPUnit versions you used: results can differ between PHP 7 and PHP 8.
- If you cannot test a PHP version that the change affects, say so instead of assuming it works.

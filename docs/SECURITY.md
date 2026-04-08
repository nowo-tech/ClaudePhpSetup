# Security — Claude PHP Setup

## Scope

Claude PHP Setup is a **development-time** Composer plugin that runs an interactive CLI and **writes** generated markdown and config files under paths you choose (`.claude/`, `CLAUDE.md`, etc.). It reads `composer.json` and related project files to detect your stack.

## Attack surface

- **Filesystem writes**: The generator writes files only when you run the wizard; use `--overwrite` deliberately in trusted project trees.
- **Composer metadata**: Reads `composer.json` / `composer.lock`; malformed data could affect detection logic—mitigated by using Composer’s APIs and stable parsing.
- **Secrets**: Do not commit API keys or tokens into generated `CLAUDE.md` or `.claude/` files; treat them like any other project docs.

## Secrets

- Never commit tokens, passwords, or private repository credentials in examples or generated files.

## Dependencies

- Run `composer audit` before releases.
- Keep PHP and Composer constraint ranges aligned with supported versions.

## Reporting a vulnerability

Report security issues **privately** to the maintainers (see `composer.json` authors). Do not disclose exploit details in public issues before a fix is available.

## Release security checklist (12.4.1)

Before tagging a release, confirm:

| Item | Notes |
|------|--------|
| **SECURITY.md** | This document is current. |
| **`.gitignore` and `.env`** | `.env` ignored; no secrets in repo. |
| **No secrets in repo** | No tokens or passwords in tracked files. |
| **Plugin / installer** | Installation does not ship secrets. |
| **Input / output** | CLI prompts and paths are user-controlled in a trusted dev context. |
| **Dependencies** | `composer audit` addressed. |
| **Logging** | No secrets in logs. |
| **Cryptography** | N/A; signing keys not embedded. |
| **Permissions / exposure** | Runs as local user/CI; document in README. |
| **Limits / DoS** | Very large `composer.json` files may slow parsing; document for CI timeouts. |

Record confirmation in the release PR or tag notes.

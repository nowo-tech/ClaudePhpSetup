# Security — Claude PHP Setup

## Table of contents

- [Scope](#scope)
- [Attack surface](#attack-surface)
- [Threat model](#threat-model)
- [Mitigations](#mitigations)
- [Secrets](#secrets)
- [Logging](#logging)
- [Dependencies](#dependencies)
- [Reporting a vulnerability](#reporting-a-vulnerability)
- [Release security checklist (12.4.1)](#release-security-checklist-1241)

## Scope

Claude PHP Setup is a **development-time** Composer plugin that runs an interactive CLI and **writes** generated markdown and config files under paths you choose (`.claude/`, `CLAUDE.md`, etc.). It reads `composer.json` and related project files to detect your stack.

## Attack surface

- **Filesystem writes**: The generator writes files only when you run the wizard; use `--overwrite` deliberately in trusted project trees.
- **Composer metadata**: Reads `composer.json` / `composer.lock`; malformed data could affect detection logic—mitigated by using Composer’s APIs and stable parsing.
- **Secrets**: Do not commit API keys or tokens into generated `CLAUDE.md` or `.claude/` files; treat them like any other project docs.

## Threat model

| Threat | Applies? | Notes |
|--------|----------|-------|
| Injection / command execution | Low | The only shell calls are fixed `stty` invocations used for arrow-key prompts on a TTY; the saved terminal mode is passed back through `escapeshellarg()`. No user answer reaches a shell. |
| Path traversal / arbitrary file write | Low | Output paths are built from the project directory plus fixed names (`CLAUDE.md`, `.claude/commands/<key>.md`, …). `<key>` values come from built-in whitelists, never from free text. |
| Overwriting user files | Medium (by design) | The wizard writes into the project; existing files are skipped unless `--overwrite` or an explicit "Overwrite existing files?" answer. |
| Malicious `composer.json` | Low | Parsed with `json_decode()` into arrays only (no object hydration, no `unserialize()`); unexpected shapes fall back to defaults. |
| Deserialization | N/A | No `unserialize()`, no PHP object input. |
| XSS / CSRF / SSRF / authz | N/A | No HTTP surface, no outgoing requests, no web UI. |
| Supply chain | Low | Composer plugin activates on install; it only prints a banner and never runs the wizard automatically. |
| Denial of service | Low | Single-user CLI; very large `composer.json` files only slow detection. |

## Mitigations

- **Opt-in execution**: nothing is generated unless the developer runs `vendor/bin/claude-php-setup` and confirms "Generate these files now?".
- **Fixed output layout**: generated file names are whitelisted keys; no path is derived from typed input.
- **No silent overwrite**: existing files are reported as `Skipped (exists)` unless overwriting is requested; write failures are reported per file and the CLI exits with code 1.
- **Safe parsing**: `composer.json` is read as JSON arrays; detection falls back to defaults on malformed data.
- **Terminal handling**: raw TTY mode is always restored in a `finally` block after interactive prompts.

## Secrets

- Never commit tokens, passwords, or private repository credentials in examples or generated files.

## Logging

The CLI prints progress (detected project name, files created/skipped/overwritten/failed) to the terminal only. It writes no log files, never prints file contents, environment variables or tokens, and the generated templates contain no secrets.

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
| **AI security audit (REQ-SEC-004)** | Latest recorded grade **Pass (good)** in the nowo-tech bundles security analysis catalog (`BUNDLES_SECURITY_ANALYSIS.md`); re-run when the CLI gains new inputs or outputs. |

Record confirmation in the release PR or tag notes.

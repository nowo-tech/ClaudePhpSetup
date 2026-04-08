# Release process

1. Update [CHANGELOG.md](CHANGELOG.md) with the version and date.
2. Run `make release-check` and fix any reported issues.
3. Commit your changes.
4. Create an annotated tag: `git tag -a vX.Y.Z -m "Release vX.Y.Z"`.
5. Push commits and tags: `git push && git push --tags`.
6. Confirm the GitHub Release workflow created the release for the tag (or create it manually).
7. Ensure Packagist has picked up the new tag (if the package is registered).

# GitHub Workflow Actions

A collection of reusable GitHub Actions workflows for automating release processes.

## Available Actions

### Pre-Release Checks

Performs validation checks before creating a release:
- Verifies that a valid version exists in `composer.json`
- Ensures a changelog entry exists for the version in `CHANGELOG.md`
- Confirms the version tag doesn't already exist

```yaml
name: Pre-Release Checks

on:
  pull_request:
    branches: [ main ]
    types: [ opened, synchronize, reopened ]

jobs:
  pre-release-checks:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      
      - name: Run Pre-Release Checks
        uses: whilesmart/workflows/package/pre-release@main
        with:
          token: ${{ secrets.GITHUB_TOKEN }}
```

### Create Release

Automates the release process:
- Extracts version from `composer.json`
- Gets release notes from `CHANGELOG.md`
- Creates a release branch
- Creates and pushes a version tag
- Creates a draft GitHub release

```yaml
name: Create Release

permissions:
  contents: write
  
on:
  workflow_dispatch:

jobs:
  create-release:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      
      - name: Create Release
        uses: whilesmart/workflows/package/release@main
        with:
          token: ${{ secrets.GITHUB_TOKEN }}
          package: your-vendor/your-package-name
```

## Requirements

To use these actions, your project should have:

1. A `composer.json` file with a `package-version` field:
   ```json
   {
     "name": "your-vendor/your-package",
     "package-version": "1.0.0",
     "description": "Your package description"
   }
   ```

2. A `CHANGELOG.md` file with entries in this format:
   ```markdown
   # Changelog

   ## [1.0.0] - 2025-09-25
   
   ### Added
   - Initial release
   ```

## License

This project is open-sourced software.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.
# GitHub Reusable Workflows

A collection of reusable GitHub Actions organized by language/platform.

## Structure

```
workflows/
├── go/
│   └── release/          # Build & release Go binaries
├── php/
│   ├── pre-release/      # Pre-release validation
│   └── release/          # Create PHP package release
├── javascript/
│   ├── ci/               # Lint, test, typecheck, build for Node.js/npm libs
│   └── release/          # Conventional-commit-driven npm release
├── package/              # [DEPRECATED] Use php/ instead
│   ├── pre-release/
│   └── release/
└── README.md
```

> **Note:** The `package/` path is deprecated. Migrate to `php/` when convenient.
> Both paths will continue to work, but new features will only be added to `php/`.

---

## Go Workflows

### Release (`go/release`)

Builds cross-platform Go binaries and creates a GitHub release with downloadable assets.

**Features:**
- Cross-compilation for Linux, macOS, Windows (amd64, arm64)
- Automatic version injection via ldflags
- Archive creation (tar.gz for Unix, zip for Windows)
- SHA256 checksums
- Optional CHANGELOG.md parsing
- Installation instructions in release notes

**Usage (supports both manual dispatch and tag push):**

```yaml
name: Release

on:
  workflow_dispatch:
    inputs:
      version:
        description: 'Version to release (e.g., 0.1.0)'
        required: true
  push:
    tags:
      - 'v*'

permissions:
  contents: write

jobs:
  release:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Create Release
        uses: whilesmart/workflows/go/release@main
        with:
          token: ${{ secrets.GITHUB_TOKEN }}
          binary_name: myapp
          build_path: ./cmd/myapp
          version: ${{ github.event.inputs.version }}
```

**Inputs:**

| Input | Required | Default | Description |
|-------|----------|---------|-------------|
| `token` | Yes | - | GitHub token for authentication |
| `binary_name` | Yes | - | Name of the output binary |
| `version` | No | - | Version to release. If not provided, extracts from git tag |
| `build_path` | No | `.` | Path to main.go directory |
| `go_version` | No | `1.21` | Go version to use |
| `platforms` | No | See below | JSON array of target platforms |
| `ldflags` | No | - | Additional ldflags for build |
| `draft` | No | `false` | Create release as draft |

**Default platforms:**
```json
["linux/amd64", "linux/arm64", "darwin/amd64", "darwin/arm64", "windows/amd64", "windows/arm64"]
```

**Custom platforms:**

Only include the platforms you need:
```yaml
platforms: '["linux/amd64", "linux/arm64", "darwin/amd64", "darwin/arm64"]'
```

Available platforms: `linux/amd64`, `linux/arm64`, `darwin/amd64`, `darwin/arm64`, `windows/amd64`, `windows/arm64`

**Two ways to release:**

1. **Manual dispatch** (GitHub UI):
   - Go to Actions → Release → Run workflow
   - Enter version (e.g., `0.1.0`)
   - Tag is created automatically

2. **Tag push** (traditional):
   ```bash
   git tag v0.1.0
   git push origin v0.1.0
   ```

---

## PHP Workflows

### Pre-Release Checks (`php/pre-release`)

Validates project readiness before creating a release.

**Checks:**
- Valid version in `composer.json`
- Changelog entry exists for the version
- Version tag doesn't already exist

**Usage:**

```yaml
name: Pre-Release Checks

on:
  pull_request:
    branches: [main]

jobs:
  pre-release-checks:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Run Pre-Release Checks
        uses: whilesmart/workflows/php/pre-release@main
        with:
          token: ${{ secrets.GITHUB_TOKEN }}
```

### Release (`php/release`)

Creates a release for PHP/Composer packages.

**Features:**
- Extracts version from `composer.json`
- Parses release notes from `CHANGELOG.md`
- Creates release branch and tag
- Creates draft GitHub release

**Usage:**

```yaml
name: Create Release

on:
  workflow_dispatch:

permissions:
  contents: write

jobs:
  create-release:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Create Release
        uses: whilesmart/workflows/php/release@main
        with:
          token: ${{ secrets.GITHUB_TOKEN }}
          package: your-vendor/your-package
```

**Requirements:**

1. `composer.json` with `package-version` field:
   ```json
   {
     "name": "your-vendor/your-package",
     "package-version": "1.0.0"
   }
   ```

2. `CHANGELOG.md` with version entries:
   ```markdown
   ## [1.0.0] - 2025-01-15

   ### Added
   - Initial release
   ```

---

## JavaScript / Node.js Workflows

### CI (`javascript/ci`)

Runs lint, tests, optional typecheck, and optional build for Node.js/npm libraries.

**Usage:**

```yaml
name: CI

on:
  pull_request:
    branches: [main]
  push:
    branches: [main]

jobs:
  ci:
    runs-on: ubuntu-latest
    steps:
      - name: JavaScript CI
        uses: whilesmart/workflows/javascript/ci@main
        with:
          token: ${{ secrets.GITHUB_TOKEN }}
          # Optional overrides:
          # node_version: '20.x'
          # working_directory: '.'
          # lint_script: 'lint'
          # run_tests: 'true'
```

**Requirements:**

1. `package.json` in the working directory.
2. NPM scripts:
   - `lint` (or the script name passed via `lint_script`).
   - `typecheck` (mandatory; can be a no-op if you are not using TypeScript).
   - `build` (mandatory; used to validate that the library can be built).
   - `test` or `test:ci` (required when `run_tests` is set to `'true'`, which is the default).

---

### Release (`javascript/release`)

Creates a new npm release for Node.js libraries based on conventional commits.

**Features:**
- Determines `patch`/`minor`/`major` bump from commit history.
- Updates `package.json` version and prepends an entry to `CHANGELOG.md`.
- Commits changes, creates a `vX.Y.Z` tag, publishes to npm, and creates a GitHub Release.
- Supports `dry_run` mode and custom npm `dist-tag` (e.g., `latest`, `next`, `beta`).

**Usage (release from main):**

```yaml
name: Release

on:
  push:
    branches: [main]

permissions:
  contents: write

jobs:
  release:
    runs-on: ubuntu-latest
    steps:
      - name: JavaScript Release
        uses: whilesmart/workflows/javascript/release@main
        with:
          token: ${{ secrets.GITHUB_TOKEN }}
          npm_token: ${{ secrets.NPM_TOKEN }}
          # Optional overrides:
          # node_version: '20.x'
          # working_directory: '.'
          # release_branch: 'main'
          # npm_registry: 'https://registry.npmjs.org/'
          # dist_tag: 'latest'
          # dry_run: 'false'
```

**Optional: pre-release / next channel**

```yaml
on:
  push:
    branches: [next]

jobs:
  release-next:
    runs-on: ubuntu-latest
    steps:
      - name: JavaScript Release (next)
        uses: whilesmart/workflows/javascript/release@main
        with:
          token: ${{ secrets.GITHUB_TOKEN }}
          npm_token: ${{ secrets.NPM_TOKEN }}
          release_branch: 'next'
          dist_tag: 'next'
```

**Requirements:**

1. `package.json` with a valid `version` field.
2. Conventional commits used on the release branch (`feat`, `fix`, `BREAKING CHANGE`, etc.).
3. `CHANGELOG.md` (optional but recommended; it will be created or prepended to if present).
4. `NPM_TOKEN` secret configured with publish permissions for the target npm registry.

---

## Adding New Languages

To add workflows for a new language:

1. Create a directory: `{language}/`
2. Add action subdirectories: `{language}/release/`, `{language}/test/`, etc.
3. Create `action.yml` in each subdirectory
4. Update this README

## License

MIT

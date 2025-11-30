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

## Adding New Languages

To add workflows for a new language:

1. Create a directory: `{language}/`
2. Add action subdirectories: `{language}/release/`, `{language}/test/`, etc.
3. Create `action.yml` in each subdirectory
4. Update this README

## License

MIT

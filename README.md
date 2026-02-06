# GitHub Reusable Workflows

A collection of reusable GitHub Actions organized by language/platform.

## Structure

```
workflows/
├── go/
│   └── release/              # Build & release Go binaries
├── js/
│   ├── pre-release/          # Pre-release validation for JS projects
│   └── release/              # Create JS/Node package or app release
├── python/
│   ├── pre-release/          # Pre-release validation for Python projects
│   └── release/              # Create Python package or app release
├── php/
│   ├── pre-release/          # Pre-release validation
│   ├── release/              # Create PHP package or app release
│   └── laravel/
│       └── release/          # Laravel-specific release with vendor
├── package/                  # [DEPRECATED] Use php/ instead
│   ├── pre-release/
│   └── release/
└── README.md
```

> **Note:** The `package/` path is deprecated. Migrate to `php/` when convenient.

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

## JS Workflows

### Pre-Release Checks (`js/pre-release`)

Validates project readiness before creating a release for JavaScript/Node.js projects.

**Checks:**
- Valid version in `package.json`
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
        uses: whilesmart/workflows/js/pre-release@main
        with:
          token: ${{ secrets.GITHUB_TOKEN }}
```

### Release (`js/release`)

Creates a release for JavaScript/Node.js projects (packages or standalone apps).

**Features:**
- Extracts version from `package.json`
- Parses release notes from `CHANGELOG.md`
- Auto-detects package manager (npm, yarn, pnpm)
- Auto-detects and runs build script (or use custom build command)
- Optional: Create deployable archive (excludes dev files)
- Creates release branch and tag
- Creates draft GitHub release

**Usage (npm package):**

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
        uses: whilesmart/workflows/js/release@main
        with:
          token: ${{ secrets.GITHUB_TOKEN }}
          package: your-package-name
```

**Usage (standalone app with archive):**

```yaml
- name: Create Release
  uses: whilesmart/workflows/js/release@main
  with:
    token: ${{ secrets.GITHUB_TOKEN }}
    package: your-app-name
    release_type: app
    create_archive: 'true'
    build_command: npm run build:prod
    deploy_instructions: |
      Download the attached archive and extract to your server.

      ```bash
      unzip your-app-v1.0.0.zip -d /var/www/your-app
      cd /var/www/your-app
      npm install --production
      npm start
      ```
```

**Inputs:**

| Input | Required | Default | Description |
|-------|----------|---------|-------------|
| `token` | Yes | - | GitHub token for authentication |
| `package` | Yes | - | Package/app name |
| `build_command` | No | - | Custom build command. Auto-detects `build` script if not provided |
| `node_version` | No | `20` | Node.js version to use |
| `skip_build` | No | `false` | Skip the build step entirely |
| `release_type` | No | `package` | `package` (npm install instructions) or `app` (deployment instructions) |
| `create_archive` | No | `false` | Create deployable zip archive (for apps) |
| `exclude_patterns` | No | - | Additional patterns to exclude from archive |
| `deploy_instructions` | No | - | Custom deployment instructions for release notes |

**Archive Exclusions:**

When `create_archive: 'true'`, these are excluded by default:
- `.git/`, `.github/`, IDE files
- `tests/`, `__tests__/`, coverage files
- `node_modules/`
- CI/CD configs, Docker files

Source files are **included** by default. Use `.releaseignore` or `exclude_patterns` to exclude specific directories if needed.

**Requirements:**

1. `package.json` with `version` field:
   ```json
   {
     "name": "your-package",
     "version": "1.0.0"
   }
   ```

2. `CHANGELOG.md` with version entries:
   ```markdown
   ## [1.0.0] - 2025-01-15

   ### Added
   - Initial release
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

Creates a release for PHP/Composer packages or standalone applications.

**Features:**
- Extracts version from `composer.json`
- Parses release notes from `CHANGELOG.md`
- Optional: Install production dependencies
- Optional: Run custom build commands
- Optional: Create deployable archive (excludes dev files)
- Creates release branch and tag
- Creates draft GitHub release

**Usage (composer package):**

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

**Usage (PHP app with vendor and archive):**

```yaml
- name: Create Release
  uses: whilesmart/workflows/php/release@main
  with:
    token: ${{ secrets.GITHUB_TOKEN }}
    package: your-app-name
    release_type: app
    install_dependencies: 'true'
    create_archive: 'true'
    php_version: '8.2'
    deploy_instructions: |
      Download the attached archive and extract to your web server.

      ```bash
      unzip your-app-v1.0.0.zip -d /var/www/your-app
      ```
```

**Inputs:**

| Input | Required | Default | Description |
|-------|----------|---------|-------------|
| `token` | Yes | - | GitHub token for authentication |
| `package` | Yes | - | Package/app name |
| `build_command` | No | - | Custom build command (e.g., `npm run build` for assets) |
| `php_version` | No | `8.2` | PHP version (only used when build features enabled) |
| `install_dependencies` | No | `false` | Run `composer install --no-dev --optimize-autoloader` |
| `release_type` | No | `package` | `package` (composer require) or `app` (deployment instructions) |
| `create_archive` | No | `false` | Create deployable zip archive (for apps) |
| `exclude_patterns` | No | - | Additional patterns to exclude from archive |
| `deploy_instructions` | No | - | Custom deployment instructions for release notes |

### Laravel Release (`php/laravel/release`)

Creates a production-ready release specifically for Laravel applications.

**Features:**
- Extracts version from `composer.json`
- Parses release notes from `CHANGELOG.md`
- Installs production composer dependencies (no-dev)
- Optional: Builds frontend assets (npm/yarn/pnpm)
- Creates deployable archive with vendor (excludes dev files)
- Laravel-specific exclusions (storage/logs, cache, etc.)
- Laravel deployment instructions in release notes
- Creates release branch, tag, and draft GitHub release

**Usage:**

```yaml
name: Create Laravel Release

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
        uses: whilesmart/workflows/php/laravel/release@main
        with:
          token: ${{ secrets.GITHUB_TOKEN }}
          package: your-app-name
          build_command: npm run build
```

**Inputs:**

| Input | Required | Default | Description |
|-------|----------|---------|-------------|
| `token` | Yes | - | GitHub token for authentication |
| `package` | Yes | - | Application name |
| `build_command` | No | - | Frontend build command (e.g., `npm run build`) |
| `php_version` | No | `8.2` | PHP version to use |
| `node_version` | No | `20` | Node.js version for asset compilation |
| `exclude_patterns` | No | - | Additional patterns to exclude from archive |

**Laravel-specific exclusions:**
- Standard dev files (tests, CI configs, IDE files)
- `storage/logs/*`, `storage/framework/cache/*`, `storage/framework/sessions/*`
- `bootstrap/cache/*.php`
- `node_modules/`

**Requirements:**

1. `composer.json` with `version` or `package-version` field:
   ```json
   {
     "name": "your-vendor/your-app",
     "version": "1.0.0"
   }
   ```

2. `CHANGELOG.md` with version entries:
   ```markdown
   ## [1.0.0] - 2025-01-15

   ### Added
   - Initial release
   ```

---

## Python Workflows

### Pre-Release Checks (`python/pre-release`)

Validates project readiness before creating a release for Python projects.

**Checks:**
- Valid semver version in `VERSION` file
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
        uses: whilesmart/workflows/python/pre-release@main
        with:
          token: ${{ secrets.GITHUB_TOKEN }}
```

### Release (`python/release`)

Creates a release for Python packages or standalone applications.

**Features:**
- Extracts version from `VERSION` file
- Parses release notes from `CHANGELOG.md`
- Optional: Install production dependencies
- Optional: Run custom build commands
- Optional: Create deployable archive (excludes dev files, `__pycache__`, `.pyc`, etc.)
- Creates release branch and tag
- Creates draft GitHub release

**Usage (Python app):**

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
        uses: whilesmart/workflows/python/release@main
        with:
          token: ${{ secrets.GITHUB_TOKEN }}
          package: your-app-name
          release_type: app
```

**Usage (pip package):**

```yaml
- name: Create Release
  uses: whilesmart/workflows/python/release@main
  with:
    token: ${{ secrets.GITHUB_TOKEN }}
    package: your-package-name
    release_type: package
    build_command: python -m build
    python_version: '3.11'
```

**Usage (app with archive):**

```yaml
- name: Create Release
  uses: whilesmart/workflows/python/release@main
  with:
    token: ${{ secrets.GITHUB_TOKEN }}
    package: your-app-name
    release_type: app
    create_archive: 'true'
    install_dependencies: 'true'
    deploy_instructions: |
      Download the attached archive and extract to your server.

      ```bash
      unzip your-app-v1.0.0.zip -d /opt/your-app
      cd /opt/your-app
      pip install -r requirements.txt
      ```
```

**Inputs:**

| Input | Required | Default | Description |
|-------|----------|---------|-------------|
| `token` | Yes | - | GitHub token for authentication |
| `package` | Yes | - | Package/app name |
| `build_command` | No | - | Custom build command (e.g., `python -m build`) |
| `python_version` | No | `3.11` | Python version to use |
| `install_dependencies` | No | `false` | Install production dependencies |
| `requirements_file` | No | `requirements.txt` | Path to requirements file |
| `release_type` | No | `app` | `package` (pip install instructions) or `app` (deployment instructions) |
| `create_archive` | No | `false` | Create deployable zip archive (for apps) |
| `exclude_patterns` | No | - | Additional patterns to exclude from archive |
| `deploy_instructions` | No | - | Custom deployment instructions for release notes |

**Requirements:**

1. `VERSION` file with semver version:
   ```
   1.0.0
   ```

2. `CHANGELOG.md` with version entries:
   ```markdown
   ## [1.0.0] - 2025-01-15

   ### Added
   - Initial release
   ```

---

## Custom Exclusions

All release workflows support custom file exclusions via:

### `.releaseignore` file

Create a `.releaseignore` file in your project root (same format as `.gitignore`):

```
# Custom exclusions
docs/*
examples/*
*.md
!README.md
!CHANGELOG.md
```

### `exclude_patterns` input

Pass additional patterns directly:

```yaml
- name: Create Release
  uses: whilesmart/workflows/php/release@main
  with:
    token: ${{ secrets.GITHUB_TOKEN }}
    package: your-app
    create_archive: 'true'
    exclude_patterns: |
      docs/*
      examples/*
      *.md
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

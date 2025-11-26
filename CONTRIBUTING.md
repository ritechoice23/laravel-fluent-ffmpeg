# Contributing to Laravel Fluent FFmpeg

Thank you for considering contributing to Laravel Fluent FFmpeg! This document outlines the development workflow and guidelines.

## Development Setup

1. **Clone the repository**
```bash
git clone https://github.com/ritechoice23/laravel-fluent-ffmpeg.git
cd laravel-fluent-ffmpeg
```

2. **Install dependencies**
```bash
composer install
```

3. **Install FFmpeg** (if not already installed)
```bash
# Ubuntu/Debian
sudo apt-get install ffmpeg

# macOS
brew install ffmpeg

# Windows
# Download from https://ffmpeg.org/download.html
```

## Running Tests

```bash
# Run all tests
composer test

# Run tests with coverage
composer test-coverage

# Run specific test file
vendor/bin/pest tests/Unit/BuilderTest.php
```

## Code Style

We use Laravel Pint for code formatting:

```bash
composer format
```

## Static Analysis

Run PHPStan for static analysis:

```bash
composer analyse
```

## Contributing Guidelines

### Pull Requests

1. **Fork the repository** and create a new branch
2. **Write tests** for new features
3. **Ensure all tests pass** (`composer test`)
4. **Format code** (`composer format`)
5. **Run static analysis** (`composer analyse`)
6. **Update documentation** if needed
7. **Submit pull request** with clear description

### Commit Messages

Use clear, descriptive commit messages:

```
Add watermark filter support
Fix progress tracking for long videos
Update documentation for HLS streaming
```

### Adding New Features

When adding new features:

1. **Create a trait** in `src/Concerns/` for related methods
2. **Add tests** in `tests/Unit/Concerns/`
3. **Update documentation** in `docs/`
4. **Add examples** to README if it's a major feature

### Package Structure

```
src/
├── Actions/           # Single-responsibility actions
├── Builder/           # FFmpegBuilder class
├── Concerns/          # Traits for builder
├── Events/            # Laravel events
├── Exceptions/        # Custom exceptions
├── Facades/           # FFmpeg facade
├── Jobs/              # Queue jobs
└── MediaInfo/         # Media information classes

tests/
├── Unit/              # Unit tests
│   ├── Actions/
│   └── Concerns/
└── Feature/           # Feature tests (future)

docs/                  # Documentation files
```

### Testing Guidelines

- **Unit tests** for individual methods
- **Feature tests** for complete workflows
- **Mock FFmpeg** for CI/CD (no real binary needed)
- **Test edge cases** and error handling

Example test:

```php
it('can apply watermark filter', function () {
    $builder = new FFmpegBuilder;
    $builder->watermark('logo.png', ['position' => 'top-right']);

    expect($builder->getFilters())->toContain('overlay');
});
```

### Documentation

When updating documentation:

- Keep it **concise and scannable**
- Include **code examples**
- Update **README.md** for major features
- Add detailed docs in **docs/** directory

## Architecture Decisions

### Traits Over Inheritance

We use traits to organize functionality:
- `HasVideoOptions` - Video encoding options
- `HasAudioOptions` - Audio encoding options
- `HasFilters` - Video filters and effects
- `HasFormatOptions` - Output formats
- `HasMetadata` - Metadata handling
- `HasAdvancedOptions` - Advanced features
- `HasHelperMethods` - Helper methods

### Actions Pattern

Single-responsibility actions:
- `BuildFFmpegCommand` - Builds FFmpeg command string
- `ExecuteFFmpegCommand` - Executes command with Symfony Process
- `ProbeMediaFile` - Extracts media information

### Smart Defaults

Methods support optional parameters with config-based defaults:

```php
->videoCodec()        // Uses config default
->videoCodec('libx265')  // Custom value
```

## Questions?

- Open an issue for bugs or feature requests
- Start a discussion for questions
- Check existing issues before creating new ones

## License

By contributing, you agree that your contributions will be licensed under the MIT License.

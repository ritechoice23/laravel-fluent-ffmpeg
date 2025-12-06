# Text Overlay

Add text overlays to your videos with customizable styling, positioning, and timing.

## Basic Usage

Add simple text to a video:

```php
use Ritechoice23\FluentFFmpeg\Facades\FFmpeg;

FFmpeg::fromPath('input.mp4')
    ->withText('Hello World')
    ->save('output.mp4');
```

## Position Presets

Use predefined position presets:

```php
FFmpeg::fromPath('input.mp4')
    ->withText('Copyright 2024', [
        'position' => 'bottom-right',
    ])
    ->save('output.mp4');
```

Available positions:

-   `top-left`
-   `top-center`
-   `top-right`
-   `center`
-   `bottom-left`
-   `bottom-center` (default)
-   `bottom-right`

## Custom Position

Set exact coordinates:

```php
FFmpeg::fromPath('input.mp4')
    ->withText('Custom Position', [
        'position' => ['x' => 100, 'y' => 200],
    ])
    ->save('output.mp4');
```

You can also use FFmpeg expressions:

```php
'position' => [
    'x' => '(w-text_w)/2',  // Center horizontally
    'y' => 'h-text_h-20',   // 20px from bottom
]
```

## Text Styling

### Font Size and Color

```php
FFmpeg::fromPath('input.mp4')
    ->withText('Styled Text', [
        'font_size' => 36,
        'font_color' => 'white',
    ])
    ->save('output.mp4');
```

Color formats:

-   Named colors: `white`, `black`, `red`, `blue`, `yellow`, etc.
-   Hex colors: `FF0000`, `#00FF00`
-   With alpha: `white@0.8`, `FF0000@0.5`

### Background Box

Add a background box behind the text:

```php
FFmpeg::fromPath('input.mp4')
    ->withText('Text with Background', [
        'background_color' => 'black@0.5',  // Semi-transparent black
        'padding' => 15,
    ])
    ->save('output.mp4');
```

Disable background:

```php
'background_color' => null,
```

### Text Border

Add a border around the text:

```php
FFmpeg::fromPath('input.mp4')
    ->withText('Bordered Text', [
        'border_width' => 2,
        'border_color' => 'black',
    ])
    ->save('output.mp4');
```

### Custom Font

Use a custom font file:

```php
FFmpeg::fromPath('input.mp4')
    ->withText('Custom Font', [
        'font_file' => '/path/to/fonts/Arial.ttf',
        'font_size' => 28,
    ])
    ->save('output.mp4');
```

## Timing

### Duration and Start Time

Control when text appears:

```php
FFmpeg::fromPath('input.mp4')
    ->withText('Temporary Text', [
        'start_time' => 5,    // Show at 5 seconds
        'duration' => 10,     // Show for 10 seconds
    ])
    ->save('output.mp4');
```

Show for entire video (default):

```php
'start_time' => 0,
'duration' => null,
```

## Dynamic Text with Callbacks

Use a callback to generate text based on the current file:

```php
FFmpeg::fromPath('input.mp4')
    ->withText(function ($filePath) {
        return 'Processing: ' . basename($filePath);
    })
    ->save('output.mp4');
```

The callback receives the full path to the file being processed.

### With Directory Processing

Perfect for batch processing with unique text per file:

```php
FFmpeg::fromDirectory('/path/to/videos')
    ->withText(function ($file) {
        $name = pathinfo($file, PATHINFO_FILENAME);
        return "Video: $name";
    }, [
        'position' => 'top-center',
        'font_size' => 30,
        'background_color' => 'black@0.7',
    ])
    ->save('/path/to/output/');
```

## Complete Configuration

All available options:

```php
FFmpeg::fromPath('input.mp4')
    ->withText('Full Configuration', [
        // Position
        'position' => 'bottom-center',      // or ['x' => 100, 'y' => 200]

        // Font
        'font_size' => 24,                  // Font size in pixels
        'font_color' => 'white',            // Text color
        'font_file' => null,                // Path to custom font

        // Background
        'background_color' => 'black@0.5',  // Background color with alpha
        'padding' => 10,                    // Padding around text

        // Border
        'border_width' => 0,                // Border width in pixels
        'border_color' => 'black',          // Border color

        // Timing
        'start_time' => 0,                  // When to start (seconds)
        'duration' => null,                 // How long to show (null = entire video)
    ])
    ->save('output.mp4');
```

## Examples

### Copyright Notice

```php
FFmpeg::fromPath('video.mp4')
    ->withText('© 2024 My Company', [
        'position' => 'bottom-right',
        'font_size' => 14,
        'font_color' => 'white',
        'background_color' => 'black@0.6',
        'padding' => 8,
    ])
    ->save('output.mp4');
```

### Title Card (First 5 Seconds)

```php
FFmpeg::fromPath('video.mp4')
    ->withText('My Video Title', [
        'position' => 'center',
        'font_size' => 48,
        'font_color' => 'white',
        'background_color' => 'black@0.8',
        'padding' => 20,
        'border_width' => 3,
        'border_color' => 'yellow',
        'start_time' => 0,
        'duration' => 5,
    ])
    ->save('output.mp4');
```

### Timestamp Overlay

```php
FFmpeg::fromPath('video.mp4')
    ->withText(function ($file) {
        return date('Y-m-d H:i:s', filemtime($file));
    }, [
        'position' => 'top-left',
        'font_size' => 16,
        'background_color' => 'black@0.7',
        'padding' => 5,
    ])
    ->save('output.mp4');
```

### Subtitle Style

```php
FFmpeg::fromPath('video.mp4')
    ->withText('This is a subtitle', [
        'position' => 'bottom-center',
        'font_size' => 20,
        'font_color' => 'white',
        'background_color' => 'black@0.8',
        'padding' => 12,
        'border_width' => 1,
        'border_color' => 'white',
    ])
    ->save('output.mp4');
```

### Watermark Text

```php
FFmpeg::fromPath('video.mp4')
    ->withText('DRAFT', [
        'position' => 'center',
        'font_size' => 80,
        'font_color' => 'red@0.3',
        'background_color' => null,
    ])
    ->save('output.mp4');
```

## Combine with Other Features

### With Image Watermark

```php
FFmpeg::fromPath('video.mp4')
    ->withWatermark('logo.png', 'top-right')
    ->withText('Copyright 2024', [
        'position' => 'bottom-right',
    ])
    ->save('output.mp4');
```

### With Intro and Outro

```php
FFmpeg::fromPath('video.mp4')
    ->withIntro('intro.mp4')
    ->withOutro('outro.mp4')
    ->withText('Episode 1', [
        'position' => 'top-left',
        'start_time' => 2,
        'duration' => 5,
    ])
    ->save('output.mp4');
```

### With Video Filters

```php
FFmpeg::fromPath('video.mp4')
    ->resize(1920, 1080)
    ->fadeIn(2)
    ->fadeOut(2)
    ->withText('My Video', [
        'position' => 'bottom-center',
    ])
    ->save('output.mp4');
```

## Advanced Usage

### Multiple Text Overlays

For multiple text overlays, chain multiple operations:

```php
// First pass: Add title
$tempFile = sys_get_temp_dir() . '/temp_video.mp4';

FFmpeg::fromPath('input.mp4')
    ->withText('Title', [
        'position' => 'top-center',
    ])
    ->save($tempFile);

// Second pass: Add copyright
FFmpeg::fromPath($tempFile)
    ->withText('© 2024', [
        'position' => 'bottom-right',
    ])
    ->save('output.mp4');

unlink($tempFile);
```

Or use the filter API directly:

```php
FFmpeg::fromPath('input.mp4')
    ->addFilter("drawtext=text='Title':x=(w-text_w)/2:y=20:fontsize=36:fontcolor=white")
    ->addFilter("drawtext=text='© 2024':x=w-text_w-10:y=h-text_h-10:fontsize=14:fontcolor=white")
    ->save('output.mp4');
```

### Animated Text

Use FFmpeg expressions for animated effects:

```php
FFmpeg::fromPath('input.mp4')
    ->addFilter("drawtext=text='Scrolling Text':x=w-mod(t*100,w+tw):y=h-50:fontsize=24:fontcolor=white")
    ->save('output.mp4');
```

### Conditional Text

Show text based on file properties:

```php
FFmpeg::fromDirectory('/path/to/videos')
    ->withText(function ($file) {
        $size = filesize($file) / (1024 * 1024); // MB
        return $size > 100 ? 'Large File' : 'Standard File';
    })
    ->save('/path/to/output/');
```

## Tips

1. **Font Files**: On Linux, common fonts are in `/usr/share/fonts/`. On Windows: `C:\Windows\Fonts\`
2. **Escape Characters**: Special characters like `:[]',;` are automatically escaped
3. **Performance**: Text overlay requires re-encoding; use appropriate codecs for quality/speed balance
4. **Preview**: Use `.dryRun()` to see the generated FFmpeg command
5. **Positioning**: Use FFmpeg expressions like `(w-text_w)/2` for dynamic positioning
6. **Testing**: Test with short clips first to verify styling before processing long videos

## Troubleshooting

### Text Not Showing

-   Ensure font file exists if using custom font
-   Check if text color contrasts with video
-   Verify position is within video bounds
-   Add a background color to make text visible

### Special Characters

If special characters don't display correctly:

```php
'font_file' => '/path/to/font/with/unicode/support.ttf',
```

### Font Size Issues

Font size is in pixels. For 1080p videos, 24-36px works well. For 4K, double the size.

```php
// Responsive font size
$videoHeight = 1080; // Get from video metadata
$fontSize = (int)($videoHeight / 30); // ~36px for 1080p

->withText('Text', ['font_size' => $fontSize])
```

# Filters & Effects

## Basic Filters

### Scale/Resize
```php
->scale(1920, 1080)
->resize(1280, 720)  // Alias
```

### Crop
```php
->crop(1920, 1080, 0, 0)  // width, height, x, y
```

### Rotate
```php
->rotate(90)   // 90, 180, or 270 degrees
```

### Flip
```php
->flip('horizontal')
->flip('vertical')
```

## Effects

### Fade
```php
->fadeIn(2)    // 2 seconds
->fadeOut(3)   // 3 seconds
->fade('in', 2)  // Generic
```

### Blur & Sharpen
```php
->blur(10)      // Strength 1-20
->sharpen(5)    // Strength 1-10
```

### Color Effects
```php
->grayscale()
->sepia()
```

## Advanced

### Watermark
```php
->watermark('logo.png', [
    'position' => 'top-right',  // top-left, top-right, bottom-left, bottom-right, center
    'opacity' => 0.7
])
```

### Speed
```php
->speed(2.0)   // 2x speed
->speed(0.5)   // Half speed
```

### Reverse
```php
->reverse()
```

### Thumbnails
```php
// Single thumbnail
->thumbnail('thumb.jpg', '00:00:05')

// Multiple thumbnails
->thumbnails('thumbs/', 10)
```

## Custom Filters

```php
->addFilter('custom_ffmpeg_filter')
```

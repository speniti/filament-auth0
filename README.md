# Filament Auth0

## Testing

When running tests locally with Laravel Herd, you may encounter segmentation faults (exit code 139) due to a bug in the
Xdebug version included with Herd's PHP 8.4.
This issue only affects local development with Xdebug enabled. CI environments and tests without Xdebug should work
correctly.

### Workarounds

You have three options to run tests locally:

1. **Disable Xdebug** (recommended for running tests):
   ```bash
   php -dxdebug.mode=off vendor/bin/pest
   ```

2. **Use PHP 8.5** (includes a newer Xdebug version):
   ```bash
   /path/to/herd/bin/php85 vendor/bin/pest
   ```

3. **Update the test script** in `composer.json` to always disable Xdebug:
   ```json
   "test": [
       "@clear",
       "@php -dxdebug.mode=off vendor/bin/pest"
   ]
   ```

### Note

- This is a known issue with Xdebug and not a bug in this package.
- The issue is tracked in [Laravel Framework #54875](https://github.com/laravel/framework/issues/54875)
- All tests pass successfully in environments where Xdebug is not loaded.

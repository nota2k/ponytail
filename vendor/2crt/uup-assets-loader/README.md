## UupVite

UupVite (double-U P vite) provides a simple API to enqueue assets that were bundled through vite with the corresponding vite plugin.

```
$asset_loader = new \Uup\ViteAssetsLoader(
    __DIR__ . '/dist/manifest.json',
    get_template_directory_uri() . '/dist/manifest.json'
);

$asset_loader->enqueue_script(
    'theme-js', // handle
    'js/main.js' // source file
);

$asset_loader->enqueue_style(
    'theme-styles', // handle 
    'resources/css/theme.scss' // source file
);
```

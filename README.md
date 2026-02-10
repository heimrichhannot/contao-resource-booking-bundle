# Resource Booking 📦🗂️ for Contao

This bundle provides resource booking capabilities for the Contao Open-Source CMS.

> [!NOTE]
> This bundle is in early development.
>  
> We welcome any feedback and contributions and will release a stable version soon.

## Installation

Install the bundle via Composer:

```bash
composer require heimrichhannot/contao-resource-booking-bundle
```

Requires **Contao ^5.3** and **PHP ^8.2**.

## Features
- Create and manage resources and their availability
- Book resources for specific time slots
- Create custom forms in the form generator to collect any information you
  need when booking resources
- Display a calendar with bookings and available resources
- Send notifications for bookings and cancellations


## Developers

### Building the JS Bundle

This Contao bundle uses Vite to build the JS modules.
Run the following command to build the minified production assets:

```bash
npm run build
```

With the following command, the bundle will be built in development mode:

```bash
npm run dev
```

To watch for changes and rebuild the bundle automatically in development mode, run:

```bash
npm run watch
```

### Using bundle assets in twig

This bundle provides a manifest file for configuration with the `symfony/asset` component.
Use `huh_rb_build` (for Vite-bundled assets) or `huh_rb` (for public bundle files) as the bundle name to reference the
assets in your twig templates.

```html
<script type="module" src="{{ asset('assets/js/index.js', 'huh_rb_build') }}"></script>
```

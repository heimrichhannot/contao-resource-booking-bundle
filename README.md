# Resource Booking 📦🗂️ for Contao

This bundle provides resource booking capabilities for the Contao Open-Source CMS.

> [!NOTE]
> This bundle is in early development.
>  
> We welcome any feedback and contributions.

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

You can create your custom booking archive types, which handle the display of booking resources and calendars within
the booking form.

> [!NOTE]
> As of now, time-slot logic is limited to whole days. In the future, the booking archive types will be able to handle
> time-slots of arbitrary length customizable down to individual days of the week.

### Setting up Custom Calendars

If you extend your custom archive type class from the abstract base class, you are most likely already setup and ready to create a custom template.

```php
use HeimrichHannot\ResourceBookingBundle\Booking\ArchiveType\AbstractArchiveType;

class MyCustomArchiveType extends AbstractArchiveType
{
}
```

If you do not override the `getTemplate()` method, the bundle will infer the template name from the class name,
i.e., `resource_booking/calendar/my_custom.html.twig`.

#### Calendar Template Logic

We recommend that you extend your custom `resource_booking/calendar` templates from the provided base template, as shown in the example below.

It is crucial to understand, that within the calendar template, _you_ are responsible for rendering interactive booking resources and time slots.

- You are provided with some template variables that you can use to render the resources and time slots.
- You may also use the provided API to fetch the booking resources and time slots with JavaScript.

You SHOULD use the provided JavaScript module to select resources and set the respective time slot.

```html
{% extends '@Contao/resource_booking/calendar.html.twig' %}

// add custom attributes to the calendar root element (mount)
{% do attributes.mergeWith({'data-my-attr': 'whatever'}) %}

{% block mount %}

    {# @var booking_archive \HeimrichHannot\ResourceBookingBundle\Model\BookingArchiveModel #}
    {# @var resource_archives \HeimrichHannot\ResourceBookingBundle\Model\ResourceArchiveModel[] #}
    {# @var resources \HeimrichHannot\ResourceBookingBundle\Model\ResourceModel[] #}

    Either render your custom calendar here using the provided template variables,
    or use the provided JavaScript module to fetch the resources and time slots programmatically
    to render them via JavaScript.

    Of course, you may also choose a hybrid approach, rendering some components via JavaScript and others via Twig.

{% endblock %}

{% block script %}
    {# Use an ES module to handle the booking form logic #}
    <script type="module">
        import { bookingForms } from '@huh/rb'; // ready to use API

        // bind the booking form to automatically handle
        // the resource and time slot data on form submit
        const bookingForm = bookingForms.find('{{ mount_id }}');
        if (!bookingForm) throw new Error('Booking form not found');
        bookingForm.bind();

        // fetch resources and bookings programmatically (optional)
        const resourceResult = await bookingForm.fetchResources();
        if (!resourceResult) throw new Error('Failed to fetch resources');
        
        const resources = resourceResult.resources || [];
        const resourceArchives = resourceResult.archives || [];
        
        const bookingsResult = await bookingForm.fetchBookings();
        if (!bookingsResult) throw new Error('Failed to fetch bookings');
        const bookings = bookingsResult.bookings || [];

        /* *Booking Form Logic*
         *
         * - access the parent element of the mount block above with
         *     `bookingForm.$mount`
         * - render resources and time slots, e.g., a calendar
         * - set the selected resources and time slot programmatically
         * - using `bookingForm.data` handles submission and validation
         *     of the booking data automatically,
         *     if `bookingForm.bind()` was called
         */

        // set the start and end date for the desired booking period
        bookingForm.data.start = new Date('2026-01-01');
        bookingForm.data.end = new Date('2026-02-02');
        // use a resource with id and set the quantity
        bookingForm.data.useResource(resources[0].id, 2);
        
        bookingForm.isLoading = false; // disable/enable a loading indicator on the form
    </script>
{% endblock %}
```

## Contributing

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

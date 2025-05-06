# AI Calendar

A beautiful and responsive WordPress calendar plugin for managing and displaying events.

## Features

- Clean and modern calendar interface
- Responsive design for all devices
- Easy event management
- Event preview with details
- Customizable colors and styles
- Support for recurring events
- Mobile-friendly interface

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

## Installation

1. Download the plugin zip file
2. Go to WordPress admin panel > Plugins > Add New
3. Click "Upload Plugin" and select the downloaded zip file
4. Click "Install Now"
5. After installation, click "Activate"

## Usage

### Adding the Calendar to a Page

Use the shortcode `[ai_calendar]` to display the calendar on any page or post.

### Block Editor

The plugin also provides a Calendar block that can be added using the block editor.

### Creating Events

1. Go to WordPress admin panel > Events > Add New
2. Fill in the event details:
   - Title
   - Start Date/Time
   - End Date/Time
   - Location
   - Description
   - Featured Image
3. For recurring events, check "Make this event recurring" and set the recurrence pattern
4. Click "Publish" to save the event

### Customization

You can customize the calendar appearance in Settings > AI Calendar:

- Calendar colors
- Event colors
- Day container size
- Events per day
- First day of week
- And more...

## Directory Structure

```
ai-calendar/
├── ai-calendar.php
├── README.md
├── assets/
│   ├── css/
│   │   └── calendar.css
│   └── js/
│       ├── frontend.js
│       └── calendar-block.js
├── includes/
│   ├── Admin/
│   │   └── Admin.php
│   ├── Frontend/
│   │   └── Frontend.php
│   └── EventManager.php
├── languages/
│   └── ai-calendar.pot
└── templates/
    └── admin/
        └── event-details-meta-box.php
```

## Support

For support or feature requests, please visit our website or create an issue in our GitHub repository.

## License

This plugin is licensed under the GPL v2 or later. 
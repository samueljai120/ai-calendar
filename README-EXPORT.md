# AI Calendar Plugin Export Instructions

This document explains how to create a clean export version of the AI Calendar plugin for public distribution.

## Export Process

1. The export process has been automated with the `export-plugin.php` script.
2. The exported version of the plugin will:
   - Hide the Calendar Theme settings from the admin UI
   - Remove the Calendar Theme section from the instructions page
   - Remove all debugging and testing files
   - Keep all functionality intact (the theme settings still work behind the scenes)

## Running the Export

You can generate a clean export of the plugin using one of the following methods:

### Method 1: Via Browser

1. Copy this file to your WordPress plugins directory under `ai-calender-1.0.0/`
2. Access the script through your browser: `https://your-site.com/wp-content/plugins/ai-calender-1.0.0/export-plugin.php`
3. The plugin will be exported to the `export` directory inside the plugin folder

### Method 2: Via Command Line

1. Navigate to the plugin directory in your terminal:
   ```
   cd /path/to/your/wp-content/plugins/ai-calender-1.0.0/
   ```

2. Run the export script:
   ```
   php export-plugin.php
   ```

3. The script will create a zip file at `/path/to/your/wp-content/plugins/ai-calender-1.0.0/export/ai-calendar.zip`

## Export Contents

The exported plugin will include:

- All necessary files for the plugin to function correctly
- No debugging or testing files
- No development-only code or files
- Clean UI without calendar theme settings visible in the admin
- Updated instructions page without references to calendar theme settings

## After Export

After exporting, you should:

1. Test the exported plugin on a fresh WordPress installation
2. Verify that all functionality works as expected
3. Confirm that the Calendar Theme settings are hidden from the UI
4. Check that the instructions page doesn't mention the Calendar Theme settings

## Distributing the Plugin

The exported zip file (`ai-calendar.zip`) is ready for distribution through:

- WordPress Plugin Directory
- Your website
- Other plugin distribution platforms

## Support

For questions about the plugin or export process, contact Samuel So at samuelso0105@gmail.com. 
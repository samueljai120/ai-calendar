<?php
/**
 * Export Plugin Script
 * 
 * This script creates a clean version of the AI Calendar plugin for public distribution.
 * It includes only the necessary files and removes any development/test files.
 */

// Check if this script is being run directly
if (!defined('ABSPATH')) {
    // Define the constant when running as a standalone script
    define('RUNNING_EXPORT_SCRIPT', true);
}

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define paths
$plugin_dir = __DIR__;
$export_dir = $plugin_dir . '/export';
$export_plugin_dir = $export_dir . '/ai-calendar';
$export_zip = $export_dir . '/ai-calendar.zip';

// Create export directory if it doesn't exist
if (!file_exists($export_dir)) {
    mkdir($export_dir, 0755, true);
}

// Remove old export directory if it exists
if (file_exists($export_plugin_dir)) {
    delete_directory($export_plugin_dir);
}

// Create plugin directory in export
mkdir($export_plugin_dir, 0755, true);

// Files and directories to include in the export
$include_dirs = [
    'assets',
    'includes',
    'templates',
];

// Additional specific files to include
$include_files = [
    'ai-calendar.php',
    'readme.txt',
    'LICENSE',
    'uninstall.php',
];

// Files and directories to exclude from the export
$exclude = [
    // Debug and test files
    'debug-',
    'test-',
    'check-',
    'fix-',
    '.debug',
    '.test',
    '.sql',
    'backup',
    'tests/',
    'sql/',
    '.git',
    '.gitignore',
    'node_modules',
    'export-plugin.php',
    'cleanup.php',
];

// Copy allowed directories
foreach ($include_dirs as $dir) {
    if (is_dir($plugin_dir . '/' . $dir)) {
        copy_directory($plugin_dir . '/' . $dir, $export_plugin_dir . '/' . $dir, $exclude);
    }
}

// Copy individual files
foreach ($include_files as $file) {
    if (file_exists($plugin_dir . '/' . $file)) {
        copy($plugin_dir . '/' . $file, $export_plugin_dir . '/' . $file);
    }
}

// Create zip archive
if (file_exists($export_zip)) {
    unlink($export_zip);
}

// Create zip file
if (create_zip($export_plugin_dir, $export_zip)) {
    echo "Plugin exported successfully to: " . $export_zip . "\n";
} else {
    echo "Failed to create zip file.\n";
}

/**
 * Helper function to copy a directory recursively
 */
function copy_directory($source, $destination, $exclude = []) {
    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }
    
    $dir = opendir($source);
    
    while (($file = readdir($dir)) !== false) {
        if ($file == '.' || $file == '..') {
            continue;
        }
        
        // Check if file should be excluded
        $exclude_file = false;
        foreach ($exclude as $pattern) {
            if (strpos($file, $pattern) !== false) {
                $exclude_file = true;
                break;
            }
        }
        
        if ($exclude_file) {
            continue;
        }
        
        $src = $source . '/' . $file;
        $dst = $destination . '/' . $file;
        
        if (is_dir($src)) {
            copy_directory($src, $dst, $exclude);
        } else {
            copy($src, $dst);
        }
    }
    
    closedir($dir);
}

/**
 * Helper function to delete a directory recursively
 */
function delete_directory($dir) {
    if (!is_dir($dir)) {
        return;
    }
    
    $files = array_diff(scandir($dir), ['.', '..']);
    
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            delete_directory($path);
        } else {
            unlink($path);
        }
    }
    
    rmdir($dir);
}

/**
 * Helper function to create a zip file
 */
function create_zip($source, $destination) {
    if (!extension_loaded('zip')) {
        return false;
    }
    
    $zip = new ZipArchive();
    
    if (!$zip->open($destination, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
        return false;
    }
    
    $source = str_replace('\\', '/', realpath($source));
    $base_dir = basename($source);
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    while ($iterator->valid()) {
        if (!$iterator->isDot()) {
            $real_path = $iterator->getPathname();
            $real_path = str_replace('\\', '/', $real_path);
            
            // Get relative path
            $relative_path = substr($real_path, strlen($source) + 1);
            
            if (is_dir($real_path)) {
                $zip->addEmptyDir($base_dir . '/' . $relative_path);
            } else if (is_file($real_path)) {
                $zip->addFile($real_path, $base_dir . '/' . $relative_path);
            }
        }
        
        $iterator->next();
    }
    
    return $zip->close();
}

// If running standalone, output message
if (defined('RUNNING_EXPORT_SCRIPT')) {
    echo "Export process completed.\n";
} 
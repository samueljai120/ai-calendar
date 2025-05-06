<?php

namespace AiCalendar\Admin;

class TemplatePreviewGenerator {
    private $image_dir;
    private $width = 800;
    private $height = 450;

    public function __construct() {
        $this->image_dir = plugin_dir_path(dirname(dirname(__FILE__))) . 'assets/images/';
        add_action('admin_init', [$this, 'generate_preview_images']);
    }

    public function generate_preview_images() {
        if (!extension_loaded('gd')) {
            return;
        }

        $this->generate_template_none_preview();
        $this->generate_template_one_preview();
        $this->generate_template_two_preview();
    }

    private function generate_template_none_preview() {
        $image = imagecreatetruecolor($this->width, $this->height);
        
        // Set colors
        $white = imagecolorallocate($image, 255, 255, 255);
        $gray = imagecolorallocate($image, 247, 250, 252);
        $text_color = imagecolorallocate($image, 51, 51, 51);
        $border = imagecolorallocate($image, 226, 232, 240);
        
        // Fill background
        imagefill($image, 0, 0, $white);
        
        // Draw content area
        imagefilledrectangle($image, 50, 50, $this->width - 50, $this->height - 50, $gray);
        imagerectangle($image, 50, 50, $this->width - 50, $this->height - 50, $border);
        
        // Add title
        imagestring($image, 5, $this->width/2 - 100, 100, "Default Theme", $text_color);
        
        // Add description
        imagestring($image, 3, $this->width/2 - 150, 150, "Uses your theme's default single post template", $text_color);
        imagestring($image, 3, $this->width/2 - 120, 180, "with event-specific meta information", $text_color);
        
        // Save image
        imagepng($image, $this->image_dir . 'template-none-preview.png');
        imagedestroy($image);
    }

    private function generate_template_one_preview() {
        $image = imagecreatetruecolor($this->width, $this->height);
        
        // Set colors
        $white = imagecolorallocate($image, 255, 255, 255);
        $primary = imagecolorallocate($image, 33, 150, 243);
        $text_color = imagecolorallocate($image, 51, 51, 51);
        $text_white = imagecolorallocate($image, 255, 255, 255);
        $gray = imagecolorallocate($image, 247, 250, 252);
        
        // Fill background
        imagefill($image, 0, 0, $white);
        
        // Draw banner
        imagefilledrectangle($image, 0, 0, $this->width, 180, $primary);
        
        // Add gradient overlay
        for ($i = 0; $i < 100; $i++) {
            $opacity = min(127, $i);
            $color = imagecolorallocatealpha($image, 0, 0, 0, $opacity);
            imageline($image, 0, 180 - $i, $this->width, 180 - $i, $color);
        }
        
        // Draw content area
        imagefilledrectangle($image, 50, 200, $this->width - 50, $this->height - 50, $white);
        
        // Add title in banner
        imagestring($image, 5, 70, 70, "Modern Banner Layout", $text_white);
        imagestring($image, 4, 70, 100, "Full-width featured image banner", $text_white);
        imagestring($image, 4, 70, 130, "with elegant content layout", $text_white);
        
        // Add content preview
        imagefilledrectangle($image, 70, 220, $this->width - 70, 260, $gray);
        imagefilledrectangle($image, 70, 280, $this->width - 70, 320, $gray);
        imagefilledrectangle($image, 70, 340, $this->width - 200, 360, $gray);
        
        // Save image
        imagepng($image, $this->image_dir . 'template-template-1-preview.png');
        imagedestroy($image);
    }

    private function generate_template_two_preview() {
        $image = imagecreatetruecolor($this->width, $this->height);
        
        // Set colors
        $white = imagecolorallocate($image, 255, 255, 255);
        $gray = imagecolorallocate($image, 247, 250, 252);
        $text_color = imagecolorallocate($image, 51, 51, 51);
        $border = imagecolorallocate($image, 226, 232, 240);
        $accent = imagecolorallocate($image, 66, 153, 225);
        
        // Fill background
        imagefill($image, 0, 0, $gray);
        
        // Draw main content area
        imagefilledrectangle($image, 50, 50, $this->width - 250, $this->height - 50, $white);
        imagerectangle($image, 50, 50, $this->width - 250, $this->height - 50, $border);
        
        // Draw sidebar
        imagefilledrectangle($image, $this->width - 230, 50, $this->width - 50, $this->height - 50, $white);
        imagerectangle($image, $this->width - 230, 50, $this->width - 50, $this->height - 50, $border);
        
        // Add title
        imagestring($image, 5, 70, 70, "Sidebar Layout", $text_color);
        
        // Add content preview
        imagefilledrectangle($image, 70, 120, $this->width - 270, 160, $gray);
        imagefilledrectangle($image, 70, 180, $this->width - 270, 220, $gray);
        imagefilledrectangle($image, 70, 240, $this->width - 350, 260, $gray);
        
        // Add sidebar content
        imagestring($image, 4, $this->width - 210, 70, "Event Details", $text_color);
        imagefilledrectangle($image, $this->width - 210, 100, $this->width - 70, 130, $accent);
        imagefilledrectangle($image, $this->width - 210, 150, $this->width - 70, 180, $accent);
        imagefilledrectangle($image, $this->width - 210, 200, $this->width - 70, 230, $accent);
        
        // Save image
        imagepng($image, $this->image_dir . 'template-template-2-preview.png');
        imagedestroy($image);
    }
} 
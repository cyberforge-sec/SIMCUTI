<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Captcha Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the captcha settings for the application.
    |
    */

    'length' => env('CAPTCHA_LENGTH', 6),
    
    'expiry' => env('CAPTCHA_EXPIRY', 300), // seconds (5 minutes)
    
    'max_attempts' => 3,
    
    'width' => 200,
    
    'height' => 60,
    
    'font_size_min' => 20,
    
    'font_size_max' => 26,
    
    'rotation_min' => -20,
    
    'rotation_max' => 20,
    
    'characters' => 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789', // Exclude confusing: O, 0, I, 1, l
    
    'colors' => [
        'background_start' => [79, 70, 229],   // Primary color
        'background_end' => [67, 56, 202],     // Primary dark
        'text' => [255, 255, 255],             // White
        'shadow' => [0, 0, 0],                 // Black
        'line' => [129, 140, 248],             // Primary light
        'dot' => [203, 213, 225],              // Gray
    ],

];

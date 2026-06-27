<?php

// Konfigurasi validasi keamanan captcha.

return [

    

    'length' => env('CAPTCHA_LENGTH', 6),
    
    'expiry' => env('CAPTCHA_EXPIRY', 300), 
    
    'max_attempts' => 3,
    
    'width' => 200,
    
    'height' => 60,
    
    'font_size_min' => 20,
    
    'font_size_max' => 26,
    
    'rotation_min' => -20,
    
    'rotation_max' => 20,
    
    'characters' => 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789', 
    
    'colors' => [
        'background_start' => [79, 70, 229],   
        'background_end' => [67, 56, 202],     
        'text' => [255, 255, 255],             
        'shadow' => [0, 0, 0],                 
        'line' => [129, 140, 248],             
        'dot' => [203, 213, 225],              
    ],

];
  
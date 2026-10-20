<?php

return [
    'provider' => 'gemini',
    'key' => env('GEMINI_API_KEY'),
    'url' => 'https://generativelanguage.googleapis.com/v1beta/models',
    'model' => env('GEMINI_MODEL', 'gemini-3.8-flash'),
    'max_tokens' => 4000,
    'connect_timeout' => 10,
    'timeout' => 120,
    'max_cv_characters' => 40000,
    'max_response_bytes' => 131072,
    'max_docx_uncompressed_bytes' => 20971520,
    'scoring' => [
        'version' => 'skills-experience-v1',
        'weights' => ['required' => 60, 'experience' => 25, 'nice' => 15],
        'thresholds' => ['strong_match' => 85, 'shortlist' => 70, 'review' => 50],
        'aliases' => ['js' => 'javascript', 'react.js' => 'react', 'reactjs' => 'react', 'node.js' => 'nodejs', 'postgres' => 'postgresql'],
    ],
];

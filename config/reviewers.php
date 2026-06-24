<?php

return [
    'low_score_threshold' => 0.7,
    'max_recommendations' => 5,
    'default_tag_relevance' => 'medium',
    'tag_relevance_levels' => [
        'very_high',
        'high',
        'medium',
        'low',
        'very_low',
    ],
    'tag_relevance_weights' => [
        'very_high' => 1.6,
        'high' => 1.25,
        'medium' => 1.0,
        'low' => 0.6,
        'very_low' => 0.35,
    ],
    // Fallback mode when a tag has no explicit coverage in taggables.
    // equal: split 1.0 equally across tags in the same classwork item.
    // full:  treat each tagged item as full 1.0 coverage for each tag.
    'default_tag_coverage_mode' => 'equal',
    'compatibility_percent_decimals' => 1,
];

<?php

return [
    'xp' => [
        'completion' => [
            'lesson_activity' => (int) env('XP_LESSON_COMPLETION', 10),
            'practice_attempt' => (int) env('XP_PRACTICE_COMPLETION', 15),
            'game_session' => (int) env('XP_GAME_COMPLETION', 20),
            'simulation_session' => (int) env('XP_SIMULATION_COMPLETION', 20),
            'test_attempt' => (int) env('XP_ASSESSMENT_COMPLETION', 30),
        ],
        'accuracy_bonus' => (int) env('XP_ACCURACY_BONUS', 20),
        'accuracy_bonus_threshold' => (float) env('XP_ACCURACY_BONUS_THRESHOLD', 80),
        'personal_best' => (int) env('XP_PERSONAL_BEST', 25),
        'daily_goal' => (int) env('XP_DAILY_GOAL', 30),
        'level_size' => (int) env('XP_LEVEL_SIZE', 100),
    ],

    'daily_goal' => [
        'activities' => (int) env('DAILY_GOAL_ACTIVITIES', 3),
        'minutes' => (int) env('DAILY_GOAL_MINUTES', 15),
        'xp' => (int) env('DAILY_GOAL_XP', 50),
    ],

    'streak' => [
        'milestones' => [1, 3, 5, 7, 14, 30],
        'initial_freezes' => (int) env('STREAK_INITIAL_FREEZES', 0),
        'freeze_max_gap_days' => (int) env('STREAK_FREEZE_MAX_GAP_DAYS', 2),
    ],
];

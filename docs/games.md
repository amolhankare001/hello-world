# Reusable game engine

The learning game module runs through ordinary authenticated Laravel web requests and browser JavaScript. It does not require Redis, WebSockets, a queue worker, or a persistent application process, so it can run on standard cPanel/Plesk shared hosting.

## Core records

- `games` stores the reusable engine key, Marathi/English metadata, visual hooks, and item bank.
- `game_levels` stores question count, choices, lives, timers, score targets, and adaptive difficulty thresholds.
- `game_skills` connects every game to one or more curriculum skills.
- `game_sessions` stores the student, academic year, selected level, expiry, and server-owned state.
- `game_questions` freezes the generated round, including public choices and a server-only expected answer.
- `game_answers` stores one validated answer, server-measured response time, correctness, and score per question.
- `game_results` stores the validated final score, accuracy, duration, XP, and completion summary.

## Request lifecycle

1. A student starts a published game.
2. The server selects the closest level from the student's current skill level.
3. The configured question provider freezes the complete round in `game_questions`.
4. The browser submits only the selected choice value.
5. The server locks the session and current question, checks ownership, verifies expiry and order, compares the expected answer, measures response time, and calculates score.
6. When questions, lives, or time are exhausted, the server creates one result, learning evidence, skill-progress updates, and replay-safe gamification rewards.

The client never submits score, correctness, XP, difficulty, remaining lives, or final accuracy.

## Reusable managers

- `GameQuestionProvider` creates frozen rounds from engine configuration and distributes questions across mapped skills.
- `GameScoreManager` calculates server-owned base and speed scores.
- `GameTimer` owns session expiry, question response time, and server-synchronized countdown values.
- `GameLives` owns initial lives, deductions, and completion checks.
- `GameDifficultyManager` selects the starting level and applies configured advancement or remediation thresholds.
- `GameFeedbackManager` supplies child-friendly Marathi feedback.
- `GamificationService` is the shared XP manager and prevents duplicate source rewards.

## Adding a catch-style game

Add a `games` record with `engine_key` set to `catch`, attach at least one skill, provide at least one level, and configure:

```php
[
    'prompt' => 'Catch :target',
    'prompt_marathi' => ':target पकडा',
    'icon' => '★',
    'items' => [
        ['value' => 'stable-server-value', 'label' => 'Child-facing label'],
    ],
    'visual_theme' => 'theme-key',
    'sound_hook' => 'positive_tone',
]
```

Each level may configure `question_count`, `choice_count`, `item_count`, `lives`, `response_time_seconds`, `difficulty_up_accuracy`, `remedial_accuracy`, `minimum_difficulty`, and `maximum_difficulty`. New content using the catch engine requires no schema change.

## Security and replay protection

- Student session routes bind only records owned by the authenticated student's profile.
- Question routes are scoped to the bound session.
- Expected answers are hidden from serialization and never included in browser state.
- Answer and completion writes use database transactions and row locks.
- Answer and finish routes are rate limited per authenticated user.
- `game_answers.game_question_id` is unique.
- `game_results.game_session_id` is unique.
- XP transactions use the existing unique source/reason constraint.

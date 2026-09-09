<?php

namespace App\Services;

class GameFeedbackManager
{
    /**
     * @return array{title: string, message: string, tone: string}
     */
    public function forAnswer(bool $isCorrect): array
    {
        if ($isCorrect) {
            return [
                'title' => 'छान!',
                'message' => 'अगदी बरोबर. पुढचे आव्हान घेऊया!',
                'tone' => 'success',
            ];
        }

        return [
            'title' => 'पुन्हा प्रयत्न करूया',
            'message' => 'काळजीपूर्वक पाहा. पुढच्या वेळी नक्की जमेल!',
            'tone' => 'encouragement',
        ];
    }
}

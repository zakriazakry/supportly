<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class TokenOptimizerService
{
    const MAX_TOKENS_PER_REQUEST = 2000;
    const CHARS_PER_TOKEN = 4;
    const SUMMARY_TRIGGER_COUNT = 10;

    public function optimizeMessages(array $messages, int $maxTokens = self::MAX_TOKENS_PER_REQUEST): array
    {
        $totalTokens = $this->estimateTokens($messages);

        if ($totalTokens <= $maxTokens) {
            return $messages;
        }

        return $this->applyRollingWindowWithSummary($messages, $maxTokens);
    }

    public function estimateTokens(array $messages): int
    {
        $totalChars = 0;

        foreach ($messages as $message) {
            if (isset($message['content'])) {
                $totalChars += mb_strlen($message['content']);
            }
        }

        return (int) ceil($totalChars / self::CHARS_PER_TOKEN);
    }

    protected function applyRollingWindowWithSummary(array $messages, int $maxTokens): array
    {
        $recentMessages = [];
        $currentTokens = 0;
        $reversedMessages = array_reverse($messages);

        foreach ($reversedMessages as $message) {
            $messageTokens = $this->estimateTokens([$message]);

            if ($currentTokens + $messageTokens <= $maxTokens * 0.8) {
                array_unshift($recentMessages, $message);
                $currentTokens += $messageTokens;
            } else {
                break;
            }
        }

        $remainingMessages = array_slice($messages, 0, count($messages) - count($recentMessages));

        if (!empty($remainingMessages)) {
            $summary = $this->summarizeMessages($remainingMessages);
            array_unshift($recentMessages, [
                'role' => 'system',
                'content' => $summary
            ]);
        }

        return $recentMessages;
    }

    protected function summarizeMessages(array $messages): string
    {
        $userMessages = [];
        $assistantMessages = [];

        foreach ($messages as $msg) {
            if ($msg['role'] === 'user') {
                $userMessages[] = $msg['content'];
            } elseif ($msg['role'] === 'assistant') {
                $assistantMessages[] = $msg['content'];
            }
        }

        $userTopics = $this->extractKeyTopics($userMessages);
        $assistantTopics = $this->extractKeyTopics($assistantMessages);

        return "ملخص المحادثات السابقة:\nالعميل سأل عن: " . implode('، ', $userTopics) .
            "\nتم الرد بخصوص: " . implode('، ', $assistantTopics);
    }

    protected function extractKeyTopics(array $messages, int $limit = 5): array
    {
        $allText = implode(' ', $messages);
        $words = preg_split('/\s+/', $allText, -1, PREG_SPLIT_NO_EMPTY);

        $words = array_filter($words, function ($word) {
            return mb_strlen($word) > 3;
        });

        $wordCounts = array_count_values($words);
        arsort($wordCounts);

        return array_slice(array_keys($wordCounts), 0, $limit);
    }

    public function compressMessage(string $message): string
    {
        $message = preg_replace('/\s+/', ' ', $message);
        $message = trim($message);

        return $message;
    }

    public function selectImportantMessages(array $messages, int $limit = 10): array
    {
        $scoredMessages = [];

        foreach ($messages as $index => $message) {
            $score = 0;

            if (
                mb_strpos($message['content'], '?') !== false ||
                mb_strpos($message['content'], '؟') !== false
            ) {
                $score += 3;
            }

            if ($message['role'] === 'user') {
                $score += 2;
            }

            if ($index >= count($messages) - 5) {
                $score += 5;
            }

            $scoredMessages[] = [
                'message' => $message,
                'score' => $score,
                'index' => $index
            ];
        }

        usort($scoredMessages, fn($a, $b) => $b['score'] <=> $a['score']);

        $selectedMessages = array_slice($scoredMessages, 0, $limit);
        usort($selectedMessages, fn($a, $b) => $a['index'] <=> $b['index']);

        return array_column($selectedMessages, 'message');
    }

    public function createSmartContext(array $allMessages, int $maxTokens = self::MAX_TOKENS_PER_REQUEST): array
    {
        if (count($allMessages) <= 10) {
            return $allMessages;
        }

        $recentCount = 5;
        $recentMessages = array_slice($allMessages, -$recentCount);
        $olderMessages = array_slice($allMessages, 0, -$recentCount);

        $importantOlder = $this->selectImportantMessages($olderMessages, 5);

        $combinedMessages = array_merge($importantOlder, $recentMessages);

        $totalTokens = $this->estimateTokens($combinedMessages);

        if ($totalTokens > $maxTokens) {
            $combinedMessages = $this->optimizeMessages($combinedMessages, $maxTokens);
        }

        return $combinedMessages;
    }

    public function logTokenUsage(string $instanceName, string $number, array $usage): void
    {
        // Log::info('Token Usage', [
        //     'instance' => $instanceName,
        //     'number' => $number,
        //     'prompt_tokens' => $usage['prompt_tokens'] ?? 0,
        //     'completion_tokens' => $usage['completion_tokens'] ?? 0,
        //     'total_tokens' => $usage['total_tokens'] ?? 0,
        // ]);
    }
}

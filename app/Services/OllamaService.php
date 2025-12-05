<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaService
{
    protected string $baseUrl;
    protected string $model;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.ollama.url', 'http://31.97.154.208:11434');
        $this->model = config('services.ollama.model', 'llama3.2:1b');
        $this->timeout = config('services.ollama.timeout', 120);
    }

    /**
     * Generate a response from Ollama AI
     *
     * @param string $prompt The user's message/prompt
     * @param string|null $systemPrompt Optional system prompt to set AI behavior
     * @param array $options Additional options for the API
     * @return array{success: bool, response: string|null, error: string|null}
     */
    public function generate(string $prompt, ?string $systemPrompt = null, array $options = []): array
    {
        try {
            $payload = [
                'model' => $options['model'] ?? $this->model,
                'prompt' => $prompt,
                'stream' => false,
            ];

            // Add system prompt if provided
            if ($systemPrompt) {
                $payload['system'] = $systemPrompt;
            }

            // Add any additional options
            if (!empty($options['temperature'])) {
                $payload['options']['temperature'] = $options['temperature'];
            }

            if (!empty($options['max_tokens'])) {
                $payload['options']['num_predict'] = $options['max_tokens'];
            }

            Log::info('Ollama API Request', [
                'url' => $this->baseUrl . '/api/generate',
                'model' => $payload['model'],
                'prompt_length' => strlen($prompt)
            ]);

            $response = Http::timeout($this->timeout)
                ->post($this->baseUrl . '/api/generate', $payload);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('Ollama API Success', [
                    'response_length' => strlen($data['response'] ?? ''),
                    'done' => $data['done'] ?? false
                ]);

                return [
                    'success' => true,
                    'response' => $data['response'] ?? null,
                    'error' => null,
                    'model' => $data['model'] ?? $this->model,
                    'total_duration' => $data['total_duration'] ?? null,
                ];
            }

            Log::error('Ollama API Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'response' => null,
                'error' => 'API request failed: ' . $response->status()
            ];
        } catch (\Exception $e) {
            Log::error('Ollama Service Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'response' => null,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate a chat response with conversation context
     *
     * @param array $messages Array of messages [['role' => 'user|assistant', 'content' => '...']]
     * @param string|null $systemPrompt Optional system prompt
     * @param array $options Additional options
     * @return array
     */
    public function chat(array $messages, ?string $systemPrompt = null, array $options = []): array
    {
        try {
            $payload = [
                'model' => $options['model'] ?? $this->model,
                'messages' => $messages,
                'stream' => false,
            ];

            if ($systemPrompt) {
                // Add system message at the beginning
                array_unshift($payload['messages'], [
                    'role' => 'system',
                    'content' => $systemPrompt
                ]);
            }

            $response = Http::timeout($this->timeout)
                ->post($this->baseUrl . '/api/chat', $payload);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'response' => $data['message']['content'] ?? null,
                    'error' => null,
                    'model' => $data['model'] ?? $this->model,
                ];
            }

            return [
                'success' => false,
                'response' => null,
                'error' => 'Chat API request failed: ' . $response->status()
            ];
        } catch (\Exception $e) {
            Log::error('Ollama Chat Exception', [
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'response' => null,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate AI response for WhatsApp customer support
     *
     * @param string $customerMessage The customer's message
     * @param string|null $context Additional context about the business/service
     * @return string The AI response or fallback message
     */
    public function generateSupportReply(string $customerMessage, ?string $context = null): string
    {
        $systemPrompt = "أنت مساعد دعم عملاء محترف وودود. تتحدث العربية بطلاقة.
قواعد مهمة:
- كن مهذباً ومحترفاً دائماً
- أجب بشكل مختصر ومفيد
- تجيب باللغة العربية
- استخدم الرموز التعبيرية بشكل معتدل
- إذا لم تعرف الإجابة، قل أنك ستحول الاستفسار للفريق المختص
- لا تقدم معلومات خاطئة أو وعود لا يمكن الوفاء بها";

        if ($context) {
            $systemPrompt .= "\n\nمعلومات إضافية عن الخدمة:\n" . $context;
        }

        $result = $this->generate($customerMessage, $systemPrompt, [
            'temperature' => 0.7,
            'max_tokens' => 500
        ]);

        if ($result['success'] && $result['response']) {
            return $result['response'];
        }

        // Fallback message if AI fails
        return "شكراً لتواصلك معنا! 🙏\nجاري تحويل استفسارك لأحد ممثلي خدمة العملاء.\nسيتم الرد عليك في أقرب وقت ممكن.";
    }

    /**
     * Generate AI response for WhatsApp customer support with streaming callback
     * This allows refreshing the typing indicator while waiting for AI response
     *
     * @param string $customerMessage The customer's message
     * @param callable $onProgress Callback function called periodically during streaming
     * @param string|null $context Additional context about the business/service
     * @return string The AI response or fallback message
     */
    public function generateSupportReplyWithCallback(string $customerMessage, callable $onProgress, ?string $context = null): string
    {
        $systemPrompt = "أنت مساعد دعم عملاء محترف وودود. تتحدث العربية بطلاقة.
قواعد مهمة:
- كن مهذباً ومحترفاً دائماً
- أجب بشكل مختصر ومفيد
- تجيب باللغة العربية
- استخدم الرموز التعبيرية بشكل معتدل
- إذا لم تعرف الإجابة، قل أنك ستحول الاستفسار للفريق المختص
- لا تقدم معلومات خاطئة أو وعود لا يمكن الوفاء بها";

        if ($context) {
            $systemPrompt .= "\n\nمعلومات إضافية عن الخدمة:\n" . $context;
        }

        try {
            $payload = [
                'model' => $this->model,
                'prompt' => $customerMessage,
                'system' => $systemPrompt,
                'stream' => true, // Enable streaming
                'options' => [
                    'temperature' => 0.7,
                    'num_predict' => 500
                ]
            ];

            Log::info('Ollama Streaming Request Started', [
                'prompt_length' => strlen($customerMessage)
            ]);

            // Use cURL for streaming support with progress callback
            $ch = curl_init();
            $fullResponse = '';
            $lastCallbackTime = time();
            $callbackInterval = 8; // Call progress callback every 8 seconds

            curl_setopt_array($ch, [
                CURLOPT_URL => $this->baseUrl . '/api/generate',
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_WRITEFUNCTION => function ($ch, $data) use (&$fullResponse, &$lastCallbackTime, $onProgress, $callbackInterval) {
                    // Parse streaming JSON responses (each line is a JSON object)
                    $lines = explode("\n", $data);
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (empty($line)) continue;

                        $json = json_decode($line, true);
                        if ($json && isset($json['response'])) {
                            $fullResponse .= $json['response'];
                        }
                    }

                    // Call progress callback periodically
                    $currentTime = time();
                    if ($currentTime - $lastCallbackTime >= $callbackInterval) {
                        $onProgress();
                        $lastCallbackTime = $currentTime;
                    }

                    return strlen($data);
                }
            ]);

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                Log::error('Ollama Streaming Error', ['error' => $error]);
                throw new \Exception("cURL error: $error");
            }

            if ($httpCode !== 200) {
                Log::error('Ollama Streaming HTTP Error', ['code' => $httpCode]);
                throw new \Exception("HTTP error: $httpCode");
            }

            Log::info('Ollama Streaming Response Complete', [
                'response_length' => strlen($fullResponse)
            ]);

            if (!empty($fullResponse)) {
                return $fullResponse;
            }

            // Fallback
            return "شكراً لتواصلك معنا! 🙏\nجاري تحويل استفسارك لأحد ممثلي خدمة العملاء.\nسيتم الرد عليك في أقرب وقت ممكن.";
        } catch (\Exception $e) {
            Log::error('Ollama Streaming Exception', [
                'message' => $e->getMessage()
            ]);

            // Re-throw to let caller handle fallback
            throw $e;
        }
    }

    /**
     * Check if Ollama service is available
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(5)->get($this->baseUrl . '/api/tags');
            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('Ollama service unavailable', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get available models from Ollama server
     *
     * @return array
     */
    public function getAvailableModels(): array
    {
        try {
            $response = Http::timeout(10)->get($this->baseUrl . '/api/tags');

            if ($response->successful()) {
                $data = $response->json();
                return $data['models'] ?? [];
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Failed to get Ollama models', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Set the model to use
     *
     * @param string $model
     * @return self
     */
    public function setModel(string $model): self
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Set timeout for requests
     *
     * @param int $seconds
     * @return self
     */
    public function setTimeout(int $seconds): self
    {
        $this->timeout = $seconds;
        return $this;
    }
}

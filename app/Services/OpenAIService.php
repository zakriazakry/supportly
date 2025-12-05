<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    protected ?string $apiKey;
    protected string $baseUrl;
    protected string $model;
    protected int $timeout;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key') ?? 'REMOVEDproj-TUvqNJFEiCSysMqk7jK-Jls9qgV3qhGn8_FypKq_I2IMA5kLDW3G1UU41NoQdjafzb3ZbcynKGT3BlbkFJNHfC42-ZwIcfH152_-KStyxxAyctuPatiXu496bt4xohdPOeYdsrnJuYOolKIUihdzADGEGZAA';
        $this->baseUrl = config('services.openai.base_url') ?? 'https://api.openai.com/v1';
        $this->model = config('services.openai.model') ?? 'gpt-4o-mini';
        $this->timeout = config('services.openai.timeout') ?? 60;
    }

    /**
     * Generate a response from OpenAI API
     *
     * @param string $prompt The user's message/prompt
     * @param string|null $systemPrompt Optional system prompt to set AI behavior
     * @param array $options Additional options for the API
     * @return array{success: bool, response: string|null, error: string|null}
     */
    public function generate(string $prompt, ?string $systemPrompt = null, array $options = []): array
    {
        try {
            $messages = [];
            Log::info('OpenAI API Request', [
                'url' => $this->baseUrl . '/chat/completions',
                'model' => $options['model'] ?? $this->model,
                'prompt_length' => strlen($prompt),
                'system_prompt' => $systemPrompt,
                'options' => $options
            ]);
            // Add system prompt if provided
            if ($systemPrompt) {
                $messages[] = [
                    'role' => 'system',
                    'content' => $systemPrompt
                ];
            }

            // Add user message
            $messages[] = [
                'role' => 'user',
                'content' => $prompt
            ];

            $payload = [
                'model' => $options['model'] ?? $this->model,
                'messages' => $messages,
                'temperature' => $options['temperature'] ?? 0.7,
                'max_tokens' => $options['max_tokens'] ?? 500,
            ];

            Log::info('OpenAI API Request', [
                'url' => $this->baseUrl . '/chat/completions',
                'model' => $payload['model'],
                'prompt_length' => strlen($prompt)
            ]);

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl . '/chat/completions', $payload);

            if ($response->successful()) {
                $data = $response->json();

                $responseText = $data['choices'][0]['message']['content'] ?? null;

                Log::info('OpenAI API Success', [
                    'response_length' => strlen($responseText ?? ''),
                    'usage' => $data['usage'] ?? null
                ]);

                return [
                    'success' => true,
                    'response' => $responseText,
                    'error' => null,
                    'model' => $data['model'] ?? $this->model,
                    'usage' => $data['usage'] ?? null,
                ];
            }

            Log::error('OpenAI API Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'response' => null,
                'error' => 'API request failed: ' . $response->status() . ' - ' . $response->body()
            ];
        } catch (\Exception $e) {
            Log::error('OpenAI Service Exception', [
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
     * @param array $messages Array of messages [['role' => 'user|assistant|system', 'content' => '...']]
     * @param string|null $systemPrompt Optional system prompt (will be prepended)
     * @param array $options Additional options
     * @return array
     */
    public function chat(array $messages, ?string $systemPrompt = null, array $options = []): array
    {
        try {
            $allMessages = [];

            // Add system prompt if provided
            if ($systemPrompt) {
                $allMessages[] = [
                    'role' => 'system',
                    'content' => $systemPrompt
                ];
            }

            // Add conversation messages
            $allMessages = array_merge($allMessages, $messages);

            $payload = [
                'model' => $options['model'] ?? $this->model,
                'messages' => $allMessages,
                'temperature' => $options['temperature'] ?? 0.7,
                'max_tokens' => $options['max_tokens'] ?? 500,
            ];

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl . '/chat/completions', $payload);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'response' => $data['choices'][0]['message']['content'] ?? null,
                    'error' => null,
                    'model' => $data['model'] ?? $this->model,
                    'usage' => $data['usage'] ?? null,
                ];
            }

            return [
                'success' => false,
                'response' => null,
                'error' => 'Chat API request failed: ' . $response->status()
            ];
        } catch (\Exception $e) {
            Log::error('OpenAI Chat Exception', [
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
     * Check if OpenAI service is available (API key is configured)
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        if (empty($this->apiKey)) {
            return false;
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ])
                ->get($this->baseUrl . '/models');

            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('OpenAI service unavailable', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get available models from OpenAI
     *
     * @return array
     */
    public function getAvailableModels(): array
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ])
                ->get($this->baseUrl . '/models');

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? [];
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Failed to get OpenAI models', ['error' => $e->getMessage()]);
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

    /**
     * Set API key
     *
     * @param string $apiKey
     * @return self
     */
    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    /**
     * Generate embeddings for text
     *
     * @param string|array $input Text or array of texts to embed
     * @param string $model Embedding model to use
     * @return array
     */
    public function createEmbeddings(string|array $input, string $model = 'text-embedding-3-small'): array
    {
        try {
            $payload = [
                'model' => $model,
                'input' => $input,
            ];

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl . '/embeddings', $payload);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'embeddings' => $data['data'] ?? [],
                    'usage' => $data['usage'] ?? null,
                    'error' => null,
                ];
            }

            return [
                'success' => false,
                'embeddings' => [],
                'error' => 'Embeddings API failed: ' . $response->status()
            ];
        } catch (\Exception $e) {
            Log::error('OpenAI Embeddings Exception', [
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'embeddings' => [],
                'error' => $e->getMessage()
            ];
        }
    }
}

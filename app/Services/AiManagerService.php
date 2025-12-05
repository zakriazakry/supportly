<?php

namespace App\Services;

class AiManagerService
{
    protected $openai;
    protected $ollama;

    public function __construct()
    {
        $this->openai = new OpenAIService();
        $this->ollama = new OllamaService();
    }

    /**
     * توليد نص من نموذج معين ومزود معين
     *
     * @param string $prompt
     * @param string|null $provider "openai" أو "ollama"
     * @param string|null $model اسم النموذج
     * @param array $options خيارات إضافية
     * @return string The generated response text
     */
    public function generate(string $prompt, ?string $provider = 'openai', ?string $model = null, array $options = []): string
    {
        $result = null;

        if ($provider === 'ollama') {
            $result = $this->ollama->generate($prompt, $model, $options);
        } else {
            // افتراضي OpenAI
            $result = $this->openai->generate($prompt, $model, $options);
        }

        // Extract the response string from the result array
        if (is_array($result) && !empty($result['success']) && !empty($result['response'])) {
            return $result['response'];
        }

        // Fallback message if AI fails
        return "شكراً لتواصلك معنا! 🙏\nجاري تحويل استفسارك لأحد ممثلي خدمة العملاء.\nسيتم الرد عليك في أقرب وقت ممكن.";
    }

    /**
     * محادثة مع نموذج محدد
     *
     * @param array $messages مصفوفة الرسائل [{"role":"user","content":"..."}, ...]
     * @param string|null $provider
     * @param string|null $model
     * @param array $options
     * @return string The generated response text
     */
    public function chat(array $messages, ?string $provider = 'openai', ?string $model = null, array $options = []): string
    {
        $result = null;

        if ($provider === 'ollama') {
            $result = $this->ollama->chat($messages, $model, $options);
        } else {
            $result = $this->openai->chat($messages, $model, $options);
        }

        // Extract the response string from the result array
        if (is_array($result) && !empty($result['success']) && !empty($result['response'])) {
            return $result['response'];
        }

        // Fallback message if AI fails
        return "شكراً لتواصلك معنا! 🙏\nجاري تحويل استفسارك لأحد ممثلي خدمة العملاء.\nسيتم الرد عليك في أقرب وقت ممكن.";
    }



    public function models(?string $provider = 'openai')
    {
        if ($provider === 'ollama') {
            return $this->ollama->getAvailableModels();
        }

        return $this->openai->getAvailableModels();
    }
}

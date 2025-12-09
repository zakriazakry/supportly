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
    public function generate(string $prompt, string $system_prompt, ?string $provider = 'openai', ?string $model = null, array $options = []): array|null
    {
        $result = null;

        if ($provider === 'openai') {
            $result = $this->openai->generate($prompt, $system_prompt, $options);
        } else {
            $result = $this->ollama->generate($prompt, $system_prompt, $options);
        }

        if (is_array($result) && !empty($result['success']) && !empty($result['response'])) {
            return $result;
        }
        return null;
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
    public function chat(array $messages, string $system_prompt, ?string $provider = 'openai', array $options = []): string
    {
        $result = null;

        if ($provider === 'openai') {
            $result = $this->openai->chat($messages, $system_prompt, $options);
        } else {
            $result = $this->ollama->chat($messages, $system_prompt, $options);
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
        if ($provider === 'openai') {
            return $this->openai->getAvailableModels();
        }

        return $this->ollama->getAvailableModels();
    }
}

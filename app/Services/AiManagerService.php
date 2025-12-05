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
     * @return mixed
     */
    public function generate(string $prompt, ?string $provider = 'openai', ?string $model = null, array $options = [])
    {
        if ($provider === 'ollama') {
            return $this->ollama->generate($prompt, $model, $options);
        }

        // افتراضي OpenAI
        return $this->openai->generate($prompt, $model, $options);
    }

    /**
     * محادثة مع نموذج محدد
     *
     * @param array $messages مصفوفة الرسائل [{"role":"user","content":"..."}, ...]
     * @param string|null $provider
     * @param string|null $model
     * @param array $options
     * @return mixed
     */
    public function chat(array $messages, ?string $provider = 'openai', ?string $model = null, array $options = [])
    {
        if ($provider === 'ollama') {
            return $this->ollama->chat($messages, $model, $options);
        }

        return $this->openai->chat($messages, $model, $options);
    }



    public function models(?string $provider = 'openai')
    {
        if ($provider === 'ollama') {
            return $this->ollama->getAvailableModels();
        }

        return $this->openai->getAvailableModels();
    }
}

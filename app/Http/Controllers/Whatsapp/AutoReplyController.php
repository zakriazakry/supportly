<?php

namespace App\Http\Controllers\Whatsapp;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppAiReply;
use App\Models\WhatsAppAutoReply;
use App\Models\WhatsAppAutoReplyRoles;
use App\Models\WhatsAppInstance;
use App\Services\AiManagerService;
use App\Services\EvolutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AutoReplyController extends Controller
{
    protected EvolutionService $evolutionService;
    protected AiManagerService $ai;

    public function __construct(EvolutionService $evolutionService, AiManagerService $ai)
    {
        $this->evolutionService = $evolutionService;
        $this->ai = $ai;
    }

    // ==========================================
    //     إدارة إعدادات الرد التلقائي العامة
    // ==========================================

    /**
     * عرض إعدادات الرد التلقائي للـ Instance
     */
    public function getAutoReplySettings(int $instanceId): JsonResponse
    {
        $instance = WhatsAppInstance::find($instanceId);

        if (!$instance) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على الـ Instance',
            ], 404);
        }

        $settings = $instance->getOrCreateAutoReply();
        $rules = $settings->rules()->orderBy('priority')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'settings' => $settings,
                'rules' => $rules,
                'rules_count' => $rules->count(),
                'active_rules_count' => $rules->where('is_active', true)->count(),
            ],
        ]);
    }

    /**
     * تحديث إعدادات الرد التلقائي العامة
     */
    public function updateAutoReplySettings(Request $request, int $instanceId): JsonResponse
    {
        $instance = WhatsAppInstance::find($instanceId);

        if (!$instance) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على الـ Instance',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'is_active' => 'sometimes|boolean',
            'stop_on_owner_message' => 'sometimes|boolean',
            'stop_on_keyword' => 'sometimes|boolean',
            'stop_keywords' => 'nullable|array',
            'stop_duration' => 'sometimes|integer|min:1',
            'custom_stop_duration' => 'sometimes|integer|min:1',
            'ignore_groups' => 'sometimes|boolean',
            'show_typing' => 'sometimes|boolean',
            'reply_once' => 'sometimes|boolean',
            'reply_delay' => 'sometimes|integer|min:0|max:60',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صالحة',
                'errors' => $validator->errors(),
            ], 422);
        }

        $autoReply = $instance->getOrCreateAutoReply();
        $autoReply->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الإعدادات بنجاح',
            'data' => $autoReply->fresh(),
        ]);
    }

    /**
     * تفعيل/تعطيل الرد التلقائي
     */
    public function toggleAutoReply(int $instanceId): JsonResponse
    {
        $instance = WhatsAppInstance::find($instanceId);

        if (!$instance) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على الـ Instance',
            ], 404);
        }

        $autoReply = $instance->getOrCreateAutoReply();
        $autoReply->update(['is_active' => !$autoReply->is_active]);

        return response()->json([
            'success' => true,
            'message' => $autoReply->is_active ? 'تم تفعيل الرد التلقائي' : 'تم تعطيل الرد التلقائي',
            'data' => ['is_active' => $autoReply->is_active],
        ]);
    }

    // ==========================================
    //        إدارة قواعد الرد التلقائي
    // ==========================================

    /**
     * عرض جميع القواعد للـ Instance
     */
    public function getRules(int $instanceId): JsonResponse
    {
        $instance = WhatsAppInstance::find($instanceId);

        if (!$instance) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على الـ Instance',
            ], 404);
        }

        $autoReply = $instance->getOrCreateAutoReply();
        $rules = $autoReply->rules()->orderBy('priority')->get();

        return response()->json([
            'success' => true,
            'data' => $rules,
        ]);
    }

    /**
     * عرض قاعدة محددة
     */
    public function getRule(int $instanceId, int $ruleId): JsonResponse
    {
        $rule = WhatsAppAutoReplyRoles::where('id', $ruleId)
            ->whereHas('autoReply', fn($q) => $q->where('whats_app_instance_id', $instanceId))
            ->first();

        if (!$rule) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على القاعدة',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $rule,
        ]);
    }

    /**
     * إنشاء قاعدة جديدة
     */
    public function createRule(Request $request, int $instanceId): JsonResponse
    {
        $instance = WhatsAppInstance::find($instanceId);

        if (!$instance) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على الـ Instance',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'is_active' => 'sometimes|boolean',
            'priority' => 'sometimes|integer|min:0',
            // إعدادات المشغّل
            'trigger_type' => 'required|in:keyword,regex,all,contains',
            'trigger_value' => 'nullable|string',
            'trigger_keywords' => 'nullable|array',
            'case_insensitive' => 'sometimes|boolean',
            'exact_match' => 'sometimes|boolean',
            // إعدادات الرد
            'response_type' => 'required|in:text,media,template,buttons',
            'response_value' => 'nullable|string',
            'random_response' => 'sometimes|boolean',
            'alternative_responses' => 'nullable|array',
            // إعدادات الوسائط
            'media_type' => 'nullable|in:image,video,document,audio',
            'media_url' => 'nullable|string|max:500',
            'media_caption' => 'nullable|string',
            // إعدادات الأزرار
            'buttons_text' => 'nullable|string',
            'buttons' => 'nullable|array',
            // إعدادات الجدولة
            'has_schedule' => 'sometimes|boolean',
            'schedule_start' => 'nullable|date_format:H:i:s',
            'schedule_end' => 'nullable|date_format:H:i:s',
            'schedule_days' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صالحة',
                'errors' => $validator->errors(),
            ], 422);
        }

        $autoReply = $instance->getOrCreateAutoReply();

        // حساب الأولوية إذا لم يتم تحديدها
        $data = $validator->validated();
        if (!isset($data['priority'])) {
            $data['priority'] = $autoReply->rules()->max('priority') + 1 ?? 0;
        }

        $data['whats_app_instance_id'] = $autoReply->whats_app_instance_id;

        $rule = WhatsAppAutoReplyRoles::create($data);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء القاعدة بنجاح',
            'data' => $rule,
        ], 201);
    }

    /**
     * تحديث قاعدة موجودة
     */
    public function updateRule(Request $request, int $instanceId, int $ruleId): JsonResponse
    {
        $rule = WhatsAppAutoReplyRoles::where('id', $ruleId)
            ->whereHas('autoReply', fn($q) => $q->where('whats_app_instance_id', $instanceId))
            ->first();

        if (!$rule) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على القاعدة',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'is_active' => 'sometimes|boolean',
            'priority' => 'sometimes|integer|min:0',
            // إعدادات المشغّل
            'trigger_type' => 'sometimes|in:keyword,regex,all,contains',
            'trigger_value' => 'nullable|string',
            'trigger_keywords' => 'nullable|array',
            'case_insensitive' => 'sometimes|boolean',
            'exact_match' => 'sometimes|boolean',
            // إعدادات الرد
            'response_type' => 'sometimes|in:text,media,template,buttons',
            'response_value' => 'nullable|string',
            'random_response' => 'sometimes|boolean',
            'alternative_responses' => 'nullable|array',
            // إعدادات الوسائط
            'media_type' => 'nullable|in:image,video,document,audio',
            'media_url' => 'nullable|string|max:500',
            'media_caption' => 'nullable|string',
            // إعدادات الأزرار
            'buttons_text' => 'nullable|string',
            'buttons' => 'nullable|array',
            // إعدادات الجدولة
            'has_schedule' => 'sometimes|boolean',
            'schedule_start' => 'nullable|date_format:H:i:s',
            'schedule_end' => 'nullable|date_format:H:i:s',
            'schedule_days' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صالحة',
                'errors' => $validator->errors(),
            ], 422);
        }

        $rule->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث القاعدة بنجاح',
            'data' => $rule->fresh(),
        ]);
    }

    /**
     * حذف قاعدة
     */
    public function deleteRule(int $instanceId, int $ruleId): JsonResponse
    {
        $rule = WhatsAppAutoReplyRoles::where('id', $ruleId)
            ->whereHas('autoReply', fn($q) => $q->where('whats_app_instance_id', $instanceId))
            ->first();

        if (!$rule) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على القاعدة',
            ], 404);
        }

        $rule->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف القاعدة بنجاح',
        ]);
    }

    /**
     * تفعيل/تعطيل قاعدة
     */
    public function toggleRule(int $instanceId, int $ruleId): JsonResponse
    {
        $rule = WhatsAppAutoReplyRoles::where('id', $ruleId)
            ->whereHas('autoReply', fn($q) => $q->where('whats_app_instance_id', $instanceId))
            ->first();

        if (!$rule) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على القاعدة',
            ], 404);
        }

        $rule->update(['is_active' => !$rule->is_active]);

        return response()->json([
            'success' => true,
            'message' => $rule->is_active ? 'تم تفعيل القاعدة' : 'تم تعطيل القاعدة',
            'data' => ['is_active' => $rule->is_active],
        ]);
    }

    /**
     * إعادة ترتيب القواعد
     */
    public function reorderRules(Request $request, int $instanceId): JsonResponse
    {
        $instance = WhatsAppInstance::find($instanceId);

        if (!$instance) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على الـ Instance',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'rules' => 'required|array',
            'rules.*.id' => 'required|integer',
            'rules.*.priority' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صالحة',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            foreach ($request->rules as $ruleData) {
                WhatsAppAutoReplyRoles::where('id', $ruleData['id'])
                    ->whereHas('autoReply', fn($q) => $q->where('whats_app_instance_id', $instanceId))
                    ->update(['priority' => $ruleData['priority']]);
            }
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إعادة ترتيب القواعد بنجاح',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إعادة الترتيب',
            ], 500);
        }
    }

    // ==========================================
    //      إدارة إعدادات الذكاء الاصطناعي
    // ==========================================

    /**
     * عرض إعدادات الذكاء الاصطناعي
     */
    public function getAiSettings(int $instanceId): JsonResponse
    {
        $instance = WhatsAppInstance::find($instanceId);

        if (!$instance) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على الـ Instance',
            ], 404);
        }

        $settings = $instance->getOrCreateAiReply();

        return response()->json([
            'success' => true,
            'data' => [
                'settings' => $settings,
                'has_api_key' => $settings->hasApiKey(),
                'available_providers' => ['openai', 'ONU'],
                'available_models' => WhatsAppAiReply::getAvailableModels($settings->provider),
            ],
        ]);
    }

    /**
     * تحديث إعدادات الذكاء الاصطناعي
     */
    public function updateAiSettings(Request $request, int $instanceId): JsonResponse
    {
        $instance = WhatsAppInstance::find($instanceId);

        if (!$instance) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على الـ Instance',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'is_active' => 'sometimes|boolean',
            'provider' => 'sometimes|in:openai,ONU',
            'api_key' => 'nullable|string',
            'model' => 'sometimes|string',
            'temperature' => 'sometimes|numeric|min:0|max:2',
            'max_tokens' => 'sometimes|integer|min:1|max:4096',
            'response_delay' => 'sometimes|integer|min:0|max:60',
            'system_prompt' => 'nullable|string',
            'stop_on_owner_message' => 'sometimes|boolean',
            'stop_on_keyword' => 'sometimes|boolean',
            'stop_keywords' => 'nullable|array',
            'stop_duration' => 'sometimes|integer|min:1',
            'custom_stop_duration' => 'sometimes|integer|min:1',
            'include_context' => 'sometimes|boolean',
            'context_messages_count' => 'sometimes|integer|min:1|max:20',
            'show_typing' => 'sometimes|boolean',
            'ignore_groups' => 'sometimes|boolean',
            'only_first_message' => 'sometimes|boolean',
            'excluded_numbers' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صالحة',
                'errors' => $validator->errors(),
            ], 422);
        }

        $aiReply = $instance->getOrCreateAiReply();

        // تحديث البيانات (باستثناء api_key الفارغ)
        $data = $validator->validated();
        if (isset($data['api_key']) && empty($data['api_key'])) {
            unset($data['api_key']);
        }

        $aiReply->update($data);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث إعدادات الذكاء الاصطناعي بنجاح',
            'data' => $aiReply->fresh(),
        ]);
    }

    /**
     * تفعيل/تعطيل الذكاء الاصطناعي
     */
    public function toggleAi(int $instanceId): JsonResponse
    {
        $instance = WhatsAppInstance::find($instanceId);

        if (!$instance) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على الـ Instance',
            ], 404);
        }

        $aiReply = $instance->getOrCreateAiReply();
        $aiReply->update(['is_active' => !$aiReply->is_active]);

        return response()->json([
            'success' => true,
            'message' => $aiReply->is_active ? 'تم تفعيل الذكاء الاصطناعي' : 'تم تعطيل الذكاء الاصطناعي',
            'data' => ['is_active' => $aiReply->is_active],
        ]);
    }

    /**
     * اختبار الذكاء الاصطناعي
     */
    public function testAi(Request $request, int $instanceId): JsonResponse
    {
        $instance = WhatsAppInstance::find($instanceId);

        if (!$instance) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على الـ Instance',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صالحة',
                'errors' => $validator->errors(),
            ], 422);
        }

        $aiReply = $instance->getOrCreateAiReply();

        if (!$aiReply->hasApiKey() && $aiReply->provider === 'openai') {
            return response()->json([
                'success' => false,
                'message' => 'يرجى إضافة مفتاح API أولاً',
            ], 400);
        }

        try {
            $response = $this->ai->generate(
                $request->message,
                $aiReply->system_prompt ?? '',
                $aiReply->provider
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'response' => $response,
                    'message' => $request->message,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('AI Test failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'فشل الاختبار: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * الحصول على إحصائيات الذكاء الاصطناعي
     */
    public function getAiStats(int $instanceId): JsonResponse
    {
        $instance = WhatsAppInstance::find($instanceId);

        if (!$instance) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على الـ Instance',
            ], 404);
        }

        $aiReply = $instance->getOrCreateAiReply();

        return response()->json([
            'success' => true,
            'data' => [
                'total_messages' => $aiReply->total_messages,
                'total_tokens_used' => $aiReply->total_tokens_used,
                'is_active' => $aiReply->is_active,
                'provider' => $aiReply->provider,
                'model' => $aiReply->model,
            ],
        ]);
    }

    // ==========================================
    //           معالجة الرسائل الواردة
    // ==========================================

    /**
     * معالجة الرسالة الواردة (Webhook)
     */
    public function whenReceiveTextMessage(array $data): void
    {
        $instanceName = $data['instanceName'] ?? null;
        $message = $data['message'] ?? null;
        $fromNumber = $data['form_number'] ?? null;
        $remoteJid = $data['remote_jid'] ?? null;
        $isGroup = str_contains($remoteJid ?? '', '@g.us');

        if (!$instanceName || !$fromNumber || !$message) {
            Log::warning('Missing required fields in WhatsApp webhook', $data);
            return;
        }

        $instance = WhatsAppInstance::where('instance_name', $instanceName)->first();
        if (!$instance) {
            Log::error('WhatsApp instance not found', ['instance_name' => $instanceName]);
            return;
        }

        // تحديد الرسالة كمقروءة
        $messageKey = $data['key'] ?? null;
        if ($messageKey) {
            $messageKey['remoteJid'] = $this->extractJid($messageKey['remoteJid'], $messageKey['remoteJidAlt'] ?? null);
            $this->evolutionService->markAsRead($instanceName, [$messageKey]);
        }

        // معالجة الرد التلقائي (قواعد)
        $this->processAutoReply($instance, $fromNumber, $message, $isGroup);

        // معالجة الذكاء الاصطناعي
        $this->processAiReply($instance, $fromNumber, $message, $isGroup);
    }

    /**
     * معالجة الرد التلقائي باستخدام القواعد
     */
    protected function processAutoReply(WhatsAppInstance $instance, string $number, string $message, bool $isGroup): void
    {
        $autoReply = $instance->autoReply;

        if (!$autoReply || !$autoReply->is_active) {
            return;
        }

        // تجاهل المجموعات إذا كان الإعداد مفعلاً
        if ($isGroup && $autoReply->ignore_groups) {
            return;
        }

        // البحث عن قاعدة مطابقة
        $matchedRule = $autoReply->activeRules()
            ->get()
            ->first(fn($rule) => $rule->matchesMessage($message));

        if (!$matchedRule) {
            return;
        }

        // إظهار "جاري الكتابة" إذا كان مفعلاً
        if ($autoReply->show_typing) {
            $this->evolutionService->sendChatPresence($instance->instance_name, $number, 'composing', $autoReply->reply_delay * 1000);
        }

        // إرسال الرد
        try {
            switch ($matchedRule->response_type) {
                case 'text':
                    $responseText = $this->processVariables($matchedRule->getResponse(), $number);
                    $this->evolutionService->sendText($instance->instance_name, $number, $responseText);
                    break;

                case 'media':
                    $this->sendMediaResponse($instance, $number, $matchedRule);
                    break;

                case 'buttons':
                    $this->sendButtonsResponse($instance, $number, $matchedRule);
                    break;
            }

            $autoReply->incrementReplies();

            Log::info('Auto Reply sent', [
                'instance' => $instance->instance_name,
                'rule' => $matchedRule->name,
                'to' => $number,
            ]);
        } catch (\Exception $e) {
            Log::error('Auto Reply failed', [
                'error' => $e->getMessage(),
                'instance' => $instance->instance_name,
                'rule' => $matchedRule->name,
            ]);
        }
    }

    /**
     * معالجة الرد بالذكاء الاصطناعي
     */
    protected function processAiReply(WhatsAppInstance $instance, string $number, string $message, bool $isGroup): void
    {
        $aiReply = $instance->aiReply;

        if (!$aiReply || !$aiReply->is_active) {
            return;
        }

        // تجاهل المجموعات إذا كان الإعداد مفعلاً
        if ($isGroup && $aiReply->ignore_groups) {
            return;
        }

        // التحقق من الأرقام المستثناة
        if ($aiReply->isNumberExcluded($number)) {
            return;
        }

        try {
            // إظهار "جاري الكتابة"
            if ($aiReply->show_typing) {
                $this->evolutionService->sendChatPresence($instance->instance_name, $number, 'composing', $aiReply->response_delay * 1000);
            }

            // توليد الرد
            $aiResponse = $this->ai->generate(
                $message,
                $aiReply->system_prompt ?? '',
                $aiReply->provider
            );

            // إرسال الرد
            $this->evolutionService->sendText($instance->instance_name, $number, $aiResponse);

            $aiReply->incrementStats();

            Log::info('AI Reply sent', [
                'instance' => $instance->instance_name,
                'to' => $number,
                'response_length' => strlen($aiResponse),
            ]);
        } catch (\Exception $e) {
            Log::error('AI Reply failed', [
                'error' => $e->getMessage(),
                'instance' => $instance->instance_name,
                'to' => $number,
            ]);

            // رسالة احتياطية
            $this->evolutionService->sendText(
                $instance->instance_name,
                $number,
                "شكراً لتواصلك معنا! 🙏\nنعتذر عن التأخير، سيتم الرد عليك قريباً."
            );
        }
    }

    // ==========================================
    //              دوال مساعدة
    // ==========================================

    /**
     * استبدال المتغيرات في النص
     */
    protected function processVariables(string $text, string $number): string
    {
        $replacements = [
            '<phone>' => preg_replace('/[^0-9]/', '', $number),
            '<name>' => 'العميل', // يمكن استبدالها بالاسم الفعلي إذا كان متاحاً
            '<date>' => now()->format('Y-m-d'),
            '<time>' => now()->format('H:i'),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }

    /**
     * إرسال رد وسائط
     */
    protected function sendMediaResponse(WhatsAppInstance $instance, string $number, WhatsAppAutoReplyRoles $rule): void
    {
        $caption = $rule->media_caption ? $this->processVariables($rule->media_caption, $number) : null;

        match ($rule->media_type) {
            // 'image' => $this->evolutionService->sendImage($instance->instance_name, $number, $rule->media_url, $caption),
            // 'video' => $this->evolutionService->sendVideo($instance->instance_name, $number, $rule->media_url, $caption),
            // 'document' => $this->evolutionService->sendDocument($instance->instance_name, $number, $rule->media_url, $caption),
            'audio' => $this->evolutionService->sendAudio($instance->instance_name, $number, $rule->media_url),
            default => null,
        };
    }

    /**
     * إرسال رد أزرار
     */
    protected function sendButtonsResponse(WhatsAppInstance $instance, string $number, WhatsAppAutoReplyRoles $rule): void
    {
        // يمكن تخصيص هذه الدالة حسب API المستخدم
        $text = $this->processVariables($rule->buttons_text ?? '', $number);
        $this->evolutionService->sendText($instance->instance_name, $number, $text);
    }

    /**
     * استخراج JID
     */
    protected function extractJid(?string $remoteJid, ?string $remoteJidAlt): ?string
    {
        foreach ([$remoteJid, $remoteJidAlt] as $jid) {
            if (!$jid) continue;
            if (preg_match('/^(\d+)@s\.whatsapp\.net$/', $jid)) {
                return $jid;
            }
        }
        return null;
    }
}

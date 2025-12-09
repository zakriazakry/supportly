<?php

namespace App\Http\Controllers\Whatsapp;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppAiReply;
use App\Models\WhatsAppAutoReply;
use App\Models\WhatsAppAutoReplyRoles;
use App\Models\WhatsAppAutoReplyStop;
use App\Models\WhatsAppInstance;
use App\Models\WhatsAppMessage;
use App\Services\AiManagerService;
use App\Services\EvolutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use phpDocumentor\Reflection\Types\Boolean;
use Ramsey\Uuid\Uuid;

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
            $generate = $this->ai->generate(
                $request->message,
                $aiReply->system_prompt ?? '',
                $aiReply->provider
            );

            if (!$generate) {
                return responseFormat('فشل الاختبار: ', 500);
            }
            $response =  $generate['response'];
            return responseFormat([
                'response' => $response,
                'message' => $request->message,
            ], 200);
        } catch (\Exception $e) {
            Log::error('AI Test failed', ['error' => $e->getMessage()]);
            return responseFormat('فشل الاختبار: ' . $e->getMessage(), 500);
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
    //        إدارة الإيقافات المؤقتة
    // ==========================================

    /**
     * الحصول على قائمة الإيقافات النشطة
     */
    public function getActiveStops(int $instanceId): JsonResponse
    {
        $instance = WhatsAppInstance::find($instanceId);

        if (!$instance) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على الـ Instance',
            ], 404);
        }

        $stops = WhatsAppAutoReplyStop::where('whats_app_instance_id', $instanceId)
            ->active()
            ->orderBy('stopped_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $stops,
        ]);
    }

    /**
     * إلغاء إيقاف مؤقت
     */
    public function removeStop(int $instanceId, int $stopId): JsonResponse
    {
        $stop = WhatsAppAutoReplyStop::where('id', $stopId)
            ->where('whats_app_instance_id', $instanceId)
            ->first();

        if (!$stop) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على الإيقاف',
            ], 404);
        }

        $stop->deactivate();

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء الإيقاف بنجاح',
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
        $pushName = $data['pushName'] ?? null;
        $fromMe = $data['fromMe'] ?? null;
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
        if ($this->processAutoReply($instance, $fromNumber, $message, $isGroup, $pushName, $fromMe)) {
            return;
        }

        // معالجة الذكاء الاصطناعي
        $this->processAiReply($instance, $fromNumber, $message, $isGroup, $pushName, $fromMe);
    }

    /**
     * معالجة الرد التلقائي باستخدام القواعد
     */
    protected function processAutoReply(WhatsAppInstance $instance, string $number, string $message, bool $isGroup, string $pushName, bool $fromMe): bool
    {
        $autoReply = $instance->autoReply;

        if (!$autoReply || !$autoReply->is_active) {
            return false;
        }

        // تجاهل المجموعات إذا كان الإعداد مفعلاً
        if ($isGroup && $autoReply->ignore_groups) {
            return false;
        }
        // ✅ التحقق من وجود إيقاف مؤقت نشط لهذه الجهة
        if (WhatsAppAutoReplyStop::hasActiveStop($instance->id, $number)) {
            Log::info('Auto Reply is stopped for this contact', [
                'instance' => $instance->instance_name,
                'number' => $number,
            ]);
            return true; // نوقف المعالجة
        }
        WhatsAppAutoReplyStop::where('whats_app_instance_id', $instance->id)->where('contact_number', $number)->delete();

        // ✅ التحقق من كلمات الإيقاف إذا كانت الميزة مفعلة
        if ($autoReply->stop_on_keyword && !empty($autoReply->stop_keywords) && $fromMe) {
            $messageLower = mb_strtolower(trim($message));

            foreach ($autoReply->stop_keywords as $keyword) {
                $keywordLower = mb_strtolower(trim($keyword));

                // التحقق من تطابق الكلمة
                if ($messageLower === $keywordLower || str_contains($messageLower, $keywordLower)) {
                    // إنشاء إيقاف جديد
                    WhatsAppAutoReplyStop::createStop(
                        $instance->id,
                        $number,
                        $autoReply->stop_duration ?? 30,
                        'keyword',
                        $keyword
                    );

                    Log::info('Auto Reply stopped due to keyword', [
                        'instance' => $instance->instance_name,
                        'number' => $number,
                        'keyword' => $keyword,
                        'duration' => $autoReply->stop_duration,
                    ]);

                    return true; // نوقف الرد التلقائي
                }
            }
        }

        // ✅ التحقق من رسالة المالك
        if ($autoReply->stop_on_owner_message && $this->hasOwnerMessageRecently($instance, $number, 5) && $fromMe) {
            // إنشاء إيقاف جديد
            WhatsAppAutoReplyStop::createStop(
                $instance->id,
                $number,
                $autoReply->stop_duration ?? 30,
                'owner_message',
                null
            );

            Log::info('Auto Reply stopped due to owner message', [
                'instance' => $instance->instance_name,
                'number' => $number,
                'duration' => $autoReply->stop_duration,
            ]);

            return true; // نوقف الرد التلقائي
        }

        if ($fromMe) {
            return false;
        }
        // البحث عن قاعدة مطابقة
        $matchedRule = $autoReply->activeRules()
            ->get()
            ->first(fn($rule) => $rule->matchesMessage($message));

        if (!$matchedRule) {
            return false;
        }

        // إظهار "جاري الكتابة" إذا كان مفعلاً
        if ($autoReply->show_typing) {
            $this->evolutionService->sendChatPresence($instance->instance_name, $number, 'composing', $autoReply->reply_delay * 1000);
        }

        // إرسال الرد
        try {
            switch ($matchedRule->response_type) {
                case 'text':
                    $responseText = $this->processVariables($matchedRule->getResponse(), $number, $pushName);
                    $this->evolutionService->sendText($instance->instance_name, $number, $responseText);
                    break;

                case 'media':
                    $this->sendMediaResponse($instance, $number, $matchedRule, $pushName);
                    break;

                case 'buttons':
                    $this->sendButtonsResponse($instance, $number, $matchedRule, $pushName);
                    break;
            }

            $autoReply->incrementReplies();

            Log::info('Auto Reply sent', [
                'instance' => $instance->instance_name,
                'rule' => $matchedRule->name,
                'to' => $number,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Auto Reply failed', [
                'error' => $e->getMessage(),
                'instance' => $instance->instance_name,
                'rule' => $matchedRule->name,
            ]);

            return false;
        }
        return false;
    }

    /**
     * معالجة الرد بالذكاء الاصطناعي
     */

    protected function processAiReply(WhatsAppInstance $instance, string $number, string $message, bool $isGroup, string $pushName, bool $fromMe): void
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

        // ✅ التحقق من وجود إيقاف مؤقت نشط لهذه الجهة
        if (WhatsAppAutoReplyStop::hasActiveStop($instance->id, $number)) {
            Log::info('AI Reply is stopped for this contact', [
                'instance' => $instance->instance_name,
                'number' => $number,
            ]);
            return; // نوقف المعالجة
        }
        WhatsAppAutoReplyStop::where('whats_app_instance_id', $instance->id)->where('contact_number', $number)->delete();

        // ✅ التحقق من كلمات الإيقاف إذا كانت الميزة مفعلة
        if ($aiReply->stop_on_keyword && !empty($aiReply->stop_keywords) && $fromMe) {
            $messageLower = mb_strtolower(trim($message));

            foreach ($aiReply->stop_keywords as $keyword) {
                $keywordLower = mb_strtolower(trim($keyword));

                // التحقق من تطابق الكلمة
                if ($messageLower === $keywordLower || str_contains($messageLower, $keywordLower)) {
                    // إنشاء إيقاف جديد
                    WhatsAppAutoReplyStop::createStop(
                        $instance->id,
                        $number,
                        $aiReply->stop_duration ?? 30,
                        'keyword',
                        $keyword
                    );

                    Log::info('AI Reply stopped due to keyword', [
                        'instance' => $instance->instance_name,
                        'number' => $number,
                        'keyword' => $keyword,
                        'duration' => $aiReply->stop_duration,
                    ]);

                    return; // نوقف الرد بالذكاء الاصطناعي
                }
            }
        }

        // ✅ التحقق من رسالة المالك
        if ($aiReply->stop_on_owner_message && $this->hasOwnerMessageRecently($instance, $number, 5) && $fromMe) {
            // إنشاء إيقاف جديد
            WhatsAppAutoReplyStop::createStop(
                $instance->id,
                $number,
                $aiReply->stop_duration ?? 30,
                'owner_message',
                null
            );

            Log::info('AI Reply stopped due to owner message', [
                'instance' => $instance->instance_name,
                'number' => $number,
                'duration' => $aiReply->stop_duration,
            ]);

            return; // نوقف الرد بالذكاء الاصطناعي
        }
        if ($fromMe) {
            return;
        }
        // ------------------

        try {
            DB::beginTransaction(); // 🟢 بدء المعاملة
            $time = date('H:i:s');
            $date = date('Y-m-d');
            $day = date('l');
            $varSTR = " <name> : {$pushName} <phone> : {$number} <time> : {$time} <date> : {$date} <day> : {$day}";
            $system_prompt = "إسم المستخدم : " . $pushName . "\n رقم المستخدم : " . $number . "\n استخدمه اذا اردت \n" . $aiReply->system_prompt ?? '';

            // � تحويل الرسائل من قاعدة البيانات إلى تنسيق OpenAI
            $formattedMessages = [];

            // �📝 جلب الرسائل السابقة فقط إذا كان include_context مفعلاً
            if ($aiReply->include_context) {
                $contextCount = $aiReply->context_messages_count ?? 5;
                $oldMessages = $instance->messages()
                    ->where('remote_jid', $number)
                    ->orderBy('created_at', 'desc')
                    ->limit($contextCount * 2) // x2 لأننا نريد رسائل المستخدم والردود
                    ->get()
                    ->reverse(); // نعكسها للحصول على الترتيب الزمني الصحيح

                // تحويل الرسائل إلى تنسيق OpenAI
                foreach ($oldMessages as $msg) {
                    $formattedMessages[] = [
                        'role' => $msg['from_me'] ? 'assistant' : 'user',
                        'content' => $msg['message_content'] ?? ''
                    ];
                }
            }

            // ➕ إضافة الرسالة الجديدة
            $formattedMessages[] = [
                'role' => 'user',
                'content' => $message
            ];

            // 📊 تسجيل تفاصيل الرسائل المُرسلة للذكاء الاصطناعي
            Log::info('🔄 Sending messages to AI', [
                'instance' => $instance->instance_name,
                'number' => $number,
                'include_context' => $aiReply->include_context,
                'context_messages_count' => $aiReply->context_messages_count,
                'total_messages_sent' => count($formattedMessages),
                'messages' => $formattedMessages, // 📝 جميع الرسائل المُرسلة
                'provider' => $aiReply->provider,
                'model' => $aiReply->model,
            ]);

            // 🤖 توليد الرد
            $aiResponse = $this->ai->chat(
                $formattedMessages,
                $system_prompt ?? '',
                $aiReply->provider,
                null,
                [
                    'model' => $aiReply->model,
                    'temperature' => $aiReply->temperature,
                    'max_tokens' => $aiReply->max_tokens,
                ]
            );


            if (!$aiResponse || !is_array($aiResponse) || empty($aiResponse['response'])) {
                Log::error('AI Reply failed', [
                    'error' => 'Failed to generate response',
                    'instance' => $instance->instance_name,
                    'ai_result' => $aiResponse
                ]);
                DB::rollBack();
                return;
            }

            // 📤 استخراج النص من النتيجة
            $responseText = $aiResponse['response'];

            Log::info('AI Response Generated', $aiResponse);


            // 💾 تخزين الرسالة الواردة من المستخدم
            $instance->messages()->create([
                'instance_id' => $instance->id,
                'message_id' => 'user_' . time() . '_' . rand(1000, 9999),
                'remote_jid' => $number,
                'from_me' => false,
                'message_type' => 'text',
                'message_content' => $message,
                'sent_at' => now(),
                'status' => 'delivered',
                'delivered_at' => now(),
                'read_at' => now(),
            ]);

            // 💾 تخزين الرد المُولّد من AI
            $instance->messages()->create([
                'instance_id' => $instance->id,
                'message_id' => 'ai_' . time() . '_' . rand(1000, 9999),
                'remote_jid' => $number,
                'from_me' => true,
                'message_type' => 'text',
                'message_content' => $responseText,
                'status' => 'delivered',
                'sent_at' => now(),
            ]);

            DB::commit(); // ✔️ نجاح المعاملة

            // إظهار "جاري الكتابة"
            if ($aiReply->show_typing) {
                $this->evolutionService->sendChatPresence(
                    $instance->instance_name,
                    $number,
                    'composing',
                    $aiReply->response_delay * 1000
                );
            }

            // إرسال الرد
            $this->evolutionService->sendText($instance->instance_name, $number, $responseText);

            $aiReply->incrementStats();

            Log::info('AI Reply sent', [
                'instance' => $instance->instance_name,
                'to' => $number,
                'response_length' => strlen($responseText),
            ]);
        } catch (\Exception $e) {

            DB::rollBack(); // ❗ فشل المعاملة

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
    protected function processVariables(string $text, string $number, string $pushName): string
    {
        $replacements = [
            '<phone>' => preg_replace('/[^0-9]/', '', $number),
            '<name>' => $pushName, // يمكن استبدالها بالاسم الفعلي إذا كان متاحاً
            '<date>' => now()->format('Y-m-d'),
            '<time>' => now()->format('H:i'),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }

    /**
     * إرسال رد وسائط
     */
    protected function sendMediaResponse(WhatsAppInstance $instance, string $number, WhatsAppAutoReplyRoles $rule, string $pushName): void
    {
        $caption = $rule->media_caption ? $this->processVariables($rule->media_caption, $number, $pushName) : null;

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
    protected function sendButtonsResponse(WhatsAppInstance $instance, string $number, WhatsAppAutoReplyRoles $rule, string $pushName): void
    {
        // يمكن تخصيص هذه الدالة حسب API المستخدم
        $text = $this->processVariables($rule->buttons_text ?? '', $number, $pushName);
        $this->evolutionService->sendText($instance->instance_name, $number, $text);
    }

    /**
     * التحقق من وجود رسالة من المالك في آخر فترة
     */
    protected function hasOwnerMessageRecently(WhatsAppInstance $instance, string $number, int $minutes = 5): bool
    {
        // ✅ التحقق من وجود رسالة مرسلة من المالك يدوياً (وليس من البوت)
        // from_me = true يعني رسالة صادرة من حساب WhatsApp
        // لكن نحتاج للتمييز بين:
        // - رسائل المالك اليدوية: message_id يبدأ بـ 'owner_'
        // - ردود البوت التلقائية: message_id يبدأ بـ 'ai_' أو 'auto_'

        $ownerMessage = $instance->messages()
            ->where('remote_jid', $number)
            ->where('from_me', true) // رسائل صادرة من حساب WhatsApp
            ->where('message_id', 'LIKE', 'owner_%') // ✅ فقط رسائل المالك اليدوية
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->exists();

        return $ownerMessage;
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

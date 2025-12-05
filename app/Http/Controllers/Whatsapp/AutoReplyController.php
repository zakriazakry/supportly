<?php

namespace App\Http\Controllers\Whatsapp;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WhatsAppInstance;
use App\Services\AiManagerService;
use App\Services\EvolutionService;
use Illuminate\Support\Facades\Log;

class AutoReplyController extends Controller
{
    protected EvolutionService $evolutionService;
    protected AiManagerService $ai;


    public function __construct(EvolutionService $evolutionService, AiManagerService $ai)
    {
        $this->evolutionService = $evolutionService;
        $this->ai = $ai;
    }

    public function whenReceiveTextMessage(array $data)
    {
        // FIXED: using correct webhook keys
        $instanceName = $data['instanceName'] ?? null;
        $message      = $data['message'] ?? null;
        $fromNumber   = $data['form_number'] ?? null;

        if (!$instanceName || !$fromNumber || !$message) {
            Log::warning('Missing required fields in WhatsApp webhook', $data);
            return;
        }

        $instance = WhatsAppInstance::where('instance_name', $instanceName)->first();
        if (!$instance) {
            Log::error('WhatsApp instance not found', ['instance_name' => $instanceName]);
            return;
        }

        $user = User::find($instance->user_id);
        if (!$user) {
            Log::error('User not found for instance', ['user_id' => $instance->user_id]);
            return;
        }
        $autoReply = $user->whasAppReply ?? null;
        $botPrompt = "
        قواعد الرد الآلي لبوت المنصّة
1. قواعد عامة

يقدّم البوت معلومات فقط ولا ينفّذ إجراءات إدارية أو مالية.

أي طلب خاص بحساب المستخدم، تعديل بيانات، أو مشاكل تقنية يتم تحويله إلى فريق الدعم.

الردود تكون قصيرة وواضحة مع روابط/خطوات جاهزة عند الحاجة.

عند عدم فهم الرسالة يُرسل البوت رسالة تطلب توضيحًا بسيطًا.

**2. قواعد الرد على رسائل الإدمن

إذا كتب الإدمن:

إدارة المستخدمين → إرسال خطوات إدارة المستخدمين + رابط لوحة التحكم.

إدارة المراكز → إرسال شرح إضافة/تعديل/حذف مركز.

الإدارة المالية → إرسال ملخص طرق متابعة العمليات المالية.

الإعلانات → إرسال تعليمات نشر إعلان + الأسعار.

شكاوي/مشاكل → تحويل الرسالة لفريق الدعم فورًا.

**3. قواعد الرد على رسائل المراكز التدريبية

عندما يرسل مركز تدريبي:

إنشاء دورة جديدة → إرسال المتطلبات: (عنوان – الفئة – المواعيد – المدرب – السعر).

إدارة الأساتذة → إرسال طريقة قبول/رفض المدربين + ربطهم بالدورات.

إدارة الطلاب → طريقة مراجعة المسجلين + إرسال التنبيهات لهم.

إعلانات داخل المنصة → إرسال الأسعار وخطوات الدفع.

شكاوي الطلاب → إرسال رابط لوحة الشكاوي أو تحويله للدعم.

مواعيد الدورات → إرسال شرح تعديل/إضافة مواعيد.

**4. قواعد الرد على رسائل الأساتذة

إذا كتب الأستاذ:

الانضمام لمركز → إرسال خطوات إرسال طلب الانضمام.

تقديم مقترح دورة → طلب المعلومات المطلوبة (العنوان – الهدف – المدة – الفئة).

جدول المواعيد → إرسال رابط عرض الجدول.

إعطاء درس مستقل → إرسال خطوات إنشاء جلسة مستقلة خارج المراكز.

قبول دعوة مركز لإعطاء دورة → إرسال طريقة قبول الدعوة.

**5. قواعد الرد على رسائل الطلاب

إذا كتب طالب:

تصفح المراكز أو الكورسات → إرسال طرق البحث + الفلترة حسب الموقع.

الانضمام لدورة → إرسال خطوات التسجيل والدفع (إن وجد).

عرض المواعيد → إرسال رابط أو توجيه لطريقة عرض الجدول.

استلام الإشعارات → شرح طريقة تفعيل/تعطيل الإشعارات.

مقترح دورة → طلب تفاصيل المقترح لإرسالها للمراكز.

استفسار عام → تزويده بمعلومات منصّة + روابط مهمة.

استخدام الذكاء الاصطناعي → إرسال شرح آلية التلخيص وإنشاء الأسئلة.

6. قواعد الطوارئ

عند وجود كلمات: عطل، مشكلة، خطأ، ما يفتح، ما يشتغل، خصم، استعادة مال…
→ يتم تحويل الرسالة مباشرة للدعم الفني.

7. رد عدم الفهم

إذا كانت الرسالة غير واضحة:

“لم أفهم رسالتك بالكامل، ممكن توضح لي المطلوب؟
اختر نوع حسابك: إدمن – مركز تدريبي – أستاذ – طالب.”

8. قواعد منع التشتت

البوت لا:

يقدّم استشارات خارج المنصّة.

يعد بتواريخ أو مواعيد رسمية.

يغيّر بيانات المستخدم.

يعطي صلاحيات.

ينفّذ أي إجراء مالي.";

        $messageKey = $data['key'] ?? null;
        if ($messageKey) {
            $this->evolutionService->markAsRead($instanceName, [$messageKey]);
        }

        $this->aiReply($instanceName, $fromNumber, $message, $botPrompt);

        // Auto reply conditions
        // if (!empty($autoReply->welcome)) {
        //     $this->welcomeReply($instanceName, $fromNumber, $message);
        // }

        // if (!empty($autoReply->ai)) {
        //     $this->aiReply($instanceName, $fromNumber, $message);
        // }

        // if (!empty($autoReply->number)) {
        //     $this->normalReply($instanceName, $fromNumber, $message);
        // }
    }

    private function welcomeReply($instanceName, $number, $msg)
    {
        $this->evolutionService->sendText($instanceName, $number, "مرحبا بك ي غالي \n كيف يمكنني مساعدتك؟");
    }

    private function aiReply($instanceName, $number, $msg, $system_prompt)
    {
        try {
            $start = microtime(true);


            $aiResponse = $this->ai->generate($msg, $system_prompt, 'openai');
            $this->evolutionService->sendChatPresence($instanceName, $number, 'composing', 2000);

            // إرسال الرد النهائي
            $this->evolutionService->sendText($instanceName, $number, $aiResponse);

            Log::info('AI Reply sent successfully', [
                'instance' => $instanceName,
                'to' => $number,
                'user_message' => $msg,
                'ai_response_length' => strlen($aiResponse)
            ]);
        } catch (\Exception $e) {
            Log::error('AI Reply failed', [
                'error' => $e->getMessage(),
                'instance' => $instanceName,
                'to' => $number,
                'user_message' => $msg
            ]);

            $this->evolutionService->sendText(
                $instanceName,
                $number,
                "شكراً لتواصلك معنا! 🙏\nنعتذر عن التأخير، سيتم الرد عليك قريباً."
            );
        }
    }


    private function normalReply($instanceName, $number, $msg)
    {
        // normal reply logic - echo back the message
        $this->evolutionService->sendText(
            $instanceName,
            $number,
            "تم استلام رسالتك: " . $msg
        );
    }
}

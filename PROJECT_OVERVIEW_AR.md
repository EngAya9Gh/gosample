# مشروع GoSample-Enhance — نظرة شاملة للمطورين

> ملف توثيقي شامل يهدف إلى تعريف المطور الجديد بفكرة النظام وآلية عمله من الناحيتين الوظيفية (المستخدم النهائي) والتقنية (البنية والـ APIs والـ Models).

---

## الفهرس

1. [القسم الأول: فكرة النظام واستخدامه كمستخدم نهائي](#القسم-الأول-فكرة-النظام-واستخدامه-كمستخدم-نهائي)
   - 1.1 [ما هو النظام باختصار](#11-ما-هو-النظام-باختصار)
   - 1.2 [المجال (Domain) الذي يخدمه](#12-المجال-domain-الذي-يخدمه)
   - 1.3 [أنواع المستخدمين (Roles)](#13-أنواع-المستخدمين-roles)
   - 1.4 [دورة العمل من البداية للنهاية](#14-دورة-العمل-من-البداية-للنهاية)
   - 1.5 [المزايا الرئيسية لكل نوع مستخدم](#15-المزايا-الرئيسية-لكل-نوع-مستخدم)
2. [القسم الثاني: الشرح التقني الكامل](#القسم-الثاني-الشرح-التقني-الكامل)
   - 2.1 [الـ Stack التقني](#21-الـ-stack-التقني)
   - 2.2 [نظام المصادقة (Authentication)](#22-نظام-المصادقة-authentication)
   - 2.3 [نظام الصلاحيات (Authorization)](#23-نظام-الصلاحيات-authorization)
   - 2.4 [الـ Models وعلاقاتها](#24-الـ-models-وعلاقاتها)
   - 2.5 [الـ APIs وتفصيلها حسب الوظيفة](#25-الـ-apis-وتفصيلها-حسب-الوظيفة)
   - 2.6 [الأنظمة الفرعية (Subsystems)](#26-الأنظمة-الفرعية-subsystems)
   - 2.7 [التكاملات الخارجية (External Integrations)](#27-التكاملات-الخارجية-external-integrations)
   - 2.8 [المهام المجدولة والـ Jobs](#28-المهام-المجدولة-والـ-jobs)
   - 2.9 [الأحداث والمستمعون (Events & Listeners)](#29-الأحداث-والمستمعون-events--listeners)
   - 2.10 [بنية المشروع (Project Structure)](#210-بنية-المشروع-project-structure)
   - 2.11 [ملاحظات أمنية مهمة](#211-ملاحظات-أمنية-مهمة)

---

# القسم الأول: فكرة النظام واستخدامه كمستخدم نهائي

## 1.1 ما هو النظام باختصار

**GoSample-Enhance** هو **نظام لإدارة عمليات نقل العينات الطبية (Medical Samples Logistics)** بين المستشفيات والمختبرات وبنوك الدم، مع التحكم في **سلسلة التبريد (Cold Chain)** وتتبع كل عينة من لحظة الاستلام وحتى التسليم.

النظام يربط بين:
- **مدير العمليات (Admin)** الذي يرتّب المهام ويتابع الأداء عبر لوحة تحكم ويب.
- **السائق (Driver)** الذي يستلم المهام عبر تطبيق موبايل ويوصل العينات.
- **العميل (Client / Hospital / Lab)** الذي يطلب نقل العينات ويتابع حالتها.
- **الأنظمة الخارجية** مثل منصة لوجستيات (Ayenati/Lean) ونظام مختبرات (Blazma) ونظام GPS (Afaqi).

> اسم قاعدة البيانات في `.env` هو `go_sample` ومتغير `AYENATI_CARRIER_ID="driver"` ومتغير `AYENATI_BASE_URL="https://internal-api.lean.sa"` يؤكدان طبيعة المشروع كنظام نقل لوجستي مرتبط بمنصة Lean السعودية.

## 1.2 المجال (Domain) الذي يخدمه

نقل العينات الطبية والمخبرية بأنواعها:
- عينات الدم (مرتبطة بنظام Blazma لبنوك الدم).
- عينات تتطلب تبريد عادي (Refrigerate).
- عينات تتطلب تجميد (Frozen).
- عينات بدرجة حرارة الغرفة (Room).

كل عربة (Car) فيها **حاويات (Containers)** بأنواع مختلفة (مبرّد / مجمّد / غرفة) لضمان نقل العينة في الظروف المناسبة لها.

## 1.3 أنواع المستخدمين (Roles)

النظام يدعم أربعة أنواع رئيسية:

| النوع | الواجهة | طريقة الدخول | المهمة الرئيسية |
|------|----------|--------------|-----------------|
| **Admin / Manager** | Web Dashboard | Session-based (Login عادي) | إدارة كل شيء (سائقين، عربات، عملاء، تقارير، صلاحيات) |
| **Driver** | تطبيق موبايل عبر API | JWT Token | تنفيذ المهام الميدانية (استلام، نقل، تسليم العينات) |
| **Client / Third-Party** | API integration | Bearer Token مخصص | إنشاء مهام نقل برمجياً + متابعة الحالة |
| **System (Webhook)** | لا يوجد واجهة | Secret Key Header | استقبال إشعارات من Blazma وغيره |

## 1.4 دورة العمل من البداية للنهاية

تخيل سيناريو نقل عينة دم من مستشفى A إلى مختبر B:

```
[1] العميل (المستشفى) → ينشئ مهمة (Task) عبر:
    - الويب من قبل الـ Admin، أو
    - API ثالث طرف مثل: POST /api/third-party/{clientId}/tasks/create

[2] النظام يربط المهمة بـ:
    - موقع الاستلام (From Location)
    - موقع التسليم (To Location)
    - السائق المناسب (حسب المنطقة Zone والوردية Shift)

[3] السائق يفتح التطبيق ويسجل دخول:
    POST /api/driver/login
    → يستلم JWT Token

[4] السائق يرى مهامه:
    POST /api/driver/tasks

[5] السائق يبدأ المهمة:
    POST /api/driver/task/start

[6] في موقع الاستلام:
    - يؤكد وصوله: POST /api/driver/task/fromlocation/confirm
    - يمسح باركود العينة: POST /api/sample/new
    - يضع العينة في الحاوية المناسبة: POST /api/sample/container/add
    - يضعها في المجمّد إذا لزم: POST /api/task/freezer

[7] أثناء النقل:
    - تتبع GPS لحظي عبر: POST /api/driver/location
    - في حال طارئ: POST /api/emergency

[8] في موقع التسليم:
    - يخرج العينة من المجمّد: POST /api/task/freezer/out
    - يؤكد وصوله: POST /api/driver/task/tolocation/confirm
    - يغلق المهمة: POST /api/task/close

[9] الإدمن يتابع كل ما سبق:
    - من Dashboard: /dashboard, /daily-operation, /map
    - يصدّر تقارير Excel/PDF يومية وشهرية
```

## 1.5 المزايا الرئيسية لكل نوع مستخدم

### للسائق (Driver):
- تسجيل دخول وخروج من الوردية (Check-in / Check-out).
- استقبال إشعارات Push عبر FCM عند تعيين مهمة جديدة.
- مسح الباركودات للعينات والحاويات والمواقع.
- رفع صور العربة قبل بداية الوردية.
- **تبادل المهام (Swap)**: السائق يستطيع تحويل مهمة لسائق آخر.
- **تحويلات نقدية (Money Transfer)**: تسليم/استلام مبالغ بين السائقين بتأكيد OTP.
- زر طوارئ (Emergency Button).
- قبول الشروط والأحكام داخل التطبيق.

### للإدمن (Admin):
- لوحات تحكم متعددة:
  - `/dashboard` (عام)
  - `/daily-operation` (العمليات اليومية)
  - `/car-dashboard` (متابعة العربات)
  - `/tasks-dashboard` (المهام)
  - `/delayedddashboard` (المهام المتأخرة)
  - `/map` (خريطة لحظية للعربات والمهام)
- **إدارة كاملة (CRUD)** لـ: السائقين، العربات، العملاء، المواقع، المناطق، الورديات، المهام المجدولة.
- **التقارير**: يومية، أسبوعية، شهرية، الأداء، أوقات المهام، أوقات الـ Swap.
- **التصدير**: Excel و PDF.
- **توليد الباركودات** بالجملة.
- **الصلاحيات**: إدارة الأدوار (Roles) والصلاحيات (Permissions) عبر Spatie.
- **Audit Log**: سجل كامل لكل تعديل من قِبل من ومتى.
- **التقويم**: عرض جدول العمليات.
- **إرسال إشعارات** للسائقين.

### للعميل (Client/Third-Party):
- إنشاء مهام نقل تلقائياً عبر API.
- الحصول على قائمة المواقع المتاحة.
- تأكيد العينات أو تبليغ عن فقدانها.
- توليد تقارير بأنواع العينات.

---

# القسم الثاني: الشرح التقني الكامل

## 2.1 الـ Stack التقني

### Backend
- **PHP**: 8.0.2+ (الموجود في النظام: 8.2.0)
- **Framework**: Laravel 9.19+
- **Database**: MySQL (مع `doctrine/dbal` لاستعراض الـ Schema)
- **ORM**: Eloquent
- **Queue Driver**: `sync` (تنفيذ مباشر بدون Worker — يمكن تغييره لـ Redis لاحقاً)

### Frontend
- **Build Tool**: Vite 4.5.2 + Laravel Vite Plugin
- **Template**: Velzon Admin 2.4.0 (قالب مدفوع)
- **CSS**: Bootstrap 5.2.1 + Sass
- **Templating**: Blade
- **JS Libraries**:
  - jQuery 3.6.4
  - Axios 0.21
  - Select2 4.1
  - Dropzone 6.0
  - Flatpickr 4.6.13
  - Feather Icons

### الباقات الرئيسية (composer.json)

| الباقة | الاستخدام |
|--------|-----------|
| `tymon/jwt-auth` | JWT للـ Driver Mobile App |
| `laravel/sanctum` | بديل JWT (موجود لكن أقل استخداماً) |
| `spatie/laravel-permission` | Roles & Permissions |
| `spatie/laravel-activitylog` | Audit Logs |
| `spatie/laravel-http-logger` | تسجيل كل الـ HTTP Requests |
| `yajra/laravel-datatables-oracle` | DataTables بـ Server-Side Rendering |
| `maatwebsite/excel` | تصدير Excel |
| `dompdf/dompdf` | توليد PDF |
| `milon/barcode` | توليد الباركودات |
| `plank/laravel-mediable` | إدارة الملفات المرفوعة |
| `matanyadaev/laravel-eloquent-spatial` | بيانات جغرافية (Polygons) |
| `akaunting/laravel-apexcharts` | الرسوم البيانية في الداشبورد |
| `guzzlehttp/guzzle` | استدعاء الـ APIs الخارجية |
| `barryvdh/laravel-debugbar` | Debug Toolbar (Dev) |

## 2.2 نظام المصادقة (Authentication)

النظام فيه **ثلاث طرق مصادقة** مختلفة حسب نوع المستخدم:

### (أ) السائق — JWT (`tymon/jwt-auth`)
- تعريف الـ Guard في `config/auth.php`:
  ```php
  'drivers' => ['driver' => 'jwt', 'provider' => 'drivers']
  ```
- موديل `Driver` يطبّق `JWTSubject` interface.
- نقاط الدخول:
  - `POST /api/driver/login` (username + password + FCM token)
  - `POST /api/driver/loginWithMobile` (mobile + password)
- بعد تسجيل الدخول يستلم السائق Token JWT يرسله في كل request في الـ Header:
  ```
  Authorization: Bearer <token>
  ```

### (ب) الإدمن — Session-based (Laravel Default)
- الـ Guard هو `web` الافتراضي.
- الدخول من `/login` ثم Session Cookie.
- محمي بـ Middleware `auth`.

### (ج) العميل / Third Party — Bearer Token مخصص
- Middleware: `App\Http\Middleware\ClientApiAuth`.
- يتحقق من Bearer token مخصص لكل عميل.
- نقطة دخول: `POST /api/third-party/login`.

### (د) Webhooks — API Key Header
- Middleware: `App\Http\Middleware\ApiKeyMiddleware`.
- يقارن قيمة Header `secret-key` مع `BLAZMA_SECRET_KEY` في `.env`.
- يستخدم في `POST /api/webhook/samples/tracking/add`.

## 2.3 نظام الصلاحيات (Authorization)

النظام يستخدم **Spatie Laravel Permission v5.7** لإدارة الأدوار والصلاحيات:

- جداول قياسية: `roles`, `permissions`, `role_has_permissions`, `model_has_roles`, `model_has_permissions`.
- موديل `User` يستخدم Trait `HasRoles`.
- صفحات الإدارة:
  - `/admin/roles` — إدارة الأدوار
  - `/admin/permissions` — إدارة الصلاحيات
  - `/admin/users` — تعيين الأدوار للمستخدمين
  - `/admin/delete-permissions` — صلاحية خاصة (User ID = 1 يستطيع منح صلاحية الحذف لمستخدمين آخرين)
- لا توجد Policies تقليدية للـ Laravel — كل التحقق يتم عبر Middleware من Spatie.

## 2.4 الـ Models وعلاقاتها

في `app/Models/` يوجد **37+ موديل**. أهمها:

### Core Models

| Model | الوصف | أهم العلاقات |
|-------|--------|---------------|
| `User` | مستخدمو الويب (Admins) — يحملون أدوار وصلاحيات | `roles()`, soft deletes |
| `Driver` | السائق — يستخدم JWT — يطبّق `JWTSubject` | `car()`, `tasks()`, `zone()`, `shifts()`, `attendances()` |
| `Task` | مهمة نقل (الكيان الأساسي في النظام) | `driver()`, `from()`, `to()`, `client()`, `samples()`, `car()` |
| `Sample` | عينة فردية تنقل ضمن مهمة | `task()`, `location()`, `container()` |
| `Container` | حاوية تخزين داخل العربة (REFRIGERATE / FROZEN / ROOM) | `car()`, `samples()` |
| `Car` | العربة المعينة للسائق | `driver()`, `containers()`, `carTracking()` |
| `Client` | المؤسسة العميلة (مستشفى، مختبر) | `locations()`, `drivers()` |
| `Location` | موقع جغرافي (نقطة استلام أو تسليم) | إحداثيات + أوقات انتظار |
| `Zone` | منطقة جغرافية (Polygon) | يستخدم `matanyadaev/laravel-eloquent-spatial` |
| `Shipment` | شحنة مرتبطة بتكامل Ayenati | `task()`, `fromLocation()`, `toLocation()` |

### Operational Models

| Model | الوصف |
|-------|--------|
| `Attendance` | حضور وانصراف السائق + التأخر والإضافي |
| `DriverShift` | قالب وردية (أيام + أوقات) |
| `ScheduledTask` | مهام دورية متكررة |
| `Swap` | طلب تبادل مهمة بين سائقَين |
| `MoneyTransfer` | تحويل نقدي بين سائقَين بـ OTP |
| `CarTracking` | إحداثيات GPS اللحظية للعربة |
| `SampleTracking` | تتبع رحلة العينة (يُربط بـ Blazma) |
| `Barcode` | باركود مولّد |
| `Notifications` | إشعارات عامة |
| `EmergencyFlag` | علامة حالة الطوارئ |

### Integration Models

| Model | الوصف |
|-------|--------|
| `Afaqi` | تكامل GPS Afaqi |
| `ApiAyenati` | لوجز تكامل Ayenati |
| `AyenatiToken` | إدارة OAuth Tokens لـ Ayenati |
| `ElmNotification` | إشعارات نظام ELM الخارجي |
| `ScheduleLog` | لوجز تنفيذ المهام المجدولة |

### Auxiliary Models

`Term`, `Bank`, `ClientAccount`, `ClientDriver`, `ClientLocation`, `Contact`, `CarLinkHistory`, `CarDriver`, `CouponCode`, `DeliveryTime`, `Product*`, `AuditLog`.

### الخريطة العلائقية المختصرة

```
Client ──many─── Location
   │                │
   │ many           │ many
   │                │
Driver ── one ── Car ── many ── Container ── many ── Sample
   │              │                                    │
   │              │                                    │
   │              └── CarTracking (GPS)                │
   │                                                   │
   └─── many ── Task ──many─── Sample (same)
                 │
                 ├── from: Location
                 ├── to:   Location
                 ├── client: Client
                 └── shipment: Shipment (optional)
```

## 2.5 الـ APIs وتفصيلها حسب الوظيفة

كل الـ APIs في `routes/api.php`، وأغلب نقاط الـ Driver محمية بـ `auth:driver` (JWT).

### (1) المصادقة (Authentication)

| Method | Endpoint | الوظيفة |
|--------|----------|---------|
| POST | `/api/driver/login` | دخول السائق بـ username/password + FCM token |
| POST | `/api/driver/loginWithMobile` | دخول بالهاتف وكلمة السر |
| POST | `/api/third-party/login` | دخول العميل الخارجي |

### (2) إدارة المهام (Tasks)

| Method | Endpoint | الوظيفة |
|--------|----------|---------|
| POST | `/api/driver/tasks` | قائمة مهام السائق |
| POST | `/api/driver/task/start` | بدء مهمة |
| POST | `/api/driver/task/confirm` | تأكيد مهمة واحدة |
| POST | `/api/driver/tasks/confirm` | تأكيد مهام متعددة |
| POST | `/api/driver/task/fromlocation/confirm` | تأكيد الوصول لموقع الاستلام |
| POST | `/api/driver/task/tolocation/confirm` | تأكيد الوصول لموقع التسليم |
| POST | `/api/task/close` | إغلاق مهمة |
| POST | `/api/tasks/close` | إغلاق متعدد |
| POST | `/api/task/nosamples` | إغلاق بدون عينات |
| POST | `/api/task/create` | إنشاء مهمة |
| POST | `/api/tasks/cache` | جلب المهام من الكاش |

### (3) العينات (Samples) — جوهر النظام

| Method | Endpoint | الوظيفة |
|--------|----------|---------|
| POST | `/api/sample/new` | إضافة عينة جديدة (مسح الباركود) |
| POST | `/api/samples/list` | قائمة عينات المهمة |
| POST | `/api/sample/container/add` | إضافة عينة لحاوية |
| POST | `/api/samples/container/add` | إضافة دفعة عينات لحاوية |
| POST | `/api/sample/container/remove` | إزالة من الحاوية |
| POST | `/api/sample/remove` | حذف العينة من المهمة |
| POST | `/api/task/collect` | تأكيد جمع العينات |
| POST | `/api/task/freezer` | وضع العينات في المجمّد |
| POST | `/api/task/freezer/out` | إخراج من المجمّد |
| POST | `/api/tasks/freezer/out` | إخراج دفعة |
| POST | `/api/samples/bags/add` | إدارة الأكياس |
| POST | `/api/task/bags/get` | جلب أكياس المهمة |
| POST | `/api/task/location/check` | مسح باركود الموقع |

### (4) العملاء (Client APIs)

| Method | Endpoint | الوظيفة |
|--------|----------|---------|
| POST | `/api/client/samples/confirm` | تأكيد العميل لاستلام العينات |
| POST | `/api/client/samples/details` | تفاصيل العينة |
| POST | `/api/client/samples/report` | توليد تقرير |
| POST | `/api/client/samples/lost` | تبليغ عن فقدان عينة |
| POST | `/api/samples/types/report` | تقرير حسب نوع العينة |

### (5) الشحنات (Shipments — تكامل Ayenati)

| Method | Endpoint | الوظيفة |
|--------|----------|---------|
| POST | `/api/shipments/create` | إنشاء شحنة |
| POST | `/api/shipments/dispatch` | إرسالها لسائق |
| POST | `/api/shipments/details` | تفاصيل الشحنة |
| POST | `/api/shipments/status-shipment` | حالة الشحنة |
| POST | `/api/shipments/update-shipment` | تحديث الشحنة |
| POST | `/api/update/otp/ayenati` | تحديث OTP من Ayenati |

### (6) ملف السائق وإدارته

| Method | Endpoint | الوظيفة |
|--------|----------|---------|
| POST | `/api/driver/profile` | معلومات السائق |
| POST | `/api/driver/notifications` | إشعاراته |
| POST | `/api/driver/location` | تحديث GPS |
| POST | `/api/driver/checkin` | بداية الوردية |
| POST | `/api/driver/checkout` | نهاية الوردية |
| POST | `/api/driver/terms/get` | جلب الشروط |
| POST | `/api/driver/terms/accept` | قبول الشروط |
| POST | `/api/driver/schedule` | جدول السائق |
| POST | `/api/driver/car/images` | رفع صور العربة |
| POST | `/api/emergency` | زر الطوارئ |

### (7) تبادل المهام (Swap)

| Method | Endpoint | الوظيفة |
|--------|----------|---------|
| POST | `/api/swap/create` | طلب تبادل |
| POST | `/api/swap/list` | قائمة الطلبات |
| POST | `/api/swap/list/driver` | الطلبات الموجهة لسائق محدد |
| POST | `/api/swap/tasks/list` | المهام المتاحة للتبادل |
| POST | `/api/swap/accept` | قبول طلب |
| POST | `/api/swap/reject` | رفض طلب |
| POST | `/api/swap/receive` | استلام مهمة مُبادَلة |
| POST | `/api/swap/list/acceptall` | قبول الكل |

### (8) التحويلات النقدية (Money Transfer)

| Method | Endpoint | الوظيفة |
|--------|----------|---------|
| POST | `/api/money/transfer/list` | قائمة التحويلات المعلّقة |
| POST | `/api/money/transfer/otp/from/verifiy` | تأكيد OTP من المرسل |
| POST | `/api/money/transfer/otp/to/verifiy` | تأكيد OTP من المستلم |

### (9) Third-Party APIs

| Method | Endpoint | الوظيفة |
|--------|----------|---------|
| POST | `/api/third-party/{clientId}/tasks/create` | إنشاء مهمة من العميل |
| GET | `/api/third-party/{clientId}/locations` | جلب المواقع المتاحة |
| POST | `/api/webhook/samples/tracking/add` | Webhook من Blazma |

### صيغة الاستجابة الموحّدة (Response Format)

كل الـ APIs ترجع JSON بالصيغة:
```json
{
  "success": true,
  "message": "string",
  "data": { ... }
}
```
عبر دالة مساعدة `response()` في الـ Controller الأساسي. أخطاء الـ Validation تُعالج في `validationHandle()`.

## 2.6 الأنظمة الفرعية (Subsystems)

### (أ) Activity Logging — سجل النشاط
- باقة: `spatie/laravel-activitylog`.
- Trait مخصص: `App\Traits\Auditable.php`.
- يسجل كل تعديل (CRUD) على الموديلات الحساسة.
- الواجهة: `/admin/audit-logs` (للقراءة فقط).

### (ب) Media & File Uploads
- باقة: `plank/laravel-mediable`.
- يستخدم في إدارة العملاء: `clients/media`, `clients/ckmedia` (مع CKEditor).
- Trait: `MediaUploadingTrait` في Admin Controllers.

### (ج) PDF & Excel Exports
- DOMPDF لتوليد PDF.
- Maatwebsite/Excel لتصدير Excel.
- Export Classes في `app/Exports/`:
  - `MonthlyPerformanceExport`
  - `TaskTimeReportExport`
  - `TaskSwapTimeReportExport`
- نقاط التصدير:
  - `/admin/export-excel` (تفاصيل المهام)
  - `/admin/swap-export-excel` (تفاصيل التبادل)
  - `/admin/reports/monthly/export` (الأداء الشهري)

### (د) Barcode Generation
- باقة: `milon/barcode`.
- الواجهة: `/admin/barcodes/generate` (واجهة + POST handler).

### (هـ) Geospatial Features
- باقة: `matanyadaev/laravel-eloquent-spatial`.
- موديل `Zone` يستخدم `Polygon` cast.
- يعرّف `newEloquentBuilder()` ليرجع `SpatialBuilder`.
- الاستخدام: تحديد مناطق الخدمة وتعيين السائقين على أساسها.

### (و) Server-Side DataTables
- باقة: `yajra/laravel-datatables-oracle`.
- مستخدم في كل صفحات الإدارة (Admin CRUD).
- يدعم Sorting / Filtering / Pagination على مستوى السيرفر للجداول الكبيرة.

### (ز) Charts & Analytics
- باقة: `akaunting/laravel-apexcharts`.
- مستخدم في الـ Dashboard لعرض الإحصائيات الشهرية وأداء السائقين.

## 2.7 التكاملات الخارجية (External Integrations)

### (1) Ayenati (Lean Logistics) — `https://internal-api.lean.sa`
- منصة لوجستيات سعودية لإدارة الشحنات.
- Controller: `ApiAyenatiController`.
- موديل: `AyenatiToken` لإدارة OAuth Tokens.
- Job: `GenerateAtenatiTokenJob` يُجدّد التوكن كل 30 دقيقة.
- Command: `AyenatiCommand` لتزامن البيانات.
- يستخدم في إنشاء الشحنات وتأكيد التسليم بـ OTP.

### (2) Blazma (نظام بنك الدم / المختبر)
- مفتاح سري في `.env`: `BLAZMA_SECRET_KEY`.
- Webhook: `POST /api/webhook/samples/tracking/add` محمي بـ `ApiKeyMiddleware`.
- موديل `SampleTracking` يخزن `order_id`, `profile_id`, `hospital_id` القادمة من Blazma.

### (3) Afaqi (نظام GPS للسيارات)
- Command: `AfaqiCommand` لمزامنة بيانات GPS.
- موديل: `Afaqi` + بيانات تخزن في `CarTracking`.
- يغذي خريطة `/map` بالمواقع اللحظية.

### (4) Firebase Cloud Messaging (FCM)
- لإرسال إشعارات Push للسائق.
- دالة: `Driver::sendNotification()` ترسل payload فيه تفاصيل المهمة.
- ⚠️ **مفتاح FCM مكتوب hardcoded داخل موديل `Driver`** (يجب نقله لـ config).

### (5) Custom Log Aggregator
- متغيرات: `LOG_HOSTS=http://158.101.243.250` + `LOG_SECRETE_KEY`.
- Middleware: `App\CustomLog\CustomLogRequests` يسجّل كل request.
- `CustomLogWriter` يرسل اللوجز لخادم خارجي.

## 2.8 المهام المجدولة والـ Jobs

تُعرَّف في `app/Console/Kernel.php` وتعمل عبر Cron.

### Commands المجدولة

| Command | التكرار | الوظيفة |
|---------|---------|----------|
| `taskDelayed:cron` | كل دقيقة | اكتشاف المهام المتأخرة |
| `car-track:cron` | كل دقيقة | تحديث تتبع العربات |
| `daily-schedule:cron` | كل دقيقتين | معالجة المهام المجدولة |
| `attendance:check-late` | كل دقيقة | متابعة تأخر السائقين |

### Jobs (في `app/Jobs/`)

| Job | الوظيفة |
|-----|----------|
| `RemoveOldNewTasks` | حذف المهام القديمة غير المعالجة |
| `CheckScheduledTasks` | تنفيذ المهام المجدولة المستحقة |
| `GenerateAtenatiTokenJob` | تجديد توكن Ayenati (كل 30 دقيقة) |
| `DailyScheduledJob` | مهام يومية في وقت محدد |
| `LogData` | تسجيل لوجز |
| `ProcessAttendanceKPIJob` | معالجة مؤشرات الحضور |

> 💡 الـ Queue Driver حالياً `sync`، فعلياً المهام تُنفّذ مباشرةً. للإنتاج يُفضّل تحويلها لـ Redis/Database مع Worker.

## 2.9 الأحداث والمستمعون (Events & Listeners)

في `app/Events/` و `app/Listeners/`، 7 أحداث رئيسية:

| Event | Listener | متى يُطلق |
|-------|----------|----------|
| `CurrentDriverLocationEvent` | `SendCurrentDriverLocationEvent` | عند تحديث GPS لبثّه لحظياً |
| `DriverArrivedAtPickUpLocationEvent` | إشعار وصول | عند تأكيد موقع الاستلام |
| `DriverArrivedAtDeliveredLocationEvent` | إشعار تسليم | عند تأكيد موقع التسليم |
| `SamplesCollectedEvent` | إشعار جمع العينات | بعد `task/collect` |
| `TaskCancelledEvent` | إشعار إلغاء | عند إلغاء المهمة |
| `TaskClosedEvent` | إشعار إغلاق | عند `task/close` |
| `GeneratePDF` | `GeneratePDFListener` | لتوليد ملفات PDF |

البث (Broadcasting) معدّ لـ Pusher (في `.env`) لكن غير مفعّل افتراضياً.

## 2.10 بنية المشروع (Project Structure)

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/              ← 48 controller للـ CRUD الإداري
│   │   ├── Auth/               ← دخول/تسجيل/استرجاع
│   │   ├── DriverController    ← API السائق
│   │   ├── SampleController    ← منطق العينات
│   │   ├── SwapController      ← تبادل المهام
│   │   ├── ShipmentController  ← تكامل اللوجستيات
│   │   └── HomeController      ← Dashboard
│   ├── Middleware/
│   │   ├── ApiKeyMiddleware    ← حماية الـ Webhooks
│   │   ├── ClientApiAuth       ← Bearer Token للعملاء
│   │   └── (Standard Laravel)
├── Models/                      ← 37 موديل
├── Events/                      ← 7 أحداث
├── Listeners/                   ← 7 مستمعين
├── Jobs/                        ← 6 Jobs
├── Console/
│   ├── Commands/               ← 7+ Commands
│   └── Kernel.php              ← الجدولة
├── Notifications/              ← إشعارات Laravel
├── Exports/                    ← 3 Excel Exports
├── Traits/
│   └── Auditable.php           ← Trait للـ Audit Log
├── Services/                   ← Business Logic
├── CustomLog/                  ← اللوج الخارجي
└── Exceptions/Handler.php

routes/
├── web.php                      ← 40+ routes إدارية + Dashboards
├── api.php                      ← 70+ API endpoints
└── console.php

resources/
├── views/
│   ├── admin/                   ← صفحات الـ Admin
│   ├── auth/
│   └── layouts/
├── css/
└── js/

database/
├── migrations/
├── factories/
└── seeders/

config/
├── auth.php                     ← 3 Guards: web, api, drivers
└── services.php                 ← مفاتيح الخدمات الخارجية
```

## 2.11 ملاحظات أمنية مهمة

> ⚠️ نقاط لاحظتها أثناء التحليل ينبغي مراجعتها قبل النشر للإنتاج:

1. **مفتاح FCM hardcoded** داخل موديل `Driver` — يجب نقله إلى `config/services.php` وقراءته من `.env`.
2. **`BLAZMA_SECRET_KEY` و `JWT_SECRET`** ظاهرة في `.env.example` بقيم حقيقية — يجب تغييرها فوراً وعدم رفعها للـ Repo (الـ `.env.example` يُفترض ألّا يحتوي قيماً سرية حقيقية).
3. **Custom Log Aggregator** يرسل البيانات على HTTP عادي إلى IP صريح (`158.101.243.250`) — يُفضّل HTTPS مع Certificate Validation.
4. **PSR-4 Violation**: في `composer install` ظهر تنبيه أن class `App\Jobs\CheckScheduledTasks` موجود في ملف `DailyScheduledJob.php` (اسم الكلاس لا يطابق اسم الملف) — يحتاج تصحيح.
5. **عدم وجود Policies**: كل التحقق يعتمد على Spatie فقط — لا توجد طبقة Policies تقليدية للـ Laravel، مما يجعل بعض المنطق المعقد للأذونات صعب الصيانة.
6. **Queue على `sync`**: للإنتاج يجب تحويلها لـ Redis مع Workers لتفادي بطء الـ Requests.
7. **`Mediable` files**: تأكد من ضبط الـ Disk للملفات المرفوعة (S3 للإنتاج بدلاً من `local`).

---

## ملحق: نقاط الانطلاق السريعة للمطور الجديد

عند البدء بالعمل على المشروع، ابدأ بالملفات التالية:

| للمهمة | اقرأ أولاً |
|--------|-----------|
| فهم الـ Routing | `routes/web.php`, `routes/api.php` |
| تعديل سلوك السائق | `app/Http/Controllers/DriverController.php`, `app/Models/Driver.php` |
| تعديل منطق العينات | `app/Http/Controllers/SampleController.php`, `app/Models/Sample.php`, `app/Models/Container.php` |
| إضافة Admin CRUD جديد | تقليد ملف موجود في `app/Http/Controllers/Admin/` + `resources/views/admin/{name}/` |
| المهام المجدولة | `app/Console/Kernel.php` + الـ Commands في `app/Console/Commands/` |
| تكامل Ayenati | `app/Http/Controllers/ApiAyenatiController.php` + `app/Jobs/GenerateAtenatiTokenJob.php` |
| الـ Audit Log | `app/Traits/Auditable.php` |

---

> **آخر تحديث**: 2026-05-10
> **إعداد**: تحليل آلي للمشروع بناءً على فحص الكود.

<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    public static function bootAuditable()
    {
        static::created(function (Model $model) {
            self::audit('audit:created', $model);
        });

        static::updated(function (Model $model) {
            $changes = $model->getChanges();
            
            // تحقق مما إذا كان المودل يحتوي على أعمدة مستثناة من التدقيق الأمني
            if (property_exists($model, 'auditExclude')) {
                $changedKeys = array_keys($changes);
                // إزالة الأعمدة المستثناة وعمود updated_at من قائمة التغييرات
                $diff = array_diff($changedKeys, $model->auditExclude, ['updated_at']);
                
                // إذا كانت كل التغييرات محصورة في الأعمدة المستثناة، فلا تقم بالتسجيل أبداً
                if (empty($diff)) {
                    return;
                }
            }

            $model->attributes = array_merge($changes, ['id' => $model->id]);

            self::audit('audit:updated', $model);
        });

        static::deleted(function (Model $model) {
            self::audit('audit:deleted', $model);
        });
    }

    protected static function audit($description, $model)
    {
        $userId = auth()->id();

        // التعديل الذي طلبته: إذا كانت العملية آلية (لا يوجد مستخدم مسجل الدخول) نوقف التسجيل فوراً.
        if (!$userId) {
            return;
        }

        AuditLog::create([
            'description'  => $description,
            'subject_id'   => $model->id ?? null,
            'subject_type' => sprintf('%s#%s', get_class($model), $model->id) ?? null,
            'user_id'      => $userId,
            'properties'   => $model ?? null,
            'host'         => request()->ip() ?? null,
        ]);
    }
}
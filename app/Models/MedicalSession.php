<?php

namespace App\Models;

use App\Enums\MedicalSessionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalSession extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'patient_id',
        'organization_id',
        'health_center_id',
        'unit_id',
        'temporary_access_code_id',
        'cta_code',
        'status',
        'triage_skipped',
        'triage_skip_reason',
        'triage_skipped_by',
        'reason_of_visit',
        'allergies',
        'started_at',
        'ended_at',
        'closure_reason',
        'summary',
        'created_by',
        'closed_by',
    ];

    protected $casts = [
        'status' => MedicalSessionStatus::class,
        'allergies' => 'array',
        'triage_skipped' => 'boolean',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function healthCenter(): BelongsTo
    {
        return $this->belongsTo(HealthCenter::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function temporaryAccessCode(): BelongsTo
    {
        return $this->belongsTo(TemporaryAccessCode::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function triageSkippedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triage_skipped_by');
    }

    public function closeSession(string $closureReason, string $summary, User $closedBy): void
    {
        $this->update([
            'status' => MedicalSessionStatus::Closed->value,
            'ended_at' => now(),
            'closure_reason' => $closureReason,
            'summary' => $summary,
            'closed_by' => $closedBy->id,
        ]);

        // El acceso del paciente termina con la atencion (S8.7, S11.3).
        $this->patient->tokens()->delete();
    }
}
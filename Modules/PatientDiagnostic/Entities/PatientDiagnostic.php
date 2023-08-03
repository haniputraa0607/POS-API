<?php

namespace Modules\PatientDiagnostic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Diagnostic\Entities\Diagnostic;
use Modules\Consultation\Entities\Consultation;

class PatientDiagnostic extends Model
{
    use HasFactory;

    protected $table = 'patient_diagnostics';
    protected $fillable = [
        'consultation_id',
        'diagnostic_id',
        'notes'
    ];

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class, 'consultation_id', 'id');
    }

    public function diagnostic(): BelongsTo
    {
        return $this->belongsTo(Diagnostic::class, 'diagnostic_id', 'id');
    }
}

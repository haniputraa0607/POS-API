<?php

namespace Modules\Queue\Entities;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Outlet\Entities\Outlet;
use Modules\Queue\Database\factories\QueueFactory;

class Queue extends Model
{
    use HasFactory;

    public const QUEUE_TYPE = ['product', 'consultation', 'treatment'];
    protected $table = 'queues';
    protected $fillable = ['code', 'type', 'outlet_id'];


    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', Carbon::today());
    }
    public function scopeProduct(Builder $query): Builder
    {
        return $query->where('type', 'product');
    }
    public function scopeTreatment(Builder $query): Builder
    {
        return $query->where('type', 'treatment');
    }
    public function scopeConsultation(Builder $query): Builder
    {
        return $query->where('type', 'consultation');
    }
    protected static function newFactory()
    {
        return QueueFactory::new();
    }
}

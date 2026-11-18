<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Receipt extends Model{
    protected $guarded = [];
    public function patungan(): BelongsTo{
        return $this->belongsTo(Patungan::class);
    }
    public function payer(): BelongsTo{
        return $this->belongsTo(Participant::class, 'payer_id');
    }
    public function items(): HasMany{
        return $this->hasMany(Item::class);
    }
}
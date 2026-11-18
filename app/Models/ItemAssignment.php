<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ItemAssignment extends Model{
    protected $guarded = [];
    public function item(): BelongsTo{
        return $this->belongsTo(Item::class);
    }
    public function participant(): BelongsTo{
        return $this->belongsTo(Participant::class);
    }
}
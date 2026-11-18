<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Item extends Model{
    protected $guarded = [];
    public function receipt(): BelongsTo{
        return $this->belongsTo(Receipt::class);
    }
    public function assignments(): HasMany{
        return $this->hasMany(ItemAssignment::class);
    }
}
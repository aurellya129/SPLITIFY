<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Participant extends Model{
    protected $guarded = [];
    public function patungan(): BelongsTo{
        return $this->belongsTo(Patungan::class);
    }
}
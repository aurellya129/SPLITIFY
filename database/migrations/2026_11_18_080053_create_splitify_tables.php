<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patungans', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 16)->unique();
            $table->string('judul', 100);
            $table->timestamps();
        });
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patungan_id')->constrained()->cascadeOnDelete();
            $table->string('name', 40);
            $table->unsignedBigInteger('total_owed')->default(0);  // porsi belanja yang ditanggung
            $table->unsignedBigInteger('total_paid')->default(0);  // yang sudah dibayarkan ke kasir
            $table->bigInteger('net')->default(0);                 // paid - owed (+ dapat uang, - harus bayar)
            $table->timestamps();
        });
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patungan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payer_id')->constrained('participants')->cascadeOnDelete();
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->unsignedBigInteger('total')->default(0);       // total setelah pajak, dibulatkan Rupiah
            $table->timestamps();
        });
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->unsignedBigInteger('price');                   // harga satuan
            $table->unsignedInteger('qty');
            $table->timestamps();
        });
        Schema::create('item_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->decimal('qty', 8, 2);
            $table->timestamps();
        });
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patungan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_id')->constrained('participants')->cascadeOnDelete();
            $table->foreignId('to_id')->constrained('participants')->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('transfers');
        Schema::dropIfExists('item_assignments');
        Schema::dropIfExists('items');
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('participants');
        Schema::dropIfExists('patungans');
    }
};
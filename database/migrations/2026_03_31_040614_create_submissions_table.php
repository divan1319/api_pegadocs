<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('assignments')->onDelete('cascade');
            $table->foreignId('assignment_member_id')->constrained('assignment_members')->onDelete('cascade');
            $table->string('file_name');
            $table->text('file_url');
            $table->text('converted_pdf_url')->nullable();
            $table->enum('file_type', ['pdf', 'image', 'other'])->default('pdf');
            $table->integer('file_size')->default(0);
            $table->enum('status', ['pending_review', 'reviewed', 'accepted', 'rejected'])->default('pending_review');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};

<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\VerificationRecord;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VerificationRecord>
 */
class VerificationRecordFactory extends Factory
{
    protected $model = VerificationRecord::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'token' => Str::uuid(),
            'fingerprint' => strtoupper(Str::random(16)),
            'status' => 'valid',
            'access_count' => 0,
        ];
    }

    /**
     * Valid status.
     */
    public function valid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'valid',
        ]);
    }

    /**
     * Revoked status.
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'revoked',
        ]);
    }

    /**
     * Superseded status.
     */
    public function superseded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'superseded',
        ]);
    }
}

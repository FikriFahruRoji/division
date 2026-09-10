<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'title' => $this->faker->sentence(4),
            'doc_number' => $this->faker->unique()->numerify('DOC-####-###'),
            'doc_date' => $this->faker->date(),
            'doc_type' => $this->faker->randomElement(['Surat Keputusan', 'Surat Tugas', 'Surat Keterangan']),
            'unit' => $this->faker->company(),
            'classification' => $this->faker->randomElement(['biasa', 'terbatas', 'rahasia']),
            'status' => 'draft',
            'version' => 1,
            'creator_id' => User::factory(),
            'file_path' => 'documents/secure/test-' . Str::random(16) . '.pdf',
            'hash' => hash('sha256', Str::random(40)),
            'fingerprint' => strtoupper(Str::random(16)),
            'qr_token' => (string) Str::uuid(),
            'sign_mode' => 'single',
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Document in draft status.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    /**
     * Document in pending_signature status.
     */
    public function pendingSignature(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending_signature',
        ]);
    }

    /**
     * Document that is signed and valid.
     */
    public function signedValid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'signed_valid',
            'signed_file_path' => 'documents/signed_test_' . Str::random(16) . '.pdf',
        ]);
    }

    /**
     * Document that is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
        ]);
    }

    /**
     * Document that is revoked.
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'revoked',
        ]);
    }

    /**
     * Document that is superseded.
     */
    public function superseded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'superseded',
        ]);
    }

    /**
     * Set sign mode to sequential.
     */
    public function sequential(): static
    {
        return $this->state(fn (array $attributes) => [
            'sign_mode' => 'sequential',
        ]);
    }

    /**
     * Set sign mode to parallel.
     */
    public function parallel(): static
    {
        return $this->state(fn (array $attributes) => [
            'sign_mode' => 'parallel',
        ]);
    }

    /**
     * Assign a specific creator.
     */
    public function withCreator(User $creator): static
    {
        return $this->state(fn (array $attributes) => [
            'creator_id' => $creator->id,
        ]);
    }
}

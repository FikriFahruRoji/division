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
            'uuid' => Str::uuid(),
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
            'fingerprint' => Str::random(64),
            'qr_token' => Str::uuid(),
            'sign_mode' => 'single',
            'notes' => $this->faker->optional()->sentence(),
        ];
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
        ]);
    }
}

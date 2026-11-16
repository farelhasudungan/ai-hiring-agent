<?php

namespace Database\Seeders;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChatLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $demoUserEmail = config('agentic-chat.demo_user_email', 'test@example.com');
        $user = User::query()->where('email', $demoUserEmail)->first() ?? User::factory()->create([
            'email' => $demoUserEmail,
            'name' => 'Agentic Chat Demo User',
        ]);

        $samples = [
            [
                'title' => 'Analisis CV Backend Engineer',
                'prompt' => 'Tolong analisis kecocokan kandidat ini untuk posisi Senior Backend Engineer.',
                'response' => 'Berdasarkan kualifikasi yang dipaparkan, kandidat memiliki rekam jejak yang kuat dalam arsitektur microservices dan Laravel. Rekomendasi: Lanjutkan ke tahap wawancara teknis.',
                'provider' => 'ollama',
                'model' => 'qwen3:14b',
            ],
            [
                'title' => 'Pertanyaan Wawancara HR',
                'prompt' => 'Berikan 3 pertanyaan wawancara behavioral untuk mengevaluasi kemampuan kepemimpinan tim.',
                'response' => "1. Ceritakan pengalaman Anda saat harus memimpin proyek dengan tenggat waktu ketat.\n2. Bagaimana cara Anda menangani konflik antaranggotatim teknis?\n3. Berikan contoh keputusan sulit yang pernah Anda ambil demi keberhasilan tim.",
                'provider' => 'senopati',
                'model' => 'qwen3:14b',
            ],
        ];

        foreach ($samples as $sample) {
            DB::transaction(function () use ($user, $sample) {
                $session = ChatSession::create([
                    'user_id' => $user->id,
                    'title' => $sample['title'],
                ]);

                ChatMessage::create([
                    'chat_session_id' => $session->id,
                    'role' => 'user',
                    'content' => $sample['prompt'],
                ]);

                ChatMessage::create([
                    'chat_session_id' => $session->id,
                    'role' => 'assistant',
                    'content' => $sample['response'],
                ]);

                $session->chatLogs()->create([
                    'user_id' => $user->id,
                    'prompt' => $sample['prompt'],
                    'ai_response' => $sample['response'],
                    'model_used' => $sample['model'],
                    'provider' => $sample['provider'],
                    'prompt_tokens' => 45,
                    'completion_tokens' => 120,
                    'total_tokens' => 165,
                ]);
            });
        }
    }
}

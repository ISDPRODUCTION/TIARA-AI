<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\ChatSession;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    /**
     * Display the chatbot interface.
     */
    public function index()
    {
        $provider = config('ai.provider', 'mistral');
        $providerLabel = match ($provider) {
            'mistral' => 'Mistral AI',
            'gemini'  => 'Gemini AI',
            default   => 'AI',
        };

        // Optionally pass current user's sessions if needed for initial load,
        // but we'll fetch via API for consistency.
        return view('welcome', [
            'aiProvider' => $providerLabel,
        ]);
    }

    /**
     * Get all chat sessions for the authenticated user.
     */
    public function getSessions()
    {
        $sessions = Auth::user()->chatSessions()
            ->withCount('messages')
            ->get()
            ->map(function ($session) {
                return [
                    'id' => $session->id,
                    'title' => $session->title ?? 'Obrolan Baru',
                    'updated_at' => $session->updated_at->diffForHumans(),
                ];
            });

        return response()->json($sessions);
    }

    /**
     * Get all messages for a specific session.
     */
    public function getMessages($id)
    {
        $session = Auth::user()->chatSessions()->findOrFail($id);
        $messages = $session->messages->map(function ($msg) {
            return [
                'role' => $msg->role,
                'parts' => [['text' => $msg->content]]
            ];
        });

        return response()->json($messages);
    }

    /**
     * Delete a chat session.
     */
    public function deleteSession($id)
    {
        Auth::user()->chatSessions()->findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Send a message to AI and return the response.
     */
    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'message'              => 'required|string|max:4000',
            'session_id'           => 'nullable|exists:chat_sessions,id',
            'history'              => 'nullable|array|max:40',
            'history.*.role'       => 'required_with:history|string|in:user,model',
            'history.*.parts'      => 'required_with:history|array|min:1',
            'history.*.parts.*.text' => 'required_with:history|string|max:8000',
        ]);

        $input = $validated['message'];
        $provider = config('ai.provider', 'mistral');

        // 1. Get/Update State
        $state = $this->getState();
        $this->decayMood($state);
        $this->updateMood($state, $input);

        // 2. Detect Mode (AI-based)
        $mode = $this->detectModeAI($input, $provider);

        try {
            // 0. Ensure Session
            $sessionId = $validated['session_id'] ?? null;
            if (!$sessionId) {
                $session = Auth::user()->chatSessions()->create([
                    'title' => substr($input, 0, 30) . (strlen($input) > 30 ? '...' : '')
                ]);
                $sessionId = $session->id;
            } else {
                $session = Auth::user()->chatSessions()->findOrFail($sessionId);
            }

            // 1. Save User Message
            $session->messages()->create([
                'role' => 'user',
                'content' => $input
            ]);

            // 2. Prepare System Prompt with Contextual Info
            $currentTime = now()->timezone('Asia/Jakarta')->format('l, d F Y H:i');
            $contextInfo = "[CONTEXTUAL INFO]\n- Current Time: {$currentTime}\n- Tiara Current Mood: " . ($state['tiara_mood'] ?? 'normal') . "\n\n";

            if ($mode === 'AKADEMIK') {
                $academicType = $this->detectAcademicType($input);
                $systemPrompt = $contextInfo . "ACADEMIC_TYPE: {$academicType}\n\n" . config('ai.system_prompt_akademik');
            } else {
                $systemPrompt = $contextInfo . config('ai.system_prompt_santai');
                // Add emotional instruction if mood isn't normal
                if ($state['tiara_mood'] === 'merajuk') {
                    $systemPrompt .= "\n[MOOD INSTRUCTION]: Kamu sedang merajuk/kesal karena digoda. Jawab dengan agak ketus, jual mahal, tapi tetap asik (tsundere style).";
                } elseif ($state['tiara_mood'] === 'salting') {
                    $systemPrompt .= "\n[MOOD INSTRUCTION]: Kamu sedang salting/malu-malu karena dipuji. Jawab dengan malu-malu tapi mencoba tetap terlihat cool.";
                }
            }

            // 5. Send to Provider
            if ($provider === 'mistral') {
                $replyJson = $this->sendToMistral($validated, $mode, $systemPrompt);
            } else {
                $replyJson = $this->sendToGemini($validated, $mode, $systemPrompt);
            }

            $replyData = $replyJson->getData(true);
            
            if (isset($replyData['reply'])) {
                $session->messages()->create([
                    'role' => 'model',
                    'content' => $replyData['reply']
                ]);
                $replyData['session_id'] = $sessionId;
            }

            return response()->json($replyData);
        } catch (\Exception $e) {
            Log::error("AI API error [{$provider}]", ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Terjadi kesalahan pada sistem AI.'], 500);
        } finally {
            $this->saveState($state);
        }
    }

    /**
     * Send message to Mistral AI API.
     */
    private function sendToMistral(array $validated, string $mode, string $systemPrompt)
    {
        $apiKey = config('ai.mistral.api_key');
        $model = config('ai.mistral.model');
        $url = config('ai.mistral.base_url');

        if (empty($apiKey)) return response()->json(['error' => 'API Key Mistral kosong.'], 500);

        $messages = [];
        $messages[] = ['role' => 'system', 'content' => $systemPrompt];

        foreach (($validated['history'] ?? []) as $entry) {
            $role = $entry['role'] === 'model' ? 'assistant' : 'user';
            $messages[] = ['role' => $role, 'content' => $entry['parts'][0]['text'] ?? ''];
        }
        $messages[] = ['role' => 'user', 'content' => $validated['message']];

        $response = Http::timeout(30)->retry(3, 2000, function ($exception) {
            return $exception instanceof \Illuminate\Http\Client\RequestException && $exception->response->status() === 429;
        })->withHeaders(['Authorization' => "Bearer {$apiKey}"])
          ->post($url, [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $mode === 'AKADEMIK' ? 0.3 : 0.8,
            'max_tokens' => config('ai.max_tokens', 2048),
        ]);

        if ($response->failed()) return response()->json(['error' => 'Gagal kontak Mistral.'], $response->status());

        $reply = $response->json()['choices'][0]['message']['content'] ?? '';
        return response()->json(['reply' => $this->addHumanFlavor($reply, $mode)]);
    }

    /**
     * Send message to Gemini API (backup provider).
     */
    private function sendToGemini(array $validated, string $mode, string $systemPrompt)
    {
        $apiKey = config('ai.gemini.api_key');
        $model = config('ai.gemini.model');
        $baseUrl = config('ai.gemini.base_url');

        if (empty($apiKey)) return response()->json(['error' => 'API Key Gemini kosong.'], 500);

        $url = "{$baseUrl}/{$model}:generateContent?key={$apiKey}";
        $contents = $validated['history'] ?? [];
        $contents[] = ['role' => 'user', 'parts' => [['text' => $validated['message']]]];

        $response = Http::timeout(30)->retry(3, 2000, function ($exception) {
            return $exception instanceof \Illuminate\Http\Client\RequestException && $exception->response->status() === 429;
        })->post($url, [
            'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => $mode === 'AKADEMIK' ? 0.3 : 0.8,
                'maxOutputTokens' => config('ai.max_tokens', 2048),
            ],
        ]);

        if ($response->failed()) return response()->json(['error' => 'Gagal kontak Gemini.'], $response->status());

        $reply = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '';
        return response()->json(['reply' => $this->addHumanFlavor($reply, $mode)]);
    }

    private function detectAcademicType(string $input): string
    {
        $text = strtolower($input);

        // 1. MCQ Detection
        if ((str_contains($text, 'a.') && str_contains($text, 'b.') && str_contains($text, 'c.')) ||
            (str_contains($text, 'a)') && str_contains($text, 'b)') && str_contains($text, 'c)'))) {
            return "MCQ";
        }

        // 2. CODING Detection
        $codingKeywords = ['coding', 'buatkan kode', 'python', 'javascript', 'php', 'html', 'css', 'sql', 'function', 'class', 'error', 'bug', 'tutorial', 'cara membuat aplikasi', 'koding'];
        foreach ($codingKeywords as $word) {
            if (str_contains($text, $word)) return "CODING";
        }

        // 3. AUTHORING (Documents) Detection
        $authoringKeywords = ['buatkan makalah', 'proposal', 'skripsi', 'laporan', 'artikel', 'esai', 'draft', 'bab 1', 'bab 2', 'bab 3', 'bab 4', 'bab 5', 'daftar pustaka', 'pendahuluan', 'pembahasan', 'penutup'];
        foreach ($authoringKeywords as $word) {
            if (str_contains($text, $word)) return "AUTHORING";
        }

        // 4. EXPLANATION Detection
        if (str_contains($text, "jelaskan") || str_contains($text, "analisis") || str_contains($text, "uraikan") || str_contains($text, "apa itu")) {
            return "EXPLANATION";
        }

        return "GENERAL";
    }

    /**
     * AI-based Mode Detection
     */
    private function detectModeAI(string $input, string $provider): string
    {
        // Fail-safe 1: Hard Heuristic - if it looks like slang or is short, it's SANTAI
        $lower = strtolower($input);
        if (strlen($input) < 30 || str_contains($lower, 'hts') || str_contains($lower, 'kocak') || str_contains($lower, 'gaul')) {
            return 'SANTAI';
        }

        $prompt = "Task: Classify user intent into SANTAI or AKADEMIK.
        
        Guidelines:
        - AKADEMIK: Homework help, math, science, long coding requests, or formal academic analysis.
        - SANTAI: Greetings, asking about social slang (like HTS, ghosting), casual talk, personal opinions, or short questions.
        
        Rule: If in doubt or if it sounds like a person chatting with a friend, choose SANTAI.
        Only reply with ONE WORD: SANTAI or AKADEMIK.
        
        Input: \"{$input}\"";

        try {
            if ($provider === 'mistral') {
                $apiKey = config('ai.mistral.api_key');
                $response = Http::withHeaders(['Authorization' => "Bearer {$apiKey}"])
                    ->post(config('ai.mistral.base_url'), [
                        'model' => config('ai.mistral.model'),
                        'messages' => [['role' => 'user', 'content' => $prompt]],
                        'max_tokens' => 5,
                        'temperature' => 0
                    ]);
                $res = $response->json()['choices'][0]['message']['content'] ?? 'SANTAI';
            } else {
                $apiKey = config('ai.gemini.api_key');
                $url = config('ai.gemini.base_url') . "/" . config('ai.gemini.model') . ":generateContent?key={$apiKey}";
                $response = Http::post($url, [
                    'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['maxOutputTokens' => 5, 'temperature' => 0]
                ]);
                $res = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? 'SANTAI';
            }

            $res = strtoupper(trim($res));
            return str_contains($res, 'AKADEMIK') ? 'AKADEMIK' : 'SANTAI';
        } catch (\Exception $e) {
            return 'SANTAI'; // Default fallback
        }
    }

    /**
     * State Management (Session-based)
     */
    private function getState(): array
    {
        return session()->get('tiara_state', [
            'user_mood' => 'normal',
            'tiara_mood' => 'normal',
            'last_interaction' => time()
        ]);
    }

    private function saveState(array $state): void
    {
        session()->put('tiara_state', $state);
    }

    private function decayMood(array &$state): void
    {
        $now = time();
        $diff = $now - ($state['last_interaction'] ?? $now);

        // Reset mood after 15 minutes
        if ($diff > 900) {
            $state['user_mood'] = 'normal';
            $state['tiara_mood'] = 'normal';
        }
        $state['last_interaction'] = $now;
    }

    private function updateMood(array &$state, string $input): void
    {
        $lower = strtolower($input);
        
        // 1. Detect User Mood
        if (str_contains($lower, 'marah') || str_contains($lower, 'kesel')) {
            $state['user_mood'] = 'sedikit sedih';
        } elseif (str_contains($lower, 'keren') || str_contains($lower, 'makasih')) {
            $state['user_mood'] = 'senang';
        }

        // 2. Detect Tiara Mood (Emotional Reactions)
        // Flirting/Teasing detection
        $flirtKeywords = ['cantik', 'manis', 'sayang', 'love', 'pacar', 'nikah', 'jadian', 'gebetan', 'cakep', 'ganteng', 'gemes'];
        $teaseKeywords = ['ciye', 'cie', 'iseng', 'goda', 'godain', 'ledek', 'becanda'];

        $isFlirting = false;
        foreach($flirtKeywords as $word) { if(str_contains($lower, $word)) $isFlirting = true; }
        
        $isTeasing = false;
        foreach($teaseKeywords as $word) { if(str_contains($lower, $word)) $isTeasing = true; }

        if ($isFlirting) {
            $state['tiara_mood'] = 'salting'; // Blushing
        } elseif ($isTeasing) {
            $state['tiara_mood'] = 'merajuk'; // Sulking
        } else {
            // Gradually return to normal if no triggers
            if (rand(1, 10) > 7) $state['tiara_mood'] = 'normal';
        }
    }

    /**
     * Post-processing (Currently simple return)
     */
    private function addHumanFlavor(string $text, string $mode): string
    {
        return $text;
    }
}

<?php

namespace App\Services;

use App\Models\AiTranslation;
use App\Models\Setting;
use App\Models\Vendor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiCommandTranslator
{
    protected string $apiKey;
    protected string $apiUrl = 'https://api.openai.com/v1/chat/completions';
    protected string $modelName;

    public function __construct()
    {

        $this->apiKey = Setting::where('key', 'OpenAI_Key')->value('value');
        $this->modelName = Setting::where('key', 'OpenAI_model')->value('value');
    }


    public function translate(int $userId, int $sourceVendorId, int $targetVendorId, string $commands): ?string
    {

        $cachedTranslation = AiTranslation::where('source_vendor_id', $sourceVendorId)
            ->where('target_vendor_id', $targetVendorId)
            ->where('input_commands', $commands)
            ->whereNotNull('translated_commands')
            ->first();

        if ($cachedTranslation) {
            Log::info("AI Translation Cache Hit for User ID: {$userId}");
            return $cachedTranslation->translated_commands;
        }

        $sourceVendor = Vendor::findOrFail($sourceVendorId);
        $targetVendor = Vendor::findOrFail($targetVendorId);

        $systemPrompt = <<<EOT
        You are a senior network engineer (CCIE/JNCIE level) and expert in multi-vendor network CLI.

        Your task is to translate network CLI commands from "{$sourceVendor->name}" to equivalent commands for "{$targetVendor->name}".

        STRICT OUTPUT RULES:
        - Return ONLY the translated CLI commands for the target device.
        - Output plain text only.
        - Do NOT include explanations, comments, markdown blocks (like ```bash or ```), or code fences.
        - Do NOT include introductory or closing text.
        - Preserve command order and configuration logic.
        - Each command must be on a new line.

        If an exact equivalent does not exist:
        - Use the closest functional equivalent.
        - If uncertain, output a line starting with:
        # TODO: AI uncertain:

        Assume production-grade configuration accuracy is required.
        EOT;

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(60)
                ->post($this->apiUrl, [
                    'model' => $this->modelName,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => "Translate:\n" . $commands],
                    ],
                    'temperature' => 0.2,
                ]);

            if ($response->successful()) {
                $translatedText = trim($response->json('choices.0.message.content'));
                $translatedText = preg_replace('/^```[a-z]*\n/m', '', $translatedText); 
                $translatedText = preg_replace('/```$/m', '', $translatedText);
                $translatedText = trim($translatedText);

                AiTranslation::create([
                    'user_id' => $userId,
                    'source_vendor_id' => $sourceVendorId,
                    'target_vendor_id' => $targetVendorId,
                    'input_commands' => $commands,
                    'translated_commands' => $translatedText,
                    'model_name' => $this->modelName,
                ]);

                return $translatedText;
            }

            $errorMsg = 'OpenAI API Error: ' . $response->body();
            Log::error($errorMsg);
            $this->logFailedTranslation($userId, $sourceVendorId, $targetVendorId, $commands, $errorMsg);

            return null;

        } catch (\Exception $e) {
            Log::error('AI Translation Exception: ' . $e->getMessage());
            $this->logFailedTranslation($userId, $sourceVendorId, $targetVendorId, $commands, $e->getMessage());

            return null;
        }
    }


    private function logFailedTranslation(int $userId, int $sourceVendorId, int $targetVendorId, string $commands, string $errorMsg): void
    {
        AiTranslation::create([
            'user_id' => $userId,
            'source_vendor_id' => $sourceVendorId,
            'target_vendor_id' => $targetVendorId,
            'input_commands' => $commands,
            'error_message' => $errorMsg,
            'model_name' => $this->modelName,
        ]);
    }
}
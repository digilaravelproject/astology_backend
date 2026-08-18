<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Throwable;

class ContentSanitizerService
{
    public const REDACTED_PLACEHOLDER = '[Removed by Admin]';

    /**
     * Common TLDs for email and web link detection.
     */
    protected const TLDS = 'com|in|org|net|co|io|edu|gov|xyz|info|live|me|online|tech|ai|dev|app|co\.in|org\.in|gov\.in';

    /**
     * Popular Indian & global UPI Virtual Payment Address (VPA) providers.
     */
    protected const UPI_HANDLES = 'okhdfcbank|okaxis|oksbi|okicici|paytm|ybl|ibl|apl|axl|upi|sbi|hdfcbank|icici|kotak|postbank|barodampay|fbl|axisbank|airtel';

    /**
     * English & Hinglish number dictionary for spoken/written number evasion.
     */
    protected const NUMBER_WORDS = 'zero|one|two|three|four|five|six|seven|eight|nine|ek|do|teen|tin|char|chaar|paanch|panch|chhe|che|saat|sat|aath|ath|nau|nou|shunya|sunya';

    /**
     * Sanitize input message text by detecting and redacting contact details.
     *
     * @param string|null $text
     * @return string|null
     */
    public static function sanitize(?string $text): ?string
    {
        if ($text === null || trim($text) === '') {
            return $text;
        }

        // Fast path: If text doesn't contain any trigger characters/keywords, return immediately
        if (!self::hasPossibleContactTriggers($text)) {
            return $text;
        }

        $sanitized = $text;
        $detectedTypes = [];

        // 1. Redact Messaging & Social URLs (wa.me, api.whatsapp.com, t.me, etc.)
        $sanitized = preg_replace_callback(
            '/(?:https?:\/\/)?(?:www\.)?(?:wa\.me|api\.whatsapp\.com|chat\.whatsapp\.com|t\.me|telegram\.me|instagram\.com|fb\.me)\/[a-zA-Z0-9_.\-\/]+/i',
            function () use (&$detectedTypes) {
                $detectedTypes[] = 'social_link';
                return self::REDACTED_PLACEHOLDER;
            },
            $sanitized
        );

        // 2. Redact UPI IDs & Payment Handles (e.g. 9876543210@paytm, user@okaxis)
        $upiRegex = '/(?i)\b[a-zA-Z0-9.\-_]{2,64}@(?:' . self::UPI_HANDLES . ')\b/';
        $sanitized = preg_replace_callback($upiRegex, function () use (&$detectedTypes) {
            $detectedTypes[] = 'upi_id';
            return self::REDACTED_PLACEHOLDER;
        }, $sanitized);

        // 3. Redact Direct & Obfuscated Email Addresses
        // Handles: user@domain.com, user [at] domain [dot] com, user(at)gmail.com, user at domain dot com
        $emailRegex = '/(?i)\b[A-Za-z0-9._%+-]+\s*(?:@|\[at\]|\(at\)|\s+at\s+)\s*[A-Za-z0-9.-]+\s*(?:\.|\bdot\b|\[dot\]|\(dot\)|\s+dot\s+)\s*(?:' . self::TLDS . ')\b/';
        $sanitized = preg_replace_callback($emailRegex, function () use (&$detectedTypes) {
            $detectedTypes[] = 'email';
            return self::REDACTED_PLACEHOLDER;
        }, $sanitized);

        // 4. Redact Spelled-out English & Hinglish Digit Sequences
        // Handles: "nine eight seven six five four three two one zero", "nau aath saat chhe paanch char teen do ek shunya", mixed
        $wordsRegex = '/(?i)\b(?:' . self::NUMBER_WORDS . ')(?:\s+(?:' . self::NUMBER_WORDS . ')){5,}\b/';
        $sanitized = preg_replace_callback($wordsRegex, function () use (&$detectedTypes) {
            $detectedTypes[] = 'word_number';
            return self::REDACTED_PLACEHOLDER;
        }, $sanitized);

        // 5. Redact Social Handles & Messaging Usernames with context
        // Handles: "insta: @username", "telegram: @username", "whatsapp: 98765...", "ig id: astro_rahul"
        $socialHandleRegex = '/(?i)\b(?:instagram|insta|ig|telegram|tg|snapchat|snap|whatsapp|wp|wa)\s*(?:id|handle|pe|par|username|user|account)?\s*[:=-]?\s*[@]?[a-zA-Z0-9._]{3,32}\b/';
        $sanitized = preg_replace_callback($socialHandleRegex, function ($match) use (&$detectedTypes) {
            if (preg_match('/(?i)\b(?:id|handle|pe|par|username|user|account)?\s*[:=-]\s*[@]?[a-zA-Z0-9._]{3,32}\b/', $match[0]) || str_contains($match[0], '@')) {
                $detectedTypes[] = 'social_handle';
                return self::REDACTED_PLACEHOLDER;
            }
            return $match[0];
        }, $sanitized);

        // 6. Redact Direct, Spaced, Delimited Phone Numbers
        // Matches sequences containing 10-15 numeric digits separated by spaces, dots, hyphens, slashes, pipes, tildes, asterisks
        $phonePattern = '/(?:\+?\d{1,4}[\s.\-_*\/|~()]*)?(?:\b\d[\d\s.\-_*\/|~()]{8,25}\d\b)/';
        $sanitized = preg_replace_callback($phonePattern, function ($matches) use (&$detectedTypes) {
            $rawMatch = $matches[0];
            $digitsOnly = preg_replace('/\D/', '', $rawMatch);
            $digitCount = strlen($digitsOnly);

            // False positive guards:
            // - Dates (DD/MM/YYYY or YYYY-MM-DD) have 8 digits
            // - Times (10:30, 10:30:45) have 4-6 digits
            // - Pincodes (400001) have 6 digits
            // - House numbers / Astrology charts (7th house, 10th house) have 1-2 digits
            $hasIntlPrefix = str_starts_with(trim($rawMatch), '+') || str_starts_with(trim($rawMatch), '00');

            if ($digitCount >= 10 && $digitCount <= 15) {
                $detectedTypes[] = 'phone';
                return self::REDACTED_PLACEHOLDER;
            }

            if ($hasIntlPrefix && $digitCount >= 8 && $digitCount <= 15) {
                $detectedTypes[] = 'intl_phone';
                return self::REDACTED_PLACEHOLDER;
            }

            return $rawMatch;
        }, $sanitized);

        // 7. Redact Contiguous Alphanumeric / Leetspeak Phone Evasions (e.g. "987654321o", "98765432l0")
        $leetspeakPattern = '/\b[0-9a-zA-Z]{10,14}\b/';
        $sanitized = preg_replace_callback($leetspeakPattern, function ($matches) use (&$detectedTypes) {
            $token = $matches[0];
            
            // Normalize common leetspeak substitutions
            $normalized = str_ireplace(['o', 'i', 'l', 's'], ['0', '1', '1', '5'], $token);
            
            // Check if normalized token is purely numeric with 10-14 digits
            if (ctype_digit($normalized) && strlen($normalized) >= 10 && strlen($normalized) <= 14) {
                $actualNumericCount = strlen(preg_replace('/\D/', '', $token));
                // Only redact if at least 70% of chars were actual numbers
                if ($actualNumericCount >= 7) {
                    $detectedTypes[] = 'leetspeak_phone';
                    return self::REDACTED_PLACEHOLDER;
                }
            }

            return $token;
        }, $sanitized);

        // 8. Clean up multiple adjacent placeholders & normalize spacing
        $placeholderEscaped = preg_quote(self::REDACTED_PLACEHOLDER, '/');
        $sanitized = preg_replace('/(?:' . $placeholderEscaped . '\s*)+/', self::REDACTED_PLACEHOLDER . ' ', $sanitized);
        $sanitized = trim($sanitized);

        // 9. Background Security Audit Logging
        if (!empty($detectedTypes)) {
            try {
                Log::warning('Contact leakage detected and redacted in chat.', [
                    'detected_types'  => array_values(array_unique($detectedTypes)),
                    'original_length' => strlen($text),
                ]);
            } catch (Throwable) {
                // Prevent log failures from affecting messaging
            }
        }

        return $sanitized;
    }

    /**
     * Fast check to quickly bypass non-contact text messages with minimum CPU cycles.
     *
     * @param string $text
     * @return bool
     */
    protected static function hasPossibleContactTriggers(string $text): bool
    {
        return (bool) preg_match(
            '/[\d@\[\(|~*\/]|wa\.me|t\.me|upi|paytm|\bat\b|\bdot\b|' . self::NUMBER_WORDS . '|insta|telegram|whatsapp/i',
            $text
        );
    }
}

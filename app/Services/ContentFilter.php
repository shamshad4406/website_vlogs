<?php

namespace App\Services;

use App\Models\BannedWord;
use Illuminate\Support\Facades\Cache;

/**
 * Launch-grade spam and abuse filter for user text: a banned-word list from
 * the admin panel, plus link limits for new accounts. Not a replacement for
 * human moderation, but it stops the obvious cases automatically.
 */
class ContentFilter
{
    public const DECISION_OK = 'ok';

    public const DECISION_FLAG = 'flag';

    public const DECISION_BLOCK = 'block';

    /** @return array{decision: string, matched: array<int, string>, reason: ?string} */
    public function inspect(string $text, bool $trustedAuthor = false): array
    {
        $matched = [];
        $decision = self::DECISION_OK;
        $reason = null;

        $haystack = ' '.preg_replace('/\s+/', ' ', mb_strtolower($text)).' ';

        foreach ($this->words() as $word => $action) {
            if (str_contains($haystack, mb_strtolower($word))) {
                $matched[] = $word;

                if ($action === 'block') {
                    return [
                        'decision' => self::DECISION_BLOCK,
                        'matched' => $matched,
                        'reason' => 'Your message contains words that are not allowed here.',
                    ];
                }

                $decision = self::DECISION_FLAG;
                $reason = 'Held for moderation.';
            }
        }

        $linkCount = preg_match_all('~https?://|www\.~i', $text);

        if (! $trustedAuthor && $linkCount > 1) {
            $decision = self::DECISION_FLAG;
            $reason = 'New accounts may post at most one link; held for moderation.';
        }

        if ($linkCount > 4) {
            return [
                'decision' => self::DECISION_BLOCK,
                'matched' => $matched,
                'reason' => 'Too many links.',
            ];
        }

        if (mb_strlen($text) > 20 && preg_match('/(.)\1{14,}/u', $text)) {
            $decision = self::DECISION_FLAG;
            $reason = 'Held for moderation.';
        }

        return ['decision' => $decision, 'matched' => $matched, 'reason' => $reason];
    }

    /** True when the text may be published straight away. */
    public function autoApproves(string $text, bool $trustedAuthor = false): bool
    {
        return $this->inspect($text, $trustedAuthor)['decision'] === self::DECISION_OK;
    }

    /** @return array<string, string> word => action */
    protected function words(): array
    {
        return Cache::remember(
            'banned_words',
            600,
            fn () => BannedWord::query()->pluck('action', 'word')->all()
        );
    }
}

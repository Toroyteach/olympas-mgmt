<?php

namespace App\Services;

use App\Enums\ContentType;
use App\Enums\DispatchStatus;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\SocialPostDispatch;
use HamzaHassanM\LaravelSocialAutoPost\Facades\SocialMedia;
use Illuminate\Support\Facades\Log;

class SocialPostingService
{
    /**
     * Publish a single dispatch to its platform via the package.
     */
    public function publishDispatch(SocialPostDispatch $dispatch): bool
    {
        $dispatch->loadMissing(['post', 'account']);
        $post = $dispatch->post;
        $account = $dispatch->account;

        if (! $account->is_active) {
            $this->markFailed($dispatch, 'Account is disabled.');
            return false;
        }

        // Temporarily set the package config from this account's credentials
        $this->applyCredentials($account);

        try {
            $result = $this->sendToplatform($account->platform->value, $post);

            $dispatch->update([
                'status' => DispatchStatus::Published,
                'platform_post_id' => $result['id'] ?? $result['data']['id'] ?? null,
                'published_at' => now(),
                'attempts' => $dispatch->attempts + 1,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Social post dispatch failed', [
                'dispatch_id' => $dispatch->id,
                'platform' => $account->platform->value,
                'error' => $e->getMessage(),
            ]);

            $this->markFailed($dispatch, $e->getMessage());
            return false;
        }
    }

    /**
     * Route the post to the correct package method based on content type and platform.
     */
    protected function sendToPlatform(string $platform, SocialPost $post): mixed
    {
        $service = SocialMedia::platform($platform);
        $caption = $post->content;
        $url = $post->url ?? '';
        $media = $post->media_paths ?? [];

        return match ($post->content_type) {
            ContentType::Image => count($media) > 1 && method_exists($service, 'shareCarousel')
                ? $service->shareCarousel($caption, $media)
                : $service->shareImage($caption, $media[0] ?? $url),
            ContentType::Video => $service->shareVideo($caption, $media[0] ?? $url),
            ContentType::Carousel => method_exists($service, 'shareCarousel')
                ? $service->shareCarousel($caption, $media)
                : $service->shareImage($caption, $media[0] ?? $url),
            default => $service->share($caption, $url),
        };
    }

    /**
     * Apply the account's stored credentials to package config at runtime.
     */
    protected function applyCredentials(SocialAccount $account): void
    {
        $creds = $account->credentials ?? [];
        $platform = $account->platform->value;

        $mapping = $this->getCredentialMapping($platform);

        foreach ($mapping as $credKey => $configKey) {
            if (isset($creds[$credKey])) {
                config(["autopost.{$configKey}" => $creds[$credKey]]);
            }
        }
    }

    /**
     * Map credential keys to autopost config keys per platform.
     */
    protected function getCredentialMapping(string $platform): array
    {
        return match ($platform) {
            'facebook' => [
                'access_token' => 'facebook_access_token',
                'page_id' => 'facebook_page_id',
            ],
            'twitter' => [
                'bearer_token' => 'twitter_bearer_token',
                'api_key' => 'twitter_api_key',
                'api_secret' => 'twitter_api_secret',
                'access_token' => 'twitter_access_token',
                'access_token_secret' => 'twitter_access_token_secret',
            ],
            'linkedin' => [
                'access_token' => 'linkedin_access_token',
                'person_urn' => 'linkedin_person_urn',
                'organization_urn' => 'linkedin_organization_urn',
            ],
            'instagram' => [
                'access_token' => 'instagram_access_token',
                'account_id' => 'instagram_account_id',
            ],
            'tiktok' => [
                'access_token' => 'tiktok_access_token',
                'client_key' => 'tiktok_client_key',
                'client_secret' => 'tiktok_client_secret',
            ],
            'youtube' => [
                'api_key' => 'youtube_api_key',
                'access_token' => 'youtube_access_token',
                'channel_id' => 'youtube_channel_id',
            ],
            'pinterest' => [
                'access_token' => 'pinterest_access_token',
                'board_id' => 'pinterest_board_id',
            ],
            'telegram' => [
                'bot_token' => 'telegram_bot_token',
                'chat_id' => 'telegram_chat_id',
            ],
            default => [],
        };
    }

    protected function markFailed(SocialPostDispatch $dispatch, string $message): void
    {
        $dispatch->update([
            'status' => DispatchStatus::Failed,
            'error_message' => $message,
            'attempts' => $dispatch->attempts + 1,
        ]);
    }

    /**
     * Get the credential fields definition for a given platform (used by Filament forms).
     */
    public static function getCredentialFields(string $platform): array
    {
        return match ($platform) {
            'facebook' => ['access_token', 'page_id'],
            'twitter' => ['bearer_token', 'api_key', 'api_secret', 'access_token', 'access_token_secret'],
            'linkedin' => ['access_token', 'person_urn', 'organization_urn'],
            'instagram' => ['access_token', 'account_id'],
            'tiktok' => ['access_token', 'client_key', 'client_secret'],
            'youtube' => ['api_key', 'access_token', 'channel_id'],
            'pinterest' => ['access_token', 'board_id'],
            'telegram' => ['bot_token', 'chat_id'],
            default => [],
        };
    }
}

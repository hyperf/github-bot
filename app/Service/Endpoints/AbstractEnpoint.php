<?php

declare(strict_types=1);
/**
 * @contact huangzhwork@gmail.com
 * @license https://github.com/huangzhhui/github-bot/blob/master/LICENSE
 */
namespace App\Service\Endpoints;

use App\Service\EndpointInterface;
use App\Traits\ClientTrait;
use App\Traits\CommentTrait;
use App\Utils\GithubUrlBuilder;
use Hyperf\Utils\Str;

abstract class AbstractEnpoint implements EndpointInterface
{
    use ClientTrait;
    use CommentTrait;

    /**
     * Parse the target users from the body of request.
     */
    protected function parseTargetUsers(): array
    {
        $reviewers = [];
        $explodedBody = explode(' ', $this->body);
        foreach ($explodedBody as $user) {
            if (Str::startsWith($user, '@')) {
                $reviewers[] = substr($user, 1);
            }
        }
        return array_unique($reviewers);
    }

    protected function addApprovedComment($repository, $pullRequestId): void
    {
        try {
            $uri = GithubUrlBuilder::buildReviewsUrl($repository, $pullRequestId);
            $response = $this->getClient()->get($uri);
            if ($response->getStatusCode() === 200 && $content = $response->getBody()->getContents()) {
                $approvedUsers = [];
                $decodedBody = json_decode($content, true);
                foreach ($decodedBody ?? [] as $review) {
                    if (isset($review['user']['login'], $review['state']) && $review['state'] === 'APPROVED') {
                        $approvedUsers[$review['user']['login']] = '[' . $review['user']['login'] . '](' . $review['html_url'] . ')';
                    }
                }
                if ($approvedUsers) {
                    $comment = "[APPROVAL NOTIFIER] This pull-request is **APPROVED**\r\n\r\nThis pull-request has been approved by: " . implode(' ', $approvedUsers);
                    $this->addComment($comment);
                }
            }
        } catch (\Throwable $e) {
        }
    }
}

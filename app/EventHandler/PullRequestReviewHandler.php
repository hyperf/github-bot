<?php

declare(strict_types=1);
/**
 * @contact huangzhwork@gmail.com
 * @license https://github.com/huangzhhui/github-bot/blob/master/LICENSE
 */
namespace App\EventHandler;

use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;

class PullRequestReviewHandler extends AbstractHandler
{
    /**
     * @Inject
     * @var CommandManager
     */
    protected $commandManager;

    /**
     * @Inject
     * @var LoggerInterface
     */
    protected $logger;

    public function handle(RequestInterface $request): ResponseInterface
    {
        if (! $request instanceof \Hyperf\HttpServer\Contract\RequestInterface) {
            return $this->response();
        }
        $this->logger->debug('Receive a pull request review request.');
        $issue = $request->all();
        $comment = $request->input('review.body', []);
        if (! $issue || ! $comment) {
            $message = 'Invalid argument.';
            $this->logger->debug($message);
            return $this->response()->withStatus(400, $message);
        }
        $reviewerAssociation = $request->input('review.author_association');
        if ($reviewerAssociation !== 'MEMBER') {
            $this->logger->debug('Receive a request, but not valid user.');
            return $this->response()->withStatus(200);
        }
        $commands = $this->parseCommands($comment);
        if (! $commands) {
            $this->logger->debug('Receive a request, but no command.');
        }
        foreach ($commands as $command) {
            $this->commandManager->execute($command, $issue);
        }
        return $this->response()->withStatus(200);
    }

    protected function parseCommands(string $body): array
    {
        $commands = [];
        $delimiter = "\r\n";
        $comments = explode($delimiter, $body);
        foreach ($comments as $comment) {
            if ($this->commandManager->isValidCommand($comment)) {
                $commands[] = $comment;
            }
        }
        return $commands;
    }
}

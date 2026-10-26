<?php

declare(strict_types=1);

namespace CyberRoot\SerendibBank\Controllers;

use CyberRoot\SerendibBank\Core\Auth;
use CyberRoot\SerendibBank\Core\Database;
use CyberRoot\SerendibBank\Core\View;
use CyberRoot\SerendibBank\Models\Portal;

final class ChatController
{
    private const MAX_HISTORY = 24;

    public function history(): void
    {
        $this->json([
            'csrf' => Auth::csrfToken(),
            'messages' => $_SESSION['chat_history'] ?? [],
        ]);
    }

    public function reply(): void
    {
        if (!Auth::verifyCsrf($_POST['_csrf'] ?? null)) {
            $this->json(['error' => 'The conversation expired. Please reopen chat.'], 400);
            return;
        }

        $message = $_POST['message'] ?? null;
        if (!is_string($message) || !preg_match('//u', $message) || strlen($message) > 500 || trim($message) === '') {
            $this->json(['error' => 'Enter a message of up to 500 characters.'], 422);
            return;
        }

        $now = time();
        $recent = array_values(array_filter(
            $_SESSION['chat_times'] ?? [],
            static fn (mixed $time): bool => is_int($time) && $time > $now - 60
        ));
        if (count($recent) >= 12) {
            $this->json(['error' => 'Please wait a moment before sending another message.'], 429);
            return;
        }
        $recent[] = $now;
        $_SESSION['chat_times'] = $recent;

        $message = trim($message);
        $answer = $this->answer(strtolower($message));
        $history = $_SESSION['chat_history'] ?? [];
        $history[] = ['role' => 'customer', 'text' => $message];
        $history[] = ['role' => 'assistant', 'text' => $answer['text'], 'action' => $answer['action']];
        $_SESSION['chat_history'] = array_slice($history, -self::MAX_HISTORY);

        $this->json(['reply' => $answer]);
    }

    public function transcript(): void
    {
        View::render('chat-transcript', [
            'title' => 'Conversation | SerendibBank',
            'messages' => $_SESSION['chat_history'] ?? [],
        ]);
    }

    public function escalate(): void
    {
        $user = Auth::requireRole('customer');
        if ($user === null) {
            return;
        }
        if (!Auth::verifyCsrf($_POST['_csrf'] ?? null)) {
            http_response_code(400);
            View::render('error', ['title' => 'Please try again', 'message' => 'Reload the conversation and try again.']);
            return;
        }
        $customerMessages = array_values(array_filter(
            $_SESSION['chat_history'] ?? [],
            static fn (mixed $item): bool => is_array($item) && ($item['role'] ?? null) === 'customer' && is_string($item['text'] ?? null)
        ));
        if ($customerMessages === []) {
            http_response_code(422);
            View::render('error', ['title' => 'No conversation yet', 'message' => 'Ask a question before sending the conversation to our team.']);
            return;
        }
        $parts = array_map(static fn (array $item): string => $item['text'], array_slice($customerMessages, -7));
        $transcript = implode("\n", $parts);
        (new Portal(Database::connect()))->createSupportRequest(
            (int) $user['customer_id'], 'digital', 'Conversation with digital assistant', $transcript
        );
        $_SESSION['flash_success'] = 'Your conversation was sent to our team.';
        header('Location: /dashboard', true, 303);
    }

    /** @return array{text: string, action: array{label: string, url: string}} */
    private function answer(string $message): array
    {
        if ($this->has($message, ['lost card', 'stolen card', 'fraud', 'suspicious', 'unauthorised', 'unauthorized'])) {
            return ['text' => 'If your card is lost or you notice an unfamiliar transaction, contact us through a verified channel promptly. Never share your PIN, password or one-time code in chat.', 'action' => ['label' => 'Get card support', 'url' => '/support']];
        }
        if ($this->has($message, ['balance', 'transaction', 'statement', 'my account'])) {
            return ['text' => 'Sign in to see your own account balances and recent activity. I cannot access or display account details in this chat.', 'action' => ['label' => 'Open online banking', 'url' => '/dashboard']];
        }
        if ($this->has($message, ['transfer', 'send money', 'move money', 'payment'])) {
            return ['text' => 'You can move money between your SerendibBank accounts after signing in. Check both accounts and the amount before you submit.', 'action' => ['label' => 'Go to transfers', 'url' => '/transfer']];
        }
        if ($this->has($message, ['credit card', 'debit card', 'apply for a card', 'new card'])) {
            return ['text' => 'Compare our debit and credit cards, then send an application from online banking. A team member will review the request.', 'action' => ['label' => 'Explore cards', 'url' => '/cards']];
        }
        if ($this->has($message, ['savings', 'deposit', 'open an account', 'new account'])) {
            return ['text' => 'Our personal banking page explains everyday accounts and savings. You can send an account application after signing in.', 'action' => ['label' => 'Explore accounts', 'url' => '/personal']];
        }
        if ($this->has($message, ['branch', 'atm', 'location', 'opening hours'])) {
            return ['text' => 'Find branches and ATMs by city in our location finder. Please confirm opening hours before you travel.', 'action' => ['label' => 'Find a location', 'url' => '/locations']];
        }
        if ($this->has($message, ['offer', 'promotion', 'dining', 'travel'])) {
            return ['text' => 'You can browse card offers by category. Availability and terms may change, so check the details before making plans.', 'action' => ['label' => 'Browse offers', 'url' => '/offers']];
        }
        return ['text' => 'I can help you find accounts, cards, transfers, branches and support. For a question about your own account, sign in and send a secure message to our team.', 'action' => ['label' => 'Visit help centre', 'url' => '/support']];
    }

    /** @param array<int, string> $words */
    private function has(string $message, array $words): bool
    {
        foreach ($words as $word) {
            if (str_contains($message, $word)) {
                return true;
            }
        }
        return false;
    }

    /** @param array<string, mixed> $payload */
    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($payload, JSON_THROW_ON_ERROR);
    }
}

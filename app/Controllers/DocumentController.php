<?php

declare(strict_types=1);

namespace CyberRoot\SerendibBank\Controllers;

use CyberRoot\SerendibBank\Core\View;

final class DocumentController
{
    private const DOCUMENTS = [
        ['file' => 'everyday-account', 'category' => 'Accounts', 'title' => 'Everyday account', 'summary' => 'An overview of daily banking, account access and important information.'],
        ['file' => 'debit-card', 'category' => 'Cards', 'title' => 'Serendib Debit', 'summary' => 'How your debit card works with an eligible current or savings account.'],
        ['file' => 'credit-card', 'category' => 'Cards', 'title' => 'Serendib Credit', 'summary' => 'Key information about credit card use, payments and support.'],
    ];

    public function index(): void
    {
        View::render('documents', [
            'title' => 'Product information | SerendibBank',
            'documents' => self::DOCUMENTS,
        ]);
    }

    public function view(): void
    {
        $file = $_GET['file'] ?? null;
        if (!is_string($file) || strlen($file) > 100 || preg_match('#^[a-z0-9./_-]+$#iD', $file) !== 1) {
            $this->notFound();
            return;
        }

        $contentRoot = realpath(dirname(__DIR__) . '/Content');
        $path = realpath(dirname(__DIR__) . '/Content/documents/' . $file . '.php');
        if ($contentRoot === false || $path === false ||
            !str_starts_with($path, $contentRoot . DIRECTORY_SEPARATOR) || !is_file($path)) {
            $this->notFound();
            return;
        }

        ob_start();
        try {
            require $path;
            $body = ob_get_clean();
        } catch (\Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }

        View::render('document-view', [
            'title' => 'Product information | SerendibBank',
            'body' => $body,
        ]);
    }

    private function notFound(): void
    {
        http_response_code(404);
        View::render('error', ['title' => 'Document unavailable', 'message' => 'We could not find that document.']);
    }
}

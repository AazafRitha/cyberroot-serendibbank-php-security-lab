<?php

declare(strict_types=1);

use CyberRoot\SerendibBank\Controllers\DashboardController;
use CyberRoot\SerendibBank\Controllers\HomeController;
use CyberRoot\SerendibBank\Controllers\PagesController;
use CyberRoot\SerendibBank\Controllers\AuthController;
use CyberRoot\SerendibBank\Controllers\TransferController;
use CyberRoot\SerendibBank\Controllers\ApplicationController;
use CyberRoot\SerendibBank\Controllers\SupportController;
use CyberRoot\SerendibBank\Controllers\StaffController;
use CyberRoot\SerendibBank\Controllers\ChatController;
use CyberRoot\SerendibBank\Controllers\BankingController;
use CyberRoot\SerendibBank\Controllers\ApiController;
use CyberRoot\SerendibBank\Core\Router;
use CyberRoot\SerendibBank\Core\View;
use CyberRoot\SerendibBank\Core\Auth;

require dirname(__DIR__) . '/app/bootstrap.php';

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self' 'unsafe-inline'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");

$router = new Router();
$router->get('/', [new HomeController(), 'index']);
$router->get('/dashboard', [new DashboardController(), 'index']);
$pages = new PagesController();
$router->get('/personal', [$pages, 'personal']);
$router->get('/cards', [$pages, 'cards']);
$router->get('/offers', [$pages, 'offers']);
$router->get('/offers/search', [$pages, 'offerSearch']);
$router->get('/digital', [$pages, 'digital']);
$router->get('/digital/continue', [$pages, 'continueToBanking']);
$router->get('/locations', [$pages, 'locations']);
$router->get('/support', [$pages, 'support']);
$auth = new AuthController();
$router->get('/login', [$auth, 'showLogin']);
$router->post('/login', [$auth, 'login']);
$router->get('/verify', [$auth, 'showOtp']);
$router->post('/verify', [$auth, 'verifyOtp']);
$router->post('/logout', [$auth, 'logout']);
$transfer = new TransferController();
$router->get('/transfer', [$transfer, 'show']);
$router->post('/transfer', [$transfer, 'submit']);
$applications = new ApplicationController();
$router->get('/apply/account', [$applications, 'account']);
$router->post('/apply/account', [$applications, 'submitAccount']);
$router->get('/apply/card', [$applications, 'card']);
$router->post('/apply/card', [$applications, 'submitCard']);
$support = new SupportController();
$router->get('/support/request', [$support, 'show']);
$router->post('/support/request', [$support, 'submit']);
$staff = new StaffController();
$router->get('/staff', [$staff, 'index']);
$router->post('/staff/applications/review', [$staff, 'reviewApplication']);
$router->post('/staff/support/resolve', [$staff, 'resolveSupport']);
$chat = new ChatController();
$router->get('/chat', [$chat, 'history']);
$router->post('/chat', [$chat, 'reply']);
$router->get('/chat/transcript', [$chat, 'transcript']);
$router->post('/chat/escalate', [$chat, 'escalate']);
$banking = new BankingController();
$router->get('/statement', [$banking, 'statement']);
$router->get('/locations/search', [$banking, 'branchSearch']);
$router->get('/transfer/repeat', [$banking, 'repeatTransfer']);
$api = new ApiController();
$router->get('/api/v1/accounts/transactions', [$api, 'accountTransactions']);
$router->get('/api/v1/support/requests', [$api, 'supportRequest']);
$router->post('/api/v1/applications/review', [$api, 'reviewApplication']);
$router->get('/api/v1/beneficiaries/lookup', [$api, 'beneficiaryLookup']);

try {
    Auth::start();
    $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
} catch (PDOException $exception) {
    error_log((string) $exception);
    http_response_code(503);
    View::render('error', ['title' => 'Service unavailable', 'message' => 'Account information is temporarily unavailable.']);
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);
    View::render('error', ['title' => 'Something went wrong', 'message' => 'Please try again.']);
}

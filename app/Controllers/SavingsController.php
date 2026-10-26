<?php

declare(strict_types=1);

namespace CyberRoot\SerendibBank\Controllers;

use CyberRoot\SerendibBank\Core\Auth;
use CyberRoot\SerendibBank\Core\View;

final class SavingsController
{
    private const INDICATIVE_RATE = 7.5;

    public function show(): void
    {
        $this->render();
    }

    public function calculate(): void
    {
        if (!Auth::verifyCsrf($_POST['_csrf'] ?? null)) {
            http_response_code(400);
            $this->render(null, 'Reload the calculator and try again.');
            return;
        }

        $principal = filter_var($_POST['principal'] ?? null, FILTER_VALIDATE_FLOAT);
        $months = filter_var($_POST['months'] ?? null, FILTER_VALIDATE_INT);
        $rate = filter_var($_POST['annual_rate'] ?? null, FILTER_VALIDATE_FLOAT);
        if ($principal === false || $principal < 1000 || $principal > 100000000 ||
            $months === false || $months < 1 || $months > 120 ||
            $rate === false || $rate < 0 || $rate > 100) {
            http_response_code(422);
            $this->render(null, 'Enter an amount from LKR 1,000 to 100,000,000 and a term from 1 to 120 months.');
            return;
        }

        // The submitted rate is used as quoted. The rate is supplied in the form.
        $interest = round($principal * ($rate / 100) * ($months / 12), 2);
        $this->render([
            'principal' => $principal,
            'months' => $months,
            'rate' => $rate,
            'interest' => $interest,
            'maturity' => round($principal + $interest, 2),
        ]);
    }

    /** @param array<string, float|int>|null $result */
    private function render(?array $result = null, ?string $error = null): void
    {
        View::render('savings-calculator', [
            'title' => 'Savings calculator | SerendibBank',
            'indicativeRate' => self::INDICATIVE_RATE,
            'result' => $result,
            'error' => $error,
        ]);
    }
}

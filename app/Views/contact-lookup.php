<section class="section soft-section">
    <div class="container portal-layout">
        <div class="form-panel">
            <span class="eyebrow">For existing customers</span>
            <h2>Use your details on file</h2>
            <p>Enter your account number to prepare a general enquiry with the contact details we have recorded.</p>
            <form class="bank-form" data-contact-lookup>
                <label for="contact-account-number">Account number</label>
                <input id="contact-account-number" name="account_number" type="text" inputmode="numeric" pattern="[0-9]{8,32}" autocomplete="off" required>
                <button class="button" type="submit">Find my details <span aria-hidden="true">→</span></button>
                <p class="field-help" role="status" aria-live="polite" data-contact-lookup-status></p>
            </form>
        </div>
    </div>
</section>

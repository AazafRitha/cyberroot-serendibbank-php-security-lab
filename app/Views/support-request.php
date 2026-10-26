<div class="container portal-shell">
    <div class="portal-topline"><a href="/support">← Back to help centre</a><span>Customer support</span></div>
    <section class="portal-header"><div><span class="eyebrow">Contact support</span><h1>How can we help?</h1><p>Send a message about your banking and follow its status in your dashboard.</p></div></section>
    <div class="portal-layout"><section class="form-panel"><h2>Your message</h2>
        <?php if (!empty($data['error'])): ?><div class="form-error" role="alert"><?= $escape($data['error']) ?></div><?php endif; ?>
        <form method="post" action="/support/request" enctype="multipart/form-data" class="bank-form">
            <input type="hidden" name="_csrf" value="<?= $escape($data['csrfToken']) ?>">
            <label for="support-category">What is this about?</label><select id="support-category" name="category" required><option value="">Select a topic</option><option value="account">My account</option><option value="card">My card</option><option value="digital">Online banking</option><option value="other">Something else</option></select>
            <label for="support-subject">Subject</label><input id="support-subject" name="subject" type="text" minlength="4" maxlength="160" required>
            <label for="support-message">How can we help?</label><textarea id="support-message" name="message" rows="7" minlength="10" maxlength="4000" required></textarea>
            <label for="support-attachment">Attachment <span class="label-optional">Optional</span></label><input id="support-attachment" name="attachment" type="file" accept=".png,.jpg,.jpeg,.pdf"><p class="field-help">Add a screenshot or document up to 2 MB.</p>
            <p class="field-help">Do not include your password, PIN or one-time code in this message.</p>
            <button class="button" type="submit">Send message <span aria-hidden="true">→</span></button>
        </form>
    </section><aside class="portal-aside"><span class="aside-icon" aria-hidden="true">?</span><h2>Need urgent card help?</h2><p>If your card is lost or you notice unfamiliar activity, contact the bank through a verified service channel immediately. A message here may not be reviewed right away.</p><a class="text-link" href="/locations">Find a branch <span aria-hidden="true">↗</span></a></aside></div>
</div>

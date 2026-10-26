<section class="hero error-page">
    <div class="eyebrow">CyberRoot SerendibBank</div>
    <h1><?= $escape($data['title'] ?? 'Error') ?></h1>
    <p><?= $escape($data['message'] ?? 'Please try again.') ?></p>
    <a class="button" href="/">Back to home</a>
</section>

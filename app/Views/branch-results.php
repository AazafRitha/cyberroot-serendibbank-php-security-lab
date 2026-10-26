<div class="container portal-shell">
    <div class="portal-topline"><a href="/locations">← All locations</a><span>Branch finder</span></div>
    <section class="portal-header"><div><span class="eyebrow">Branches &amp; ATMs</span><h1>Find a branch.</h1><p>Search by city to find a convenient location.</p></div></section>
    <form class="bank-form branch-search-form" method="get" action="/locations/search"><label for="branch-q">City</label><div><input id="branch-q" name="q" value="<?= $escape($data['query']) ?>" maxlength="80" placeholder="Try Colombo or Kandy"><button class="button" type="submit">Search</button></div></form>
    <?php if ($data['error']): ?><div class="form-error" role="alert"><?= $escape($data['error']) ?></div><?php endif; ?>
    <section class="portal-section" aria-labelledby="branch-results"><div class="portal-section-heading"><div><span class="eyebrow">Your search</span><h2 id="branch-results">Results</h2></div></div><div class="location-grid"><?php foreach ($data['branches'] as $branch): ?><article class="location-card"><span class="location-type">Branch</span><h3><?= $escape($branch['city']) ?></h3><p><?= $escape($branch['service_note']) ?></p></article><?php endforeach; ?></div><?php if ($data['query'] !== '' && $data['branches'] === [] && !$data['error']): ?><p>No branches match that city.</p><?php endif; ?></section>
</div>

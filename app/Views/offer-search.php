<div class="container portal-shell offer-search-page">
    <div class="portal-topline"><a href="/offers">← All card offers</a><span>Card offers</span></div>
    <section class="portal-header"><div><span class="eyebrow">Explore offers</span><h1>Find more to enjoy.</h1><p>Search dining, travel, shopping and wellbeing offers.</p></div></section>
    <form class="bank-form branch-search-form" method="get" action="/offers/search"><label for="offer-q">Search offers</label><div><input id="offer-q" name="q" value="<?= $escape($data['query']) ?>" maxlength="80" placeholder="Try dining or travel"><button class="button" type="submit">Search</button></div></form>
    <?php if ($data['query'] !== ''): ?><p class="offer-search-summary">Results for <strong><?= $data['labMode'] ? $data['query'] : $escape($data['query']) ?></strong></p><?php endif; ?>
    <div class="offer-grid offers-page-grid">
        <?php foreach ($data['offers'] as $offer): ?><article class="offer-card"><div class="offer-art offer-art-<?= $escape($offer['art']) ?>"><small><?= $escape(strtoupper($offer['category'])) ?></small></div><div class="offer-body"><span class="offer-tag"><?= $escape($offer['category']) ?></span><h3><?= $escape($offer['title']) ?></h3><p><?= $escape($offer['description']) ?></p></div></article><?php endforeach; ?>
    </div>
    <?php if ($data['offers'] === []): ?><p class="empty-filter">No offers match that search. Try another category.</p><?php endif; ?>
</div>

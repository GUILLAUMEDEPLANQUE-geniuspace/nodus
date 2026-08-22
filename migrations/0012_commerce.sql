-- Commerce : prix / note / votes / stock sur les produits.
-- Partage = URL canonique (pas un widget tiers).

alter table shop_products add column if not exists rating text not null default '0';
alter table shop_products add column if not exists votes int not null default 0;
alter table shop_products add column if not exists stock text not null default 'en stock';

update shop_products set rating = '4.8', votes = 128, stock = '12 pièces' where id = 'sp-op-1';
update shop_products set rating = '4.6', votes = 86, stock = 'sur commande' where id = 'sp-op-2';
update shop_products set rating = '5.0', votes = 40, stock = 'numérique' where id = 'sp-op-3';
update shop_products set rating = '4.9', votes = 21, stock = 'pièce unique' where id = 'sp-at-1';

-- Making-of labradorite = mode boutique (prix + note sur la fiche vidéo)
update node_media set mode = 'shop', access_kind = 'freemium', teaser_sec = 6,
       price = '180 €', views = 2100, rating = '4.9', ribbon = 'Pièce unique'
 where node_id = 'labradorite';
